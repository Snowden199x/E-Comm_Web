<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Admin' }} - Vendo</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="antialiased bg-[#faf6f0]" x-data="{ sidebarOpen: false }">
    <div class="flex min-h-screen">

        <!-- Sidebar (fixed drawer on mobile, static column on desktop) -->
        <div x-cloak :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            class="fixed inset-y-0 left-0 z-40 w-64 transform transition-transform duration-200 ease-in-out lg:sticky lg:top-0 lg:h-screen lg:translate-x-0 lg:z-auto">
            @include('admin.partials.sidebar')
        </div>

        <!-- Mobile backdrop overlay -->
        <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false" class="fixed inset-0 bg-black/40 z-30 lg:hidden">
        </div>

        <div class="flex-1 flex flex-col min-w-0">

            <!-- Top Bar -->
            <header class="flex items-center justify-between px-8 py-4 bg-[#faf6f0] border-b border-gray-200">
                <button class="p-2 lg:hidden" @click="sidebarOpen = !sidebarOpen">
                    <img src="{{ asset('assets/icons/dashboard/sidebar-menu-icon.svg') }}" alt="Menu"
                        class="w-6 h-6">
                </button>

                <div class="flex items-center gap-6 ml-auto">
                    <button class="p-2">
                        <img src="{{ asset('assets/icons/dashboard/notifications-icon.svg') }}" alt="Notifications"
                            class="w-6 h-6">
                    </button>

                    <button class="p-2">
                        <img src="{{ asset('assets/icons/dashboard/message-icon.svg') }}" alt="Messages"
                            class="w-6 h-6">
                    </button>

                    <div class="flex items-center gap-3">
                        <img src="{{ asset('assets/icons/dashboard/user-icon.svg') }}" alt=""
                            class="w-9 h-9 rounded-full bg-gray-200 p-1.5">
                        <div class="text-sm">
                            <p class="font-semibold text-gray-900">{{ Auth::user()->name }}</p>
                            <p class="text-gray-500">Super Administrator</p>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1">
                {{ $slot }}
            </main>

        </div>

    </div>
</body>

</html>
