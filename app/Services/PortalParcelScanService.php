<?php

namespace App\Services;

use App\Models\Communication\Notification;
use App\Models\Ecommerce\Order;
use App\Models\Profiles\LogisticsFacility;
use App\Models\Profiles\OrderFacilityStop;
use App\Models\Profiles\RiderTask;
use App\Models\Profiles\ManualLogisticsHandoff;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PortalParcelScanService
{
    public function record(User $actor, LogisticsFacility $facility, string $trackingNumber, string $scanKey): array
    {
        return DB::transaction(function () use ($actor, $facility, $trackingNumber, $scanKey) {
            abort_unless($facility->is_active && $facility->company?->status === 'approved', 409, 'This facility is not active.');
            $order = Order::query()->where('tracking_number', $trackingNumber)
                ->whereHas('facilityStops', fn ($query) => $query->where('facility_id', $facility->id))
                ->lockForUpdate()->firstOrFail();
            $existing = DB::table('facility_scan_events')->where('scan_key', $scanKey)->first();
            if ($existing) {
                abort_unless((int) $existing->actor_id === (int) $actor->id
                    && (int) $existing->facility_id === (int) $facility->id
                    && (int) $existing->order_id === (int) $order->id, 409, 'This scan key was already used.');
                return $this->result($order, $facility, $existing->action, true);
            }
            abort_if($order->logisticsExceptions()->where('status', 'open')->exists(), 409,
                'Resolve the open parcel exception before scanning another route step.');

            $stop = OrderFacilityStop::query()->where('order_id', $order->id)
                ->where('facility_id', $facility->id)->lockForUpdate()->firstOrFail();
            $previous = OrderFacilityStop::query()->where('order_id', $order->id)
                ->where('position', $stop->position - 1)->first();
            $next = OrderFacilityStop::query()->where('order_id', $order->id)
                ->where('position', $stop->position + 1)->first();
            $from = $order->status;

            if (! $stop->arrived_at) {
                if ($previous) {
                    abort_unless($previous->departed_at, 409, 'The previous facility has not scanned departure.');
                    $handoff = ManualLogisticsHandoff::query()->where('order_id', $order->id)
                        ->where('origin_facility_id', $previous->facility_id)
                        ->where('destination_facility_id', $facility->id)->lockForUpdate()->first();
                    if ($handoff) {
                        abort_unless($handoff->status === 'pending'
                            && (int) $handoff->destination_company_id === (int) $facility->logistics_company_id,
                            409, 'The cross-company handoff is not awaiting receipt here.');
                    } else {
                        $trip = $order->transportTrips()->where('origin_facility_id', $previous->facility_id)
                            ->where('destination_facility_id', $facility->id)
                            ->whereIn('status', ['in_transit', 'completed'])->latest('transport_trips.id')->first();
                        abort_unless($trip, 409, 'The transport trip has not departed for this facility.');
                    }
                } else {
                    abort_unless($order->status === 'picked_up' && $order->courier_id, 409,
                        'The assigned Rider must scan seller pickup first.');
                }
                $stop->arrived_at = now();
                $stop->sorted_at = now();
                $action = 'arrival_sort';
                $order->status = $next ? 'sorted' : 'at_destination_hub';
                $note = 'Parcel received and sorted at '.$facility->name.', '.$facility->municipality.'.';
                if (isset($handoff) && $handoff) {
                    $handoff->update(['status' => 'accepted', 'accepted_by' => $actor->id, 'accepted_at' => now()]);
                    $note = 'Cross-company handoff accepted and parcel received at '.$facility->name.'.';
                    if ($handoff->manifest_id) {
                        $manifest = $handoff->manifest()->lockForUpdate()->firstOrFail();
                        if (! $manifest->handoffs()->where('status', '!=', 'accepted')->exists()) {
                            $manifest->update(['status' => 'completed', 'completed_at' => now()]);
                            $manifest->driver()->where('status', 'on_trip')->update(['status' => 'available']);
                            $manifest->vehicle()->where('status', 'on_trip')->update(['status' => 'available']);
                        }
                    }
                }
                if (! $previous && $order->courier_id) {
                    RiderTask::query()->where('order_id', $order->id)->where('station_hub_id', $facility->id)
                        ->where('type', 'pickup')->whereIn('status', ['assigned', 'in_progress'])
                        ->update(['status' => 'completed', 'completed_at' => now()]);
                }
            } elseif ($next && $stop->sorted_at && ! $stop->departed_at) {
                abort_if($order->route_type === 'manual_handoff'
                    && (int) $facility->logistics_company_id !== (int) $next->facility?->logistics_company_id,
                    409, 'Initiate the cross-company handoff from the Company portal.');
                $trip = $order->transportTrips()->where('origin_facility_id', $facility->id)
                    ->where('destination_facility_id', $next->facility_id)
                    ->where('status', 'scheduled')->latest('transport_trips.id')->first();
                abort_unless($trip, 409, 'Schedule a trip to the next facility before scanning departure.');
                $stop->departed_at = now();
                $action = 'departure';
                $note = 'Parcel scanned for departure from '.$facility->name.' on trip TRP-'.str_pad((string) $trip->id, 5, '0', STR_PAD_LEFT).'.';
            } else {
                abort(409, 'There is no further scan step for this parcel at this facility.');
            }

            abort_if(DB::table('facility_scan_events')->where('order_id', $order->id)
                ->where('facility_id', $facility->id)->where('action', $action)->exists(),
                409, 'This parcel step was already scanned.');
            $stop->save();
            if ($order->isDirty('status')) {
                $order->saveQuietly();
            }
            $order->statusEvents()->create(['user_id' => $actor->id, 'from_status' => $from,
                'to_status' => $order->status, 'note' => $note]);
            DB::table('facility_scan_events')->insert([
                'order_id' => $order->id, 'facility_id' => $facility->id, 'actor_id' => $actor->id,
                'action' => $action, 'scan_key' => $scanKey, 'created_at' => now(), 'updated_at' => now(),
            ]);
            foreach (array_unique(array_filter([$order->seller_id, $order->buyer_id])) as $userId) {
                Notification::create(['user_id' => $userId, 'type' => 'shipment_update',
                    'title' => 'Order '.$order->number.' updated', 'message' => $note,
                    'link' => (int) $userId === (int) $order->seller_id
                        ? route('seller.orders.show', $order) : route('buyer.orders.show', $order)]);
            }
            foreach (array_unique(array_filter([$facility->staff?->user_id, $facility->company?->logisticsCenter?->user_id])) as $userId) {
                Notification::create(['user_id' => $userId, 'type' => 'shipment_update',
                    'title' => 'Parcel '.$order->number.' scanned', 'message' => $note,
                    'link' => (int) $userId === (int) $facility->staff?->user_id
                        ? route($facility->isProvinceHub() ? 'lgp.province.tracking' : 'lgp.station.tracking', ['q' => $order->tracking_number])
                        : route('lgp.company.tracking', ['q' => $order->tracking_number])]);
            }
            if (isset($handoff) && $handoff && $action === 'arrival_sort') {
                $sourceUserId = $handoff->originCompany?->logisticsCenter?->user_id;
                if ($sourceUserId) {
                    Notification::create(['user_id' => $sourceUserId, 'type' => 'logistics_handoff',
                        'title' => 'Handoff received '.$order->number, 'message' => $note,
                        'link' => route('lgp.company.handoffs')]);
                }
            }

            return $this->result($order, $facility, $action, false);
        }, 3);
    }

    private function result(Order $order, LogisticsFacility $facility, string $action, bool $duplicate): array
    {
        $next = $order->facilityStops()->with('facility')->whereNull('arrived_at')->first();

        return ['tracking_number' => $order->tracking_number, 'status' => $order->status,
            'facility' => $facility->name, 'action' => $action, 'next_facility' => $next?->facility?->name,
            'duplicate' => $duplicate];
    }
}
