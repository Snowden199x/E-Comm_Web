<?php

namespace App\Services;

use App\Models\Communication\Notification;
use App\Models\Ecommerce\Order;
use App\Models\Ecommerce\Product;
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
 * A parcel gets two attempts. The first failure returns it to the destination
 * Station Hub for a final attempt the next day; the second failure cancels the
 * order automatically, restores the stock and notifies the Buyer, Seller, hub
 * and Rider. `orders.delivery_attempts` records the count.
 *
 * This project has no scheduler on the development path, so the sweep runs
 * lazily whenever the Rider app loads its assignments or the Station Hub opens
 * a delivery list. `routes/console.php` also schedules it, so a host with the
 * scheduler running closes the window even when nobody is looking.
 */
class DeliveryCutoff
{
    /** Failed delivery attempts allowed before the parcel is cancelled automatically. */
    public const MAX_DELIVERY_ATTEMPTS = 2;

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

        // A parcel that already failed (or was scheduled for a day that has passed) and still was not
        // delivered used its final chance: cancel it automatically instead of leaving it stuck.
        Order::query()
            ->where(function ($query) {
                $query->where(function ($failed) {
                    $failed->where('status', 'delivery_failed')->whereNotNull('delivery_failed_at')
                        ->whereDate('delivery_failed_at', '<', today()->toDateString());
                })->orWhere(function ($scheduled) {
                    $scheduled->where('status', 'assigned_to_rider')->whereNotNull('delivery_scheduled_for')
                        ->whereDate('delivery_scheduled_for', '<', today()->toDateString())
                        ->where('delivery_attempts', '>=', 1);
                });
            })
            ->orderBy('id')
            ->chunkById(100, function ($orders) use (&$failed) {
                foreach ($orders as $order) {
                    if ($this->cancelStaleFailure((int) $order->id)) {
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

            $attempts = (int) $order->delivery_attempts + 1;
            if ($attempts >= self::MAX_DELIVERY_ATTEMPTS) {
                $this->cancelUndelivered($order, $attempts);

                return true;
            }

            $station = $order->destinationLogisticsCenter;
            $stationLabel = $station
                ? $station->business_name.' ('.($station->municipality ?: $station->province).')'
                : 'the destination Station Hub';

            $order->delivery_attempts = $attempts;
            $order->status = 'delivery_failed';
            $order->delivery_failed_at = now();
            // Buyer and Seller get the standard failed-delivery update from the
            // Order model's status listener.
            $order->save();

            $order->statusEvents()->create([
                'user_id' => null,
                'from_status' => 'out_for_delivery',
                'to_status' => 'delivery_failed',
                'note' => 'Delivery attempt '.$attempts.' of '.self::MAX_DELIVERY_ATTEMPTS.' failed before the '.$this->label()
                    .' window closed. The parcel returns to '.$stationLabel.'. One final attempt can be booked for the next day.',
            ]);

            if ($order->delivery_courier_id) {
                Notification::create([
                    'user_id' => $order->delivery_courier_id,
                    'type' => 'delivery_window_closed',
                    'title' => 'Delivery window closed '.$order->number,
                    'message' => 'Attempt '.$attempts.' of '.self::MAX_DELIVERY_ATTEMPTS.' failed. The parcel is back at '
                        .$stationLabel.'. Reschedule the final attempt for tomorrow — if it fails again the order is cancelled automatically.',
                ]);
            }

            Notification::create([
                'user_id' => $order->buyer_id,
                'type' => 'order_update',
                'title' => 'Delivery attempt failed '.$order->number,
                'message' => 'We could not deliver your parcel today. It returns to '.$stationLabel
                    .' for one final attempt tomorrow. If that attempt also fails, the order is cancelled automatically.',
                'link' => route('buyer.orders.show', $order),
            ]);

            $hubUserId = $station?->user_id;
            if ($hubUserId) {
                Notification::create([
                    'user_id' => $hubUserId,
                    'type' => 'shipment_update',
                    'title' => 'Delivery failed '.$order->number,
                    'message' => 'Attempt '.$attempts.' of '.self::MAX_DELIVERY_ATTEMPTS.' failed and the parcel is back. Schedule the final attempt for tomorrow; a second failure cancels the order automatically.',
                    'link' => route('lgp.station.delivery-queue'),
                ]);
            }

            return true;
        }, 3);
    }

    /** Cancel a parcel that stayed failed past the next day without a successful attempt. */
    private function cancelStaleFailure(int $orderId): bool
    {
        return (bool) DB::transaction(function () use ($orderId) {
            $order = Order::query()->with('destinationLogisticsCenter')->lockForUpdate()->find($orderId);
            if (! $order) {
                return false;
            }

            $today = today()->toDateString();
            $staleFailed = $order->status === 'delivery_failed' && $order->delivery_failed_at
                && $order->delivery_failed_at->toDateString() < $today;
            $staleScheduled = $order->status === 'assigned_to_rider' && $order->delivery_scheduled_for
                && $order->delivery_scheduled_for->toDateString() < $today && (int) $order->delivery_attempts >= 1;

            if (! $staleFailed && ! $staleScheduled) {
                return false;
            }

            $attempts = min(self::MAX_DELIVERY_ATTEMPTS, (int) $order->delivery_attempts + 1);
            $this->cancelUndelivered($order, max($attempts, self::MAX_DELIVERY_ATTEMPTS), $order->status);

            return true;
        }, 3);
    }

    /**
     * Second failed attempt: the parcel is cancelled automatically. Stock returns to the Seller, the
     * voucher is voided and everyone involved is told. Runs inside the caller's transaction and row lock.
     */
    private function cancelUndelivered(Order $order, int $attempts, string $fromStatus = 'delivery_failed'): void
    {
        $items = $order->items()->orderBy('product_id')->orderBy('product_variant_id')->get();
        $products = Product::query()->whereIn('id', $items->pluck('product_id')->unique())
            ->orderBy('id')->lockForUpdate()->get()->keyBy('id');

        foreach ($items as $item) {
            $product = $products->get($item->product_id);
            if (! $product) {
                continue;
            }
            $variant = $item->product_variant_id
                ? $product->variants()->lockForUpdate()->find($item->product_variant_id)
                : null;

            app(InventoryService::class)->changeLocked(
                $product,
                (int) $item->quantity,
                'cancellation',
                null,
                $order->id,
                'Cancelled automatically after '.$attempts.' failed delivery attempts',
                variant: $variant,
            );
        }

        $station = $order->destinationLogisticsCenter;
        $stationLabel = $station
            ? $station->business_name.' ('.($station->municipality ?: $station->province).')'
            : 'the destination Station Hub';

        $order->delivery_attempts = $attempts;
        $order->status = 'cancelled';
        $order->delivery_failed_at = now();
        $order->save();
        app(SellerVoucherService::class)->voidForOrderLocked($order->id);

        $order->statusEvents()->create([
            'user_id' => null,
            'from_status' => $fromStatus,
            'to_status' => 'cancelled',
            'note' => 'Cancelled automatically: '.$attempts.' delivery attempts failed and the buyer did not receive the parcel. '
                .'The parcel is held at '.$stationLabel.' and the stock went back to the seller.',
        ]);

        Notification::create([
            'user_id' => $order->buyer_id,
            'type' => 'order_update',
            'title' => $order->number.': Order cancelled',
            'message' => 'We tried to deliver this parcel '.$attempts.' times and it was not received, so the order was cancelled automatically. No payment is needed.',
            'link' => route('buyer.orders.show', $order),
        ]);
        Notification::create([
            'user_id' => $order->seller_id,
            'type' => 'order_update',
            'title' => $order->number.': Cancelled after failed deliveries',
            'message' => 'The parcel failed '.$attempts.' delivery attempts and was cancelled automatically. The items are back in stock.',
            'link' => route('seller.orders.show', $order),
        ]);
        if ($order->delivery_courier_id) {
            Notification::create([
                'user_id' => $order->delivery_courier_id,
                'type' => 'shipment_cancelled',
                'title' => $order->number.' cancelled',
                'message' => 'Both delivery attempts failed, so this parcel is cancelled. Return it to '.$stationLabel.' stock.',
            ]);
        }
        if ($station?->user_id) {
            Notification::create([
                'user_id' => $station->user_id,
                'type' => 'shipment_update',
                'title' => $order->number.' cancelled',
                'message' => 'The second delivery attempt failed, so the order was cancelled automatically. Hold the parcel at the hub.',
                'link' => route('lgp.station.delivery-queue'),
            ]);
        }
    }
}
