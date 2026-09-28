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

        return $matches->count() === 1 ? $matches->first() : null;
    }
}
