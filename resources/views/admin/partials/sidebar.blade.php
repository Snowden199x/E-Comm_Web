@php
    /*
     * Sidebar groups.
     * - Every destination has its own icon (x-admin.icon, drawn in currentColor so it can change colour and move).
     * - 'anim' picks the hover motion for the icon: nudge (default), spin, drive, tilt, grow, pop, drop, exit. See sidebar.css.
     * - 'badge' keys read a count prepared below; remove a key to hide its badge.
     * - $skeletonMap comes from the layout: it tells the loading skeleton which page shape to draw for each link.
     */
    $skeletonMap = $skeletonMap ?? [];

    $pendingRegistrations = \App\Models\User::whereIn('role', ['seller', 'buyer', 'logistics_center'])->where('status', 'pending')->count();
    $productsForReview = \App\Models\Ecommerce\Product::where('status', 'for_review')->count();
    $openComplaints = \App\Models\Complaints\Complaint::where('status', 'open')->count();
    $pendingCategoryRequests = \App\Models\Profiles\SellerCategoryRequest::where('status', 'pending')->count();

    // Grouped by what the admin is doing, not by feature age:
    //   (no label)      where to look first
    //   Approvals       queues waiting for an admin decision
    //   Users & Safety  people, seller compliance and the support inbox
    //   Finance         money rules and money in
    //   System          platform configuration (Logistics Coverage is super-admin only)
    $navGroups = [
        [
            'label' => null,
            'items' => [
                ['route' => 'admin.dashboard', 'active' => 'admin.dashboard', 'icon' => 'dashboard', 'label' => 'Dashboard', 'anim' => 'pop'],
                ['route' => 'admin.reports.index', 'active' => 'admin.reports.*', 'icon' => 'bar-chart', 'label' => 'Reports', 'anim' => 'grow'],
            ],
        ],
        [
            'label' => 'Approvals',
            'items' => [
                ['route' => 'admin.registrations.index', 'active' => 'admin.registrations.*', 'icon' => 'user-plus', 'label' => 'Registrations', 'badge' => $pendingRegistrations],
                ['route' => 'admin.category-requests.index', 'active' => 'admin.category-requests.*', 'icon' => 'layers', 'label' => 'Category Requests', 'badge' => $pendingCategoryRequests, 'anim' => 'pop'],
                ['route' => 'admin.logistics-documents.index', 'active' => 'admin.logistics-documents.*', 'icon' => 'file-check', 'label' => 'Logistics Documents'],
            ],
        ],
        [
            'label' => 'Users & Safety',
            'items' => [
                ['route' => 'admin.user-management.index', 'active' => 'admin.user-management.*', 'icon' => 'users', 'label' => 'User Management'],
                ['route' => 'admin.seller-compliance.overview', 'active' => 'admin.seller-compliance.*', 'icon' => 'shield-check', 'label' => 'Seller Compliance', 'badge' => $productsForReview, 'anim' => 'pop'],
                ['route' => 'admin.complaints.index', 'active' => 'admin.complaints.*', 'icon' => 'scale', 'label' => 'Complaints & Disputes', 'badge' => $openComplaints, 'anim' => 'tilt'],
                ['route' => 'admin.messages.index', 'active' => 'admin.messages.*', 'icon' => 'message', 'label' => 'Messages', 'anim' => 'tilt'],
            ],
        ],
        [
            'label' => 'Finance',
            'items' => [
                ['route' => 'admin.commission.index', 'active' => 'admin.commission.*', 'icon' => 'percent', 'label' => 'Commission', 'anim' => 'spin'],
                ['route' => 'admin.delivery-fees.index', 'active' => 'admin.delivery-fees.*', 'icon' => 'truck', 'label' => 'Delivery Fees', 'anim' => 'drive'],
                ['route' => 'admin.remittances.index', 'active' => 'admin.remittances.*', 'icon' => 'banknote', 'label' => 'Cash Remittances', 'anim' => 'tilt'],
            ],
        ],
        [
            'label' => 'System',
            'items' => [
                ...(auth('admin')->user()?->is_super_admin ? [['route' => 'admin.logistics-coverage.index', 'active' => 'admin.logistics-coverage.*', 'icon' => 'map-pin', 'label' => 'Logistics Coverage', 'anim' => 'drop']] : []),
                ['route' => 'admin.platform-settings.index', 'active' => 'admin.platform-settings.*', 'icon' => 'settings', 'label' => 'Platform Settings', 'anim' => 'spin'],
                ['route' => 'admin.account-management.index', 'active' => 'admin.account-management.*', 'icon' => 'user-cog', 'label' => 'Account Management'],
            ],
        ],
    ];

    $navIndex = 0;
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

