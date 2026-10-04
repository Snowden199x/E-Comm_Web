@props(['title' => 'Vendo — Buyer'])

@php
    use Illuminate\Support\Str;

    $recentBuyerNotifications = \App\Models\Communication\Notification::query()->where('user_id', auth()->id())->latest()->limit(5)->get();
    $buyerUnreadNotifications = \App\Models\Communication\Notification::query()->where('user_id', auth()->id())->whereNull('read_at')->count();
    $buyerUser = Auth::user();

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

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/shared/app.css', 'resources/css/buyer/layout.css', 'resources/js/shared/app.js'])
    <link rel="stylesheet" href="{{ asset('assets/css/notification-actions.css') }}">
    @if(request()->routeIs('buyer.cart.index'))<link rel="stylesheet" href="{{ asset('assets/css/cart.css') }}">@endif
</head>

<body class="vb-body min-h-screen" x-data>

    <!-- ===================== Header ===================== -->
    <header class="sticky top-0 z-40 text-white"
        x-data="{
            open: false,
            active: 0,
            timer: null,
            show(i) {
                clearTimeout(this.timer);
                this.active = i;
                this.timer = setTimeout(() => this.open = true, this.open ? 0 : 90);
            },
            keep() { clearTimeout(this.timer); },
            hide() { clearTimeout(this.timer); this.timer = setTimeout(() => this.open = false, 140); },
        }"
        @mouseleave="hide()" @keydown.escape.window="open = false">

        <!-- Dim the page while the category menu is open -->
        <div x-show="open" x-cloak @mouseenter="hide()" @click="open = false"
            x-transition:enter="transition-opacity duration-300 ease-vendo" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity duration-200 ease-vendo"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="fixed inset-0 -z-10 bg-[#1b0b1e]/45"></div>

        <div class="vb-header">
            <!-- Top row -->
            <div
                class="mx-auto flex max-w-[1200px] flex-wrap items-center gap-x-3 gap-y-2 px-3 py-2.5 sm:h-16 sm:flex-nowrap sm:gap-x-5 sm:px-4 sm:py-0">

                <a href="{{ route('buyer.dashboard') }}" class="flex-shrink-0" aria-label="Vendo home">
                    <img src="{{ asset('assets/branding/log-in-logo.svg') }}" alt="Vendo" class="h-9 w-auto sm:h-10">
                </a>

                <!-- Search -->
                <form action="{{ route('buyer.products.index') }}" method="GET" role="search"
                    class="order-last flex h-10 w-full min-w-0 items-center rounded-lg bg-white p-[3px] transition-shadow duration-300 ease-vendo focus-within:shadow-[0_0_0_3px_rgba(232,200,116,0.55)] sm:order-none sm:mx-auto sm:max-w-[640px] sm:flex-1">
                    <input type="search" name="search" value="{{ request('search') }}" placeholder="Search products"
                        autocomplete="off"
                        class="h-full min-w-0 flex-1 border-0 bg-transparent px-3 text-[13px] text-[#2b1730] placeholder:text-[#9a8a9d] focus:outline-none focus:ring-0">
                    <button type="submit" aria-label="Search"
                        class="grid h-full w-12 flex-shrink-0 place-items-center rounded-md bg-[#e8c874] text-[#402143] transition-all duration-300 ease-vendo hover:bg-[#f1d786] active:scale-95">
                        <span class="vb-icon h-[18px] w-[18px]"
                            style="--icon: url('{{ asset('assets/icons/buyer/search-icon.svg') }}')"></span>
                    </button>
                </form>

                <div class="ml-auto flex flex-shrink-0 items-center gap-0.5 sm:ml-0 sm:gap-1">

                    <!-- Messages -->
                    <a href="{{ route('buyer.messages.index') }}" aria-label="Messages"
                        class="grid h-10 w-10 place-items-center rounded-full text-white transition-colors duration-200 hover:bg-white/10">
                        <span class="vb-icon h-5 w-5"
                            style="--icon: url('{{ asset('assets/icons/buyer/messages-icon.svg') }}')"></span>
                    </a>

                    <!-- Cart -->
                    <a href="{{ route('buyer.cart.index') }}" aria-label="Cart"
                        class="relative grid h-10 w-10 place-items-center rounded-full text-white/90 transition-colors duration-200 hover:bg-white/10 hover:text-white">
                        <span class="vb-icon h-6 w-6"
                            style="--icon: url('{{ asset('assets/icons/buyer/cart-icon.svg') }}')"></span>
                        <span id="cart-badge">
                            @if ($cartCount > 0)
                                <span
                                    class="vb-pop absolute right-0 top-0 grid h-[18px] min-w-[18px] place-items-center rounded-full bg-[#e8c874] px-1 text-[10px] font-semibold leading-none text-[#402143]">{{ $cartCount > 99 ? '99+' : $cartCount }}</span>
                            @endif
                        </span>
                    </a>

                    <!-- Notifications -->
                    <div class="relative" id="buyerBellWrap">
                        <button type="button" id="buyerBellBtn" aria-label="Notifications" aria-controls="buyerBellMenu"
                            aria-expanded="false"
                            class="relative grid h-10 w-10 place-items-center rounded-full text-white/90 transition-colors duration-200 hover:bg-white/10 hover:text-white">
                            <span class="vb-icon h-6 w-6"
                                style="--icon: url('{{ asset('assets/icons/buyer/notifications-icon.svg') }}')"></span>
                            <span id="buyerBellCount"
                                class="absolute right-0 top-0 rounded-full bg-[#e8c874] px-1 text-[10px] font-semibold leading-[18px] text-[#402143]"
                                @if(!$buyerUnreadNotifications) hidden @endif>{{ $buyerUnreadNotifications > 99 ? '99+' : $buyerUnreadNotifications }}</span>
                        </button>
                        <div id="buyerBellMenu"
                            class="vb-menu absolute right-0 z-50 mt-2 max-h-[70vh] w-80 max-w-[calc(100vw-24px)] overflow-y-auto rounded-xl border border-gray-100 bg-white text-gray-900 shadow-xl sm:w-[350px]"
                            hidden>
                            <div class="flex items-center justify-between border-b px-4 py-3"><strong class="text-sm">Notifications</strong><div class="notification-menu-actions"><form method="POST" action="{{ route('buyer.notifications.read-all') }}">@csrf<button type="submit">Mark all as read</button></form><a href="{{ route('buyer.notifications.index') }}">View all</a></div></div>
                            <div id="buyerBellList">@include('buyer.notifications.recent', ['notifications' => $recentBuyerNotifications])</div>
                        </div>
                    </div>

                    <!-- Account menu -->
                    <div class="relative ml-1" x-data="{ o: false }" @click.outside="o = false" @keydown.escape="o = false">
                        <button type="button" @click="o = !o" :aria-expanded="o"
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

                        <div x-show="o" x-cloak
                            x-transition:enter="transition duration-200 ease-vendo" x-transition:enter-start="opacity-0 -translate-y-1 scale-95"
                            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                            x-transition:leave="transition duration-150 ease-vendo" x-transition:leave-start="opacity-100 scale-100"
                            x-transition:leave-end="opacity-0 scale-95"
                            class="absolute right-0 z-50 mt-2 w-56 origin-top-right overflow-hidden rounded-xl border border-[#eee6ef] bg-white py-1.5 text-[13px] text-[#2b1730] shadow-[0_18px_40px_-16px_rgba(43,23,48,0.5)]">
                            <div class="border-b border-[#f1e8f2] px-4 pb-2.5 pt-1.5">
                                <p class="truncate font-semibold">{{ $buyerUser->name }}</p>
                                <p class="text-[11px] text-[#9a8a9d]">Buyer account</p>
                            </div>
                            <a href="{{ route('buyer.orders.index') }}" class="block px-4 py-2.5 transition-colors duration-150 hover:bg-[#f7eff8]">My Orders</a>
                            <a href="{{ route('buyer.account.index') }}" class="block px-4 py-2.5 transition-colors duration-150 hover:bg-[#f7eff8]">Account Management</a>
                            <form method="POST" action="{{ route('buyer.logout') }}" class="border-t border-[#f1e8f2]">
                                @csrf
                                <button type="submit" class="block w-full px-4 py-2.5 text-left text-[#a32b43] transition-colors duration-150 hover:bg-[#fdf1f3]">Logout</button>
                            </form>
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
                        class="vb-navlink flex h-10 flex-shrink-0 items-center gap-1.5 px-3 text-[13px] font-medium transition-colors duration-200 hover:text-white">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="4" width="6.5" height="6.5" rx="1.5" /><rect x="13.5" y="4" width="6.5" height="6.5" rx="1.5" /><rect x="4" y="13.5" width="6.5" height="6.5" rx="1.5" /><rect x="13.5" y="13.5" width="6.5" height="6.5" rx="1.5" /></svg>
                        All Categories
                        <svg class="h-3 w-3 transition-transform duration-300 ease-vendo" :class="open ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6" /></svg>
                    </button>

                    <span class="mx-1 hidden h-4 w-px flex-shrink-0 bg-white/15 sm:block"></span>

                    <div class="vb-no-scrollbar vb-fade-x flex min-w-0 flex-1 items-center overflow-x-auto">
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
            class="absolute inset-x-0 top-full max-h-[calc(100vh-110px)] overflow-y-auto border-t border-[#efe4f1] bg-white text-[#2b1730] shadow-[0_30px_50px_-24px_rgba(43,23,48,0.55)]">
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
                                        <span class="text-[12px] leading-4 text-[#3d2a42] transition-colors duration-200 group-hover:text-[#805487]">View All</span>
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
    </header>

    <main>{{ $slot }}</main>

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
        button.addEventListener('click', () => { const open = menu.hidden; menu.hidden = !open; button.setAttribute('aria-expanded', String(open)); });
        document.addEventListener('click', event => { if (!wrap.contains(event.target)) close(); });
        document.addEventListener('keydown', event => { if (event.key === 'Escape' && !menu.hidden) { close(); button.focus(); } });
        const refresh = async () => {
            try {
                const response = await fetch(@json(route('buyer.notifications.recent')), {headers: {'Accept': 'application/json'}});
                if (!response.ok) return;
                const data = await response.json();
                const currentId = Number(data.latest_id) || 0;
                if (currentId > latestId) chime();
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
    @include('shared.live-revision-script')
</body>

</html>
