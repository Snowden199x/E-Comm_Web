@props(['title' => 'Vendo — Buyer'])

@php
    $recentBuyerNotifications = \App\Models\Communication\Notification::query()->where('user_id', auth()->id())->latest()->limit(5)->get();
    $buyerUnreadNotifications = \App\Models\Communication\Notification::query()->where('user_id', auth()->id())->whereNull('read_at')->count();
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/shared/app.css', 'resources/js/shared/app.js'])
</head>

<body class="bg-gray-50">
    <div x-data="{ accountOpen: false }">

        <!-- Top bar -->
        <header class="bg-[#3b1735] text-white">
            <div class="max-w-7xl mx-auto px-4 py-3 flex flex-wrap items-center gap-3 sm:gap-4">
                <a href="{{ route('buyer.dashboard') }}" class="shrink-0">
                    <img src="{{ asset('assets/branding/vendo-logo.svg') }}" class="h-8">
                </a>

                <form action="#" class="order-2 flex w-full min-w-0 sm:order-none sm:flex-1">
                    <input type="text" placeholder="Search products..."
                        class="min-w-0 w-full rounded-l-lg px-4 py-2 text-gray-900 text-sm focus:outline-none">
                    <button class="bg-[#e8c874] px-4 rounded-r-lg">
                        <img src="{{ asset('assets/icons/registration/search-icon.svg') }}" class="w-4 h-4">
                    </button>
                </form>

                <a href="{{ route('buyer.cart.index') }}" class="relative ml-auto shrink-0 sm:ml-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    @if ($cartCount > 0)
                        <span
                            class="absolute -top-2 -right-2 bg-[#e8c874] text-[#3b1735] text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center">{{ $cartCount }}</span>
                    @endif
                </a>

                <div class="relative shrink-0" id="buyerBellWrap">
                    <button type="button" id="buyerBellBtn" aria-label="Notifications" aria-controls="buyerBellMenu" aria-expanded="false" class="relative grid h-9 w-9 place-items-center rounded-lg hover:bg-white/10">
                        <svg viewBox="0 0 24 24" width="23" height="23" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/></svg>
                        <span id="buyerBellCount" class="absolute -right-1 -top-1 rounded-full bg-[#e8c874] px-1 text-[10px] font-bold leading-4 text-[#3b1735]" @if(!$buyerUnreadNotifications) hidden @endif>{{ $buyerUnreadNotifications > 99 ? '99+' : $buyerUnreadNotifications }}</span>
                    </button>
                    <div id="buyerBellMenu" class="absolute right-0 z-50 mt-3 max-h-[70vh] w-80 max-w-[calc(100vw-24px)] overflow-y-auto rounded-xl border border-gray-100 bg-white text-gray-900 shadow-xl sm:w-[350px]" hidden>
                        <div class="flex items-center justify-between border-b px-4 py-3"><strong class="text-sm">Notifications</strong><a href="{{ route('buyer.notifications.index') }}" class="text-xs font-semibold text-[#5b2963]">View all</a></div>
                        <div id="buyerBellList">@include('buyer.notifications.recent', ['notifications' => $recentBuyerNotifications])</div>
                    </div>
                </div>

                <div class="relative shrink-0">
                    <button @click="accountOpen = !accountOpen" class="flex items-center gap-2">
                        @if(Auth::user()->profile_picture)
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url(Auth::user()->profile_picture) }}" alt="" class="h-8 w-8 rounded-full border border-white/70 bg-white object-cover">
                        @else
                            <img src="{{ asset('assets/icons/dashboard/user-icon.svg') }}" alt="" class="h-7 w-7">
                        @endif
                        <span class="text-sm hidden sm:inline">{{ Auth::user()->name }}</span>
                    </button>

                    <div x-show="accountOpen" x-cloak @click.outside="accountOpen = false"
                        class="absolute right-0 mt-2 w-48 bg-white text-gray-900 rounded-lg shadow-lg py-2 z-50">
                        <a href="{{ route('buyer.orders.index') }}" class="block px-4 py-2 text-sm hover:bg-gray-100">My
                            Orders</a>
                        <a href="{{ route('buyer.messages.index') }}"
                            class="block px-4 py-2 text-sm hover:bg-gray-100">Messages</a>
                        <a href="{{ route('buyer.account.index') }}"
                            class="block px-4 py-2 text-sm hover:bg-gray-100">Account</a>
                        <form method="POST" action="{{ route('buyer.logout') }}">
                            @csrf
                            <button class="w-full text-left px-4 py-2 text-sm hover:bg-gray-100">Logout</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Category strip -->
            <nav class="bg-[#4d1f45] px-4">
                <div class="max-w-7xl mx-auto flex gap-6 overflow-x-auto text-sm py-2">
                    <a href="{{ route('buyer.dashboard') }}" class="whitespace-nowrap hover:text-[#e8c874]">Home</a>
                    <a href="{{ route('buyer.categories') }}"
                        class="whitespace-nowrap hover:text-[#e8c874]">Categories</a>
                </div>
            </nav>
        </header>

        <main>{{ $slot }}</main>
    </div>
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
    @include('shared.message-delete-dialog')
    @include('shared.live-revision-script')
</body>

</html>
