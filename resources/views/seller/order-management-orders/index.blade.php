@php
    $statCards = [
        ['new', 'New Orders', 'new-orders-icon.png'],
        ['pack', 'To Pack', 'to-pack-icon.png'],
        ['pickup', 'Ready for Pickup', 'ready-for-pickup-icon.png'],
        ['pending', 'Pending Deliveries', 'pending-deliveries-icon.png'],
    ];
    $tabs = [
        ['all', 'All'], ['new', 'New'], ['pack', 'To Pack'], ['pickup', 'Ready for Pickup'], ['pending', 'Pending Delivery'],
        ['completed', 'Delivered / Completed'], ['cancelled', 'Cancelled'], ['returned', 'Returned'],
    ];
    $chevron = '<svg class="omo-chev" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>';
@endphp
<x-seller.layout title="Orders">
    @vite('resources/css/seller/order-management-orders.css')

    <div class="omo-content" id="omoApp" data-endpoint="{{ route('seller.orders.index') }}" data-order-base="{{ url('/seller/orders') }}" data-today="{{ now()->toDateString() }}">
        <p id="omoError" role="alert" class="omo-error" hidden></p>

        {{-- ============ HEADER ============ --}}
        <section class="omo-head">
            <div>
                <h1 class="omo-head__title">Orders</h1>
                <p class="omo-head__sub">Manage and process your customer orders.</p>
            </div>
            <span class="omo-live" id="omoLive" title="This page refreshes automatically"><i aria-hidden="true"></i><span>Live</span> <small id="omoUpdated"></small></span>
        </section>

        {{-- ============ STAT CARDS (also quick filters) ============ --}}
        <section class="omo-stats" aria-label="Order summary">
            @foreach($statCards as $index => [$key, $label, $icon])
                <button type="button" class="omo-stat" data-filter="{{ $key }}" style="--i: {{ $index }}" aria-pressed="false">
                    <span class="omo-stat__icon"><img src="{{ asset('assets/icons/seller/'.$icon) }}" alt=""></span>
                    <span class="omo-stat__text">
                        <span class="omo-stat__label">{{ $label }}</span>
                        <span class="omo-stat__value" data-count="{{ $key }}">{{ number_format($counts[$key]) }}</span>
                    </span>
                </button>
            @endforeach
        </section>

        {{-- ============ TABS ============ --}}
        <nav class="omo-tabs" id="omoTabs" aria-label="Order status filter">
            @foreach($tabs as [$key, $label])
                <button type="button" class="omo-tab @if($key === 'all') is-active @endif" data-status="{{ $key }}">{{ $label }}@if($key !== 'all') <span class="omo-tab__badge" data-count="{{ $key }}">{{ number_format($counts[$key]) }}</span>@endif</button>
            @endforeach
        </nav>

        {{-- ============ FILTERS ============ --}}
        <div class="omo-filters">
            <label class="omo-search">
                <span class="omo-sr">Search orders</span>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.35-4.35"/></svg>
                <input type="text" id="omoSearchInput" placeholder="Search order ID or customer..." autocomplete="off">
                <button type="button" class="omo-search__clear" id="omoSearchClear" aria-label="Clear search" hidden>
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
                </button>
            </label>

            <div class="omo-filter" id="omoDateFilter">
                <button type="button" class="omo-filter-btn" id="omoDateBtn" aria-haspopup="true" aria-expanded="false">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4.5" width="18" height="16" rx="2.5"/><path d="M3 9.5h18M8 3v3M16 3v3"/></svg>
                    <span id="omoDateLabel">All Dates</span>
                </button>
                <div class="omo-filter-panel omo-date-panel" id="omoDatePanel">
                    <div class="omo-date-presets">
                        <button type="button" class="omo-date-preset" data-preset="all">All Dates</button>
                        <button type="button" class="omo-date-preset" data-preset="today">Today</button>
                        <button type="button" class="omo-date-preset" data-preset="7days">Last 7 Days</button>
                        <button type="button" class="omo-date-preset" data-preset="month">This Month</button>
                    </div>
                    <div class="omo-date-range">
                        <div><label for="omoDateFrom">From</label><input type="date" id="omoDateFrom"></div>
                        <div><label for="omoDateTo">To</label><input type="date" id="omoDateTo"></div>
                    </div>
                    <div class="omo-date-actions">
                        <button type="button" class="omo-btn-clear" id="omoDateClear">Clear</button>
                        <button type="button" class="omo-btn-apply" id="omoDateApply">Apply</button>
                    </div>
                </div>
            </div>

            <div class="omo-filter" id="omoStatusFilter">
                <button type="button" class="omo-filter-btn" id="omoStatusBtn" aria-haspopup="true" aria-expanded="false">
                    <span id="omoStatusLabel">All status</span>{!! $chevron !!}
                </button>
                <div class="omo-filter-panel omo-status-panel" id="omoStatusPanel">
                    <ul class="omo-status-list" id="omoStatusList">
                        @foreach($tabs as [$value, $label])
                            <li class="omo-status-option @if($value === 'all') is-selected @endif" data-status="{{ $value }}" tabindex="0" role="button">
                                <span>{{ $value === 'all' ? 'All status' : $label }}</span>
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 13l4 4L19 7"/></svg>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            <button type="button" class="omo-clear-filters" id="omoClearFilters" hidden>Clear filters</button>
        </div>

        {{-- ============ MAIN LAYOUT: TABLE + DRAWER ============ --}}
        <div class="omo-layout" id="omoLayout">
            <div class="omo-main">
                <section class="omo-card" id="omoCard" aria-busy="false">
                    <div class="omo-table-wrap">
                        <table class="omo-table">
                            <thead>
                                <tr>
                                    <th scope="col">Order ID</th>
                                    <th scope="col">Customer</th>
                                    <th scope="col">Items</th>
                                    <th scope="col">Total</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Order Date</th>
                                    <th scope="col"><span class="omo-sr">Action</span></th>
                                </tr>
                            </thead>
                            <tbody id="omoTableBody" class="is-enter">@include('seller.order-management-orders.rows')</tbody>
                        </table>
                    </div>

                    <div class="omo-table-foot" id="omoTableFoot" @if($orders->total() === 0) hidden @endif>
                        <span id="omoResultCount">Showing {{ $orders->firstItem() ?? 0 }}–{{ $orders->lastItem() ?? 0 }} out of {{ $orders->total() }} entries</span>
                        <div class="omo-pager" id="omoPager" aria-label="Orders pagination">
                            <button type="button" id="omoPrevPage" aria-label="Previous page">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 6l-6 6 6 6"/></svg>
                            </button>
                            <span class="omo-pager__pages" id="omoPagerPages">
                                @for($page = max(1, $orders->currentPage() - 2); $page <= min($orders->lastPage(), $orders->currentPage() + 2); $page++)<button type="button" data-page="{{ $page }}" class="{{ $page === $orders->currentPage() ? 'is-active' : '' }}">{{ $page }}</button>@endfor
                            </span>
                            <button type="button" id="omoNextPage" aria-label="Next page">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>
                            </button>
                        </div>
                    </div>
                </section>
            </div>

            {{-- Order details (side panel on desktop, slide-over sheet on small screens) --}}
            <aside class="omo-drawer" id="omoDrawer" aria-hidden="true" aria-label="Order details">
                <div class="omo-drawer__inner"></div>
            </aside>
        </div>
        <div class="omo-backdrop" id="omoBackdrop"></div>
        <div class="omo-toast" id="omoToast" role="status" aria-live="polite"></div>
    </div>

    @php $initialOrderData = ['filters' => $filters, 'counts' => $counts, 'pagination' => ['page' => $orders->currentPage(), 'last' => $orders->lastPage(), 'total' => $orders->total(), 'from' => $orders->firstItem(), 'to' => $orders->lastItem()]]; @endphp
    <script type="application/json" id="omoInitial">@json($initialOrderData)</script>
    @vite('resources/js/seller/order-management-orders/index.js')

</x-seller.layout>