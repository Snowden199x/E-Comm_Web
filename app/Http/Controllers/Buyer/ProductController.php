<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::with('images')
            ->where('status', 'approved')
            ->when($request->category_id, fn ($q) => $q->where('category_id', $request->category_id))
            ->when($request->search, fn ($q) => $q->where('name', 'like', '%' . $request->search . '%'))
            ->latest()
            ->paginate(12);

        return view('buyer.products.index', compact('products'));
    }

    public function show(Product $product)
    {
        abort_unless($product->status === 'approved', 404);

        $product->load('images', 'category', 'seller.sellerDetail');
        $reviews = $product->publishedReviews()
            ->with(['buyer:id,name', 'reply:id,product_review_id,body,seller_id,created_at', 'reply.seller:id,name'])
            ->latest('id')->paginate(8);
        $ratingSummary = $product->publishedReviews()
            ->select([])->selectRaw('COUNT(*) AS total_reviews, AVG(rating) AS average_rating')
            ->first();

        return view('buyer.products.show', compact('product', 'reviews', 'ratingSummary'));
    }
}
