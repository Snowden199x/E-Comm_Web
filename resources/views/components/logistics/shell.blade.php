@props(['title', 'role' => 'station', 'skeleton' => null])

@php
    $user = auth()->user();
    $facility = request()->attributes->get('logistics_facility');
    $identity = $role === 'company'
        ? [$user?->logisticsCenterDetail?->business_name ?? $user?->name ?? 'Logistics Company', 'Company Portal']
        : [$facility?->name ?? $user?->name ?? 'Logistics Facility', $role === 'province' ? 'Province Hub' : 'Station Hub'];
    [$orgName, $roleLabel] = $identity;
    $initial = mb_strtoupper(mb_substr($orgName, 0, 1));
    $home = $role === 'company' ? route('lgp.company') : route('lgp.hub.'.$role);

    /*
     * Loading skeleton shape per page. Keys are route-name patterns (first match wins); anything not listed draws the
     * table layout. A page can override it with the shell's skeleton prop (skeleton="form"). The sidebar uses the same map for
     * its links, so the skeleton that appears while a link loads already has the shape of the page it opens.
     */
    $skeletonMap = [
        'lgp.company' => 'dashboard',
        'lgp.hub.province' => 'dashboard',
        'lgp.hub.station' => 'dashboard',
        'lgp.*.reports' => 'dashboard',
        'lgp.*.messages' => 'split',
        'lgp.company.network' => 'cards',
        'lgp.company.hubs.show' => 'cards',
        'lgp.*.account' => 'form',
    ];
    $skeletonFor = fn (string $routeName) => collect($skeletonMap)
        ->first(fn ($shape, $pattern) => \Illuminate\Support\Str::is($pattern, $routeName), 'table');
    $skeletonVariant = $skeleton ?? $skeletonFor((string) request()->route()?->getName());

    // Sidebar lists live in config/logistics_nav.php. Shape the shell loops over:
    // [label, icon, url, active, badge, skeleton shape]
    $nav = collect(config('logistics_nav.'.$role))->map(fn ($group) => [
        'label' => $group['label'],
        'items' => array_map(fn ($item) => [
            $item['label'],
            $item['icon'],
            route($item['route']),
            request()->routeIs(...(array) ($item['active'] ?? $item['route'])),
            $item['badge'] ?? 0,
            $skeletonFor($item['route']),
        ], $group['items']),
    ])->all();
    $railItems = collect($nav)->sum(fn ($group) => count($group['items']));

    $alerts = \App\Models\Communication\Notification::query()->where('user_id', $user?->id)
        ->whereNull('read_at')->latest()->limit(8)->get();
    $alertCount = \App\Models\Communication\Notification::query()->where('user_id', $user?->id)
        ->whereNull('read_at')->count();
    $initialAlerts = $alerts->map(fn ($item) => ['id' => $item->id, 'title' => $item->title,
        'message' => $item->message, 'open_url' => route('lgp.notifications.open', $item)])->values();

    $linkBase = 'group relative flex h-10 w-full items-center gap-3 overflow-hidden rounded-xl px-3 transition-colors duration-200 ease-vendo focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#e8c874]/70';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('shared.site-icon')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} - Vendo Logistics</title>

    {{-- The sidebar's pinned state, known before first paint so the loading skeleton draws the right rail width. --}}
    <script>
        try {
            if (localStorage.getItem('vendo_sidebar_collapsed') === 'true') document.documentElement.dataset.rail = 'collapsed';
        } catch (e) {}
    </script>

    {{-- Just enough CSS to paint the loading skeleton before any stylesheet arrives (no white flash). --}}
    <style>
        #vd-boot { position: fixed; inset: 0; z-index: 100; display: flex; background: #FBF7F2; }
        #vd-boot .vd-boot__rail { display: none; width: 272px; flex: none; background: #3b1735; }
        html[data-rail="collapsed"] #vd-boot .vd-boot__rail { width: 80px; }
        @media (min-width: 1024px) { #vd-boot .vd-boot__rail { display: block; } }
    </style>
    <noscript><style>#vd-boot { display: none; }</style></noscript>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    {{-- Fonts load without blocking the first paint and swap in when they arrive. --}}
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap"
        rel="stylesheet" media="print" onload="this.media='all'">
    <noscript><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet"></noscript>
    {{-- Same sidebar store, layout css and motion as the admin (motion.css = skeleton, empty state, button spinner);
         workspace.css only styles the page content (cards, tables, pills). states.js lifts the loading skeleton. --}}
    @vite(['resources/css/shared/app.css', 'resources/css/admin/layout.css', 'resources/css/admin/motion.css', 'resources/css/logistics/workspace.css', 'resources/css/logistics/banner.css', 'resources/js/admin/sidebar.js', 'resources/js/logistics/states.js', 'resources/js/logistics/banner.js', 'resources/js/shared/app.js'])
    @if ($facility && $facility->is_active)
        @vite('resources/js/logistics/facility-scanner.js')
    @endif
    {{-- Page theme layers (for example logistics/dashboard-theme.css) are pushed here so they load after everything above. --}}
    @stack('lg-head')
</head>
{{-- x-data is required: Alpine only runs :class / @click / x-show (sidebar collapse, mobile drawer, backdrop) inside an x-data root. The admin layout does the same on its <body>. --}}
<body class="vd-booting h-full antialiased bg-[#FBF7F2] text-[#2B1730]" data-skeleton="{{ $skeletonVariant }}" x-data="{}">

{{-- Loading screen: a skeleton of the shell itself. states.js lifts it once the page is ready; the timer is a safety net. --}}
@include('logistics.partials.boot-skeleton', ['variant' => $skeletonVariant, 'railItems' => $railItems])
<script>
    window.setTimeout(function () {
        var s = document.getElementById('vd-boot');
        if (s) s.remove();
        document.body.classList.remove('vd-booting');
    }, 8000);
</script>

{{-- Thin progress bar while a link opens another page. --}}
<div class="vd-progress" id="lg-progress" aria-hidden="true"><span></span></div>

<a href="#main-content" class="lg-skip">Skip to main content</a>

{{-- Top banners: window.logisticsBanner({ type, title, message, actionLabel, onAction }) in resources/js/logistics/banner.js.
     Session messages come from <x-logistics.flash />. --}}
<div class="lgb-host" id="lgBanners" aria-live="polite"></div>

{{-- Toasts: any page can run $dispatch('lg-toast', { type: 'success' | 'error' | 'info', message: '...' }).
     They are shown as top banners so every message looks the same; the old stack below is only the fallback if the banner script did not load. --}}
<div class="lg-toasts" role="status" aria-live="polite"
     x-data="{
        items: [],
        add(d) {
            if (window.logisticsBanner) { window.logisticsBanner({ type: d.type || 'info', message: d.message }); return; }
            const id = Date.now() + Math.random();
            this.items.push({ id, type: d.type || 'info', message: d.message });
            setTimeout(() => this.remove(id), 5000);
        },
        remove(id) { this.items = this.items.filter(i => i.id !== id); }
     }"
     @lg-toast.window="add($event.detail)">
    <template x-for="t in items" :key="t.id">
        <div class="lg-toast" :class="'lg-toast--' + t.type">
            <span class="lg-toast__icon" aria-hidden="true">
                <x-logistics.icon name="check-circle" :size="20" x-show="t.type === 'success'" />
                <x-logistics.icon name="alert" :size="20" x-show="t.type === 'error'" x-cloak />
                <x-logistics.icon name="info" :size="20" x-show="t.type === 'info'" x-cloak />
            </span>
            <p x-text="t.message"></p>
            <button type="button" class="lg-toast__close" @click="remove(t.id)" aria-label="Dismiss message"><x-logistics.icon name="x" :size="16" /></button>
        </div>
    </template>
</div>

<div class="flex h-full overflow-hidden">

    {{-- Spacer holds the column open at the pinned width --}}
    <div :class="$store.sidebar.collapsed ? 'lg:w-[80px]' : 'lg:w-[272px]'" class="hidden lg:block flex-shrink-0 transition-[width] duration-500 ease-vendo"></div>

    <div x-show="$store.sidebar.mobileOpen" x-cloak @click="$store.sidebar.close()"
         x-transition:enter="transition-opacity duration-300 ease-vendo" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity duration-300 ease-vendo" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-[#2B1730]/50 z-30 lg:hidden"></div>

    <aside id="sidebar" @mouseenter="$store.sidebar.hoverOn()" @mouseleave="$store.sidebar.hoverOff()" aria-label="Logistics navigation"
        :class="[
            $store.sidebar.expanded ? 'lg:w-[272px]' : 'lg:w-[80px]',
            $store.sidebar.collapsed && $store.sidebar.hovering ? 'lg:shadow-[12px_0_40px_-18px_rgba(43,23,48,0.55)]' : '',
            $store.sidebar.mobileOpen ? 'translate-x-0 shadow-2xl' : '-translate-x-full lg:translate-x-0',
        ]"
        class="fixed inset-y-0 left-0 z-40 w-[272px] flex flex-col bg-[#3b1735] text-white transition-[width,transform,box-shadow] duration-500 ease-vendo overflow-hidden">

        <div class="h-[72px] flex-shrink-0 flex items-center relative px-4 border-b border-white/10">
            <a href="{{ $home }}" @click="$store.sidebar.close()" aria-label="Vendo logistics dashboard" class="relative flex items-center h-full w-full focus-visible:outline-none">
                <img src="{{ asset('assets/branding/log-in-logo.svg') }}" alt="Vendo"
                    :class="$store.sidebar.expanded ? 'opacity-100 scale-100' : 'opacity-0 scale-95 pointer-events-none'"
                    class="absolute left-3 top-1/2 -translate-y-1/2 h-11 w-auto max-w-[180px] object-contain object-left origin-left transition-all duration-300 ease-vendo">
                <img src="{{ asset('images/logo/vendo-icon.png') }}" alt="Vendo"
                    :class="$store.sidebar.expanded ? 'opacity-0 scale-90 pointer-events-none' : 'opacity-100 scale-100'"
                    class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 w-9 h-9 object-contain transition-all duration-300 ease-vendo">
            </a>
            <button type="button" @click="$store.sidebar.close()" class="lg:hidden absolute right-4 top-1/2 -translate-y-1/2 text-white/70 hover:text-white" aria-label="Close navigation">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>

        {{-- Who am I signed in as: shows the hub name under the logo --}}
        <div :class="$store.sidebar.expanded ? 'opacity-100 h-14' : 'opacity-0 h-0'" class="overflow-hidden px-6 flex flex-col justify-center transition-all duration-300 ease-vendo">
            <p class="truncate text-sm font-semibold">{{ $orgName }}</p>
            <p class="text-xs text-white/50">{{ $roleLabel }}</p>
        </div>

        <nav class="flex flex-1 flex-col px-3 pb-3 pt-2 overflow-y-auto overflow-x-hidden thin-scroll">
            <div class="my-auto">
            @foreach ($nav as $group)
                <div class="{{ $loop->first ? '' : 'mt-3' }}">
                    @if ($group['label'])
                        <p :class="$store.sidebar.expanded ? 'opacity-100 h-6' : 'opacity-0 h-0'" class="overflow-hidden px-3 pt-1 text-xs font-medium text-white/50 transition-all duration-300 ease-vendo">{{ $group['label'] }}</p>
                        <div :class="$store.sidebar.expanded ? 'opacity-0 h-0' : 'opacity-100 h-px mb-2'" class="mx-3 bg-white/10 transition-all duration-300 ease-vendo" aria-hidden="true"></div>
                    @endif
                    <div class="space-y-0.5">
                    @foreach ($group['items'] as [$label, $icon, $href, $active, $badge, $shape])
                        <a href="{{ $href }}" data-skeleton="{{ $shape }}" @click="$store.sidebar.close()" title="{{ $label }}" @if ($active) aria-current="page" @endif
                           class="{{ $linkBase }} {{ $active ? 'bg-white/[0.14]' : 'hover:bg-white/[0.07] active:bg-white/10' }}">
                            @if ($active)<span aria-hidden="true" class="absolute left-0 top-2 bottom-2 w-[3px] rounded-r-full bg-[#e8c874]"></span>@endif
                            <span class="relative flex h-6 w-10 flex-shrink-0 items-center justify-center {{ $active ? 'text-white' : 'text-white/75 group-hover:text-white' }}">
                                <x-logistics.icon :name="$icon" :size="20" />
                                @if ($badge > 0)<span x-show="!$store.sidebar.expanded" aria-hidden="true" class="absolute right-1 top-0 h-2 w-2 rounded-full bg-[#e8c874] ring-2 ring-[#3b1735]"></span>@endif
                            </span>
                            <span :class="$store.sidebar.expanded ? 'opacity-100' : 'lg:opacity-0'" class="min-w-0 flex-1 whitespace-nowrap text-sm leading-tight {{ $active ? 'font-semibold text-white' : 'font-medium text-white/85' }} transition-opacity duration-300 ease-vendo">{{ $label }}</span>
                            @if ($badge > 0)
                                <span :class="$store.sidebar.expanded ? 'opacity-100' : 'lg:opacity-0'" class="flex h-5 min-w-5 flex-shrink-0 items-center justify-center rounded-full bg-[#e8c874] px-1.5 text-[11px] font-bold tabular-nums text-[#2B1730] transition-opacity duration-300 ease-vendo">
                                    <span class="sr-only">{{ $badge }} waiting</span><span aria-hidden="true">{{ $badge }}</span>
                                </span>
                            @endif
                        </a>
                    @endforeach
                    </div>
                </div>
            @endforeach
            </div>
        </nav>

        <div class="px-3 pb-3 pt-2 flex-shrink-0 border-t border-white/10">
            <form method="POST" action="{{ route('logistics.logout') }}">@csrf
                <button type="submit" title="Log out" class="{{ $linkBase }} hover:bg-white/[0.07]">
                    <span class="flex h-6 w-10 flex-shrink-0 items-center justify-center"><img src="{{ asset('assets/icons/dashboard/logout-menu.svg') }}" alt="" class="lg-logout-img h-5 w-5 brightness-0 invert opacity-75 group-hover:opacity-100"></span>
                    <span :class="$store.sidebar.expanded ? 'opacity-100' : 'lg:opacity-0'" class="min-w-0 flex-1 whitespace-nowrap text-left text-sm font-medium text-white/85 transition-opacity duration-300 ease-vendo">Log out</span>
                </button>
            </form>
        </div>
    </aside>

    <div class="flex-1 flex flex-col min-w-0">
        <header class="h-[72px] flex-shrink-0 bg-[#FBF7F2] border-b border-[#ece4ec] flex items-center justify-between gap-4 px-4 sm:px-6 z-20">
            <div class="flex min-w-0 items-center gap-2">
                <button type="button" @click="$store.sidebar.toggle()" aria-label="Toggle navigation"
                    class="w-10 h-10 -ml-2 flex-shrink-0 rounded-xl flex items-center justify-center text-[#3b1735] hover:bg-[#f1e9f1] transition-colors duration-200 ease-vendo focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16" /></svg>
                </button>
                <nav aria-label="Breadcrumb" class="hidden min-w-0 items-center gap-2 sm:flex">
                    <span class="hidden truncate text-sm text-gray-500 xl:inline">{{ $orgName }}</span>
                    <span class="hidden text-gray-300 xl:inline" aria-hidden="true">/</span>
                    <span class="truncate font-display text-[15px] font-semibold" aria-current="page">{{ $title }}</span>
                </nav>
            </div>

            <div class="flex items-center gap-3 sm:gap-5">
                @if ($facility && $facility->is_active)
                    <div data-facility-scanner data-scan-url="{{ route($role === 'province' ? 'lgp.province.scans.store' : 'lgp.station.scans.store') }}">
                        <button type="button" class="lg-btn lg-btn--sm" data-scanner-open>Scan parcel</button>
                        <div class="lg-scan-overlay" data-scanner-modal hidden>
                            <div class="lg-scan-dialog" role="dialog" aria-modal="true" aria-labelledby="portalScannerTitle">
                                <div class="lg-scan-head"><div><h2 id="portalScannerTitle">Scan parcel</h2><p>{{ $facility->name }} · {{ $facility->municipality }}</p></div><button type="button" class="lg-scan-close" data-scanner-close aria-label="Close scanner" title="Close scanner"><span aria-hidden="true">×</span></button></div>
                                <div class="lg-scan-body">
                                    <div class="lg-scan-camera"><video data-scanner-video muted playsinline aria-label="Camera preview"></video><div class="lg-scan-target" aria-hidden="true"></div></div>
                                    <p class="lg-scan-help">Aim at the shipping label's QR code or barcode. Scans process automatically.</p>
                                    <p class="lg-scan-message" data-scanner-message role="status">Allow camera access. Scans are recorded automatically.</p>
                                    <form class="lg-scan-form" data-scanner-form>
                                        <div class="lg-field"><label for="portalScanTracking">Tracking number</label><input id="portalScanTracking" class="lg-input" data-scanner-tracking autocomplete="off" required placeholder="Scan label or enter number and press Enter"></div>
                                        <div class="lg-scan-actions"><button type="button" class="lg-btn lg-btn--outline" data-scanner-restart>Use camera</button></div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
                <div class="relative" x-data="{
                    open: false,
                    alerts: @js($initialAlerts),
                    unreadCount: @js($alertCount),
                    latestId: @js($initialAlerts->max('id') ?? 0),
                    refreshError: false,
                    ringing: false,
                    timer: null,
                    init() {
                        this.timer = window.setInterval(() => this.refresh(), 20000);
                        document.addEventListener('visibilitychange', () => {
                            if (document.visibilityState === 'visible') this.refresh();
                        });
                    },
                    ring() {
                        this.ringing = false;
                        this.$nextTick(() => { this.ringing = true; });
                    },
                    async refresh() {
                        if (document.visibilityState !== 'visible') return;
                        try {
                            const response = await fetch(@js(route('lgp.notifications.feed')), {
                                headers: { 'Accept': 'application/json' }, credentials: 'same-origin', cache: 'no-store'
                            });
                            if (!response.ok) throw new Error('Refresh failed');
                            const feed = await response.json();
                            const newest = feed.items.reduce((max, item) => Math.max(max, Number(item.id)), 0);
                            if (newest > this.latestId) {
                                const latest = feed.items.find((item) => Number(item.id) === newest);
                                this.ring();
                                window.logisticsBanner?.({
                                    type: 'info', title: latest.title, message: latest.message,
                                    actionLabel: 'View details', onAction: () => window.lgOpenNotification?.(latest.open_url), timeout: 8000
                                });
                                this.latestId = newest;
                            }
                            this.alerts = feed.items;
                            this.unreadCount = feed.unread_count;
                            this.refreshError = false;
                        } catch (_) {
                            if (!this.refreshError) {
                                window.logisticsBanner?.({
                                    type: 'error', title: 'Notifications are not updating',
                                    message: 'Check your internet connection. This page tries again every 20 seconds.'
                                });
                            }
                            this.refreshError = true;
                        }
                    }
                }" @keydown.escape.window="open = false">
                    <button type="button" id="lgBellBtn" @click="open = !open" @click.outside="open = false" aria-haspopup="menu" :aria-expanded="open"
                        :class="{ 'is-ringing': ringing }" @animationend="if ($event.target.classList.contains('lg-bell-img')) ringing = false"
                        :aria-label="'Notifications, ' + unreadCount + ' unread'"
                        class="relative flex h-10 w-10 items-center justify-center rounded-full transition-colors hover:bg-[#f1e9f1] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40">
                        <img src="{{ asset('assets/icons/dashboard/notifications-icon.svg') }}" alt="" class="lg-bell-img h-6 w-6">
                        <span x-show="unreadCount > 0" x-cloak x-text="unreadCount > 99 ? '99+' : unreadCount" aria-hidden="true" class="lgb-count absolute -right-1 -top-1"></span>
                    </button>
                    <div x-show="open" x-cloak x-transition.opacity.duration.150ms role="menu" aria-label="Notifications"
                        class="lgb-menu absolute right-0 z-50 mt-2 w-[min(20rem,calc(100vw-2rem))] origin-top-right rounded-2xl border border-[#ece4ec] bg-white py-1.5 shadow-[0_18px_40px_-20px_rgba(43,23,48,0.4)]">
                        <p class="px-4 pb-1.5 pt-2 text-xs font-medium text-gray-500">Needs your attention</p>
                        <template x-for="alert in alerts" :key="alert.id">
                            <form method="POST" :action="alert.open_url">@csrf
                                <button type="submit" role="menuitem" class="block w-full px-4 py-2.5 text-left hover:bg-[#F7F1F7] focus-visible:bg-[#F7F1F7] focus-visible:outline-none">
                                    <span class="block text-sm font-semibold text-[#2B1730]" x-text="alert.title"></span>
                                    <span class="block text-xs text-gray-600" x-text="alert.message"></span>
                                </button>
                            </form>
                        </template>
                        <p x-show="!alerts.length" class="px-4 py-3 text-sm text-gray-500">No new notifications.</p>
                        <p x-show="refreshError" x-cloak class="lgb-menu__error px-4 py-2 text-xs">Can't load notifications. Check your internet connection and wait a moment.</p>
                        <a href="{{ route('lgp.notifications.index') }}" class="block border-t border-[#ece4ec] px-4 py-2.5 text-sm font-medium text-[#3b1735] hover:bg-[#F7F1F7]">View all notifications</a>
                    </div>
                </div>

                <div class="relative" x-data="{ open: false }" @keydown.escape.window="open = false">
                    <button type="button" @click="open = !open" @click.outside="open = false" aria-haspopup="menu" :aria-expanded="open"
                        class="flex items-center gap-3 rounded-full pr-1 py-1 hover:bg-[#f1e9f1] transition-colors duration-200 ease-vendo focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40">
                        <div class="w-10 h-10 rounded-full bg-[#3b1735] text-white flex items-center justify-center flex-shrink-0 font-semibold">{{ $initial }}</div>
                        <div class="hidden sm:block text-sm text-left leading-tight pr-2">
                            <p class="font-semibold">{{ $orgName }}</p>
                            <p class="text-xs text-gray-500">{{ $roleLabel }}</p>
                        </div>
                    </button>
                    <div x-show="open" x-cloak role="menu"
                        class="lgb-menu absolute right-0 mt-2 w-52 bg-white rounded-2xl border border-[#ece4ec] py-1.5 z-50 shadow-[0_18px_40px_-20px_rgba(43,23,48,0.4)] origin-top-right">
                        <a href="{{ route('lgp.'.$role.'.account') }}" role="menuitem" class="block px-4 py-2.5 text-sm text-gray-700 hover:bg-[#F7F1F7]">Account settings</a>
                        <form method="POST" action="{{ route('logistics.logout') }}">@csrf
                            <button type="submit" role="menuitem" class="lgb-menu__danger w-full text-left px-4 py-2.5 text-sm">Log out</button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <div class="relative flex-1 overflow-hidden">
            <main id="main-content" tabindex="-1" class="vd-main h-full overflow-y-auto thin-scroll focus:outline-none">
                <div class="lg-body" style="min-height:0;background:transparent"><div class="lg-content">{{ $slot }}</div></div>
            </main>

            {{-- Page-to-page loading skeleton. Sits over #main-content; states.js shows it when a sidebar link is slow to open. --}}
            <div class="vd-nav-skeleton" aria-hidden="true">
                <x-logistics.skeleton variant="auto" />
            </div>
        </div>
    </div>
</div>
</body>
</html>
