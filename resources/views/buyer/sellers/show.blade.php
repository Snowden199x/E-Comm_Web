{{--
    Buyer view of a seller's shop.
    Works with what SellerProfileController@show passes today ($seller with sellerDetail, $products paginator).
    The in-shop search and sort bar appears only when the controller also passes $filters
    (['search' => string, 'sort' => 'latest|price_asc|price_desc']); see the backend note.
--}}
@php
    use Illuminate\Support\Facades\Storage;
    use Illuminate\Support\Str;

    $detail = $seller->sellerDetail;
    $shopName = $detail?->business_name ?: $seller->name;
    $location = implode(', ', array_filter([$detail?->municipality, $detail?->province]));
    $bannerUrl = $detail?->shop_banner_path ? Storage::url($detail->shop_banner_path) : null;

    $filtersEnabled = isset($filters);
    $search = trim((string) ($filters['search'] ?? ''));
    $sort = $filters['sort'] ?? 'latest';

    // TODO(backend): move these read-only counts into SellerProfileController@show.
    $shopReviews = \App\Models\Ecommerce\ProductReview::query()->where('seller_id', $seller->id)->where('visibility', 'published');
    $stats = [
        'ratings' => (clone $shopReviews)->count(),
        'average' => (clone $shopReviews)->avg('rating'),
        'products' => \App\Models\Ecommerce\Product::query()->where('seller_id', $seller->id)->where('status', 'approved')->count(),
        'joined' => $seller->created_at?->format('M Y'),
    ];
@endphp

