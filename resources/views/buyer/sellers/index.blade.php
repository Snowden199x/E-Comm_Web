{{--
    Shop search (new page). Needs the route buyer.sellers.index and a controller method that passes:
      $sellers  paginator of approved, active seller Users with sellerDetail loaded
      $search   the search text (string, may be empty)
    Optional: $seller->approved_products_count (withCount) to show how many products each shop sells.
    See the backend note. The header search switches to "Shops" and sends here once the route exists.
--}}
@php
    use Illuminate\Support\Facades\Storage;
    use Illuminate\Support\Str;

    $search = trim((string) ($search ?? request('search')));
@endphp

<x-buyer.layout title="Shops | Vendo">
    <div class="vb-enter mx-auto max-w-[1000px] px-3 pb-14 pt-4 sm:px-4">

        <h1 class="text-[20px] font-semibold text-[#2b1730]">{{ $search !== '' ? 'Shops for “' . $search . '”' : 'Shops' }}</h1>
        <p class="mt-0.5 text-[13px] text-[#7a6a7e]">{{ number_format($sellers->total()) }} {{ Str::plural('shop', $sellers->total()) }}. Search by shop name or account name.</p>

        <form method="GET" action="{{ route('buyer.sellers.index') }}" role="search" class="mt-4 flex items-center gap-2">
            <label for="shop-query" class="sr-only">Search shops</label>
            <input id="shop-query" type="search" name="search" value="{{ $search }}" placeholder="Shop name or account name" autocomplete="off"
                class="h-10 min-w-0 flex-1 rounded-md border-[#e5dce7] bg-white px-3 text-[13px] text-[#2b1730] placeholder:text-[#9a8a9d] focus:border-[#805487] focus:ring-[#805487]">
            <button type="submit" class="inline-flex h-10 items-center rounded-md bg-[#402143] px-5 text-[13px] font-medium text-white transition-colors duration-200 hover:bg-[#52245b]">Search shops</button>
        </form>

        @if ($sellers->isEmpty())
            <div class="mt-4 rounded-lg border border-[#eee6ef] bg-white">
                @if ($search !== '')
                    <x-buyer.empty-state icon="store" title="No shops found for “{{ $search }}”" text="Check the spelling, or search by part of the shop or account name.">
                        <a href="{{ route('buyer.sellers.index') }}" class="inline-flex h-10 items-center rounded-md bg-[#402143] px-5 text-[13px] font-medium text-white transition-colors duration-200 hover:bg-[#52245b]">Show all shops</a>
                        <a href="{{ route('buyer.products.index', ['search' => $search]) }}" class="inline-flex h-10 items-center rounded-md border border-[#805487] px-5 text-[13px] font-medium text-[#52245b] transition-colors duration-200 hover:bg-[#f5ecf6]">Search products instead</a>
                    </x-buyer.empty-state>
                @else
                    <x-buyer.empty-state icon="store" title="No shops to show yet" text="Approved shops will be listed here.">
                        <a href="{{ route('buyer.dashboard') }}" class="inline-flex h-10 items-center rounded-md bg-[#402143] px-5 text-[13px] font-medium text-white transition-colors duration-200 hover:bg-[#52245b]">Back to home</a>
                    </x-buyer.empty-state>
                @endif
            </div>
        @else
            <ul class="mt-4 grid gap-3 sm:grid-cols-2">
                @foreach ($sellers as $seller)
                    @php
                        $detail = $seller->sellerDetail;
                        $shopName = $detail?->business_name ?: $seller->name;
                        $location = implode(', ', array_filter([$detail?->municipality, $detail?->province]));
                    @endphp
                    <li class="flex items-center gap-3 rounded-lg border border-[#eee6ef] bg-white p-4 transition-colors duration-200 hover:border-[#cfb2d4]">
                        <a href="{{ route('buyer.sellers.show', $seller) }}" class="flex-shrink-0" tabindex="-1" aria-hidden="true">
                            @if ($seller->profile_picture)
                                <img src="{{ Storage::url($seller->profile_picture) }}" alt="" loading="lazy" class="h-14 w-14 rounded-full border border-[#eee6ef] bg-white object-cover">
                            @else
                                <span class="grid h-14 w-14 place-items-center rounded-full bg-[#805487] text-[20px] font-semibold text-white">{{ mb_strtoupper(mb_substr($shopName, 0, 1)) }}</span>
                            @endif
                        </a>
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('buyer.sellers.show', $seller) }}" class="block truncate text-[14px] font-semibold text-[#2b1730] transition-colors duration-200 hover:text-[#805487]">{{ $shopName }}</a>
                            @if ($detail?->business_name && $detail->business_name !== $seller->name)
                                <p class="truncate text-[12px] text-[#8a7a8e]">{{ $seller->name }}</p>
                            @endif
                            <p class="mt-0.5 truncate text-[12px] text-[#7a6a7e]">
                                {{ $location ?: 'Philippines' }}@if (isset($seller->approved_products_count)), {{ number_format($seller->approved_products_count) }} {{ Str::plural('product', $seller->approved_products_count) }}@endif
                            </p>
                        </div>
                        <div class="flex flex-shrink-0 flex-col gap-1.5">
                            <a href="{{ route('buyer.sellers.show', $seller) }}" class="inline-flex h-8 items-center justify-center rounded-md bg-[#402143] px-3.5 text-[12px] font-medium text-white transition-colors duration-200 hover:bg-[#52245b]">View shop</a>
                            <a href="{{ route('buyer.marketplace-messages.seller.show', $seller) }}" class="inline-flex h-8 items-center justify-center rounded-md border border-[#e5dce7] px-3.5 text-[12px] font-medium text-[#3d2a42] transition-colors duration-200 hover:border-[#c9a9ce] hover:bg-[#faf5fa]">Chat</a>
                        </div>
                    </li>
                @endforeach
            </ul>

            @if ($sellers->hasPages())
                <div class="mt-6">{{ $sellers->withQueryString()->links() }}</div>
            @endif
        @endif
    </div>
</x-buyer.layout>