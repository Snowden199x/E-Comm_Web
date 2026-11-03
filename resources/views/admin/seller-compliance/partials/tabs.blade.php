{{--
    Seller Compliance section tabs. Each tab is a normal link (full navigation).
    The active pill and the tab bar carry view-transition names (see seller-compliance.css),
    so supporting browsers slide the highlight to the next tab while the page cross-fades.
--}}
@php
    $tabs = [
        ['admin.seller-compliance.overview', 'Overview', 'layout-grid'],
        ['admin.seller-compliance.products-for-review', 'Products for Review', 'package'],
        ['admin.seller-compliance.warnings', 'Warnings', 'triangle-alert'],
        ['admin.seller-compliance.violations', 'Violations', 'alert-circle'],
        ['admin.seller-compliance.suspended-sellers', 'Suspended Sellers', 'lock'],
    ];

    // Products waiting for review, shown beside "Products for Review" when above zero.
    $reviewCount = \App\Models\Ecommerce\Product::where('status', 'for_review')->count();
@endphp

<nav aria-label="Seller compliance sections" data-skeleton="table" class="sc-tabs thin-scroll mb-5 max-w-full overflow-x-auto">
    <div class="inline-flex min-w-max gap-1 rounded-2xl border border-vd-line-strong bg-white p-1.5">
        @foreach ($tabs as [$route, $label, $icon])
            @php $active = request()->routeIs($route); @endphp
            <a href="{{ route($route) }}" x-target.push="main-content" @if ($active) aria-current="page" @endif
                class="relative flex items-center gap-2 whitespace-nowrap rounded-xl px-4 py-2 text-sm font-medium transition-colors duration-200
                       focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-plum/40
                       {{ $active ? 'text-vd-plum' : 'text-vd-ink hover:bg-vd-tint hover:text-vd-plum' }}">
                @if ($active)
                    <span aria-hidden="true" class="sc-tab-pill absolute inset-0 rounded-xl border border-vd-plum-soft bg-vd-lavender"></span>
                @endif
                <x-admin.icon :name="$icon" class="relative h-[18px] w-[18px]" />
                <span class="relative">{{ $label }}</span>
                @if ($route === 'admin.seller-compliance.products-for-review' && $reviewCount > 0)
                    <span class="relative flex h-5 min-w-5 items-center justify-center rounded-full border border-vd-warning-line bg-vd-warning-bg px-1.5 text-[11px] font-semibold tabular-nums text-vd-warning-ink">
                        <span class="sr-only">{{ $reviewCount }} waiting for review</span>
                        <span aria-hidden="true">{{ $reviewCount > 99 ? '99+' : $reviewCount }}</span>
                    </span>
                @endif
            </a>
        @endforeach
    </div>
</nav>
