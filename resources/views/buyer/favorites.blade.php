{{--
    Buyer favorites page.
    Data from PersonalizationController@favorites: $products (saved products, newest saved first).
    Cards are the shared product card, so Add to cart, Choose options and the heart behave as on every other page.
    Search, sort and removing a favorite run in the browser against the Alpine 'fav' store (see partials/favorites-panel.blade.php).
    Removing shows an Undo banner; clearing everything asks first, then also offers Undo.
--}}
@php
    use Illuminate\Support\Str;

    $meta = $products->values()->map(fn ($product, $rank) => [
        'id' => $product->id,
        'name' => Str::lower($product->name),
        'shop' => Str::lower((string) ($product->seller?->sellerDetail?->business_name ?: $product->seller?->name)),
        'price' => (float) $product->price,
        'rank' => $rank,
    ])->all();
@endphp

<x-buyer.layout title="Favorites — Vendo">
    <div class="mx-auto max-w-[1200px] px-3 pb-8 pt-4 sm:px-4"
        x-data="{
            meta: @js($meta),
            q: '',
            sort: 'recent',
            confirm: false,
            clearing: false,
            get saved() { return this.meta.filter((m) => Alpine.store('fav').has(m.id)); },
            get count() { return Alpine.store('fav').items.length; },
            get shown() {
                const q = this.q.trim().toLowerCase();
                return this.saved.filter((m) => !q || m.name.includes(q) || m.shop.includes(q)).map((m) => m.id);
            },
            get position() {
                const by = {
                    recent: (a, b) => a.rank - b.rank,
                    low: (a, b) => a.price - b.price || a.rank - b.rank,
                    high: (a, b) => b.price - a.price || a.rank - b.rank,
                    name: (a, b) => a.name.localeCompare(b.name),
                }[this.sort];
                return Object.fromEntries([...this.meta].sort(by).map((m, i) => [m.id, i]));
            },
            async clearAll() {
                this.clearing = true;
                await Alpine.store('fav').clear();
                this.clearing = false;
                this.confirm = false;
                this.q = '';
            },
        }"
        @keydown.escape="confirm = false">

        <!-- ===================== Header ===================== -->
        <div class="vb-reveal flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-[20px] font-semibold leading-7 text-[#402143]">Favorites</h1>
                <p class="text-[12px] text-[#7a6a7e]"><span x-text="count">{{ $products->count() }}</span> of 60 saved</p>
            </div>

            <div x-show="count" x-cloak class="flex w-full flex-wrap items-center gap-2 sm:w-auto">
                <label class="relative min-w-0 flex-1 sm:w-[240px] sm:flex-none">
                    <span class="sr-only">Search favorites</span>
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[#9a8a9d]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7" /><path d="m20 20-3.5-3.5" /></svg>
                    <input type="search" x-model.debounce.150ms="q" placeholder="Search favorites" autocomplete="off"
                        class="vb-field h-10 w-full pl-9 pr-3">
                </label>

                <label class="flex-shrink-0">
                    <span class="sr-only">Sort by</span>
                    <select x-model="sort" class="vb-field h-10 cursor-pointer pl-3 pr-8">
                        <option value="recent">Recently added</option>
                        <option value="low">Price: low to high</option>
                        <option value="high">Price: high to low</option>
                        <option value="name">Name: A to Z</option>
                    </select>
                </label>

                <button type="button" @click="confirm = true" x-show="!confirm" class="vb-btn vb-btn--danger-soft">
                    <svg class="vb-ico vb-ico--trash h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 12a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1l1-12M9 7V4h6v3" /></svg>
                    Clear all
                </button>
            </div>
        </div>

        <!-- Confirm clear -->
        <div x-show="confirm && count" x-cloak
            x-transition:enter="transition duration-300 ease-vendo" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
            class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-[#f0c9d0] bg-[#fdf1f3] px-4 py-3" role="alertdialog" aria-labelledby="fav-confirm">
            <p id="fav-confirm" class="text-[13px] font-medium text-[#8f2438]">Remove all <span x-text="count"></span> favorites?</p>
            <div class="flex items-center gap-2">
                <button type="button" @click="confirm = false" class="vb-btn vb-btn--outline vb-btn--sm">Keep favorites</button>
                <button type="button" @click="clearAll()" :disabled="clearing" :class="clearing && 'vb-loading'" class="vb-btn vb-btn--danger vb-btn--sm">Remove all</button>
            </div>
        </div>

        <!-- ===================== Empty: nothing saved ===================== -->
        <div x-show="count === 0" x-cloak class="vb-reveal mt-4 rounded-xl border border-[#eee6ef] bg-white">
            <x-buyer.empty-state icon="heart" title="No favorites yet" text="Tap the heart on a product to save it here.">
                <a href="{{ route('buyer.products.index') }}" class="vb-btn vb-btn--primary">Browse products</a>
            </x-buyer.empty-state>
        </div>

        <!-- ===================== Empty: search finds nothing ===================== -->
        <div x-show="count > 0 && shown.length === 0" x-cloak class="mt-4 rounded-xl border border-[#eee6ef] bg-white">
            <x-buyer.empty-state icon="search" title="No matches" text="Try a shorter word or check the spelling.">
                <button type="button" @click="q = ''" class="vb-btn vb-btn--outline">Clear search</button>
            </x-buyer.empty-state>
        </div>

        <!-- ===================== Grid ===================== -->
        <div x-show="count > 0" class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
            @foreach ($products as $product)
                <div class="flex"
                    x-show="shown.includes({{ $product->id }})"
                    :style="{ order: position[{{ $product->id }}] }"
                    x-transition:leave="transition duration-200 ease-vendo" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
                    @include('buyer.partials.product-card', ['product' => $product])
                </div>
            @endforeach
        </div>
    </div>

    @include('buyer.partials.quick-add')
    @include('shared.live-revision', ['endpoint' => route('buyer.live', 'catalog'), 'mode' => 'reload'])
</x-buyer.layout>
