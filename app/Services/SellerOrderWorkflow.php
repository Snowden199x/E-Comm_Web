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
                $items = $order->items()->orderBy('product_id')->orderBy('product_variant_id')->get();
                $products = Product::whereIn('id', $items->pluck('product_id'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
                foreach ($items as $item) {
                    $product = $products->get($item->product_id);
                    $variant = $item->product_variant_id ? $product->variants()->findOrFail($item->product_variant_id) : null;
                    app(InventoryService::class)->changeLocked($product, (int) $item->quantity, 'cancellation', $seller->id, $order->id, $reason, variant: $variant);
                }
            }
            if ($to === 'ready_for_pickup') {
                $order->pickup_request_status = 'pending';
                $order->pickup_decline_reason = null;
                $order->pickup_verified_at = null;
            }
            $order->update(['status' => $to]);
            if ($to === 'ready_for_pickup') {
                $previousCenterId = $order->logistics_center_id;
                app(OrderRoutingService::class)->route($order);
                if ($previousCenterId) {
                    $center = $order->logisticsCenter;
                    if ($center) {
                        Notification::create([
                            'user_id' => $center->user_id, 'type' => 'logistics_pickup_request',
                            'title' => 'Pickup request '.$order->number,
                            'message' => 'The seller marked this parcel ready for pickup.',
                            'link' => route('logistics.incoming-parcels'),
                        ]);
                    }
                }
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
