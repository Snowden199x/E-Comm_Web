<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Communication\Notification;
use App\Models\Ecommerce\InventoryMovement;
use App\Models\Ecommerce\Product;
use App\Models\User;
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

    private const MAX_UPLOAD_BYTES = 10 * 1024 * 1024;

    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => 'nullable|string|max:100', 'category' => 'nullable|integer',
            'stock_status' => ['nullable', Rule::in(['in_stock', 'low_stock', 'out_of_stock', 'alerts'])],
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
            'alerts' => $query->where('status', 'approved')->where('stock', '<=', Product::LOW_STOCK_THRESHOLD),
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
        $product = Product::where('seller_id', $request->user()->id)
            ->with(['category', 'images', 'attributeValues', 'specifications', 'variationTypes.options', 'variants'])
            ->findOrFail($product);
        if ($mode === 'edit') {
            return view('seller.products.form', ['product' => $product, 'categories' => $this->categories($request)]);
        }
        $movements = $product->movements()->with('user')->latest('id')->paginate(8)->withQueryString();

        return view('seller.products.detail', compact('product', 'movements', 'mode'));
    }

    private function validated(Request $request, bool $creating): array
    {
        $draft = $request->input('action', 'submit') === 'draft';
        $allowed = $this->categories($request);
        $data = $request->validate([
            'action' => ['required', Rule::in(['draft', 'submit'])],
            'name' => 'required|string|max:255', 'description' => [$draft ? 'nullable' : 'required', 'string', 'max:5000'],
            'price' => [$draft || $request->boolean('has_variations') ? 'nullable' : 'required', 'numeric', 'min:0.01', 'max:99999999.99', 'decimal:0,2'],
            'sku' => 'prohibited',
            'compare_at_price' => 'nullable|numeric|min:0.01|max:99999999.99|decimal:0,2',
            'category_id' => [$draft ? 'nullable' : 'required', 'integer', Rule::in($allowed->pluck('id')->all())],
            'brand' => 'nullable|string|max:255', 'no_brand' => 'nullable|boolean',
            'condition' => [$draft ? 'nullable' : 'required', Rule::in(['new', 'used'])],
            'weight_kg' => [$draft ? 'nullable' : 'required', 'numeric', 'gt:0', 'max:99999', 'decimal:0,3'],
            'package_length' => [$draft ? 'nullable' : 'required', 'numeric', 'gt:0', 'max:99999'],
            'package_width' => [$draft ? 'nullable' : 'required', 'numeric', 'gt:0', 'max:99999'],
            'package_height' => [$draft ? 'nullable' : 'required', 'numeric', 'gt:0', 'max:99999'],
            'fragile' => 'nullable|boolean', 'has_variations' => 'nullable|boolean',
            'stock' => $creating ? ($draft || $request->boolean('has_variations') ? 'nullable|integer|min:0|max:1000000' : 'required|integer|min:0|max:1000000') : 'prohibited',
            'main_image' => [$creating && ! $draft ? 'required' : 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'gallery_images' => 'nullable|array|max:5',
            'gallery_images.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'main_image_id' => $creating ? 'prohibited' : 'nullable|integer|min:1',
            'remove_images' => $creating ? 'prohibited' : 'nullable|array|max:6',
            'remove_images.*' => 'integer|distinct|min:1',
            'video' => 'nullable|file|mimes:mp4,webm,mov,qt|max:10240',
            'remove_video' => 'nullable|boolean',
            'attributes' => 'nullable|array|max:30',
            'specs' => 'nullable|array|max:30',
            'specs.*.name' => 'required_with:specs.*.value|string|max:60',
            'specs.*.value' => 'required_with:specs.*.name|string|max:255',
            'variation_types' => 'nullable|array|max:3',
            'variation_types.*.name' => 'required|string|max:30',
            'variation_types.*.options' => 'required|array|min:1|max:20',
            'variation_types.*.options.*' => 'required|string|max:30',
            'variants' => 'nullable|array|max:100',
            'variants.*.label' => 'required|string|max:255',
            'variants.*.price' => 'required|numeric|min:0.01|max:99999999.99|decimal:0,2',
            'variants.*.stock' => 'required|integer|min:0|max:1000000',
            'variants.*.sku' => 'prohibited',
            'variants.*.options' => 'required|array',
            'variants.*.image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'expected_revision' => $creating ? 'nullable' : 'required|string',
        ]);

        $uploads = array_filter([$request->file('main_image'), ...($request->file('gallery_images') ?? [])]);
        if (array_sum(array_map(fn ($file) => $file->getSize(), $uploads)) > self::MAX_UPLOAD_BYTES) {
            throw ValidationException::withMessages(['gallery_images' => 'All new photos together must be 10 MB or less.']);
        }

        if (! $draft && ! empty($data['category_id'])) {
            $category = $allowed->firstWhere('id', (int) $data['category_id']);
            if ($category && $allowed->contains('parent_id', $category->id)) {
                throw ValidationException::withMessages(['category_id' => 'Choose a subcategory for this category.']);
            }
        }
        $category = ! empty($data['category_id']) ? $allowed->firstWhere('id', (int) $data['category_id']) : null;
        $schemaKey = $category ? Str::slug(str_replace('&', 'and', $category->name)) : null;
        if ($category && ! isset(config('product-attributes', [])[$schemaKey])) {
            $parent = $allowed->firstWhere('id', $category->parent_id);
            $schemaKey = $parent ? Str::slug(str_replace('&', 'and', $parent->name)) : null;
        }
        $fields = collect(config('product-attributes.'.$schemaKey.'.fields', []))->keyBy('key');
        $attributes = [];
        foreach ($data['attributes'] ?? [] as $key => $value) {
            $field = $fields->get($key);
            if (! $field) {
                throw ValidationException::withMessages(['attributes.'.$key => 'This detail does not belong to the selected category.']);
            }
            $values = is_array($value) ? array_values($value) : [$value];
            if ($field['type'] !== 'chips' && count($values) !== 1) {
                throw ValidationException::withMessages(['attributes.'.$key => 'Choose one value for this detail.']);
            }
            foreach ($values as $entry) {
                if (! is_string($entry) || mb_strlen($entry) > ($field['type'] === 'textarea' ? 1000 : 255)
                    || (isset($field['options']) && ! in_array($entry, $field['options'], true))
                    || ($field['type'] === 'yesno' && ! in_array($entry, ['Yes', 'No'], true))
                    || ($field['type'] === 'date' && $entry !== '' && (! strtotime($entry) || strtotime($entry) <= time()))) {
                    throw ValidationException::withMessages(['attributes.'.$key => 'Enter a valid value for '.$field['label'].'.']);
                }
            }
            $attributes[$key] = $field['type'] === 'chips' ? $values : $values[0];
        }
        if (! $draft) {
            foreach ($fields as $key => $field) {
                if (($field['required'] ?? false) && empty($attributes[$key])) {
                    throw ValidationException::withMessages(['attributes.'.$key => $field['label'].' is required.']);
                }
            }
        }
        $data['attributes'] = $attributes;
        $this->validateVariants($data, $draft);
        $listingPrice = ! empty($data['has_variations']) && ! empty($data['variants'])
            ? min(array_column($data['variants'], 'price')) : ($data['price'] ?? null);
        if (! $draft && isset($data['compare_at_price']) && $listingPrice !== null
            && (float) $data['compare_at_price'] <= (float) $listingPrice) {
            throw ValidationException::withMessages(['compare_at_price' => 'The original price must be higher than the selling price.']);
        }

        return $data;
    }

    private function validateVariants(array &$data, bool $draft): void
    {
        if (empty($data['has_variations'])) {
            $data['variation_types'] = [];
            $data['variants'] = [];
            return;
        }
        if ($draft && (empty($data['variation_types']) || empty($data['variants']))) {
            $data['has_variations'] = false;
            return;
        }
        $types = $data['variation_types'] ?? [];
        $variants = $data['variants'] ?? [];
        if (! $types || ! $variants || count($types) > 3 || count($variants) > 100) {
            throw ValidationException::withMessages(['variants' => 'Add 1–3 variation types and up to 100 complete variants.']);
        }
        $names = array_column($types, 'name');
        if (count(array_unique(array_map('mb_strtolower', $names))) !== count($names)) {
            throw ValidationException::withMessages(['variation_types' => 'Variation names must be unique.']);
        }
        $total = 1;
        foreach ($types as $type) {
            $options = $type['options'];
            if (count(array_unique(array_map('mb_strtolower', $options))) !== count($options)) {
                throw ValidationException::withMessages(['variation_types' => 'Options in one variation must be unique.']);
            }
            $total *= count($options);
        }
        if ($total !== count($variants)) {
            throw ValidationException::withMessages(['variants' => 'Every variation combination must have one price and stock entry.']);
        }
        $seen = [];
        foreach ($variants as $variant) {
            $options = $variant['options'];
            if (count($options) !== count($types) || array_keys($options) !== $names) {
                throw ValidationException::withMessages(['variants' => 'A variant has invalid options.']);
            }
            foreach ($types as $type) {
                if (! in_array($options[$type['name']], $type['options'], true)) {
                    throw ValidationException::withMessages(['variants' => 'A variant contains an unavailable option.']);
                }
            }
            $key = json_encode($options);
            if (isset($seen[$key])) {
                throw ValidationException::withMessages(['variants' => 'Duplicate variation combination.']);
            }
            $seen[$key] = true;
        }
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
            if ($request->hasFile('video')) {
                $paths['video'] = $request->file('video')->store('products/videos', 'public');
                if (! $paths['video']) {
                    throw new \RuntimeException('Unable to store the product video.');
                }
            }
            foreach ($request->file('variants', []) as $index => $variant) {
                if (isset($variant['image'])) {
                    $paths['variants'][$index] = $variant['image']->store('products/variants', 'public');
                    if (! $paths['variants'][$index]) {
                        throw new \RuntimeException('Unable to store a variant photo.');
                    }
                }
            }
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($this->uploadedPaths($paths));
            throw $exception;
        }

        return $paths;
    }

    private function uploadedPaths(array $paths): array
    {
        return array_values(array_filter([$paths['main'] ?? null, ...($paths['gallery'] ?? []),
            $paths['video'] ?? null, ...($paths['variants'] ?? [])]));
    }

    private function productPhotoPaths(array $paths): array
    {
        return array_values(array_filter([$paths['main'] ?? null, ...($paths['gallery'] ?? [])]));
    }

    private function nextProductCode(): string
    {
        $year = (int) now()->year;
        DB::table('product_sequences')->insertOrIgnore(['year' => $year, 'last_number' => 0]);
        $sequence = DB::table('product_sequences')->where('year', $year)->lockForUpdate()->first();
        $number = $sequence->last_number + 1;
        DB::table('product_sequences')->where('year', $year)->update(['last_number' => $number]);

        return 'PRD-'.$year.'-'.str_pad((string) $number, 4, '0', STR_PAD_LEFT);
    }

    private function saveDetails(Product $product, array $data): void
    {
        $product->attributeValues()->delete();
        foreach ($data['attributes'] ?? [] as $key => $value) {
            if ($value !== '' && $value !== []) {
                $product->attributeValues()->create(['key' => $key, 'value' => is_array($value) ? $value : [$value]]);
            }
        }
        $product->specifications()->delete();
        foreach ($data['specs'] ?? [] as $position => $spec) {
            if (! empty($spec['name']) && ! empty($spec['value'])) {
                $product->specifications()->create($spec + ['sort_order' => $position]);
            }
        }
    }

    private function saveNewVariants(Product $product, array $data, array $paths, int $actorId): void
    {
        foreach ($data['variation_types'] ?? [] as $position => $type) {
            $record = $product->variationTypes()->create(['name' => $type['name'], 'sort_order' => $position]);
            foreach ($type['options'] as $optionPosition => $option) {
                $record->options()->create(['value' => $option, 'sort_order' => $optionPosition]);
            }
        }
        foreach ($data['variants'] ?? [] as $position => $variant) {
            $sku = $product->product_code.'-'.str_pad((string) ($position + 1), 2, '0', STR_PAD_LEFT);
            $record = $product->variants()->create([
                'label' => $variant['label'], 'sku' => $sku, 'price' => $variant['price'],
                'stock' => 0, 'options' => $variant['options'], 'sort_order' => $position,
                'image_path' => $paths['variants'][$position] ?? null,
            ]);
            if ($variant['stock'] > 0) {
                app(InventoryService::class)->changeLocked($product, (int) $variant['stock'], 'initial', $actorId,
                    reason: 'Opening variant stock', variant: $record);
            }
        }
    }

    public function store(Request $request)
    {
        $data = $this->validated($request, true);
        $paths = $this->storeUploads($request);
        try {
            DB::transaction(function () use ($request, $data, $paths) {
                $stock = (int) ($data['stock'] ?? 0);
                $details = $data;
                $code = $this->nextProductCode();
                $hasVariations = ! empty($data['has_variations']);
                $product = Product::create([
                    'product_code' => $code, 'seller_id' => $request->user()->id,
                    'name' => $data['name'], 'description' => $data['description'] ?? null,
                    'category_id' => $data['category_id'] ?? null, 'brand' => $data['brand'] ?? null,
                    'condition' => $data['condition'] ?? null,
                    'price' => $hasVariations ? min(array_column($data['variants'], 'price')) : ($data['price'] ?? 0),
                    'compare_at_price' => $data['compare_at_price'] ?? null,
                    'stock' => 0, 'status' => $data['action'] === 'draft' ? 'draft' : 'for_review',
                    'weight_kg' => $data['weight_kg'] ?? null,
                    'weight' => isset($data['weight_kg']) ? $data['weight_kg'].' kg' : null,
                    'material' => is_string($data['attributes']['material'] ?? null) ? $data['attributes']['material'] : null,
                    'package_length' => $data['package_length'] ?? null, 'package_width' => $data['package_width'] ?? null,
                    'package_height' => $data['package_height'] ?? null,
                    'is_fragile' => (bool) ($data['fragile'] ?? false), 'has_variations' => $hasVariations,
                    'country_of_origin' => 'Philippines', 'video_path' => $paths['video'] ?? null,
                ]);
                if ($hasVariations) {
                    $this->saveNewVariants($product, $data, $paths, $request->user()->id);
                } elseif ($stock) {
                    app(InventoryService::class)->changeLocked($product, $stock, 'initial', $request->user()->id, reason: 'Opening stock');
                }
                $this->saveDetails($product, $details);
                foreach ($this->productPhotoPaths($paths) as $position => $path) {
                    $product->images()->create(['path' => $path, 'sort_order' => $position]);
                }
                if ($data['action'] === 'submit') {
                    $this->notifyProductSubmittedForReview($product, $request->user(), false);
                }
            });
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($this->uploadedPaths($paths));
            throw $exception;
        }

        return response()->json(['message' => $data['action'] === 'draft' ? 'Draft saved.' : 'Product submitted for admin review.']);
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
                $wasRejectedOrWarned = in_array($record->status, ['rejected', 'warned'], true);

                $images = $record->images()->orderBy('sort_order')->orderBy('id')->get();
                $removeIds = array_map('intval', $data['remove_images'] ?? []);
                if (array_diff($removeIds, $images->pluck('id')->all())) {
                    throw ValidationException::withMessages(['remove_images' => 'One of the selected photos is no longer available.']);
                }
                $removeIds = array_unique($removeIds);
                $remaining = $images->reject(fn ($image) => in_array($image->id, $removeIds, true))->values();
                $total = $remaining->count() + (isset($paths['main']) ? 1 : 0) + count($paths['gallery'] ?? []);
                if (($data['action'] === 'submit' && $total < 1) || $total > self::MAX_PHOTOS) {
                    throw ValidationException::withMessages(['gallery_images' => 'Keep a main photo and no more than five additional photos.']);
                }
                $mainId = (int) ($data['main_image_id'] ?? 0);
                if ($mainId && ! $remaining->contains('id', $mainId)) {
                    throw ValidationException::withMessages(['main_image_id' => 'Choose a photo that remains on this product.']);
                }
                $oldVariants = $record->variants()->orderBy('sort_order')->get();
                if ($oldVariants->isNotEmpty()) {
                    abort_unless(empty($paths['variants']), 422, 'Existing variant images cannot be replaced here.');
                    abort_unless(! empty($data['has_variations']) && count($data['variants'] ?? []) === $oldVariants->count(),
                        422, 'Existing variant combinations cannot be removed.');
                    foreach ($oldVariants as $position => $variant) {
                        $submitted = $data['variants'][$position];
                        abort_unless($submitted['label'] === $variant->label
                            && $submitted['options'] === $variant->options
                            && (int) $submitted['stock'] === $variant->stock,
                            422, 'Keep existing variants and use Restock to change their quantities.');
                        $variant->update(['price' => $submitted['price']]);
                    }
                } elseif (! empty($data['has_variations'])) {
                    abort_unless($record->stock === 0 && ! $record->orderItems()->exists(), 422,
                        'A simple product can be converted to variants only before it has stock or orders.');
                    $record->has_variations = true;
                    $this->saveNewVariants($record, $data, $paths, $request->user()->id);
                }
                $record->fill([
                    'name' => $data['name'], 'description' => $data['description'] ?? null,
                    'category_id' => $data['category_id'] ?? null, 'brand' => $data['brand'] ?? null,
                    'condition' => $data['condition'] ?? null,
                    'price' => ! empty($data['has_variations']) ? min(array_column($data['variants'], 'price')) : ($data['price'] ?? 0),
                    'compare_at_price' => $data['compare_at_price'] ?? null,
                    'weight_kg' => $data['weight_kg'] ?? null,
                    'weight' => isset($data['weight_kg']) ? $data['weight_kg'].' kg' : null,
                    'material' => is_string($data['attributes']['material'] ?? null) ? $data['attributes']['material'] : null,
                    'package_length' => $data['package_length'] ?? null, 'package_width' => $data['package_width'] ?? null,
                    'package_height' => $data['package_height'] ?? null,
                    'is_fragile' => (bool) ($data['fragile'] ?? false),
                    'has_variations' => (bool) ($data['has_variations'] ?? false),
                    'status' => $data['action'] === 'draft' ? 'draft' : 'for_review',
                    'rejection_reason' => null, 'rejection_details' => null,
                ]);
                $oldVideo = $record->video_path;
                if (isset($paths['video']) || ! empty($data['remove_video'])) {
                    $record->video_path = $paths['video'] ?? null;
                }
                $record->save();
                $this->saveDetails($record, $data);
                if ($removeIds) {
                    $record->images()->whereIn('id', $removeIds)->delete();
                }
                $position = 0;
                if (isset($paths['main'])) {
                    $record->images()->create(['path' => $paths['main'], 'sort_order' => $position++]);
                }
                if ($mainId) {
                    $main = $remaining->firstWhere('id', $mainId);
                    $main->update(['sort_order' => $position++]);
                }
                foreach ($remaining as $image) {
                    if ($image->id !== $mainId) {
                        $image->update(['sort_order' => $position++]);
                    }
                }
                foreach ($paths['gallery'] ?? [] as $path) {
                    $record->images()->create(['path' => $path, 'sort_order' => $position++]);
                }
                if ($data['action'] === 'submit') {
                    $this->notifyProductSubmittedForReview($record, $request->user(), $wasRejectedOrWarned);
                }
                return array_values(array_filter([
                    ...$images->whereIn('id', $removeIds)->pluck('path')->all(),
                    $oldVideo !== $record->video_path ? $oldVideo : null,
                ]));
            }, 3);
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($this->uploadedPaths($paths));
            throw $exception;
        }
        Storage::disk('public')->delete($removedPaths);

        return response()->json(['message' => $data['action'] === 'draft' ? 'Draft saved.' : 'Product saved for admin review.']);
    }

    private function notifyProductSubmittedForReview(Product $product, User $seller, bool $resubmitted): void
    {
        Notification::create([
            'user_id' => null,
            'type' => 'product_submitted_for_review',
            'title' => $resubmitted ? 'Product resubmitted for review' : 'Product submitted for review',
            'message' => $seller->name.' submitted "'.$product->name.'" ('.$product->product_code.') for review.',
            'link' => route('admin.seller-compliance.products-for-review', ['search' => $product->name]),
        ]);
    }

    public function restock(Request $request, int $product)
    {
        $data = $request->validate(['quantity' => 'required|integer|min:1|max:1000000', 'reason' => 'required|string|max:500',
            'request_key' => 'required|uuid', 'variant_id' => 'nullable|integer']);
        DB::transaction(function () use ($request, $product, $data) {
            $record = Product::where('seller_id', $request->user()->id)->lockForUpdate()->findOrFail($product);
            $previous = InventoryMovement::where('request_key', $data['request_key'])->first();
            if ($previous) {
                abort_unless($previous->product_id === $record->id && $previous->user_id === $request->user()->id
                    && (int) $previous->quantity === (int) $data['quantity'] && $previous->reason === $data['reason']
                    && (int) $previous->product_variant_id === (int) ($data['variant_id'] ?? 0), 409, 'This stock update reference was already used.');

                return;
            }
            $variant = isset($data['variant_id']) ? $record->variants()->findOrFail($data['variant_id']) : null;
            app(InventoryService::class)->changeLocked($record, (int) $data['quantity'], 'restock', $request->user()->id,
                reason: $data['reason'], requestKey: $data['request_key'], variant: $variant);
        }, 3);

        return response()->json(['message' => 'Stock updated and recorded in inventory history.']);
    }
}
