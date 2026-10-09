{{--
    Buyer product detail.
    Uses only data the current ProductController already passes
    ($product, $reviews, $ratingSummary, $attributeLabels, $recommendedProducts).
    Optional extras show only when present: $product->sold_count (withCardMetrics) and compare_at_price.
    Form fields posted to buyer.cart.store are unchanged: product_id, variant_id, color, size, quantity.
    Variations: each variation type (Color, then Size, ...) is its own row, read from the saved options of the variants.
    A hidden variant_id is filled once every type is chosen. Later rows only offer what is in stock for the earlier choices.
    Shipping fee: shows $shippingFee when the controller passes it, otherwise the current rate (0), same as checkout.
    Add to Favorites uses the Saved items store (browser storage) from favorites-panel.blade.php.
--}}
@php
    use Illuminate\Support\Str;

    $colors = array_values(array_filter(array_map('trim', explode(',', $product->colors ?? ''))));
    $sizes = array_values(array_filter(array_map('trim', explode(',', $product->sizes ?? ''))));

    $photos = $product->images;
    $photoCount = $photos->count();
    $videoUrl = $product->video_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($product->video_path) : null;
    $mediaCount = $photoCount + ($videoUrl ? 1 : 0);

    $seller = $product->seller;
    $sellerDetail = $seller?->sellerDetail;
    $shopName = $sellerDetail?->business_name ?: $seller?->name;
    $shipsFrom = implode(', ', array_filter([$sellerDetail?->municipality, $sellerDetail?->province]));
    $category = $product->category;

    $fmt = fn ($amount) => '₱' . number_format($amount, fmod((float) $amount, 1) ? 2 : 0);

    // Variants -> small payload for the picker (price and stock change with the selected variant)
    $variantData = $product->has_variations
        ? $product->variants->map(fn ($variant) => [
            'id' => $variant->id,
            'label' => $variant->label,
            'price' => (float) $variant->price,
            'stock' => (int) $variant->stock,
            'options' => (object) (array) $variant->options,
        ])->values()
        : collect();

    // Variation types (Color, Size, ...) in the order the seller set them, with their values
    $variantTypeMap = [];
    foreach ($product->has_variations ? $product->variants : [] as $variant) {
        foreach ((array) $variant->options as $typeName => $optionValue) {
            $variantTypeMap[$typeName] = $variantTypeMap[$typeName] ?? [];
            if (! in_array($optionValue, $variantTypeMap[$typeName], true)) {
                $variantTypeMap[$typeName][] = $optionValue;
            }
        }
    }
    $variantTypes = collect($variantTypeMap)
        ->map(fn ($values, $name) => ['name' => (string) $name, 'values' => array_values($values)])->values();
    $stepped = $variantTypes->isNotEmpty(); // false for old variants that only have a label: the single list is used

    // Shipping fee shown before checkout. Checkout currently charges 0 (no quotes yet), so 0 is the honest default.
    $shippingFee = isset($shippingFee) ? (float) $shippingFee : 0.0;
    $shippingFeeText = '₱' . number_format($shippingFee, 2);
    $variantPrices = $variantData->pluck('price');
    $startPrice = $variantPrices->isNotEmpty() ? $variantPrices->min() : (float) $product->price;
    $basePriceText = ($variantPrices->isNotEmpty() && $variantPrices->min() != $variantPrices->max())
        ? $fmt($variantPrices->min()) . ' – ' . $fmt($variantPrices->max())
        : $fmt($startPrice);

    // Strike-through price only for single-price products
    $compareAt = ! $product->has_variations ? ($product->compare_at_price ?? null) : null;
    $showCompare = $compareAt && $compareAt > $product->price;
    $discount = $showCompare ? (int) round((1 - $product->price / $compareAt) * 100) : 0;

    $firstPhoto = $photos->first();
    $favorite = [
        'id' => $product->id,
        'name' => $product->name,
        'price' => $basePriceText,
        'image' => $firstPhoto ? asset('storage/' . $firstPhoto->path) : asset('images/products/tote-bag.jpg'),
        'url' => parse_url(route('buyer.products.show', $product), PHP_URL_PATH) ?: route('buyer.products.show', $product),
        'shop' => $shopName,
    ];

    $avg = (float) ($ratingSummary?->average_rating ?? 0);
    $ratingTotal = (int) ($ratingSummary?->total_reviews ?? 0);
    $sold = $product->sold_count ?? null;

    // TODO(backend): move these small read-only queries into ProductController@show.
    $ratingCounts = $ratingTotal
        ? $product->publishedReviews()->selectRaw('rating, COUNT(*) as total')->groupBy('rating')->pluck('total', 'rating')
        : collect();

    $shopStats = null;
    if ($seller) {
        $shopReviews = \App\Models\Ecommerce\ProductReview::query()->where('seller_id', $seller->id)->where('visibility', 'published');
        $shopStats = [
            'ratings' => (clone $shopReviews)->count(),
            'average' => (clone $shopReviews)->avg('rating'),
            'products' => \App\Models\Ecommerce\Product::query()->where('seller_id', $seller->id)->where('status', 'approved')->count(),
            'joined' => $seller->created_at?->diffForHumans(),
        ];
    }

    // Specification rows: base facts, then category details, then extra specifications
    $specs = collect([
        'Category' => collect([$category?->parent?->name, $category?->name])->filter()->implode(' / '),
        'Brand' => $product->brand,
        'Material' => $product->material,
        'Weight' => $product->weight,
        'Country of origin' => $product->country_of_origin,
        'Ships from' => $shipsFrom,
    ])->filter(fn ($value) => filled($value))
        ->map(fn ($value, $label) => ['label' => $label, 'value' => $value])->values();

    foreach ($product->attributeValues as $item) {
        $value = collect($item->value ?? [])->filter(fn ($v) => filled($v))->implode(', ');
        if ($value !== '') {
            $specs->push(['label' => $attributeLabels[$item->key] ?? Str::headline($item->key), 'value' => $value]);
        }
    }
    foreach ($product->specifications as $item) {
        if (filled($item->name) && filled($item->value)) {
            $specs->push(['label' => $item->name, 'value' => $item->value]);
        }
    }

    $chip = 'inline-flex min-h-[36px] items-center rounded-md border border-[#e5dce7] bg-white px-3 py-1.5 text-[13px] text-[#3d2a42] transition-colors duration-200 ease-vendo hover:border-[#c9a9ce] peer-checked:border-[#805487] peer-checked:bg-[#f5ecf6] peer-checked:text-[#52245b] peer-checked:shadow-[inset_0_0_0_1px_#805487] peer-focus-visible:outline peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-[#805487] peer-disabled:cursor-not-allowed peer-disabled:border-dashed peer-disabled:bg-[#faf7fb] peer-disabled:text-[#b7a9ba] peer-disabled:line-through';
    $chipBase = 'inline-flex min-h-[36px] items-center rounded-md border px-3 py-1.5 text-[13px] transition-colors duration-200 ease-vendo focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#805487]';
    $sectionBar = 'rounded-t-lg bg-[#f6f2f7] px-4 py-2.5 text-[15px] font-semibold text-[#402143] sm:px-5';
