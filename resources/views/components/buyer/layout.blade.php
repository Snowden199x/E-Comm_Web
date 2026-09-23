<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Vendo — Buyer' }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/shared/app.css', 'resources/js/shared/app.js'])
</head>

<body class="bg-gray-50">
    <div x-data="{ accountOpen: false }">

        <!-- Top bar -->
        <header class="bg-[#3b1735] text-white">
            <div class="max-w-7xl mx-auto px-4 py-3 flex items-center gap-4">
                <a href="{{ route('buyer.dashboard') }}" class="shrink-0">
                    <img src="{{ asset('assets/branding/vendo-logo.svg') }}" class="h-8">
                </a>

                <form action="#" class="flex-1 flex">
                    <input type="text" placeholder="Search products..."
                        class="w-full rounded-l-lg px-4 py-2 text-gray-900 text-sm focus:outline-none">
                    <button class="bg-[#e8c874] px-4 rounded-r-lg">
                        <img src="{{ asset('assets/icons/registration/search-icon.svg') }}" class="w-4 h-4">
                    </button>
                </form>

                <a href="{{ route('buyer.cart.index') }}" class="relative shrink-0">
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

                <div class="relative shrink-0">
                    <button @click="accountOpen = !accountOpen" class="flex items-center gap-2">
                        <img src="{{ asset('assets/icons/dashboard/user-icon.svg') }}" class="w-6 h-6">
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

        <main>
            {{ $slot }}
        </main>
    </div>
</body>

</html>
