<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Admin' }} - Vendo</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700&display=swap"
        rel="stylesheet">

    @vite(['resources/css/shared/app.css', 'resources/js/admin/sidebar.js', 'resources/js/shared/app.js'])
    <link rel="stylesheet" href="{{ asset('assets/css/notification-actions.css') }}">

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.snow.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.min.js"></script>

    <script>
        setInterval(function() {
            fetch('{{ route('admin.check-status') }}')
                .then(r => r.json())
                .then(data => {
                    if (!data.active) {
                        window.location.href = '{{ route('admin.login') }}';
                    }
                })
                .catch(() => {});
        }, 5000);
    </script>

    <style>
        body { font-family: 'Inter', ui-sans-serif, system-ui, sans-serif; }
        h1, h2, h3, h4, .font-display { font-family: 'Poppins', 'Inter', sans-serif; }

        /* One shared easing so every panel, pill and label moves the same way */
        .ease-vendo { transition-timing-function: cubic-bezier(0.16, 1, 0.3, 1); }

        .admin-bell-menu[hidden] { display: none; }
        .thin-scroll::-webkit-scrollbar { width: 6px; height: 6px; }
        .thin-scroll::-webkit-scrollbar-thumb { background: #e2dbe4; border-radius: 999px; }
        .thin-scroll::-webkit-scrollbar-track { background: transparent; }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                transition-duration: 0.01ms !important;
                animation-duration: 0.01ms !important;
            }
        }
    </style>
</head>

