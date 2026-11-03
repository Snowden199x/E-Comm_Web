<?php

namespace App\Services;

use App\Models\Profiles\LogisticsFacility;

/**
 * Attaches a Transport Driver to a Station Hub.
 *
 * A station-only driver picks the Station Hub itself. A province-only or
 * "both" driver is attached to the Station Hub where they live, or the nearest
 * one by municipality coordinates when their own municipality has no Station
 * Hub. Nothing is returned when no Station Hub can be determined, so the
 * application is never attached to an arbitrary facility.
 */
class StationHubResolver
{
    public function __construct(private LocationCatalog $locations) {}

    /**
     * @param  array{station_hub_id?: int|null, province_hub_id?: int|null, province_code?: string|null, city_code?: string|null}  $data
     */
    public function resolve(array $data): ?LogisticsFacility
    {
        $chosen = isset($data['station_hub_id']) ? $this->station((int) $data['station_hub_id']) : null;
        if ($chosen) {
            return $chosen;
        }

        $provinceHubId = isset($data['province_hub_id']) ? (int) $data['province_hub_id'] : null;
        $cityCode = $data['city_code'] ?? null;
        $provinceCode = $data['province_code'] ?? null;

        $candidates = LogisticsFacility::query()
            ->where('kind', 'station_hub')
            ->where('is_active', true)
            ->when($provinceHubId, fn ($query) => $query->where('parent_id', $provinceHubId))
            ->orderBy('name')
            ->get();

        if ($candidates->isEmpty()) {
            return null;
        }

        // 1. Where the driver lives: the Station Hub covers that municipality.
        if ($cityCode) {
            $sameCity = $candidates->first(fn (LogisticsFacility $hub) => $hub->municipality_code === $cityCode
                || $hub->station_municipality_key === $cityCode);
            if ($sameCity) {
                return $sameCity;
            }
        }

        // 2. The nearest Station Hub, measured between municipality centres.
        $origin = $this->coordinates($provinceCode, $cityCode);
        if ($origin) {
            $nearest = $this->nearest($candidates, $origin);
            if ($nearest) {
                return $nearest;
            }
        }

        // 3. Any Station Hub in the chosen Province Hub's network.
        if ($provinceHubId) {
            return $candidates->first();
        }

        return null;
    }

    public function station(int $stationId): ?LogisticsFacility
    {
        return LogisticsFacility::query()->whereKey($stationId)
            ->where('kind', 'station_hub')->where('is_active', true)->first();
    }

    private function nearest($candidates, array $origin): ?LogisticsFacility
    {
        $best = null;
        $bestDistance = null;

        foreach ($candidates as $hub) {
            $point = $this->coordinates($hub->province_code, $hub->municipality_code)
                ?? $this->coordinatesByName($hub->province, $hub->municipality);
            if (! $point) {
                continue;
            }
            $distance = $this->distanceKm($origin, $point);
            if ($bestDistance === null || $distance < $bestDistance) {
                $best = $hub;
                $bestDistance = $distance;
            }
        }

        return $best;
    }

    private function coordinates(?string $provinceCode, ?string $cityCode): ?array
    {
        if (! $provinceCode || ! $cityCode) {
            return null;
        }
        $address = $this->locations->address($provinceCode, $cityCode);
        if (! $address) {
            return null;
        }

        return $this->locations->municipalityCoordinates($address['province'], $address['city']);
    }

    private function coordinatesByName(?string $province, ?string $city): ?array
    {
        if (! $province || ! $city) {
            return null;
        }

        return $this->locations->municipalityCoordinates($province, $city);
    }

    private function distanceKm(array $a, array $b): float
    {
        $lat1 = deg2rad((float) ($a['lat'] ?? $a['latitude'] ?? 0));
        $lon1 = deg2rad((float) ($a['lng'] ?? $a['lon'] ?? $a['longitude'] ?? 0));
        $lat2 = deg2rad((float) ($b['lat'] ?? $b['latitude'] ?? 0));
        $lon2 = deg2rad((float) ($b['lng'] ?? $b['lon'] ?? $b['longitude'] ?? 0));
        $deltaLat = $lat2 - $lat1;
        $deltaLon = $lon2 - $lon1;
        $h = sin($deltaLat / 2) ** 2 + cos($lat1) * cos($lat2) * sin($deltaLon / 2) ** 2;

        return 6371 * 2 * asin(min(1, sqrt($h)));
    }
}
