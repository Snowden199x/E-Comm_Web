@props(['title' => 'Vendo — Buyer', 'footer' => true])

@php
    use Illuminate\Support\Str;

    $recentBuyerNotifications = \App\Models\Communication\Notification::query()->where('user_id', auth()->id())->latest()->limit(5)->get();
    $buyerUnreadNotifications = \App\Models\Communication\Notification::query()->where('user_id', auth()->id())->whereNull('read_at')->count();
    $buyerUser = Auth::user();
    $buyerPersonalization = app(\App\Services\BuyerPersonalizationService::class);
    $buyerSavedItems = $buyerPersonalization->savedItemsFor((int) auth()->id());
    $buyerStoredPrefs = $buyerPersonalization->preferencesFor((int) auth()->id());

    // Category menu data (top-level categories + their subcategories)
    $menuCategories = \App\Models\Category::query()
        ->whereNull('parent_id')
        ->with(['children' => fn ($q) => $q->orderBy('id')])
        ->orderBy('id')
        ->get();
    $slugify = fn ($name) => Str::slug(str_replace('&', 'and', $name));
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('shared.site-icon')
    <title>{{ $title }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Account preferences and saved items, with one-time import of previous browser-only data. --}}
    <script>
    (() => {
        const uid = @json((string) auth()->id());
        const root = document.documentElement;
        const prefKey = 'vendo.prefs.' + uid;
        const favKey = 'vendo.favorites.' + uid;
        const read = (key, fallback) => { try { return JSON.parse(localStorage.getItem(key)) ?? fallback; } catch (_) { return fallback; } };
        const defaults = { text: 'default', calm: false, sound: true };
        const storedPrefs = @js($buyerStoredPrefs);
        const initialItems = @js($buyerSavedItems);
        const urls = {
            saved: @js(route('buyer.saved-items.index')),
            import: @js(route('buyer.saved-items.import')),
            item: @js(route('buyer.saved-items.store', ['product' => '__ID__'])),
            clear: @js(route('buyer.saved-items.clear')),
            settings: @js(route('buyer.settings.update')),
        };
        const request = async (url, method, data) => {
            let response;
            try {
                response = await fetch(url, {
                    method,
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: data === undefined ? undefined : JSON.stringify(data),
                });
            } catch (_) {
                throw new Error('No connection. Check your network and try again.');
            }
            if (response.status === 419) throw new Error('Your session expired. Reload the page and try again.');
            if (!response.ok) throw new Error((await response.json().catch(() => ({}))).message || 'Could not save. Try again in a moment.');
            return response.json();
        };
        const legacyPrefs = read(prefKey, {});
        let prefs = storedPrefs ?? {
            text: ['default', 'lg', 'xl'].includes(legacyPrefs?.text) ? legacyPrefs.text : 'default',
            calm: legacyPrefs?.calm === true,
            sound: legacyPrefs?.sound !== false,
        };
        let saving = Promise.resolve();

        const applyPrefs = (p) => {
            root.classList.toggle('vb-calm', p.calm === true);
            root.classList.toggle('vb-text-lg', p.text === 'lg');
            root.classList.toggle('vb-text-xl', p.text === 'xl');
        };
        root.classList.add('vb-js');
        applyPrefs(prefs);

        try {
            if (storedPrefs === null && localStorage.getItem(prefKey) !== null) {
                saving = request(urls.settings, 'PUT', prefs)
                    .then(() => localStorage.removeItem(prefKey)).catch(() => {});
            }
        } catch (_) {}

        window.vendoPrefs = {
            defaults,
            get: () => ({ ...prefs }),
            async set(key, value) {
                saving = saving.catch(() => {}).then(async () => {
                    prefs = await request(urls.settings, 'PUT', { ...prefs, [key]: value });
                    applyPrefs(prefs);
                    return this.get();
                });
                return saving;
            },
            async reset() {
                saving = saving.catch(() => {}).then(async () => {
                    prefs = await request(urls.settings, 'DELETE');
                    try { localStorage.removeItem(prefKey); } catch (_) {}
                    applyPrefs(prefs);
                    return this.get();
                });
                return saving;
            },
        };
        window.vendoFavStorage = {
            key: favKey,
            initial: initialItems,
            legacy: () => { const list = read(favKey, []); return Array.isArray(list) ? list : []; },
            clearLegacy: () => { try { localStorage.removeItem(favKey); } catch (_) {} },
            refresh: () => request(urls.saved, 'GET'),
            import: (ids) => request(urls.import, 'POST', { ids }),
            add: (id) => request(urls.item.replace('__ID__', encodeURIComponent(id)), 'POST'),
            remove: (id) => request(urls.item.replace('__ID__', encodeURIComponent(id)), 'DELETE'),
            clear: () => request(urls.clear, 'DELETE'),
        };
    })();
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/shared/app.css', 'resources/css/buyer/layout.css', 'resources/js/shared/app.js'])
    <link rel="stylesheet" href="{{ asset('assets/css/notification-actions.css') }}">
    @if(request()->routeIs('buyer.cart.index'))<link rel="stylesheet" href="{{ asset('assets/css/cart.css') }}">@endif
