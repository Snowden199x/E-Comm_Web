{{-- Shared storefront product card.
     Params: $product (Product with images), $compact (bool, optional: icon-only cart button).
     Optional data (shown only when the controller supplies it):
       reviews_avg_rating, sold_count, compare_at_price, seller.sellerDetail --}}
@php
    $compact = $compact ?? false;
    $image = $product->images->first();
    $imageUrl = $image ? asset('storage/' . $image->path) : asset('images/products/tote-bag.jpg');
    $seller = $product->seller;
    $shopName = $seller?->sellerDetail?->business_name ?: $seller?->name;
    $rating = $product->reviews_avg_rating ?? null;
    $sold = $product->sold_count ?? null;
    $compareAt = $product->compare_at_price ?? null;
    $colorOptions = array_values(array_filter(array_map('trim', explode(',', (string) $product->colors))));
    $sizeOptions = array_values(array_filter(array_map('trim', explode(',', (string) $product->sizes))));
    $hasOptions = $colorOptions || $sizeOptions;
    $soldOut = $product->stock <= 0;
    $fmt = fn ($amount) => '₱' . number_format($amount, fmod((float) $amount, 1) ? 2 : 0);
    $payload = [
        'id' => $product->id,
        'name' => $product->name,
        'price' => $fmt($product->price),
        'image' => $imageUrl,
        'colors' => $colorOptions,
        'sizes' => $sizeOptions,
        'stock' => (int) $product->stock,
    ];
@endphp

<article
    class="group relative flex flex-col overflow-hidden rounded-lg border border-[#eee6ef] bg-white transition-all duration-300 ease-vendo hover:-translate-y-0.5 hover:border-[#cfb2d4] hover:shadow-[0_14px_26px_-16px_rgba(64,33,67,0.5)]">

    <a href="{{ route('buyer.products.show', $product) }}" class="block aspect-square overflow-hidden bg-[#f8f3e6]" tabindex="-1" aria-hidden="true">
        <img src="{{ $imageUrl }}" alt="" loading="lazy"
            class="vb-img h-full w-full object-cover group-hover:scale-105"
            onload="this.classList.add('is-loaded')">
    </a>

    <div class="flex flex-1 flex-col p-2.5">
        <a href="{{ route('buyer.products.show', $product) }}" title="{{ $product->name }}"
            class="line-clamp-2 min-h-[36px] text-[13px] leading-[18px] text-[#2b1730] transition-colors duration-200 hover:text-[#805487]">{{ $product->name }}</a>

        <div class="mt-1.5 flex min-h-[16px] items-center gap-1 text-[11px] leading-4">
            @if (! is_null($rating))
                <svg class="h-3 w-3 text-[#e0b84a]" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2.5l2.9 6.1 6.6.8-4.9 4.6 1.3 6.6L12 17.3l-5.9 3.3 1.3-6.6L2.5 9.4l6.6-.8z" /></svg>
                <span class="font-medium">{{ number_format((float) $rating, 1) }}</span>
            @endif
            @if (! is_null($sold))
                <span class="text-[#9a8a9d]">{{ $rating !== null ? '(' . number_format($sold) . ' sold)' : number_format($sold) . ' sold' }}</span>
            @endif
        </div>

        <div class="mt-1 flex items-center justify-between gap-2">
            <div class="flex min-w-0 items-baseline gap-1.5">
                <span class="text-[16px] font-semibold leading-6 text-[#52245b]">{{ $fmt($product->price) }}</span>
                @if ($compareAt && $compareAt > $product->price)
                    <span class="text-[11px] text-[#9a8a9d] line-through">{{ $fmt($compareAt) }}</span>
                @endif
            </div>

            @if ($compact && ! $soldOut)
                @if ($hasOptions)
                    <button type="button" aria-label="Add {{ $product->name }} to cart"
                        @click="$dispatch('quick-add', @js($payload))"
                        class="grid h-8 w-8 flex-shrink-0 place-items-center rounded-full text-[#805487] transition-all duration-300 ease-vendo hover:bg-[#f3e8f5] hover:text-[#402143] active:scale-90">
                        <span class="vb-icon h-[18px] w-[18px]" style="--icon: url('{{ asset('assets/icons/buyer/cart-icon.svg') }}')"></span>
                    </button>
                @else
                    <form method="POST" action="{{ route('buyer.cart.store') }}" x-data="{ adding: false }"
                        x-target="cart-badge buyer-toast" @ajax:before="adding = true" @ajax:after="adding = false">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                        <button type="submit" :disabled="adding" aria-label="Add {{ $product->name }} to cart"
                            class="grid h-8 w-8 flex-shrink-0 place-items-center rounded-full text-[#805487] transition-all duration-300 ease-vendo hover:bg-[#f3e8f5] hover:text-[#402143] active:scale-90 disabled:opacity-50">
                            <span class="vb-icon h-[18px] w-[18px]" style="--icon: url('{{ asset('assets/icons/buyer/cart-icon.svg') }}')"></span>
                        </button>
                    </form>
                @endif
            @endif
        </div>

        @unless ($compact)
            @if ($shopName)
                <p class="mt-1 flex items-center gap-1 truncate text-[11px] leading-4 text-[#8a7a8e]">
                    <span class="h-1.5 w-1.5 flex-shrink-0 rounded-full bg-[#805487]"></span>
                    <span class="truncate">{{ $shopName }}</span>
                </p>
            @endif

            <div class="mt-2.5">
                @if ($soldOut)
                    <span class="flex h-8 w-full items-center justify-center rounded-md bg-[#efe8f0] text-[12px] font-medium text-[#8c7a8e]">Out of Stock</span>
                @elseif ($hasOptions)
                    <button type="button" @click="$dispatch('quick-add', @js($payload))"
                        class="flex h-8 w-full items-center justify-center rounded-md bg-[#805487] text-[12px] font-medium text-white transition-all duration-300 ease-vendo hover:bg-[#6d4574] hover:shadow-[0_10px_18px_-10px_rgba(128,84,135,0.95)] active:scale-[0.97]">Add to Cart</button>
                @else
                    <form method="POST" action="{{ route('buyer.cart.store') }}" x-data="{ adding: false }"
                        x-target="cart-badge buyer-toast" @ajax:before="adding = true" @ajax:after="adding = false">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                        <button type="submit" :disabled="adding"
                            class="flex h-8 w-full items-center justify-center rounded-md bg-[#805487] text-[12px] font-medium text-white transition-all duration-300 ease-vendo hover:bg-[#6d4574] hover:shadow-[0_10px_18px_-10px_rgba(128,84,135,0.95)] active:scale-[0.97] disabled:opacity-70">
                            <span x-text="adding ? 'Adding…' : 'Add to Cart'">Add to Cart</span>
                        </button>
                    </form>
                @endif
            </div>
        @endunless
    </div>
</article>