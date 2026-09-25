<x-buyer.layout :title="($seller->sellerDetail?->business_name ?: $seller->name).' | Vendo'">
    <div class="mx-auto max-w-7xl p-4 sm:p-5 lg:p-6">
        <a href="{{ url()->previous() }}" class="text-sm font-medium text-[#5b2963] hover:underline">← Back</a>
        <section class="mt-4 overflow-hidden rounded-2xl bg-white shadow-sm">
            <div class="h-36 bg-[#4b2452] sm:h-48" @if($seller->sellerDetail?->shop_banner_path) style="background: center / cover url('{{ \Illuminate\Support\Facades\Storage::url($seller->sellerDetail->shop_banner_path) }}')" @endif></div>
            <div class="flex flex-wrap items-end gap-4 p-5 sm:p-7">
                @if($seller->profile_picture)
                    <img src="{{ \Illuminate\Support\Facades\Storage::url($seller->profile_picture) }}" alt="" class="h-20 w-20 rounded-full border-4 border-white bg-white object-cover shadow sm:h-24 sm:w-24">
                @else
                    <span class="grid h-20 w-20 place-items-center rounded-full border-4 border-white bg-[#694272] text-3xl font-bold text-white shadow sm:h-24 sm:w-24">{{ mb_strtoupper(mb_substr($seller->sellerDetail?->business_name ?: $seller->name, 0, 1)) }}</span>
                @endif
                <div class="min-w-0 flex-1">
                    <h1 class="text-2xl font-bold text-gray-900">{{ $seller->sellerDetail?->business_name ?: $seller->name }}</h1>
                    <p class="text-sm text-gray-500">{{ implode(', ', array_filter([$seller->sellerDetail?->municipality, $seller->sellerDetail?->province])) }}</p>
                </div>
                <a href="{{ route('buyer.marketplace-messages.seller.show', $seller) }}" class="rounded-lg bg-[#3b1735] px-5 py-3 text-sm font-semibold text-white hover:bg-[#542149]">Chat with Seller</a>
            </div>
            @if($seller->sellerDetail?->shop_description)
                <p class="px-5 pb-6 text-sm leading-relaxed text-gray-700 sm:px-7">{{ $seller->sellerDetail->shop_description }}</p>
            @endif
        </section>
        <section class="mt-7">
            <h2 class="mb-4 text-lg font-bold text-gray-900">Products from this shop</h2>
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                @forelse($products as $product)
                    <a href="{{ route('buyer.products.show', $product) }}" class="rounded-2xl bg-white p-3 shadow-sm transition hover:shadow-md">
                        @if($product->images->first())<img src="{{ asset('storage/'.$product->images->first()->path) }}" alt="" class="mb-2 h-36 w-full rounded-lg object-cover">
                        @else<div class="mb-2 grid h-36 place-items-center rounded-lg bg-gray-100 text-xs text-gray-400">No photo</div>@endif
                        <strong class="line-clamp-2 block text-sm text-gray-900">{{ $product->name }}</strong>
                        <span class="mt-1 block text-sm font-bold text-[#3b1735]">₱{{ number_format($product->price, 2) }}</span>
                    </a>
                @empty<p class="col-span-full text-sm text-gray-500">No products available.</p>@endforelse
            </div>
            <div class="mt-6">{{ $products->links() }}</div>
        </section>
    </div>
</x-buyer.layout>
