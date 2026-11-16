<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('users.{userId}', function (User $user, int $userId): bool {
    if ((int) $user->id !== $userId) {
        return false;
    }

    if ($user->status !== 'approved' || $user->archived_at
        || $user->must_change_password
        || ! in_array($user->account_status, [null, 'active'], true)
        || ! in_array($user->role, ['buyer', 'seller', 'logistics_center', 'province_hub', 'station_hub'], true)) {
        return false;
    }

    if ($user->role === 'logistics_center') {
        return $user->logisticsCenterDetail?->company?->status === 'approved';
    }

    if (in_array($user->role, ['province_hub', 'station_hub'], true)) {
        $facility = $user->facilityStaff?->facility;

        return (bool) $facility?->is_active && $facility?->company?->status === 'approved';
    }

    return true;
}, ['guards' => ['web']]);

Broadcast::channel('admin.notifications', function (User $user): bool {
    return $user->role === 'admin'
        && $user->status === 'approved'
        && ! $user->archived_at
        && ! $user->must_change_password
        && in_array($user->account_status, [null, 'active'], true);
}, ['guards' => ['admin']]);
