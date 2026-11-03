<?php

namespace App\Services;

use App\Models\Communication\Notification;
use App\Models\Profiles\LogisticsTransportTrip;
use App\Models\Profiles\OrderFacilityStop;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TransportTripWorkflow
{
    public function depart(LogisticsTransportTrip $trip, User $actor): LogisticsTransportTrip
    {
        return DB::transaction(function () use ($trip, $actor) {
            $trip = LogisticsTransportTrip::query()->lockForUpdate()->findOrFail($trip->id);
            abort_unless($trip->status === 'scheduled', 409, 'Only a scheduled trip can depart.');
            $orders = $trip->orders()->orderBy('orders.id')->lockForUpdate()->get();
            abort_unless($orders->isNotEmpty(), 409, 'A trip needs a parcel manifest.');
            foreach ($orders as $order) {
                abort_if($order->logisticsExceptions()->where('status', 'open')->exists(), 409,
                    'Resolve the parcel exception before departing this trip.');
                $origin = OrderFacilityStop::query()->where('order_id', $order->id)
                    ->where('facility_id', $trip->origin_facility_id)->first();
                abort_unless($origin?->departed_at, 409,
                    'Scan departure for every manifest parcel before starting this trip.');
            }

            $trip->update(['status' => 'in_transit', 'departed_at' => now()]);
            foreach ($orders as $order) {
                $from = $order->status;
                $order->status = 'in_transit_to_hub';
                $order->saveQuietly();
                $note = 'Trip TRP-'.str_pad((string) $trip->id, 5, '0', STR_PAD_LEFT)
                    .' departed '.$trip->origin->name.' for '.$trip->destination->name.'.';
                $order->statusEvents()->create(['user_id' => $actor->id, 'from_status' => $from,
                    'to_status' => $order->status, 'note' => $note]);
                foreach (array_unique(array_filter([$order->seller_id, $order->buyer_id])) as $userId) {
                    Notification::create(['user_id' => $userId, 'type' => 'shipment_update',
                        'title' => 'Order '.$order->number.' in transit', 'message' => $note,
                        'link' => (int) $userId === (int) $order->seller_id
                            ? route('seller.orders.show', $order) : route('buyer.orders.show', $order)]);
                }
            }

            return $trip->load(['origin', 'destination', 'driver.user', 'vehicle', 'orders']);
        }, 3);
    }

    /**
     * The driver's load scan for one parcel or a whole container label.
     *
     * Every parcel named by the label is physically on the truck, so each one is
     * recorded as the origin departure scan the depart check looks for. The order
     * status is left to depart(), which moves the whole manifest; a scan only
     * proves the parcels left the origin hub with this trip.
     *
     * A tracking number that is not on the manifest, or whose route has no stop
     * at the origin hub, is reported back instead of failing the whole label.
     *
     * @param  array<int, string>  $trackingNumbers
     * @return array{trip: LogisticsTransportTrip, recorded: array<int, string>, duplicates: array<int, string>, unknown: array<int, string>, not_routed: array<int, string>}
     */
    public function scanLoad(LogisticsTransportTrip $trip, User $actor, array $trackingNumbers, string $scanKey): array
    {
        return DB::transaction(function () use ($trip, $actor, $trackingNumbers, $scanKey) {
            $trip = LogisticsTransportTrip::query()->lockForUpdate()->findOrFail($trip->id);
            abort_unless($trip->status === 'scheduled', 409, 'Only a scheduled trip takes load scans.');
            abort_unless($trip->origin_facility_id, 409, 'This trip has no origin hub to scan at.');

            $manifest = $trip->orders()->lockForUpdate()->get()->keyBy('tracking_number');
            $recorded = [];
            $duplicates = [];
            $unknown = [];
            $notRouted = [];
            // A scan key names one row in facility_scan_events, so a container
            // label needs a key per parcel; a single parcel keeps the app's key.
            $rowKey = count($trackingNumbers) === 1 ? $scanKey : (string) Str::uuid();

            foreach (array_values(array_unique($trackingNumbers)) as $tracking) {
                $tracking = trim((string) $tracking);
                if ($tracking === '') {
                    continue;
                }
                $order = $manifest->get($tracking);
                if (! $order) {
                    $unknown[] = $tracking;
                    continue;
                }

                $stop = OrderFacilityStop::query()->where('order_id', $order->id)
                    ->where('facility_id', $trip->origin_facility_id)->lockForUpdate()->first();
                if (! $stop) {
                    $notRouted[] = $tracking;
                    continue;
                }
                if ($stop->departed_at) {
                    $duplicates[] = $tracking;
                    continue;
                }

                $stop->departed_at = now();
                $stop->save();
                DB::table('facility_scan_events')->insert([
                    'order_id' => $order->id, 'facility_id' => $stop->facility_id,
                    'actor_id' => $actor->id, 'action' => 'departure', 'scan_key' => $rowKey,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                $order->statusEvents()->create([
                    'user_id' => $actor->id, 'from_status' => $order->status, 'to_status' => $order->status,
                    'note' => 'Load scan: '.$order->tracking_number.' confirmed on board TRP-'
                        .str_pad((string) $trip->id, 5, '0', STR_PAD_LEFT).' at '
                        .($trip->origin?->name ?? 'the origin hub').'.',
                ]);
                $recorded[] = $order->tracking_number;

                if (count($trackingNumbers) > 1) {
                    $rowKey = (string) Str::uuid();
                }
            }

            return [
                'trip' => $trip->load(['origin', 'destination', 'vehicle', 'orders']),
                'recorded' => $recorded,
                'duplicates' => $duplicates,
                'unknown' => $unknown,
                'not_routed' => $notRouted,
            ];
        }, 3);
    }

    public function arrive(LogisticsTransportTrip $trip, ?User $actor = null): LogisticsTransportTrip
    {
        return DB::transaction(function () use ($trip, $actor) {
            $trip = LogisticsTransportTrip::query()->lockForUpdate()->findOrFail($trip->id);
            abort_unless($trip->status === 'in_transit', 409, 'Only a departed trip can be completed.');
            $orders = $trip->orders()->orderBy('orders.id')->lockForUpdate()->get();
            foreach ($orders as $order) {
                $destination = OrderFacilityStop::query()->where('order_id', $order->id)
                    ->where('facility_id', $trip->destination_facility_id)->first();
                abort_unless($destination?->arrived_at, 409,
                    'Scan every manifest parcel at the destination before completing this trip.');
            }
            $trip->update(['status' => 'completed', 'arrived_at' => now()]);
            $trip->driver()->where('status', 'on_trip')->update(['status' => 'available']);
            $trip->vehicle()->where('status', 'on_trip')->update(['status' => 'available']);
            if ($actor) {
                $companyUserId = $trip->company()->with('logisticsCenter')->first()?->logisticsCenter?->user_id;
                if ($companyUserId) {
                    \App\Models\Communication\Notification::create([
                        'user_id' => $companyUserId,
                        'type' => 'logistics_trip_arrived',
                        'title' => 'Trip '.str_pad((string) $trip->id, 5, '0', STR_PAD_LEFT).' arrived',
                        'message' => 'The assigned Transport Driver completed the trip arrival scans.',
                        'link' => route('lgp.company.network'),
                    ]);
                }
            }

            return $trip->load(['origin', 'destination', 'driver.user', 'vehicle', 'orders']);
        }, 3);
    }
}
