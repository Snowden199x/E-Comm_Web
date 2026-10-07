<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Communication\MarketplaceConversation;
use App\Models\Ecommerce\Order;
use App\Models\User;
use Illuminate\Contracts\View\View;

class BuyerProfileController extends Controller
{
    public function show(User $buyer): View
    {
        abort_unless($buyer->role === 'buyer', 404);

        $sellerId = auth()->id();
        $orderQuery = Order::query()->where('seller_id', $sellerId)->where('buyer_id', $buyer->id);
        $hasConversation = MarketplaceConversation::query()->where('seller_id', $sellerId)
            ->where('buyer_id', $buyer->id)->exists();

        abort_unless($hasConversation || $orderQuery->exists(), 404);

        $buyer->load('buyerDetail');
        $orderCount = $orderQuery->count();

        return view('seller.buyers.show', compact('buyer', 'orderCount'));
    }
}
