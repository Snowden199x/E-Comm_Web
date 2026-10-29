<?php

namespace App\Services;

use App\Models\Ecommerce\Order;
use App\Models\Profiles\LogisticsTransportTrip;
use App\Models\Profiles\OrderFacilityStop;
use App\Models\Profiles\RiderTask;
use Illuminate\Support\Facades\DB;

class ShippingEarningsSettlementService
{
    /** Legacy per-stop credit, still used for orders created before rate snapshots existed. */
    public const RIDER_SHARE_CENTS_PER_HUB = 1000;

    public function __construct(private DeliveryFeeDistributionCalculator $distribution) {}

    public function settleDeliveredOrder(Order $order): void
    {
        if ($order->status !== 'delivered' || ! is_array($order->shipping_route_snapshot) || $order->shipping_fee <= 0) {
            return;
        }

        DB::transaction(function () use ($order) {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);
            if ($lockedOrder->status !== 'delivered' || DB::table('order_shipping_settlements')->where('order_id', $lockedOrder->id)->exists()) {
                return;
            }

            $snapshot = $lockedOrder->shipping_route_snapshot;
            $snapshotIds = array_map('intval', array_column($snapshot, 'facility_id'));
            $stops = OrderFacilityStop::query()->where('order_id', $lockedOrder->id)->orderBy('position')->get();
            $actualIds = $stops->pluck('facility_id')->map(fn ($id) => (int) $id)->all();
            $matchesQuote = $snapshotIds !== [] && $snapshotIds === $actualIds
                && $stops->every(fn ($stop) => $stop->arrived_at !== null && $stop->sorted_at !== null);

            $settlementId = DB::table('order_shipping_settlements')->insertGetId([
                'order_id' => $lockedOrder->id,
                'shipping_fee' => $lockedOrder->shipping_fee,
                'hub_count' => (int) $lockedOrder->shipping_hub_count,
                'rider_total' => 0,
                'vendo_commission' => 0,
                'logistics_remainder' => $lockedOrder->shipping_fee,
                'status' => $matchesQuote ? 'accrued' : 'review',
                'settled_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // A route change after checkout needs human review; do not credit a rider against a stale quote.
            if (! $matchesQuote) {
                return;
            }

            // Orders placed after the COD/fee-distribution change carry their rates; older orders keep the per-stop rule.
            if ($lockedOrder->rider_pay_rate !== null) {
                $this->accrueParcelSplit($lockedOrder, $stops, $settlementId);
            } else {
                $this->accrueLegacyPerStop($lockedOrder, $stops, $settlementId);
            }
        }, 3);
    }

    /**
     * New rule: one fixed rider payment per delivered parcel (to the delivery Rider only), Vendo takes a
     * percentage of the balance after that payment, and the Logistics Company keeps the rest. The rates
     * come from the snapshot saved on the order, never from the current settings.
     */
    private function accrueParcelSplit(Order $order, $stops, int $settlementId): void
    {
        $lastStop = $stops->last();
        $feeCents = (int) round(((float) $order->shipping_fee) * 100);
        $riderUserId = $this->deliveryRider($order, $lastStop);
        // No completed delivery task means no rider payment; that amount stays in the balance.
        $riderPayCents = $riderUserId ? (int) round(((float) $order->rider_pay_rate) * 100) : 0;

        $split = $this->distribution->split(
            $feeCents,
            $riderPayCents,
            (float) ($order->vendo_delivery_commission_rate ?? 0),
        );

        if ($riderUserId && $split['rider_cents'] > 0) {
            DB::table('order_shipping_earning_lines')->insertOrIgnore([
                'settlement_id' => $settlementId,
                'route_position' => $lastStop->position,
                'facility_id' => $lastStop->facility_id,
                'rider_user_id' => $riderUserId,
                'amount' => number_format($split['rider_cents'] / 100, 2, '.', ''),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('order_shipping_settlements')->where('id', $settlementId)->update([
            'rider_total' => number_format($split['rider_cents'] / 100, 2, '.', ''),
            'vendo_commission' => number_format($split['vendo_cents'] / 100, 2, '.', ''),
            'logistics_remainder' => number_format($split['logistics_cents'] / 100, 2, '.', ''),
            'updated_at' => now(),
        ]);
    }

    private function accrueLegacyPerStop(Order $lockedOrder, $stops, int $settlementId): void
    {
        $eligibleCents = min(
            (int) round(((float) $lockedOrder->shipping_fee) * 100),
            (int) $lockedOrder->shipping_hub_count * self::RIDER_SHARE_CENTS_PER_HUB,
        );
        $creditedCents = 0;
        foreach ($stops as $index => $stop) {
            $riderUserId = $this->responsibleRider($lockedOrder, $stops, $index, $stop);
            if (! $riderUserId || $creditedCents + self::RIDER_SHARE_CENTS_PER_HUB > $eligibleCents) {
                continue;
            }
            DB::table('order_shipping_earning_lines')->insertOrIgnore([
                'settlement_id' => $settlementId,
                'route_position' => $stop->position,
                'facility_id' => $stop->facility_id,
                'rider_user_id' => $riderUserId,
                'amount' => number_format(self::RIDER_SHARE_CENTS_PER_HUB / 100, 2, '.', ''),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $creditedCents += self::RIDER_SHARE_CENTS_PER_HUB;
        }

        DB::table('order_shipping_settlements')->where('id', $settlementId)->update([
            'rider_total' => number_format($creditedCents / 100, 2, '.', ''),
            'logistics_remainder' => number_format(((int) round(((float) $lockedOrder->shipping_fee) * 100) - $creditedCents) / 100, 2, '.', ''),
            'updated_at' => now(),
        ]);
    }

    /** The Rider who completed the delivery task at the destination Station Hub (the last route stop). */
    private function deliveryRider(Order $order, OrderFacilityStop $lastStop): ?int
    {
        $task = RiderTask::query()->with('rider.user')->where('order_id', $order->id)
            ->where('station_hub_id', $lastStop->facility_id)->where('type', 'delivery')
            ->where('status', 'completed')->first();

        return $task?->rider?->user_id;
    }

    private function responsibleRider(Order $order, $stops, int $index, OrderFacilityStop $stop): ?int
    {
        $isFirst = $index === 0;
        $isLast = $index === $stops->count() - 1;
        $task = null;
        if ($isFirst && $isLast) {
            $task = RiderTask::query()->with('rider.user')->where('order_id', $order->id)
                ->where('station_hub_id', $stop->facility_id)->where('type', 'delivery')->where('status', 'completed')->first();
            $task ??= RiderTask::query()->with('rider.user')->where('order_id', $order->id)
                ->where('station_hub_id', $stop->facility_id)->where('type', 'pickup')->where('status', 'completed')->first();
        } elseif ($isFirst) {
            $task = RiderTask::query()->with('rider.user')->where('order_id', $order->id)
                ->where('station_hub_id', $stop->facility_id)->where('type', 'pickup')->where('status', 'completed')->first();
        } elseif ($isLast) {
            $task = RiderTask::query()->with('rider.user')->where('order_id', $order->id)
                ->where('station_hub_id', $stop->facility_id)->where('type', 'delivery')->where('status', 'completed')->first();
        } else {
            $trip = LogisticsTransportTrip::query()->with('driver.user')
                ->where('destination_facility_id', $stop->facility_id)->where('status', 'completed')
                ->whereHas('orders', fn ($query) => $query->where('orders.id', $order->id))
                ->latest('id')->first();
            return $trip?->driver?->user_id;
        }

        return $task?->rider?->user_id;
    }
}
