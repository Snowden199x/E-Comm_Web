@php
    use Illuminate\Support\Str;

    $backendReady = true;

    $editing = $product->exists;
    $keyOf = fn ($name) => Str::slug(str_replace('&', 'and', (string) $name));

    // Main categories + their subcategories, limited to what the seller is approved to sell (Seller Compliance).
    $parents = $categories->whereNull('parent_id')->values();
    $subcategories = $categories->whereNotNull('parent_id')->groupBy('parent_id')
        ->map(fn ($group) => $group->sortBy('name')->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->values())->all();
    $selectedParent = $product->category?->parent_id ?? $product->category_id;
    $selectedSub = $product->category?->parent_id ? $product->category_id : null;
    $categoryKeys = $parents->mapWithKeys(fn ($c) => [$c->id => $keyOf($c->name)])->all();
    // A subcategory can have its own detail fields (e.g. Makeup & Cosmetics under Health and Beauty).
    $subcategoryKeys = $categories->whereNotNull('parent_id')->mapWithKeys(fn ($c) => [$c->id => $keyOf($c->name)])->all();

    $kg = '';
    if ($editing && $product->weight_kg) {
        $kg = $product->weight_kg;
    } elseif ($editing && preg_match('/^([\d.]+)\s*(g|kg)$/i', (string) $product->weight, $m)) {
        $kg = strtolower($m[2]) === 'g' ? round($m[1] / 1000, 3) : $m[1];
    }
    $legacySpecs = [];
    if ($editing && $product->colors) { $legacySpecs[] = ['name' => 'Colors', 'value' => $product->colors]; }
    if ($editing && $product->sizes) { $legacySpecs[] = ['name' => 'Sizes', 'value' => $product->sizes]; }

    $config = [
        'schema' => config('product-attributes', []),
        'categoryKeys' => $categoryKeys,
        'subcategories' => (object) $subcategories,
        'subcategoryKeys' => (object) $subcategoryKeys,
        'selectedSub' => $selectedSub,
        'existingImages' => $editing ? $product->images->map(fn ($i) => ['id' => $i->id, 'url' => asset('storage/'.$i->path)])->values() : [],
        'attributes' => $editing ? ($product->attributeValues->mapWithKeys(fn ($item) => [$item->key => count($item->value) === 1 ? $item->value[0] : $item->value])->all() ?: ($product->material ? ['material' => $product->material] : [])) : (object) [],
        'specs' => $editing && $product->specifications->isNotEmpty() ? $product->specifications->map(fn ($spec) => ['name' => $spec->name, 'value' => $spec->value])->all() : $legacySpecs,
        'hasVariations' => $editing && $product->has_variations,
        'showGeneratedSku' => $editing && $product->status !== 'draft',
        'productCode' => $editing && $product->status !== 'draft' ? $product->product_code : null,
        'variationTypes' => $editing ? $product->variationTypes->map(fn ($type) => ['name' => $type->name, 'options' => $type->options->pluck('value')->all()])->all() : [],
        'variants' => $editing ? $product->variants->mapWithKeys(fn ($variant) => [$variant->label => ['price' => $variant->price, 'stock' => $variant->stock, 'sku' => $variant->sku, 'url' => $variant->image_path ? asset('storage/'.$variant->image_path) : '']])->all() : [],
        'videoUrl' => $editing && $product->video_path ? asset('storage/'.$product->video_path) : null,
    ];
    $steps = ['basic' => 'Basic Information', 'category' => 'Category Details', 'variations' => 'Variations, Price & Stock', 'shipping' => 'Shipping', 'review' => 'Review & Submit'];
