<?php

namespace App\Services;

use App\Models\Communication\Notification;
use App\Models\Ecommerce\Order;
use App\Models\Ecommerce\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SellerOrderWorkflow
{
    public function transition(User $seller, int $orderId, string $action, string $expectedStatus, ?string $reason = null): void
    {
        DB::transaction(function () use ($seller, $orderId, $action, $expectedStatus, $reason) {
            $order = Order::where('seller_id', $seller->id)->lockForUpdate()->findOrFail($orderId);
            $transitions = [
                'accept' => [['placed'], 'confirmed'],
                'decline' => [['placed'], 'cancelled'],
                'prepare' => [['confirmed'], 'preparing'],
                'ready' => [['preparing'], 'ready_for_pickup'],
                'cancel_shipment' => [['confirmed', 'preparing', 'ready_for_pickup'], 'cancelled'],
            ];
            abort_unless(isset($transitions[$action]), 422, 'Unknown order action.');
            [$allowed, $to] = $transitions[$action];
            $from = $order->status;
            abort_unless($from === $expectedStatus && in_array($from, $allowed, true), 409, 'This order has changed or the action is unavailable. Reload its details.');
            if ($to === 'cancelled') {
                abort_unless(trim($reason ?? '') !== '', 422, 'Enter a cancellation reason.');
                $quantities = $order->items()->get()->groupBy('product_id')->map(fn ($items) => $items->sum('quantity'));
                $products = Product::whereIn('id', $quantities->keys())->orderBy('id')->lockForUpdate()->get();
                foreach ($products as $product) {
                    app(InventoryService::class)->changeLocked($product, (int) $quantities[$product->id], 'cancellation', $seller->id, $order->id, $reason);
                }
            }
            $order->update(['status' => $to]);
            if ($to === 'ready_for_pickup') {
                app(OrderRoutingService::class)->route($order);
            }
            $order->statusEvents()->create(['user_id' => $seller->id, 'from_status' => $from, 'to_status' => $to, 'note' => $to === 'cancelled' ? $reason : null]);
            Notification::create([
                'user_id' => $order->buyer_id, 'type' => 'order_update',
                'title' => $order->number.': '.Order::STATUSES[$to],
                'message' => $to === 'cancelled' ? $reason : 'Your order status has been updated.',
                'link' => route('buyer.orders.show', $order),
            ]);
            if ($to === 'cancelled' && $order->courier_id) {
                Notification::create(['user_id' => $order->courier_id, 'type' => 'shipment_cancelled', 'title' => $order->number.' cancelled', 'message' => $reason]);
            }
        }, 3);
    }
}
