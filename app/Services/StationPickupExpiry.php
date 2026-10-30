<?php

namespace App\Services;

use App\Models\Communication\Notification;
use App\Models\Ecommerce\Order;
use App\Models\Profiles\RiderTask;
use Illuminate\Support\Facades\DB;

/**
 * Releases a pickup assignment the assigned Rider never started.
 *
 * The station hub assigns a pickup Rider from the Pickup Requests or Task
 * Assignment screen, and the Rider is expected to open the Rider app and scan
 * the parcel. A task still sitting in `assigned` once the deadline passes is
 * expired here: the task becomes `expired`, the order stops pointing at that
 * Rider, and both the Rider and the staff member who assigned are notified.
 * The parcel then reappears on the assignable list so the hub can hand it to
 * another Rider.
 *
 * This project has no scheduler, so the sweep runs lazily whenever the station
 * hub reads its lists or assigns a Rider - the only moments a reassignment can
 * happen anyway.
 */
class StationPickupExpiry
{
    /** Minutes a Rider has to scan the parcel before the assignment is released. */
    public const MINUTES = 60;

    /** Expire overdue pickup tasks for one station. Returns how many were released. */
    public function expireOverdue(int $stationHubId): int
    {
        $expired = 0;

        $ids = RiderTask::query()
            ->where('type', 'pickup')
            ->where('status', 'assigned')
            ->whereNotNull('assigned_at')
            ->where('assigned_at', '<=', now()->subMinutes(self::MINUTES))
            ->where('station_hub_id', $stationHubId)
            ->pluck('id');

        foreach ($ids as $id) {
            if ($this->expireOne((int) $id)) {
                $expired++;
            }
        }

        return $expired;
    }

    private function expireOne(int $taskId): bool
    {
        return (bool) DB::transaction(function () use ($taskId) {
            $task = RiderTask::query()->whereKey($taskId)->lockForUpdate()->first();

            // Re-read inside the lock: the Rider may have scanned while we were deciding.
            if (! $task || $task->status !== 'assigned' || $task->started_at || ! $task->assigned_at
                || $task->assigned_at->greaterThan(now()->subMinutes(self::MINUTES))) {
                return false;
            }

            $order = Order::query()->whereKey($task->order_id)->lockForUpdate()->first();
            if (! $order) {
                return false;
            }

            $riderUserId = $task->rider?->user_id;
            $released = $riderUserId !== null
                && (int) $order->courier_id === (int) $riderUserId;

            if ($released) {
                $order->courier_id = null;
                $order->save();
            }

            $task->status = 'expired';
            $task->failure_reason = 'No pickup scan within '.self::MINUTES.' minutes of assignment.';
            $task->save();

            // Only a parcel still waiting for pickup can be reassigned, so only
            // that case is announced. A cancelled or already collected parcel just
            // loses the stale task.
            if ($released && $order->status === 'ready_for_pickup') {
                Notification::create([
                    'user_id' => $riderUserId,
                    'type' => 'rider_task_expired',
                    'title' => 'Pickup assignment expired '.$order->number,
                    'message' => 'Your station hub assigned this parcel for pickup and no scan arrived within '
                        .self::MINUTES.' minutes, so the assignment was released. Another Rider can take it now.',
                ]);
                if ($task->assigned_by) {
                    Notification::create([
                        'user_id' => $task->assigned_by,
                        'type' => 'rider_task_expired',
                        'title' => 'Pickup assignment expired '.$order->number,
                        'message' => 'The assigned Rider did not scan this parcel within '.self::MINUTES
                            .' minutes, so the pickup request is free to assign again.',
                        'link' => route('lgp.station.pickups'),
                    ]);
                }
            }

            return true;
        }, 3);
    }
}
