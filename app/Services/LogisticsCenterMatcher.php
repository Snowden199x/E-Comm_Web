<?php

namespace App\Services;

use App\Models\Profiles\LogisticsCenter;
use App\Models\Profiles\LogisticsFacility;
use Illuminate\Support\Facades\DB;

class LogisticsCenterMatcher
{
    public function __construct(private LocationCatalog $locations) {}

    public function forAddress(string $province, string $city): ?LogisticsCenter
    {
        $station = $this->stationForAddress($province, $city);
        if ($station) {
            return $station->network;
        }
        if ($this->stationsForAddress($province, $city)->isNotEmpty()) {
            return null;
        }

        $provinceCode = $this->locations->provinceCodeForName($province);
        if (! $provinceCode) {
            return null;
        }

        $legacyCenters = LogisticsCenter::query()->where('coverage_province_code', $provinceCode)
            ->whereHas('user', fn ($user) => $user->where('status', 'approved')->whereNull('archived_at')
                ->where(fn ($active) => $active->whereNull('account_status')->orWhere('account_status', 'active')))
            ->limit(2)->get();

        return $legacyCenters->count() === 1 ? $legacyCenters->first() : null;
    }

    public function stationForAddress(string $province, string $city, ?int $companyId = null): ?LogisticsFacility
    {
        $codes = $this->locations->codesForNames($province, $city);
        if (! $codes) {
            return null;
        }

        $primaryId = DB::table('primary_logistics_coverage')
            ->where('province_code', $codes[0])->where('municipality_code', $codes[1])
            ->value('station_hub_id');
        if ($primaryId) {
            return $this->stationQuery($codes, $companyId)->whereKey($primaryId)->first();
        }

        $stations = $this->stationQuery($codes, $companyId)->limit(2)->get();

        return $stations->count() === 1 ? $stations->first() : null;
    }

    private function stationsForAddress(string $province, string $city, ?int $companyId = null)
    {
        $codes = $this->locations->codesForNames($province, $city);
        if (! $codes) {
            return collect();
        }

        return $this->stationQuery($codes, $companyId)->orderBy('id')->limit(2)->get();
    }

    private function stationQuery(array $codes, ?int $companyId = null)
    {
        return LogisticsFacility::query()->where('kind', 'station_hub')
            ->when($companyId, fn ($query) => $query->where('logistics_company_id', $companyId))
            ->where('province_code', $codes[0])->where('municipality_code', $codes[1])
            ->where('is_active', true)
            ->whereHas('company', fn ($company) => $company->where('status', 'approved')
                ->whereHas('logisticsCenter.user', fn ($user) => $user->where('role', 'logistics_center')
                    ->where('status', 'approved')->whereNull('archived_at')
                    ->where(fn ($active) => $active->whereNull('account_status')->orWhere('account_status', 'active'))))
            ->whereHas('parent', fn ($parent) => $parent->where('is_active', true))
            ->with('network');
    }
}