<body class="h-full antialiased bg-[#FBF7F2] text-[#2B1730]" x-data="{ loading: false }"
    @ajax:before.window="loading = true" @ajax:after.window="loading = false">

    @php
        $admin = Auth::guard('admin')->user();
        $adminNotificationCount = \App\Models\Communication\Notification::whereNull('user_id')->where('type', '!=', 'new_order')->whereNull('read_at')->count();
        $latestAdminNotificationId = \App\Models\Communication\Notification::whereNull('user_id')->where('type', '!=', 'new_order')->max('id') ?? 0;
        $recentAdminNotifications = \App\Models\Communication\Notification::whereNull('user_id')->where('type', '!=', 'new_order')->latest()->limit(5)->get();
    @endphp

    <div class="flex h-full overflow-hidden">

        @include('admin.partials.sidebar')

        <div class="flex-1 flex flex-col min-w-0">

            <!-- Top Bar -->
            <header
                class="h-16 sm:h-[72px] flex-shrink-0 bg-[#FBF7F2]/90 backdrop-blur border-b border-[#ece4ec]
                       flex items-center justify-between gap-4 px-4 sm:px-6 z-20">

                <button type="button" @click="$store.sidebar.toggle()"
                    class="w-10 h-10 -ml-2 rounded-xl flex items-center justify-center text-[#3b1735]
                           hover:bg-[#f1e9f1] transition-colors duration-200 ease-vendo"
                    aria-label="Toggle navigation">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16" />
                    </svg>
                </button>

                <div class="flex items-center gap-3 sm:gap-5">

                    <div class="relative" id="adminBellWrap">
                        <button type="button" id="adminBellButton" aria-label="Notifications" aria-controls="adminBellMenu" aria-expanded="false"
                            class="relative flex h-10 w-10 items-center justify-center rounded-full transition-colors hover:bg-[#f1e9f1]">
                            <img src="{{ asset('assets/icons/dashboard/notifications-icon.svg') }}" alt="" class="h-6 w-6">
                            <span id="adminNotificationCount" class="absolute -right-1 -top-1 rounded-full bg-red-600 px-1.5 text-[10px] text-white" @if(!$adminNotificationCount) hidden @endif>{{ $adminNotificationCount }}</span>
                        </button>
                        <div id="adminBellMenu" class="admin-bell-menu absolute right-0 top-full z-50 mt-2 w-[min(350px,calc(100vw-2rem))] overflow-hidden rounded-xl border border-gray-100 bg-white text-gray-900 shadow-xl" hidden>
                            <div class="flex items-center justify-between border-b px-4 py-3"><strong class="text-sm">Notifications</strong><div class="notification-menu-actions"><form method="POST" action="{{ route('admin.notifications.read-all') }}">@csrf<button type="submit">Mark all as read</button></form><a href="{{ route('admin.notifications.index') }}">View all</a></div></div>
                            <div id="adminBellList" class="max-h-[min(60dvh,430px)] overflow-y-auto">@include('admin.notifications.recent', ['notifications' => $recentAdminNotifications])</div>
                        </div>
                    </div>

                    <div class="relative" x-data="{ open: false }">
                        <button type="button" @click="open = !open" @click.outside="open = false"
                            class="flex items-center gap-3 rounded-full pr-1 py-1 hover:bg-[#f1e9f1]
                                   transition-colors duration-200 ease-vendo">
                            <div class="w-10 h-10 rounded-full bg-[#3b1735] text-white flex items-center justify-center
                                        overflow-hidden flex-shrink-0 font-semibold">
                                @if ($admin->profile_picture)
                                    <img src="{{ asset('storage/' . $admin->profile_picture) }}"
                                        class="w-full h-full object-cover">
                                @else
                                    {{ strtoupper(substr($admin->first_name ?? $admin->name, 0, 1)) }}
                                @endif
                            </div>
                            <div class="hidden sm:block text-sm text-left leading-tight pr-2">
                                <p class="font-semibold text-[#2B1730]">{{ $admin->first_name ?? $admin->name }}</p>
                                <p class="text-xs text-gray-500">
                                    {{ $admin->is_super_admin ? 'Super Administrator' : 'Administrator' }}
                                </p>
                            </div>
                        </button>

                        <div x-show="open" x-cloak
                            x-transition:enter="transition duration-200 ease-vendo"
                            x-transition:enter-start="opacity-0 -translate-y-1 scale-95"
                            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                            x-transition:leave="transition duration-150 ease-vendo"
                            x-transition:leave-start="opacity-100 scale-100"
                            x-transition:leave-end="opacity-0 scale-95"
                            class="absolute right-0 mt-2 w-52 bg-white rounded-2xl border border-gray-100 py-1.5 z-50
                                   shadow-[0_18px_40px_-20px_rgba(43,23,48,0.4)] origin-top-right">
                            <a href="{{ route('admin.account-management.index') }}" x-target.push="main-content sidebar"
                                @click="open = false"
                                class="block px-4 py-2.5 text-sm text-gray-700 hover:bg-[#F7F1F7] transition-colors duration-200">
                                Account Settings
                            </a>
                            <form method="POST" action="{{ route('admin.logout') }}">
                                @csrf
                                <button type="submit"
                                    class="w-full text-left px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition-colors duration-200">
                                    Log out
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <div class="relative flex-1 overflow-hidden">
                <main id="main-content" class="h-full overflow-y-auto thin-scroll">
                    {{ $slot }}
                </main>

                <div x-show="loading" x-cloak x-transition.opacity
                    class="absolute inset-0 z-20 flex flex-col items-center justify-center gap-4 bg-[#FBF7F2]/90 backdrop-blur-sm">
                    <div class="w-10 h-10 border-4 border-[#3b1735]/20 border-t-[#3b1735] rounded-full animate-spin"></div>
                </div>
            </div>

        </div>
    </div>

<script>
        (() => {
            const bellWrap = document.getElementById('adminBellWrap');
            const bellButton = document.getElementById('adminBellButton');
            const bellMenu = document.getElementById('adminBellMenu');
            const closeBell = () => { bellMenu.hidden = true; bellButton.setAttribute('aria-expanded', 'false'); };
            bellButton.addEventListener('click', () => {
                const opening = bellMenu.hidden;
                bellMenu.hidden = !opening;
                bellButton.setAttribute('aria-expanded', String(opening));
            });
            document.addEventListener('click', event => { if (!bellWrap.contains(event.target)) closeBell(); });
            document.addEventListener('keydown', event => { if (event.key === 'Escape' && !bellMenu.hidden) { closeBell(); bellButton.focus(); } });
            let latestId = @json($latestAdminNotificationId);
            let audio;
            function sound() {
                try {
                    audio ||= new (window.AudioContext || window.webkitAudioContext)();
                    audio.resume();
                    const oscillator = audio.createOscillator();
                    const gain = audio.createGain();
                    oscillator.frequency.value = 880;
                    gain.gain.setValueAtTime(0.07, audio.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.001, audio.currentTime + 0.16);
                    oscillator.connect(gain).connect(audio.destination);
                    oscillator.start(); oscillator.stop(audio.currentTime + 0.17);
                } catch (_) {}
            }
            async function refreshAdminNotifications() {
                if (document.hidden) return;
                try {
                    const response = await fetch(@json(route('admin.notifications.recent')), {headers: {'Accept': 'application/json'}});
                    if (!response.ok) return;
                    const data = await response.json();
                    const badge = document.getElementById('adminNotificationCount');
                    if (badge) { badge.hidden = !data.unread_count; badge.textContent = data.unread_count > 99 ? '99+' : data.unread_count; }
                    if (data.html) document.getElementById('adminBellList').innerHTML = data.html;
                    if (data.latest_id > latestId) sound();
                    latestId = Math.max(latestId, data.latest_id);
                } catch (_) {}
            }
            setInterval(refreshAdminNotifications, 3000);
            document.addEventListener('visibilitychange', () => { if (!document.hidden) refreshAdminNotifications(); });
        })();
    </script>
    @include('shared.message-delete-dialog')
    @include('shared.live-revision-script')
</body>

</html>
