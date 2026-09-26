@props(['title' => 'Seller Dashboard'])

@php
    $sellerName = auth()->user()->name;
    $sellerRole = 'Seller';
    $icon = fn (string $file) => asset('assets/icons/seller/' . $file);
    $recentNotifications = \App\Models\Communication\Notification::query()->where('user_id', auth()->id())->latest()->limit(10)->get();
    $unreadNotifications = \App\Models\Communication\Notification::query()->where('user_id', auth()->id())->whereNull('read_at')->count();
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} | Vendo</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite('resources/css/seller/seller-dashboard.css')
    <link rel="stylesheet" href="{{ asset('assets/css/notification-actions.css') }}">
</head>
<body class="sd-body @if(request()->routeIs('seller.products.*', 'seller.shipments.*', 'seller.completed-orders.*', 'seller.feedback.*', 'seller.reports.*', 'seller.messages.*', 'seller.marketplace-messages.*', 'seller.account.*', 'seller.notifications.*')) sd-sidebar-pinned @endif">

    {{-- ============ SIDEBAR ============ --}}
    {{-- Rests collapsed (icons only). Hovering expands it as an overlay.
         The hamburger button in the topbar pins it open (adds .sd-sidebar-pinned
         to <body>), which also pushes the main content over instead of overlaying it. --}}
    <aside class="sd-sidebar" id="sdSidebar">
        <a href="{{ route('seller.dashboard') }}" class="sd-brand" aria-label="Vendo home">
            <img src="{{ asset('images/logo/vendo-icon.png') }}" alt="Vendo" class="sd-brand__icon">
            <img src="{{ asset('assets/branding/log-in-logo.svg') }}" alt="Vendo – Buy. Sell. Delivered." class="sd-brand__full">
        </a>

        <nav class="sd-nav" aria-label="Seller navigation">
            <a href="{{ route('seller.dashboard') }}" class="sd-nav__item @if(request()->routeIs('seller.dashboard')) is-active @endif" @if(request()->routeIs('seller.dashboard')) aria-current="page" @endif title="Dashboard">
                <img src="{{ $icon('dashboard-icon.png') }}" alt="">
                <span>Dashboard</span>
            </a>

            <button type="button" class="sd-nav__item sd-nav__toggle @if(request()->routeIs('seller.orders.*', 'seller.products.*', 'seller.shipments.*', 'seller.completed-orders.*', 'seller.feedback.*')) is-active @endif" id="orderMenuToggle" aria-expanded="{{ request()->routeIs('seller.orders.*', 'seller.products.*', 'seller.shipments.*', 'seller.completed-orders.*', 'seller.feedback.*') ? 'true' : 'false' }}" aria-controls="orderSubmenu" title="Order Management">
                <img src="{{ $icon('Ordermanagement-icon.png') }}" alt="">
                <span>Order Management</span>
                <svg class="sd-nav__chevron" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
            </button>

            <div class="sd-submenu @if(!request()->routeIs('seller.orders.*', 'seller.products.*', 'seller.shipments.*', 'seller.completed-orders.*', 'seller.feedback.*')) is-closed @endif" id="orderSubmenu">
                <a href="{{ route('seller.orders.index') }}" class="sd-nav__item sd-nav__item--sub @if(request()->routeIs('seller.orders.index')) is-active @endif" @if(request()->routeIs('seller.orders.index')) aria-current="page" @endif title="Orders">
                    <img src="{{ $icon('orders-icon.png') }}" alt="">
                    <span>Orders</span>
                </a>
                <a href="{{ route('seller.products.index') }}" class="sd-nav__item sd-nav__item--sub @if(request()->routeIs('seller.products.*')) is-active @endif" @if(request()->routeIs('seller.products.*')) aria-current="page" @endif title="Products &amp; Inventory">
                    <img src="{{ $icon('products-inventory--icon.png') }}" alt="">
                    <span>Products &amp; Inventory</span>
                </a>
                <a href="{{ route('seller.shipments.index') }}" class="sd-nav__item sd-nav__item--sub @if(request()->routeIs('seller.shipments.*')) is-active @endif" @if(request()->routeIs('seller.shipments.*')) aria-current="page" @endif title="Shipments">
                    <img src="{{ $icon('shipments-icon.png') }}" alt="">
                    <span>Shipments</span>
                </a>
                <a href="{{ route('seller.completed-orders.index') }}" class="sd-nav__item sd-nav__item--sub @if(request()->routeIs('seller.completed-orders.*')) is-active @endif" @if(request()->routeIs('seller.completed-orders.*')) aria-current="page" @endif title="Delivered Orders">
                    <img src="{{ $icon('completed-orders-icon.png') }}" alt="">
                    <span>Delivered Orders</span>
                </a>
                <a href="{{ route('seller.feedback.index') }}" class="sd-nav__item sd-nav__item--sub @if(request()->routeIs('seller.feedback.*')) is-active @endif" @if(request()->routeIs('seller.feedback.*')) aria-current="page" @endif title="Feedback">
                    <img src="{{ $icon('feedback-icon.png') }}" alt="">
                    <span>Feedback</span>
                </a>
            </div>

            <a href="{{ route('seller.reports.index') }}" class="sd-nav__item @if(request()->routeIs('seller.reports.*')) is-active @endif" @if(request()->routeIs('seller.reports.*')) aria-current="page" @endif title="Reports">
                <img src="{{ $icon('reports-icon.png') }}" alt="">
                <span>Reports</span>
            </a>
            <a href="{{ route('seller.messages.index') }}" class="sd-nav__item @if(request()->routeIs('seller.messages.*', 'seller.marketplace-messages.*')) is-active @endif" @if(request()->routeIs('seller.messages.*', 'seller.marketplace-messages.*')) aria-current="page" @endif title="Messages">
                <img src="{{ $icon('messages-icon.png') }}" alt="">
                <span>Messages</span>
            </a>
            <a href="{{ route('seller.account.index') }}" class="sd-nav__item @if(request()->routeIs('seller.account.*')) is-active @endif" @if(request()->routeIs('seller.account.*')) aria-current="page" @endif title="Account Management">
                <img src="{{ $icon('account-management-icon.png') }}" alt="">
                <span>Account Management</span>
            </a>
        </nav>

        <form method="POST" action="{{ route('seller.logout') }}" class="sd-logout">
            @csrf
            <button type="submit" class="sd-nav__item" title="Logout">
                <img src="{{ $icon('logout-icon.png') }}" alt="">
                <span>Logout</span>
            </button>
        </form>
    </aside>

    <div class="sd-backdrop" id="sdBackdrop"></div>

    {{-- ============ MAIN ============ --}}
    <div class="sd-main">
        <header class="sd-topbar">
            <button type="button" class="sd-topbar__menu" id="sdMenuBtn" aria-label="Pin sidebar open">
                <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" aria-hidden="true"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
            </button>

            <div class="sd-topbar__right">
                <div class="sd-bell-wrap" id="sdBellWrap">
                    <button type="button" class="sd-topbar__bell" id="sdBellBtn" aria-label="Notifications" aria-controls="sdBellMenu" aria-expanded="false">
                        <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 8a6 6 0 10-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 01-3.4 0"/></svg>
                        <span class="sd-bell-count" id="sdBellCount" @if(!$unreadNotifications) hidden @endif>{{ $unreadNotifications > 99 ? '99+' : $unreadNotifications }}</span>
                    </button>
                    <div class="sd-bell-menu" id="sdBellMenu" hidden><div class="sd-bell-menu__head"><strong>Notifications</strong><div class="notification-menu-actions"><form method="POST" action="{{ route('seller.notifications.read-all') }}">@csrf<button type="submit">Mark all as read</button></form><a href="{{ route('seller.notifications.index') }}">View all</a></div></div><div id="sdBellList">@include('seller.notifications.recent', ['notifications' => $recentNotifications])</div></div>
                </div>
                <div class="sd-user-wrap" id="sdUserWrap">
                    <button type="button" class="sd-user" id="sdUserBtn" aria-label="Account menu" aria-controls="sdUserMenu" aria-expanded="false">
                    @if(auth()->user()->profile_picture)<img class="sd-user__avatar sd-user__avatar--photo" src="{{ \Illuminate\Support\Facades\Storage::url(auth()->user()->profile_picture) }}" alt="">
                    @else<svg class="sd-user__avatar" viewBox="0 0 40 40" width="30" height="30" aria-hidden="true">
                        <circle cx="20" cy="20" r="18.5" fill="none" stroke="currentColor" stroke-width="2.4"/>
                        <circle cx="20" cy="15.5" r="5.6" fill="currentColor"/>
                        <path d="M8.5 31c1.6-5 6-7.4 11.5-7.4S29.900 26 31.500 31A17 17 0 0120 37a17 17 0 01-11.500-6z" fill="currentColor"/>
                    </svg>@endif
                    <div class="sd-user__text">
                        <strong>{{ $sellerName }}</strong>
                        <span>{{ $sellerRole }}</span>
                    </div>
                    </button>
                    <div class="sd-user-menu" id="sdUserMenu" hidden>
                        <a href="{{ route('seller.account.index') }}">Account Management</a>
                        <form method="POST" action="{{ route('seller.logout') }}">@csrf<button type="submit">Logout</button></form>
                    </div>
                </div>
            </div>
        </header>

        <main class="sd-content">
            {{ $slot }}
        </main>
    </div>

    <script>
        (function () {
            var body = document.body;
            var isMobile = function () { return window.matchMedia('(max-width: 900px)').matches; };

            // Hamburger: on mobile it opens the off-canvas drawer;
            // on desktop it pins the rail open (overrides hover-to-expand).
            document.getElementById('sdMenuBtn').addEventListener('click', function () {
                body.classList.toggle(isMobile() ? 'sd-sidebar-open' : 'sd-sidebar-pinned');
            });
            document.getElementById('sdBackdrop').addEventListener('click', function () {
                body.classList.remove('sd-sidebar-open');
            });

            var toggle = document.getElementById('orderMenuToggle');
            var submenu = document.getElementById('orderSubmenu');
            toggle.addEventListener('click', function () {
                var open = toggle.getAttribute('aria-expanded') === 'true';
                toggle.setAttribute('aria-expanded', String(!open));
                submenu.classList.toggle('is-closed', open);
            });
            // Keep aria-expanded / is-closed in sync with the server-rendered
            // active state above, in case CSS/markup order ever drifts.
            submenu.classList.toggle('is-closed', toggle.getAttribute('aria-expanded') !== 'true');

            var bellWrap = document.getElementById('sdBellWrap');
            var bellButton = document.getElementById('sdBellBtn');
            var bellMenu = document.getElementById('sdBellMenu');
            var closeBell = function () { bellMenu.hidden = true; bellButton.setAttribute('aria-expanded', 'false'); };
            var userWrap = document.getElementById('sdUserWrap');
            var userButton = document.getElementById('sdUserBtn');
            var userMenu = document.getElementById('sdUserMenu');
            var closeUser = function () { userMenu.hidden = true; userButton.setAttribute('aria-expanded', 'false'); };
            bellButton.addEventListener('click', function () {
                var opening = bellMenu.hidden;
                closeUser();
                bellMenu.hidden = !opening;
                bellButton.setAttribute('aria-expanded', String(opening));
            });
            userButton.addEventListener('click', function () {
                var opening = userMenu.hidden;
                closeBell();
                userMenu.hidden = !opening;
                userButton.setAttribute('aria-expanded', String(opening));
            });
            document.addEventListener('click', function (event) {
                if (!bellWrap.contains(event.target)) closeBell();
                if (!userWrap.contains(event.target)) closeUser();
            });
            document.addEventListener('keydown', function (event) {
                if (event.key !== 'Escape') return;
                if (!bellMenu.hidden) { closeBell(); bellButton.focus(); }
                if (!userMenu.hidden) { closeUser(); userButton.focus(); }
            });
            var latestNotificationId = {{ $recentNotifications->first()?->id ?? 0 }};
            var audioContext;
            var unlockAudio = function () {
                try {
                    audioContext = audioContext || new (window.AudioContext || window.webkitAudioContext)();
                    if (audioContext.state === 'suspended') audioContext.resume();
                } catch (_) {}
            };
            document.addEventListener('pointerdown', unlockAudio, { once: true });
            document.addEventListener('keydown', unlockAudio, { once: true });
            var playNotification = function () {
                unlockAudio();
                if (!audioContext || audioContext.state !== 'running') return;
                [740, 980].forEach(function (frequency, index) {
                    var oscillator = audioContext.createOscillator();
                    var gain = audioContext.createGain();
                    var start = audioContext.currentTime + index * .13;
                    oscillator.type = 'sine';
                    oscillator.frequency.value = frequency;
                    gain.gain.setValueAtTime(.0001, start);
                    gain.gain.exponentialRampToValueAtTime(.085, start + .02);
                    gain.gain.exponentialRampToValueAtTime(.0001, start + .22);
                    oscillator.connect(gain).connect(audioContext.destination);
                    oscillator.start(start);
                    oscillator.stop(start + .23);
                });
            };
            var refreshBell = async function () {
                if (document.hidden) return;
                try {
                    var response = await fetch(@json(route('seller.notifications.recent')), {headers: {'Accept': 'application/json'}});
                    if (!response.ok) return;
                    var data = await response.json();
                    var latestCount = Number(data.unread_count) || 0;
                    var latestId = Number(data.latest_id) || 0;
                    if (latestId > latestNotificationId) playNotification();
                    latestNotificationId = Math.max(latestNotificationId, latestId);
                    var badge = document.getElementById('sdBellCount');
                    badge.hidden = !latestCount;
                    badge.textContent = latestCount > 99 ? '99+' : latestCount;
                    document.getElementById('sdBellList').innerHTML = data.html;
                    var dashboardList = document.getElementById('sdDashboardNotifications');
                    if (dashboardList) dashboardList.innerHTML = data.dashboard_html;
                } catch (_) {}
            };
            window.setInterval(refreshBell, 3000);
            document.addEventListener('visibilitychange', function () { if (!document.hidden) refreshBell(); });
        })();
    </script>
    @include('shared.live-revision-script')
</body>
</html>
