@props(['title', 'role' => 'station'])

@php
    // HARDCODED PREVIEW: replace $identity with auth()->user()->hub / ->company later.
    $identity = [
        'company'  => ['JNT Express Philippines', 'Company Portal'],
        'province' => ['JNT Laguna Hub',          'Province Hub'],
        'station'  => ['JNT Santa Cruz Station',  'Municipality Station'],
    ][$role];
    [$orgName, $roleLabel] = $identity;
    $initial = mb_strtoupper(mb_substr($orgName, 0, 1));
    $page = request()->route('page');
    $hubUrl = fn (string $p) => route('lgp.hub.page', [$role, $p]);
    $home = $role === 'company' ? route('lgp.company') : route('lgp.hub.'.$role);

    // [label, icon, url, active, badge]
    $nav = $role === 'company' ? [
        ['label' => null, 'items' => [['Dashboard', 'home', route('lgp.company'), request()->routeIs('lgp.company'), 0]]],
        ['label' => 'Network', 'items' => [
            ['Hubs', 'map-pin', route('lgp.company.hubs'), request()->routeIs('lgp.company.hubs'), 0],
            ['Hub Managers', 'user-cog', route('lgp.company.managers'), request()->routeIs('lgp.company.managers'), 0],
        ]],
        ['label' => 'Business', 'items' => [['Reports', 'bar-chart', route('lgp.company.reports'), request()->routeIs('lgp.company.reports'), 0]]],
        ['label' => 'System', 'items' => [['Account Management', 'shield', route('lgp.company.account'), request()->routeIs('lgp.company.account'), 0]]],
    ] : [
        ['label' => null, 'items' => [['Dashboard', 'home', $home, request()->routeIs('lgp.hub.'.$role), 0]]],
        ['label' => 'People', 'items' => [['Rider Management', 'users', $hubUrl('riders'), $page === 'riders', 3]]],
        ['label' => 'Parcels', 'items' => array_values(array_filter([
            ['Incoming Parcels', 'inbox', $hubUrl('incoming'), $page === 'incoming', 12],
            ['Parcel Sorting', 'layers', $hubUrl('sorting'), $page === 'sorting', 0],
            $role === 'province' ? ['Linehaul', 'truck', route('lgp.hub.linehaul'), request()->routeIs('lgp.hub.linehaul'), 0] : null,
            $role === 'station' ? ['Delivery Assignments', 'truck', $hubUrl('assignments'), $page === 'assignments', 0] : null,
            ['Delivery Monitoring', 'map-pin', $hubUrl('monitoring'), $page === 'monitoring', 0],
        ]))],
        ['label' => 'Business', 'items' => [['Reports', 'bar-chart', $hubUrl('reports'), $page === 'reports', 0]]],
        ['label' => 'System', 'items' => [['Account Management', 'shield', $hubUrl('account'), $page === 'account', 0]]],
    ];

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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    {{-- Same sidebar store, layout css and motion as the admin; workspace.css only styles the page content (cards, tables, pills). --}}
    @vite(['resources/css/shared/app.css', 'resources/css/admin/layout.css', 'resources/css/logistics/workspace.css', 'resources/js/admin/sidebar.js', 'resources/js/shared/app.js'])
