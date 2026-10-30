<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\SellerVoucher;
use App\Models\Ecommerce\SellerVoucherRedemption;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->role === 'buyer', 403);

        $vouchers = SellerVoucher::query()
            ->whereNull('deleted_at')
            ->where('status', 'active')
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->whereHas('seller', fn ($seller) => $seller->where('role', 'seller')->where('status', 'approved')
                ->whereNull('archived_at')
                ->where(fn ($active) => $active->whereNull('account_status')->orWhere('account_status', 'active')))
            ->with(['seller.sellerDetail', 'products:id,name', 'categories:id,name'])
            ->orderByRaw('starts_at is not null and starts_at > ?', [now()])
            ->orderBy('ends_at')
            ->orderBy('name')
            ->paginate(12);

        $usedCounts = SellerVoucherRedemption::query()
            ->where('buyer_id', $request->user()->id)
            ->whereNull('voided_at')
            ->whereIn('voucher_id', $vouchers->pluck('id'))
            ->selectRaw('voucher_id, count(*) as total')
            ->groupBy('voucher_id')
            ->pluck('total', 'voucher_id');

        return view('buyer.vouchers.index', compact('vouchers', 'usedCounts'));
    }
}
