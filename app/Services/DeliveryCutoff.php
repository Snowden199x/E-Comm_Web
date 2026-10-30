<?php

namespace App\Services;

use App\Models\Communication\Notification;
use App\Models\Ecommerce\Order;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Closes the daily delivery window.
 *
 * A parcel that is still `out_for_delivery` when the cutoff (5:00 PM by
 * default) passes is marked `delivery_failed`, the Rider and Station Hub are
 * told, and the parcel shows up in the Rider app with a Reschedule action that
 * books the next delivery attempt for tomorrow.
 *
 * This project has no scheduler on the development path, so the sweep runs
 * lazily whenever the Rider app loads its assignments or the Station Hub opens
 * a delivery list. `routes/console.php` also schedules it, so a host with the
 * scheduler running closes the window even when nobody is looking.
 */
class DeliveryCutoff
{
    /** Local time of the daily cutoff, e.g. "5:00 PM". */
    public function label(): string
    {
        return $this->cutoffFor()->format('g:i A');
    }

    /** The cutoff on a given day (today when omitted), in the app timezone. */
    public function cutoffFor(?CarbonInterface $day = null): CarbonImmutable
    {
        $date = $day
            ? CarbonImmutable::parse($day->toDateString())
            : CarbonImmutable::today();

        return $date->setTimeFromTimeString(config('delivery.cutoff', '17:00'));
    }

    /** Whether today's window has already closed. */
    public function isClosed(?CarbonInterface $at = null): bool
    {
        $at = $at ? CarbonImmutable::instance($at) : CarbonImmutable::now();

        return $at->greaterThanOrEqualTo($this->cutoffFor($at));
    }

    /** True when the next attempt is booked for a later day. */
    public function isScheduledForLater(Order $order): bool
    {
        return $order->delivery_scheduled_for !== null
            && $order->delivery_scheduled_for->toDateString() > today()->toDateString();
    }

    /** The cutoff that applies to this parcel's current attempt. */
    public function deadlineFor(Order $order): CarbonImmutable
    {
        if ($order->status === 'delivery_failed' && $order->delivery_failed_at) {
            return $this->cutoffFor($order->delivery_failed_at);
        }

        $scheduled = $order->delivery_scheduled_for;
        if ($scheduled && $scheduled->toDateString() >= today()->toDateString()) {
            return $this->cutoffFor($scheduled);
        }

        return $this->cutoffFor();
    }

    /** e.g. "5:00 PM today" or "31 Oct 2026, 5:00 PM". */
    public function deadlineLabel(Order $order): string
    {
        $deadline = $this->deadlineFor($order);

        return $deadline->isToday()
            ? $deadline->format('g:i A').' today'
            : $deadline->format('j M Y, g:i A');
    }

    /**
     * Mark every parcel still out for delivery as failed. Returns the number
     * of parcels closed. Safe to call on any read: the once-a-minute cache
     * guard keeps it from repeating work on a busy page.
     */
    public function sweep(): int
    {
        $now = now();
        if (! $this->isClosed($now)) {
            return 0;
        }

        if (! Cache::add('delivery-window-swept:'.$now->toDateString(), true, $now->copy()->addMinute())) {
            return 0;
        }

        $failed = 0;
        Order::query()
            ->where('status', 'out_for_delivery')
            ->whereNotNull('delivery_courier_id')
            ->orderBy('id')
            ->chunkById(100, function ($orders) use (&$failed) {
                foreach ($orders as $order) {
                    if ($this->failOne((int) $order->id)) {
                        $failed++;
                    }
                }
            });

        return $failed;
    }

    private function failOne(int $orderId): bool
    {
        return (bool) DB::transaction(function () use ($orderId) {
            $order = Order::query()->with('destinationLogisticsCenter')
                ->lockForUpdate()->find($orderId);

            // Re-read inside the lock: the Rider may have completed the
            // delivery between the sweep query and this row.
            if (! $order || $order->status !== 'out_for_delivery') {
                return false;
            }

            $order->status = 'delivery_failed';
            $order->delivery_failed_at = now();
            // Buyer and Seller get the standard failed-delivery update from the
            // Order model's status listener.
            $order->save();

            $order->statusEvents()->create([
                'user_id' => null,
                'from_status' => 'out_for_delivery',
                'to_status' => 'delivery_failed',
                'note' => 'The '.$this->label().' delivery window closed before this parcel was delivered. The Rider can reschedule it for another day.',
            ]);

            if ($order->delivery_courier_id) {
                Notification::create([
                    'user_id' => $order->delivery_courier_id,
                    'type' => 'delivery_window_closed',
                    'title' => 'Delivery window closed '.$order->number,
                    'message' => 'The '.$this->label().' deadline passed before this parcel was delivered. Open it in Parcels and reschedule the delivery to try again.',
                ]);
            }

            $hubUserId = $order->destinationLogisticsCenter?->user_id;
            if ($hubUserId) {
                Notification::create([
                    'user_id' => $hubUserId,
                    'type' => 'shipment_update',
                    'title' => 'Delivery failed '.$order->number,
                    'message' => 'The '.$this->label().' delivery window closed before delivery. The Rider can reschedule it, or assign the parcel again from the delivery queue.',
                    'link' => route('lgp.station.delivery-queue'),
                ]);
            }

            return true;
        }, 3);
    }
}
