@props(['title'])

@php
    $icon = fn (string $file) => asset('assets/icons/seller/'.$file);
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
    @vite(['resources/css/shared/app.css', 'resources/css/logistics/workspace.css', 'resources/js/shared/app.js'])
</head>
<body class="lg-body" x-data="{ navOpen: false, navPinned: {{ request()->routeIs('logistics.dashboard') ? 'false' : 'true' }} }" @keydown.escape.window="navOpen = false">
    <aside class="lg-sidebar" id="logisticsNavigation" :class="{ 'is-open': navOpen, 'is-pinned': navPinned }" aria-label="Logistics navigation">
        <a href="{{ route('logistics.dashboard') }}" class="lg-brand" aria-label="Vendo Logistics Rider Management">
            <img class="lg-brand-icon" src="{{ asset('images/logo/vendo-icon.png') }}" alt="">
            <img class="lg-brand-full" src="{{ asset('assets/branding/log-in-logo.svg') }}" alt="Vendo">
        </a>
        <p class="lg-center-name">{{ auth()->user()->logisticsCenterDetail?->business_name ?? 'Logistics center' }}</p>
        <nav class="lg-nav" aria-label="Logistics sections">
            <a href="{{ route('logistics.dashboard') }}" class="lg-nav-item" @if(request()->routeIs('logistics.dashboard')) aria-current="page" @endif title="Rider Management"><img src="{{ $icon('dashboard-icon.png') }}" alt=""><span>Rider Management</span></a>
            <a href="{{ route('logistics.incoming-parcels') }}" class="lg-nav-item" @if(request()->routeIs('logistics.incoming-parcels')) aria-current="page" @endif title="Incoming Parcel Management"><img src="{{ $icon('ready-for-pickup-icon.png') }}" alt=""><span>Incoming Parcel Management</span></a>
            <a href="{{ route('logistics.parcel-sorting') }}" class="lg-nav-item" @if(request()->routeIs('logistics.parcel-sorting')) aria-current="page" @endif title="Parcel Sorting"><img src="{{ $icon('products-inventory--icon.png') }}" alt=""><span>Parcel Sorting</span></a>
            <details class="lg-nav-group" @if(request()->routeIs('logistics.delivery-*')) open @endif>
                <summary class="lg-nav-item" title="Delivery"><img src="{{ $icon('pending-deliveries-icon.png') }}" alt=""><span>Delivery</span><span class="lg-chevron" aria-hidden="true">⌄</span></summary>
                <div class="lg-submenu">
                    <a href="{{ route('logistics.delivery-assignments') }}" class="lg-nav-item lg-nav-item--sub" @if(request()->routeIs('logistics.delivery-assignments')) aria-current="page" @endif title="Delivery assignments"><img src="{{ $icon('shipments-icon.png') }}" alt=""><span>Delivery assignments</span></a>
                    <a href="{{ route('logistics.delivery-monitoring') }}" class="lg-nav-item lg-nav-item--sub" @if(request()->routeIs('logistics.delivery-monitoring')) aria-current="page" @endif title="Delivery Monitoring"><img src="{{ $icon('completed-orders-icon.png') }}" alt=""><span>Delivery Monitoring</span></a>
                </div>
            </details>
            <a href="{{ route('logistics.reports') }}" class="lg-nav-item" @if(request()->routeIs('logistics.reports')) aria-current="page" @endif title="Reports"><img src="{{ $icon('reports-icon.png') }}" alt=""><span>Reports</span></a>
            <a href="{{ route('logistics.messages') }}" class="lg-nav-item" @if(request()->routeIs('logistics.messages')) aria-current="page" @endif title="Messages"><img src="{{ $icon('messages-icon.png') }}" alt=""><span>Messages</span></a>
            <a href="{{ route('logistics.account.index') }}" class="lg-nav-item" @if(request()->routeIs('logistics.account.*')) aria-current="page" @endif title="Account Management"><img src="{{ $icon('account-management-icon.png') }}" alt=""><span>Account Management</span></a>
        </nav>
        <form method="POST" action="{{ route('logistics.logout') }}" class="lg-logout">
            @csrf
            <button type="submit" class="lg-nav-item" title="Logout"><img src="{{ $icon('logout-icon.png') }}" alt=""><span>Logout</span></button>
        </form>
    </aside>
    <button type="button" class="lg-backdrop" x-show="navOpen" x-cloak @click="navOpen = false" aria-label="Close navigation"></button>
    <div class="lg-main" :class="{ 'is-pinned': navPinned }">
        <header class="lg-topbar">
            <button type="button" class="lg-menu-button" aria-controls="logisticsNavigation" :aria-expanded="(navOpen || navPinned).toString()" @click="window.matchMedia('(max-width: 900px)').matches ? navOpen = !navOpen : navPinned = !navPinned" aria-label="Toggle sidebar">
                <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" aria-hidden="true"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
            </button>
            <h1>{{ $title }}</h1>
            <a class="lg-topbar-account" href="{{ route('logistics.account.index') }}"><span class="lg-account-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr(auth()->user()->logisticsCenterDetail?->business_name ?: auth()->user()->name, 0, 1)) }}</span><span><strong>{{ auth()->user()->logisticsCenterDetail?->business_name }}</strong><small>Logistics Center</small></span></a>
        </header>
        <main class="lg-content">{{ $slot }}</main>
    </div>
</body>
</html>
