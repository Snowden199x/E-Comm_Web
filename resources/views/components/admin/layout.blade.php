@php
    /*
     * Loading skeleton shape per page. Keys are route-name patterns; anything not listed draws the table layout.
     * Pages can override with <x-admin.layout skeleton="form">. The sidebar uses the same map for its links.
     */
    $skeletonMap = [
        'admin.dashboard' => 'dashboard',
        'admin.reports.*' => 'dashboard',
        'admin.messages.*' => 'split',
        'admin.category-requests.*' => 'cards',
        'admin.logistics-documents.*' => 'cards',
        'admin.platform-settings.*' => 'form',
        'admin.account-management.*' => 'form',
    ];

    $skeletonVariant = $skeleton
        ?? collect($skeletonMap)->first(fn ($shape, $pattern) => request()->routeIs($pattern), 'table');
@endphp<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('shared.site-icon')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Admin' }} - Vendo</title>

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
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>

    {{-- Fonts and the Quill stylesheet must never hold up the first paint: they load without blocking and
         swap in when they arrive (the fallback stack is already in layout.css). --}}
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700&display=swap"
        rel="stylesheet" media="print" onload="this.media='all'">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.snow.min.css" rel="stylesheet"
        media="print" onload="this.media='all'">
    <noscript>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.snow.min.css" rel="stylesheet">
    </noscript>

    {{-- Chart.js and Quill are only used inside Alpine/nextTick callbacks, so they are deferred instead of
         blocking the page. They stay ABOVE @vite on purpose: deferred scripts run in document order, and the
         Chart/Quill globals must exist before Alpine starts and components initialise. --}}
    <script defer src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
    <script defer src="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.min.js"></script>

    @vite(['resources/css/shared/app.css', 'resources/css/admin/layout.css', 'resources/css/admin/sidebar.css', 'resources/css/admin/motion.css', 'resources/js/admin/sidebar.js', 'resources/js/admin/layout.js', 'resources/js/shared/app.js'])
    <link rel="stylesheet" href="{{ asset('assets/css/notification-actions.css') }}">

</head>

