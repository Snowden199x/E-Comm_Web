{{--
    Buyer home / dashboard.
    Data from BuyerDashboardController (unchanged): $announcements (or one $announcement), $categories, $products.
    New in this version (views only, no controller change): announcement carousel with arrows, swipe and timed dots,
    quick links, a benefits strip, "Saved for later" (browser-stored favorites), animated sections, and the footer
    from the layout.
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
            'message' => 'Shop your favorites from approved sellers and get them delivered to your door.',
        ]]);
    }
    $pillLabel = $usingFallback ? 'Welcome' : 'Announcement';
    // Flat solid backgrounds that rotate per slide (no gradients)
    $slideBackgrounds = ['#402143', '#4b2152', '#3a2a5c', '#512a49'];
    $many = $slides->count() > 1;
@endphp

<x-buyer.layout title="Dashboard — Vendo">
    <div class="mx-auto max-w-[1200px] px-3 pb-6 pt-4 sm:px-4">

        <!-- ===================== Hero: announcements + quick links ===================== -->
        <div class="vb-reveal grid gap-3 lg:grid-cols-[1fr_272px]">

            <section aria-roledescription="carousel" aria-label="Announcements"
                class="vb-hero relative overflow-hidden rounded-xl"
                :class="{ 'is-paused': paused }"
                style="--banner-art: url('{{ asset('images/buyer/announcement-banner.png') }}')"
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
                            style="--slide-bg: {{ $slideBackgrounds[$i % count($slideBackgrounds)] }}"
                            role="group" aria-roledescription="slide" aria-label="{{ $i + 1 }} of {{ $slides->count() }}"
                            :aria-hidden="active !== {{ $i }} ? 'true' : 'false'">
                            <div class="vb-hero__art" aria-hidden="true"></div>

                            <!-- Flat artwork: bag, parcel and coins (decorative) -->
                            <div class="pointer-events-none absolute inset-y-0 right-0 hidden w-[44%] sm:block" aria-hidden="true">
                                <svg class="h-full w-full" viewBox="0 0 400 300" fill="none" preserveAspectRatio="xMidYMid slice">
                                    <circle cx="300" cy="150" r="128" fill="#fff" fill-opacity="0.07" />
                                    <circle cx="300" cy="150" r="88" fill="#fff" fill-opacity="0.07" />
                                    <g class="vb-float">
                                        <rect x="238" y="104" width="128" height="116" rx="14" fill="#f6d9b8" />
                                        <rect x="238" y="104" width="128" height="30" rx="14" fill="#e8c874" />
                                        <rect x="292" y="104" width="20" height="116" fill="#402143" fill-opacity="0.18" />
                                        <path d="M278 104c0-22 14-38 24-38s24 16 24 38" stroke="#402143" stroke-opacity="0.55" stroke-width="7" stroke-linecap="round" />
                                    </g>
                                    <g class="vb-float vb-float--slow">
                                        <rect x="150" y="168" width="84" height="72" rx="10" fill="#d8bfdc" />
                                        <rect x="150" y="168" width="84" height="18" rx="9" fill="#c9a9ce" />
                                        <rect x="184" y="168" width="14" height="72" fill="#805487" fill-opacity="0.45" />
                                    </g>
                                    <circle cx="376" cy="58" r="14" fill="#e8c874" />
                                    <circle cx="206" cy="82" r="8" fill="#fff" fill-opacity="0.35" />
                                    <rect x="120" y="116" width="14" height="14" rx="3" transform="rotate(18 127 123)" fill="#e8c874" fill-opacity="0.8" />
                                </svg>
                            </div>

                            <div class="relative flex min-h-[220px] flex-col justify-center px-6 py-7 sm:min-h-[270px] sm:px-12 sm:pr-[46%]">
                                <span class="vb-rise inline-flex h-6 w-fit items-center gap-1.5 rounded-full bg-[#f6d9b8] px-3 text-[11px] font-semibold text-[#402143]" style="--d: 0">
                                    {{ $pillLabel }}
                                    @if ($many)<span class="text-[#402143]/60">{{ sprintf('%02d', $i + 1) }} / {{ sprintf('%02d', $slides->count()) }}</span>@endif
                                </span>

                                <h2 class="vb-rise mt-3 line-clamp-2 max-w-[560px] pb-1 text-[clamp(26px,3.6vw,42px)] font-semibold leading-[1.1] text-white" style="--d: 1">{{ $slide->title }}</h2>

                                <p class="vb-rise mt-1.5 line-clamp-3 max-w-[460px] whitespace-pre-line text-[13px] leading-6 text-white/90 sm:text-[14px]" style="--d: 2">{{ $slide->message }}</p>

                                <div class="vb-rise mt-5 flex flex-wrap items-center gap-2.5" style="--d: 3">
                                    <a href="{{ route('buyer.products.index') }}" :tabindex="active === {{ $i }} ? 0 : -1"
                                        class="group inline-flex h-10 items-center gap-2 rounded-full bg-[#e8c874] pl-5 pr-4 text-[13px] font-semibold text-[#402143] transition-all duration-300 ease-vendo hover:bg-[#f0d68c] hover:shadow-[0_14px_26px_-14px_rgba(232,200,116,0.9)] active:scale-[0.97]">
                                        Shop now
                                        <svg class="h-4 w-4 transition-transform duration-300 ease-vendo group-hover:translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg>
                                    </a>
                                    <a href="{{ route('buyer.categories') }}" :tabindex="active === {{ $i }} ? 0 : -1"
                                        class="inline-flex h-10 items-center rounded-full border border-white/40 px-5 text-[13px] font-medium text-white transition-colors duration-300 ease-vendo hover:bg-white/10">Browse categories</a>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                @if ($many)
                    <!-- Arrows -->
                    <button type="button" @click="prev()" aria-label="Previous announcement"
                        class="absolute left-3 top-1/2 hidden h-10 w-10 -translate-y-1/2 place-items-center rounded-full bg-white/15 text-white backdrop-blur-[2px] transition-all duration-300 ease-vendo hover:bg-white/30 active:scale-90 sm:grid">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6" /></svg>
                    </button>
                    <button type="button" @click="next()" aria-label="Next announcement"
                        class="absolute right-3 top-1/2 hidden h-10 w-10 -translate-y-1/2 place-items-center rounded-full bg-white/15 text-white backdrop-blur-[2px] transition-all duration-300 ease-vendo hover:bg-white/30 active:scale-90 sm:grid">
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

            <!-- Quick links -->
            <div class="grid grid-cols-2 gap-3 lg:grid-cols-1">
                <a href="{{ route('buyer.orders.index') }}"
                    class="group flex flex-col justify-center rounded-xl border border-[#eee6ef] bg-white p-4 transition-all duration-300 ease-vendo hover:-translate-y-0.5 hover:border-[#cfb2d4] hover:shadow-[0_14px_26px_-16px_rgba(64,33,67,0.5)]">
                    <span class="grid h-10 w-10 place-items-center rounded-full bg-[#f3e8f5] text-[#52245b] transition-colors duration-300 group-hover:bg-[#805487] group-hover:text-white">
                        <span class="vb-icon h-5 w-5" style="--icon: url('{{ asset('assets/icons/buyer/my-orders-icon.svg') }}')"></span>
                    </span>
                    <p class="mt-3 text-[14px] font-semibold text-[#2b1730]">Track my orders</p>
                    <p class="mt-0.5 text-[12px] leading-5 text-[#7a6a7e]">See where your parcels are, and rate what arrived.</p>
                </a>

                <button type="button" x-data @click="$dispatch('open-favorites')"
                    class="group flex flex-col justify-center rounded-xl border border-[#eee6ef] bg-white p-4 text-left transition-all duration-300 ease-vendo hover:-translate-y-0.5 hover:border-[#cfb2d4] hover:shadow-[0_14px_26px_-16px_rgba(64,33,67,0.5)]">
                    <span class="grid h-10 w-10 place-items-center rounded-full bg-[#f3e8f5] text-[#52245b] transition-colors duration-300 group-hover:bg-[#805487] group-hover:text-white">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20.4s-7.6-4.6-9.2-9.5C1.7 7.5 3.8 4.6 6.9 4.6c1.9 0 3.6 1 5.1 3 1.5-2 3.2-3 5.1-3 3.1 0 5.2 2.9 4.1 6.3-1.6 4.9-9.2 9.5-9.2 9.5z" /></svg>
                    </span>
                    <p class="mt-3 flex items-center gap-2 text-[14px] font-semibold text-[#2b1730]">Saved items
                        <span x-show="$store.fav.items.length" x-cloak x-text="$store.fav.items.length" class="rounded-full bg-[#f3e8f5] px-1.5 text-[11px] font-medium leading-[18px] text-[#52245b]"></span>
                    </p>
                    <p class="mt-0.5 text-[12px] leading-5 text-[#7a6a7e]">Tap the heart on a product to find it again later.</p>
                </button>
            </div>
        </div>

        <!-- ===================== Benefits ===================== -->
        <ul class="vb-reveal mt-3 grid grid-cols-2 gap-px overflow-hidden rounded-xl border border-[#eee6ef] bg-[#eee6ef] lg:grid-cols-4" style="--i: 1">
            @foreach ([
                ['Cash on Delivery', 'Pay when your order arrives.', 'M3 7h18v10H3zM3 11h18M7 15h3'],
                ['Track every order', 'Live status from seller to doorstep.', 'M12 21s-6-5.2-6-10a6 6 0 1 1 12 0c0 4.8-6 10-6 10zM12 8.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5z'],
                ['Approved sellers', 'Shops are reviewed before they sell.', 'M12 3l7 3v5c0 5-3 8.5-7 10-4-1.5-7-5-7-10V6zM9 12l2 2 4-4'],
                ['Chat with sellers', 'Ask questions before you buy.', 'M4 5h16v11H9l-5 4zM8 9h8M8 12h5'],
            ] as [$benefitTitle, $benefitText, $benefitPath])
                <li class="flex items-center gap-3 bg-white px-4 py-3.5">
                    <span class="grid h-10 w-10 flex-shrink-0 place-items-center rounded-full bg-[#f3e8f5] text-[#52245b]">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $benefitPath }}" /></svg>
                    </span>
                    <span class="min-w-0">
                        <span class="block truncate text-[13px] font-semibold text-[#2b1730]">{{ $benefitTitle }}</span>
                        <span class="block text-[12px] leading-4 text-[#7a6a7e]">{{ $benefitText }}</span>
                    </span>
                </li>
            @endforeach
        </ul>

        <!-- ===================== Categories ===================== -->
        <section aria-labelledby="category-title" class="vb-reveal relative mt-4 rounded-xl border border-[#eee6ef] bg-white" style="--i: 2"
            x-data="{
                atStart: true,
                atEnd: false,
                update() {
                    const t = this.$refs.track;
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
                <a href="{{ route('buyer.categories') }}" class="group inline-flex items-center gap-1 text-[12px] font-medium text-[#805487] transition-colors duration-200 hover:text-[#402143]">
                    See all
                    <svg class="h-3.5 w-3.5 transition-transform duration-300 ease-vendo group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m9 6 6 6-6 6" /></svg>
                </a>
            </div>

            <div class="relative px-4 py-4">
                <button type="button" @click="scroll(-1)" x-show="!atStart" x-cloak
                    x-transition.opacity.duration.250ms
                    class="absolute -left-3 top-[44px] z-10 hidden h-8 w-8 place-items-center rounded-full border border-[#eee6ef] bg-white text-[#805487] shadow-[0_6px_14px_-6px_rgba(43,23,48,0.35)] transition-all duration-300 ease-vendo hover:scale-110 hover:text-[#402143] active:scale-95 sm:grid"
                    aria-label="Scroll categories left">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6" /></svg>
                </button>

                <div x-ref="track" @scroll.passive="update()"
                    class="vb-no-scrollbar flex snap-x gap-5 overflow-x-auto scroll-smooth">
                    @forelse ($categories as $category)
                        @php $slug = Str::slug(str_replace('&', 'and', $category->name)); @endphp
                        <a href="{{ route('buyer.products.index', ['category_id' => $category->id]) }}"
                            class="group flex w-[84px] flex-shrink-0 snap-start flex-col items-center">
                            <span
                                class="block h-[72px] w-[72px] overflow-hidden rounded-full bg-[#ece4ed] transition-all duration-500 ease-vendo group-hover:-translate-y-1 group-hover:shadow-[0_12px_20px_-12px_rgba(64,33,67,0.6)]">
                                <img src="{{ asset('images/buyer/categories/' . $slug . '.png') }}" alt="" loading="lazy"
                                    class="vb-img h-full w-full object-cover group-hover:scale-110"
                                    onload="this.classList.add('is-loaded')" onerror="this.remove()">
                            </span>
                            <span
                                class="mt-2 line-clamp-2 text-center text-[12px] leading-4 transition-colors duration-300 group-hover:text-[#805487]">{{ $category->name }}</span>
                        </a>
                    @empty
                        <p class="py-4 text-sm text-[#9a8a9d]">No categories yet.</p>
                    @endforelse
                </div>

                <button type="button" @click="scroll(1)" x-show="!atEnd" x-cloak
                    x-transition.opacity.duration.250ms
                    class="absolute -right-3 top-[44px] z-10 hidden h-8 w-8 place-items-center rounded-full border border-[#eee6ef] bg-white text-[#805487] shadow-[0_6px_14px_-6px_rgba(43,23,48,0.35)] transition-all duration-300 ease-vendo hover:scale-110 hover:text-[#402143] active:scale-95 sm:grid"
                    aria-label="Scroll categories right">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6" /></svg>
                </button>
            </div>
        </section>

        <!-- ===================== Saved for later (shows only when the buyer has saved items) ===================== -->
        <section x-data x-show="$store.fav.items.length" x-cloak aria-labelledby="saved-title"
            x-transition:enter="transition duration-500 ease-vendo" x-transition:enter-start="opacity-0 translate-y-3" x-transition:enter-end="opacity-100 translate-y-0"
            class="mt-4 rounded-xl border border-[#eee6ef] bg-white">
            <div class="flex items-center justify-between border-b border-[#f3ecf4] px-4 py-3">
                <h2 id="saved-title" class="text-[15px] font-semibold text-[#402143]">Saved for later</h2>
                <button type="button" @click="$dispatch('open-favorites')" class="text-[12px] font-medium text-[#805487] transition-colors duration-200 hover:text-[#402143]">See all</button>
            </div>
            <ul class="vb-no-scrollbar flex gap-3 overflow-x-auto px-4 py-3.5">
                <template x-for="item in $store.fav.items.slice(0, 10)" :key="item.id">
                    <li class="w-[148px] flex-shrink-0">
                        <a :href="$store.fav.href(item)" class="group block">
                            <span class="block aspect-square overflow-hidden rounded-lg border border-[#eee6ef] bg-[#faf7fb]">
                                <img :src="item.image" alt="" loading="lazy" class="h-full w-full object-cover transition-transform duration-500 ease-vendo group-hover:scale-105">
                            </span>
                            <span x-text="item.name" class="mt-1.5 line-clamp-1 block text-[12px] text-[#2b1730] transition-colors duration-200 group-hover:text-[#805487]"></span>
                            <span x-text="item.price" class="block text-[13px] font-semibold text-[#52245b]"></span>
                        </a>
                    </li>
                </template>
            </ul>
        </section>

        <!-- ===================== Recommended for You ===================== -->
        <section aria-labelledby="recommended-title" class="mt-5">
            <div class="vb-reveal mb-3 flex items-end justify-between gap-3">
                <div>
                    <h2 id="recommended-title" class="text-[18px] font-semibold text-[#402143]">Recommended for you</h2>
                    <p class="text-[12px] text-[#7a6a7e]">Fresh picks from approved shops.</p>
                </div>
                <a href="{{ route('buyer.products.index') }}" class="group inline-flex items-center gap-1 text-[12px] font-medium text-[#805487] transition-colors duration-200 hover:text-[#402143]">
                    View all products
                    <svg class="h-3.5 w-3.5 transition-transform duration-300 ease-vendo group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m9 6 6 6-6 6" /></svg>
                </a>
            </div>

            @if ($products->isEmpty())
                <div class="rounded-xl border border-[#eee6ef] bg-white">
                    <x-buyer.empty-state icon="box" title="No products to show yet" text="Products from approved sellers will appear here as soon as they are listed." />
                </div>
            @else
                <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-3 sm:gap-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
                    @foreach ($products as $product)
                        <div class="vb-reveal flex" style="--i: {{ $loop->index % 12 }}">
                            @include('buyer.partials.product-card', ['product' => $product, 'toProduct' => true])
                        </div>
                    @endforeach
                </div>

                <div class="vb-reveal mt-6 flex justify-center">
                    <a href="{{ route('buyer.products.index') }}"
                        class="inline-flex h-11 items-center rounded-full border border-[#805487] bg-white px-8 text-[13px] font-medium text-[#52245b] transition-all duration-300 ease-vendo hover:bg-[#805487] hover:text-white active:scale-[0.97]">See more products</a>
                </div>
            @endif
        </section>
    </div>

    @include('buyer.partials.quick-add')
    @include('shared.live-revision', ['endpoint' => route('buyer.live', 'catalog'), 'mode' => 'reload'])
</x-buyer.layout>