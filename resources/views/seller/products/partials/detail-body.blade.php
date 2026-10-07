@php
    $stockLabels = \App\Models\Ecommerce\Product::STOCK_LABELS;
    $listing = ['for_review' => 'Pending review', 'approved' => 'Published', 'warned' => 'Needs changes', 'rejected' => 'Rejected', 'draft' => 'Draft'];
    $cat = $product->category; $main = $cat?->parent ?? $cat;
    $colors = $main?->colors ?? ['border' => '#6B7280', 'bg' => '#F3F4F6'];
    // Variants appear once the backend adds a `variants` relation (see the Seller Products & Inventory spec).
    $variants = method_exists($product, 'variants') ? $product->variants : collect();
    $totalStock = $variants->isNotEmpty() ? $variants->sum('stock') : $product->stock;
    $threshold = \App\Models\Ecommerce\Product::LOW_STOCK_THRESHOLD;
    $variantStatus = fn ($qty) => $qty <= 0 ? 'out_of_stock' : ($qty <= $threshold ? 'low_stock' : 'in_stock');
    $showVariations = $product->has_variations || $variants->isNotEmpty();
    $variationTypes = $product->relationLoaded('variationTypes') ? $product->variationTypes : collect();
    $attributeValues = $product->relationLoaded('attributeValues') ? $product->attributeValues : collect();
    $specifications = $product->relationLoaded('specifications') ? $product->specifications : collect();
    $formatValue = fn ($value) => is_array($value) ? implode(', ', array_map('strval', $value)) : (is_bool($value) ? ($value ? 'Yes' : 'No') : (string) $value);
    $priceRange = $variants->isNotEmpty() ? [$variants->min('price'), $variants->max('price')] : null;
