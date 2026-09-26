<?php

namespace App\Services;

use App\Models\Communication\Notification;
use App\Models\Ecommerce\Order;
use App\Models\Ecommerce\OrderScanEvent;
use App\Models\Profiles\LogisticsCenter;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OrderScanWorkflow
{
    private const TRANSITIONS = [
        'pickup' => ['ready_for_pickup', 'picked_up', 'courier_id', 'logistics_center_id'],
        'origin_arrival' => ['picked_up', 'at_sorting_center', 'courier_id', 'logistics_center_id'],
        'out_for_delivery' => ['assigned_to_rider', 'out_for_delivery', 'delivery_courier_id', 'destination_logistics_center_id'],
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
                'out_for_delivery' => 'Delivery rider scanned the parcel for dispatch from '.$center->business_name.' in '.$this->location($center).'.',
            };

            $order->status = $transition[1];
            $order->save();
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
