<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\Product;
use App\Models\User;

class SellerProfileController extends Controller
{
    public function show(User $seller)
    {
        abort_unless(auth()->user()?->role === 'buyer', 403);
        abort_unless($seller->role === 'seller' && $seller->status === 'approved'
            && ! $seller->archived_at && (! $seller->account_status || $seller->account_status === 'active'), 404);

        $seller->load('sellerDetail');
        $products = Product::query()->where('seller_id', $seller->id)->where('status', 'approved')
            ->with('images')->latest()->paginate(12);
        return view('buyer.sellers.show', compact('seller', 'products'));
    }
}
