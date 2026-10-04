<?php

namespace App\Services;

use App\Models\Communication\Notification;
use App\Models\Ecommerce\Order;
use App\Models\Ecommerce\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OrderCancellationService
{
    public const CANCELLABLE_STATUSES = ['placed', 'confirmed', 'preparing', 'ready_for_pickup'];

    public const BUYER_REASONS = [
        'changed_mind' => 'Changed my mind',
        'ordered_by_mistake' => 'Ordered by mistake',
        'delivery_timing' => 'Delivery timing no longer works for me',
        'found_elsewhere' => 'Found the item elsewhere',
        'other' => 'Other',
    ];

    public const SELLER_REASONS = [
        'item_unavailable' => 'Item is unavailable',
        'unable_to_fulfill' => 'Unable to fulfill the order',
        'listing_error' => 'Product listing or price error',
        'other' => 'Other',
    ];

    public function cancelForBuyer(User $buyer, int $orderId, string $expectedStatus, string $reasonKey, ?string $details = null): void
    {
        abort_unless($buyer->role === 'buyer', 403);
        abort_unless(isset(self::BUYER_REASONS[$reasonKey]), 422, 'Choose a valid cancellation reason.');

        $reason = $this->formatReason(self::BUYER_REASONS[$reasonKey], $details, $reasonKey === 'other');

        DB::transaction(function () use ($buyer, $orderId, $expectedStatus, $reason) {
            $order = Order::query()->where('buyer_id', $buyer->id)->lockForUpdate()->findOrFail($orderId);
            abort_unless($order->status === $expectedStatus, 409, 'This order has changed. Reload its details.');
            abort_unless(in_array($order->status, self::CANCELLABLE_STATUSES, true), 409, 'This order can no longer be cancelled.');

            $this->cancelLocked($order, $buyer, $reason);
        }, 3);
    }

    public function sellerReason(?string $reasonKey, ?string $details = null): string
    {
        if (isset(self::SELLER_REASONS[$reasonKey ?? ''])) {
            return $this->formatReason(self::SELLER_REASONS[$reasonKey], $details, $reasonKey === 'other');
        }

        // Keep existing seller integrations that submit a free-text reason working.
        return trim((string) $reasonKey);
    }

    public function cancelLocked(Order $order, User $actor, string $reason): void
    {
        abort_unless(in_array($actor->role, ['buyer', 'seller'], true), 403);
        $reason = trim($reason);
        abort_unless($reason !== '', 422, 'Choose or enter a cancellation reason.');
        abort_unless(in_array($order->status, self::CANCELLABLE_STATUSES, true), 409, 'This order can no longer be cancelled.');

        $fromStatus = $order->status;
        $items = $order->items()->orderBy('product_id')->orderBy('product_variant_id')->get();
        $products = Product::query()->whereIn('id', $items->pluck('product_id')->unique())
            ->orderBy('id')->lockForUpdate()->get()->keyBy('id');

        foreach ($items as $item) {
            $product = $products->get($item->product_id);
            abort_unless($product, 409, 'A product on this order is no longer available for stock restoration.');
            $variant = $item->product_variant_id
                ? $product->variants()->lockForUpdate()->findOrFail($item->product_variant_id)
                : null;

            app(InventoryService::class)->changeLocked(
                $product,
                (int) $item->quantity,
                'cancellation',
                $actor->id,
                $order->id,
                $reason,
                variant: $variant,
            );
        }

        $order->update(['status' => 'cancelled']);
        $actorLabel = $actor->role === 'buyer' ? 'Buyer' : 'Seller';
        $eventNote = $actorLabel.' cancellation reason: '.$reason;
        $order->statusEvents()->create([
            'user_id' => $actor->id,
            'from_status' => $fromStatus,
            'to_status' => 'cancelled',
            'note' => $eventNote,
        ]);

        $recipientId = $actor->role === 'buyer' ? $order->seller_id : $order->buyer_id;
        $recipientRoute = $actor->role === 'buyer' ? 'seller.orders.show' : 'buyer.orders.show';
        Notification::create([
            'user_id' => $recipientId,
            'type' => 'order_update',
            'title' => $order->number.': Order cancelled',
            'message' => $eventNote,
            'link' => route($recipientRoute, $order),
        ]);

        foreach (collect([$order->courier_id, $order->delivery_courier_id])->filter()->unique() as $riderId) {
            Notification::create([
                'user_id' => $riderId,
                'type' => 'shipment_cancelled',
                'title' => $order->number.' cancelled',
                'message' => $eventNote,
            ]);
        }

        if ($order->logistics_center_id && $order->pickup_request_status === 'pending') {
            $center = $order->logisticsCenter;
            if ($center) {
                Notification::create([
                    'user_id' => $center->user_id,
                    'type' => 'logistics_pickup_cancelled',
                    'title' => $order->number.' pickup cancelled',
                    'message' => $eventNote,
                    'link' => route('logistics.incoming-parcels'),
                ]);
            }
        }
    }

    private function formatReason(string $label, ?string $details, bool $detailsRequired): string
    {
        $details = trim((string) $details);
        abort_unless(! $detailsRequired || $details !== '', 422, 'Add details for the selected reason.');

        return $details !== '' ? $label.': '.$details : $label;
    }
}
