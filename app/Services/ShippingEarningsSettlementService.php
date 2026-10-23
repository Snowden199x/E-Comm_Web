<?php

namespace App\Services;

use App\Models\Ecommerce\Order;
use App\Models\Profiles\LogisticsTransportTrip;
use App\Models\Profiles\OrderFacilityStop;
use App\Models\Profiles\RiderTask;
use Illuminate\Support\Facades\DB;

class ShippingEarningsSettlementService
{
    public const RIDER_SHARE_CENTS_PER_HUB = 1000;

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
        }, 3);
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
