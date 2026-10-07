{{--
    Saved items (favorites), small toast, and back-to-top. Included once by components/buyer/layout.blade.php.

    Saved items are persisted to the signed-in Buyer account. Earlier browser-only items are imported once.
    The store: Alpine.store('fav') with items, has(id), toggle(product), remove(id), clear().
    A product is saved as { id, name, price, image, url, shop }.
--}}
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.store('ui', {
            msg: '',
            timer: null,
            say(message) {
                this.msg = message;
                clearTimeout(this.timer);
                this.timer = setTimeout(() => this.msg = '', 2400);
            },
        });

        Alpine.store('fav', {
            items: window.vendoFavStorage.initial,
            init() {
                const legacyIds = window.vendoFavStorage.legacy().map((item) => Number(item.id)).filter((id) => Number.isInteger(id) && id > 0).slice(0, 60);
                if (legacyIds.length) {
                    window.vendoFavStorage.import([...new Set(legacyIds)])
                        .then((data) => { this.items = data.items; window.vendoFavStorage.clearLegacy(); })
                        .catch(() => Alpine.store('ui').say('Could not sync saved items. Please reload.'));
                } else {
                    window.vendoFavStorage.clearLegacy();
                }
                window.addEventListener('focus', () => window.vendoFavStorage.refresh()
                    .then((data) => { this.items = data.items; }).catch(() => {}));
            },
            has(id) { return this.items.some((item) => item.id === id); },
            href(item) {
                const url = String(item.url || '');
                return url.startsWith('/') || url.startsWith(location.origin) ? url : '#';
            },
            async toggle(product) {
                if (this.has(product.id)) {
                    await this.remove(product.id);
                    return;
                }
                try {
                    this.items = (await window.vendoFavStorage.add(product.id)).items;
                    Alpine.store('ui').say('Saved for later');
                } catch (error) { Alpine.store('ui').say(error.message); }
            },
            async remove(id) {
                try {
                    this.items = (await window.vendoFavStorage.remove(id)).items;
                    Alpine.store('ui').say('Removed from saved items');
                } catch (error) { Alpine.store('ui').say(error.message); }
            },
            async clear() {
                try {
                    this.items = (await window.vendoFavStorage.clear()).items;
                    Alpine.store('ui').say('Saved items cleared');
                } catch (error) { Alpine.store('ui').say(error.message); }
            },
        });
    });
</script>

