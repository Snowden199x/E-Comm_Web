<?php

namespace App\Services;

use App\Models\Ecommerce\Product;
use App\Models\Ecommerce\ProductVariant;
use App\Models\Communication\Notification;

class InventoryService
{
    // The caller must hold the product row lock inside its business transaction.
    public function changeLocked(Product $product, int $quantity, string $type, ?int $actorId, ?int $orderId = null, ?string $reason = null, ?string $requestKey = null, ?ProductVariant $variant = null): void
    {
        if ($product->has_variations) {
            abort_unless($variant && $variant->product_id === $product->id, 422, 'Choose a product variant for this stock change.');
            $variant = ProductVariant::query()->where('product_id', $product->id)->lockForUpdate()->findOrFail($variant->id);
            $variantAfter = (int) $variant->stock + $quantity;
            abort_if($variantAfter < 0 || $variantAfter > 1000000, 422, 'The resulting variant stock is outside the allowed range.');
        } else {
            abort_if($variant !== null, 422, 'This product does not use variants.');
        }
        $before = (int) $product->stock;
        $after = $before + $quantity;
        abort_if($after < 0 || $after > 2147483647, 422, 'The resulting stock quantity is outside the allowed range.');
        if ($variant) {
            $variantBefore = $variant->stock;
            $variant->update(['stock' => $variantAfter]);
            if (($variantAfter === 0 && $variantBefore > 0)
                || ($variantAfter > 0 && $variantAfter <= Product::LOW_STOCK_THRESHOLD
                    && $variantBefore > Product::LOW_STOCK_THRESHOLD)) {
                Notification::create([
                    'user_id' => $product->seller_id, 'type' => 'inventory_alert',
                    'title' => ($variantAfter === 0 ? 'Out of stock' : 'Low stock').': '.$product->name.' · '.$variant->label,
                    'message' => $variantAfter.' units remain. Check Products & Inventory.',
                    'link' => route('seller.products.show', $product),
                ]);
            }
        }
        $product->update(['stock' => $after]);
        $product->movements()->create([
            'user_id' => $actorId, 'order_id' => $orderId, 'product_variant_id' => $variant?->id, 'type' => $type,
            'quantity' => $quantity, 'stock_before' => $before, 'stock_after' => $after,
            'reason' => $reason, 'request_key' => $requestKey,
        ]);
    }
}