@endphp
<x-seller.layout :title="$editing ? 'Edit Product' : 'Add Product'">
    @vite(['resources/css/seller/products.css', 'resources/js/seller/products.js'])
    <section class="pi-page">
        <header class="pi-head">
            <div>
                <h1>{{ $editing ? 'Edit Product' : 'Add Product' }}</h1>
                <p>{{ $editing ? 'Changes to listing details and photos are sent to admin for review.' : 'New products are reviewed by Vendo admin before buyers can see them.' }}</p>
            </div>
            <a class="pi-btn" href="{{ route('seller.products.index') }}">← Back to Products</a>
        </header>

        @if($editing && $product->has_variations)
            <p class="pi-warn" role="status" style="margin:0 0 20px">Existing variant options and stock are locked to protect carts and orders. Edit prices here; use Restock to add stock.</p>
        @endif
        @if($categories->isEmpty())
            <p class="pi-callout" role="alert">No selling categories are assigned to your account. Contact the administrator before adding a product.</p>
        @else
        <form id="piProductForm" method="POST" action="{{ $editing ? route('seller.products.update', $product) : route('seller.products.store') }}" novalidate
              data-mode="{{ $editing ? 'edit' : 'create' }}" data-redirect="{{ route('seller.products.index') }}"
              data-product-draft-key="seller-{{ auth()->id() }}-product-{{ $editing ? $product->id : 'new' }}"
              data-max-images="6" data-max-mb="2" data-total-mb="10" data-video-mb="10" data-video-sec="60">
            @csrf
            @if($editing)
                @method('PATCH')
                <input type="hidden" name="expected_revision" value="{{ $product->revision }}">
            @endif
            <script type="application/json" id="piConfig">@json($config)</script>

            <div class="pi-form-layout">
                <div class="pi-sections">

                    {{-- 1. BASIC INFORMATION --}}
                    <section class="pi-card pi-section" id="sec-basic" data-section="basic">
                        <div class="pi-sec-head"><span class="pi-sec-num">1</span><div><h2>Basic Information</h2><p>Tell buyers what you are selling.</p></div></div>
                        <div class="pi-grid">
                            <div class="pi-field pi-span">
                                <label class="pi-label" for="piName">Product Name<em>*</em></label>
                                <input class="pi-input" id="piName" name="name" data-field="name" maxlength="255" value="{{ $product->name }}" placeholder="e.g. Wireless Bluetooth Headphones">
                                <div class="pi-error" data-error-for="name"></div>
                            </div>
                            <div class="pi-field">
                                <label class="pi-label" for="piCategory">Category<em>*</em></label>
                                <select class="pi-input" id="piCategory" data-field="category_id">
                                    <option value="">Select category</option>
                                    @foreach($parents as $category)
                                        <option value="{{ $category->id }}" data-key="{{ $categoryKeys[$category->id] }}" @selected($selectedParent === $category->id)>{{ $category->name }}</option>
                                    @endforeach
                                </select>
                                <div class="pi-help">Only categories approved for your store are listed.</div>
                                <div class="pi-error" data-error-for="category_id"></div>
                            </div>
                            <div class="pi-field" id="piSubWrap">
                                <label class="pi-label" for="piSubcategory">Subcategory<em id="piSubReq">*</em></label>
                                <select class="pi-input" id="piSubcategory" data-field="subcategory_id" disabled><option value="">Select a category first</option></select>
                                <div class="pi-error" data-error-for="subcategory_id"></div>
                            </div>
                            <input type="hidden" name="category_id" id="piCategoryId" value="{{ $product->category_id }}">
                            <div class="pi-field">
                                <label class="pi-label" for="piBrand">Brand</label>
                                <input class="pi-input" id="piBrand" name="brand" data-field="brand" maxlength="255" value="{{ $product->brand }}" placeholder="e.g. Sony">
                                <label class="pi-check"><input type="checkbox" id="piNoBrand"> No Brand (unbranded product)</label>
                                <div class="pi-error" data-error-for="brand"></div>
                            </div>
                            <div class="pi-field">
                                <span class="pi-label">Condition<em>*</em></span>
                                <div class="pi-seg" role="radiogroup" aria-label="Condition">
                                    <label><input type="radio" name="condition" value="new" @checked(($product->condition ?? 'new') === 'new')><span>New</span></label>
                                    <label><input type="radio" name="condition" value="used" @checked($product->condition === 'used')><span>Used</span></label>
                                </div>
                                <div class="pi-error" data-error-for="condition"></div>
                            </div>
                            <div class="pi-field pi-span">
                                <label class="pi-label" for="piDesc">Product Description<em>*</em> <span class="pi-count" id="piDescCount">0 / 5000</span></label>
                                <textarea class="pi-input" id="piDesc" name="description" data-field="description" maxlength="5000" rows="6" placeholder="Describe the features, what is included, and anything buyers should know.">{{ $product->description }}</textarea>
                                <div class="pi-error" data-error-for="description"></div>
                            </div>

                            <div class="pi-field pi-span">
                                <span class="pi-label">Product Images<em>*</em> <span class="pi-count" id="piImgCount">0 / 6 photos</span></span>
                                <div class="pi-drop" id="piDrop" data-field="images" tabindex="0" role="button" aria-label="Upload product photos">
                                    <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 16V4M7 9l5-5 5 5M4 16v3a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-3"/></svg>
                                    <strong>Click to upload or drag photos here</strong>
                                    <small>JPG, PNG or WebP · up to 2 MB each · 10 MB total · up to 6 photos</small>
                                </div>
                                <input type="file" id="piFiles" accept="image/jpeg,image/png,image/webp" multiple hidden>
                                <div class="pi-tiles" id="piTiles" aria-live="polite"></div>
                                <div class="pi-help">The first photo is your main photo. Hover a photo to set it as main or remove it.</div>
                                <div class="pi-error" data-error-for="images"></div>
                            </div>

                            <div class="pi-field pi-span">
                                <span class="pi-label">Product Video <span class="pi-muted" style="font-weight:400">(optional)</span></span>
                                <button type="button" class="pi-btn pi-btn--sm" onclick="document.getElementById('piVideoFile').click()">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="13" height="14" rx="2"/><path d="m16 10 5-3v10l-5-3"/></svg> Upload video
                                </button>
                                <input type="file" id="piVideoFile" accept="video/mp4,video/webm,video/quicktime" hidden>
                                <div class="pi-video-box" id="piVideoPreview"></div>
                                <div class="pi-upload-progress" id="piUploadProgress" role="status" aria-live="polite" hidden>
                                    <div class="pi-upload-progress__head"><span>Product file upload</span><strong id="piUploadPercent">0%</strong></div>
                                    <progress id="piUploadBar" max="100" value="0" aria-label="Product file upload progress"></progress>
                                    <p id="piUploadStatus">Preparing upload…</p>
                                </div>
                                <div class="pi-help">One short video · MP4, WebM or MOV · up to 10 MB and 60 seconds.</div>
                                <div class="pi-error" data-error-for="video"></div>
                            </div>

                        </div>
                    </section>

                    {{-- 2. CATEGORY DETAILS --}}
                    <section class="pi-card pi-section" id="sec-category" data-section="category">
                        <div class="pi-sec-head"><span class="pi-sec-num">2</span><div><h2>Category Details</h2><p>Details change based on the category you choose. Leave blank anything that does not apply.</p></div></div>
                        <div class="pi-placeholder" id="piCatEmpty">Choose a category in Basic Information to see the details buyers expect for it.</div>
                        <div class="pi-catfields" id="piCatFields" hidden></div>

                        <h3 class="pi-subtitle">Additional Specifications</h3>
                        <p class="pi-help" style="margin:0 0 14px">Add anything not covered above, such as “Battery Capacity: 5000 mAh”.</p>
                        <div class="pi-specs" id="piSpecs"></div>
                        <button type="button" class="pi-btn pi-btn--sm" id="piAddSpec" style="margin-top:14px">+ Add Specification</button>
                    </section>

                    {{-- 3. VARIATIONS, PRICE & STOCK --}}
                    <section class="pi-card pi-section" id="sec-variations" data-section="variations">
                        <div class="pi-sec-head"><span class="pi-sec-num">3</span><div><h2>Variations, Price &amp; Stock</h2><p>Stock is tracked separately for each variation.</p></div></div>
                        <div class="pi-field">
                            <span class="pi-label">Does this product have variations?</span>
                            <div class="pi-seg" role="radiogroup" aria-label="Has variations">
                                <label><input type="radio" name="has_variations" value="0" @checked(! $product->has_variations) @disabled($editing && $product->has_variations)><span>No</span></label>
                                <label><input type="radio" name="has_variations" value="1" @checked($product->has_variations)><span>Yes</span></label>
                            </div>
                        </div>

                        <div id="piSimple" class="pi-grid pi-grid--3" style="margin-top:22px">
                            <div class="pi-field">
                                <label class="pi-label" for="piPrice">Price<em>*</em></label>
                                <div class="pi-unit pi-unit--pre"><span>₱</span><input class="pi-input" id="piPrice" name="price" data-field="price" type="number" min="0.01" max="99999999.99" step="0.01" value="{{ $editing ? $product->price : '' }}" placeholder="0.00"></div>
                                <div class="pi-error" data-error-for="price"></div>
                            </div>
                            <div class="pi-field">
                                <label class="pi-label" for="piStock">Stock Quantity<em>*</em></label>
                                @if($editing)
                                    <input class="pi-input" id="piStock" value="{{ $product->stock }}" readonly aria-readonly="true">
                                    <div class="pi-help">Use Restock to change stock so every change is recorded.</div>
                                @else
                                    <input class="pi-input" id="piStock" name="stock" data-field="stock" type="number" min="0" max="1000000" step="1" placeholder="0">
                                @endif
                                <div class="pi-error" data-error-for="stock"></div>
                            </div>
                            <div class="pi-field">
                                <label class="pi-label" for="piSku">SKU</label>
                                @if($editing && $product->status !== 'draft')
                                    <input class="pi-input" id="piSku" value="{{ $product->product_code }}" readonly aria-readonly="true">
                                @else
                                    <input class="pi-input" id="piSku" value="" placeholder="Assigned after submission" readonly aria-readonly="true">
                                    <div class="pi-help">Vendo assigns this automatically when you submit the listing.</div>
                                @endif
                            </div>
                        </div>

                        <div id="piVarBox" class="pi-hidden">
                            <div class="pi-vars" id="piTypes"></div>
                            <div class="pi-hints" id="piVarHints"></div>
                            <button type="button" class="pi-btn pi-btn--sm" id="piAddType" style="margin-top:14px">+ Add Another Variation</button>
                            <div class="pi-error" data-error-for="type.0.options" style="margin-top:10px"></div>
                            <div class="pi-bulk" id="piBulk" hidden>
                                <strong>Apply to all variants</strong>
                                <div class="pi-unit pi-unit--pre"><span>₱</span><input class="pi-input" id="piBulkPrice" type="number" min="0.01" step="0.01" placeholder="Price" aria-label="Price for all variants"></div>
                                <input class="pi-input" id="piBulkStock" type="number" min="0" step="1" placeholder="Stock" aria-label="Stock for all variants">
                                <button type="button" class="pi-btn pi-btn--sm" id="piBulkApply">Apply</button>
                            </div>
                            <div id="piVariantTable" hidden></div>
                            <p class="pi-warn" id="piVarWarn" hidden>Too many combinations. Remove some options to continue (maximum 100 variants).</p>
                        </div>
                        <div class="pi-field" style="margin-top:22px">
                            <label class="pi-label" for="piComparePrice">Original price <span class="pi-help">(optional)</span></label>
                            <div class="pi-unit pi-unit--pre"><span>₱</span><input class="pi-input" id="piComparePrice" name="compare_at_price" data-field="compare_at_price" type="number" min="0.01" max="99999999.99" step="0.01" value="{{ $product->compare_at_price }}" placeholder="0.00"></div>
                            <div class="pi-help">Shown crossed out on product cards when it is higher than the selling price.</div>
                            <div class="pi-error" data-error-for="compare_at_price"></div>
                        </div>
                    </section>

                    {{-- 4. SHIPPING --}}
                    <section class="pi-card pi-section" id="sec-shipping" data-section="shipping">
                        <div class="pi-sec-head"><span class="pi-sec-num">4</span><div><h2>Shipping Information</h2><p>Packed-parcel details, separate from the product’s own size.</p></div></div>
                        <div class="pi-grid">
                            <div class="pi-field">
                                <label class="pi-label" for="piWeight">Product / Package Weight<em>*</em></label>
                                <div class="pi-unit"><input class="pi-input" id="piWeight" name="weight_kg" data-field="weight_kg" type="number" min="0.001" step="0.001" value="{{ $kg }}" placeholder="0.00"><span>kg</span></div>
                                <div class="pi-error" data-error-for="weight_kg"></div>
                            </div>
                            <div class="pi-field">
                                <span class="pi-label">Fragile Item?</span>
                                <div class="pi-seg" role="radiogroup" aria-label="Fragile item">
                                    <label><input type="radio" name="fragile" value="1" @checked($product->is_fragile)><span>Yes</span></label>
                                    <label><input type="radio" name="fragile" value="0" @checked(! $product->is_fragile)><span>No</span></label>
                                </div>
                            </div>
                            <div class="pi-field pi-span">
                                <span class="pi-label">Package Size<em>*</em></span>
                                <div class="pi-grid pi-grid--3">
                                    @foreach(['package_length' => 'Length', 'package_width' => 'Width', 'package_height' => 'Height'] as $name => $label)
                                        <div class="pi-field">
                                            <div class="pi-unit"><input class="pi-input" name="{{ $name }}" data-field="{{ $name }}" type="number" min="0.1" step="0.1" value="{{ $product->{$name} }}" placeholder="{{ $label }}" aria-label="Package {{ strtolower($label) }}"><span>cm</span></div>
                                            <div class="pi-error" data-error-for="{{ $name }}"></div>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="pi-help">Measure the parcel after packing. This is separate from the product’s own dimensions.</div>
                            </div>
                        </div>
                    </section>

                    {{-- 5. REVIEW & SUBMIT --}}
                    <section class="pi-card pi-section" id="sec-review" data-section="review">
                        <div class="pi-sec-head"><span class="pi-sec-num">5</span><div><h2>Review &amp; Submit</h2><p>Check everything before sending it to admin.</p></div></div>
                        <div class="pi-review-box" id="piReview"></div>
                        <p class="pi-help" style="margin-top:16px">Saving as a draft keeps the product private to you. After you submit for review, it stays hidden from buyers until an admin approves it.</p>
                        <div class="pi-formerr" id="piFormError" role="alert"></div>
                        <div class="pi-actions" id="piActions">
                            <a class="pi-btn pi-btn--ghost" href="{{ route('seller.products.index') }}">Cancel</a>
                            @if($backendReady)<button type="button" class="pi-btn" id="piDraft">Save as Draft</button>@endif
                            <button type="button" class="pi-btn pi-btn--primary" id="piSubmit">{{ $editing ? 'Save & Submit for Review' : 'Submit for Review' }}</button>
                        </div>
                    </section>
                </div>

                <aside class="pi-card pi-progress" aria-label="Listing progress">
                    <h2>Listing progress</h2>
                    <div class="pi-bar"><i id="piBar"></i></div>
                    <ol class="pi-steps">
                        @foreach($steps as $key => $label)
                            <li data-step="{{ $key }}"><a href="#sec-{{ $key }}"><span class="pi-dot">✓</span>{{ $label }}</a></li>
                        @endforeach
                    </ol>
                    <p class="pi-note">Vendo assigns the product and variant SKUs automatically. They appear after you submit the listing for review (format <strong>PRD-{{ now()->year }}-0001</strong>).</p>
                </aside>
            </div>
        </form>
        @endif
        <div class="pi-toast" id="piToast" role="status" aria-live="polite"></div>
    </section>
</x-seller.layout>
