<x-seller.layout title="Products & Inventory">
    @vite(['resources/css/seller/operations.css','resources/js/seller/operations.js'])
    <section class="ops-page">
        <header class="ops-page-head"><div><h1>Products &amp; Inventory</h1><p>Manage your products, stock levels and categories.</p></div><a class="ops-button ops-button--primary" href="{{ route('seller.products.create') }}" data-panel data-panel-title="Add Product">＋ Add Product</a></header>
        <div class="ops-stats">
            @foreach(['all'=>['Total Products','products-inventory--icon.png'],'in_stock'=>['In Stock','to-pack-icon.png'],'low_stock'=>['Low Stock','ready-for-pickup-icon.png'],'out_of_stock'=>['Out of Stock','products-inventory--icon.png']] as $key=>[$label,$icon])
                <a class="ops-stat" href="{{ route('seller.products.index', $key==='all'?[]:['stock_status'=>$key]) }}"><span class="ops-stat-icon"><img src="{{ asset('assets/icons/seller/'.$icon) }}" alt=""></span><span>{{ $label }}<strong>{{ number_format($counts[$key]) }}</strong></span></a>
            @endforeach
        </div>
        <form method="GET" action="{{ route('seller.products.index') }}" class="ops-filters">
            <label class="ops-search"><span class="ops-sr-only">Search products</span><input type="search" name="search" value="{{ $filters['search']??'' }}" placeholder="Search products or SKU…" maxlength="100"></label>
            <label><span class="ops-sr-only">Category</span><select name="category"><option value="">All categories</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected(($filters['category']??'')==$category->id)>{{ $category->name }}</option>@endforeach</select></label>
            <label><span class="ops-sr-only">Stock status</span><select name="stock_status"><option value="">All stock statuses</option><option value="alerts" @selected(($filters['stock_status']??'')==='alerts')>Inventory alerts</option>@foreach(\App\Models\Ecommerce\Product::STOCK_LABELS as $key=>$label)<option value="{{ $key }}" @selected(($filters['stock_status']??'')===$key)>{{ $label }}</option>@endforeach</select></label>
            <button class="ops-button" type="submit">Apply</button>@if(request()->query())<a class="ops-text-link" href="{{ route('seller.products.index') }}">Clear</a>@endif
        </form>
        @if($errors->any())<p class="ops-alert" role="alert">{{ $errors->first() }}</p>@endif
        <div class="ops-inventory-layout">
            <section class="ops-card ops-table-card" aria-label="Products">
                <div class="ops-table-scroll"><table class="ops-table"><thead><tr><th>Product</th><th>Category</th><th>Price</th><th>Stock</th><th>Status</th><th><span class="ops-sr-only">Actions</span></th></tr></thead><tbody>
                    @forelse($products as $product)<tr>
                        <td><a href="{{ route('seller.products.show',$product) }}" class="ops-product" data-panel data-panel-title="Product Details">@include('seller.operations.image',['product'=>$product])<span><strong>{{ $product->name }}</strong><small>SKU: {{ $product->product_code }}</small></span></a></td>
                        <td><span class="ops-category">{{ $product->category?->name ?? 'Uncategorized' }}</span></td><td class="ops-nowrap">₱{{ number_format($product->price,2) }}</td><td>{{ number_format($product->stock) }}</td>
                        <td><span class="ops-badge ops-badge--{{ $product->stock_status }}">{{ \App\Models\Ecommerce\Product::STOCK_LABELS[$product->stock_status] }}</span><small class="ops-review">{{ ucfirst(str_replace('_',' ',$product->status)) }}</small></td>
                        <td><details class="ops-menu"><summary aria-label="Actions for {{ $product->name }}">⋮</summary><div><a href="{{ route('seller.products.show',$product) }}" data-panel data-panel-title="Product Details">View / stock history</a><a href="{{ route('seller.products.show',[$product,'mode'=>'edit']) }}" data-panel data-panel-title="Edit Product">Edit product</a><a href="{{ route('seller.products.show',[$product,'mode'=>'restock']) }}" data-panel data-panel-title="Restock Product">Restock</a></div></details></td>
                    </tr>@empty<tr><td colspan="6" class="ops-empty">No products match your filters. Add a product or clear the filters.</td></tr>@endforelse
                </tbody></table></div>
                @include('seller.operations.pagination',['records'=>$products])
            </section>
            <aside class="ops-side-panels">
                <section class="ops-card"><header class="ops-card-head"><h2>Categories</h2><button class="ops-text-link" type="button" data-dialog-open="opsCategories">View all</button></header>
                    @forelse($categories->take(3) as $category)<a class="ops-list-row" href="{{ route('seller.products.index',['category'=>$category->id]) }}"><span class="ops-category-symbol" aria-hidden="true">▦</span><span>{{ $category->name }}</span><small>{{ $category->products_count }}</small><span aria-hidden="true">›</span></a>@empty<p class="ops-muted">Your product categories will appear here.</p>@endforelse
                </section>
                <section class="ops-card"><header class="ops-card-head"><h2>Low Stock Alert</h2><a class="ops-text-link" href="{{ route('seller.products.index',['stock_status'=>'low_stock']) }}">View all</a></header>
                    @forelse($lowStock as $product)<div class="ops-list-row">@include('seller.operations.image',['product'=>$product])<span><strong>{{ $product->name }}</strong><small>{{ $product->stock }} pcs</small></span><a class="ops-text-link" href="{{ route('seller.products.show',[$product,'mode'=>'restock']) }}" data-panel data-panel-title="Restock Product">Restock</a></div>@empty<p class="ops-muted">No low-stock products.</p>@endforelse
                    <p class="ops-caption">Low stock: 1–{{ \App\Models\Ecommerce\Product::LOW_STOCK_THRESHOLD }} units. Counts include all your listings.</p>
                </section>
            </aside>
        </div>
        <dialog id="opsCategories" class="ops-modal" aria-labelledby="opsCategoryTitle"><header class="ops-card-head"><h2 id="opsCategoryTitle">Product Categories</h2><button type="button" data-close-dialog aria-label="Close categories">×</button></header>@forelse($categories as $category)<a class="ops-list-row" href="{{ route('seller.products.index',['category'=>$category->id]) }}"><span>{{ $category->name }}</span><small>{{ $category->products_count }} products</small><span>›</span></a>@empty<p class="ops-muted">No products assigned to a category yet.</p>@endforelse</dialog>
    </section>
    @include('seller.operations.drawer')
@include('shared.live-revision', ['endpoint' => route('seller.live', 'products'), 'mode' => 'reload'])
</x-seller.layout>
