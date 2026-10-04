@php
    // 'icon' is the file name inside public/assets/icons/buyer/
    $navItems = [
        ['route' => 'buyer.dashboard',      'active' => ['buyer.dashboard'],                                   'icon' => 'dashboard-icon.svg',          'label' => 'Dashboard'],
        ['route' => 'buyer.categories',     'active' => ['buyer.categories', 'buyer.products.*'],              'icon' => 'categories-icon.svg',         'label' => 'Categories'],
        ['route' => 'buyer.orders.index',   'active' => ['buyer.orders.*'],                                    'icon' => 'my-orders-icon.svg',          'label' => 'My Orders'],
        ['route' => 'buyer.messages.index', 'active' => ['buyer.messages.*', 'buyer.marketplace-messages.*'],  'icon' => 'messages-icon.svg',          'label' => 'Messages'],
        ['route' => 'buyer.account.index',  'active' => ['buyer.account.*'],                                   'icon' => 'account-management-icon.svg', 'label' => 'Account Management'],
    ];
@endphp

{{-- Spacer: keeps the page column open at the PINNED width so a hover-peek
     floats over the page instead of pushing the content sideways. --}}
<div :class="$store.buyerSidebar.collapsed ? 'lg:w-[104px]' : 'lg:w-[308px]'"
    class="hidden flex-shrink-0 transition-[width] duration-500 ease-vendo lg:block"></div>

<!-- Mobile backdrop -->
<div x-show="$store.buyerSidebar.mobileOpen" x-cloak @click="$store.buyerSidebar.close()"
    x-transition:enter="transition-opacity duration-300 ease-vendo" x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity duration-300 ease-vendo"
    x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-30 bg-[#2B1730]/50 backdrop-blur-[2px] lg:hidden"></div>

<aside id="buyer-sidebar" @mouseenter="$store.buyerSidebar.hoverOn()" @mouseleave="$store.buyerSidebar.hoverOff()"
    :class="[
        $store.buyerSidebar.expanded ? 'lg:w-[308px]' : 'lg:w-[104px]',
        $store.buyerSidebar.collapsed && $store.buyerSidebar.hovering ? 'lg:shadow-[12px_0_40px_-18px_rgba(43,23,48,0.55)]' : '',
        $store.buyerSidebar.mobileOpen ? 'translate-x-0 shadow-2xl' : '-translate-x-full lg:translate-x-0',
    ]"
    class="fixed inset-y-0 left-0 z-40 flex w-[308px] max-w-[85vw] flex-col overflow-hidden bg-[#402143] text-white transition-[width,transform,box-shadow] duration-500 ease-vendo"
    aria-label="Main navigation">

    <!-- Brand -->
    <div class="relative h-[160px] flex-shrink-0 lg:h-[271px]">
        <a href="{{ route('buyer.dashboard') }}" @click="$store.buyerSidebar.close()" class="block h-full w-full"
            aria-label="Vendo home">

            <!-- Full logo -->
            <img src="{{ asset('assets/branding/log-in-logo.svg') }}" alt="Vendo"
                :class="$store.buyerSidebar.expanded ? 'opacity-100 scale-100' : 'opacity-0 scale-95 pointer-events-none'"
                class="absolute left-6 top-7 h-auto w-[252px] max-w-none origin-left object-contain object-left transition-all duration-300 ease-vendo">

            <!-- Collapsed icon -->
            <img src="{{ asset('images/logo/vendo-icon.png') }}" alt=""
                :class="$store.buyerSidebar.expanded ? 'opacity-0 scale-90 pointer-events-none' : 'opacity-100 scale-100'"
                class="absolute left-6 top-7 h-14 w-14 object-contain transition-all duration-300 ease-vendo">
        </a>

        <!-- Mobile close -->
        <button type="button" @click="$store.buyerSidebar.close()"
            class="absolute right-3 top-3 grid h-10 w-10 place-items-center rounded-full text-white/70 transition-colors duration-200 hover:bg-white/10 hover:text-white lg:hidden"
            aria-label="Close navigation">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <!-- Menu -->
    <nav class="vb-thin-scroll flex-1 overflow-y-auto overflow-x-hidden px-6">
        @foreach ($navItems as $item)
            @php $isActive = request()->routeIs(...$item['active']); @endphp

            <a href="{{ route($item['route']) }}" @click="$store.buyerSidebar.close()"
                @if ($isActive) aria-current="page" @endif
                :title="$store.buyerSidebar.expanded ? null : '{{ $item['label'] }}'"
                class="group flex h-16 w-full items-center gap-[14px] overflow-hidden rounded-[10px] px-4 transition-colors duration-300 ease-vendo
                       {{ $isActive ? 'bg-[#805487] text-white' : 'text-white/55 hover:bg-white/[0.07] hover:text-white active:bg-white/10' }}">

                <span class="vb-icon h-6 w-6 group-hover:scale-110"
                    style="--icon: url('{{ asset('assets/icons/buyer/' . $item['icon']) }}')"></span>

                <span :class="$store.buyerSidebar.expanded ? 'opacity-100' : 'lg:opacity-0'"
                    class="min-w-0 flex-1 whitespace-nowrap text-[16px] leading-6 transition-opacity duration-300 ease-vendo {{ $isActive ? 'font-medium' : 'font-normal' }}">
                    {{ $item['label'] }}
                </span>
            </a>
        @endforeach
    </nav>

    <!-- Logout -->
    <div class="flex-shrink-0 px-6 pb-9 pt-2">
        <form method="POST" action="{{ route('buyer.logout') }}">
            @csrf
            <button type="submit"
                :title="$store.buyerSidebar.expanded ? null : 'Logout'"
                class="group flex h-16 w-full items-center gap-[14px] overflow-hidden rounded-[10px] px-4 text-white/55 transition-colors duration-300 ease-vendo hover:bg-white/[0.07] hover:text-white active:bg-white/10">

                <span class="vb-icon h-6 w-6 group-hover:translate-x-0.5"
                    style="--icon: url('{{ asset('assets/icons/buyer/logout-icon.svg') }}')"></span>

                <span :class="$store.buyerSidebar.expanded ? 'opacity-100' : 'lg:opacity-0'"
                    class="min-w-0 flex-1 whitespace-nowrap text-left text-[16px] font-normal leading-6 transition-opacity duration-300 ease-vendo">
                    Logout
                </span>
            </button>
        </form>
    </div>
</aside>