<x-buyer.layout :title="$shopName . ' | Vendo'">
    <div class="vb-enter mx-auto max-w-[1200px] px-3 pb-14 pt-4 sm:px-4">

        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('buyer.products.index') }}"
            class="inline-flex items-center gap-1 text-[13px] font-medium text-[#805487] transition-colors duration-200 hover:text-[#402143]">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 6-6 6 6 6" /></svg>
            Back
        </a>

        <!-- Shop header -->
        <section class="mt-2 overflow-hidden rounded-lg border border-[#eee6ef] bg-white" aria-label="Shop">
            <div class="h-32 bg-[#4b2452] bg-cover bg-center sm:h-44" @if ($bannerUrl) style="background-image: url('{{ $bannerUrl }}')" @endif></div>

            <div class="px-4 pb-5 sm:px-6">
                <div class="-mt-10 flex flex-wrap items-end gap-x-4 gap-y-3 sm:-mt-12">
                    @if ($seller->profile_picture)
                        <img src="{{ Storage::url($seller->profile_picture) }}" alt="" class="h-20 w-20 flex-shrink-0 rounded-full border-4 border-white bg-white object-cover sm:h-24 sm:w-24">
                    @else
                        <span class="grid h-20 w-20 flex-shrink-0 place-items-center rounded-full border-4 border-white bg-[#805487] text-[30px] font-semibold text-white sm:h-24 sm:w-24">{{ mb_strtoupper(mb_substr($shopName, 0, 1)) }}</span>
                    @endif

                    <div class="min-w-0 flex-1 pb-1">
                        <h1 class="truncate text-[20px] font-semibold text-[#2b1730] sm:text-[22px]">{{ $shopName }}</h1>
                        @if ($location)
                            <p class="mt-0.5 flex items-center gap-1 text-[13px] text-[#7a6a7e]">
                                <svg class="h-3.5 w-3.5 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21s7-5.6 7-11a7 7 0 1 0-14 0c0 5.4 7 11 7 11z" /><circle cx="12" cy="10" r="2.5" /></svg>
                                {{ $location }}
                            </p>
                        @endif
                    </div>

                    <a href="{{ route('buyer.marketplace-messages.seller.show', $seller) }}"
                        class="inline-flex h-10 items-center gap-2 rounded-md bg-[#402143] px-5 text-[13px] font-medium text-white transition-all duration-300 ease-vendo hover:bg-[#52245b] active:scale-[0.98]">
                        <span class="vb-icon h-[18px] w-[18px]" style="--icon: url('{{ asset('assets/icons/buyer/messages-icon.svg') }}')"></span>
                        Chat with seller
                    </a>
                </div>

                <dl class="mt-5 grid grid-cols-2 gap-x-6 gap-y-3 border-t border-[#f1e8f2] pt-4 text-[13px] sm:grid-cols-4">
                    <div><dt class="text-[#8a7a8e]">Products</dt><dd class="mt-0.5 text-[16px] font-semibold text-[#52245b]">{{ number_format($stats['products']) }}</dd></div>
                    <div><dt class="text-[#8a7a8e]">Ratings</dt><dd class="mt-0.5 text-[16px] font-semibold text-[#52245b]">{{ number_format($stats['ratings']) }}</dd></div>
                    <div>
                        <dt class="text-[#8a7a8e]">Average rating</dt>
                        <dd class="mt-0.5 flex items-center gap-1.5 text-[16px] font-semibold text-[#52245b]">
                            @if ($stats['ratings'])
                                {{ number_format($stats['average'], 1) }}<x-buyer.stars :rating="$stats['average']" />
                            @else
                                <span class="text-[13px] font-normal text-[#9a8a9d]">None yet</span>
                            @endif
                        </dd>
                    </div>
                    <div><dt class="text-[#8a7a8e]">Joined</dt><dd class="mt-0.5 text-[16px] font-semibold text-[#52245b]">{{ $stats['joined'] ?? 'Unknown' }}</dd></div>
                </dl>

                @if (filled($detail?->shop_description))
                    <div class="mt-4 border-t border-[#f1e8f2] pt-4" x-data="{ open: false }">
                        <h2 class="text-[13px] font-semibold text-[#402143]">About this shop</h2>
                        <p class="mt-1 whitespace-pre-line text-[13px] leading-6 text-[#3d2a42]" :class="open ? '' : 'line-clamp-3'">{{ $detail->shop_description }}</p>
                        @if (Str::length($detail->shop_description) > 220)
                            <button type="button" @click="open = !open" class="mt-1 text-[12px] font-medium text-[#805487] hover:text-[#402143]" x-text="open ? 'Show less' : 'Read more'">Read more</button>
                        @endif
                    </div>
                @endif
            </div>
        </section>

        <!-- Products -->
        <section class="mt-5" aria-labelledby="shop-products-heading">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 id="shop-products-heading" class="text-[16px] font-semibold text-[#402143]">Products from this shop</h2>
                    <p class="text-[12px] text-[#7a6a7e]">
                        {{ number_format($products->total()) }} {{ Str::plural('product', $products->total()) }}@if ($search !== '') for “{{ $search }}”@endif
                    </p>
                </div>

                @if ($filtersEnabled)
                    <form method="GET" action="{{ route('buyer.sellers.show', $seller) }}" role="search" class="flex w-full flex-wrap items-center gap-2 sm:w-auto">
                        <label for="shop-search" class="sr-only">Search this shop</label>
                        <input id="shop-search" type="search" name="search" value="{{ $search }}" placeholder="Search in this shop" autocomplete="off"
                            class="h-9 min-w-0 flex-1 rounded-md border-[#e5dce7] bg-white px-3 text-[13px] text-[#2b1730] placeholder:text-[#9a8a9d] focus:border-[#805487] focus:ring-[#805487] sm:w-64 sm:flex-none">
                        <label for="shop-sort" class="sr-only">Sort products</label>
                        <select id="shop-sort" name="sort" onchange="this.form.requestSubmit()"
                            class="h-9 rounded-md border-[#e5dce7] bg-white py-0 pl-3 pr-8 text-[13px] text-[#2b1730] focus:border-[#805487] focus:ring-[#805487]">
                            <option value="latest" @selected($sort === 'latest')>Latest</option>
                            <option value="price_asc" @selected($sort === 'price_asc')>Price, low to high</option>
                            <option value="price_desc" @selected($sort === 'price_desc')>Price, high to low</option>
                        </select>
                        <button type="submit" class="inline-flex h-9 items-center rounded-md bg-[#402143] px-4 text-[13px] font-medium text-white transition-colors duration-200 hover:bg-[#52245b]">Search</button>
                    </form>
                @endif
            </div>

            @if ($products->isEmpty())
                <div class="mt-3 rounded-lg border border-[#eee6ef] bg-white">
                    @if ($search !== '')
                        <x-buyer.empty-state icon="search" title="No products match “{{ $search }}”" text="Try a shorter word, or clear the search to see everything this shop sells.">
                            <a href="{{ route('buyer.sellers.show', $seller) }}" class="inline-flex h-10 items-center rounded-md bg-[#402143] px-5 text-[13px] font-medium text-white transition-colors duration-200 hover:bg-[#52245b]">Show all products</a>
                        </x-buyer.empty-state>
                    @else
                        <x-buyer.empty-state icon="store" title="This shop has no products yet" text="Check back later, or browse products from other shops.">
                            <a href="{{ route('buyer.products.index') }}" class="inline-flex h-10 items-center rounded-md bg-[#402143] px-5 text-[13px] font-medium text-white transition-colors duration-200 hover:bg-[#52245b]">Browse all products</a>
                        </x-buyer.empty-state>
                    @endif
                </div>
            @else
                <div class="mt-3 grid grid-cols-2 gap-2.5 sm:grid-cols-3 sm:gap-3 md:grid-cols-4 lg:grid-cols-5">
                    @foreach ($products as $product)
                        @include('buyer.partials.product-card', ['product' => $product])
                    @endforeach
                </div>
                @if ($products->hasPages())
                    <div class="mt-6">{{ $products->withQueryString()->links() }}</div>
                @endif
            @endif
        </section>
    </div>

    @include('buyer.partials.quick-add')
</x-buyer.layout>