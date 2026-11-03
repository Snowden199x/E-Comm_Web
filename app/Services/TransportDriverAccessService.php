<?php

namespace App\Services;

use App\Models\Profiles\TransportDriverDetail;
use App\Models\User;

class TransportDriverAccessService
{
    /**
     * A driver may sign in when its approving hub is open: the Station Hub for a
     * station-only driver, the Province Hub for province and both drivers. The
     * company, owner account and license gates stay the same for everyone.
     */
    public function approvedDriver(?User $user): ?TransportDriverDetail
    {
        if (! $user || $user->role !== 'transport_driver' || $user->status !== 'approved'
            || $user->archived_at || ($user->account_status && $user->account_status !== 'active')) {
            return null;
        }

        $driver = TransportDriverDetail::query()->where('user_id', $user->id)
            ->with(['provinceHub.company.logisticsCenter.user', 'stationHub'])->first();
        $hub = $driver?->provinceHub;
        $company = $hub?->company;
        $owner = $company?->logisticsCenter?->user;
        $approving = $driver?->worksStationToStation() ? $driver?->stationHub : $hub;

        if (! $driver || $driver->status === 'pending' || ! $hub?->is_active
            || $hub->kind !== 'province_hub' || ! $approving?->is_active
            || $company?->status !== 'approved'
            || ! $owner || $owner->status !== 'approved' || $owner->archived_at
            || ($owner->account_status && $owner->account_status !== 'active')
            || ! $driver->license_expires_at || $driver->license_expires_at->isPast()) {
            return null;
        }

        return $driver;
    }
}
