<?php

namespace App\Services;

use App\Models\Ecommerce\Order;
use App\Models\Profiles\LogisticsRoutePlan;

class LogisticsRoutePlanner
{
    public function __construct(private LocationCatalog $locations) {}

    public function selectPlan(Order $order): ?LogisticsRoutePlan
    {
        if (! $order->logistics_center_id || ! $order->destination_logistics_center_id
            || $order->logistics_center_id === $order->destination_logistics_center_id) {
            return null;
        }

        $origin = $order->logisticsCenter;
        $destination = $order->destinationLogisticsCenter;
        if (! $origin || ! $destination || ! $order->shipping_province || ! $order->shipping_city) {
            return null;
        }

        $originPoint = $this->locations->municipalityCoordinates($origin->province, $origin->municipality);
        $destinationPoint = $this->locations->municipalityCoordinates($destination->province, $destination->municipality);
        if (! $originPoint || ! $destinationPoint) {
            return null;
        }

        $plans = LogisticsRoutePlan::query()
            ->where('is_active', true)
            ->where('from_logistics_center_id', $origin->id)
            ->where('to_logistics_center_id', $destination->id)
            ->with('stops.checkpoint')
            ->get()
            ->map(function (LogisticsRoutePlan $plan) use ($originPoint, $destinationPoint, $order) {
                $checkpoints = $plan->stops->map(fn ($stop) => $stop->checkpoint);
                if ($checkpoints->contains(fn ($checkpoint) => ! $checkpoint || ! $checkpoint->is_active)) {
                    return null;
                }

                $buyerPoint = $this->locations->municipalityCoordinates($order->shipping_province, $order->shipping_city);
                if (! $buyerPoint) {
                    return null;
                }

                $routePoints = [$originPoint];
                foreach ($checkpoints as $checkpoint) {
                    $point = $this->locations->municipalityCoordinates($checkpoint->province, $checkpoint->municipality);
                    if (! $point) {
                        return null;
                    }
                    $routePoints[] = $point;
                }
                $routePoints[] = $destinationPoint;

                $routeDistance = 0.0;
                for ($index = 1; $index < count($routePoints); $index++) {
                    $routeDistance += $this->distanceKm($routePoints[$index - 1], $routePoints[$index]);
                }

                // A configured route is the allowed path; checkpoint
                // municipalities are locality labels for route waypoints,
                // not street addresses or registered facilities.
                // Choose the route with a waypoint nearest to the buyer.
                $candidatePoints = $checkpoints->isEmpty()
                    ? [$destinationPoint]
                    : $checkpoints->map(fn ($checkpoint) => $this->locations->municipalityCoordinates(
                        $checkpoint->province,
                        $checkpoint->municipality
                    ))->all();
                $buyerDistance = min(array_map(
                    fn (array $point) => $this->distanceKm($buyerPoint, $point),
                    $candidatePoints
                ));

                return ['plan' => $plan, 'distance' => $routeDistance, 'buyer_distance' => $buyerDistance];
            })
            ->filter()
            ->sort(function ($left, $right) {
                $buyerOrder = $left['buyer_distance'] <=> $right['buyer_distance'];
                if ($buyerOrder !== 0 && abs($left['buyer_distance'] - $right['buyer_distance']) >= 0.01) {
                    return $buyerOrder;
                }

                $distanceOrder = $left['distance'] <=> $right['distance'];
                return $distanceOrder !== 0
                    ? $distanceOrder
                    : ($left['plan']->priority <=> $right['plan']->priority);
            })
            ->values();

        return $plans->first()['plan'] ?? null;
    }

    private function distanceKm(array $from, array $to): float
    {
        [$lat1, $lon1] = $from;
        [$lat2, $lon2] = $to;
        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);
        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lonDelta / 2) ** 2;

        return 6371 * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
