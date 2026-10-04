@props(['title'])

@php
    $user = auth()->user();
    $center = $user->logisticsCenterDetail;
    $centerName = $center?->business_name ?: $user->name;
    $initial = mb_strtoupper(mb_substr($centerName, 0, 1));

    // Rider Management lives on the dashboard route today. If a dedicated riders
    // route is added later, the link switches to it automatically.
    $ridersRoute = \Illuminate\Support\Facades\Route::has('logistics.riders.index');

    $nav = [
        ['label' => null, 'items' => [
            ['Dashboard', 'home', route('logistics.dashboard'), request()->routeIs('logistics.dashboard')],
        ]],
        ['label' => 'People', 'items' => [
            ['Rider Management', 'users', $ridersRoute ? route('logistics.riders.index') : route('logistics.dashboard').'#rider-applications', $ridersRoute && request()->routeIs('logistics.riders.*')],
        ]],
        ['label' => 'Parcels', 'items' => [
            ['Incoming Parcels', 'inbox', route('logistics.incoming-parcels'), request()->routeIs('logistics.incoming-parcels')],
            ['Parcel Sorting', 'layers', route('logistics.parcel-sorting'), request()->routeIs('logistics.parcel-sorting')],
            ['Delivery Assignments', 'truck', route('logistics.delivery-assignments'), request()->routeIs('logistics.delivery-assignments')],
            ['Delivery Monitoring', 'map-pin', route('logistics.delivery-monitoring'), request()->routeIs('logistics.delivery-monitoring')],
        ]],
        ['label' => 'Insights', 'items' => [
            ['Reports', 'bar-chart', route('logistics.reports'), request()->routeIs('logistics.reports')],
            ['Messages', 'message', route('logistics.messages'), request()->routeIs('logistics.messages')],
        ]],
        ['label' => 'Settings', 'items' => [
            ['Account Management', 'user-cog', route('logistics.account.index'), request()->routeIs('logistics.account.*')],
        ]],
    ];

    // Flash messages are shown here once for every Logistics page.
    $toasts = [];
    if (session('success')) {
        $toasts[] = ['tone' => 'success', 'text' => session('success')];
    }
    if (session('confirmation') === 'approved') {
        $toasts[] = ['tone' => 'success', 'text' => 'Rider approved. They can now log in to their account.'];
    } elseif (session('confirmation') === 'rejected') {
        $toasts[] = ['tone' => 'info', 'text' => 'Rider application rejected.'];
    }
    if ($errors->any()) {
        $toasts[] = ['tone' => 'error', 'text' => $errors->first(), 'sticky' => true];
    }
@endphp

<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} | Vendo Logistics</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/shared/app.css', 'resources/css/logistics/workspace.css', 'resources/js/shared/app.js', 'resources/js/logistics/workspace.js'])
</head>
<body class="lg-body">
    <a class="lg-skip" href="#lgMain">Skip to content</a>

    <aside class="lg-sidebar" id="lgSidebar" aria-label="Logistics navigation">
        <a href="{{ route('logistics.dashboard') }}" class="lg-brand" aria-label="Vendo Logistics dashboard">
            <img class="lg-brand-icon" src="{{ asset('images/logo/vendo-icon.png') }}" alt="">
            <img class="lg-brand-full" src="{{ asset('assets/branding/log-in-logo.svg') }}" alt="Vendo">
        </a>

        <div class="lg-center">
            <span class="lg-center__avatar" aria-hidden="true">{{ $initial }}</span>
            <span class="lg-center__text">
                <strong>{{ $centerName }}</strong>
                <small>Logistics Center</small>
            </span>
        </div>

        <nav class="lg-nav" aria-label="Logistics sections">
            @foreach ($nav as $group)
                @if ($group['label'])
                    <p class="lg-nav__section">{{ $group['label'] }}</p>
                @endif
                @foreach ($group['items'] as [$label, $icon, $href, $active])
                    <a href="{{ $href }}" class="lg-nav-item" title="{{ $label }}" @if ($active) aria-current="page" @endif>
                        <x-logistics.icon :name="$icon" />
                        <span class="lg-nav-label">{{ $label }}</span>
                    </a>
                @endforeach
            @endforeach
        </nav>

        <form method="POST" action="{{ route('logistics.logout') }}" class="lg-logout">
            @csrf
            <button type="submit" class="lg-nav-item" title="Logout">
                <x-logistics.icon name="log-out" />
                <span class="lg-nav-label">Logout</span>
            </button>
        </form>
    </aside>

    <button type="button" class="lg-backdrop" data-lg-backdrop aria-label="Close navigation"></button>

    <div class="lg-main">
        <header class="lg-topbar">
            <button type="button" class="lg-menu-button" data-lg-toggle aria-controls="lgSidebar" aria-expanded="true" aria-label="Toggle navigation">
                <x-logistics.icon name="menu" :size="22" />
            </button>

            <nav class="lg-crumbs" aria-label="Breadcrumb">
                <span>Logistics</span>
                <span aria-hidden="true">/</span>
                <strong>{{ $title }}</strong>
            </nav>

            <span class="lg-topbar__spacer"></span>

            <span class="lg-date">{{ now()->format('l, F j') }}</span>

            <div class="lg-account" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false">
                <button type="button" class="lg-account-trigger" @click="open = !open" :aria-expanded="open.toString()" aria-haspopup="menu">
                    <span class="lg-avatar" aria-hidden="true">{{ $initial }}</span>
                    <span>
                        <strong>{{ $centerName }}</strong>
                        <small>Logistics Center</small>
                    </span>
                    <x-logistics.icon name="chevron-down" :size="16" />
                </button>

                <div class="lg-menu" role="menu" x-show="open" x-cloak
                     x-transition:enter="lg-t-enter" x-transition:enter-start="lg-t-enter-start" x-transition:enter-end="lg-t-enter-end"
                     x-transition:leave="lg-t-leave" x-transition:leave-start="lg-t-leave-start" x-transition:leave-end="lg-t-leave-end">
                    <a href="{{ route('logistics.account.index') }}" role="menuitem">
                        <x-logistics.icon name="user-cog" :size="18" /> Account Management
                    </a>
                    <hr>
                    <form method="POST" action="{{ route('logistics.logout') }}">
                        @csrf
                        <button type="submit" role="menuitem"><x-logistics.icon name="log-out" :size="18" /> Logout</button>
                    </form>
                </div>
            </div>
        </header>

        @if ($toasts)
            <div class="lg-toasts" aria-live="polite">
                @foreach ($toasts as $toast)
                    <div class="lg-toast lg-toast--{{ $toast['tone'] }}" role="{{ $toast['tone'] === 'error' ? 'alert' : 'status' }}" @unless ($toast['sticky'] ?? false) data-timeout="5000" @endunless>
                        <x-logistics.icon :name="$toast['tone'] === 'error' ? 'alert' : ($toast['tone'] === 'success' ? 'check-circle' : 'info')" :size="20" />
                        <p>{{ $toast['text'] }}</p>
                        <button type="button" class="lg-toast__close" aria-label="Dismiss message"><x-logistics.icon name="x" :size="16" /></button>
                    </div>
                @endforeach
            </div>
        @endif

        <main class="lg-content" id="lgMain" tabindex="-1">{{ $slot }}</main>
    </div>
</body>
</html>