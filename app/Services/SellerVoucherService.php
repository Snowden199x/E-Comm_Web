<?php

namespace App\Services;

use App\Models\Ecommerce\SellerVoucher;
use App\Models\Ecommerce\SellerVoucherRedemption;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class SellerVoucherService
{
    /** Maximum vouchers a single Seller order may use. */
    public const MAX_STACK = 2;

    /** Returns active, unexpired vouchers matching one seller's selected checkout lines, including unavailable reasons. */
    public function checkoutOptions(int $sellerId, Collection $items, Collection $products, Collection $variants, int $buyerId): array
    {
        $vouchers = SellerVoucher::query()->where('seller_id', $sellerId)->whereNull('deleted_at')
            ->where('status', 'active')
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->with(['products:id,name', 'categories:id,name'])->orderBy('name')->get();

        return $vouchers->map(function (SellerVoucher $voucher) use ($items, $products, $variants, $buyerId) {
            $productIds = $voucher->products->pluck('id')->map(fn ($id) => (int) $id)->all();
            $categoryIds = $voucher->categories->pluck('id')->map(fn ($id) => (int) $id)->all();
            $matches = $items->filter(function ($item) use ($voucher, $products, $productIds, $categoryIds) {
                $product = $products->get($item->product_id);
                if (! $product) return false;
                if ($voucher->scope === 'products') return in_array((int) $product->id, $productIds, true);
                if ($voucher->scope === 'categories') {
                    $mainCategoryId = (int) ($product->category?->parent_id ?: $product->category_id);
                    return in_array($mainCategoryId, $categoryIds, true);
                }
                return true;
            });

            if ($matches->isEmpty()) return null;
            $reason = null;
            $discountCents = 0;
            if ($voucher->starts_at?->isFuture()) {
                $reason = 'Starts '.$voucher->starts_at->format('M j, Y g:i A');
            } elseif ($voucher->usage_limit !== null && $voucher->used_count >= $voucher->usage_limit) {
                $reason = 'The shop voucher use limit has been reached.';
            } elseif ($voucher->redemptions()->where('buyer_id', $buyerId)->whereNull('voided_at')->count() >= $voucher->per_buyer_limit) {
                $reason = 'You have reached this voucher’s use limit.';
            } else {
                try {
                    $discountCents = $this->discountCents($voucher, $matches, $products, $variants, $buyerId);
                } catch (ValidationException $exception) {
                    $reason = collect($exception->errors())->flatten()->first() ?: 'This voucher is not available for these items.';
                }
            }

            $scopeLabel = match ($voucher->scope) {
                'products' => 'Eligible products: '.$voucher->products->pluck('name')->implode(', '),
                'categories' => 'Eligible categories: '.$voucher->categories->pluck('name')->implode(', '),
                default => 'Eligible on all items from this shop',
            };

            return [
                'name' => $voucher->name,
                'code' => $voucher->code,
                'scope' => $voucher->scope,
                'scope_label' => $scopeLabel,
                'eligible' => $reason === null,
                'reason' => $reason,
                'starts_at' => $voucher->starts_at?->format('M j, Y g:i A'),
                'min_spend' => (float) $voucher->min_spend,
                'discount' => number_format($discountCents / 100, 2, '.', ''),
                'discount_label' => $voucher->type === 'percent'
                    ? rtrim(rtrim(number_format((float) $voucher->value, 2, '.', ''), '0'), '.').'% off'
                    : '₱'.number_format((float) $voucher->value, 2).' off',
                'ends_at' => $voucher->ends_at?->format('M j, Y g:i A'),
                'usage_limit' => $voucher->usage_limit,
                'remaining_uses' => $voucher->usage_limit === null ? null : max(0, $voucher->usage_limit - $voucher->used_count),
                'per_buyer_limit' => $voucher->per_buyer_limit,
            ];
        })->filter()->values()->all();
    }

    /** Returns the discount in centavos for one voucher on one seller's selected cart lines. */
    public function discountCents(SellerVoucher $voucher, Collection $items, Collection $products, Collection $variants, int $buyerId): int
    {
        return $this->stackedDiscountCents([$voucher], $items, $products, $variants, $buyerId)['total'];
    }

    /**
     * Discount for up to two vouchers on one Seller order.
     *
     * Rule: a shop-wide voucher and one product/category voucher may stack. Two shop-wide
     * vouchers, or two item vouchers, cannot. The shop-wide voucher is applied first; the
     * second voucher's percentage is computed on what is left, and the total never exceeds
     * the selected items' value.
     *
     * @return array{lines: array<string,int>, total:int}
     */
    public function stackedDiscountCents(array $vouchers, Collection $items, Collection $products, Collection $variants, int $buyerId): array
    {
        $vouchers = array_values($vouchers);
        if ($vouchers === [] || count($vouchers) > self::MAX_STACK) {
            throw ValidationException::withMessages(['voucher_codes' => 'Use one or two shop vouchers for this order.']);
        }

        $sellerId = $vouchers[0]->seller_id;
        $scopes = [];
        foreach ($vouchers as $voucher) {
            if ($voucher->seller_id !== $sellerId) {
                throw ValidationException::withMessages(['voucher_codes' => 'Shop vouchers from different shops cannot be combined.']);
            }
            $scope = $voucher->scope === 'all' ? 'all' : 'item';
            if (in_array($scope, $scopes, true)) {
                throw ValidationException::withMessages(['voucher_codes' => $scope === 'all'
                    ? 'Only one shop-wide voucher can be used on a shop order.'
                    : 'Only one product or category voucher can be used on a shop order.']);
            }
            $scopes[] = $scope;
        }

        // Shop-wide first so the item voucher is calculated on the remaining amount.
        usort($vouchers, fn ($a, $b) => ($a->scope === 'all' ? 0 : 1) <=> ($b->scope === 'all' ? 0 : 1));

        $orderCents = 0;
        foreach ($items as $item) {
            $product = $products->get($item->product_id);
            $variant = $item->product_variant_id ? $variants->get($item->product_variant_id) : null;
            $price = $variant?->price ?? $product?->price;
            if ($price !== null && $product?->seller_id === $sellerId) {
                $orderCents += (int) round((float) $price * 100) * $item->quantity;
            }
        }

        $lines = [];
        $applied = 0;
        foreach ($vouchers as $voucher) {
            $eligible = $this->eligibleCents($voucher, $items, $products, $variants, $buyerId);
            $base = max(0, $eligible - min($eligible, $applied));
            $discount = $this->discountFor($voucher, $base);
            $lines[$voucher->code] = $discount;
            $applied += $discount;
        }

        return ['lines' => $lines, 'total' => min($applied, $orderCents)];
    }

    /** Eligible item value for one voucher, after checking it can still be used. */
    private function eligibleCents(SellerVoucher $voucher, Collection $items, Collection $products, Collection $variants, int $buyerId): int
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

        return $eligibleCents;
    }

    /** Percent/fixed discount for a given eligible amount, capped at that amount. */
    private function discountFor(SellerVoucher $voucher, int $eligibleCents): int
    {
        if ($eligibleCents <= 0) {
            return 0;
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

    /** Releases every voucher redeemed on an order that was cancelled before pickup. */
    public function voidForOrderLocked(int $orderId): void
    {
        $redemptions = SellerVoucherRedemption::query()->where('order_id', $orderId)
            ->whereNull('voided_at')->lockForUpdate()->get();

        foreach ($redemptions as $redemption) {
            $voucher = SellerVoucher::query()->whereKey($redemption->voucher_id)->lockForUpdate()->first();
            if (! $voucher) {
                $redemption->update(['voided_at' => now()]);
                continue;
            }
            $redemption->update(['voided_at' => now()]);
            $voucher->decrement('used_count');
        }
    }
}
