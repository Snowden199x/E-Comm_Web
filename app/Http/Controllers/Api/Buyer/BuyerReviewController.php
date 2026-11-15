<?php

namespace App\Http\Controllers\Api\Buyer;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\ProductReview;
use App\Services\BuyerApiPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The Buyer's own product reviews, newest first, with the Seller's reply when
 * there is one. Submitting a review stays on `POST /order-items/{item}/review`.
 */
class BuyerReviewController extends Controller
{
    public function index(Request $request, BuyerApiPresenter $presenter): JsonResponse
    {
        $reviews = ProductReview::query()
            ->where('buyer_id', $request->user()->id)
            ->with(['product:id,name', 'product.images', 'seller.sellerDetail', 'reply'])
            ->latest('id')
            ->limit(60)
            ->get()
            ->map(function (ProductReview $review) use ($presenter) {
                $seller = $review->seller;
                $reply = $review->reply;

                return [
                    'id' => $review->id,
                    'order_id' => $review->order_id,
                    'product_id' => $review->product_id,
                    'product_name' => $review->product?->name ?? 'Product no longer listed',
                    'image' => $presenter->imageUrl($review->product?->images->first()?->path),
                    'shop' => $seller?->sellerDetail?->business_name ?: $seller?->name,
                    'rating' => (int) $review->rating,
                    'comment' => (string) $review->comment,
                    'visibility' => $review->visibility ?: 'published',
                    'created_at_label' => $review->created_at?->format('M j, Y'),
                    'reply' => $reply ? [
                        'body' => (string) $reply->body,
                        'at_label' => $reply->created_at?->format('M j, Y'),
                    ] : null,
                ];
            })
            ->values();

        return response()->json(['data' => $reviews]);
    }
}
