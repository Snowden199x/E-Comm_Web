<?php

namespace App\Services;

use App\Models\Communication\Notification;
use App\Models\Ecommerce\Order;
use App\Models\Ecommerce\OrderScanEvent;
use App\Models\Profiles\LogisticsCenter;
use App\Models\Profiles\RiderTask;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OrderScanWorkflow
{
    public function __construct(private ShippingEarningsSettlementService $shippingSettlement) {}

    private const TRANSITIONS = [
        'pickup' => ['ready_for_pickup', 'picked_up', 'courier_id', 'logistics_center_id'],
        'origin_arrival' => ['picked_up', 'at_sorting_center', 'courier_id', 'logistics_center_id'],
        'soc5' => ['sorted', 'to_soc5', 'courier_id', 'logistics_center_id'],
        'soc6' => ['to_soc5', 'to_soc6', 'courier_id', 'logistics_center_id'],
        'destination_hub' => ['to_soc6', 'in_transit_to_hub', 'courier_id', 'logistics_center_id'],
        'out_for_delivery' => ['assigned_to_rider', 'out_for_delivery', 'delivery_courier_id', 'destination_logistics_center_id'],
        'delivered' => ['out_for_delivery', 'delivered', 'delivery_courier_id', 'destination_logistics_center_id'],
    ];

    public function record(User $rider, LogisticsCenter $center, array $data): array
    {
        return DB::transaction(function () use ($rider, $center, $data) {
            $order = Order::query()->where('tracking_number', $data['tracking_number'])
                ->lockForUpdate()->firstOrFail();
            $transition = self::TRANSITIONS[$data['scan_type']];
            abort_unless((int) $order->{$transition[2]} === (int) $rider->id
                && (int) $order->{$transition[3]} === (int) $center->id, 403,
                'This parcel is not assigned to this rider and logistics center.');
            $taskType = $data['scan_type'] === 'pickup' ? 'pickup'
                : (in_array($data['scan_type'], ['out_for_delivery', 'delivered'], true) ? 'delivery' : null);
            if ($taskType) {
                abort_if(RiderTask::query()->where('order_id', $order->id)
                    ->where('rider_id', $rider->courierDetail?->id)->where('type', $taskType)
                    ->where('status', 'failed')->exists(), 409,
                    'This task was paused after a Rider report. Contact the Station Hub before scanning.');
            }
            abort_if($order->logisticsExceptions()->where('status', 'open')->exists(), 409,
                'Resolve the open parcel exception before scanning.');
            if (in_array($data['scan_type'], ['soc5', 'soc6', 'destination_hub'], true)) {
                abort_if($order->facilityStops()->exists(), 409,
                    'This parcel uses actual facility stops. Use the Logistics facility scanner for handoffs.');
                abort_unless($order->destination_logistics_center_id
                    && (int) $order->destination_logistics_center_id !== (int) $center->id,
                    409, 'Inter-hub scans require a different resolved destination hub.');
            }
            if ($data['scan_type'] === 'delivered' && $order->facilityStops()->exists()) {
                abort_unless($order->deliveryProof()->where('scan_key', $data['scan_key'])
                    ->where('rider_id', $rider->id)->exists(), 409,
                    'Delivery proof is required for this facility-routed parcel.');
            }

            $existing = $order->scanEvents()->where('scan_key', $data['scan_key'])->first();
            if ($existing) {
                abort_unless($existing->actor_id === $rider->id && $existing->scan_type === $data['scan_type'],
                    409, 'This scan key was already used for a different scan.');

                return $this->response($order, $center, $existing, true);
            }

            abort_unless($order->status === $transition[0], 409,
                'The parcel is no longer in the expected stage. Refresh assignments before scanning.');

            $note = match ($data['scan_type']) {
                'pickup' => 'Picked up by the assigned rider for '.$center->business_name.' in '.$this->location($center).'.',
                'origin_arrival' => 'Rider scan confirmed arrival at '.$center->business_name.' in '.$this->location($center).'.',
                'soc5' => 'Parcel scanned after sorting at '.$center->business_name.'; SOC5 is the next virtual route checkpoint.',
                'soc6' => 'SOC5 virtual route checkpoint scanned; SOC6 is next.',
                'destination_hub' => 'SOC6 virtual route checkpoint scanned; next destination is '.$order->destinationLogisticsCenter->business_name.'. Receipt there is not yet confirmed.',
                'out_for_delivery' => 'Delivery rider scanned the parcel for dispatch from '.$center->business_name.' in '.$this->location($center).'.',
                'delivered' => 'Assigned delivery rider scanned the parcel as delivered for '.$center->business_name.' in '.$this->location($center).'. Buyer receipt confirmation is pending.',
            };

            $order->status = $transition[1];
            $order->save();
            if (in_array($data['scan_type'], ['pickup', 'out_for_delivery', 'delivered'], true)
                && $rider->courierDetail?->station_hub_id) {
                RiderTask::query()->where('order_id', $order->id)
                    ->where('station_hub_id', $rider->courierDetail->station_hub_id)
                    ->where('rider_id', $rider->courierDetail->id)
                    ->where('type', $data['scan_type'] === 'pickup' ? 'pickup' : 'delivery')
                    ->whereIn('status', ['assigned', 'in_progress'])
                    ->update($data['scan_type'] === 'delivered'
                        ? ['status' => 'completed', 'completed_at' => now()]
                        : ['status' => 'in_progress', 'started_at' => now()]);
            }
            $order->statusEvents()->create([
                'user_id' => $rider->id,
                'from_status' => $transition[0],
                'to_status' => $transition[1],
                'note' => $note,
            ]);
            $event = $order->scanEvents()->create([
                'actor_id' => $rider->id,
                'logistics_center_id' => $center->id,
                'scan_type' => $data['scan_type'],
                'from_status' => $transition[0],
                'to_status' => $transition[1],
                'scan_key' => $data['scan_key'],
            ]);

            if ($data['scan_type'] === 'delivered') {
                $this->shippingSettlement->settleDeliveredOrder($order);
            }

            Notification::create([
                'user_id' => $center->user_id,
                'type' => 'shipment_update',
                'title' => $order->number.': '.$order->status_label,
                'message' => $note,
                'link' => route('logistics.dispatch.index'),
            ]);

            return $this->response($order, $center, $event, false);
        }, 3);
    }

    private function location(LogisticsCenter $center): string
    {
        return implode(', ', array_filter([$center->municipality, $center->province]));
    }

    private function response(Order $order, LogisticsCenter $center, OrderScanEvent $event, bool $duplicate): array
    {
        return [
            'order_id' => $order->id,
            'tracking_number' => $order->tracking_number,
            'status' => $event->to_status,
            'logistics_center' => ['id' => $center->id, 'name' => $center->business_name],
            'scanned_at' => $event->created_at?->toIso8601String(),
            'duplicate' => $duplicate,
        ];
    }
}
