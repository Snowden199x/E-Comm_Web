@php
    $navItems = [
        ['route' => 'admin.dashboard',                  'active' => 'admin.dashboard',              'icon' => 'dashboard-menu.svg',           'label' => 'Dashboard'],
        ['route' => 'admin.registrations.index',        'active' => 'admin.registrations.*',        'icon' => 'registrations-menu.svg',       'label' => 'Registrations'],
        ['route' => 'admin.user-management.index',      'active' => 'admin.user-management.*',      'icon' => 'user-management-menu.svg',     'label' => 'User Management'],
        ['route' => 'admin.seller-compliance.overview', 'active' => 'admin.seller-compliance.*',    'icon' => 'seller-compliance-menu.svg',   'label' => 'Seller Compliance'],
        ['route' => 'admin.complaints.index',           'active' => 'admin.complaints.*',           'icon' => 'complaints-disputes-menu.svg', 'label' => 'Complaints and Disputes'],
        ['route' => 'admin.commission.index',           'active' => 'admin.commission.*',           'icon' => 'commission-menu.svg',          'label' => 'Commission'],
        ['route' => 'admin.reports.index',              'active' => 'admin.reports.*',              'icon' => 'reports-menu.svg',             'label' => 'Reports'],
        ['route' => 'admin.platform-settings.index',    'active' => 'admin.platform-settings.*',    'icon' => 'platform-settings-menu.svg',   'label' => 'Platform Settings'],
        ['route' => 'admin.messages.index',             'active' => 'admin.messages.*',             'icon' => 'messages-menu.svg',            'label' => 'Messages'],
        ['route' => 'admin.account-management.index',   'active' => 'admin.account-management.*',   'icon' => 'account-management-menu.svg',  'label' => 'Account Management'],
    ];
@endphp

{{-- Spacer: holds the layout column open at the PINNED width, so a hover-peek
     floats over the page instead of shoving the content sideways. --}}
<div :class="$store.sidebar.collapsed ? 'lg:w-[88px]' : 'lg:w-[272px]'"
    class="hidden lg:block flex-shrink-0 transition-[width] duration-500 ease-vendo"></div>

<!-- Mobile backdrop -->
<div x-show="$store.sidebar.mobileOpen" x-cloak @click="$store.sidebar.close()"
    x-transition:enter="transition-opacity duration-300 ease-vendo" x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity duration-300 ease-vendo"
    x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
    class="fixed inset-0 bg-[#2B1730]/50 backdrop-blur-[2px] z-30 lg:hidden"></div>

<aside id="sidebar" @mouseenter="$store.sidebar.hoverOn()" @mouseleave="$store.sidebar.hoverOff()"
    :class="[
        $store.sidebar.expanded ? 'lg:w-[272px]' : 'lg:w-[88px]',
        $store.sidebar.collapsed && $store.sidebar.hovering ? 'lg:shadow-[12px_0_40px_-18px_rgba(43,23,48,0.55)]' : '',
        $store.sidebar.mobileOpen ? 'translate-x-0 shadow-2xl' : '-translate-x-full lg:translate-x-0',
    ]"
    class="fixed inset-y-0 left-0 z-40 w-[272px] flex flex-col
           bg-gradient-to-b from-[#4a1f42] via-[#3b1735] to-[#2c0f28] text-white
           transition-[width,transform,box-shadow] duration-500 ease-vendo overflow-hidden">

    <!-- Brand -->
        <div class="h-[72px] flex-shrink-0 flex items-center relative px-4">

            <a href="{{ route('admin.dashboard') }}"
                x-target.push="main-content sidebar"
                @click="$store.sidebar.close()"
                class="relative flex items-center h-full w-full">

                <!-- FULL VENDO LOGO -->
                <img src="{{ asset('assets/branding/log-in-logo.svg') }}"
                    alt="Vendo"
                    :class="$store.sidebar.expanded
                        ? 'opacity-100 scale-100'
                        : 'opacity-0 scale-95 pointer-events-none'"
                    class="absolute left-2 top-1/2
                        -translate-y-1/2
                        w-[180px] h-auto
                        max-w-none
                        object-contain object-left
                        origin-left
                        transition-all duration-300 ease-vendo">

                <!-- COLLAPSED VENDO ICON -->
                <img src="{{ asset('images/logo/vendo-icon.png') }}"
                    alt="Vendo"
                    :class="$store.sidebar.expanded
                        ? 'opacity-0 scale-90 pointer-events-none'
                        : 'opacity-100 scale-100'"
                    class="absolute left-1/2 top-1/2
                        -translate-x-1/2 -translate-y-1/2
                        w-10 h-10
                        object-contain
                        transition-all duration-300 ease-vendo">

            </a>

            <!-- Mobile close -->
            <button type="button"
                @click="$store.sidebar.close()"
                class="lg:hidden absolute right-4 top-1/2 -translate-y-1/2
                    text-white/70 hover:text-white"
                aria-label="Close navigation">

                <svg class="w-5 h-5"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" d="M6 18L18 6M6 6l12 12" />
                </svg>

            </button>

        </div>

    <!-- Menu -->
    <nav class="flex-1 px-3 py-3 space-y-1 overflow-y-auto overflow-x-hidden thin-scroll">
        @foreach ($navItems as $item)
            @php $isActive = request()->routeIs($item['active']); @endphp

            <a href="{{ route($item['route']) }}" x-target.push="main-content sidebar"
                @click="$store.sidebar.close()"
                class="group flex items-center gap-3 h-12 w-full px-3 rounded-2xl overflow-hidden
                       transition-colors duration-300 ease-vendo
                       {{ $isActive ? 'bg-[#7d5580] shadow-[0_8px_20px_-12px_rgba(0,0,0,0.65)]' : 'hover:bg-white/[0.07] active:bg-white/10' }}">

                <span class="w-10 h-6 flex items-center justify-center flex-shrink-0">
                    <img src="{{ asset('assets/icons/dashboard/' . $item['icon']) }}" alt=""
                        class="w-[22px] h-[22px] brightness-0 invert {{ $isActive ? 'opacity-100' : 'opacity-80' }}">
                </span>

                <span :class="$store.sidebar.expanded ? 'opacity-100' : 'lg:opacity-0'"
                    class="flex-1 min-w-0 truncate text-[15px] leading-snug
                           {{ $isActive ? 'font-semibold' : 'font-medium text-white/85' }}
                           transition-opacity duration-300 ease-vendo">
                    {{ $item['label'] }}
                </span>
            </a>
        @endforeach
    </nav>

    <!-- Logout -->
    <div class="px-3 pb-5 pt-2 flex-shrink-0">
        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf
            <button type="submit"
                class="flex items-center gap-3 h-12 w-full px-3 rounded-2xl overflow-hidden
                       hover:bg-white/[0.07] transition-colors duration-300 ease-vendo">
                <span class="w-10 h-6 flex items-center justify-center flex-shrink-0">
                    <img src="{{ asset('assets/icons/dashboard/logout-menu.svg') }}" alt=""
                        class="w-[22px] h-[22px] brightness-0 invert opacity-80">
                </span>
                <span :class="$store.sidebar.expanded ? 'opacity-100' : 'lg:opacity-0'"
                    class="flex-1 min-w-0 truncate text-left text-[15px] font-medium text-white/85
                           transition-opacity duration-300 ease-vendo">
                    Logout
                </span>
            </button>
        </form>
    </div>
</aside>