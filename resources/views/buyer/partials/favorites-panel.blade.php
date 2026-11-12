{{--
    Favorites store and back-to-top button. Included once by components/buyer/layout.blade.php.
    The old slide-over panel is gone: favorites live on their own page (route buyer.favorites).

    Favorites are saved to the signed-in Buyer account. Earlier browser-only items are imported once.
    The store: Alpine.store('fav') with items, busy, has(id), toggle(product), remove(id), clear(), restore(ids).
    A product is saved as { id, name, price, image, url, shop }.
    Banner messages come from partials/banner.blade.php.
--}}
<script>
    document.addEventListener('alpine:init', () => {
        const banner = () => Alpine.store('banner');

        Alpine.store('fav', {
            items: window.vendoFavStorage.initial,
            busy: {},
            init() {
                const legacyIds = window.vendoFavStorage.legacy().map((item) => Number(item.id)).filter((id) => Number.isInteger(id) && id > 0).slice(0, 60);
                if (legacyIds.length) {
                    window.vendoFavStorage.import([...new Set(legacyIds)])
                        .then((data) => { this.items = data.items; window.vendoFavStorage.clearLegacy(); })
                        .catch(() => banner().show({
                            type: 'error', title: "Favorites didn't sync", text: 'Reload the page to try again.',
                            action: { label: 'Reload page', onClick: () => location.reload() },
                        }));
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
            lock(id, on) {
                const next = { ...this.busy };
                if (on) next[id] = true; else delete next[id];
                this.busy = next;
            },
            fail(error, retry) {
                banner().show({
                    type: 'error', title: "Couldn't update favorites", text: error.message,
                    action: retry ? { label: 'Try again', onClick: retry } : null,
                });
            },
            async toggle(product) {
                if (this.busy[product.id]) return;
                if (this.has(product.id)) { await this.remove(product.id); return; }
                this.lock(product.id, true);
                try {
                    this.items = (await window.vendoFavStorage.add(product.id)).items;
                    banner().show({ type: 'success', title: 'Added to favorites', action: { label: 'View favorites', href: @js(route('buyer.favorites')) }, duration: 4000 });
                } catch (error) { this.fail(error, () => this.toggle(product)); }
                finally { this.lock(product.id, false); }
            },
            async remove(id) {
                if (this.busy[id]) return;
                this.lock(id, true);
                try {
                    this.items = (await window.vendoFavStorage.remove(id)).items;
                    banner().show({ type: 'info', title: 'Removed from favorites', action: { label: 'Undo', onClick: () => this.restore([id]) }, duration: 6000 });
                } catch (error) { this.fail(error, () => this.remove(id)); }
                finally { this.lock(id, false); }
            },
            async restore(ids) {
                try {
                    // One item: the plain add call. Several (after Clear all): the bulk import call.
                    this.items = (ids.length === 1 ? await window.vendoFavStorage.add(ids[0]) : await window.vendoFavStorage.import(ids)).items;
                    banner().show({ type: 'success', title: ids.length > 1 ? 'Favorites restored' : 'Favorite restored', duration: 3000 });
                } catch (error) { this.fail(error, () => this.restore(ids)); }
            },
            async clear() {
                const ids = this.items.map((item) => item.id);
                try {
                    this.items = (await window.vendoFavStorage.clear()).items;
                    banner().show({ type: 'info', title: 'Favorites cleared', action: { label: 'Undo', onClick: () => this.restore(ids) }, duration: 8000 });
                } catch (error) { this.fail(error, () => this.clear()); }
            },
        });
    });
</script>

{{-- Older links that still dispatch open-favorites go to the Favorites page --}}
<div x-data @open-favorites.window="location.href = @js(route('buyer.favorites'))"></div>

<!-- Back to top -->
<div x-data="{ show: false }" @scroll.window.throttle.100ms="show = window.scrollY > 700">
    <button type="button" x-show="show" x-cloak @click="window.scrollTo({ top: 0, behavior: 'smooth' })" aria-label="Back to top"
        x-transition:enter="transition duration-300 ease-vendo" x-transition:enter-start="translate-y-3 opacity-0" x-transition:enter-end="translate-y-0 opacity-100"
        x-transition:leave="transition duration-200 ease-vendo" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="vb-top vb-act fixed bottom-5 right-4 z-40 grid h-10 w-10 place-items-center rounded-full bg-[#402143] text-white transition-colors duration-200 hover:bg-[#52245b] sm:right-6">
        <svg class="vb-ico vb-ico--up h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19V5M5 12l7-7 7 7" /></svg>
    </button>
</div>