</head>
<body class="h-full antialiased bg-[#FBF7F2] text-[#2B1730]">
<div class="flex h-full overflow-hidden">

    {{-- Spacer holds the column open at the pinned width --}}
    <div :class="$store.sidebar.collapsed ? 'lg:w-[80px]' : 'lg:w-[272px]'" class="hidden lg:block flex-shrink-0 transition-[width] duration-500 ease-vendo"></div>

    <div x-show="$store.sidebar.mobileOpen" x-cloak @click="$store.sidebar.close()" class="fixed inset-0 bg-[#2B1730]/50 z-30 lg:hidden"></div>

    <aside id="sidebar" @mouseenter="$store.sidebar.hoverOn()" @mouseleave="$store.sidebar.hoverOff()" aria-label="Logistics navigation"
        :class="[
            $store.sidebar.expanded ? 'lg:w-[272px]' : 'lg:w-[80px]',
            $store.sidebar.collapsed && $store.sidebar.hovering ? 'lg:shadow-[12px_0_40px_-18px_rgba(43,23,48,0.55)]' : '',
            $store.sidebar.mobileOpen ? 'translate-x-0 shadow-2xl' : '-translate-x-full lg:translate-x-0',
        ]"
        class="fixed inset-y-0 left-0 z-40 w-[272px] flex flex-col bg-[#3b1735] text-white transition-[width,transform,box-shadow] duration-500 ease-vendo overflow-hidden">

        <div class="h-[72px] flex-shrink-0 flex items-center relative px-4 border-b border-white/10">
            <a href="{{ $home }}" aria-label="Vendo logistics dashboard" class="relative flex items-center h-full w-full focus-visible:outline-none">
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
                    @foreach ($group['items'] as [$label, $icon, $href, $active, $badge])
                        <a href="{{ $href }}" title="{{ $label }}" @if ($active) aria-current="page" @endif
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
                <button type="submit" title="Logout" class="{{ $linkBase }} hover:bg-white/[0.07]">
                    <span class="flex h-6 w-10 flex-shrink-0 items-center justify-center"><img src="{{ asset('assets/icons/dashboard/logout-menu.svg') }}" alt="" class="h-5 w-5 brightness-0 invert opacity-75 group-hover:opacity-100"></span>
                    <span :class="$store.sidebar.expanded ? 'opacity-100' : 'lg:opacity-0'" class="min-w-0 flex-1 whitespace-nowrap text-left text-sm font-medium text-white/85 transition-opacity duration-300 ease-vendo">Logout</span>
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
                <p class="hidden min-w-0 truncate font-display text-[15px] font-semibold sm:block">{{ $title }}</p>
            </div>

            <div class="flex items-center gap-3 sm:gap-5">
                {{-- PREVIEW ONLY: delete this block when real auth is wired --}}
                <div class="hidden md:flex items-center gap-1.5 text-xs">
                    <span class="text-gray-500">Preview as</span>
                    @foreach (['company' => ['Company', route('lgp.company')], 'province' => ['Province hub', route('lgp.hub.province')], 'station' => ['Station', route('lgp.hub.station')]] as $key => [$l, $u])
                        <a href="{{ $u }}" class="rounded-full px-2.5 py-1 font-medium {{ $role === $key ? 'bg-[#3b1735] text-white' : 'bg-[#f1e9f1] text-[#3b1735] hover:bg-[#e8dce8]' }}">{{ $l }}</a>
                    @endforeach
                </div>

                <button type="button" aria-label="Notifications" class="relative flex h-10 w-10 items-center justify-center rounded-full transition-colors hover:bg-[#f1e9f1] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40">
                    <img src="{{ asset('assets/icons/dashboard/notifications-icon.svg') }}" alt="" class="h-6 w-6">
                    <span class="absolute -right-1 -top-1 rounded-full bg-red-600 px-1.5 text-[10px] text-white">2</span>
                </button>

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
                        class="absolute right-0 mt-2 w-52 bg-white rounded-2xl border border-[#ece4ec] py-1.5 z-50 shadow-[0_18px_40px_-20px_rgba(43,23,48,0.4)] origin-top-right">
                        <a href="{{ $role === 'company' ? route('lgp.company.account') : $hubUrl('account') }}" role="menuitem" class="block px-4 py-2.5 text-sm text-gray-700 hover:bg-[#F7F1F7]">Account Settings</a>
                        <form method="POST" action="{{ route('logistics.logout') }}">@csrf
                            <button type="submit" role="menuitem" class="w-full text-left px-4 py-2.5 text-sm text-red-600 hover:bg-red-50">Log out</button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <div class="relative flex-1 overflow-hidden">
            <main id="main-content" class="h-full overflow-y-auto thin-scroll">
                <div class="lg-body" style="min-height:0;background:transparent"><div class="lg-content">{{ $slot }}</div></div>
            </main>
        </div>
    </div>
</div>
</body>
</html>