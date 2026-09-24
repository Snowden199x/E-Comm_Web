<x-seller.layout title="Orders">
    @vite('resources/css/seller/order-management-orders.css')

    <div class="omo-content" id="omoApp" data-endpoint="{{ route('seller.orders.index') }}" data-order-base="{{ url('/seller/orders') }}" data-today="{{ now()->toDateString() }}">
        <p id="omoError" role="alert" class="omo-error" hidden></p>

        {{-- ============ HEADER ============ --}}
        <section class="omo-head">
            <h1 class="omo-head__title">Orders</h1>
            <p class="omo-head__sub">Manage and process your customer orders.</p>
        </section>

        {{-- ============ STAT PILLS ============ --}}
        <section class="omo-stats">
            <button type="button" class="omo-stat" data-filter="new">
                <span class="omo-stat__icon"><img src="{{ asset('assets/icons/seller/new-orders-icon.png') }}" alt=""></span>
                <span>
                    <span class="omo-stat__label" style="display:block">New Orders</span>
                    <span class="omo-stat__value" data-count="new">{{ number_format($counts['new']) }}</span>
                </span>
            </button>
            <button type="button" class="omo-stat" data-filter="pack">
                <span class="omo-stat__icon"><img src="{{ asset('assets/icons/seller/to-pack-icon.png') }}" alt=""></span>
                <span>
                    <span class="omo-stat__label" style="display:block">To Pack</span>
                    <span class="omo-stat__value" data-count="pack">{{ number_format($counts['pack']) }}</span>
                </span>
            </button>
            <button type="button" class="omo-stat" data-filter="pickup">
                <span class="omo-stat__icon"><img src="{{ asset('assets/icons/seller/ready-for-pickup-icon.png') }}" alt=""></span>
                <span>
                    <span class="omo-stat__label" style="display:block">Ready for Pickup</span>
                    <span class="omo-stat__value" data-count="pickup">{{ number_format($counts['pickup']) }}</span>
                </span>
            </button>
            <button type="button" class="omo-stat" data-filter="pending">
                <span class="omo-stat__icon"><img src="{{ asset('assets/icons/seller/pending-deliveries-icon.png') }}" alt=""></span>
                <span>
                    <span class="omo-stat__label" style="display:block">Pending Deliveries</span>
                    <span class="omo-stat__value" data-count="pending">{{ number_format($counts['pending']) }}</span>
                </span>
            </button>
        </section>

        {{-- ============ TABS ============ --}}
        <nav class="omo-tabs" id="omoTabs" aria-label="Order status filter">
            <button type="button" class="omo-tab is-active" data-status="all">All</button>
            <button type="button" class="omo-tab" data-status="new">New <span class="omo-tab__badge" data-count="new">0</span></button>
            <button type="button" class="omo-tab" data-status="pack">To Pack <span class="omo-tab__badge" data-count="pack">0</span></button>
            <button type="button" class="omo-tab" data-status="pickup">Ready for Pickup <span class="omo-tab__badge" data-count="pickup">0</span></button>
            <button type="button" class="omo-tab" data-status="pending">Pending Delivery <span class="omo-tab__badge" data-count="pending">0</span></button>
            @foreach (['completed'=>'Delivered / Completed','cancelled'=>'Cancelled','returned'=>'Returned'] as $key=>$label)
                <button type="button" class="omo-tab" data-status="{{ $key }}">{{ $label }} <span class="omo-tab__badge" data-count="{{ $key }}">{{ $counts[$key] }}</span></button>
            @endforeach
        </nav>

        {{-- ============ FILTERS ============ --}}
        <div class="omo-filters">
            <label class="omo-search">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.35-4.35"/></svg>
                <input type="text" id="omoSearchInput" placeholder="Search order ID or customer...">
            </label>

            {{-- Date filter --}}
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
                        <div>
                            <label for="omoDateFrom">From</label>
                            <input type="date" id="omoDateFrom">
                        </div>
                        <div>
                            <label for="omoDateTo">To</label>
                            <input type="date" id="omoDateTo">
                        </div>
                    </div>
                    <div class="omo-date-actions">
                        <button type="button" class="omo-btn-clear" id="omoDateClear">Clear</button>
                        <button type="button" class="omo-btn-apply" id="omoDateApply">Apply</button>
                    </div>
                </div>
            </div>

            {{-- Status filter --}}
            <div class="omo-filter" id="omoStatusFilter">
                <button type="button" class="omo-filter-btn" id="omoStatusBtn" aria-haspopup="true" aria-expanded="false">
                    <span id="omoStatusLabel">All status</span>
                    <svg class="omo-chev" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
                </button>
                <div class="omo-filter-panel omo-status-panel" id="omoStatusPanel">
                    <ul class="omo-status-list" id="omoStatusList">
                        @foreach ([
                            ['all', 'All status'],
                            ['new', 'New'],
                            ['pack', 'To Pack'],
                            ['pickup', 'Ready for Pickup'],
                            ['pending', 'Pending Delivery'],
                            ['completed', 'Delivered / Completed'],
                            ['cancelled', 'Cancelled'],
                            ['returned', 'Returned'],
                        ] as [$value, $label])
                            <li class="omo-status-option @if($value === 'all') is-selected @endif" data-status="{{ $value }}">
                                <span>{{ $label }}</span>
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"/></svg>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>

        {{-- ============ MAIN LAYOUT: TABLE + DRAWER ============ --}}
        <div class="omo-layout" id="omoLayout">

            {{-- ---------- TABLE ---------- --}}
            <div class="omo-main">
                <section class="omo-card">
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
                                    <th scope="col">Action</th>
                                </tr>
                            </thead>
                            <tbody id="omoTableBody">@include('seller.order-management-orders.rows')</tbody>
                        </table>
                    </div>

                    <div class="omo-table-foot">
                        <span id="omoResultCount">Showing {{ $orders->firstItem() ?? 0 }}–{{ $orders->lastItem() ?? 0 }} out of {{ $orders->total() }} entries</span>
                        <div class="omo-pager" id="omoPager" aria-label="Orders pagination">
                            <button type="button" id="omoPrevPage" aria-label="Previous page">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6"/></svg>
                            </button>
                            <span class="omo-pager__pages" id="omoPagerPages">
                                @for ($page=max(1,$orders->currentPage()-2);$page<=min($orders->lastPage(),$orders->currentPage()+2);$page++)<button type="button" data-page="{{ $page }}" class="{{ $page===$orders->currentPage()?'is-active':'' }}">{{ $page }}</button>@endfor
                            </span>
                            <button type="button" id="omoNextPage" aria-label="Next page">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg>
                            </button>
                        </div>
                    </div>
                </section>
            </div>

            {{-- ---------- DRAWER: ORDER DETAILS ---------- --}}
            <aside class="omo-drawer" id="omoDrawer" aria-hidden="true">
                <div class="omo-drawer__inner"></div>
            </aside>

        </div>
    </div>

    @php $initialOrderData=['filters'=>$filters,'counts'=>$counts,'pagination'=>['page'=>$orders->currentPage(),'last'=>$orders->lastPage(),'total'=>$orders->total(),'from'=>$orders->firstItem(),'to'=>$orders->lastItem()]]; @endphp
    <script type="application/json" id="omoInitial">@json($initialOrderData)</script>
    @vite('resources/js/seller/order-management-orders/index.js')

</x-seller.layout>