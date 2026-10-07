{{--
    Buyer product list (all products, a category, or search results).
    Uses the shared product card, so Add to Cart and the quick-select sheet work here too.
    The shop cross-links appear only once the route buyer.sellers.index exists.
--}}
@php
    $search = trim((string) request('search'));
    $activeCategory = request()->filled('category_id') ? \App\Models\Category::query()->find(request()->integer('category_id')) : null;
    $shopSearchUrl = \Illuminate\Support\Facades\Route::has('buyer.sellers.index') ? route('buyer.sellers.index') : null;
    $heading = $search !== '' ? 'Results for “' . $search . '”' : ($activeCategory?->name ?? 'All products');
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
                @if ($search !== '' || $activeCategory)
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

        @if ($products->isEmpty())
            <div class="mt-4 rounded-lg border border-[#eee6ef] bg-white">
                @if ($search !== '')
                    <x-buyer.empty-state icon="search" title="No products found for “{{ $search }}”"
                        text="Check the spelling, or try a shorter or more general word.">
                        <a href="{{ route('buyer.products.index') }}" class="inline-flex h-10 items-center rounded-md bg-[#402143] px-5 text-[13px] font-medium text-white transition-colors duration-200 hover:bg-[#52245b]">Browse all products</a>
                        @if ($shopSearchUrl)
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