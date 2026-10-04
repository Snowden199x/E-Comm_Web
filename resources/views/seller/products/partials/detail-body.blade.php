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
        <div class="pi-bigprice">₱{{ number_format($product->price, 2) }}</div>

        @if($product->rejection_reason)<p class="pi-callout"><strong>Admin feedback:</strong> {{ $product->rejection_reason }} {{ $product->rejection_details }}</p>@endif

        <div class="pi-tabs" role="tablist">
            <button type="button" role="tab" data-tab="details" class="is-active">Details</button>
            <button type="button" role="tab" data-tab="restock">Restock</button>
            <button type="button" role="tab" data-tab="history">Stock history</button>
        </div>

        <div class="pi-tabpanel" data-tabpanel="details">
            <dl class="pi-info">
                <div><dt>{{ $variants->isNotEmpty() ? 'Total stock (all variations)' : 'Available stock' }}</dt><dd>{{ number_format($totalStock) }} units</dd></div>
                <div><dt>Brand</dt><dd>{{ $product->brand ?: '—' }}</dd></div>
                <div><dt>Material</dt><dd>{{ $product->material ?: '—' }}</dd></div>
                <div><dt>Weight</dt><dd>{{ $product->weight ?: '—' }}</dd></div>
                <div><dt>Colors</dt><dd>{{ $product->colors ?: '—' }}</dd></div>
                <div><dt>Sizes</dt><dd>{{ $product->sizes ?: '—' }}</dd></div>
                <div><dt>Country of origin</dt><dd>{{ $product->country_of_origin ?: '—' }}</dd></div>
                <div><dt>Date added</dt><dd>{{ $product->created_at?->format('M j, Y') ?? '—' }}</dd></div>
            </dl>
            @if($product->description)<p class="pi-desc">{{ $product->description }}</p>@endif

            @if($variants->isNotEmpty())
                <h4 class="pi-subhead">Variations &amp; stock</h4>
                <div style="overflow-x:auto">
                    <table class="pi-hist pi-variants">
                        <thead><tr><th>Variant</th><th>SKU</th><th>Price</th><th>Stock</th><th>Status</th></tr></thead>
                        <tbody>
                        @foreach($variants as $variant)
                            <tr>
                                <td><strong>{{ $variant->label }}</strong></td>
                                <td>{{ $product->status === 'draft' ? 'After submission' : ($variant->sku ?: '—') }}</td>
                                <td class="pi-nowrap">₱{{ number_format($variant->price, 2) }}</td>
                                <td>{{ number_format($variant->stock) }}</td>
                                <td><span class="pi-pill pi-pill--{{ $variantStatus($variant->stock) }}">{{ $stockLabels[$variantStatus($variant->stock)] }}</span></td>
                            </tr>
                        @endforeach
                        </tbody>
                        <tfoot><tr><td colspan="3"><strong>Total</strong></td><td colspan="2"><strong>{{ number_format($totalStock) }} units</strong></td></tr></tfoot>
                    </table>
                </div>
            @endif
        </div>

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
