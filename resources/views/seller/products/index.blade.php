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

    // Category and Subcategory are separate filters. They travel as ONE request value (`category`): the subcategory when
    // one is chosen, otherwise the category. Existing controller semantics keep working (see backend-needs-2026-10-07.md).
    $topCats = $categories->map(fn ($c) => $c->parent ?? $c)->unique('id')->sortBy('name')->values();
    $subCats = $categories->filter(fn ($c) => $c->parent_id)->sortBy('name')->values();
    $selId = $filters['category'] ?? null;
    $selSub = $selId ? $subCats->firstWhere('id', (int) $selId) : null;
    $selTop = $selSub ? $topCats->firstWhere('id', $selSub->parent_id) : ($selId ? $topCats->firstWhere('id', (int) $selId) : null);
    $activeCount = ($selTop ? 1 : 0) + ($selSub ? 1 : 0) + ($activeStat !== '' ? 1 : 0);
    $hasFilters = $activeCount > 0 || ! empty($filters['search']);
    $without = fn (array $drop, array $set = []) => route('seller.products.index', array_filter(array_merge(request()->except(array_merge(['page'], $drop)), $set), fn ($v) => $v !== null && $v !== ''));
    $stockChipLabel = $activeStat === 'alerts' ? 'Inventory alerts' : ($stockLabels[$activeStat] ?? null);
    $voucherPage = \Illuminate\Support\Facades\Route::has('seller.vouchers.index');
