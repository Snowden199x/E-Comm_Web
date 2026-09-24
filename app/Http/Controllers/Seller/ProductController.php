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
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    private const MAX_PHOTOS = 6;

    private const MAX_UPLOAD_BYTES = 7 * 1024 * 1024;

    private const SIZES = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];

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
        $data = $request->validate([
            'name' => 'required|string|max:255', 'description' => 'nullable|string|max:5000',
            'price' => 'required|numeric|min:0.01|max:99999999.99|decimal:0,2',
            'category_id' => ['required', 'integer', Rule::in($this->categories($request)->pluck('id')->all())],
            'brand' => 'nullable|string|max:255',
            'material' => 'required|string|max:255',
            'weight' => ['required', 'string', 'max:30', 'regex:/^\d+(?:\.\d{1,2})?\s*(?:g|kg)$/i'],
            'sizes' => 'nullable|array|max:6', 'sizes.*' => ['string', Rule::in(self::SIZES)],
            'colors' => 'nullable|array|max:10',
            'colors.*' => ['string', 'max:30', 'regex:/^[\pL][\pL\s\-]{0,29}$/u'],
            'stock' => $creating ? 'required|integer|min:0|max:1000000' : 'prohibited',
            'main_image' => [$creating ? 'required' : 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'gallery_images' => 'nullable|array|max:5',
            'gallery_images.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_images' => $creating ? 'prohibited' : 'nullable|array|max:6',
            'remove_images.*' => 'integer|distinct|min:1',
            'expected_revision' => $creating ? 'nullable' : 'required|string',
        ]);

        $uploads = array_filter([$request->file('main_image'), ...($request->file('gallery_images') ?? [])]);
        if (array_sum(array_map(fn ($file) => $file->getSize(), $uploads)) > self::MAX_UPLOAD_BYTES) {
            throw ValidationException::withMessages(['gallery_images' => 'All new photos together must be 7 MB or less.']);
        }

        $sizes = array_values(array_unique($data['sizes'] ?? []));
        $colors = [];
        foreach ($data['colors'] ?? [] as $color) {
            $color = trim(preg_replace('/\s+/u', ' ', $color));
            if ($color !== '' && ! collect($colors)->contains(fn ($existing) => Str::lower($existing) === Str::lower($color))) {
                $colors[] = $color;
            }
        }
        if (strlen(implode(', ', $colors)) > 255) {
            throw ValidationException::withMessages(['colors' => 'The selected colors are too long. Choose fewer colors.']);
        }
        if ((float) $data['weight'] <= 0) {
            throw ValidationException::withMessages(['weight' => 'Enter a weight greater than zero.']);
        }

        $data['sizes'] = $sizes ? implode(', ', $sizes) : null;
        $data['colors'] = $colors ? implode(', ', $colors) : null;
        $data['weight'] = trim(preg_replace('/\s+/u', ' ', $data['weight']));
        $data['country_of_origin'] = 'Philippines';

        return $data;
    }

    private function storeUploads(Request $request): array
    {
        $paths = [];
        try {
            if ($request->hasFile('main_image')) {
                $paths['main'] = $request->file('main_image')->store('products', 'public');
                if (! $paths['main']) {
                    throw new \RuntimeException('Unable to store the main photo.');
                }
            }
            foreach ($request->file('gallery_images', []) as $image) {
                $path = $image->store('products', 'public');
                if (! $path) {
                    throw new \RuntimeException('Unable to store an additional photo.');
                }
                $paths['gallery'][] = $path;
            }
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete(array_filter([$paths['main'] ?? null, ...($paths['gallery'] ?? [])]));
            throw $exception;
        }

        return $paths;
    }

    private function uploadedPaths(array $paths): array
    {
        return array_values(array_filter([$paths['main'] ?? null, ...($paths['gallery'] ?? [])]));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request, true);
        $paths = $this->storeUploads($request);
        try {
            DB::transaction(function () use ($request, $data, $paths) {
                $stock = (int) $data['stock'];
                unset($data['stock'], $data['main_image'], $data['gallery_images'], $data['remove_images'], $data['expected_revision']);
                $product = Product::create($data + ['product_code' => 'PRD-'.Str::ulid(), 'seller_id' => $request->user()->id, 'stock' => 0, 'status' => 'for_review']);
                if ($stock) {
                    app(InventoryService::class)->changeLocked($product, $stock, 'initial', $request->user()->id, reason: 'Opening stock');
                }
                foreach ($this->uploadedPaths($paths) as $position => $path) {
                    $product->images()->create(['path' => $path, 'sort_order' => $position]);
                }
            });
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($this->uploadedPaths($paths));
            throw $exception;
        }

        return response()->json(['message' => 'Product submitted for admin review.']);
    }

    public function update(Request $request, int $product)
    {
        Product::where('seller_id', $request->user()->id)->findOrFail($product);
        $data = $this->validated($request, false);
        $paths = $this->storeUploads($request);
        try {
            $removedPaths = DB::transaction(function () use ($request, $product, $data, $paths) {
                $record = Product::where('seller_id', $request->user()->id)->lockForUpdate()->findOrFail($product);
                abort_unless($record->revision === $data['expected_revision'], 409, 'This product changed. Reopen it before editing.');

                $images = $record->images()->orderBy('id')->get();
                $removeIds = array_map('intval', $data['remove_images'] ?? []);
                if (array_diff($removeIds, $images->pluck('id')->all())) {
                    throw ValidationException::withMessages(['remove_images' => 'One of the selected photos is no longer available.']);
                }
                if (isset($paths['main']) && $images->isNotEmpty()) {
                    $removeIds[] = $images->first()->id;
                }
                $removeIds = array_unique($removeIds);
                $remaining = $images->reject(fn ($image) => in_array($image->id, $removeIds, true))->values();
                $total = $remaining->count() + count($this->uploadedPaths($paths));
                if ($total < 1 || $total > self::MAX_PHOTOS) {
                    throw ValidationException::withMessages(['gallery_images' => 'Keep a main photo and no more than five additional photos.']);
                }

                unset($data['main_image'], $data['gallery_images'], $data['remove_images'], $data['expected_revision'], $data['stock']);
                $record->fill($data);
                if ($record->isDirty() || $removeIds || $this->uploadedPaths($paths)) {
                    $record->fill(['status' => 'for_review', 'rejection_reason' => null, 'rejection_details' => null])->save();
                    if ($removeIds) {
                        $record->images()->whereIn('id', $removeIds)->delete();
                    }
                    $position = 0;
                    if (isset($paths['main'])) {
                        $record->images()->create(['path' => $paths['main'], 'sort_order' => $position++]);
                    }
                    foreach ($remaining as $image) {
                        $image->update(['sort_order' => $position++]);
                    }
                    foreach ($paths['gallery'] ?? [] as $path) {
                        $record->images()->create(['path' => $path, 'sort_order' => $position++]);
                    }
                }
                return $images->whereIn('id', $removeIds)->pluck('path')->all();
            }, 3);
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($this->uploadedPaths($paths));
            throw $exception;
        }
        Storage::disk('public')->delete($removedPaths);

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
