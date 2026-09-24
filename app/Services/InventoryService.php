<?php

namespace App\Services;

use App\Models\Ecommerce\Product;

class InventoryService
{
    // The caller must hold the product row lock inside its business transaction.
    public function changeLocked(Product $product, int $quantity, string $type, ?int $actorId, ?int $orderId = null, ?string $reason = null, ?string $requestKey = null): void
    {
        $before = (int) $product->stock;
        $after = $before + $quantity;
        abort_if($after < 0 || $after > 2147483647, 422, 'The resulting stock quantity is outside the allowed range.');
        $product->update(['stock' => $after]);
        $product->movements()->create([
            'user_id' => $actorId, 'order_id' => $orderId, 'type' => $type,
            'quantity' => $quantity, 'stock_before' => $before, 'stock_after' => $after,
            'reason' => $reason, 'request_key' => $requestKey,
        ]);
    }
}
