@props(['title' => 'Logistics'])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('shared.site-icon')
    <title>{{ $title }} — Vendo Logistics</title>
    @vite(['resources/css/shared/app.css', 'resources/css/admin/layout.css', 'resources/css/logistics/workspace.css', 'resources/js/shared/app.js'])
</head>
<body class="bg-[#FBF7F2] text-[#2B1730]">
    <div class="min-h-screen lg:flex">
        <aside class="bg-[#3b1735] text-white lg:w-[272px] lg:min-h-screen lg:shrink-0">
            <div class="flex items-center justify-between border-b border-white/15 px-5 py-5">
                <a href="{{ route('logistics.dashboard') }}" class="flex items-center gap-3 text-lg font-semibold">
                    <img src="{{ asset('images/logo/vendo-icon.png') }}" alt="" class="h-9 w-9 object-contain">
                    <span>Vendo Logistics</span>
                </a>
                <span class="text-xs text-white/70">Main Hub</span>
            </div>
            <nav class="flex gap-1 overflow-x-auto px-3 py-3 text-sm lg:flex-col" aria-label="Logistics navigation">
                @foreach([
                    ['Dashboard', 'logistics.dashboard'],
                    ['Hubs & Stations', 'logistics.facilities.index'],
                    ['Rider Management', 'logistics.riders.index'],
                    ['Incoming Parcels', 'logistics.incoming-parcels'],
                    ['Parcel Sorting', 'logistics.parcel-sorting'],
                    ['Delivery Assignments', 'logistics.delivery-assignments'],
                    ['Delivery Monitoring', 'logistics.delivery-monitoring'],
                    ['Reports', 'logistics.reports'],
                    ['Messages', 'logistics.messages'],
                    ['Notifications', 'logistics.notifications.index'],
                    ['Account Management', 'logistics.account.index'],
                ] as [$label, $routeName])
                    <a href="{{ route($routeName) }}"
                        @if(request()->routeIs($routeName)) aria-current="page" @endif
                        class="shrink-0 rounded-lg px-3 py-2.5 transition-colors hover:bg-white/10 focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#DAC582] {{ request()->routeIs($routeName) ? 'bg-white/15 font-semibold' : 'text-white/80' }}">
                        {{ $label }}
                    </a>
                @endforeach
                <form action="{{ route('logistics.logout') }}" method="POST" class="shrink-0">@csrf
                    <button type="submit" class="w-full rounded-lg px-3 py-2.5 text-left text-white/80 hover:bg-white/10">Log out</button>
                </form>
            </nav>
        </aside>
        <main class="min-w-0 flex-1 px-4 py-6 sm:px-8 lg:py-9">
            @if(session('success'))<p role="status" class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('success') }}</p>@endif
            @if($errors->any())<div role="alert" class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">{{ $errors->first() }}</div>@endif
            {{ $slot }}
        </main>
    </div>
</body>
</html>
