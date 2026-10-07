<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Ecommerce\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $categoryIds = $request->filled('category_id')
            ? Category::query()->whereKey($request->integer('category_id'))
                ->orWhere('parent_id', $request->integer('category_id'))->pluck('id')
            : null;

        $products = Product::with(['images', 'seller.sellerDetail'])
            ->availableToBuy()->withCardMetrics()
            ->when($categoryIds, fn ($q) => $q->whereIn('category_id', $categoryIds))
            ->when($request->search, fn ($q) => $q->where('name', 'like', '%' . $request->search . '%'))
            ->latest()
            ->paginate(12);

        return view('buyer.products.index', compact('products'));
    }

    public function show(Product $product)
    {
        abort_unless($product->status === 'approved' && $product->seller?->status === 'approved'
            && ! $product->seller->archived_at
            && (! $product->seller->account_status || $product->seller->account_status === 'active'), 404);

        $product->load('images', 'category.parent', 'seller.sellerDetail', 'variants', 'attributeValues', 'specifications');
        $category = $product->category;
        $schemas = config('product-attributes', []);
        $schemaKey = $category ? Str::slug(str_replace('&', 'and', $category->name)) : null;
        if ($category && ! isset($schemas[$schemaKey])) {
            $schemaKey = $category->parent
                ? Str::slug(str_replace('&', 'and', $category->parent->name))
                : null;
        }
        $attributeLabels = collect(data_get($schemas, $schemaKey.'.fields', []))
            ->mapWithKeys(fn (array $field) => [$field['key'] => $field['label']])
            ->all();
        $recommendedProducts = collect();

        if ($category) {
            $recommendationParentId = $category->parent_id ?: $category->id;
            $recommendationCategoryIds = Category::query()
                ->whereKey($recommendationParentId)
                ->orWhere('parent_id', $recommendationParentId)
                ->pluck('id');

            $recommendedProducts = Product::query()
                ->availableToBuy()
                ->where('stock', '>', 0)
                ->whereIn('category_id', $recommendationCategoryIds)
                ->where('id', '!=', $product->id)
                ->with(['images', 'seller.sellerDetail'])
                ->withCardMetrics()
                ->latest()
                ->limit(4)
                ->get();
        }
        $reviews = $product->publishedReviews()
            ->with(['buyer:id,name', 'reply:id,product_review_id,body,seller_id,created_at', 'reply.seller:id,name'])
            ->latest('id')->paginate(8);
        $ratingSummary = $product->publishedReviews()
            ->select([])->selectRaw('COUNT(*) AS total_reviews, AVG(rating) AS average_rating')
            ->first();

        return view('buyer.products.show', compact('product', 'reviews', 'ratingSummary', 'attributeLabels', 'recommendedProducts'));
    }
}
