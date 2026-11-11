{{--
    Buyer home / dashboard.
    Data from BuyerDashboardController: $announcements (or one $announcement), $categories, $products, $cartTotal.

    Layout rhythm: 16px page gutters, 16px between sections, 12px between cards, 16px inside cards.
    Colors: plum (#402143) for primary actions, lavender tints for surfaces, pastel green/rose/gold only for status.
    Empty states: no announcements (welcome slide), no categories, no products. Favorites appear only once something is saved.
    Loading: images shimmer until loaded, Add to cart shows a spinner, page changes show the top progress bar.
--}}
@php
    use Illuminate\Support\Str;

    // ---- Announcements -> carousel slides ------------------------------------
    $slides = collect($announcements ?? [])->values();
    if ($slides->isEmpty() && ! empty($announcement)) {
        $slides = collect([$announcement]);
    }
    $usingFallback = $slides->isEmpty();
    if ($usingFallback) {
        $slides = collect([(object) [
            'title' => 'Welcome to Vendo',
            'message' => 'Shop approved sellers. Pay when your order arrives.',
        ]]);
    }
    $pillLabel = $usingFallback ? 'Welcome' : 'Announcement';
    $many = $slides->count() > 1;
    $cartTotal = (int) ($cartTotal ?? 0);

    // Category name -> photo in public/images/categories (names that are not listed show their initial)
    $categoryPhotos = [
        'Pet Supplies' => 'pet-and-supplies', 'Electronics and Gadgets' => 'electronics-and-gadgets',
        "Women's Apparel" => "women's-apparel", "Men's Apparel" => "men's-apparel", 'Kids and Baby' => 'kids-and-baby',
        'Home and Garden' => 'home-and-garden', 'Sports and Outdoors' => 'sports-and-outdoor', 'Health and Beauty' => 'health-and-beauty',
        'Books and Media' => 'books-and-media', 'Food and Gourmet' => 'food-and-gourmet', 'Automotive & Motorcycle' => 'automotive-and-motorcycle',
        'Furniture and Office Equipment' => 'furniture-and-office', 'Jewelry and Watches' => 'jewelry-and-watches', 'Office and School Supplies' => 'office-and-school',
    ];
@endphp

<x-buyer.layout title="Dashboard — Vendo">
    <div class="mx-auto max-w-[1200px] px-3 pb-8 pt-4 sm:px-4">

        <!-- ===================== Hero + shortcuts ===================== -->
        <div class="vb-reveal grid gap-3 lg:grid-cols-[1fr_272px]">

            <section aria-roledescription="carousel" aria-label="Announcements"
                class="vb-hero relative overflow-hidden rounded-xl"
                :class="{ 'is-paused': paused }"
                tabindex="0"
                x-data="{
                    active: 0,
                    total: {{ $slides->count() }},
                    paused: false,
                    startX: null,
                    go(i) { this.active = (i + this.total) % this.total; },
                    next() { this.go(this.active + 1); },
                    prev() { this.go(this.active - 1); },
                    swipeStart(e) { this.startX = e.touches[0].clientX; },
                    swipeEnd(e) {
                        if (this.startX === null) return;
                        const dx = e.changedTouches[0].clientX - this.startX;
                        this.startX = null;
                        if (Math.abs(dx) > 48) { dx < 0 ? this.next() : this.prev(); }
                    },
                }"
                @mouseenter="paused = true" @mouseleave="paused = false"
                @focusin="paused = true" @focusout="paused = false"
                @keydown.arrow-right="next()" @keydown.arrow-left="prev()"
                @touchstart.passive="swipeStart($event)" @touchend.passive="swipeEnd($event)"
                @visibilitychange.document="paused = document.hidden">

                <div class="vb-hero__track" :style="`transform: translateX(-${active * 100}%)`">
                    @foreach ($slides as $i => $slide)
                        <article class="vb-hero__slide {{ $i === 0 ? 'is-active' : '' }}" :class="{ 'is-active': active === {{ $i }} }"
                            role="group" aria-roledescription="slide" aria-label="{{ $i + 1 }} of {{ $slides->count() }}"
                            :aria-hidden="active !== {{ $i }} ? 'true' : 'false'">

                            <!-- Flat artwork: parcels and coins (decorative) -->
                            <div class="pointer-events-none absolute inset-y-0 right-0 hidden w-[44%] sm:block" aria-hidden="true">
                                <svg class="h-full w-full" viewBox="0 0 400 300" fill="none" preserveAspectRatio="xMidYMid slice">
                                    <circle cx="300" cy="150" r="128" fill="#fff" fill-opacity="0.6" />
                                    <circle cx="300" cy="150" r="88" fill="#fff" fill-opacity="0.7" />
                                    <g class="vb-float">
                                        <rect x="238" y="104" width="128" height="116" rx="14" fill="#f6d9b8" />
                                        <rect x="238" y="104" width="128" height="30" rx="14" fill="#e8c874" />
                                        <rect x="292" y="104" width="20" height="116" fill="#402143" fill-opacity="0.14" />
                                        <path d="M278 104c0-22 14-38 24-38s24 16 24 38" stroke="#805487" stroke-opacity="0.7" stroke-width="7" stroke-linecap="round" />
                                    </g>
                                    <g class="vb-float vb-float--slow">
                                        <rect x="150" y="168" width="84" height="72" rx="10" fill="#c9a9ce" />
                                        <rect x="150" y="168" width="84" height="18" rx="9" fill="#b78fbd" />
                                        <rect x="184" y="168" width="14" height="72" fill="#52245b" fill-opacity="0.3" />
                                    </g>
                                    <circle cx="376" cy="58" r="14" fill="#e8c874" />
                                    <circle cx="206" cy="82" r="8" fill="#d8bfdc" />
                                    <rect x="120" y="116" width="14" height="14" rx="3" transform="rotate(18 127 123)" fill="#e8c874" />
                                </svg>
                            </div>

                            <div class="relative flex min-h-[220px] flex-col justify-center px-6 py-7 sm:min-h-[270px] sm:px-12 sm:pr-[46%]">
                                <span class="vb-rise inline-flex h-6 w-fit items-center gap-1.5 rounded-full border border-[#e3d3e6] bg-white px-3 text-[11px] font-semibold text-[#52245b]" style="--d: 0">
                                    {{ $pillLabel }}
                                    @if ($many)<span class="text-[#805487]">{{ sprintf('%02d', $i + 1) }} / {{ sprintf('%02d', $slides->count()) }}</span>@endif
                                </span>

                                <h2 class="vb-rise mt-3 line-clamp-2 max-w-[560px] pb-1 text-[clamp(26px,3.6vw,40px)] font-semibold leading-[1.1] text-[#402143]" style="--d: 1">{{ $slide->title }}</h2>

                                <p class="vb-rise mt-1.5 line-clamp-3 max-w-[460px] whitespace-pre-line text-[13px] leading-6 text-[#5b4a60] sm:text-[14px]" style="--d: 2">{{ $slide->message }}</p>

                                <div class="vb-rise mt-5 flex flex-wrap items-center gap-2.5" style="--d: 3">
                                    <a href="{{ route('buyer.products.index') }}" :tabindex="active === {{ $i }} ? 0 : -1" class="vb-btn vb-btn--primary vb-act">
                                        Shop now
                                        <svg class="vb-ico vb-ico--arrow h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg>
                                    </a>
                                    <a href="{{ route('buyer.categories') }}" :tabindex="active === {{ $i }} ? 0 : -1" class="vb-btn vb-btn--outline">Browse categories</a>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                @if ($many)
                    <!-- Arrows -->
                    <button type="button" @click="prev()" aria-label="Previous announcement"
                        class="vb-act absolute left-3 top-1/2 hidden h-10 w-10 -translate-y-1/2 place-items-center rounded-full border border-[#e3d3e6] bg-white text-[#52245b] shadow-[0_6px_14px_-8px_rgba(43,23,48,0.4)] transition-all duration-300 ease-vendo hover:bg-[#402143] hover:text-white active:scale-90 sm:grid">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6" /></svg>
                    </button>
                    <button type="button" @click="next()" aria-label="Next announcement"
                        class="vb-act absolute right-3 top-1/2 hidden h-10 w-10 -translate-y-1/2 place-items-center rounded-full border border-[#e3d3e6] bg-white text-[#52245b] shadow-[0_6px_14px_-8px_rgba(43,23,48,0.4)] transition-all duration-300 ease-vendo hover:bg-[#402143] hover:text-white active:scale-90 sm:grid">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6" /></svg>
                    </button>

                    <!-- Dots: the active one fills over 6.5s; when it finishes, the next slide opens -->
                    <div class="absolute bottom-4 left-6 flex items-center gap-1.5 sm:left-12">
                        @foreach ($slides as $i => $slide)
                            <button type="button" @click="go({{ $i }})" class="vb-dot" :class="{ 'is-active': active === {{ $i }} }"
                                :aria-current="active === {{ $i }}" aria-label="Show announcement {{ $i + 1 }}">
                                <span class="vb-dot__fill" @animationend="if (active === {{ $i }}) next()"></span>
                            </button>
                        @endforeach
                    </div>
                @endif
            </section>

            <!-- Shortcuts -->
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 lg:grid-cols-1" x-data>
                @php
                    $tileClass = 'vb-act group flex items-center gap-3 rounded-xl border border-[#eee6ef] bg-white p-3.5 transition-all duration-300 ease-vendo hover:-translate-y-0.5 hover:border-[#cfb2d4] hover:shadow-[0_14px_26px_-16px_rgba(64,33,67,0.5)]';
                    $chipClass = 'grid h-10 w-10 flex-shrink-0 place-items-center rounded-full bg-[#f3e8f5] text-[#52245b] transition-colors duration-300 group-hover:bg-[#805487] group-hover:text-white';
                    $arrow = '<svg class="vb-ico vb-ico--arrow ml-auto h-4 w-4 flex-shrink-0 text-[#9a8a9d] transition-colors group-hover:text-[#805487]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6" /></svg>';
                @endphp

                <a href="{{ route('buyer.orders.index') }}" class="{{ $tileClass }}">
                    <span class="{{ $chipClass }}"><span class="vb-icon h-5 w-5" style="--icon: url('{{ asset('assets/icons/buyer/my-orders-icon.svg') }}')"></span></span>
                    <span class="min-w-0">
                        <span class="block truncate text-[14px] font-semibold text-[#2b1730]">Track orders</span>
                        <span class="block truncate text-[12px] text-[#7a6a7e]">Check delivery status</span>
                    </span>
                    {!! $arrow !!}
                </a>

                <a href="{{ route('buyer.cart.index') }}" class="{{ $tileClass }}">
                    <span class="{{ $chipClass }}"><span class="vb-icon vb-ico vb-ico--cart h-5 w-5" style="--icon: url('{{ asset('assets/icons/buyer/cart-icon.svg') }}')"></span></span>
                    <span class="min-w-0">
                        <span class="block truncate text-[14px] font-semibold text-[#2b1730]">View cart</span>
                        <span class="block truncate text-[12px] text-[#7a6a7e]">{{ $cartTotal > 0 ? \App\Models\Ecommerce\CartItem::displayCount($cartTotal).' '.Str::plural('item', $cartTotal).' ready to order' : 'Your cart is empty' }}</span>
                    </span>
                    {!! $arrow !!}
                </a>

                <a href="{{ route('buyer.favorites') }}" class="{{ $tileClass }}">
                    <span class="{{ $chipClass }}">
                        <svg class="vb-ico vb-ico--heart h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20.4s-7.6-4.6-9.2-9.5C1.7 7.5 3.8 4.6 6.9 4.6c1.9 0 3.6 1 5.1 3 1.5-2 3.2-3 5.1-3 3.1 0 5.2 2.9 4.1 6.3-1.6 4.9-9.2 9.5-9.2 9.5z" /></svg>
                    </span>
                    <span class="min-w-0">
                        <span class="block truncate text-[14px] font-semibold text-[#2b1730]">View favorites</span>
                        <span class="block truncate text-[12px] text-[#7a6a7e]" x-text="$store.fav.items.length ? $store.fav.items.length + ' saved' : 'Nothing saved yet'">Nothing saved yet</span>
                    </span>
                    {!! $arrow !!}
                </a>
            </div>
        </div>

        <!-- ===================== Categories ===================== -->
        <section aria-labelledby="category-title" class="vb-reveal relative mt-4 rounded-xl border border-[#eee6ef] bg-white" style="--i: 1"
            x-data="{
                atStart: true,
                atEnd: false,
                update() {
                    const t = this.$refs.track;
                    if (!t) return;
                    this.atStart = t.scrollLeft <= 4;
                    this.atEnd = t.scrollLeft + t.clientWidth >= t.scrollWidth - 4;
                },
                scroll(dir) {
                    const t = this.$refs.track;
                    t.scrollBy({ left: dir * t.clientWidth * 0.8, behavior: 'smooth' });
                },
            }"
            x-init="$nextTick(() => update())"
            @resize.window.debounce.150ms="update()">

            <div class="flex items-center justify-between border-b border-[#f3ecf4] px-4 py-3">
                <h2 id="category-title" class="text-[15px] font-semibold text-[#402143]">Shop by category</h2>
                <a href="{{ route('buyer.categories') }}" class="vb-act group inline-flex items-center gap-1 text-[12px] font-medium text-[#805487] transition-colors duration-200 hover:text-[#402143]">
                    View all
                    <svg class="vb-ico vb-ico--arrow h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6" /></svg>
                </a>
            </div>

            @if ($categories->isEmpty())
                <x-buyer.empty-state compact icon="box" title="No categories yet" text="They appear here once sellers list products." />
            @else
                <div class="relative px-4 py-4">
                    <button type="button" @click="scroll(-1)" x-show="!atStart" x-cloak
                        x-transition.opacity.duration.250ms
                        class="absolute -left-3 top-[44px] z-10 hidden h-8 w-8 place-items-center rounded-full border border-[#eee6ef] bg-white text-[#805487] shadow-[0_6px_14px_-6px_rgba(43,23,48,0.35)] transition-all duration-300 ease-vendo hover:scale-110 hover:text-[#402143] active:scale-95 sm:grid"
                        aria-label="Scroll categories left">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6" /></svg>
                    </button>

                    <div x-ref="track" @scroll.passive="update()"
                        class="vb-no-scrollbar flex snap-x gap-4 overflow-x-auto scroll-smooth">
                        @foreach ($categories as $category)
                            @php
                                $categoryPhoto = $categoryPhotos[$category->name] ?? null;
                                $categoryColors = $category->colors;
                            @endphp
                            <a href="{{ route('buyer.products.index', ['category_id' => $category->id]) }}"
                                class="group flex w-[84px] flex-shrink-0 snap-start flex-col items-center">
                                {{-- Initial on the category tint shows until the photo loads, and stays if there is no photo --}}
                                <span class="relative grid h-[72px] w-[72px] place-items-center overflow-hidden rounded-full border text-[24px] font-semibold transition-all duration-500 ease-vendo group-hover:-translate-y-1 group-hover:shadow-[0_12px_20px_-12px_rgba(64,33,67,0.6)]"
                                    style="background: {{ $categoryColors['bg'] }}; border-color: {{ $categoryColors['border'] }}40; color: {{ $categoryColors['border'] }}">
                                    {{ Str::upper(Str::substr($category->name, 0, 1)) }}
                                    @if ($categoryPhoto)
                                        <img src="{{ asset('images/categories/'.rawurlencode($categoryPhoto).'.jpg') }}" alt="" loading="lazy"
                                            class="vb-img absolute inset-0 h-full w-full object-cover group-hover:scale-110"
                                            onload="this.classList.add('is-loaded')" onerror="this.remove()">
                                    @endif
                                </span>
                                <span class="mt-2 line-clamp-2 text-center text-[12px] leading-4 text-[#3d2a42] transition-colors duration-300 group-hover:text-[#805487]">{{ $category->name }}</span>
                            </a>
                        @endforeach
                    </div>

                    <button type="button" @click="scroll(1)" x-show="!atEnd" x-cloak
                        x-transition.opacity.duration.250ms
                        class="absolute -right-3 top-[44px] z-10 hidden h-8 w-8 place-items-center rounded-full border border-[#eee6ef] bg-white text-[#805487] shadow-[0_6px_14px_-6px_rgba(43,23,48,0.35)] transition-all duration-300 ease-vendo hover:scale-110 hover:text-[#402143] active:scale-95 sm:grid"
                        aria-label="Scroll categories right">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6" /></svg>
                    </button>
                </div>
            @endif
        </section>

        <!-- ===================== Favorites (only when something is saved) ===================== -->
        <section x-data x-show="$store.fav.items.length" x-cloak aria-labelledby="saved-title"
            x-transition:enter="transition duration-500 ease-vendo" x-transition:enter-start="opacity-0 translate-y-3" x-transition:enter-end="opacity-100 translate-y-0"
            class="mt-4 rounded-xl border border-[#eee6ef] bg-white">
            <div class="flex items-center justify-between border-b border-[#f3ecf4] px-4 py-3">
                <h2 id="saved-title" class="text-[15px] font-semibold text-[#402143]">Your favorites</h2>
                <a href="{{ route('buyer.favorites') }}" class="vb-act group inline-flex items-center gap-1 text-[12px] font-medium text-[#805487] transition-colors duration-200 hover:text-[#402143]">
                    View all
                    <svg class="vb-ico vb-ico--arrow h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6" /></svg>
                </a>
            </div>
            <ul class="vb-no-scrollbar flex gap-3 overflow-x-auto p-4">
                <template x-for="item in $store.fav.items.slice(0, 10)" :key="item.id">
                    <li class="w-[148px] flex-shrink-0">
                        <a :href="$store.fav.href(item)" class="group block">
                            <span class="vb-img-shell block aspect-square overflow-hidden rounded-lg border border-[#eee6ef]">
                                <img :src="item.image" alt="" loading="lazy" class="vb-img h-full w-full object-cover group-hover:scale-105"
                                    @load="$el.classList.add('is-loaded'); $el.parentElement.classList.add('is-loaded')">
                            </span>
                            <span x-text="item.name" class="mt-1.5 line-clamp-1 block text-[12px] text-[#2b1730] transition-colors duration-200 group-hover:text-[#805487]"></span>
                            <span x-text="item.price" class="block text-[13px] font-semibold text-[#52245b]"></span>
                        </a>
                    </li>
                </template>
            </ul>
        </section>

        <!-- ===================== Recommended for you ===================== -->
        <section aria-labelledby="recommended-title" class="mt-4">
            <h2 id="recommended-title" class="vb-reveal mb-3 text-[18px] font-semibold text-[#402143]">Recommended for you</h2>

            @if ($products->isEmpty())
                <div class="rounded-xl border border-[#eee6ef] bg-white">
                    <x-buyer.empty-state icon="box" title="No products yet" text="New listings from approved sellers appear here.">
                        <a href="{{ route('buyer.categories') }}" class="vb-btn vb-btn--outline">Browse categories</a>
                    </x-buyer.empty-state>
                </div>
            @else
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
                    @foreach ($products as $product)
                        <div class="vb-reveal flex" style="--i: {{ $loop->index % 12 }}">
                            @include('buyer.partials.product-card', ['product' => $product])
                        </div>
                    @endforeach
                </div>

                <div class="vb-reveal mt-6 flex justify-center">
                    <a href="{{ route('buyer.products.index') }}" class="vb-btn vb-btn--outline vb-act">
                        View all products
                        <svg class="vb-ico vb-ico--arrow h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg>
                    </a>
                </div>
            @endif
        </section>
    </div>

    @include('buyer.partials.quick-add')
    @include('shared.live-revision', ['endpoint' => route('buyer.live', 'catalog'), 'mode' => 'reload'])
</x-buyer.layout>
