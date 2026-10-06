@php
    use Illuminate\Support\Str;

    // ---- Data the view can use today, with graceful fallbacks ----------------
    // $announcements : collection of published admin announcements (controller needs to pass it;
    //                  a single $announcement is also accepted). Empty -> welcome banner.
    $slides = collect($announcements ?? [])->values();
    if ($slides->isEmpty() && ! empty($announcement)) {
        $slides = collect([$announcement]);
    }
    $usingFallback = $slides->isEmpty();
    if ($usingFallback) {
        $slides = collect([(object) [
            'title' => 'Welcome to Vendo',
            'message' => 'Shop your favorites and get them delivered to your door.',
        ]]);
    }
    $pillLabel = $usingFallback ? 'Welcome' : 'Announcement';
@endphp

<x-buyer.layout title="Dashboard — Vendo">
    <div class="vb-enter mx-auto max-w-[1200px] px-3 pb-14 pt-4 sm:px-4">

        <!-- Announcement banner (admin announcements) -->
        <section aria-label="Announcements"
            class="vb-banner relative overflow-hidden rounded-xl"
            style="--banner-art: url('{{ asset('images/buyer/announcement-banner.png') }}')"
            x-data="{
                active: 0,
                total: {{ $slides->count() }},
                paused: false,
                init() {
                    if (this.total > 1) {
                        setInterval(() => { if (!this.paused) this.active = (this.active + 1) % this.total }, 6500);
                    }
                },
            }"
            @mouseenter="paused = true" @mouseleave="paused = false"
            @focusin="paused = true" @focusout="paused = false">

            <div class="vb-slides">
                @foreach ($slides as $i => $slide)
                    <article
                        class="vb-slide flex min-h-[190px] flex-col justify-center px-5 py-5 sm:min-h-[240px] sm:px-10 {{ $i === 0 ? 'is-active' : '' }}"
                        :class="{ 'is-active': active === {{ $i }} }"
                        :aria-hidden="active !== {{ $i }} ? 'true' : 'false'">

                        <span
                            class="inline-flex h-6 w-fit items-center gap-1.5 rounded-full bg-[#f6d9b8] px-3 text-[11px] font-semibold text-[#402143]">
                            {{ $pillLabel }}
                            <svg class="h-3 w-3" viewBox="0 0 24 24" fill="#fff" aria-hidden="true"><path d="M12 2l2.2 7.8L22 12l-7.8 2.2L12 22l-2.2-7.8L2 12l7.8-2.2z" /></svg>
                        </span>

                        <h2
                            class="mt-2 line-clamp-2 w-fit max-w-[520px] pb-1 text-[clamp(24px,3.4vw,38px)] font-semibold leading-[1.1] text-white">
                            {{ $slide->title }}
                        </h2>

                        <p class="mt-1 line-clamp-3 max-w-[380px] whitespace-pre-line text-[13px] leading-5 text-white/95 sm:text-[14px]">{{ $slide->message }}</p>

                        <a href="{{ route('buyer.products.index') }}"
                            class="group mt-4 inline-flex h-9 w-fit items-center gap-2 rounded-full bg-[#382059] pl-5 pr-4 text-[13px] font-medium text-white transition-all duration-300 ease-vendo hover:bg-[#2c1948] hover:shadow-[0_12px_24px_-12px_rgba(56,32,89,0.9)] active:scale-[0.97]"
                            :tabindex="active === {{ $i }} ? 0 : -1">
                            Shop Now
                            <svg class="h-4 w-4 transition-transform duration-300 ease-vendo group-hover:translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg>
                        </a>
                    </article>
                @endforeach
            </div>

            @if ($slides->count() > 1)
                <div class="absolute bottom-3 right-4 flex items-center gap-1.5">
                    @foreach ($slides as $i => $slide)
                        <button type="button" @click="active = {{ $i }}"
                            :class="active === {{ $i }} ? 'w-5 bg-white' : 'w-1.5 bg-white/50 hover:bg-white/80'"
                            class="h-1.5 rounded-full transition-all duration-500 ease-vendo"
                            aria-label="Show announcement {{ $i + 1 }}"></button>
                    @endforeach
                </div>
            @endif
        </section>

        <!-- Categories -->
        <section aria-labelledby="category-title" class="relative mt-4 rounded-xl border border-[#eee6ef] bg-white"
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
                <h2 id="category-title" class="text-[15px] font-semibold text-[#402143]">Categories</h2>
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

        <!-- Recommended for You -->
        <section aria-labelledby="recommended-title" class="mt-5">
            <div class="rounded-t-xl border border-b-0 border-[#eee6ef] bg-white px-4 pt-3">
                <h2 id="recommended-title"
                    class="inline-block border-b-[3px] border-[#805487] pb-2 text-[14px] font-semibold uppercase tracking-wide text-[#52245b]">Recommended for You</h2>
            </div>
            <div class="h-px bg-[#eee6ef]"></div>

            @if ($products->isEmpty())
                <div class="rounded-b-xl border border-t-0 border-[#eee6ef] bg-white">
                    <x-buyer.empty-state icon="box" title="No products to show yet" text="Products from approved sellers will appear here as soon as they are listed." />
                </div>
            @else
                <div class="mt-3 grid grid-cols-2 gap-2.5 sm:grid-cols-3 sm:gap-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
                    @foreach ($products as $product)
                        @include('buyer.partials.product-card', ['product' => $product, 'toProduct' => true])
                    @endforeach
                </div>
            @endif
        </section>
    </div>

    @include('buyer.partials.quick-add')
    @include('shared.live-revision', ['endpoint' => route('buyer.live', 'catalog'), 'mode' => 'reload'])
</x-buyer.layout>