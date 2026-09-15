<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Admin' }} - Vendo</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.snow.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.min.js"></script>
</head>

<body class="antialiased bg-[#faf6f0]" x-data="{ sidebarOpen: false, loading: false }" @ajax:before.window="loading = true"
    @ajax:after.window="loading = false" x-init="setInterval(() => {
        fetch('{{ route('admin.session.status') }}', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin'
            })
            .then(response => {
                if (response.status === 401 || response.status === 403) {
                    window.location.href = '{{ route('admin.login') }}';
                    return null;
                }
    
                return response.json();
            })
            .then(data => {
                if (data && data.active === false) {
                    window.location.href = '{{ route('admin.login') }}';
                }
            })
            .catch(() => {});
    }, 3000);">
    <div class="flex min-h-screen">

        <!-- Sidebar (fixed drawer on mobile, static column on desktop) -->
        <div id="sidebar" x-cloak :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            class="fixed inset-y-0 left-0 z-40 w-64 transform transition-transform duration-200 ease-in-out lg:sticky lg:top-0 lg:h-screen lg:translate-x-0 lg:z-auto">
            @include('admin.partials.sidebar')
        </div>

        <!-- Mobile backdrop overlay -->
        <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false" class="fixed inset-0 bg-black/40 z-30 lg:hidden">
        </div>

        <div class="flex-1 flex flex-col min-w-0">

            <!-- Top Bar -->
            <header
                class="sticky top-0 z-30 flex items-center justify-between px-8 py-4 bg-[#faf6f0] border-b border-gray-200">
                <button class="p-2 lg:hidden" @click="sidebarOpen = !sidebarOpen">
                    <img src="{{ asset('assets/icons/dashboard/sidebar-menu-icon.svg') }}" alt="Menu"
                        class="w-6 h-6">
                </button>

                <div class="flex items-center gap-6 ml-auto">
                    <button class="p-2">
                        <img src="{{ asset('assets/icons/dashboard/notifications-icon.svg') }}" alt="Notifications"
                            class="w-6 h-6">
                    </button>

                    @php $admin = Auth::guard('admin')->user(); @endphp
                    <div class="relative" x-data="{ open: false }">
                        <button type="button" @click="open = !open" @click.outside="open = false"
                            class="flex items-center gap-3">
                            <div
                                class="w-9 h-9 rounded-full bg-gray-200 overflow-hidden flex items-center justify-center text-xs font-bold text-gray-600">
                                @if ($admin->profile_picture)
                                    <img src="{{ asset('storage/' . $admin->profile_picture) }}"
                                        class="w-full h-full object-cover">
                                @else
                                    <img src="{{ asset('assets/icons/dashboard/user-icon.svg') }}" alt=""
                                        class="w-full h-full p-1.5">
                                @endif
                            </div>
                            <div class="text-sm text-left">
                                <p class="font-semibold text-gray-900">{{ $admin->name }}</p>
                                <p class="text-gray-500">
                                    {{ $admin->is_super_admin ? 'Super Administrator' : 'Administrator' }}</p>
                            </div>
                        </button>

                        <div x-show="open" x-cloak x-transition
                            class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-100 py-1 z-50">
                            <a href="{{ route('admin.account-management.index') }}"
                                x-target.push="main-content sidebar" @click="open = false"
                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                Account Settings
                            </a>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <div class="relative flex-1 overflow-hidden">
                <main id="main-content" class="h-full overflow-y-auto">
                    {{ $slot }}
                </main>
                <div x-show="loading" x-cloak x-transition.opacity
                    class="absolute inset-0 z-20 flex flex-col items-center justify-center gap-4 bg-[#faf6f0]/90 backdrop-blur-sm">
                    <div class="w-10 h-10 border-4 border-[#3b1735]/20 border-t-[#3b1735] rounded-full animate-spin">
                    </div>
                </div>
            </div>
        </div>
</body>

</html>
