<?php

namespace App\Services;

use App\Models\Communication\Notification;
use App\Models\Ecommerce\SellerVoucher;
use Illuminate\Support\Facades\DB;

class SellerVoucherExpirationNotificationService
{
    /** Create any due Seller alerts once, when the Seller next polls notifications. */
    public function notifyExpiredForSeller(int $sellerId): void
    {
        $expiredVouchers = SellerVoucher::query()->where('seller_id', $sellerId)->whereNull('deleted_at')
            ->where('status', 'active')->whereNull('expired_notified_at')
            ->where(function ($query) {
                $query->where(fn ($q) => $q->whereNotNull('ends_at')->where('ends_at', '<=', now()))
                    ->orWhere(fn ($q) => $q->whereNotNull('usage_limit')->whereColumn('used_count', '>=', 'usage_limit'));
            })->orderBy('id')->pluck('id');

        foreach ($expiredVouchers as $voucherId) {
            DB::transaction(function () use ($sellerId, $voucherId) {
                $voucher = SellerVoucher::query()->whereKey($voucherId)->lockForUpdate()->first();
                if (! $voucher || $voucher->seller_id !== $sellerId || $voucher->status !== 'active'
                    || $voucher->deleted_at || $voucher->expired_notified_at) return;

                $endedByDate = $voucher->ends_at && $voucher->ends_at->isPast();
                $reason = $endedByDate ? 'It reached its end date.' : 'Its usage limit was reached.';
                Notification::create([
                    'user_id' => $sellerId,
                    'type' => 'seller_voucher_expired',
                    'title' => 'Voucher ended: '.$voucher->name,
                    'message' => 'Voucher '.$voucher->code.' is no longer available. '.$reason,
                    'link' => route('seller.vouchers.index'),
                ]);
                $voucher->update(['expired_notified_at' => now()]);
            }, 3);
        }
    }
}
