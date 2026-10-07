<?php

namespace App\Services;

use App\Models\Profiles\LogisticsCenter;

class LogisticsCenterMatcher
{
    public function __construct(private LocationCatalog $locations) {}

    public function forAddress(string $province, string $city): ?LogisticsCenter
    {
        $matches = LogisticsCenter::query()->whereHas('user', fn ($query) => $query
            ->where('role', 'logistics_center')->where('status', 'approved')
            ->whereNull('archived_at')
            ->where(fn ($active) => $active->whereNull('account_status')->orWhere('account_status', 'active')))
            ->get()->filter(fn ($center) => $this->locations->normalize($center->province) === $this->locations->normalize($province));

        $sameCity = $matches->filter(fn ($center) => $this->locations->normalize($center->municipality) === $this->locations->normalize($city));

        if ($sameCity->count() === 1) {
            return $sameCity->first();
        }

        if ($sameCity->count() > 1 || $matches->isEmpty()) {
            return null;
        }

        if ($matches->count() === 1) {
            return $matches->first();
        }

        $origin = $this->locations->municipalityCoordinates($province, $city);
        if (! $origin) {
            return null;
        }

        $ranked = $matches->map(function ($center) use ($origin) {
            $point = $this->locations->municipalityCoordinates($center->province, $center->municipality);
            return $point ? ['center' => $center, 'distance' => $this->distanceKm($origin, $point)] : null;
        })->filter()->sortBy('distance')->values();

        if ($ranked->isEmpty()) {
            return null;
        }

        // If two centers resolve to the same nearest distance, leave routing
        // unresolved for an operator instead of choosing arbitrarily.
        if ($ranked->count() > 1 && abs($ranked[0]['distance'] - $ranked[1]['distance']) < 0.01) {
            return null;
        }

        return $ranked[0]['center'];
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
