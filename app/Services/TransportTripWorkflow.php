<?php

namespace App\Services;

use App\Models\Communication\Notification;
use App\Models\Profiles\LogisticsTransportTrip;
use App\Models\Profiles\OrderFacilityStop;
use App\Models\User;
use Illuminate\Support\Facades\DB;

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