<!-- ===================== Saved items panel ===================== -->
<div x-data="{ open: false, confirmClear: false }"
    @open-favorites.window="open = true; confirmClear = false; $nextTick(() => $refs.close.focus())"
    @keydown.escape.window="open = false"
    x-effect="document.body.style.overflow = open ? 'hidden' : ''">

    <div x-show="open" x-cloak class="fixed inset-0 z-[80]" role="dialog" aria-modal="true" aria-labelledby="vb-fav-title">
        <div class="vb-fav-scrim absolute inset-0" @click="open = false"
            x-show="open"
            x-transition:enter="transition-opacity duration-300 ease-vendo" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity duration-200 ease-vendo" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>

        <aside x-show="open"
            x-transition:enter="transition duration-[400ms] ease-vendo" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
            x-transition:leave="transition duration-[250ms] ease-vendo" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
            class="absolute inset-y-0 right-0 flex w-full max-w-[400px] flex-col bg-white shadow-[-24px_0_48px_-24px_rgba(43,23,48,0.55)]">

            <header class="flex items-center justify-between border-b border-[#f1e8f2] px-5 py-4">
                <div>
                    <h2 id="vb-fav-title" class="text-[16px] font-semibold text-[#2b1730]">Saved items</h2>
                    <p class="text-[12px] text-[#8a7a8e]" x-text="$store.fav.items.length ? $store.fav.items.length + ($store.fav.items.length === 1 ? ' product' : ' products') + ' to look at later' : 'Nothing saved yet'"></p>
                </div>
                <button type="button" x-ref="close" @click="open = false" aria-label="Close saved items"
                    class="grid h-9 w-9 place-items-center rounded-full text-[#8a7a8e] transition-colors duration-200 hover:bg-[#f3e8f5] hover:text-[#402143]">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M6 6l12 12M18 6 6 18" /></svg>
                </button>
            </header>

            <div class="vb-thin-scroll min-h-0 flex-1 overflow-y-auto">
                <!-- Empty -->
                <div x-show="!$store.fav.items.length" class="flex h-full flex-col items-center justify-center px-8 py-12 text-center">
                    <svg class="h-24 w-24" viewBox="0 0 112 112" fill="none" aria-hidden="true">
                        <circle cx="56" cy="58" r="44" fill="#f3e8f5" />
                        <circle cx="92" cy="26" r="5" fill="#e8c874" />
                        <circle cx="19" cy="83" r="3.5" fill="#d8bfdc" />
                        <path d="M56 80s-20-12-24-25c-3-9 3-17 11-17 5 0 9 3 13 8 4-5 8-8 13-8 8 0 14 8 11 17-4 13-24 25-24 25z" fill="#fff" stroke="#52245b" stroke-width="3" stroke-linejoin="round" />
                    </svg>
                    <p class="mt-4 text-[14px] font-semibold text-[#2b1730]">No saved items yet</p>
                    <p class="mt-1 max-w-[260px] text-[13px] leading-5 text-[#7a6a7e]">Tap the heart on a product to keep it here and find it again later. Saving does not add it to your cart.</p>
                    <a href="{{ route('buyer.products.index') }}" class="mt-5 inline-flex h-10 items-center rounded-md bg-[#402143] px-5 text-[13px] font-medium text-white transition-colors duration-200 hover:bg-[#52245b]">Browse products</a>
                </div>

                <!-- List -->
                <ul x-show="$store.fav.items.length" class="divide-y divide-[#f3ecf4]">
                    <template x-for="item in $store.fav.items" :key="item.id">
                        <li class="flex gap-3 px-5 py-3.5">
                            <a :href="$store.fav.href(item)" class="h-[68px] w-[68px] flex-shrink-0 overflow-hidden rounded-md border border-[#eee6ef] bg-[#faf7fb]" tabindex="-1" aria-hidden="true">
                                <img :src="item.image" alt="" loading="lazy" class="h-full w-full object-cover">
                            </a>
                            <div class="min-w-0 flex-1">
                                <a :href="$store.fav.href(item)" x-text="item.name" class="line-clamp-2 text-[13px] leading-[18px] text-[#2b1730] transition-colors duration-200 hover:text-[#805487]"></a>
                                <p x-show="item.shop" x-text="item.shop" class="mt-0.5 truncate text-[11px] text-[#8a7a8e]"></p>
                                <div class="mt-1.5 flex items-center justify-between gap-2">
                                    <span x-text="item.price" class="text-[14px] font-semibold text-[#52245b]"></span>
                                    <span class="flex items-center gap-1.5">
                                        <a :href="$store.fav.href(item)" class="inline-flex h-7 items-center rounded-md border border-[#805487] px-3 text-[12px] font-medium text-[#52245b] transition-colors duration-200 hover:bg-[#f5ecf6]">View</a>
                                        <button type="button" @click="$store.fav.remove(item.id)" :aria-label="'Remove ' + item.name + ' from saved items'"
                                            class="grid h-7 w-7 place-items-center rounded-md text-[#8a7a8e] transition-colors duration-200 hover:bg-[#fdf1f3] hover:text-[#a32b43]">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 12a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1l1-12M9 7V4h6v3" /></svg>
                                        </button>
                                    </span>
                                </div>
                            </div>
                        </li>
                    </template>
                </ul>
            </div>

            <footer class="border-t border-[#f1e8f2] px-5 py-3">
                <p class="text-[11px] leading-4 text-[#9a8a9d]">Saved items follow your Vendo account. They are not added to your cart.</p>
                <div x-show="$store.fav.items.length" class="mt-2.5 flex items-center justify-between gap-3">
                    <a href="{{ route('buyer.account.index', ['tab' => 'saved']) }}" class="text-[12px] font-medium text-[#805487] transition-colors duration-200 hover:text-[#402143]">Manage in Settings</a>
                    <button type="button" @click="if (confirmClear) { $store.fav.clear(); confirmClear = false; } else { confirmClear = true; setTimeout(() => confirmClear = false, 3000); }"
                        :class="confirmClear ? 'bg-[#a32b43] text-white' : 'text-[#a32b43] hover:bg-[#fdf1f3]'"
                        class="inline-flex h-8 items-center rounded-md px-3 text-[12px] font-medium transition-colors duration-200"
                        x-text="confirmClear ? 'Click again to clear all' : 'Clear all'"></button>
                </div>
            </footer>
        </aside>
    </div>
</div>

<!-- Small toast (saved / removed) -->
<div x-data class="pointer-events-none fixed inset-x-0 bottom-20 z-[75] flex justify-center px-4" aria-live="polite">
    <div x-show="$store.ui.msg" x-cloak
        x-transition:enter="transition duration-300 ease-vendo" x-transition:enter-start="translate-y-3 opacity-0" x-transition:enter-end="translate-y-0 opacity-100"
        x-transition:leave="transition duration-200 ease-vendo" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="rounded-full bg-[#2b1730] px-4 py-2 text-[13px] font-medium text-white shadow-[0_14px_30px_-12px_rgba(43,23,48,0.8)]"
        x-text="$store.ui.msg"></div>
</div>

<!-- Back to top -->
<div x-data="{ show: false }" @scroll.window.throttle.100ms="show = window.scrollY > 700">
    <button type="button" x-show="show" x-cloak @click="window.scrollTo({ top: 0, behavior: 'smooth' })" aria-label="Back to top"
        x-transition:enter="transition duration-300 ease-vendo" x-transition:enter-start="translate-y-3 opacity-0" x-transition:enter-end="translate-y-0 opacity-100"
        x-transition:leave="transition duration-200 ease-vendo" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="vb-top fixed bottom-5 right-4 z-40 grid h-10 w-10 place-items-center rounded-full bg-[#402143] text-white transition-colors duration-200 hover:bg-[#52245b] sm:right-6">
        <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19V5M5 12l7-7 7 7" /></svg>
    </button>
</div>