</head>

<body class="vb-body flex min-h-screen flex-col" x-data>

    <div id="vb-progress" class="vb-progress" aria-hidden="true"></div>
    <script>
    // Coming back with the browser's Back button restores the page from memory: reset the progress bar.
    window.addEventListener('pageshow', (e) => { if (e.persisted) document.getElementById('vb-progress')?.classList.remove('is-running'); });
    </script>

    <!-- ===================== Header ===================== -->
    <header class="sticky top-0 z-40 text-white"
        x-data="{
            open: false,
            active: 0,
            timer: null,
            show(i) {
                clearTimeout(this.timer);
                this.active = i;
                this.timer = setTimeout(() => { this.open = true; this.$dispatch('vb-menu', { id: 'mega' }); }, this.open ? 0 : 90);
            },
            keep() { clearTimeout(this.timer); },
            hide() { clearTimeout(this.timer); this.timer = setTimeout(() => this.open = false, 140); },
        }"
        @mouseleave="hide()" @keydown.escape.window="open = false"
        @vb-menu.window="if ($event.detail.id !== 'mega') { clearTimeout(timer); open = false }">

        <!-- Dim the page while the category menu is open -->
        <div x-show="open" x-cloak @mouseenter="hide()" @click="open = false"
            x-transition:enter="transition-opacity duration-300 ease-vendo" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity duration-200 ease-vendo"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="fixed inset-0 -z-10 bg-[#1b0b1e]/45"></div>

        <div class="vb-header">
            <!-- Top row. Moving up from the categories closes the category menu so the account menu is never covered. -->
            <div @mouseenter="hide()"
                class="mx-auto flex max-w-[1200px] flex-wrap items-center gap-x-3 gap-y-2 px-3 py-2.5 sm:h-16 sm:flex-nowrap sm:gap-x-5 sm:px-4 sm:py-0">

                <a href="{{ route('buyer.dashboard') }}" class="flex-shrink-0" aria-label="Vendo home">
                    <img src="{{ asset('assets/branding/log-in-logo.svg') }}" alt="Vendo" class="h-9 w-auto sm:h-10">
                </a>

                <!-- Search products or approved shops -->
                @php
                    $shopSearchUrl = \Illuminate\Support\Facades\Route::has('buyer.sellers.index') ? route('buyer.sellers.index') : null;
                    $searchScope = $shopSearchUrl && request()->routeIs('buyer.sellers.index') ? 'shops' : 'products';
                @endphp
                <form action="{{ route('buyer.products.index') }}" method="GET" role="search"
                    @if ($shopSearchUrl) x-data="{ scope: @js($searchScope) }" :action="scope === 'shops' ? @js($shopSearchUrl) : @js(route('buyer.products.index'))" @endif
                    class="order-last flex h-10 w-full min-w-0 items-center rounded-lg bg-white p-[3px] transition-shadow duration-300 ease-vendo focus-within:shadow-[0_0_0_3px_rgba(232,200,116,0.55)] sm:order-none sm:mx-auto sm:max-w-[640px] sm:flex-1">
                    @if ($shopSearchUrl)
                        <label for="vb-search-scope" class="sr-only">Search in</label>
                        <select id="vb-search-scope" x-model="scope"
                            class="h-full w-[96px] flex-shrink-0 cursor-pointer rounded-none border-0 border-r border-[#eee6ef] bg-transparent py-0 pl-3 pr-7 text-[12px] font-medium text-[#52245b] focus:outline-none focus:ring-0 sm:w-[108px]">
                            <option value="products">Products</option>
                            <option value="shops">Shops</option>
                        </select>
                    @endif
                    <input type="search" name="search" value="{{ request('search') }}"
                        @if ($shopSearchUrl) :placeholder="scope === 'shops' ? 'Search shops' : 'Search products'" @endif
                        placeholder="Search products" autocomplete="off"
                        class="h-full min-w-0 flex-1 border-0 bg-transparent px-3 text-[13px] text-[#2b1730] placeholder:text-[#9a8a9d] focus:outline-none focus:ring-0">
                    <button type="submit" aria-label="Search"
                        class="vb-act grid h-full w-11 flex-shrink-0 place-items-center rounded-md bg-transparent text-[#402143] transition-colors duration-200 ease-vendo hover:bg-[#f3e8f5] active:scale-95">
                        <span class="vb-icon vb-ico vb-ico--search h-[18px] w-[18px]"
                            style="--icon: url('{{ asset('assets/icons/buyer/search-icon.svg') }}')"></span>
                    </button>
                </form>

                <div class="ml-auto flex flex-shrink-0 items-center gap-0.5 sm:ml-0 sm:gap-1">

                    <!-- Favorites -->
                    <a href="{{ route('buyer.favorites') }}" aria-label="Favorites" @if(request()->routeIs('buyer.favorites')) aria-current="page" @endif
                        x-data="{ bump: false }"
                        x-init="$watch('$store.fav.items.length', (n, o) => { if (n > o) { bump = true; setTimeout(() => bump = false, 700) } })"
                        :class="{ 'is-bump': bump }"
                        class="vb-act relative hidden h-10 w-10 place-items-center rounded-full text-white/90 transition-colors duration-200 hover:bg-white/10 hover:text-white aria-[current=page]:bg-white/10 min-[400px]:grid">
                        <svg class="vb-ico vb-ico--heart h-[22px] w-[22px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20.4s-7.6-4.6-9.2-9.5C1.7 7.5 3.8 4.6 6.9 4.6c1.9 0 3.6 1 5.1 3 1.5-2 3.2-3 5.1-3 3.1 0 5.2 2.9 4.1 6.3-1.6 4.9-9.2 9.5-9.2 9.5z" /></svg>
                        <span x-show="$store.fav.items.length" x-cloak x-text="$store.fav.items.length > 99 ? '99+' : $store.fav.items.length"
                            class="vb-pop absolute right-0 top-0 grid h-[18px] min-w-[18px] place-items-center rounded-full bg-[#e8c874] px-1 text-[10px] font-semibold leading-none text-[#402143]"></span>
                    </a>

                    <!-- Messages -->
                    <a href="{{ route('buyer.messages.index') }}" aria-label="Messages" @if(request()->routeIs('buyer.messages.*')) aria-current="page" @endif
                        class="vb-act grid h-10 w-10 place-items-center rounded-full text-white/90 transition-colors duration-200 hover:bg-white/10 hover:text-white aria-[current=page]:bg-white/10">
                        <span class="vb-icon vb-ico vb-ico--msg h-[22px] w-[22px]"
                            style="--icon: url('{{ asset('assets/icons/buyer/messages-icon.svg') }}')"></span>
                    </a>

                    <!-- Cart. #cart-badge is display:contents so it is not a second grid row (that pushed the icon up). -->
                    <a href="{{ route('buyer.cart.index') }}" id="vb-cart-link" aria-label="Cart" @if(request()->routeIs('buyer.cart.*')) aria-current="page" @endif
                        class="vb-act relative grid h-10 w-10 place-items-center rounded-full text-white/90 transition-colors duration-200 hover:bg-white/10 hover:text-white aria-[current=page]:bg-white/10">
                        <span class="vb-icon vb-ico vb-ico--cart h-[22px] w-[22px]"
                            style="--icon: url('{{ asset('assets/icons/buyer/cart-icon.svg') }}')"></span>
                        <span id="cart-badge" class="contents">
                            @if ($cartCount > 0)
                                <span
                                    class="vb-pop absolute right-0 top-0 grid h-[18px] min-w-[18px] place-items-center rounded-full bg-[#e8c874] px-1 text-[10px] font-semibold leading-none text-[#402143]">{{ \App\Models\Ecommerce\CartItem::displayCount($cartCount) }}</span>
                            @endif
                        </span>
                    </a>

                    <!-- Notifications -->
                    <div class="relative" id="buyerBellWrap">
                        <button type="button" id="buyerBellBtn" aria-label="Notifications" aria-controls="buyerBellMenu"
                            aria-expanded="false"
                            class="vb-act relative grid h-10 w-10 place-items-center rounded-full text-white/90 transition-colors duration-200 hover:bg-white/10 hover:text-white">
                            <span class="vb-icon vb-ico vb-ico--bell h-[22px] w-[22px]"
                                style="--icon: url('{{ asset('assets/icons/buyer/notifications-icon.svg') }}')"></span>
                            <span id="buyerBellCount"
                                class="absolute right-0 top-0 h-[18px] min-w-[18px] rounded-full bg-[#e8c874] px-1 text-center text-[10px] font-semibold leading-[18px] text-[#402143]"
                                @if(!$buyerUnreadNotifications) hidden @endif>{{ $buyerUnreadNotifications > 99 ? '99+' : $buyerUnreadNotifications }}</span>
                        </button>
                        <div id="buyerBellMenu"
                            class="vb-menu absolute right-0 z-50 mt-2 max-h-[70vh] w-80 max-w-[calc(100vw-24px)] overflow-y-auto rounded-xl border border-gray-100 bg-white text-gray-900 shadow-xl sm:w-[350px]"
                            hidden>
                            <div class="flex items-center justify-between border-b border-[#f1e8f2] px-4 py-3"><strong class="text-[14px] text-[#2b1730]">Notifications</strong><div class="notification-menu-actions"><form method="POST" action="{{ route('buyer.notifications.read-all') }}">@csrf<button type="submit">Mark all as read</button></form><a href="{{ route('buyer.notifications.index') }}">View all</a></div></div>
                            <div id="buyerBellList">@include('buyer.notifications.recent', ['notifications' => $recentBuyerNotifications])</div>
                        </div>
                    </div>

                    <!-- Account menu: opens on hover (desktop) or tap, and sits above the category menu -->
                    <div class="relative ml-1"
                        x-data="{
                            o: false, t: null,
                            hover: window.matchMedia('(hover: hover) and (min-width: 768px)').matches,
                            openMenu() { clearTimeout(this.t); if (!this.o) { this.o = true; this.$dispatch('vb-menu', { id: 'account' }); } },
                            closeMenu() { clearTimeout(this.t); this.t = setTimeout(() => this.o = false, 160); },
                        }"
                        @mouseenter="if (hover) openMenu()" @mouseleave="if (hover) closeMenu()"
                        @vb-menu.window="if ($event.detail.id !== 'account') o = false"
                        @click.outside="o = false" @keydown.escape="o = false">
                        <button type="button" @click="o ? o = false : openMenu()" :aria-expanded="o" aria-haspopup="menu"
                            class="flex items-center gap-2 rounded-full py-1 pl-1 pr-1 transition-colors duration-200 hover:bg-white/10 md:pr-3"
                            aria-label="Account menu">
                            @if($buyerUser->profile_picture)
                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($buyerUser->profile_picture) }}"
                                    alt="" class="h-8 w-8 rounded-full bg-white object-cover ring-2 ring-white/30">
                            @else
                                <img src="{{ asset('assets/icons/dashboard/user-icon.svg') }}" alt=""
                                    class="h-8 w-8 rounded-full bg-white p-0.5 ring-2 ring-white/30">
                            @endif
                            <span class="hidden max-w-[120px] truncate text-[13px] font-medium md:block">{{ $buyerUser->name }}</span>
                            <svg class="hidden h-3.5 w-3.5 text-white/70 transition-transform duration-300 ease-vendo md:block"
                                :class="o ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6" /></svg>
                        </button>

                        {{-- pt-2 (not mt-2) keeps the hover path from the button to the menu unbroken --}}
                        <div x-show="o" x-cloak role="menu"
                            x-transition:enter="transition duration-200 ease-vendo" x-transition:enter-start="opacity-0 -translate-y-1 scale-95"
                            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                            x-transition:leave="transition duration-150 ease-vendo" x-transition:leave-start="opacity-100 scale-100"
                            x-transition:leave-end="opacity-0 scale-95"
                            class="absolute right-0 top-full z-50 w-60 origin-top-right pt-2">
                            <div class="overflow-hidden rounded-xl border border-[#eee6ef] bg-white py-1.5 text-[13px] text-[#2b1730] shadow-[0_18px_40px_-16px_rgba(43,23,48,0.5)]">
                                <div class="border-b border-[#f1e8f2] px-4 pb-2.5 pt-1.5">
                                    <p class="truncate font-semibold">{{ $buyerUser->name }}</p>
                                    <p class="truncate text-[12px] text-[#7a6a7e]">{{ $buyerUser->email }}</p>
                                </div>
                                @php
                                    $accountLinks = [
                                        ['Orders', route('buyer.orders.index'), 'M7 3h10a1 1 0 0 1 1 1v17l-3-2-3 2-3-2-3 2V4a1 1 0 0 1 1-1zM9.5 8h5M9.5 12h5'],
                                        ['Vouchers', route('buyer.vouchers.index'), 'M3 8a2 2 0 0 0 0 4v4h18v-4a2 2 0 0 1 0-4V6H3zM9 6v12'],
                                        ['Complaints', route('buyer.complaints.index'), 'M5 21V4M5 4h11l-2 4 2 4H5'],
                                        ['Support', route('buyer.support.index'), 'M21 12a8 8 0 0 1-11.6 7.1L4 21l1.9-5.4A8 8 0 1 1 21 12zM9 11h6M9 14h4'],
                                    ];
                                @endphp
                                @foreach ($accountLinks as [$linkLabel, $linkUrl, $linkPath])
                                    <a href="{{ $linkUrl }}" role="menuitem" class="vb-menu-link">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $linkPath }}" /></svg>
                                        {{ $linkLabel }}
                                    </a>
                                @endforeach
                                <a href="{{ route('buyer.favorites') }}" role="menuitem" class="vb-menu-link">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20.4s-7.6-4.6-9.2-9.5C1.7 7.5 3.8 4.6 6.9 4.6c1.9 0 3.6 1 5.1 3 1.5-2 3.2-3 5.1-3 3.1 0 5.2 2.9 4.1 6.3-1.6 4.9-9.2 9.5-9.2 9.5z" /></svg>
                                    Favorites
                                    <span x-show="$store.fav.items.length" x-cloak x-text="$store.fav.items.length" class="ml-auto rounded-full bg-[#f3e8f5] px-1.5 text-[11px] font-medium leading-[18px] text-[#52245b]"></span>
                                </a>
                                <div class="my-1.5 border-t border-[#f1e8f2]"></div>
                                <a href="{{ route('buyer.account.index') }}" role="menuitem" class="vb-menu-link">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="4" /><path d="M4 21c0-4 3.6-7 8-7s8 3 8 7" /></svg>
                                    Account
                                </a>
                                <a href="{{ route('buyer.account.index', ['tab' => 'settings']) }}" role="menuitem" class="vb-menu-link">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="3" /><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3h0a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5h0a1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8v0a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z" /></svg>
                                    Settings
                                </a>
                                <form method="POST" action="{{ route('buyer.logout') }}" class="mt-1.5 border-t border-[#f1e8f2] pt-1.5">
                                    @csrf
                                    <button type="submit" role="menuitem" class="vb-menu-link w-full text-left !text-[#a32b43] hover:!bg-[#fdf1f3]">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h4M16 17l5-5-5-5M21 12H9" /></svg>
                                        Log out
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Category strip -->
            <nav class="border-t border-white/10" aria-label="Categories">
                <div class="mx-auto flex h-10 max-w-[1200px] items-center px-1 sm:px-3">
                    <a href="{{ route('buyer.dashboard') }}" @if(request()->routeIs('buyer.dashboard')) aria-current="page" @endif
                        class="vb-navlink flex h-10 flex-shrink-0 items-center px-3 text-[13px] font-medium text-white/85 transition-colors duration-200 hover:text-white aria-[current=page]:text-white">Home</a>

                    <button type="button" @click="open ? open = false : show(active)" @mouseenter="show(active)"
                        :aria-expanded="open" aria-controls="vb-mega"
                        :class="open ? 'is-open text-white' : 'text-white/85'"
                        class="vb-navlink vb-act flex h-10 flex-shrink-0 items-center gap-1.5 px-3 text-[13px] font-medium transition-colors duration-200 hover:text-white">
                        <svg class="vb-ico vb-ico--grid h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="4" width="6.5" height="6.5" rx="1.5" /><rect x="13.5" y="4" width="6.5" height="6.5" rx="1.5" /><rect x="4" y="13.5" width="6.5" height="6.5" rx="1.5" /><rect x="13.5" y="13.5" width="6.5" height="6.5" rx="1.5" /></svg>
                        All categories
                        <svg class="h-3 w-3 transition-transform duration-300 ease-vendo" :class="open ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6" /></svg>
                    </button>

                    <span class="mx-1 hidden h-4 w-px flex-shrink-0 bg-white/15 sm:block"></span>

                    <div class="vb-no-scrollbar flex min-w-0 flex-1 items-center overflow-x-auto">
                        @foreach ($menuCategories as $i => $category)
                            <a href="{{ route('buyer.products.index', ['category_id' => $category->id]) }}"
                                @mouseenter="show({{ $i }})"
                                @if((int) request('category_id') === $category->id) aria-current="page" @endif
                                :class="open && active === {{ $i }} ? 'is-open text-white' : 'text-white/75'"
                                class="vb-navlink flex h-10 flex-shrink-0 items-center whitespace-nowrap px-3 text-[13px] transition-colors duration-200 hover:text-white aria-[current=page]:text-white">{{ $category->name }}</a>
                        @endforeach
                    </div>
                </div>
            </nav>
        </div>

        <!-- Subcategory menu -->
        <div id="vb-mega" x-show="open" x-cloak @mouseenter="keep()"
            x-transition:enter="transition duration-300 ease-vendo" x-transition:enter-start="opacity-0 -translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition duration-200 ease-vendo"
            x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-1"
            class="absolute inset-x-0 top-full z-20 max-h-[calc(100vh-110px)] overflow-y-auto border-t border-[#efe4f1] bg-white text-[#2b1730] shadow-[0_30px_50px_-24px_rgba(43,23,48,0.55)]">
            <div class="mx-auto flex max-w-[1200px] flex-col lg:flex-row">

                <!-- Category list (chips on mobile, column on desktop) -->
                <ul
                    class="vb-no-scrollbar vb-thin-scroll flex flex-shrink-0 gap-1 overflow-x-auto border-b border-[#f1e8f2] p-2 lg:block lg:max-h-[440px] lg:w-[250px] lg:overflow-y-auto lg:border-b-0 lg:border-r">
                    @foreach ($menuCategories as $i => $category)
                        <li class="flex-shrink-0">
                            <a href="{{ route('buyer.products.index', ['category_id' => $category->id]) }}"
                                @mouseenter="active = {{ $i }}" @focus="active = {{ $i }}"
                                @click="if (window.innerWidth < 1024) { $event.preventDefault(); active = {{ $i }} }"
                                :class="active === {{ $i }} ? 'bg-[#f5ecf6] font-medium text-[#52245b]' : 'text-[#3d2a42] hover:bg-[#faf5fa]'"
                                class="flex items-center justify-between gap-2 whitespace-nowrap rounded-lg px-3 py-2 text-[13px] transition-colors duration-200">
                                {{ $category->name }}
                                <svg class="hidden h-3.5 w-3.5 text-[#805487] transition-all duration-300 ease-vendo lg:block"
                                    :class="active === {{ $i }} ? 'translate-x-0.5 opacity-100' : 'opacity-0'"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m9 6 6 6-6 6" /></svg>
                            </a>
                        </li>
                    @endforeach
                </ul>

                <!-- Subcategories of the active category -->
                <div class="min-w-0 flex-1 p-4 lg:min-h-[340px] lg:p-6">
                    <div class="vb-mega-stack">
                        @foreach ($menuCategories as $i => $category)
                            @php
                                $colors = $category->colors;
                                $categoryFolder = $slugify($category->name);
                            @endphp
                            <section class="vb-mega-pane" :class="{ 'is-active': active === {{ $i }} }"
                                :aria-hidden="active !== {{ $i }}">
                                <div class="mb-4 flex items-center justify-between border-b border-[#f1e8f2] pb-3">
                                    <h3 class="text-[15px] font-semibold text-[#402143]">{{ $category->name }}</h3>
                                    <a href="{{ route('buyer.products.index', ['category_id' => $category->id]) }}"
                                        class="group inline-flex items-center gap-1 text-[12px] font-medium text-[#805487] transition-colors duration-200 hover:text-[#402143]">
                                        View all
                                        <svg class="h-3.5 w-3.5 transition-transform duration-300 ease-vendo group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m9 6 6 6-6 6" /></svg>
                                    </a>
                                </div>

                                <div class="grid grid-cols-3 gap-x-3 gap-y-5 sm:grid-cols-4 lg:grid-cols-6">
                                    <!-- View all tile -->
                                    <a href="{{ route('buyer.products.index', ['category_id' => $category->id]) }}"
                                        class="group flex flex-col items-center gap-2 text-center">
                                        <span
                                            class="grid h-[72px] w-[72px] place-items-center rounded-full bg-[#f1e8f2] text-[#805487] transition-all duration-300 ease-vendo group-hover:-translate-y-0.5 group-hover:bg-[#805487] group-hover:text-white group-hover:shadow-[0_10px_18px_-10px_rgba(128,84,135,0.9)]">
                                            <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="4" width="6.5" height="6.5" rx="1.5" /><rect x="13.5" y="4" width="6.5" height="6.5" rx="1.5" /><rect x="4" y="13.5" width="6.5" height="6.5" rx="1.5" /><circle cx="16.75" cy="16.75" r="3.25" /></svg>
                                        </span>
                                        <span class="text-[12px] leading-4 text-[#3d2a42] transition-colors duration-200 group-hover:text-[#805487]">View all</span>
                                    </a>

                                    @foreach ($category->children as $sub)
                                        <a href="{{ route('buyer.products.index', ['category_id' => $sub->id]) }}"
                                            class="group flex flex-col items-center gap-2 text-center">
                                            <span
                                                class="relative grid h-[72px] w-[72px] place-items-center overflow-hidden rounded-full border text-[24px] font-semibold transition-all duration-300 ease-vendo group-hover:-translate-y-0.5 group-hover:shadow-[0_10px_18px_-10px_rgba(43,23,48,0.55)]"
                                                style="background: {{ $colors['bg'] }}; border-color: {{ $colors['border'] }}40; color: {{ $colors['border'] }}">
                                                {{ Str::upper(Str::substr($sub->name, 0, 1)) }}
                                                <img src="{{ asset('images/buyer/subcategories/' . $categoryFolder . '/' . $slugify($sub->name) . '.png') }}"
                                                    alt="" loading="lazy"
                                                    class="vb-img absolute inset-0 h-full w-full object-cover group-hover:scale-110"
                                                    onload="this.classList.add('is-loaded')" onerror="this.remove()">
                                            </span>
                                            <span class="line-clamp-2 text-[12px] leading-4 text-[#3d2a42] transition-colors duration-200 group-hover:text-[#805487]">{{ $sub->name }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            </section>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Banner notifications hang below the header (above page content, below the open menus) --}}
        @include('buyer.partials.banner')
    </header>

    <main class="flex-1">{{ $slot }}</main>

    @if ($footer)
        @include('buyer.partials.storefront-footer')
    @endif

    {{-- Favorites store, back to top --}}
    @include('buyer.partials.favorites-panel')

    <script>
    (() => {
        const wrap = document.getElementById('buyerBellWrap');
        const button = document.getElementById('buyerBellBtn');
        const menu = document.getElementById('buyerBellMenu');
        const badge = document.getElementById('buyerBellCount');
        let latestId = {{ $recentBuyerNotifications->first()?->id ?? 0 }};
        let audioContext;
        const unlock = () => {
            try {
                audioContext ||= new (window.AudioContext || window.webkitAudioContext)();
                if (audioContext.state === 'suspended') audioContext.resume();
            } catch (_) {}
        };
        document.addEventListener('pointerdown', unlock, {once: true});
        document.addEventListener('keydown', unlock, {once: true});
        const chime = () => {
            if (window.vendoPrefs && window.vendoPrefs.get().sound === false) return;
            unlock();
            if (!audioContext || audioContext.state !== 'running') return;
            [740, 980].forEach((frequency, index) => {
                const oscillator = audioContext.createOscillator();
                const gain = audioContext.createGain();
                const start = audioContext.currentTime + index * .13;
                oscillator.type = 'sine'; oscillator.frequency.value = frequency;
                gain.gain.setValueAtTime(.0001, start);
                gain.gain.exponentialRampToValueAtTime(.085, start + .02);
                gain.gain.exponentialRampToValueAtTime(.0001, start + .22);
                oscillator.connect(gain).connect(audioContext.destination);
                oscillator.start(start); oscillator.stop(start + .23);
            });
        };
        const close = () => { menu.hidden = true; button.setAttribute('aria-expanded', 'false'); };
        button.addEventListener('click', () => {
            const open = menu.hidden;
            menu.hidden = !open;
            button.setAttribute('aria-expanded', String(open));
            if (open) window.dispatchEvent(new CustomEvent('vb-menu', { detail: { id: 'bell' } }));
        });
        // Opening the category or account menu closes this one
        window.addEventListener('vb-menu', (event) => { if (event.detail.id !== 'bell') close(); });
        const ring = () => {
            button.classList.remove('is-ringing'); void button.offsetWidth; button.classList.add('is-ringing');
            setTimeout(() => button.classList.remove('is-ringing'), 1400);
        };
        document.addEventListener('click', event => { if (!wrap.contains(event.target)) close(); });
        document.addEventListener('keydown', event => { if (event.key === 'Escape' && !menu.hidden) { close(); button.focus(); } });
        const refresh = async () => {
            if (document.hidden) return;
            try {
                const response = await fetch(@json(route('buyer.notifications.recent')), {headers: {'Accept': 'application/json'}});
                if (!response.ok) return;
                const data = await response.json();
                const currentId = Number(data.latest_id) || 0;
                if (currentId > latestId) {
                    chime();
                    ring();
                    if (data.latest && window.Alpine?.store('banner')) {
                        Alpine.store('banner').show({
                            type: 'info',
                            title: data.latest.title,
                            text: data.latest.message,
                            action: { label: 'View', href: data.latest.url },
                        });
                    }
                }
                latestId = Math.max(latestId, currentId);
                const unread = Number(data.unread_count) || 0;
                badge.hidden = !unread; badge.textContent = unread > 99 ? '99+' : unread;
                document.getElementById('buyerBellList').innerHTML = data.html;
            } catch (_) {}
        };
        setInterval(refresh, 3000);
        document.addEventListener('visibilitychange', () => { if (!document.hidden) refresh(); });
    })();
    </script>
    <script>
    // Cart icon bumps whenever the badge is replaced after an Add to cart.
    (() => {
        const link = document.getElementById('vb-cart-link');
        if (!link) return;
        new MutationObserver(() => {
            link.classList.remove('is-bump'); void link.offsetWidth; link.classList.add('is-bump');
            setTimeout(() => link.classList.remove('is-bump'), 800);
        }).observe(link, { childList: true, subtree: true });
    })();

    // Submit buttons show a spinner and lock while a normal form posts, so a slow request is never clicked twice.
    (() => {
        document.addEventListener('submit', (e) => {
            const form = e.target;
            if (e.defaultPrevented || form.hasAttribute('x-target') || form.getAttribute('role') === 'search' || (form.target && form.target !== '_self')) return;
            const button = e.submitter || form.querySelector('button[type=submit]:not([name])');
            if (!button || button.name || button.disabled) return;
            setTimeout(() => { button.classList.add('vb-loading'); button.setAttribute('aria-busy', 'true'); button.disabled = true; }, 0);
        });
        window.addEventListener('pageshow', (e) => {
            if (!e.persisted) return;
            document.querySelectorAll('.vb-loading').forEach((b) => { b.classList.remove('vb-loading'); b.removeAttribute('aria-busy'); b.disabled = false; });
        });
    })();
    </script>
    <script>
    // Page transitions: progress bar while the next page loads, and fade-up reveal for sections as they scroll in.
    (() => {
        const bar = document.getElementById('vb-progress');
        const run = () => { if (bar) { bar.classList.remove('is-running'); void bar.offsetWidth; bar.classList.add('is-running'); } };
        document.addEventListener('click', (e) => {
            const a = e.target.closest('a[href]');
            if (!a || e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
            if (a.target && a.target !== '_self') return;
            if (a.hasAttribute('download') || a.getAttribute('href').startsWith('#')) return;
            const url = new URL(a.href, location.href);
            if (url.origin !== location.origin || (url.pathname === location.pathname && url.search === location.search)) return;
            run();
        });
        document.addEventListener('submit', (e) => { if (!e.defaultPrevented && !e.target.hasAttribute('x-target')) run(); });

        // Pages that do not mark their own sections get their main blocks animated automatically.
        const main = document.querySelector('main');
        if (main && !main.querySelector('.vb-reveal')) {
            const root = main.children.length === 1 ? main.firstElementChild : main;
            [...root.children]
                .filter((el) => !['SCRIPT', 'TEMPLATE', 'STYLE'].includes(el.tagName) && el.getBoundingClientRect().height > 0 && getComputedStyle(el).position !== 'fixed')
                .slice(0, 14)
                .forEach((el, i) => { el.classList.add('vb-reveal'); el.style.setProperty('--i', i); });
        }

        const items = [...document.querySelectorAll('.vb-reveal')];
        const show = (el) => el.classList.add('is-in');
        if (!('IntersectionObserver' in window)) { items.forEach(show); return; }
        const io = new IntersectionObserver((entries) => {
            entries.forEach((entry) => { if (entry.isIntersecting) { show(entry.target); io.unobserve(entry.target); } });
        }, { rootMargin: '0px 0px -6% 0px', threshold: 0.05 });
        // Start on the next frame so the hidden state paints first, then everything on screen moves in.
        requestAnimationFrame(() => requestAnimationFrame(() => items.forEach((el) => io.observe(el))));
    })();
    </script>
    @include('shared.live-revision-script')
</body>

</html>
