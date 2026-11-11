{{--
    Buyer product list (all products, a category, or search results).
    Uses the shared product card, so Add to Cart and the quick-select sheet work here too.
    Filters (brand, price, stock, sort) stay in the URL so a shared link keeps the same results.
--}}
@php
    $search = $filters['search'] ?? trim((string) request('search'));
    $activeCategory = $filters['categoryId'] ? \App\Models\Category::query()->find($filters['categoryId']) : null;
    $shopSearchUrl = \Illuminate\Support\Facades\Route::has('buyer.sellers.index') ? route('buyer.sellers.index') : null;
    $heading = $search !== '' ? 'Results for “' . $search . '”' : ($activeCategory?->name ?? 'All products');
    $filtered = $search !== '' || $activeCategory || ($filters['brand'] ?? '') !== ''
        || ($filters['minPrice'] ?? null) !== null || ($filters['maxPrice'] ?? null) !== null || ($filters['inStock'] ?? false);
    $fieldClass = 'h-9 w-full rounded-md border border-[#e5dce7] bg-white px-2.5 text-[12px] text-[#3d2a42] focus:border-[#805487] focus:outline-none';
@endphp

<x-buyer.layout :title="$heading . ' | Vendo'">
    <div class="vb-enter mx-auto max-w-[1200px] px-3 pb-14 pt-4 sm:px-4">

        <div class="flex flex-wrap items-end justify-between gap-x-4 gap-y-2">
            <div class="min-w-0">
                <h1 class="truncate text-[20px] font-semibold text-[#2b1730]">{{ $heading }}</h1>
                <p class="mt-0.5 text-[13px] text-[#7a6a7e]">
                    {{ number_format($products->total()) }} {{ \Illuminate\Support\Str::plural('product', $products->total()) }}
                    @if ($search !== '' && $activeCategory) in {{ $activeCategory->name }}@endif
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2 text-[12px]">
                @if ($filtered)
                    <a href="{{ route('buyer.products.index') }}"
                        class="inline-flex h-8 items-center gap-1.5 rounded-md border border-[#e5dce7] bg-white px-3 font-medium text-[#3d2a42] transition-colors duration-200 hover:border-[#c9a9ce] hover:bg-[#faf5fa]">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" /></svg>
                        Clear filters
                    </a>
                @endif
                @if ($shopSearchUrl && $search !== '')
                    <a href="{{ $shopSearchUrl }}?search={{ urlencode($search) }}"
                        class="inline-flex h-8 items-center gap-1.5 rounded-md border border-[#805487] px-3 font-medium text-[#52245b] transition-colors duration-200 hover:bg-[#f5ecf6]">
                        Search shops for “{{ $search }}”
                    </a>
                @endif
            </div>
        </div>

        <form method="GET" action="{{ route('buyer.products.index') }}" role="search"
              class="mt-3 rounded-lg border border-[#eee6ef] bg-white p-3">
            @if ($activeCategory)
                <input type="hidden" name="category_id" value="{{ $activeCategory->id }}">
            @endif
            <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-6">
                <label class="col-span-2 block sm:col-span-1 lg:col-span-2">
                    <span class="mb-1 block text-[11px] font-medium text-[#6f5f73]">Search</span>
                    <input type="search" name="search" value="{{ $search }}" maxlength="80" placeholder="Product or brand"
                        class="{{ $fieldClass }}">
                </label>
                <label class="block">
                    <span class="mb-1 block text-[11px] font-medium text-[#6f5f73]">Brand</span>
                    <select name="brand" class="{{ $fieldClass }}">
                        <option value="">All brands</option>
                        @foreach ($brands as $option)
                            <option value="{{ $option }}" @selected(($filters['brand'] ?? '') === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block">
                    <span class="mb-1 block text-[11px] font-medium text-[#6f5f73]">Lowest price</span>
                    <input type="number" name="min_price" min="0" step="0.01" inputmode="decimal"
                        value="{{ $filters['minPrice'] !== null ? rtrim(rtrim(number_format($filters['minPrice'], 2, '.', ''), '0'), '.') : '' }}"
                        placeholder="₱0" class="{{ $fieldClass }}">
                </label>
                <label class="block">
                    <span class="mb-1 block text-[11px] font-medium text-[#6f5f73]">Highest price</span>
                    <input type="number" name="max_price" min="0" step="0.01" inputmode="decimal"
                        value="{{ $filters['maxPrice'] !== null ? rtrim(rtrim(number_format($filters['maxPrice'], 2, '.', ''), '0'), '.') : '' }}"
                        placeholder="Any" class="{{ $fieldClass }}">
                </label>
                <label class="block">
                    <span class="mb-1 block text-[11px] font-medium text-[#6f5f73]">Sort by</span>
                    <select name="sort" class="{{ $fieldClass }}">
                        <option value="relevance" @selected(($filters['effectiveSort'] ?? '') === 'relevance')>Best match</option>
                        <option value="newest" @selected(($filters['effectiveSort'] ?? '') === 'newest')>Newest</option>
                        <option value="price_low" @selected(($filters['sort'] ?? '') === 'price_low')>Price: low to high</option>
                        <option value="price_high" @selected(($filters['sort'] ?? '') === 'price_high')>Price: high to low</option>
                    </select>
                </label>
                <label class="col-span-2 flex items-center gap-2 self-end sm:col-span-1">
                    <input type="checkbox" name="in_stock" value="1" @checked($filters['inStock'] ?? false)
                        class="h-4 w-4 rounded border-[#c9a9ce] text-[#52245b]">
                    <span class="text-[12px] text-[#5b4a60]">In stock only</span>
                </label>
                <div class="col-span-2 flex items-end gap-2 sm:col-span-1 lg:col-span-6">
                    <button type="submit"
                        class="inline-flex h-9 items-center rounded-md bg-[#402143] px-4 text-[12px] font-medium text-white transition-colors duration-200 hover:bg-[#52245b]">Apply filters</button>
                    @if ($filtered)
                        <a href="{{ route('buyer.products.index', $activeCategory ? ['category_id' => $activeCategory->id] : []) }}"
                            class="inline-flex h-9 items-center rounded-md border border-[#e5dce7] px-3 text-[12px] font-medium text-[#3d2a42] transition-colors duration-200 hover:bg-[#faf5fa]">Reset</a>
                    @endif
                </div>
            </div>
        </form>

        @if ($products->isEmpty())
            <div class="mt-4 rounded-lg border border-[#eee6ef] bg-white">
                @if ($search !== '' || $filtered)
                    <x-buyer.empty-state icon="search" title="No products match these filters"
                        text="Check the spelling, raise the price range, or remove the brand or stock filter.">
                        <a href="{{ route('buyer.products.index') }}" class="inline-flex h-10 items-center rounded-md bg-[#402143] px-5 text-[13px] font-medium text-white transition-colors duration-200 hover:bg-[#52245b]">Browse all products</a>
                        @if ($shopSearchUrl && $search !== '')
                            <a href="{{ $shopSearchUrl }}?search={{ urlencode($search) }}" class="inline-flex h-10 items-center rounded-md border border-[#805487] px-5 text-[13px] font-medium text-[#52245b] transition-colors duration-200 hover:bg-[#f5ecf6]">Search shops instead</a>
                        @endif
                    </x-buyer.empty-state>
                @else
                    <x-buyer.empty-state icon="box" title="No products here yet"
                        :text="$activeCategory ? 'Nothing is listed in ' . $activeCategory->name . ' right now. Try another category.' : 'Sellers have not listed any products yet. Check back soon.'">
                        @if ($activeCategory)
                            <a href="{{ route('buyer.products.index') }}" class="inline-flex h-10 items-center rounded-md bg-[#402143] px-5 text-[13px] font-medium text-white transition-colors duration-200 hover:bg-[#52245b]">Browse all products</a>
                        @endif
                        <a href="{{ route('buyer.dashboard') }}" class="inline-flex h-10 items-center rounded-md border border-[#805487] px-5 text-[13px] font-medium text-[#52245b] transition-colors duration-200 hover:bg-[#f5ecf6]">Back to home</a>
                    </x-buyer.empty-state>
                @endif
            </div>
        @else
            <div class="mt-4 grid grid-cols-2 gap-2.5 sm:grid-cols-3 sm:gap-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
                @foreach ($products as $product)
                    @include('buyer.partials.product-card', ['product' => $product])
                @endforeach
            </div>

            @if ($products->hasPages())
                <div class="mt-6">{{ $products->withQueryString()->links() }}</div>
            @endif
        @endif
    </div>

    @include('buyer.partials.quick-add')
    @include('shared.live-revision', ['endpoint' => route('buyer.live', 'catalog'), 'mode' => 'reload'])
</x-buyer.layout>
