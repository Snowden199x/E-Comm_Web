<?php

namespace App\Services;

use App\Models\Ecommerce\Order;
use App\Models\Communication\Notification;
use App\Models\Profiles\OrderFacilityStop;
use App\Models\Profiles\ManualLogisticsHandoff;
use Illuminate\Support\Facades\DB;

class OrderRoutingService
{
    public function __construct(
        private LogisticsCenterMatcher $centers,
        private LogisticsFacilityRouteResolver $routes,
    ) {}

    public function routeUnresolvedReady(): int
    {
        $count = 0;
        Order::query()->where('status', 'ready_for_pickup')
            ->where(fn ($query) => $query->whereNull('origin_station_id')->orWhereNull('destination_station_id'))
            ->with('seller.sellerDetail')
            ->chunkById(100, function ($orders) use (&$count) {
                foreach ($orders as $order) {
                    $this->route($order);
                    $count++;
                }
            });

        return $count;
    }

    public function route(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);
            if ($order->status !== 'ready_for_pickup' || $order->facilityStops()->exists()) {
                return;
            }
            $order->loadMissing('seller.sellerDetail');
            $seller = $order->seller?->sellerDetail;
            $origin = $seller ? $this->centers->stationForAddress($seller->province, $seller->municipality) : null;
            $destination = $order->shipping_province && $order->shipping_city
                ? $this->centers->stationForAddress($order->shipping_province, $order->shipping_city) : null;

            if (! $origin || ! $destination || ! $origin->parent?->is_active || ! $destination->parent?->is_active) {
                return;
            }

            $previousOrigin = $order->origin_station_id;
            $sameCompany = (int) $origin->logistics_company_id === (int) $destination->logistics_company_id;
            $originHub = $origin->parent;
            $destinationHub = $destination->parent;
            $route = $this->routes->between($origin, $destination);
            if ($route === []) {
                return;
            }

            $order->fill([
                'logistics_center_id' => $origin->logistics_center_id,
                'destination_logistics_center_id' => $destination->logistics_center_id,
                'origin_station_id' => $origin->id,
                'origin_province_hub_id' => $originHub->id,
                'destination_station_id' => $destination->id,
                'destination_province_hub_id' => $destinationHub->id,
                'route_type' => $sameCompany ? (count($route) === 1 ? 'local'
                    : ($originHub->is($destinationHub) ? 'intra_province' : 'inter_province')) : 'manual_handoff',
            ])->save();

            foreach ($route as $position => $facility) {
                OrderFacilityStop::create(['order_id' => $order->id, 'facility_id' => $facility->id, 'position' => $position + 1]);
            }
            if (! $sameCompany) {
                ManualLogisticsHandoff::create([
                    'order_id' => $order->id, 'origin_company_id' => $origin->logistics_company_id,
                    'destination_company_id' => $destination->logistics_company_id,
                    'origin_facility_id' => $originHub->id, 'destination_facility_id' => $destinationHub->id,
                    'status' => 'awaiting_origin',
                ]);
            }

            if (! $previousOrigin) {
                $recipients = array_unique(array_filter([$origin->staff?->user_id, $origin->company?->logisticsCenter?->user_id]));
                foreach ($recipients as $userId) {
                    Notification::create([
                        'user_id' => $userId, 'type' => 'logistics_pickup_request',
                        'title' => 'New pickup request '.$order->number,
                        'message' => $sameCompany
                            ? 'A seller parcel is ready for pickup at '.$origin->name.'.'
                            : 'A seller parcel needs pickup and a manual cross-company handoff.',
                        'link' => (int) $userId === (int) $origin->staff?->user_id
                            ? route('lgp.station.pickups') : route('lgp.company.tracking', ['q' => $order->tracking_number]),
                    ]);
                }
            }
        }, 3);
    }

}
