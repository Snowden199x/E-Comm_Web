{{--
    Seller Compliance section tabs. Each tab is a normal link (full navigation).
    The active pill and the tab bar carry view-transition names (see seller-compliance.css),
    so supporting browsers slide the highlight to the next tab while the page cross-fades.
--}}
@php
    $tabs = [
        ['admin.seller-compliance.overview', 'Overview', 'layout-grid'],
        ['admin.seller-compliance.products-for-review', 'Products for Review', 'clipboard-check'],
        ['admin.seller-compliance.warnings', 'Warnings', 'triangle-alert'],
        ['admin.seller-compliance.violations', 'Violations', 'shield-x'],
        ['admin.seller-compliance.suspended-sellers', 'Suspended Sellers', 'ban'],
    ];

    // Number of products waiting for review, shown beside "Products for Review" when above zero.
    $reviewCount = \App\Models\Ecommerce\Product::where('status', 'for_review')->count();
@endphp

<nav aria-label="Seller compliance sections" class="sc-tabs thin-scroll mb-6 max-w-full overflow-x-auto pb-1">
    <div class="inline-flex min-w-max gap-1 rounded-2xl border border-[#ece4ec] bg-white p-1.5 shadow-[0_1px_2px_rgba(43,23,48,0.04)]">
        @foreach ($tabs as [$route, $label, $icon])
            @php $active = request()->routeIs($route); @endphp
            <a href="{{ route($route) }}" @if ($active) aria-current="page" @endif
                class="relative flex items-center gap-2 whitespace-nowrap rounded-xl px-4 py-2.5 text-sm font-medium transition-colors duration-200
                       focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40
                       {{ $active ? 'text-white' : 'text-gray-600 hover:bg-[#F7F1F7] hover:text-[#3b1735]' }}">
                @if ($active)
                    <span aria-hidden="true" class="sc-tab-pill absolute inset-0 rounded-xl bg-[#3b1735] shadow-[0_8px_18px_-10px_rgba(59,23,53,0.8)]"></span>
                @endif
                <x-admin.icon :name="$icon" class="relative h-[18px] w-[18px]" />
                <span class="relative">{{ $label }}</span>
                @if ($route === 'admin.seller-compliance.products-for-review' && $reviewCount > 0)
                    <span class="relative flex h-5 min-w-5 items-center justify-center rounded-full px-1.5 text-[11px] font-bold tabular-nums
                                 {{ $active ? 'bg-[#e8c874] text-[#2B1730]' : 'bg-[#FEF3C7] text-[#92400E]' }}">
                        <span class="sr-only">{{ $reviewCount }} waiting for review</span>
                        <span aria-hidden="true">{{ $reviewCount > 99 ? '99+' : $reviewCount }}</span>
                    </span>
                @endif
            </a>
        @endforeach
    </div>
</nav>