@endphp

<x-buyer.layout :title="$product->name . ' | Vendo'">
    <div class="vb-enter mx-auto max-w-[1200px] px-3 pb-14 pt-4 sm:px-4">

        <!-- Breadcrumb -->
        <nav aria-label="Breadcrumb" class="rounded-lg border border-[#eee6ef] bg-white px-4 py-2.5">
            <ol class="flex flex-wrap items-center gap-x-1.5 gap-y-1 text-[12px] text-[#7a6a7e]">
                <li><a href="{{ route('buyer.dashboard') }}" class="transition-colors duration-200 hover:text-[#805487]">Home</a></li>
                @if ($category?->parent)
                    <li class="flex items-center gap-1.5">
                        <svg class="h-3 w-3 text-[#c9bccc]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6" /></svg>
                        <a href="{{ route('buyer.products.index', ['category_id' => $category->parent->id]) }}" class="transition-colors duration-200 hover:text-[#805487]">{{ $category->parent->name }}</a>
                    </li>
                @endif
                @if ($category)
                    <li class="flex items-center gap-1.5">
                        <svg class="h-3 w-3 text-[#c9bccc]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6" /></svg>
                        <a href="{{ route('buyer.products.index', ['category_id' => $category->id]) }}" class="transition-colors duration-200 hover:text-[#805487]">{{ $category->name }}</a>
                    </li>
                @endif
                <li class="flex min-w-0 items-center gap-1.5">
                    <svg class="h-3 w-3 flex-shrink-0 text-[#c9bccc]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6" /></svg>
                    <span class="truncate text-[#2b1730]" aria-current="page">{{ $product->name }}</span>
                </li>
            </ol>
        </nav>

        <!-- Gallery + purchase panel -->
        <section aria-label="Product" class="mt-3 rounded-lg border border-[#eee6ef] bg-white p-4 sm:p-5"
            x-data="{
                media: 0,
                total: {{ max(1, $mediaCount) }},
                touchX: null,
                variants: @js($variantData),
                variantId: @js(old('variant_id') ? (int) old('variant_id') : null),
                qty: {{ max(1, (int) old('quantity', 1)) }},
                stock: {{ (int) $product->stock }},
                basePrice: @js($basePriceText),
                baseNumber: {{ (float) $product->price }},
                types: @js($variantTypes),
                picks: {},
                attempted: false,
                shipping: {{ $shippingFee }},
                fav: @js($favorite),
                init() {
                    const start = this.variants.find(v => v.id === this.variantId);
                    if (start && this.types.length) { this.picks = { ...start.options }; }
                },
                isPicked(i, value) { return this.picks[this.types[i].name] === value; },
                pool(i) {
                    return this.variants.filter(v => v.stock > 0 && this.types.slice(0, i).every(t => !this.picks[t.name] || v.options[t.name] === this.picks[t.name]));
                },
                available(i, value) { const name = this.types[i].name; return this.pool(i).some(v => v.options[name] === value); },
                choose(i, value) {
                    if (!this.available(i, value)) { return; }
                    this.picks = { ...this.picks, [this.types[i].name]: value };
                    this.types.forEach((t, j) => {
                        if (j > i && this.picks[t.name] && !this.available(j, this.picks[t.name])) {
                            const next = { ...this.picks }; delete next[t.name]; this.picks = next;
                        }
                    });
                    this.clamp();
                },
                get missing() { return this.types.filter(t => !this.picks[t.name]).map(t => t.name.toLowerCase()); },
                get unitPrice() { if (this.variant) { return this.variant.price; } return this.variants.length ? null : this.baseNumber; },
                get lineTotal() { return this.unitPrice === null ? null : this.unitPrice * (Number(this.qty) || 1) + this.shipping; },
                get variant() {
                    if (!this.variants.length) { return null; }
                    if (this.types.length) {
                        if (this.missing.length) { return null; }
                        return this.variants.find(v => this.types.every(t => v.options[t.name] === this.picks[t.name])) || null;
                    }
                    return this.variants.find(v => v.id === this.variantId) || null;
                },
                get maxQty() { return this.variant ? this.variant.stock : this.stock; },
                get priceText() {
                    if (this.variant) { return this.money(this.variant.price); }
                    if (this.types.length) {
                        const prices = this.variants.filter(v => this.types.every(t => !this.picks[t.name] || v.options[t.name] === this.picks[t.name])).map(v => v.price);
                        if (prices.length) {
                            const lo = Math.min(...prices), hi = Math.max(...prices);
                            return lo === hi ? this.money(lo) : this.money(lo) + ' – ' + this.money(hi);
                        }
                    }
                    return this.basePrice;
                },
                money(n) { n = Number(n); return '₱' + n.toLocaleString('en-PH', { minimumFractionDigits: Number.isInteger(n) ? 0 : 2, maximumFractionDigits: 2 }); },
                step(d) { this.qty = Math.min(Math.max(1, (Number(this.qty) || 1) + d), Math.max(1, this.maxQty)); },
                clamp() { this.qty = Math.min(Math.max(1, Math.floor(Number(this.qty)) || 1), Math.max(1, this.maxQty)); },
                prev() { this.media = (this.media - 1 + this.total) % this.total; },
                next() { this.media = (this.media + 1) % this.total; },
            }">
            <div class="grid gap-6 lg:grid-cols-[minmax(0,500px)_minmax(0,1fr)] lg:gap-8">

                <!-- Gallery -->
                <div class="flex flex-col-reverse gap-3 md:flex-row">
                    @if ($mediaCount > 1)
                        <div role="group" aria-label="Product photos and video"
                            class="vb-thin-scroll flex gap-2 overflow-x-auto pb-1 md:max-h-[460px] md:w-[72px] md:flex-shrink-0 md:flex-col md:overflow-y-auto md:overflow-x-hidden md:pb-0 md:pr-1">
                            @foreach ($photos as $image)
                                <button type="button" @click="media = {{ $loop->index }}" :aria-pressed="media === {{ $loop->index }}"
                                    :class="media === {{ $loop->index }} ? 'border-[#805487]' : 'border-[#eee6ef] hover:border-[#c9a9ce]'"
                                    class="h-[60px] w-[60px] flex-shrink-0 overflow-hidden rounded-md border-2 bg-[#faf7fb] transition-colors duration-200 ease-vendo md:h-[68px] md:w-[68px]"
                                    aria-label="Show photo {{ $loop->iteration }}">
                                    <img src="{{ asset('storage/' . $image->path) }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                </button>
                            @endforeach
                            @if ($videoUrl)
                                <button type="button" @click="media = {{ $photoCount }}" :aria-pressed="media === {{ $photoCount }}"
                                    :class="media === {{ $photoCount }} ? 'border-[#805487]' : 'border-[#eee6ef] hover:border-[#c9a9ce]'"
                                    class="grid h-[60px] w-[60px] flex-shrink-0 place-items-center overflow-hidden rounded-md border-2 bg-[#2b1730] text-white transition-colors duration-200 ease-vendo md:h-[68px] md:w-[68px]"
                                    aria-label="Show product video">
                                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M9 6.5v11l9-5.5z" /></svg>
                                </button>
                            @endif
                        </div>
                    @endif

                    <div class="group relative aspect-square min-w-0 flex-1 overflow-hidden rounded-lg border border-[#eee6ef] bg-[#faf7fb]" tabindex="0"
                        @keydown.arrow-left.prevent="prev()" @keydown.arrow-right.prevent="next()"
                        @touchstart.passive="touchX = $event.changedTouches[0].screenX"
                        @touchend.passive="if (touchX !== null && Math.abs($event.changedTouches[0].screenX - touchX) > 40) { $event.changedTouches[0].screenX < touchX ? next() : prev(); } touchX = null"
                        aria-label="Product gallery. Swipe or use the arrow keys to browse photos and video.">
                        @if ($mediaCount > 0)
                            @foreach ($photos as $image)
                                <img src="{{ asset('storage/' . $image->path) }}" alt="{{ $product->name }}, photo {{ $loop->iteration }} of {{ $photoCount }}"
                                    class="absolute inset-0 h-full w-full object-contain"
                                    x-show="media === {{ $loop->index }}" x-transition.opacity.duration.250ms @if (! $loop->first) x-cloak @endif>
                            @endforeach
                            @if ($videoUrl)
                                <video class="absolute inset-0 h-full w-full bg-black object-contain" controls playsinline preload="metadata"
                                    x-show="media === {{ $photoCount }}" @if ($photoCount > 0) x-cloak @endif
                                    src="{{ $videoUrl }}" aria-label="{{ $product->name }} product video">
                                    Your browser does not support product video playback.
                                </video>
                            @endif
                            @if ($mediaCount > 1)
                                <button type="button" @click="prev()" aria-label="Previous gallery item"
                                    class="absolute left-2 top-1/2 grid h-9 w-9 -translate-y-1/2 place-items-center rounded-full bg-white/95 text-[#402143] shadow-[0_6px_14px_-6px_rgba(43,23,48,0.45)] transition-all duration-200 ease-vendo hover:bg-white active:scale-90 md:opacity-0 md:group-hover:opacity-100 md:group-focus-within:opacity-100">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 6-6 6 6 6" /></svg>
                                </button>
                                <button type="button" @click="next()" aria-label="Next gallery item"
                                    class="absolute right-2 top-1/2 grid h-9 w-9 -translate-y-1/2 place-items-center rounded-full bg-white/95 text-[#402143] shadow-[0_6px_14px_-6px_rgba(43,23,48,0.45)] transition-all duration-200 ease-vendo hover:bg-white active:scale-90 md:opacity-0 md:group-hover:opacity-100 md:group-focus-within:opacity-100">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6" /></svg>
                                </button>
                                <span class="pointer-events-none absolute bottom-2 right-2 rounded-full bg-[#2b1730]/70 px-2 py-0.5 text-[11px] text-white" x-text="(media + 1) + ' / ' + total"></span>
                            @endif
                        @else
                            <x-buyer.empty-state icon="box" compact title="No photo yet" text="The seller has not added photos for this product." class="h-full justify-center" />
                        @endif
                    </div>
                </div>

                <!-- Purchase panel -->
                <div class="min-w-0">
                    <h1 class="text-[20px] font-semibold leading-snug text-[#2b1730] sm:text-[22px]">{{ $product->name }}</h1>

                    <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-[13px] text-[#5b4a60]">
                        @if ($ratingTotal)
                            <a href="#reviews" class="flex items-center gap-1.5 transition-colors duration-200 hover:text-[#402143]">
                                <span class="font-semibold text-[#402143] underline underline-offset-2">{{ number_format($avg, 1) }}</span>
                                <x-buyer.stars :rating="$avg" />
                            </a>
                            <span class="h-4 w-px bg-[#e5dce7]" aria-hidden="true"></span>
                            <span>{{ number_format($ratingTotal) }} {{ Str::plural('rating', $ratingTotal) }}</span>
                        @else
                            <span class="text-[#9a8a9d]">No ratings yet</span>
                        @endif
                        @if ($sold)
                            <span class="h-4 w-px bg-[#e5dce7]" aria-hidden="true"></span>
                            <span>{{ number_format($sold) }} sold</span>
                        @endif
                    </div>

                    <!-- Price -->
                    <div class="mt-4 flex flex-wrap items-center gap-x-3 gap-y-1 rounded-lg bg-[#f6f2f7] px-4 py-3">
                        <span class="text-[26px] font-semibold leading-none text-[#52245b] sm:text-[28px]" x-text="priceText">{{ $basePriceText }}</span>
                        @if ($showCompare)
                            <span class="text-[14px] text-[#9a8a9d] line-through">{{ $fmt($compareAt) }}</span>
                            <span class="rounded bg-[#e8c874] px-1.5 py-0.5 text-[11px] font-semibold text-[#402143]">&minus;{{ $discount }}%</span>
                        @endif
                    </div>

                    <form action="{{ route('buyer.cart.store') }}" method="POST" class="mt-5"
                        @submit="if (types.length && !variant) { $event.preventDefault(); attempted = true; $nextTick(() => document.getElementById('variation-block')?.scrollIntoView({ behavior: 'smooth', block: 'center' })); }">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">

                        <dl class="space-y-4 text-[13px]">
                            <div class="grid grid-cols-[88px_minmax(0,1fr)] items-start gap-3 sm:grid-cols-[104px_minmax(0,1fr)]">
                                <dt class="pt-0.5 text-[#8a7a8e]">Delivery</dt>
                                <dd class="text-[#2b1730]">
                                    @if ($shipsFrom)<span class="block">Ships from {{ $shipsFrom }}</span>@endif
                                    <span class="mt-1 flex flex-wrap items-baseline gap-x-2">
                                        <span class="text-[#5b4a60]">Shipping fee</span>
                                        <span class="text-[15px] font-semibold text-[#52245b]">{{ $shippingFeeText }}</span>
                                    </span>
                                    <span class="mt-0.5 block text-[12px] text-[#6f5f73]">
                                        @if ($shippingFee > 0)
                                            Added to your total at checkout.
                                        @else
                                            Shipping quotes are not available yet, so no shipping fee is charged.
                                        @endif
                                    </span>
                                </dd>
                            </div>
                            <div class="grid grid-cols-[88px_minmax(0,1fr)] items-start gap-3 sm:grid-cols-[104px_minmax(0,1fr)]">
                                <dt class="pt-0.5 text-[#8a7a8e]">Payment</dt>
                                <dd class="text-[#2b1730]">Cash on delivery</dd>
                            </div>

                            @if ($product->stock > 0)
                                @if ($product->has_variations && $variantData->isNotEmpty())
                                    @if ($stepped)
                                        @foreach ($variantTypes as $i => $type)
                                            <div @if ($loop->first) id="variation-block" @endif class="grid grid-cols-[88px_minmax(0,1fr)] items-start gap-3 sm:grid-cols-[104px_minmax(0,1fr)]">
                                                <dt class="pt-2 text-[#8a7a8e]" id="variation-label-{{ $i }}">{{ $type['name'] }}</dt>
                                                <dd>
                                                    <div class="flex flex-wrap gap-2" role="radiogroup" aria-labelledby="variation-label-{{ $i }}">
                                                        @foreach ($type['values'] as $value)
                                                            <button type="button" role="radio"
                                                                @click="choose({{ $i }}, @js($value))"
                                                                :aria-checked="isPicked({{ $i }}, @js($value))"
                                                                :disabled="!available({{ $i }}, @js($value))"
                                                                :class="isPicked({{ $i }}, @js($value))
                                                                    ? 'border-[#805487] bg-[#f5ecf6] text-[#52245b] shadow-[inset_0_0_0_1px_#805487]'
                                                                    : (available({{ $i }}, @js($value))
                                                                        ? 'border-[#e5dce7] bg-white text-[#3d2a42] hover:border-[#c9a9ce]'
                                                                        : 'cursor-not-allowed border-dashed border-[#e5dce7] bg-[#faf7fb] text-[#b7a9ba] line-through')"
                                                                class="{{ $chipBase }}">
                                                                <svg x-show="isPicked({{ $i }}, @js($value))" x-cloak class="mr-1 h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12.5 4.5 4.5L19 7.5" /></svg>
                                                                {{ $value }}
                                                            </button>
                                                        @endforeach
                                                    </div>
                                                    @if (! $loop->first)
                                                        <p x-show="!picks[types[{{ $i - 1 }}].name]" class="mt-1.5 text-[12px] text-[#6f5f73]">Choose a {{ \Illuminate\Support\Str::lower($variantTypes[$i - 1]['name']) }} first to see what is in stock.</p>
                                                    @endif
                                                    <p x-show="attempted && !picks[types[{{ $i }}].name]" x-cloak class="mt-1.5 text-[12px] text-[#a32b43]" role="alert">Select a {{ \Illuminate\Support\Str::lower($type['name']) }}.</p>
                                                </dd>
                                            </div>
                                        @endforeach
                                        <input type="hidden" name="variant_id" :value="variant ? variant.id : ''">
                                    @else
                                    <div class="grid grid-cols-[88px_minmax(0,1fr)] items-start gap-3 sm:grid-cols-[104px_minmax(0,1fr)]">
                                        <dt class="pt-2 text-[#8a7a8e]" id="variation-label">Variation</dt>
                                        <dd><div class="flex flex-wrap gap-2" role="radiogroup" aria-labelledby="variation-label">
                                            @foreach ($product->variants as $variant)
                                                <label class="relative cursor-pointer" title="{{ $fmt($variant->price) }}, {{ $variant->stock }} available">
                                                    <input type="radio" name="variant_id" value="{{ $variant->id }}" class="peer sr-only" required
                                                        x-model.number="variantId" @disabled($variant->stock < 1) @checked((int) old('variant_id') === $variant->id)>
                                                    <span class="{{ $chip }}">{{ $variant->label }}@if ($variant->stock < 1)<span class="sr-only"> (sold out)</span>@endif</span>
                                                </label>
                                            @endforeach
                                        </div></dd>
                                    </div>
                                                                    @endif
                                @endif

                                @if ($colors && ! $product->has_variations)
                                    <div class="grid grid-cols-[88px_minmax(0,1fr)] items-start gap-3 sm:grid-cols-[104px_minmax(0,1fr)]">
                                        <dt class="pt-2 text-[#8a7a8e]" id="color-label">Color</dt>
                                        <dd><div class="flex flex-wrap gap-2" role="radiogroup" aria-labelledby="color-label">
                                            @foreach ($colors as $color)
                                                <label class="relative cursor-pointer">
                                                    <input type="radio" name="color" value="{{ $color }}" class="peer sr-only" required @checked(old('color') === $color)>
                                                    <span class="{{ $chip }}">{{ $color }}</span>
                                                </label>
                                            @endforeach
                                        </div></dd>
                                    </div>
                                @endif

                                @if ($sizes && ! $product->has_variations)
                                    <div class="grid grid-cols-[88px_minmax(0,1fr)] items-start gap-3 sm:grid-cols-[104px_minmax(0,1fr)]">
                                        <dt class="pt-2 text-[#8a7a8e]" id="size-label">Size</dt>
                                        <dd><div class="flex flex-wrap gap-2" role="radiogroup" aria-labelledby="size-label">
                                            @foreach ($sizes as $size)
                                                <label class="relative cursor-pointer">
                                                    <input type="radio" name="size" value="{{ $size }}" class="peer sr-only" required @checked(old('size') === $size)>
                                                    <span class="{{ $chip }}">{{ $size }}</span>
                                                </label>
                                            @endforeach
                                        </div></dd>
                                    </div>
                                @endif

                                <div class="grid grid-cols-[88px_minmax(0,1fr)] items-center gap-3 sm:grid-cols-[104px_minmax(0,1fr)]">
                                    <dt class="text-[#8a7a8e]"><label for="product-quantity">Quantity</label></dt>
                                    <dd class="flex flex-wrap items-center gap-3">
                                        <div class="inline-flex h-9 items-center overflow-hidden rounded-md border border-[#e5dce7] bg-white">
                                            <button type="button" @click="step(-1)" :disabled="qty <= 1" aria-label="Decrease quantity"
                                                class="grid h-full w-9 place-items-center text-[#52245b] transition-colors duration-200 hover:bg-[#f5ecf6] disabled:text-[#c9bccc] disabled:hover:bg-transparent">
                                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M5 12h14" /></svg>
                                            </button>
                                            <input id="product-quantity" type="number" name="quantity" x-model.number="qty" @change="clamp()"
                                                min="1" :max="maxQty" inputmode="numeric" required
                                                class="h-full w-12 rounded-none border-0 border-x border-[#e5dce7] p-0 text-center text-[13px] text-[#2b1730] focus:ring-0 [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none">
                                            <button type="button" @click="step(1)" :disabled="qty >= maxQty" aria-label="Increase quantity"
                                                class="grid h-full w-9 place-items-center text-[#52245b] transition-colors duration-200 hover:bg-[#f5ecf6] disabled:text-[#c9bccc] disabled:hover:bg-transparent">
                                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14" /></svg>
                                            </button>
                                        </div>
                                        <span class="text-[12px] text-[#2e6b46]" x-text="variants.length && !variant ? stock + ' in stock in total' : maxQty + ' available'">{{ $product->stock }} available</span>
                                    </dd>
                                </div>
                                <div class="grid grid-cols-[88px_minmax(0,1fr)] items-center gap-3 sm:grid-cols-[104px_minmax(0,1fr)]">
                                    <dt class="text-[#8a7a8e]">Order total</dt>
                                    <dd class="flex flex-wrap items-baseline gap-x-2">
                                        <span x-show="lineTotal !== null" x-text="lineTotal === null ? '' : money(lineTotal)" class="text-[16px] font-semibold text-[#52245b]"></span>
                                        <span x-show="lineTotal !== null" class="text-[12px] text-[#6f5f73]">item price and shipping fee</span>
                                        <span x-show="lineTotal === null" x-cloak class="text-[12px] text-[#6f5f73]" x-text="'Choose ' + missing.join(' and ') + ' to see your total.'"></span>
                                    </dd>
                                </div>
                            @endif
                        </dl>

                        <p x-show="attempted && types.length && !variant" x-cloak class="mt-4 rounded-md border border-[#f0c9d0] bg-[#fdf1f3] px-3 py-2 text-[13px] text-[#a32b43]" role="alert"
                            x-text="'Please choose ' + missing.join(' and ') + ' before adding to your cart.'"></p>

                        @if ($errors->any())
                            <p class="mt-4 rounded-md border border-[#f0c9d0] bg-[#fdf1f3] px-3 py-2 text-[13px] text-[#a32b43]" role="alert">{{ $errors->first() }}</p>
                        @endif

                        <div class="mt-5 flex flex-wrap items-stretch gap-2.5">
                            @if ($product->stock <= 0)
                                <span class="flex h-11 items-center justify-center rounded-md bg-[#efe8f0] px-6 text-[14px] font-medium text-[#8c7a8e]">Out of stock</span>
                            @else
                                <button type="submit"
                                    class="flex h-11 min-w-[180px] flex-1 items-center justify-center gap-2 rounded-md bg-[#402143] px-6 text-[14px] font-medium text-white transition-all duration-300 ease-vendo hover:bg-[#52245b] hover:shadow-[0_12px_20px_-12px_rgba(64,33,67,0.9)] active:scale-[0.98] sm:flex-none sm:min-w-[220px]">
                                    <span class="vb-icon h-[18px] w-[18px]" style="--icon: url('{{ asset('assets/icons/buyer/cart-icon.svg') }}')"></span>
                                    Add to Cart
                                </button>
                            @endif
                            <button type="button" @click="$store.fav.toggle(fav)" :aria-pressed="$store.fav.has(fav.id)"
                                :class="$store.fav.has(fav.id) ? 'border-[#c0395b] bg-[#fdf1f3] text-[#a32b43]' : 'border-[#e5dce7] text-[#3d2a42] hover:border-[#c9a9ce] hover:bg-[#faf5fa]'"
                                class="flex h-11 items-center justify-center gap-2 rounded-md border px-5 text-[14px] font-medium transition-all duration-200 ease-vendo active:scale-[0.98]">
                                <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" :fill="$store.fav.has(fav.id) ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20.4s-7.6-4.6-9.2-9.5C1.7 7.5 3.8 4.6 6.9 4.6c1.9 0 3.6 1 5.1 3 1.5-2 3.2-3 5.1-3 3.1 0 5.2 2.9 4.1 6.3-1.6 4.9-9.2 9.5-9.2 9.5z" /></svg>
                                <span x-text="$store.fav.has(fav.id) ? 'Added to Favorites' : 'Add to Favorites'">Add to Favorites</span>
                            </button>
                            @if ($seller)
                                <a href="{{ route('buyer.marketplace-messages.seller.show', $seller) }}"
                                    class="flex h-11 items-center justify-center gap-2 rounded-md border border-[#805487] px-5 text-[14px] font-medium text-[#52245b] transition-colors duration-200 hover:bg-[#f5ecf6]">
                                    <span class="vb-icon h-[18px] w-[18px]" style="--icon: url('{{ asset('assets/icons/buyer/messages-icon.svg') }}')"></span>
                                    Chat with seller
                                </a>
                            @endif
                        </div>
                    </form>
                </div>
            </div>

            <!-- What the buyer can rely on (each item is backed by current behavior) -->
            <ul class="mt-6 grid grid-cols-2 gap-3 border-t border-[#f1e8f2] pt-4 text-[12px] text-[#5b4a60] sm:grid-cols-4">
                <li class="flex items-center gap-2">
                    <svg class="h-[18px] w-[18px] flex-shrink-0 text-[#805487]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3l7 3v5c0 4.5-3 8-7 10-4-2-7-5.5-7-10V6z" /><path d="M9 12l2 2 4-4" /></svg>
                    Approved seller
                </li>
                <li class="flex items-center gap-2">
                    <svg class="h-[18px] w-[18px] flex-shrink-0 text-[#805487]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="6" width="18" height="12" rx="2" /><circle cx="12" cy="12" r="2.5" /></svg>
                    Pay on delivery
                </li>
                <li class="flex items-center gap-2">
                    <svg class="h-[18px] w-[18px] flex-shrink-0 text-[#805487]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 12a9 9 0 1 0 3-6.7L3 8" /><path d="M3 3v5h5" /></svg>
                    Cancel before preparing
                </li>
                <li class="flex items-center gap-2">
                    <svg class="h-[18px] w-[18px] flex-shrink-0 text-[#805487]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 5h16v11H9l-5 4z" /></svg>
                    Chat with the seller
                </li>
            </ul>
        </section>

        <!-- Shop -->
        @if ($seller)
            <section aria-label="Shop" class="mt-3 rounded-lg border border-[#eee6ef] bg-white p-4 sm:p-5">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-center">
                    <div class="flex min-w-0 items-center gap-4 lg:w-[44%] lg:flex-shrink-0 lg:border-r lg:border-[#f1e8f2] lg:pr-6">
                        @if ($seller->profile_picture)
                            <img src="{{ \Illuminate\Support\Facades\Storage::url($seller->profile_picture) }}" alt="" class="h-16 w-16 flex-shrink-0 rounded-full border border-[#eee6ef] bg-white object-cover">
                        @else
                            <span class="grid h-16 w-16 flex-shrink-0 place-items-center rounded-full bg-[#805487] text-[24px] font-semibold text-white">{{ mb_strtoupper(mb_substr($shopName, 0, 1)) }}</span>
                        @endif
                        <div class="min-w-0">
                            <a href="{{ route('buyer.sellers.show', $seller) }}" class="block truncate text-[15px] font-semibold text-[#2b1730] transition-colors duration-200 hover:text-[#805487]">{{ $shopName }}</a>
                            @if ($shipsFrom)<p class="mt-0.5 truncate text-[12px] text-[#8a7a8e]">{{ $shipsFrom }}</p>@endif
                            <div class="mt-2.5 flex flex-wrap gap-2">
                                <a href="{{ route('buyer.marketplace-messages.seller.show', $seller) }}"
                                    class="inline-flex h-8 items-center gap-1.5 rounded-md border border-[#805487] px-3 text-[12px] font-medium text-[#52245b] transition-colors duration-200 hover:bg-[#f5ecf6]">
                                    <span class="vb-icon h-4 w-4" style="--icon: url('{{ asset('assets/icons/buyer/messages-icon.svg') }}')"></span>Chat now
                                </a>
                                <a href="{{ route('buyer.sellers.show', $seller) }}"
                                    class="inline-flex h-8 items-center gap-1.5 rounded-md border border-[#e5dce7] px-3 text-[12px] font-medium text-[#3d2a42] transition-colors duration-200 hover:border-[#c9a9ce] hover:bg-[#faf5fa]">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 9l1.6-5h14.8L21 9" /><path d="M3 9a3 3 0 0 0 6 0a3 3 0 0 0 6 0a3 3 0 0 0 6 0" /><path d="M5 12v8h14v-8" /></svg>View shop
                                </a>
                            </div>
                        </div>
                    </div>

                    <dl class="grid flex-1 grid-cols-2 gap-x-6 gap-y-4 text-[13px] sm:grid-cols-4">
                        <div><dt class="text-[#8a7a8e]">Ratings</dt><dd class="mt-0.5 font-semibold text-[#52245b]">{{ number_format($shopStats['ratings']) }}</dd></div>
                        <div><dt class="text-[#8a7a8e]">Average rating</dt><dd class="mt-0.5 font-semibold text-[#52245b]">{{ $shopStats['ratings'] ? number_format($shopStats['average'], 1) . ' / 5' : 'None yet' }}</dd></div>
                        <div><dt class="text-[#8a7a8e]">Products</dt><dd class="mt-0.5 font-semibold text-[#52245b]">{{ number_format($shopStats['products']) }}</dd></div>
                        <div><dt class="text-[#8a7a8e]">Joined</dt><dd class="mt-0.5 font-semibold text-[#52245b]">{{ $shopStats['joined'] ?? 'Unknown' }}</dd></div>
                    </dl>
                </div>
            </section>
        @endif

        <!-- Specifications -->
        <section aria-labelledby="specs-heading" class="mt-3 rounded-lg border border-[#eee6ef] bg-white">
            <h2 id="specs-heading" class="{{ $sectionBar }}">Product specifications</h2>
            @if ($specs->isNotEmpty())
                <dl class="p-4 text-[13px] sm:p-5 lg:columns-2 lg:gap-x-12">
                    @foreach ($specs as $row)
                        <div class="grid break-inside-avoid grid-cols-[110px_minmax(0,1fr)] gap-3 py-1.5 sm:grid-cols-[140px_minmax(0,1fr)]">
                            <dt class="text-[#8a7a8e]">{{ $row['label'] }}</dt>
                            <dd class="whitespace-pre-line text-[#2b1730]">{{ $row['value'] }}</dd>
                        </div>
                    @endforeach
                </dl>
            @else
                <x-buyer.empty-state icon="box" compact title="No specifications yet" text="The seller has not listed details for this product." />
            @endif
        </section>

        <!-- Description -->
        <section aria-labelledby="description-heading" class="mt-3 rounded-lg border border-[#eee6ef] bg-white">
            <h2 id="description-heading" class="{{ $sectionBar }}">Product description</h2>
            @if (filled($product->description))
                <p class="whitespace-pre-line p-4 text-[13px] leading-6 text-[#3d2a42] sm:p-5">{{ $product->description }}</p>
            @else
                <x-buyer.empty-state icon="orders" compact title="No description yet" text="The seller has not written a description for this product." />
            @endif
        </section>

        <!-- Ratings -->
        <section id="reviews" aria-labelledby="reviews-heading" class="mt-3 scroll-mt-32 rounded-lg border border-[#eee6ef] bg-white">
            <h2 id="reviews-heading" class="{{ $sectionBar }}">Product ratings</h2>

            @if ($ratingTotal)
                <div class="flex flex-col gap-5 p-4 sm:p-5 md:flex-row md:items-center">
                    <div class="rounded-lg bg-[#f6f2f7] px-8 py-5 text-center md:w-[220px] md:flex-shrink-0">
                        <p class="text-[34px] font-semibold leading-none text-[#52245b]">{{ number_format($avg, 1) }}<span class="ml-1 text-[14px] font-normal text-[#7a6a7e]">out of 5</span></p>
                        <x-buyer.stars :rating="$avg" size="h-5 w-5" class="mt-2" />
                        <p class="mt-1.5 text-[12px] text-[#7a6a7e]">{{ number_format($ratingTotal) }} {{ Str::plural('rating', $ratingTotal) }}</p>
                    </div>
                    <ul class="min-w-0 flex-1 space-y-1.5 text-[12px] text-[#5b4a60]" aria-label="Ratings by star">
                        @foreach ([5, 4, 3, 2, 1] as $star)
                            @php $count = (int) ($ratingCounts[$star] ?? 0); @endphp
                            <li class="flex items-center gap-3">
                                <span class="w-12 flex-shrink-0">{{ $star }} {{ $star === 1 ? 'star' : 'stars' }}</span>
                                <span class="h-2 flex-1 overflow-hidden rounded-full bg-[#f0e9f2]"><span class="block h-full rounded-full bg-[#e0b84a]" style="width: {{ round($count / $ratingTotal * 100) }}%"></span></span>
                                <span class="w-10 flex-shrink-0 text-right tabular-nums">{{ number_format($count) }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="divide-y divide-[#f1e8f2] border-t border-[#f1e8f2] px-4 sm:px-5">
                @forelse ($reviews as $review)
                    <article class="py-4">
                        <div class="flex items-start gap-3">
                            <span class="grid h-9 w-9 flex-shrink-0 place-items-center rounded-full bg-[#805487] text-[13px] font-semibold text-white" aria-hidden="true">{{ mb_strtoupper(mb_substr($review->buyer?->name ?? 'B', 0, 1)) }}</span>
                            <div class="min-w-0 flex-1">
                                <p class="text-[13px] font-medium text-[#2b1730]">{{ $review->buyer?->name ?? 'Buyer' }}</p>
                                <div class="mt-0.5 flex flex-wrap items-center gap-x-3 gap-y-1">
                                    <x-buyer.stars :rating="$review->rating" />
                                    <time class="text-[11px] text-[#8a7a8e]" datetime="{{ $review->created_at->toDateString() }}">{{ $review->created_at->format('M j, Y') }}</time>
                                    <span class="rounded-full bg-[#eaf5ee] px-2 py-0.5 text-[11px] text-[#2e6b46]">Verified purchase</span>
                                </div>
                                @if (filled($review->comment))
                                    <p class="mt-2 whitespace-pre-line text-[13px] leading-6 text-[#3d2a42]">{{ $review->comment }}</p>
                                @endif
                                @if ($review->reply)
                                    <div class="mt-3 rounded-md border-l-2 border-[#805487] bg-[#f6f2f7] px-3 py-2.5 text-[13px]">
                                        <p class="font-medium text-[#402143]">Seller response</p>
                                        <p class="mt-1 whitespace-pre-line text-[#5b4a60]">{{ $review->reply->body }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </article>
                @empty
                    <x-buyer.empty-state icon="review" compact title="No ratings yet" text="Ratings appear here after buyers confirm they received this product." />
                @endforelse
            </div>

            @if ($reviews->hasPages())
                <div class="border-t border-[#f1e8f2] p-4 sm:px-5">{{ $reviews->fragment('reviews')->links() }}</div>
            @endif
        </section>

        <!-- You may also like -->
        @if ($recommendedProducts->isNotEmpty())
            <section aria-labelledby="related-heading" class="mt-5">
                <h2 id="related-heading" class="mb-3 text-[15px] font-semibold text-[#402143]">You may also like</h2>
                <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-3 sm:gap-3 lg:grid-cols-4">
                    @foreach ($recommendedProducts as $recommendedProduct)
                        @include('buyer.partials.product-card', ['product' => $recommendedProduct, 'compact' => true])
                    @endforeach
                </div>
            </section>
        @endif
    </div>

    {{-- Quick-add sheet for related cards that need a colour/size, and the "Added to cart" toast --}}
    @include('buyer.partials.quick-add')
    @include('shared.live-revision', ['endpoint' => route('buyer.live', 'catalog'), 'mode' => 'notice'])
</x-buyer.layout>