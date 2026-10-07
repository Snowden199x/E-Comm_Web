@php
    /*
     * Sidebar groups. Labels are written to fit the 272px sidebar on one line, and every
     * link also carries a title (the collapsed rail shows it as a tooltip).
     * 'badge' keys read a count prepared below; remove a key to hide its badge.
     */
    $pendingRegistrations = \App\Models\User::whereIn('role', ['seller', 'buyer', 'logistics_center'])->where('status', 'pending')->count();
    $productsForReview = \App\Models\Ecommerce\Product::where('status', 'for_review')->count();
    $openComplaints = \App\Models\Complaints\Complaint::where('status', 'open')->count();

    $navGroups = [
        [
            'label' => null,
            'items' => [
                ['route' => 'admin.dashboard', 'active' => 'admin.dashboard', 'icon' => 'dashboard-menu.svg', 'label' => 'Dashboard'],
            ],
        ],
        [
            'label' => 'Review',
            'items' => [
                ['route' => 'admin.registrations.index', 'active' => 'admin.registrations.*', 'icon' => 'registrations-menu.svg', 'label' => 'Registrations', 'badge' => $pendingRegistrations],
                ['route' => 'admin.user-management.index', 'active' => 'admin.user-management.*', 'icon' => 'user-management-menu.svg', 'label' => 'User Management'],
                ['route' => 'admin.seller-compliance.overview', 'active' => 'admin.seller-compliance.*', 'icon' => 'seller-compliance-menu.svg', 'label' => 'Seller Compliance', 'badge' => $productsForReview],
                ['route' => 'admin.complaints.index', 'active' => 'admin.complaints.*', 'icon' => 'complaints-disputes-menu.svg', 'label' => 'Complaints & Disputes', 'badge' => $openComplaints],
            ],
        ],
        [
            'label' => 'Business',
            'items' => [
                ['route' => 'admin.commission.index', 'active' => 'admin.commission.*', 'icon' => 'commission-menu.svg', 'label' => 'Commission'],
                ['route' => 'admin.reports.index', 'active' => 'admin.reports.*', 'icon' => 'reports-menu.svg', 'label' => 'Reports'],
                ['route' => 'admin.messages.index', 'active' => 'admin.messages.*', 'icon' => 'messages-menu.svg', 'label' => 'Messages'],
            ],
        ],
        [
            'label' => 'System',
            'items' => [
                ['route' => 'admin.platform-settings.index', 'active' => 'admin.platform-settings.*', 'icon' => 'platform-settings-menu.svg', 'label' => 'Platform Settings'],
                ['route' => 'admin.account-management.index', 'active' => 'admin.account-management.*', 'icon' => 'account-management-menu.svg', 'label' => 'Account Management'],
            ],
        ],
    ];

    $linkBase = 'group relative flex h-10 w-full items-center gap-3 overflow-hidden rounded-xl px-3
                 transition-colors duration-200 ease-vendo
                 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#e8c874]/70';
@endphp

{{-- Spacer: holds the layout column open at the PINNED width, so a hover-peek
     floats over the page instead of shoving the content sideways. --}}
<div :class="$store.sidebar.collapsed ? 'lg:w-[80px]' : 'lg:w-[272px]'"
    class="hidden lg:block flex-shrink-0 transition-[width] duration-500 ease-vendo"></div>

<!-- Mobile backdrop -->
<div x-show="$store.sidebar.mobileOpen" x-cloak @click="$store.sidebar.close()"
    x-transition:enter="transition-opacity duration-300 ease-vendo" x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity duration-300 ease-vendo"
    x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
    class="fixed inset-0 bg-[#2B1730]/50 z-30 lg:hidden"></div>

