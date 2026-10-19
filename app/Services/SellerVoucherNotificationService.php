<?php

namespace App\Services;

use App\Models\Communication\Notification;
use App\Models\Ecommerce\SellerVoucher;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SellerVoucherNotificationService
{
    /** Announce the first activation once, together with the voucher save transaction. */
    public function announceFirstActivation(SellerVoucher $voucher): void
    {
        $voucher = SellerVoucher::query()->whereKey($voucher->id)->lockForUpdate()->firstOrFail();
        if ($voucher->status !== 'active' || $voucher->deleted_at || $voucher->new_voucher_notified_at) return;

        $seller = User::query()->with('sellerDetail')->findOrFail($voucher->seller_id);
        $shopName = $seller->sellerDetail?->business_name ?: $seller->name;
        $scope = match ($voucher->scope) {
            'products' => 'Selected products: '.$voucher->products()->orderBy('name')->pluck('name')->implode(', '),
            'categories' => 'Selected categories: '.$voucher->categories()->orderBy('name')->pluck('name')->implode(', '),
            default => 'All items from '.$shopName,
        };
        $availability = $voucher->starts_at?->isFuture()
            ? ' It starts '.$voucher->starts_at->format('M j, Y g:i A').'.'
            : '';
        $buyers = User::query()->whereIn('id', DB::table('orders')->select('buyer_id')->where('seller_id', $seller->id)->distinct())
            ->where('role', 'buyer')->where('status', 'approved')
            ->whereNull('archived_at')->where(fn ($query) => $query->whereNull('account_status')->orWhere('account_status', 'active'))
            ->orderBy('id');
        $buyers->chunkById(100, function ($batch) use ($voucher, $shopName, $scope, $availability, $seller) {
            foreach ($batch as $buyer) {
                $inserted = DB::table('seller_voucher_notification_recipients')->insertOrIgnore([
                    'voucher_id' => $voucher->id,
                    'buyer_id' => $buyer->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                if ($inserted === 0) continue;

                $notification = Notification::create([
                    'user_id' => $buyer->id,
                    'type' => 'seller_voucher',
                    'title' => Str::limit('New voucher from '.$shopName, 255, ''),
                    'message' => $voucher->name.' ('.$voucher->code.'): '.$scope.'.'.$availability.' Visit the shop to see eligible items.',
                    'link' => route('buyer.sellers.show', $seller),
                ]);

                DB::table('seller_voucher_notification_recipients')
                    ->where('voucher_id', $voucher->id)->where('buyer_id', $buyer->id)
                    ->update(['notification_id' => $notification->id, 'updated_at' => now()]);
            }
        });

        $voucher->update(['new_voucher_notified_at' => now()]);
    }
}
