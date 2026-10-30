<?php

namespace App\Services;

use App\Models\Ecommerce\CartItem;

/**
 * The checkout_revision fingerprint. It covers the cart lines and the live product/variant
 * price, stock and options, so an order is only placed against exactly what the Buyer reviewed.
 * Shared by the web checkout and the Buyer mobile API so both compare the same hash.
 */
class CheckoutRevisionService
{
    public function for(iterable $cartItems, $products, $variants): string
    {
        return hash('sha256', json_encode(collect($cartItems)->map(function ($item) use ($products, $variants) {
            $product = $products->get($item->product_id);
            $variant = $item->product_variant_id ? $variants->get($item->product_variant_id) : null;

            return [
                $item->id, $item->product_id, $item->product_variant_id, $item->quantity, $item->color, $item->size,
                $product?->price, $product?->stock, $product?->status,
                $product?->colors, $product?->sizes, $product?->seller_id,
                $variant?->price, $variant?->stock, $variant?->options,
            ];
        })->all()));
    }

    /** Convenience for a buyer's selected cart lines that are already loaded. */
    public function forLoadedCart(iterable $cartItems): string
    {
        $items = collect($cartItems);

        return $this->for(
            $items,
            $items->mapWithKeys(fn (CartItem $item) => [$item->product_id => $item->product]),
            $items->filter(fn (CartItem $item) => $item->variant)->mapWithKeys(fn (CartItem $item) => [$item->variant->id => $item->variant]),
        );
    }
}