@endphp
<x-seller.layout title="Products & Inventory">
    @vite(['resources/css/seller/products.css', 'resources/css/seller/products-filters.css', 'resources/js/seller/products.js', 'resources/js/seller/products-filters.js'])
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

        <div class="pi-filters">
            {{-- Search stays in the form; the filter panel's fields join it through the form="piFilters" attribute. --}}
            <form method="GET" action="{{ route('seller.products.index') }}" id="piFilters" class="pi-filters__form">
                <label class="pi-search">
                    <span class="pi-sr">Search products</span>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                    <input class="pi-input" style="border-radius:999px" type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search products or SKU…" maxlength="100">
                </label>
                <input type="hidden" name="category" id="piCategoryValue" value="{{ $selId }}">
                <noscript><button class="pi-btn" type="submit">Search</button></noscript>
            </form>

            <details class="pi-fpop" id="piFPop">
                <summary class="pi-fbtn @if($activeCount) is-on @endif" aria-label="Filters{{ $activeCount ? ', '.$activeCount.' applied' : '' }}">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 6h16M7 12h10M10 18h4"/></svg>
                    <span>Filters</span>
                    @if($activeCount)<b class="pi-fbadge">{{ $activeCount }}</b>@endif
                </summary>
                <div class="pi-fpanel" role="group" aria-label="Product filters">
                    <header class="pi-fpanel__head"><strong>Filter products</strong><span>Narrow the list by category and stock.</span></header>
                    <label class="pi-ffield"><span>Category</span>
                        <select class="pi-input" id="piCat">
                            <option value="">All categories</option>
                            @foreach($topCats as $cat)<option value="{{ $cat->id }}" @selected($selTop?->id === $cat->id)>{{ $cat->name }}</option>@endforeach
                        </select>
                    </label>
                    <label class="pi-ffield"><span>Subcategory</span>
                        <select class="pi-input" id="piSub">
                            <option value="">All subcategories</option>
                            @foreach($subCats as $sub)<option value="{{ $sub->id }}" data-parent="{{ $sub->parent_id }}" @selected($selSub?->id === $sub->id)>{{ $sub->name }}</option>@endforeach
                        </select>
                        <small id="piSubHint">Pick a category first to see its subcategories.</small>
                    </label>
                    <label class="pi-ffield"><span>Stock status</span>
                        <select class="pi-input" name="stock_status" form="piFilters">
                            <option value="">All stock levels</option><option value="alerts" @selected($activeStat === 'alerts')>Inventory alerts</option>
                            @foreach($stockLabels as $key => $label)<option value="{{ $key }}" @selected($activeStat === $key)>{{ $label }}</option>@endforeach
                        </select>
                    </label>
                    <footer class="pi-fpanel__foot">
                        <a class="pi-link" href="{{ route('seller.products.index', array_filter(['search' => $filters['search'] ?? null])) }}">Reset</a>
                        <button type="submit" form="piFilters" class="pi-btn pi-btn--primary">Apply filters</button>
                    </footer>
                </div>
            </details>

            @if($hasFilters)<a class="pi-link" href="{{ route('seller.products.index') }}">Clear all</a>@endif
        </div>

        @if($hasFilters)
            <ul class="pi-chips" aria-label="Active filters">
                @if(! empty($filters['search']))<li><span>Search: {{ $filters['search'] }}</span><a href="{{ $without(['search']) }}" aria-label="Remove search filter">×</a></li>@endif
                @if($selTop)<li><span>Category: {{ $selTop->name }}</span><a href="{{ $without(['category']) }}" aria-label="Remove category filter">×</a></li>@endif
                @if($selSub)<li><span>Subcategory: {{ $selSub->name }}</span><a href="{{ $without(['category'], ['category' => $selTop?->id]) }}" aria-label="Remove subcategory filter">×</a></li>@endif
                @if($stockChipLabel)<li><span>Stock: {{ $stockChipLabel }}</span><a href="{{ $without(['stock_status']) }}" aria-label="Remove stock filter">×</a></li>@endif
            </ul>
        @endif

        @if($errors->any())<p class="pi-callout" role="alert">{{ $errors->first() }}</p>@endif

        <div class="pi-layout">
            <section class="pi-card pi-table-card" aria-label="Products">
                <div class="pi-table-scroll">
                    <table class="pi-table">
                        <thead><tr><th scope="col">Product</th><th scope="col">Category</th><th scope="col">Subcategory</th><th scope="col">Price</th><th scope="col">Stock</th><th scope="col">Stock status</th><th scope="col">Listing</th><th scope="col">Vouchers</th><th scope="col">Last updated</th><th scope="col"><span class="pi-sr">Actions</span></th></tr></thead>
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
                                <td><span class="pi-pill" style="color: {{ $colors['border'] }}; border-color: {{ $colors['border'] }}; background: {{ $colors['bg'] }}">{{ $main?->name ?? 'Uncategorized' }}</span></td>
                                <td>@if($cat?->parent)<span class="pi-subpill">{{ $cat->name }}</span>@else<span class="pi-dash">—</span>@endif</td>
                                <td class="pi-nowrap pi-price">₱{{ number_format($product->price, 2) }}</td>
                                <td>{{ number_format($product->stock) }}</td>
                                <td><span class="pi-pill pi-pill--{{ $product->stock_status }}">{{ $stockLabels[$product->stock_status] }}</span></td>
                                <td>@if(isset($listing[$product->status]))<span class="pi-review pi-review--{{ $product->status }}">{{ $listing[$product->status] }}</span>@else<span class="pi-live"><i aria-hidden="true"></i>Live</span>@endif</td>
                                <td>
                                    @php $voucherCount = (int) ($product->vouchers_count ?? 0); @endphp
                                    @if($voucherCount > 0)
                                        <a class="pi-vbadge" href="{{ $voucherPage ? route('seller.vouchers.index', ['product' => $product->id]) : '#' }}" aria-label="{{ $voucherCount }} {{ \Illuminate\Support\Str::plural('voucher', $voucherCount) }} apply to {{ $product->name }}">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 9a2 2 0 002-2V6h14v1a2 2 0 002 2v6a2 2 0 00-2 2v1H5v-1a2 2 0 00-2-2z"/><path d="M12 7v10" stroke-dasharray="2 2"/></svg>{{ $voucherCount }}
                                        </a>
                                    @else<span class="pi-dash">—</span>@endif
                                </td>
                                <td class="pi-nowrap pi-updated">{{ $product->updated_at->format('M j, Y') }}<small>{{ $product->updated_at->diffForHumans() }}</small></td>
                                <td>
                                    <button type="button" class="pi-kebab" data-kebab="pi-menu-{{ $product->id }}" aria-label="Actions for {{ $product->name }}" aria-haspopup="true">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="12" cy="5" r="1.8"/><circle cx="12" cy="12" r="1.8"/><circle cx="12" cy="19" r="1.8"/></svg>
                                    </button>
                                    <div class="pi-menu" id="pi-menu-{{ $product->id }}" role="menu">
                                        <button type="button" role="menuitem" data-product-modal="{{ route('seller.products.show', $product) }}">View details</button>
                                        <a role="menuitem" href="{{ route('seller.products.show', [$product, 'mode' => 'edit']) }}">Edit product</a>
                                        <button type="button" role="menuitem" data-product-modal="{{ route('seller.products.show', [$product, 'mode' => 'restock']) }}" data-tab="restock" data-modal-title="Restock Product">Restock</button>
                                        @if($voucherPage)<a role="menuitem" href="{{ route('seller.vouchers.index', ['create' => 1, 'product' => $product->id]) }}">Add to a voucher</a>@endif
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
                            <tr><td colspan="10" class="pi-empty">
                                <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3" aria-hidden="true"><path d="m12 3 9 5v8l-9 5-9-5V8zM3 8l9 5 9-5M12 13v8"/></svg>
                                <h3>{{ $hasFilters ? 'No products match your filters' : 'No products yet' }}</h3>
                                <p>{{ $hasFilters ? 'Try a different search or clear the filters.' : 'Add your first product to start selling on Vendo.' }}</p>
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