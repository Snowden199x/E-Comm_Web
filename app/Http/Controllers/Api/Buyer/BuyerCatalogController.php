<?php

namespace App\Http\Controllers\Api\Buyer;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Communication\Announcement;
use App\Models\Ecommerce\Product;
use App\Models\User;
use App\Services\BuyerApiPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Storefront read endpoints for the Buyer mobile app. They reuse the same availability scopes as
 * the web pages (Product::availableToBuy, approved sellers), so only buyable listings appear.
 */
class BuyerCatalogController extends Controller
{
    public function __construct(private BuyerApiPresenter $presenter) {}

    public function home(Request $request): JsonResponse
    {
        $products = Product::with(['images', 'seller.sellerDetail'])
            ->availableToBuy()->withCardMetrics()
            ->latest()->take(18)->get();

        $announcements = Announcement::query()
            ->whereIn('audience', ['All Users', 'Buyers & Sellers', 'Buyers Only'])
            ->where(fn ($query) => $query->where('status', 'published')
                ->orWhere(fn ($scheduled) => $scheduled->where('status', 'scheduled')
                    ->whereNotNull('scheduled_at')->where('scheduled_at', '<=', now())))
            ->latest()->take(5)->get();

        return response()->json([
            'categories' => $this->topCategories(),
            'announcements' => $announcements->map(fn (Announcement $announcement) => [
                'id' => $announcement->id,
                'title' => $announcement->title,
                'message' => $announcement->message,
            ])->values()->all(),
            'products' => $products->map(fn (Product $product) => $this->presenter->productCard($product))->values()->all(),
        ]);
    }

    public function categories(): JsonResponse
    {
        $categories = Category::query()->with('children:id,parent_id,name')->orderBy('name')->get();

        return response()->json([
            'categories' => $categories->whereNull('parent_id')->values()->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'children' => $category->children->map(fn (Category $child) => [
                    'id' => $child->id, 'name' => $child->name,
                ])->values()->all(),
            ])->all(),
        ]);
    }

    public function products(Request $request): JsonResponse
    {
        $data = $request->validate([
            'category_id' => ['nullable', 'integer'],
            'search' => ['nullable', 'string', 'max:100'],
            'sort' => ['nullable', Rule::in(['latest', 'price_asc', 'price_desc'])],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $categoryIds = isset($data['category_id'])
            ? Category::query()->whereKey($data['category_id'])
                ->orWhere('parent_id', $data['category_id'])->pluck('id')
            : null;

        $products = Product::with(['images', 'seller.sellerDetail'])
            ->availableToBuy()->withCardMetrics()
            ->when($categoryIds, fn ($query) => $query->whereIn('category_id', $categoryIds))
            ->when(filled($data['search'] ?? null), fn ($query) => $query->where('name', 'like', '%'.$data['search'].'%'))
            ->when(($data['sort'] ?? 'latest') === 'price_asc', fn ($query) => $query->orderBy('price')->orderBy('id'))
            ->when(($data['sort'] ?? 'latest') === 'price_desc', fn ($query) => $query->orderByDesc('price')->orderBy('id'))
            ->when(($data['sort'] ?? 'latest') === 'latest', fn ($query) => $query->latest())
            ->paginate(12);

        return $this->paginated($products, fn (Product $product) => $this->presenter->productCard($product));
    }

    public function product(Product $product): JsonResponse
    {
        abort_unless($product->status === 'approved' && $product->seller?->status === 'approved'
            && ! $product->seller->archived_at
            && (! $product->seller->account_status || $product->seller->account_status === 'active'), 404);

        $product->load('images', 'category.parent', 'seller.sellerDetail', 'variants', 'specifications');

        $recommended = Product::query()
            ->availableToBuy()->withCardMetrics()->with(['images', 'seller.sellerDetail'])
            ->where('id', '!=', $product->id)
            ->when($product->category, function ($query) use ($product) {
                $parentId = $product->category->parent_id ?: $product->category->id;
                $ids = Category::query()->whereKey($parentId)->orWhere('parent_id', $parentId)->pluck('id');
                $query->whereIn('category_id', $ids);
            })
            ->where('stock', '>', 0)
            ->latest()->take(8)->get();

        return response()->json([
            'product' => $this->presenter->productDetail($product),
            'recommended' => $recommended->map(fn (Product $item) => $this->presenter->productCard($item))->values()->all(),
        ]);
    }

    public function sellers(Request $request): JsonResponse
    {
        $data = $request->validate(['search' => ['nullable', 'string', 'max:100']]);
        $search = trim($data['search'] ?? '');

        $sellers = User::query()->where('role', 'seller')->where('status', 'approved')
            ->whereNull('archived_at')
            ->where(fn ($query) => $query->whereNull('account_status')->orWhere('account_status', 'active'))
            ->when($search !== '', fn ($query) => $query->where(fn ($match) => $match
                ->where('name', 'like', '%'.$search.'%')
                ->orWhereHas('sellerDetail', fn ($detail) => $detail->where('business_name', 'like', '%'.$search.'%'))))
            ->with('sellerDetail')
            ->withCount(['products as approved_products_count' => fn ($query) => $query->where('status', 'approved')])
            ->orderBy('name')->paginate(12);

        return $this->paginated($sellers, fn (User $seller) => $this->presenter->sellerSummary($seller));
    }

    public function seller(Request $request, User $seller): JsonResponse
    {
        abort_unless($seller->role === 'seller' && $seller->status === 'approved'
            && ! $seller->archived_at && (! $seller->account_status || $seller->account_status === 'active'), 404);

        $data = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'sort' => ['nullable', Rule::in(['latest', 'price_asc', 'price_desc'])],
        ]);
        $search = trim($data['search'] ?? '');
        $sort = $data['sort'] ?? 'latest';

        $seller->load('sellerDetail');
        $seller->loadCount(['products as approved_products_count' => fn ($query) => $query->where('status', 'approved')]);

        $products = Product::query()->where('seller_id', $seller->id)->where('status', 'approved')
            ->when($search !== '', fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
            ->with(['images', 'seller.sellerDetail'])->withCardMetrics()
            ->when($sort === 'price_asc', fn ($query) => $query->orderBy('price')->orderBy('id'))
            ->when($sort === 'price_desc', fn ($query) => $query->orderByDesc('price')->orderBy('id'))
            ->when($sort === 'latest', fn ($query) => $query->latest())
            ->paginate(12);

        return $this->paginated($products, fn (Product $product) => $this->presenter->productCard($product), [
            'seller' => $this->presenter->sellerSummary($seller),
        ]);
    }

    private function topCategories(): array
    {
        return Category::query()->whereNull('parent_id')->orderBy('name')->get()
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
            ])->values()->all();
    }

    private function paginated($paginator, callable $map, array $extra = []): JsonResponse
    {
        return response()->json($extra + [
            'data' => collect($paginator->items())->map($map)->values()->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }
}
