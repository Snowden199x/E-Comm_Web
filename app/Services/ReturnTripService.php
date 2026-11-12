<?php

namespace App\Services;

use App\Models\Communication\Notification;
use App\Models\Ecommerce\Order;
use App\Models\Ecommerce\OrderReturnRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * The physical return leg after a Seller approves a return: the Rider collects the parcel from the
 * Buyer and hands it back to the Seller. Stock was already restored at approval, so this only records
 * the trip, tells both sides where the parcel is, and keeps the events in the order timeline.
 */
class ReturnTripService
{
    public function pickup(User $rider, Order $order, string $scanKey): array
    {
        return DB::transaction(function () use ($rider, $order, $scanKey) {
            $record = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $return = $this->approvedReturn($record);

            abort_unless($record->status === 'returned', 409, 'Only an approved return can be collected from the buyer.');
            abort_unless((int) $record->delivery_courier_id === (int) $rider->id, 403, 'This return is not assigned to you.');
            abort_unless($return->return_status === OrderReturnRequest::RETURN_STATUS_PENDING_PICKUP, 409,
                'This return is not waiting for pickup any more.');

            if ($this->alreadyScanned($record, $scanKey, 'return_pickup', $rider)) {
                return $this->payload($record, $return, true, 'This return pickup was already recorded.');
            }

            $return->update([
                'return_status' => OrderReturnRequest::RETURN_STATUS_PICKED_UP,
                'pickup_rider_id' => $rider->id,
                'picked_up_at' => now(),
            ]);

            $note = 'Rider '.$rider->name.' collected the returned parcel from the buyer. It is on the way back to the seller.';
            $record->statusEvents()->create([
                'user_id' => $rider->id, 'from_status' => 'returned', 'to_status' => 'returned', 'note' => $note,
            ]);
            $this->recordScan($record, $rider, 'return_pickup', $scanKey);

            $this->notify('Return parcel picked up '.$record->number,
                'The rider collected the parcel for return. It is on the way back to the seller.',
                route('buyer.orders.show', $record), $record->buyer_id);
            $this->notify('Return parcel on the way back '.$record->number,
                'Rider '.$rider->name.' collected the returned parcel and will hand it over to you.',
                route('seller.returns.index'), $record->seller_id);

            return $this->payload($record, $return, false, 'Return collected from the buyer. Hand it over to the seller next.');
        }, 3);
    }

    public function handover(User $rider, Order $order, string $scanKey): array
    {
        return DB::transaction(function () use ($rider, $order, $scanKey) {
            $record = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $return = $this->approvedReturn($record);

            abort_unless($record->status === 'returned', 409, 'This order is not an approved return.');
            abort_unless((int) $return->pickup_rider_id === (int) $rider->id, 403, 'This return is not assigned to you.');
            abort_unless($return->return_status === OrderReturnRequest::RETURN_STATUS_PICKED_UP, 409,
                'Collect the parcel from the buyer before handing it to the seller.');

            if ($this->alreadyScanned($record, $scanKey, 'returned_to_seller', $rider)) {
                return $this->payload($record, $return, true, 'This return handover was already recorded.');
            }

            $return->update([
                'return_status' => OrderReturnRequest::RETURN_STATUS_DELIVERED_TO_SELLER,
                'delivered_to_seller_at' => now(),
            ]);

            $note = 'Rider '.$rider->name.' handed the returned parcel back to the seller.';
            $record->statusEvents()->create([
                'user_id' => $rider->id, 'from_status' => 'returned', 'to_status' => 'returned', 'note' => $note,
            ]);
            $this->recordScan($record, $rider, 'returned_to_seller', $scanKey);

            $this->notify('Return parcel handed to the seller '.$record->number,
                'The rider returned the parcel to the seller.',
                route('buyer.orders.show', $record), $record->buyer_id);
            $this->notify('Return parcel received '.$record->number,
                'Rider '.$rider->name.' handed the returned parcel back to you.',
                route('seller.returns.index'), $record->seller_id);

            return $this->payload($record, $return, false, 'Return handed over to the seller.');
        }, 3);
    }

    /** The Seller can confirm receipt on the web if the Rider app cannot finish the scan. */
    public function confirmReceived(User $seller, OrderReturnRequest $return): OrderReturnRequest
    {
        return DB::transaction(function () use ($seller, $return) {
            $record = OrderReturnRequest::query()->whereKey($return->id)->lockForUpdate()->firstOrFail();
            abort_unless((int) $record->seller_id === (int) $seller->id, 403);
            abort_unless($record->status === OrderReturnRequest::STATUS_APPROVED, 409, 'This return was not approved.');
            abort_unless($record->return_status === OrderReturnRequest::RETURN_STATUS_PICKED_UP, 409,
                'The rider has not collected the parcel yet.');

            $record->update([
                'return_status' => OrderReturnRequest::RETURN_STATUS_DELIVERED_TO_SELLER,
                'delivered_to_seller_at' => now(),
            ]);

            $order = Order::query()->find($record->order_id);
            if ($order) {
                $order->statusEvents()->create([
                    'user_id' => $seller->id, 'from_status' => 'returned', 'to_status' => 'returned',
                    'note' => 'Seller confirmed the returned parcel was received.',
                ]);
                $this->notify('Seller received the return '.$order->number,
                    'The seller confirmed the returned parcel arrived.',
                    route('buyer.orders.show', $order), $order->buyer_id);
            }

            return $record->fresh();
        }, 3);
    }

    private function approvedReturn(Order $order): OrderReturnRequest
    {
        $return = $order->returnRequests()
            ->where('status', OrderReturnRequest::STATUS_APPROVED)
            ->latest('decided_at')
            ->first();

        abort_unless($return, 409, 'This order has no approved return.');

        return $return;
    }

    /** A retried request with the same key is answered with the current state instead of writing again. */
    private function alreadyScanned(Order $order, string $scanKey, string $type, User $rider): bool
    {
        $existing = DB::table('order_scan_events')
            ->where('order_id', $order->id)->where('scan_key', $scanKey)->first();

        if (! $existing) {
            return false;
        }

        abort_unless((int) $existing->actor_id === (int) $rider->id && $existing->scan_type === $type,
            409, 'This scan key was already used for another action.');

        return true;
    }

    private function recordScan(Order $order, User $rider, string $type, string $scanKey): void
    {
        DB::table('order_scan_events')->insert([
            'order_id' => $order->id,
            'actor_id' => $rider->id,
            'logistics_center_id' => $order->destination_logistics_center_id,
            'scan_type' => $type,
            'from_status' => 'returned',
            'to_status' => 'returned',
            'scan_key' => $scanKey,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function notify(string $title, string $message, string $link, ?int $userId): void
    {
        if (! $userId) {
            return;
        }

        Notification::create([
            'user_id' => $userId,
            'type' => 'order_update',
            'title' => $title,
            'message' => $message,
            'link' => $link,
        ]);
    }

    private function payload(Order $order, OrderReturnRequest $return, bool $duplicate, string $message): array
    {
        return [
            'order_id' => $order->id,
            'tracking_number' => $order->tracking_number,
            'order_number' => $order->number,
            'return_status' => $return->return_status,
            'return_status_label' => $return->return_status_label,
            'picked_up_at' => $return->picked_up_at?->toIso8601String(),
            'delivered_to_seller_at' => $return->delivered_to_seller_at?->toIso8601String(),
            'duplicate' => $duplicate,
            'message' => $message,
        ];
    }
}