<aside id="sidebar" @mouseenter="$store.sidebar.hoverOn()" @mouseleave="$store.sidebar.hoverOff()"
    aria-label="Admin navigation"
    :class="[
        $store.sidebar.expanded ? 'lg:w-[272px]' : 'lg:w-[80px]',
        $store.sidebar.collapsed && $store.sidebar.hovering ? 'lg:shadow-[12px_0_40px_-18px_rgba(43,23,48,0.55)]' : '',
        $store.sidebar.mobileOpen ? 'translate-x-0 shadow-2xl' : '-translate-x-full lg:translate-x-0',
    ]"
    class="fixed inset-y-0 left-0 z-40 w-[272px] flex flex-col bg-[#3b1735] text-white
           transition-[width,transform,box-shadow] duration-500 ease-vendo overflow-hidden">

    <!-- Brand (same 72px height as the top bar, logo centred in it) -->
    <div class="h-[72px] flex-shrink-0 flex items-center relative px-4 border-b border-white/10">

        <a href="{{ route('admin.dashboard') }}" x-target.push="main-content sidebar"
            @click="$store.sidebar.close()" aria-label="Vendo admin dashboard"
            class="relative flex items-center h-full w-full focus-visible:outline-none">

            <img src="{{ asset('assets/branding/log-in-logo.svg') }}" alt="Vendo"
                :class="$store.sidebar.expanded ? 'opacity-100 scale-100' : 'opacity-0 scale-95 pointer-events-none'"
                class="absolute left-3 top-1/2 -translate-y-1/2 h-11 w-auto max-w-[180px]
                       object-contain object-left origin-left transition-all duration-300 ease-vendo">

            <img src="{{ asset('images/logo/vendo-icon.png') }}" alt="Vendo"
                :class="$store.sidebar.expanded ? 'opacity-0 scale-90 pointer-events-none' : 'opacity-100 scale-100'"
                class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 w-9 h-9 object-contain
                       transition-all duration-300 ease-vendo">
        </a>

        <!-- Mobile close -->
        <button type="button" @click="$store.sidebar.close()"
            class="lg:hidden absolute right-4 top-1/2 -translate-y-1/2 text-white/70 hover:text-white"
            aria-label="Close navigation">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <!-- Menu -->
    <nav class="flex flex-1 flex-col px-3 pb-3 pt-4 overflow-y-auto overflow-x-hidden thin-scroll">
        {{-- my-auto centres the whole list between the logo and Logout; on short screens it collapses and the list scrolls --}}
        <div class="my-auto">
        @foreach ($navGroups as $group)
            <div class="{{ $loop->first ? '' : 'mt-3' }}">
                @if ($group['label'])
                    {{-- Group name when open; a thin divider when the rail is collapsed. --}}
                    <p :class="$store.sidebar.expanded ? 'opacity-100 h-6' : 'opacity-0 h-0 lg:h-0'"
                        class="overflow-hidden px-3 pt-1 text-xs font-medium text-white/50 transition-all duration-300 ease-vendo">
                        {{ $group['label'] }}
                    </p>
                    <div :class="$store.sidebar.expanded ? 'opacity-0 h-0' : 'opacity-100 h-px mb-2'"
                        class="mx-3 bg-white/10 transition-all duration-300 ease-vendo" aria-hidden="true"></div>
                @endif

                <div class="space-y-0.5">
                    @foreach ($group['items'] as $item)
                        @php
                            $isActive = request()->routeIs($item['active']);
                            $badge = (int) ($item['badge'] ?? 0);
                        @endphp

                        <a href="{{ route($item['route']) }}" x-target.push="main-content sidebar"
                            @click="$store.sidebar.close()" title="{{ $item['label'] }}"
                            @if ($isActive) aria-current="page" @endif
                            class="{{ $linkBase }} {{ $isActive ? 'bg-white/[0.14]' : 'hover:bg-white/[0.07] active:bg-white/10' }}">

                            {{-- Gold marker on the active item --}}
                            @if ($isActive)
                                <span aria-hidden="true" class="absolute left-0 top-2 bottom-2 w-[3px] rounded-r-full bg-[#e8c874]"></span>
                            @endif

                            <span class="relative flex h-6 w-10 flex-shrink-0 items-center justify-center">
                                <img src="{{ asset('assets/icons/dashboard/' . $item['icon']) }}" alt=""
                                    class="h-5 w-5 brightness-0 invert {{ $isActive ? 'opacity-100' : 'opacity-75 group-hover:opacity-100' }}">

                                {{-- Collapsed rail: a dot stands in for the count --}}
                                @if ($badge > 0)
                                    <span x-show="!$store.sidebar.expanded" aria-hidden="true"
                                        class="absolute right-1 top-0 h-2 w-2 rounded-full bg-[#e8c874] ring-2 ring-[#3b1735]"></span>
                                @endif
                            </span>

                            <span :class="$store.sidebar.expanded ? 'opacity-100' : 'lg:opacity-0'"
                                class="min-w-0 flex-1 whitespace-nowrap text-sm leading-tight
                                       {{ $isActive ? 'font-semibold text-white' : 'font-medium text-white/85' }}
                                       transition-opacity duration-300 ease-vendo">
                                {{ $item['label'] }}
                            </span>

                            @if ($badge > 0)
                                <span :class="$store.sidebar.expanded ? 'opacity-100' : 'lg:opacity-0'"
                                    class="flex h-5 min-w-5 flex-shrink-0 items-center justify-center rounded-full bg-[#e8c874] px-1.5 text-[11px] font-bold tabular-nums text-[#2B1730]
                                           transition-opacity duration-300 ease-vendo">
                                    <span class="sr-only">{{ $badge }} waiting</span>
                                    <span aria-hidden="true">{{ $badge > 99 ? '99+' : $badge }}</span>
                                </span>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>
        @endforeach
        </div>
    </nav>

    <!-- Logout -->
    <div class="px-3 pb-3 pt-2 flex-shrink-0 border-t border-white/10">
        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf
            <button type="submit" title="Logout"
                class="{{ $linkBase }} hover:bg-white/[0.07]">
                <span class="flex h-6 w-10 flex-shrink-0 items-center justify-center">
                    <img src="{{ asset('assets/icons/dashboard/logout-menu.svg') }}" alt=""
                        class="h-5 w-5 brightness-0 invert opacity-75 group-hover:opacity-100">
                </span>
                <span :class="$store.sidebar.expanded ? 'opacity-100' : 'lg:opacity-0'"
                    class="min-w-0 flex-1 whitespace-nowrap text-left text-sm font-medium text-white/85
                           transition-opacity duration-300 ease-vendo">
                    Logout
                </span>
            </button>
        </form>
    </div>
</aside>