{{-- The aside itself is never swapped by AJAX navigation: only #sidebar (the link list) is.
     That keeps the width, the hover-peek and the sliding highlight alive between pages. --}}
<aside id="admin-sidebar" @mouseenter="$store.sidebar.hoverOn()" @mouseleave="$store.sidebar.hoverOff()"
    :data-expanded="$store.sidebar.expanded ? 'true' : 'false'"
    :class="[
        $store.sidebar.expanded ? 'lg:w-[272px]' : 'lg:w-[80px]',
        $store.sidebar.collapsed && $store.sidebar.hovering ? 'lg:shadow-[12px_0_40px_-18px_rgba(43,23,48,0.55)]' : '',
        $store.sidebar.mobileOpen ? 'translate-x-0 border border-vd-line-strong' : '-translate-x-full lg:translate-x-0',
    ]"
    class="vd-aside fixed inset-y-0 left-0 z-40 w-[272px] flex flex-col bg-[#3b1735] text-white
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
            class="lg:hidden absolute right-4 top-1/2 -translate-y-1/2 text-white/70 hover:text-white transition-colors active:scale-90"
            aria-label="Close navigation">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <!-- Menu -->
    <nav aria-label="Admin navigation"
        class="vd-nav-scroll relative flex flex-1 flex-col px-3 pb-3 pt-4 overflow-y-auto overflow-x-hidden thin-scroll">

        {{-- One highlight for the whole list. sidebar.js slides it to the clicked (then the active) item. --}}
        <span id="vd-nav-pill" class="vd-nav-pill" aria-hidden="true"></span>

        {{-- my-auto centres the whole list between the logo and Logout; on short screens it collapses and the list scrolls --}}
        <div id="sidebar" class="my-auto">
            @foreach ($navGroups as $group)
                <div class="{{ $loop->first ? '' : 'mt-3' }}">
                    @if ($group['label'])
                        {{-- Group name when open; a thin divider when the rail is collapsed. --}}
                        <p class="vd-nav-group">{{ $group['label'] }}</p>
                        <div class="vd-nav-rule" aria-hidden="true"></div>
                    @endif

                    <div class="space-y-0.5">
                        @foreach ($group['items'] as $item)
                            @php
                                $isActive = request()->routeIs($item['active']);
                                $badge = (int) ($item['badge'] ?? 0);
                                $navIndex++;
                            @endphp

                            <div class="vd-nav-item" style="--i: {{ $navIndex }}">
                                <a href="{{ route($item['route']) }}" x-target.push="main-content sidebar"
                                    @click="$store.sidebar.close()"
                                    data-skeleton="{{ $skeletonMap[$item['active']] ?? 'table' }}"
                                    data-anim="{{ $item['anim'] ?? 'nudge' }}"
                                    title="{{ $item['label'] }}"
                                    @if ($isActive) aria-current="page" @endif
                                    class="vd-nav-link">

                                    <span class="vd-nav-icon">
                                        <x-admin.icon :name="$item['icon']" size="20" stroke="1.7" class="vd-nav-svg" />

                                        {{-- Collapsed rail: a dot stands in for the count --}}
                                        @if ($badge > 0)
                                            <span aria-hidden="true" class="vd-nav-dot"></span>
                                        @endif
                                    </span>

                                    <span class="vd-nav-label">{{ $item['label'] }}</span>

                                    @if ($badge > 0)
                                        <span class="vd-nav-badge">
                                            <span class="sr-only">{{ $badge }} waiting</span>
                                            <span aria-hidden="true">{{ $badge > 99 ? '99+' : $badge }}</span>
                                        </span>
                                    @endif
                                </a>
                            </div>
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
            <div class="vd-nav-item">
                <button type="submit" data-anim="exit" title="Logout" class="vd-nav-link vd-nav-link--logout">
                    <span class="vd-nav-icon">
                        <x-admin.icon name="log-out" size="20" stroke="1.7" class="vd-nav-svg" />
                    </span>
                    <span class="vd-nav-label">Logout</span>
                </button>
            </div>
        </form>
    </div>
</aside>