<body class="vd-booting h-full antialiased bg-[#FBF7F2] text-[#2B1730]"
    data-skeleton="{{ $skeletonVariant }}"
    data-check-status-url="{{ route('admin.check-status') }}" data-login-url="{{ route('admin.login') }}"
    x-data="{ loading: false }"
    @ajax:before.window="loading = true" @ajax:after.window="loading = false">

    {{-- Loading screen: a skeleton of the shell itself. motion.js lifts it once the page has loaded; the timer is a safety net. --}}
    @include('admin.partials.boot-skeleton', ['variant' => $skeletonVariant])
    <script>
        window.setTimeout(function () {
            var s = document.getElementById('vd-boot');
            if (s) s.remove();
            document.body.classList.remove('vd-booting');
        }, 8000);
    </script>

    {{-- Thin progress bar for in-app navigation (sidebar links swap content without a full reload). --}}
    <div class="vd-progress" :class="loading ? 'is-active' : ''" aria-hidden="true"><span></span></div>

    @php
        $admin = Auth::guard('admin')->user();
        $adminNotificationCount = \App\Models\Communication\Notification::whereNull('user_id')->where('type', '!=', 'new_order')->whereNull('read_at')->count();
        $latestAdminNotificationId = \App\Models\Communication\Notification::whereNull('user_id')->where('type', '!=', 'new_order')->max('id') ?? 0;
        $recentAdminNotifications = \App\Models\Communication\Notification::whereNull('user_id')->where('type', '!=', 'new_order')->latest()->limit(5)->get();
    @endphp

    <div class="flex h-full overflow-hidden">

        @include('admin.partials.sidebar', ['skeletonMap' => $skeletonMap])

        <div class="flex-1 flex flex-col min-w-0">

            <!-- Top Bar -->
            <header
                class="h-[72px] flex-shrink-0 bg-[#FBF7F2] border-b border-[#ece4ec]
                       flex items-center justify-between gap-4 px-4 sm:px-6 z-20">

                <div class="flex min-w-0 items-center gap-2">
                    <button type="button" @click="$store.sidebar.toggle()"
                        class="group w-10 h-10 -ml-2 flex-shrink-0 rounded-xl flex items-center justify-center text-[#3b1735]
                               hover:bg-[#f1e9f1] active:scale-90 transition duration-200 ease-vendo
                               focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40"
                        aria-label="Toggle navigation">
                        {{-- The middle bar shortens while the rail is open, so the icon answers the click. --}}
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" d="M4 7h16M4 17h16" />
                            <path stroke-linecap="round" d="M4 12h16"
                                :class="$store.sidebar.collapsed ? 'scale-x-100' : 'scale-x-[0.6]'"
                                class="origin-left [transform-box:fill-box] transition-transform duration-300 ease-vendo" />
                        </svg>
                    </button>

                    {{-- Always rendered: motion.js rewrites it after in-app navigation (the top bar is not swapped). --}}
                    <p id="admin-page-title" class="hidden min-w-0 truncate font-display text-[15px] font-semibold text-[#2B1730] sm:block">{{ $title ?? '' }}</p>
                </div>

                <div class="flex items-center gap-3 sm:gap-5">

                    <div class="relative" id="adminBellWrap" data-latest-id="{{ $latestAdminNotificationId }}"
                        data-recent-url="{{ route('admin.notifications.recent') }}">
                        <button type="button" id="adminBellButton" aria-label="Notifications" aria-controls="adminBellMenu" aria-expanded="false"
                            class="relative flex h-10 w-10 items-center justify-center rounded-full transition duration-200 hover:bg-[#f1e9f1] active:scale-90
                                   focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40">
                            <img src="{{ asset('assets/icons/dashboard/notifications-icon.svg') }}" alt="" class="h-6 w-6">
                            <span id="adminNotificationCount" class="absolute -right-1 -top-1 rounded-full bg-vd-danger-ink px-1.5 text-[10px] font-semibold text-white" @if(!$adminNotificationCount) hidden @endif>{{ $adminNotificationCount }}</span>
                        </button>
                        <div id="adminBellMenu" class="admin-bell-menu absolute right-0 top-full z-50 mt-2 w-[min(350px,calc(100vw-2rem))] overflow-hidden rounded-xl border border-vd-line bg-white text-vd-ink" hidden>
                            <div class="flex items-center justify-between border-b border-vd-line px-4 py-3"><strong class="text-sm">Notifications</strong><div class="notification-menu-actions"><form method="POST" action="{{ route('admin.notifications.read-all') }}">@csrf<button type="submit">Mark all as read</button></form><a href="{{ route('admin.notifications.index') }}">View all</a></div></div>
                            <div id="adminBellList" class="max-h-[min(60dvh,430px)] overflow-y-auto">@include('admin.notifications.recent', ['notifications' => $recentAdminNotifications])</div>
                        </div>
                    </div>

                    <div class="relative" x-data="{ open: false }" @keydown.escape.window="open = false">
                        <button type="button" @click="open = !open" @click.outside="open = false"
                            aria-haspopup="menu" :aria-expanded="open"
                            class="flex items-center gap-3 rounded-full pr-1 py-1 hover:bg-[#f1e9f1] active:scale-[0.98]
                                   transition duration-200 ease-vendo
                                   focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40">
                            <div class="w-10 h-10 rounded-full bg-[#3b1735] text-white flex items-center justify-center
                                        overflow-hidden flex-shrink-0 font-semibold">
                                @if ($admin->profile_picture)
                                    <img src="{{ asset('storage/' . $admin->profile_picture) }}" alt=""
                                        class="w-full h-full object-cover">
                                @else
                                    {{ strtoupper(substr($admin->first_name ?? $admin->name, 0, 1)) }}
                                @endif
                            </div>
                            <div class="hidden sm:block text-sm text-left leading-tight pr-2">
                                <p class="font-semibold text-[#2B1730]">{{ $admin->first_name ?? $admin->name }}</p>
                                <p class="text-xs text-vd-muted">
                                    {{ $admin->is_super_admin ? 'Super administrator' : 'Administrator' }}
                                </p>
                            </div>
                        </button>

                        <div x-show="open" x-cloak role="menu"
                            x-transition:enter="transition duration-200 ease-vendo"
                            x-transition:enter-start="opacity-0 -translate-y-1 scale-95"
                            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                            x-transition:leave="transition duration-150 ease-vendo"
                            x-transition:leave-start="opacity-100 scale-100"
                            x-transition:leave-end="opacity-0 scale-95"
                            class="absolute right-0 mt-2 w-52 bg-white rounded-2xl border border-vd-line py-1.5 z-50 origin-top-right">
                            <a href="{{ route('admin.account-management.index') }}" x-target.push="main-content sidebar"
                                data-skeleton="form" @click="open = false" role="menuitem"
                                class="block px-4 py-2.5 text-sm text-vd-ink hover:bg-vd-tint transition-colors duration-200">
                                Account settings
                            </a>
                            <form method="POST" action="{{ route('admin.logout') }}">
                                @csrf
                                <button type="submit" role="menuitem"
                                    class="w-full text-left px-4 py-2.5 text-sm text-vd-danger-ink hover:bg-vd-danger-bg transition-colors duration-200">
                                    Log out
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <div class="relative flex-1 overflow-hidden">
                <main id="main-content" tabindex="-1"
                    class="vd-main vd-enter h-full overflow-y-auto thin-scroll outline-none">
                    {{ $slot }}
                </main>

                {{-- Page-to-page loading skeleton. Sits over #main-content (never swapped); shown by motion.js when a navigation is slow. --}}
                <div class="vd-nav-skeleton" aria-hidden="true">
                    <x-admin.skeleton variant="auto" />
                </div>
            </div>

        </div>
    </div>

    @include('admin.partials.document-viewer')
    @include('shared.message-delete-dialog')
    @include('shared.live-revision-script')
</body>

</html>
