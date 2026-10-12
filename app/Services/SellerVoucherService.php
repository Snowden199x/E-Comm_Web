<?php

namespace App\Services;

use App\Models\Ecommerce\SellerVoucher;
use App\Models\Ecommerce\SellerVoucherRedemption;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class SellerVoucherService
{
    /** Returns the discount in centavos for one seller's selected cart lines. */
    public function discountCents(SellerVoucher $voucher, Collection $items, Collection $products, Collection $variants, int $buyerId): int
    {
        if ($voucher->deleted_at || $voucher->status !== 'active'
            || ($voucher->starts_at && $voucher->starts_at->isFuture())
            || ($voucher->ends_at && $voucher->ends_at->isPast())
            || ($voucher->usage_limit !== null && $voucher->used_count >= $voucher->usage_limit)
            || $voucher->redemptions()->where('buyer_id', $buyerId)->whereNull('voided_at')->count() >= $voucher->per_buyer_limit) {
            throw ValidationException::withMessages(['voucher_codes' => 'This voucher is no longer available.']);
        }

        $productIds = $voucher->scope === 'products' ? array_map('intval', $voucher->products()->pluck('products.id')->all()) : [];
        $categoryIds = $voucher->scope === 'categories' ? array_map('intval', $voucher->categories()->pluck('categories.id')->all()) : [];
        $eligibleCents = 0;
        foreach ($items as $item) {
            $product = $products->get($item->product_id);
            if (! $product || $product->seller_id !== $voucher->seller_id) {
                throw ValidationException::withMessages(['voucher_codes' => 'This voucher does not belong to the selected shop.']);
            }
            $parentId = $product->category?->parent_id ?: $product->category_id;
            if ($voucher->scope === 'products' && ! in_array($product->id, $productIds, true)) continue;
            if ($voucher->scope === 'categories' && ! in_array($parentId, $categoryIds, true)) continue;
            $variant = $item->product_variant_id ? $variants->get($item->product_variant_id) : null;
            if ($item->product_variant_id && $variant?->product_id !== $product->id) {
                throw ValidationException::withMessages(['voucher_codes' => 'A selected product variant changed. Refresh checkout.']);
            }
            $price = $variant?->price ?? $product->price;
            if ($price === null) {
                throw ValidationException::withMessages(['voucher_codes' => 'A selected product changed. Refresh checkout.']);
            }
            $eligibleCents += (int) round((float) $price * 100) * $item->quantity;
        }

        if ($eligibleCents === 0 || $eligibleCents < (int) round((float) $voucher->min_spend * 100)) {
            throw ValidationException::withMessages(['voucher_codes' => 'Your eligible items do not meet this voucher’s minimum spend.']);
        }

        $discount = $voucher->type === 'percent'
            ? intdiv($eligibleCents * (int) round((float) $voucher->value * 100) + 5000, 10000)
            : (int) round((float) $voucher->value * 100);
        if ($voucher->type === 'percent' && $voucher->max_discount !== null) {
            $discount = min($discount, (int) round((float) $voucher->max_discount * 100));
        }

        return min($discount, $eligibleCents);
    }

    public function redeemLocked(SellerVoucher $voucher, int $orderId, int $buyerId, int $discountCents): void
    {
        SellerVoucherRedemption::create([
            'voucher_id' => $voucher->id, 'order_id' => $orderId, 'buyer_id' => $buyerId,
            'discount' => number_format($discountCents / 100, 2, '.', ''),
        ]);
        $voucher->increment('used_count');
    }

    public function voidForOrderLocked(int $orderId): void
    {
        $redemption = SellerVoucherRedemption::query()->where('order_id', $orderId)->lockForUpdate()->first();
        if (! $redemption || $redemption->voided_at) return;

        $voucher = SellerVoucher::query()->whereKey($redemption->voucher_id)->lockForUpdate()->firstOrFail();
        $redemption->update(['voided_at' => now()]);
        $voucher->decrement('used_count');
    }
}