@endphp
<div data-initial-tab="{{ $mode === 'restock' ? 'restock' : 'details' }}" hidden></div>
<div class="pi-detail">
    <div>
        <div class="pi-gallery-main" data-gallery-main>
            @if($product->images->isNotEmpty())<img src="{{ asset('storage/'.$product->images->first()->path) }}" alt="{{ $product->name }}">
            @else<svg width="46" height="46" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" aria-hidden="true"><path d="m12 3 9 5v8l-9 5-9-5V8zM3 8l9 5 9-5M12 13v8"/></svg>@endif
        </div>
        @if($product->images->count() > 1)
            <div class="pi-gallery-thumbs">
                @foreach($product->images as $image)
                    <button type="button" class="@if($loop->first) is-active @endif" data-gallery-src="{{ asset('storage/'.$image->path) }}" data-gallery-alt="Photo {{ $loop->iteration }} of {{ $product->name }}" aria-label="Show photo {{ $loop->iteration }}"><img src="{{ asset('storage/'.$image->path) }}" alt=""></button>
                @endforeach
            </div>
        @endif
    </div>

    <div>
        <h3>{{ $product->name }}</h3>
        <p class="pi-sku">{{ $product->status === 'draft' ? 'SKU assigned after submission' : 'SKU: '.$product->product_code }}</p>
        <div class="pi-badges">
            <span class="pi-pill" style="color: {{ $colors['border'] }}; border-color: {{ $colors['border'] }}; background: {{ $colors['bg'] }}">{{ $main?->name ?? 'Uncategorized' }}</span>
            @if($cat?->parent)<span class="pi-pill" style="color:#512258;border-color:#cfc4d2;background:#fff">{{ $cat->name }}</span>@endif
            <span class="pi-pill pi-pill--{{ $product->stock_status }}">{{ $stockLabels[$product->stock_status] }}</span>
            <span class="pi-pill" style="color:#512258;border-color:#cfc4d2;background:#f4eef5">{{ $listing[$product->status] ?? ucfirst(str_replace('_', ' ', $product->status)) }}</span>
        </div>
        <div class="pi-bigprice">
            @if($priceRange && (float) $priceRange[0] !== (float) $priceRange[1])₱{{ number_format($priceRange[0], 2) }} – ₱{{ number_format($priceRange[1], 2) }}
            @else₱{{ number_format($priceRange[0] ?? $product->price, 2) }}@endif
            @if($product->compare_at_price && (float) $product->compare_at_price > (float) $product->price)<s class="pi-oldprice">₱{{ number_format($product->compare_at_price, 2) }}</s>@endif
        </div>

        @if($product->rejection_reason)<p class="pi-callout"><strong>Admin feedback:</strong> {{ $product->rejection_reason }} {{ $product->rejection_details }}</p>@endif

        <div class="pi-tabs" role="tablist">
            <button type="button" role="tab" data-tab="details" class="is-active">Details</button>
            @if($showVariations)<button type="button" role="tab" data-tab="variations">Variations <span class="pi-tabcount">{{ $variants->count() }}</span></button>@endif
            <button type="button" role="tab" data-tab="restock">Restock</button>
            <button type="button" role="tab" data-tab="history">Stock history</button>
        </div>

        <div class="pi-tabpanel" data-tabpanel="details">
            <dl class="pi-info">
                <div><dt>{{ $variants->isNotEmpty() ? 'Total stock (all variations)' : 'Available stock' }}</dt><dd>{{ number_format($totalStock) }} units</dd></div>
                <div><dt>Brand</dt><dd>{{ $product->brand ?: '—' }}</dd></div>
                {{-- Legacy single-value columns: only shown when they actually hold a value. --}}
                @foreach(['material' => 'Material', 'weight' => 'Weight', 'country_of_origin' => 'Country of origin'] as $field => $label)
                    @if(filled($product->{$field}))<div><dt>{{ $label }}</dt><dd>{{ $product->{$field} }}</dd></div>@endif
                @endforeach
                {{-- Colors / Sizes now come from the product's variation options. --}}
                @forelse($variationTypes as $type)
                    <div><dt>{{ $type->name }}</dt><dd>{{ $type->options->pluck('value')->implode(', ') ?: '—' }}</dd></div>
                @empty
                    @foreach(['colors' => 'Colors', 'sizes' => 'Sizes'] as $field => $label)
                        @if(filled($product->{$field}))<div><dt>{{ $label }}</dt><dd>{{ $product->{$field} }}</dd></div>@endif
                    @endforeach
                @endforelse
                <div><dt>Date added</dt><dd>{{ $product->created_at?->format('M j, Y') ?? '—' }}</dd></div>
            </dl>
            @if($product->description)<p class="pi-desc">{{ $product->description }}</p>@endif

            @if($showVariations)
                <p class="pi-vnote"><strong>{{ $variants->count() }}</strong> variation{{ $variants->count() === 1 ? '' : 's' }} · <button type="button" class="pi-link" data-tab="variations">View variations &amp; stock</button></p>
            @endif

            @if($attributeValues->isNotEmpty())
                <h4 class="pi-subhead">Category details</h4>
                <dl class="pi-info">
                    @foreach($attributeValues as $attribute)
                        <div><dt>{{ \Illuminate\Support\Str::headline($attribute->key) }}</dt><dd>{{ $formatValue($attribute->value) ?: '—' }}</dd></div>
                    @endforeach
                </dl>
            @endif

            @if($specifications->isNotEmpty())
                <h4 class="pi-subhead">Additional specifications</h4>
                <dl class="pi-info">
                    @foreach($specifications as $spec)<div><dt>{{ $spec->name }}</dt><dd>{{ $spec->value }}</dd></div>@endforeach
                </dl>
            @endif

            <h4 class="pi-subhead">Shipping information</h4>
            <dl class="pi-info">
                <div><dt>Package weight</dt><dd>{{ $product->weight_kg ? rtrim(rtrim(number_format((float) $product->weight_kg, 3), '0'), '.').' kg' : '—' }}</dd></div>
                <div><dt>Package size (L × W × H)</dt><dd>{{ $product->package_length ? rtrim(rtrim((string) $product->package_length, '0'), '.').' × '.rtrim(rtrim((string) $product->package_width, '0'), '.').' × '.rtrim(rtrim((string) $product->package_height, '0'), '.').' cm' : '—' }}</dd></div>
                <div><dt>Fragile</dt><dd>{{ $product->is_fragile ? 'Yes — handle with care' : 'No' }}</dd></div>
                <div><dt>Condition</dt><dd>{{ $product->condition ? ucfirst($product->condition) : '—' }}</dd></div>
            </dl>

            @if($product->video_path)
                <h4 class="pi-subhead">Product video</h4>
                <video class="pi-video" controls preload="metadata" src="{{ asset('storage/'.$product->video_path) }}"></video>
            @endif
        </div>

        @if($showVariations)
        <div class="pi-tabpanel" data-tabpanel="variations" hidden>
            @if($variants->isEmpty())
                <div class="pi-vempty">
                    <svg width="38" height="38" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h10"/></svg>
                    <h4>No variations saved yet</h4>
                    <p>This product is marked as having variations, but none are stored. Open Edit product to add them.</p>
                </div>
            @else
                <div class="pi-vsummary">
                    <div><span>Variation types</span><strong>{{ $variationTypes->count() ?: count($variants->first()->options ?? []) }}</strong></div>
                    <div><span>Combinations</span><strong>{{ $variants->count() }}</strong></div>
                    <div><span>Total stock</span><strong>{{ number_format($totalStock) }}</strong></div>
                    <div><span>Out of stock</span><strong>{{ $variants->where('stock', '<=', 0)->count() }}</strong></div>
                </div>

                @if($variationTypes->isNotEmpty())
                    <div class="pi-vtypes">
                        @foreach($variationTypes as $type)
                            <div class="pi-vtype"><span class="pi-vtype__name">{{ $type->name }}</span>
                                <span class="pi-chips">@foreach($type->options as $option)<span class="pi-chip">{{ $option->value }}</span>@endforeach</span>
                            </div>
                        @endforeach
                    </div>
                @endif

                <ul class="pi-vlist">
                    @foreach($variants as $variant)
                        @php $status = $variantStatus($variant->stock); @endphp
                        <li class="pi-vrow" style="--i: {{ $loop->index }}">
                            @if($variant->image_path)<img class="pi-vthumb" src="{{ asset('storage/'.$variant->image_path) }}" alt="" loading="lazy">
                            @else<span class="pi-vthumb pi-vthumb--empty" aria-hidden="true"></span>@endif
                            <div class="pi-vrow__main">
                                <strong class="pi-vrow__title">{{ $variant->label }}</strong>
                                @if(is_array($variant->options) && $variant->options)
                                    <span class="pi-vrow__opts">@foreach($variant->options as $name => $value){{ $name }}: {{ $value }}@unless($loop->last) · @endunless @endforeach</span>
                                @endif
                                <span class="pi-vrow__sku">SKU: {{ $product->status === 'draft' ? 'assigned after submission' : ($variant->sku ?: '—') }}</span>
                            </div>
                            <div class="pi-vrow__cell"><span class="pi-vrow__k">Price</span><strong>₱{{ number_format($variant->price, 2) }}</strong></div>
                            <div class="pi-vrow__cell"><span class="pi-vrow__k">Stock</span><strong>{{ number_format($variant->stock) }}</strong></div>
                            <div class="pi-vrow__end">
                                <span class="pi-pill pi-pill--{{ $status }}">{{ $stockLabels[$status] }}</span>
                                <button type="button" class="pi-link" data-tab="restock" data-restock-variant="{{ $variant->id }}">Restock</button>
                            </div>
                        </li>
                    @endforeach
                </ul>
                <p class="pi-vtotal"><span>Total across all variations</span><strong>{{ number_format($totalStock) }} units</strong></p>
            @endif
        </div>
        @endif

        <div class="pi-tabpanel" data-tabpanel="restock">
            <form action="{{ route('seller.products.restock', $product) }}" method="POST" data-restock data-draft-key="seller-{{ auth()->id() }}-restock-{{ $product->id }}" data-draft-ajax>
                @csrf
                <input type="hidden" name="request_key" value="{{ \Illuminate\Support\Str::uuid() }}">
                <div class="pi-grid" style="grid-template-columns:1fr;gap:16px">
                    @if($variants->isNotEmpty())
                        <label class="pi-field"><span class="pi-label">Variation<em>*</em></span>
                            <select class="pi-input" name="variant_id" required>
                                <option value="">Select variation</option>
                                @foreach($variants as $variant)<option value="{{ $variant->id }}">{{ $variant->label }} — {{ $variant->stock }} in stock</option>@endforeach
                            </select></label>
                    @endif
                    <label class="pi-field"><span class="pi-label">Units to add<em>*</em></span><input class="pi-input" type="number" name="quantity" min="1" max="1000000" step="1" required></label>
                    <label class="pi-field"><span class="pi-label">Reason / stock reference<em>*</em></span><textarea class="pi-input" name="reason" maxlength="500" rows="3" required placeholder="For example, supplier delivery reference"></textarea></label>
                </div>
                <p class="pi-error" data-form-error role="alert"></p>
                <button type="submit" class="pi-btn pi-btn--primary" style="margin-top:16px">Add Stock</button>
            </form>
        </div>

        <div class="pi-tabpanel" data-tabpanel="history">
            <div style="overflow-x:auto">
                <table class="pi-hist">
                    <thead><tr><th>When / by</th><th>Movement</th><th>Before → after</th></tr></thead>
                    <tbody>
                    @forelse($movements as $movement)
                        <tr>
                            <td>{{ $movement->created_at->format('M j, Y g:i A') }}<small>{{ $movement->user?->name ?? 'System' }}</small></td>
                            <td>{{ ucfirst($movement->type) }}: {{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity }}<small>{{ $movement->reason }}</small>@if($movement->order_id)<small>Order #{{ $movement->order_id }}</small>@endif</td>
                            <td>{{ $movement->stock_before }} → {{ $movement->stock_after }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="pi-muted">No recorded stock movements yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if($movements->previousPageUrl() || $movements->nextPageUrl())
                <p style="margin-top:12px;display:flex;gap:16px">
                    @if($movements->previousPageUrl())<a class="pi-link" href="{{ $movements->previousPageUrl() }}" data-modal-nav data-tab="history">← Newer</a>@endif
                    @if($movements->nextPageUrl())<a class="pi-link" href="{{ $movements->nextPageUrl() }}" data-modal-nav data-tab="history">Older →</a>@endif
                </p>
            @endif
        </div>
    </div>
</div>

<div data-modal-footer>
    <a class="pi-btn" href="{{ route('seller.products.show', [$product, 'mode' => 'edit']) }}">Edit product</a>
    @if(request()->ajax())<button type="button" class="pi-btn pi-btn--primary" data-close-modal>Close</button>@endif
</div>