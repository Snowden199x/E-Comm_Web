{{-- Shared storefront product card (preview style: the whole card opens the product page).
     Params: $product (Product with images), $compact (bool, optional: icon-only cart button),
       $toProduct (bool, optional: the Add to Cart button opens the product page instead of adding directly).
     Optional data (shown only when the controller supplies it):
       reviews_avg_rating, sold_count, compare_at_price, seller.sellerDetail
     The heart saves the product to the buyer's saved items (browser storage, see favorites-panel.blade.php);
     it never adds anything to the cart. --}}
@php
    $compact = $compact ?? false;
    $toProduct = $toProduct ?? false; // true: "Add to Cart" opens the product page so the buyer sees details first
    $image = $product->images->first();
    $imageUrl = $image ? asset('storage/' . $image->path) : asset('images/products/tote-bag.jpg');
    $seller = $product->seller;
    $shopName = $seller?->sellerDetail?->business_name ?: $seller?->name;
    $rating = $product->reviews_avg_rating ?? null;
    $sold = $product->sold_count ?? null;
    $compareAt = $product->compare_at_price ?? null;
    $discount = ($compareAt && $compareAt > $product->price) ? (int) round((1 - ($product->price / $compareAt)) * 100) : 0;
    $colorOptions = array_values(array_filter(array_map('trim', explode(',', (string) $product->colors))));
    $sizeOptions = array_values(array_filter(array_map('trim', explode(',', (string) $product->sizes))));
    $hasOptions = $colorOptions || $sizeOptions;
    $soldOut = $product->stock <= 0;
    $fmt = fn ($amount) => '₱' . number_format($amount, fmod((float) $amount, 1) ? 2 : 0);
    $productUrl = route('buyer.products.show', $product);
    $payload = [
        'id' => $product->id,
        'name' => $product->name,
        'price' => $fmt($product->price),
        'image' => $imageUrl,
        'colors' => $colorOptions,
        'sizes' => $sizeOptions,
        'stock' => (int) $product->stock,
    ];
    // What the saved-items list needs to show the product again later
    $favorite = [
        'id' => $product->id,
        'name' => $product->name,
        'price' => $fmt($product->price),
        'image' => $imageUrl,
        'url' => parse_url($productUrl, PHP_URL_PATH) ?: $productUrl,
        'shop' => $shopName,
    ];
@endphp

