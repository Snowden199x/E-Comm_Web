<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Ecommerce\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /** Sort choices offered on the listing page; relevance is the default while searching. */
    private const SORTS = ['relevance', 'newest', 'price_low', 'price_high'];

    public function index(Request $request)
    {
        $search = mb_substr(trim((string) $request->query('search', '')), 0, 80);
        $brand = mb_substr(trim((string) $request->query('brand', '')), 0, 80);
        $minPrice = $this->amount($request->query('min_price'));
        $maxPrice = $this->amount($request->query('max_price'));
        if ($minPrice !== null && $maxPrice !== null && $minPrice > $maxPrice) {
            [$minPrice, $maxPrice] = [$maxPrice, $minPrice];
        }
        $inStock = $request->boolean('in_stock');
        $sort = in_array($request->query('sort'), self::SORTS, true) ? $request->query('sort') : null;
        $categoryId = $request->filled('category_id') ? $request->integer('category_id') : null;

        $categoryIds = $categoryId
            ? Category::query()->whereKey($categoryId)->orWhere('parent_id', $categoryId)->pluck('id')
            : null;

        $query = Product::with(['images', 'seller.sellerDetail'])
            ->availableToBuy()->withCardMetrics()
            ->when($categoryIds, fn ($q) => $q->whereIn('category_id', $categoryIds))
            ->when($search !== '', function ($q) use ($search) {
                $like = '%'.$search.'%';
                $q->where(fn ($w) => $w->where('name', 'like', $like)
                    ->orWhere('brand', 'like', $like)
                    ->orWhere('description', 'like', $like));
            })
            ->when($brand !== '', fn ($q) => $q->where('brand', $brand))
            ->when($minPrice !== null, fn ($q) => $q->where('price', '>=', $minPrice))
            ->when($maxPrice !== null, fn ($q) => $q->where('price', '<=', $maxPrice))
            ->when($inStock, fn ($q) => $q->where('stock', '>', 0));

        // Searching defaults to best match; browsing stays newest first.
        $effectiveSort = $sort ?? ($search !== '' ? 'relevance' : 'newest');
        match ($effectiveSort) {
            // A name match outranks a description-only match, then the newest listing wins.
            'relevance' => $query->orderByRaw(
                'CASE WHEN LOWER(name) = ? THEN 0 WHEN LOWER(name) LIKE ? THEN 1 WHEN LOWER(name) LIKE ? THEN 2 ELSE 3 END',
                [mb_strtolower($search), mb_strtolower($search).'%', '%'.mb_strtolower($search).'%'],
            )->latest(),
            'price_low' => $query->orderBy('price')->latest(),
            'price_high' => $query->orderByDesc('price')->latest(),
            default => $query->latest(),
        };

        $products = $query->paginate(12)->withQueryString();

        $brands = Product::query()->availableToBuy()
            ->whereNotNull('brand')->where('brand', '!=', '')
            ->distinct()->orderBy('brand')->limit(60)->pluck('brand');

        return view('buyer.products.index', [
            'products' => $products,
            'brands' => $brands,
            'filters' => compact('search', 'brand', 'minPrice', 'maxPrice', 'inStock', 'sort', 'categoryId', 'effectiveSort'),
        ]);
    }

    /** A non-negative money amount from the query string, or null when it is not a number. */
    private function amount(mixed $value): ?float
    {
        return is_numeric($value) ? max(0, (float) $value) : null;
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
