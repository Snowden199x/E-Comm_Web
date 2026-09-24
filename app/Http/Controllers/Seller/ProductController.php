<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Ecommerce\InventoryMovement;
use App\Models\Ecommerce\Product;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => 'nullable|string|max:100', 'category' => 'nullable|integer',
            'stock_status' => ['nullable', Rule::in(['in_stock', 'low_stock', 'out_of_stock'])],
            'page' => 'nullable|integer|min:1',
        ]);
        $base = Product::where('seller_id', $request->user()->id);
        $counts = [
            'all' => (clone $base)->count(), 'in_stock' => (clone $base)->where('stock', '>', Product::LOW_STOCK_THRESHOLD)->count(),
            'low_stock' => (clone $base)->whereBetween('stock', [1, Product::LOW_STOCK_THRESHOLD])->count(),
            'out_of_stock' => (clone $base)->where('stock', 0)->count(),
        ];
        $categories = Category::whereIn('id', (clone $base)->whereNotNull('category_id')->select('category_id'))
            ->withCount(['products' => fn ($query) => $query->where('seller_id', $request->user()->id)])
            ->orderBy('name')->get();
        $query = (clone $base)->with(['images', 'category']);
        if ($search = trim($filters['search'] ?? '')) {
            $query->where(fn ($q) => $q->where('name', 'like', '%'.$search.'%')->orWhere('product_code', 'like', '%'.$search.'%'));
        }
        if (! empty($filters['category'])) {
            $query->where('category_id', $filters['category']);
        }
        match ($filters['stock_status'] ?? '') {
            'in_stock' => $query->where('stock', '>', Product::LOW_STOCK_THRESHOLD),
            'low_stock' => $query->whereBetween('stock', [1, Product::LOW_STOCK_THRESHOLD]),
            'out_of_stock' => $query->where('stock', 0), default => null,
        };
        $products = $query->latest()->orderByDesc('id')->paginate(10)->withQueryString();
        $lowStock = (clone $base)->with('images')->whereBetween('stock', [1, Product::LOW_STOCK_THRESHOLD])->orderBy('stock')->limit(4)->get();

        return view('seller.products.index', compact('products', 'categories', 'counts', 'lowStock', 'filters'));
    }

    private function categories(Request $request)
    {
        $ids = $request->user()->categories()->pluck('categories.id');

        return Category::whereIn('id', $ids)->orWhereIn('parent_id', $ids)->orderBy('name')->get();
    }

    public function create(Request $request)
    {
        return view('seller.products.form', ['product' => new Product, 'categories' => $this->categories($request)]);
    }

    public function show(Request $request, int $product)
    {
        $mode = $request->validate(['mode' => ['nullable', Rule::in(['view', 'edit', 'restock'])]])['mode'] ?? 'view';
        $product = Product::where('seller_id', $request->user()->id)->with(['category', 'images'])->findOrFail($product);
        if ($mode === 'edit') {
            return view('seller.products.form', ['product' => $product, 'categories' => $this->categories($request)]);
        }
        $movements = $product->movements()->with('user')->latest('id')->paginate(8)->withQueryString();

        return view('seller.products.detail', compact('product', 'movements', 'mode'));
    }

    private function validated(Request $request, bool $creating): array
    {
        return $request->validate([
            'name' => 'required|string|max:255', 'description' => 'nullable|string|max:5000',
            'price' => 'required|numeric|min:0.01|max:99999999.99|decimal:0,2',
            'category_id' => ['required', 'integer', Rule::in($this->categories($request)->pluck('id')->all())],
            'brand' => 'nullable|string|max:255', 'colors' => 'nullable|string|max:255', 'sizes' => 'nullable|string|max:255',
            'stock' => $creating ? 'required|integer|min:0|max:1000000' : 'prohibited',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'expected_revision' => $creating ? 'nullable' : 'required|string',
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request, true);
        $path = $request->file('image')?->store('products', 'public');
        try {
            DB::transaction(function () use ($request, $data, $path) {
                $stock = (int) $data['stock'];
                unset($data['stock'], $data['image'], $data['expected_revision']);
                $product = Product::create($data + ['product_code' => 'PRD-'.Str::ulid(), 'seller_id' => $request->user()->id, 'stock' => 0, 'status' => 'for_review']);
                if ($stock) {
                    app(InventoryService::class)->changeLocked($product, $stock, 'initial', $request->user()->id, reason: 'Opening stock');
                }
                if ($path) {
                    $product->images()->create(['path' => $path, 'sort_order' => 0]);
                }
            });
        } catch (\Throwable $exception) {
            if ($path) {
                Storage::disk('public')->delete($path);
            }
            throw $exception;
        }

        return response()->json(['message' => 'Product submitted for admin review.']);
    }

    public function update(Request $request, int $product)
    {
        Product::where('seller_id', $request->user()->id)->findOrFail($product);
        $data = $this->validated($request, false);
        $path = $request->file('image')?->store('products', 'public');
        try {
            DB::transaction(function () use ($request, $product, $data, $path) {
                $record = Product::where('seller_id', $request->user()->id)->lockForUpdate()->findOrFail($product);
                abort_unless($record->revision === $data['expected_revision'], 409, 'This product changed. Reopen it before editing.');
                unset($data['image'], $data['expected_revision'], $data['stock']);
                $record->fill($data);
                if ($record->isDirty() || $path) {
                    $record->fill(['status' => 'for_review', 'rejection_reason' => null, 'rejection_details' => null])->save();
                    // Preserve prior images; a replacement becomes the first product image.
                    if ($path) {
                        $record->images()->create(['path' => $path, 'sort_order' => ((int) $record->images()->min('sort_order')) - 1]);
                    }
                }
            });
        } catch (\Throwable $exception) {
            if ($path) {
                Storage::disk('public')->delete($path);
            }
            throw $exception;
        }

        return response()->json(['message' => 'Product saved. Changed listings require admin approval.']);
    }

    public function restock(Request $request, int $product)
    {
        $data = $request->validate(['quantity' => 'required|integer|min:1|max:1000000', 'reason' => 'required|string|max:500', 'request_key' => 'required|uuid']);
        DB::transaction(function () use ($request, $product, $data) {
            $record = Product::where('seller_id', $request->user()->id)->lockForUpdate()->findOrFail($product);
            $previous = InventoryMovement::where('request_key', $data['request_key'])->first();
            if ($previous) {
                abort_unless($previous->product_id === $record->id && $previous->user_id === $request->user()->id
                    && (int) $previous->quantity === (int) $data['quantity'] && $previous->reason === $data['reason'], 409, 'This stock update reference was already used.');

                return;
            }
            app(InventoryService::class)->changeLocked($record, (int) $data['quantity'], 'restock', $request->user()->id, reason: $data['reason'], requestKey: $data['request_key']);
        }, 3);

        return response()->json(['message' => 'Stock updated and recorded in inventory history.']);
    }
}