<article
    class="group relative flex w-full flex-col overflow-hidden rounded-lg border border-[#eee6ef] bg-white transition-all duration-300 ease-vendo hover:-translate-y-0.5 hover:border-[#cfb2d4] hover:shadow-[0_14px_26px_-16px_rgba(64,33,67,0.5)]">

    <!-- Photo -->
    <div class="relative aspect-square overflow-hidden bg-[#f8f3e6]">
        <img src="{{ $imageUrl }}" alt="" loading="lazy"
            class="vb-img h-full w-full object-cover group-hover:scale-105"
            onload="this.classList.add('is-loaded')">

        @if ($discount > 0 && ! $soldOut)
            <span class="absolute left-2 top-2 rounded bg-[#402143] px-1.5 py-0.5 text-[10px] font-semibold leading-4 text-white">-{{ $discount }}%</span>
        @endif

        @if ($soldOut)
            <span class="absolute inset-0 grid place-items-center bg-white/65">
                <span class="rounded-full bg-[#2b1730]/85 px-3 py-1 text-[11px] font-medium text-white">Sold out</span>
            </span>
        @endif

        <!-- Save for later (does not touch the cart) -->
        <button type="button" x-data="{ p: @js($favorite) }" @click.prevent.stop="$store.fav.toggle(p)"
            :aria-pressed="$store.fav.has(p.id)" :aria-label="($store.fav.has(p.id) ? 'Remove ' : 'Save ') + p.name + ($store.fav.has(p.id) ? ' from saved items' : ' for later')"
            :class="$store.fav.has(p.id) ? 'is-saved bg-white text-[#c0395b]' : 'bg-white/90 text-[#805487] hover:bg-white hover:text-[#c0395b]'"
            class="vb-heart absolute right-2 top-2 z-10 grid h-8 w-8 place-items-center rounded-full shadow-[0_6px_14px_-8px_rgba(43,23,48,0.6)]">
            <svg class="h-[17px] w-[17px]" viewBox="0 0 24 24" :fill="$store.fav.has(p.id) ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20.4s-7.6-4.6-9.2-9.5C1.7 7.5 3.8 4.6 6.9 4.6c1.9 0 3.6 1 5.1 3 1.5-2 3.2-3 5.1-3 3.1 0 5.2 2.9 4.1 6.3-1.6 4.9-9.2 9.5-9.2 9.5z" /></svg>
        </button>
    </div>

    <div class="flex flex-1 flex-col p-2.5">
        {{-- The name link is stretched over the whole card (after:inset-0), so a click anywhere opens the product. --}}
        <a href="{{ $productUrl }}" title="{{ $product->name }}"
            class="line-clamp-2 min-h-[36px] text-[13px] leading-[18px] text-[#2b1730] transition-colors duration-200 after:absolute after:inset-0 after:content-[''] hover:text-[#805487]">{{ $product->name }}</a>

        <div class="mt-1 flex min-h-[16px] items-center gap-1 text-[11px] leading-4">
            @if (! is_null($rating))
                <svg class="h-3 w-3 text-[#e0b84a]" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2.5l2.9 6.1 6.6.8-4.9 4.6 1.3 6.6L12 17.3l-5.9 3.3 1.3-6.6L2.5 9.4l6.6-.8z" /></svg>
                <span class="font-medium">{{ number_format((float) $rating, 1) }}</span>
            @endif
            @if (! is_null($sold))
                <span class="text-[#9a8a9d]">{{ $rating !== null ? '| ' : '' }}{{ number_format($sold) }} sold</span>
            @endif
        </div>

        <div class="mt-1 flex items-center justify-between gap-2">
            <div class="flex min-w-0 items-baseline gap-1.5">
                <span class="text-[16px] font-semibold leading-6 text-[#52245b]">{{ $fmt($product->price) }}</span>
                @if ($discount > 0)
                    <span class="text-[11px] text-[#9a8a9d] line-through">{{ $fmt($compareAt) }}</span>
                @endif
            </div>

            @if ($compact && ! $soldOut)
                @if ($product->has_variations)
                    <a href="{{ $productUrl }}" aria-label="Choose a variant of {{ $product->name }}"
                        class="relative z-10 grid h-8 w-8 flex-shrink-0 place-items-center rounded-full text-[#805487] hover:bg-[#f3e8f5]">
                        <span class="vb-icon h-[18px] w-[18px]" style="--icon: url('{{ asset('assets/icons/buyer/cart-icon.svg') }}')"></span>
                    </a>
                @elseif ($hasOptions)
                    <button type="button" aria-label="Add {{ $product->name }} to cart"
                        @click="$dispatch('quick-add', @js($payload))"
                        class="relative z-10 grid h-8 w-8 flex-shrink-0 place-items-center rounded-full text-[#805487] transition-all duration-300 ease-vendo hover:bg-[#f3e8f5] hover:text-[#402143] active:scale-90">
                        <span class="vb-icon h-[18px] w-[18px]" style="--icon: url('{{ asset('assets/icons/buyer/cart-icon.svg') }}')"></span>
                    </button>
                @else
                    <form method="POST" action="{{ route('buyer.cart.store') }}" x-data="{ adding: false }" class="relative z-10"
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
                    <svg class="h-3 w-3 flex-shrink-0 text-[#805487]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 9l1.6-5h14.8L21 9" /><path d="M3 9a3 3 0 0 0 6 0a3 3 0 0 0 6 0a3 3 0 0 0 6 0" /><path d="M5 12v8h14v-8" /></svg>
                    <span class="truncate">{{ $shopName }}</span>
                </p>
            @endif

            <div class="relative z-10 mt-2.5">
                @if ($soldOut)
                    <span class="flex h-8 w-full items-center justify-center rounded-md bg-[#efe8f0] text-[12px] font-medium text-[#8c7a8e]">Out of Stock</span>
                @elseif ($toProduct || $product->has_variations)
                    <a href="{{ $productUrl }}"
                        class="flex h-8 w-full items-center justify-center rounded-md border border-[#805487] bg-white text-[12px] font-medium text-[#52245b] transition-all duration-300 ease-vendo hover:bg-[#805487] hover:text-white active:scale-[0.97]">{{ $toProduct ? 'Add to Cart' : 'Choose variation' }}</a>
                @elseif ($hasOptions)
                    <button type="button" @click="$dispatch('quick-add', @js($payload))"
                        class="flex h-8 w-full items-center justify-center rounded-md border border-[#805487] bg-white text-[12px] font-medium text-[#52245b] transition-all duration-300 ease-vendo hover:bg-[#805487] hover:text-white active:scale-[0.97]">Add to Cart</button>
                @else
                    <form method="POST" action="{{ route('buyer.cart.store') }}" x-data="{ adding: false }"
                        x-target="cart-badge buyer-toast" @ajax:before="adding = true" @ajax:after="adding = false">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                        <button type="submit" :disabled="adding"
                            class="flex h-8 w-full items-center justify-center rounded-md border border-[#805487] bg-white text-[12px] font-medium text-[#52245b] transition-all duration-300 ease-vendo hover:bg-[#805487] hover:text-white active:scale-[0.97] disabled:opacity-70">
                            <span x-text="adding ? 'Adding…' : 'Add to Cart'">Add to Cart</span>
                        </button>
                    </form>
                @endif
            </div>
        @endunless
    </div>
</article>