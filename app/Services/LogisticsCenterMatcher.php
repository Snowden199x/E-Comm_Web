<?php

namespace App\Services;

use App\Models\Profiles\LogisticsCenter;

class LogisticsCenterMatcher
{
    public function __construct(private LocationCatalog $locations) {}

    public function forAddress(string $province, string $city): ?LogisticsCenter
    {
        $code = $this->locations->provinceCodeForName($province);
        if (! $code) {
            return null;
        }

        return LogisticsCenter::query()->where('coverage_province_code', $code)
            ->whereHas('user', fn ($query) => $query
            ->where('role', 'logistics_center')->where('status', 'approved')
            ->whereNull('archived_at')
            ->where(fn ($active) => $active->whereNull('account_status')->orWhere('account_status', 'active')))
            ->first();
    }
}
