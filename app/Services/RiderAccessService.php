<?php

namespace App\Services;

use App\Models\Profiles\LogisticsCenter;
use App\Models\User;

class RiderAccessService
{
    public function approvedCenter(?User $rider): ?LogisticsCenter
    {
        if (! $rider || $rider->role !== 'courier' || $rider->status !== 'approved'
            || $rider->archived_at || ($rider->account_status && $rider->account_status !== 'active')) {
            return null;
        }

        $rider->loadMissing('courierDetail.logisticsCenter.user', 'courierDetail.stationHub.company.logisticsCenter.user');
        $center = $rider->courierDetail?->logisticsCenter;
        $owner = $center?->user;
        $station = $rider->courierDetail?->stationHub;

        if ($station) {
            $company = $station->company;
            $companyOwner = $company?->logisticsCenter?->user;

            return $station->is_active && $company?->status === 'approved'
                && $companyOwner && $companyOwner->status === 'approved'
                && ! $companyOwner->archived_at
                && (! $companyOwner->account_status || $companyOwner->account_status === 'active')
                ? $center
                : null;
        }

        return $owner && $owner->role === 'logistics_center' && $owner->status === 'approved'
            && ! $owner->archived_at && (! $owner->account_status || $owner->account_status === 'active')
            ? $center
            : null;
    }
}
