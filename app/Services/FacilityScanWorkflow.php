<?php

namespace App\Services;

use App\Models\Communication\Notification;
use App\Models\Ecommerce\Order;
use App\Models\Profiles\LogisticsFacility;
use App\Models\Profiles\OrderFacilityStop;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class FacilityScanWorkflow
{
    public function record(
        User $actor,
        LogisticsFacility $facility,
        string $trackingNumber,
        string $action,
        string $scanKey,
        ?Order $authorizedOrder = null,
    ): array
    {
        return DB::transaction(function () use ($actor, $facility, $trackingNumber, $action, $scanKey, $authorizedOrder) {
            $orderQuery = Order::query()->where('tracking_number', $trackingNumber);
            if ($authorizedOrder) {
                $orderQuery->whereKey($authorizedOrder->getKey());
            } else {
                $orderQuery->where(fn ($query) => $query->where('logistics_center_id', $facility->logistics_center_id)
                    ->orWhere('destination_logistics_center_id', $facility->logistics_center_id));
            }
            $order = $orderQuery->lockForUpdate()->firstOrFail();
            $existing = DB::table('facility_scan_events')->where('scan_key', $scanKey)->first();
            if ($existing) {
                abort_unless((int) $existing->actor_id === $actor->id
                    && (int) $existing->facility_id === $facility->id
                    && (int) $existing->order_id === $order->id
                    && ($action === 'auto' || $existing->action === $action), 409, 'This scan key was already used.');

                return $this->result($order, $facility, true, $existing->action);
            }

            abort_unless($facility->is_active, 409, 'This facility is inactive.');
            if ($action === 'auto') {
                $action = $this->nextAction($order, $facility);
            }
            abort_if(DB::table('facility_scan_events')->where('order_id', $order->id)
                ->where('facility_id', $facility->id)->where('action', $action)->exists(),
                409, 'This parcel scan has already been recorded at this facility.');

            $from = $order->status;
            if ($action === 'origin_arrival') {
                abort_unless($facility->kind === 'main_hub'
                    && (int) $order->logistics_center_id === $facility->logistics_center_id
                    && $order->status === 'picked_up' && $order->courier_id, 409,
                    'This parcel is not awaiting arrival at its origin Main Hub.');
                $order->status = 'at_sorting_center';
            } elseif ($action === 'origin_departure') {
                abort_unless($facility->kind === 'main_hub'
                    && (int) $order->logistics_center_id === $facility->logistics_center_id
                    && $order->status === 'sorted' && $order->facilityStops()->exists(), 409,
                    'Sort and plan this parcel before scanning departure from the origin Main Hub.');
                if ($order->logistics_center_id !== $order->destination_logistics_center_id) {
                    abort_unless($order->linehaul_rider_id, 409, 'Assign a Truck Rider before origin Main Hub departure.');
                }
                $order->status = 'in_transit_to_hub';
            } else {
                $stop = OrderFacilityStop::query()->where('order_id', $order->id)
                    ->where('facility_id', $facility->id)->lockForUpdate()->first();
                abort_unless($stop, 409, 'This facility is not on the parcel route.');
                $previous = OrderFacilityStop::query()->where('order_id', $order->id)
                    ->where('position', $stop->position - 1)->first();
                $lastPosition = OrderFacilityStop::query()->where('order_id', $order->id)->max('position');
                $isFinal = (int) $stop->position === (int) $lastPosition;

                if ($action === 'arrival') {
                    abort_unless(! $stop->arrived_at && (! $previous || $previous->departed_at)
                        && in_array($order->status, ['sorted', 'in_transit_to_hub'], true), 409,
                        'A previous stop must depart before this facility can receive the parcel.');
                    if (! $previous) {
                        abort_unless(DB::table('facility_scan_events')->where('order_id', $order->id)
                            ->where('action', 'origin_departure')->exists(), 409,
                            'Scan departure from the origin Main Hub first.');
                    }
                    if ($order->logistics_center_id !== $order->destination_logistics_center_id) {
                        abort_unless($order->linehaul_rider_id, 409, 'Assign a Truck Rider before recording the inter-province arrival.');
                    }
                    $stop->arrived_at = now();
                    $order->status = $isFinal ? 'at_destination_hub' : 'in_transit_to_hub';
                } elseif ($action === 'arrival_sort') {
                    abort_unless(! $stop->arrived_at && (! $previous || $previous->departed_at)
                        && in_array($order->status, ['sorted', 'in_transit_to_hub'], true), 409,
                        'A previous stop must depart before this facility can receive the parcel.');
                    if (! $previous) {
                        abort_unless(DB::table('facility_scan_events')->where('order_id', $order->id)
                            ->where('action', 'origin_departure')->exists(), 409,
                            'Scan departure from the origin Main Hub first.');
                    }
                    if ($order->logistics_center_id !== $order->destination_logistics_center_id) {
                        abort_unless($order->linehaul_rider_id, 409, 'Assign a Truck Rider before recording the inter-province arrival.');
                    }
                    $stop->arrived_at = now();
                    $stop->sorted_at = now();
                    $order->status = $isFinal ? 'at_destination_hub' : 'in_transit_to_hub';
                } elseif ($action === 'sort') {
                    abort_unless($stop->arrived_at && ! $stop->sorted_at, 409,
                        'Receive this parcel at the facility before sorting it.');
                    $stop->sorted_at = now();
                } elseif ($action === 'departure') {
                    abort_unless(! $isFinal && $stop->sorted_at && ! $stop->departed_at, 409,
                        'Sort this parcel at an intermediate stop before departure.');
                    $stop->departed_at = now();
                } else {
                    abort(422, 'Unknown scan action.');
                }
                $stop->save();
            }

            $note = match ($action) {
                'origin_arrival', 'arrival' => 'Parcel scanned on arrival at '.$facility->name.', '.$facility->municipality.'.',
                'arrival_sort' => 'Parcel received and sorted at '.$facility->name.', '.$facility->municipality.'.',
                'origin_departure' => 'Parcel departed '.$facility->name.', '.$facility->municipality.'.',
                'sort' => 'Parcel sorted at '.$facility->name.', '.$facility->municipality.'.',
                'departure' => 'Parcel scanned on departure from '.$facility->name.', '.$facility->municipality.'.',
            };
            if ($order->isDirty('status')) {
                // The facility-specific notification below replaces the generic status notification.
                $order->saveQuietly();
            }
            $order->statusEvents()->create([
                'user_id' => $actor->id, 'from_status' => $from,
                'to_status' => $order->status, 'note' => $note,
            ]);
            DB::table('facility_scan_events')->insert([
                'order_id' => $order->id, 'facility_id' => $facility->id,
                'actor_id' => $actor->id, 'action' => $action, 'scan_key' => $scanKey,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            foreach (array_unique(array_filter([$order->seller_id, $order->buyer_id])) as $userId) {
                Notification::create([
                    'user_id' => $userId, 'type' => 'shipment_update',
                    'title' => 'Order '.$order->number.' updated', 'message' => $note,
                    'link' => (int) $userId === (int) $order->seller_id
                        ? route('seller.orders.show', $order)
                        : route('buyer.orders.show', $order),
                ]);
            }
            Notification::create([
                'user_id' => $facility->network->user_id,
                'type' => 'shipment_update',
                'title' => 'Parcel scan '.$order->number,
                'message' => $note,
                'link' => route('logistics.facilities.show', $facility),
            ]);

            return $this->result($order, $facility, false, $action);
        }, 3);
    }

    private function nextAction(Order $order, LogisticsFacility $facility): string
    {
        if ($facility->kind === 'main_hub' && (int) $order->logistics_center_id === (int) $facility->logistics_center_id) {
            if ($order->status === 'picked_up' && $order->courier_id) {
                return 'origin_arrival';
            }
            if ($order->status === 'at_sorting_center') {
                abort(409, 'Sort and plan this parcel in Dispatch before scanning it again.');
            }
            if ($order->status === 'sorted' && $order->facilityStops()->exists()
                && ! DB::table('facility_scan_events')->where('order_id', $order->id)
                    ->where('facility_id', $facility->id)->where('action', 'origin_departure')->exists()) {
                return 'origin_departure';
            }
        }

        $stop = OrderFacilityStop::query()->where('order_id', $order->id)
            ->where('facility_id', $facility->id)->first();
        abort_unless($stop, 409, 'This facility is not on the parcel route.');
        if (! $stop->arrived_at) {
            return 'arrival_sort';
        }
        if (! $stop->sorted_at) {
            return 'sort';
        }

        $lastPosition = OrderFacilityStop::query()->where('order_id', $order->id)->max('position');
        if ((int) $stop->position < (int) $lastPosition && ! $stop->departed_at) {
            return 'departure';
        }

        abort(409, 'There is no further scan step for this parcel at this facility.');
    }

    private function result(Order $order, LogisticsFacility $facility, bool $duplicate, ?string $action = null): array
    {
        $next = $order->facilityStops()->with('facility')->whereNull('arrived_at')->first();

        return [
            'tracking_number' => $order->tracking_number,
            'status' => $order->status,
            'facility' => $facility->name,
            'action' => $action,
            'next_facility' => $next?->facility?->name,
            'duplicate' => $duplicate,
        ];
    }
}
