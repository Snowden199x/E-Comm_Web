@php
    $stockLabels = \App\Models\Ecommerce\Product::STOCK_LABELS;
    $listing = ['for_review' => 'Pending review', 'warned' => 'Needs changes', 'rejected' => 'Rejected', 'draft' => 'Draft'];
    $statCards = [
        'all' => ['Total Products', 'total-products-icon.svg'],
        'in_stock' => ['In Stock', 'in-stock-icon.svg'],
        'low_stock' => ['Low Stock', 'low-stock-icon.svg'],
        'out_of_stock' => ['Out of Stock', 'out-of-stock-icon.svg'],
    ];
    $activeStat = $filters['stock_status'] ?? '';
@endphp
<x-seller.layout title="Products & Inventory">
    @vite(['resources/css/seller/products.css', 'resources/js/seller/products.js'])
    <section class="pi-page">
        <header class="pi-head">
            <div><h1>Products &amp; Inventory</h1><p>Manage your products, stock levels and categories.</p></div>
            <a class="pi-btn pi-btn--primary" href="{{ route('seller.products.create') }}">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg> Add Product
            </a>
        </header>

        <div class="pi-stats">
            @foreach($statCards as $key => [$label, $icon])
                <a class="pi-stat @if(($key === 'all' && $activeStat === '') || $key === $activeStat) is-active @endif" href="{{ route('seller.products.index', $key === 'all' ? [] : ['stock_status' => $key]) }}">
                    <img src="{{ asset('assets/icons/seller/'.$icon) }}" alt="">
                    <div><span>{{ $label }}</span><strong>{{ number_format($counts[$key]) }}</strong></div>
                </a>
            @endforeach
        </div>

        <form method="GET" action="{{ route('seller.products.index') }}" class="pi-filters" id="piFilters">
            <label class="pi-search">
                <span class="pi-sr">Search products</span>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                <input class="pi-input" style="border-radius:999px" type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search products or SKU…" maxlength="100">
            </label>
            <label><span class="pi-sr">Category</span>
                <select class="pi-input pi-pill-select" name="category"><option value="">All categories</option>
                    @foreach($categories as $category)<option value="{{ $category->id }}" @selected(($filters['category'] ?? '') == $category->id)>{{ $category->name }}</option>@endforeach
                </select></label>
            <label><span class="pi-sr">Stock status</span>
                <select class="pi-input pi-pill-select" name="stock_status"><option value="">All status</option><option value="alerts" @selected($activeStat === 'alerts')>Inventory alerts</option>
                    @foreach($stockLabels as $key => $label)<option value="{{ $key }}" @selected($activeStat === $key)>{{ $label }}</option>@endforeach
                </select></label>
            <noscript><button class="pi-btn" type="submit">Apply</button></noscript>
            @if(request()->query())<a class="pi-link" href="{{ route('seller.products.index') }}">Clear filters</a>@endif
        </form>

        @if($errors->any())<p class="pi-callout" role="alert">{{ $errors->first() }}</p>@endif

        <div class="pi-layout">
            <section class="pi-card pi-table-card" aria-label="Products">
                <div class="pi-table-scroll">
                    <table class="pi-table">
                        <thead><tr><th>Product</th><th>Category</th><th>Price</th><th>Stock</th><th>Status</th><th><span class="pi-sr">Actions</span></th></tr></thead>
                        <tbody>
                        @forelse($products as $product)
                            @php
                                $cat = $product->category; $main = $cat?->parent ?? $cat;
                                $colors = $main?->colors ?? ['border' => '#6B7280', 'bg' => '#F3F4F6'];
                            @endphp
                            <tr data-row-url="{{ route('seller.products.show', $product) }}" style="--i: {{ $loop->index }}">
                                <td>
                                    <button type="button" class="pi-prod" data-product-modal="{{ route('seller.products.show', $product) }}" aria-label="View details of {{ $product->name }}">
                                        @include('seller.products.partials.thumb', ['product' => $product])
                                        <span><strong>{{ $product->name }}</strong><small>{{ $product->status === 'draft' ? 'SKU assigned after submission' : 'SKU: '.$product->product_code }}</small></span>
                                    </button>
                                </td>
                                <td><span class="pi-pill" style="color: {{ $colors['border'] }}; border-color: {{ $colors['border'] }}; background: {{ $colors['bg'] }}">{{ $main?->name ?? 'Uncategorized' }}</span>@if($cat?->parent)<small class="pi-subcat">{{ $cat->name }}</small>@endif</td>
                                <td class="pi-nowrap pi-price">₱{{ number_format($product->price, 2) }}</td>
                                <td>{{ number_format($product->stock) }}</td>
                                <td>
                                    <span class="pi-pill pi-pill--{{ $product->stock_status }}">{{ $stockLabels[$product->stock_status] }}</span>
                                    @if(isset($listing[$product->status]))<span class="pi-review pi-review--{{ $product->status }}">{{ $listing[$product->status] }}</span>@endif
                                </td>
                                <td>
                                    <button type="button" class="pi-kebab" data-kebab="pi-menu-{{ $product->id }}" aria-label="Actions for {{ $product->name }}" aria-haspopup="true">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="12" cy="5" r="1.8"/><circle cx="12" cy="12" r="1.8"/><circle cx="12" cy="19" r="1.8"/></svg>
                                    </button>
                                    <div class="pi-menu" id="pi-menu-{{ $product->id }}" role="menu">
                                        <button type="button" role="menuitem" data-product-modal="{{ route('seller.products.show', $product) }}">View details</button>
                                        <a role="menuitem" href="{{ route('seller.products.show', [$product, 'mode' => 'edit']) }}">Edit product</a>
                                        <button type="button" role="menuitem" data-product-modal="{{ route('seller.products.show', [$product, 'mode' => 'restock']) }}" data-tab="restock" data-modal-title="Restock Product">Restock</button>
                                        <span class="pi-menu-sep" role="separator"></span>
                                        {{-- Backend pending: DELETE /seller/products/{product} (see handoff notes). --}}
                                        <button type="button" role="menuitem" class="pi-menu-danger" data-delete-product data-delete-url="{{ url('/seller/products/'.$product->id) }}" data-delete-name="{{ $product->name }}">
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18M8 6V4a1 1 0 011-1h6a1 1 0 011 1v2M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6M10 11v6M14 11v6"/></svg>
                                            Delete product
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="pi-empty">
                                <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3" aria-hidden="true"><path d="m12 3 9 5v8l-9 5-9-5V8zM3 8l9 5 9-5M12 13v8"/></svg>
                                <h3>{{ request()->query() ? 'No products match your filters' : 'No products yet' }}</h3>
                                <p>{{ request()->query() ? 'Try a different search or clear the filters.' : 'Add your first product to start selling on Vendo.' }}</p>
                            </td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                @include('seller.products.partials.pagination', ['records' => $products])
            </section>

            <aside class="pi-side">
                <section class="pi-card">
                    <header class="pi-card-head"><h2>Low Stock Alert</h2><a class="pi-link" href="{{ route('seller.products.index', ['stock_status' => 'low_stock']) }}">View all</a></header>
                    @forelse($lowStock as $product)
                        <div class="pi-alert-row" style="animation-delay: {{ $loop->index * 60 }}ms">
                            @include('seller.products.partials.thumb', ['product' => $product])
                            <div class="pi-grow"><strong>{{ $product->name }}</strong><small>{{ $product->stock }} pcs</small></div>
                            <button type="button" class="pi-link" data-product-modal="{{ route('seller.products.show', [$product, 'mode' => 'restock']) }}" data-tab="restock" data-modal-title="Restock Product">Restock</button>
                        </div>
                    @empty
                        <p class="pi-muted">No low-stock products. Nice work!</p>
                    @endforelse
                    <p class="pi-note">Low stock means 1–{{ \App\Models\Ecommerce\Product::LOW_STOCK_THRESHOLD }} units left.</p>
                </section>
            </aside>
        </div>

        <dialog id="piModal" class="pi-modal" aria-labelledby="piModalTitle">
            <div class="pi-modal-bar"><h2 id="piModalTitle">Product Details</h2><button type="button" class="pi-x" data-close-modal aria-label="Close details">×</button></div>
            <div class="pi-modal-body" id="piModalBody"></div>
            <div class="pi-modal-foot" id="piModalFoot" hidden></div>
        </dialog>
        <dialog id="piDeleteDialog" class="pi-confirm" aria-labelledby="piDeleteTitle" aria-describedby="piDeleteCopy">
            <div class="pi-confirm__icon" aria-hidden="true">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M8 6V4a1 1 0 011-1h6a1 1 0 011 1v2M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6M10 11v6M14 11v6"/></svg>
            </div>
            <h2 id="piDeleteTitle">Delete this product?</h2>
            <p id="piDeleteCopy"><strong data-delete-name></strong> will be removed from your products and from the Vendo marketplace. Vendo admin will be notified. Past orders keep their record.</p>
            <p class="pi-error" data-delete-error role="alert"></p>
            <div class="pi-confirm__actions">
                <button type="button" class="pi-btn" data-delete-cancel>Keep product</button>
                <button type="button" class="pi-btn pi-btn--danger-solid" data-delete-confirm>Delete product</button>
            </div>
        </dialog>
        <div class="pi-toast" id="piToast" role="status" aria-live="polite"></div>
    </section>
    @include('shared.live-revision', ['endpoint' => route('seller.live', 'products'), 'mode' => 'reload'])
</x-seller.layout>