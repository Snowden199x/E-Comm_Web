<?php

namespace App\Http\Controllers\Api\Buyer;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\SellerVoucher;
use App\Models\Ecommerce\SellerVoucherRedemption;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The Buyer mobile "My Vouchers" list. The web page lists the same shop vouchers
 * with the same labels; this endpoint hands the app one JSON row per voucher so
 * the copy is written once on the server.
 */
class BuyerVoucherController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $buyerId = (int) $request->user()->id;

        $vouchers = SellerVoucher::query()
            ->whereNull('deleted_at')
            ->where('status', 'active')
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->whereHas('seller', fn ($seller) => $seller->where('role', 'seller')->where('status', 'approved')
                ->whereNull('archived_at')
                ->where(fn ($active) => $active->whereNull('account_status')->orWhere('account_status', 'active')))
            ->with(['seller.sellerDetail', 'products:id,name', 'categories:id,name'])
            // Vouchers that already started come first, then the soonest to expire.
            ->orderByRaw('starts_at is not null and starts_at > ?', [now()])
            ->orderBy('ends_at')
            ->orderBy('name')
            ->limit(60)
            ->get();

        $usedCounts = SellerVoucherRedemption::query()
            ->where('buyer_id', $buyerId)
            ->whereNull('voided_at')
            ->whereIn('voucher_id', $vouchers->pluck('id'))
            ->selectRaw('voucher_id, count(*) as total')
            ->groupBy('voucher_id')
            ->pluck('total', 'voucher_id');

        $data = $vouchers->map(function (SellerVoucher $voucher) use ($usedCounts) {
            $used = (int) ($usedCounts[$voucher->id] ?? 0);
            $seller = $voucher->seller;
            $reason = $this->unavailableReason($voucher, $used);

            return [
                'id' => $voucher->id,
                'shop_id' => $voucher->seller_id,
                'shop' => $seller?->sellerDetail?->business_name ?: $seller?->name,
                'name' => $voucher->name,
                'code' => $voucher->code,
                'discount_label' => $this->discountLabel($voucher),
                'scope_label' => $this->scopeLabel($voucher),
                'min_spend_label' => (float) $voucher->min_spend > 0
                    ? 'Min. spend ₱'.number_format((float) $voucher->min_spend, fmod((float) $voucher->min_spend, 1) ? 2 : 0)
                    : 'No minimum spend',
                'max_discount_label' => $voucher->type === 'percent' && $voucher->max_discount !== null
                    ? 'Up to ₱'.number_format((float) $voucher->max_discount, fmod((float) $voucher->max_discount, 1) ? 2 : 0)
                    : null,
                'valid_label' => $this->validLabel($voucher),
                'uses_left' => max(0, (int) $voucher->per_buyer_limit - $used),
                'shop_uses_left' => $voucher->usage_limit === null
                    ? null
                    : max(0, (int) $voucher->usage_limit - (int) $voucher->used_count),
                'usable' => $reason === null,
                'unavailable_reason' => $reason,
            ];
        })->values();

        return response()->json(['data' => $data]);
    }

    /** Why this voucher cannot be used right now, or null when it can. */
    private function unavailableReason(SellerVoucher $voucher, int $usedByBuyer): ?string
    {
        if ($voucher->starts_at?->isFuture()) {
            return 'Starts '.$voucher->starts_at->format('M j, Y g:i A');
        }
        if ($voucher->usage_limit !== null && (int) $voucher->used_count >= (int) $voucher->usage_limit) {
            return 'The shop voucher use limit has been reached.';
        }
        if ($usedByBuyer >= (int) $voucher->per_buyer_limit) {
            return 'You have reached this voucher’s use limit.';
        }

        return null;
    }

    private function discountLabel(SellerVoucher $voucher): string
    {
        return $voucher->type === 'percent'
            ? rtrim(rtrim(number_format((float) $voucher->value, 2, '.', ''), '0'), '.').'% off'
            : '₱'.number_format((float) $voucher->value, 2).' off';
    }

    private function scopeLabel(SellerVoucher $voucher): string
    {
        return match ($voucher->scope) {
            'products' => 'Eligible products: '.$voucher->products->pluck('name')->implode(', '),
            'categories' => 'Eligible categories: '.$voucher->categories->pluck('name')->implode(', '),
            default => 'Eligible on all items from this shop',
        };
    }

    private function validLabel(SellerVoucher $voucher): string
    {
        if ($voucher->starts_at?->isFuture()) {
            return 'Starts '.$voucher->starts_at->format('M j, Y');
        }
        if ($voucher->ends_at) {
            return 'Until '.$voucher->ends_at->format('M j, Y');
        }

        return 'No expiry';
    }
}
