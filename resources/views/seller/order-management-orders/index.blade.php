<x-seller-layout title="Orders">
    @vite('resources/css/seller/order-management-orders.css')

    <div class="omo-content">

        {{-- ============ HEADER ============ --}}
        <section class="omo-head">
            <h1 class="omo-head__title">Orders</h1>
            <p class="omo-head__sub">Manage and process your customer orders.</p>
        </section>

        {{-- ============ STAT PILLS ============ --}}
        <section class="omo-stats">
            <button type="button" class="omo-stat">
                <span class="omo-stat__icon"><img src="{{ asset('assets/icons/seller/new-orders-icon.png') }}" alt=""></span>
                <span>
                    <span class="omo-stat__label" style="display:block">New Orders</span>
                    <span class="omo-stat__value">521</span>
                </span>
            </button>
            <button type="button" class="omo-stat">
                <span class="omo-stat__icon"><img src="{{ asset('assets/icons/seller/to-pack-icon.png') }}" alt=""></span>
                <span>
                    <span class="omo-stat__label" style="display:block">To Pack</span>
                    <span class="omo-stat__value">149</span>
                </span>
            </button>
            <button type="button" class="omo-stat">
                <span class="omo-stat__icon"><img src="{{ asset('assets/icons/seller/ready-for-pickup-icon.png') }}" alt=""></span>
                <span>
                    <span class="omo-stat__label" style="display:block">Ready for Pickup</span>
                    <span class="omo-stat__value">321</span>
                </span>
            </button>
            <button type="button" class="omo-stat">
                <span class="omo-stat__icon"><img src="{{ asset('assets/icons/seller/pending-deliveries-icon.png') }}" alt=""></span>
                <span>
                    <span class="omo-stat__label" style="display:block">Pending Deliveries</span>
                    <span class="omo-stat__value">51</span>
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
                            ['completed', 'Completed'],
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
                            <tbody id="omoTableBody">
                                @foreach ([
                                    ['VN-10245', 'Juan Dela Cruz',    2, '₱458.00',   'New',              'new',       '2026-05-20', 'May 20, 2026'],
                                    ['VN-10244', 'Maria Santos',      1, '₱899.00',   'New',              'new',       '2026-05-19', 'May 19, 2026'],
                                    ['VN-10243', 'Carlo Mendoza',     3, '₱1,240.00', 'New',              'new',       '2026-05-18', 'May 18, 2026'],
                                    ['VN-10242', 'Angela Cruz',       2, '₱620.00',   'To Pack',          'pack',      '2026-05-17', 'May 17, 2026'],
                                    ['VN-10241', 'Paolo Ramirez',     1, '₱350.00',   'Ready for Pickup', 'pickup',    '2026-05-15', 'May 15, 2026'],
                                    ['VN-10240', 'Kim Bautista',      4, '₱2,150.00', 'To Pack',          'pack',      '2026-05-14', 'May 14, 2026'],
                                    ['VN-10239', 'Nico Torres',       2, '₱780.00',   'Pending Delivery', 'pending',   '2026-05-10', 'May 10, 2026'],
                                    ['VN-10238', 'Ella Villanueva',   1, '₱299.00',   'Completed',        'completed', '2026-05-05', 'May 5, 2026'],
                                    ['VN-10237', 'Marco Ilagan',      3, '₱1,050.00', 'Pending Delivery', 'pending',   '2026-05-02', 'May 2, 2026'],
                                    ['VN-10236', 'Liza Reyes',        2, '₱540.00',   'Completed',        'completed', '2026-05-01', 'May 1, 2026'],
                                ] as [$orderId, $customer, $items, $total, $statusLabel, $statusClass, $isoDate, $displayDate])
                                    <tr data-status="{{ $statusClass }}" data-customer="{{ $customer }}" data-order="{{ $orderId }}" data-date="{{ $isoDate }}">
                                        <td class="omo-order-id">{{ $orderId }}</td>
                                        <td>
                                            <span class="omo-customer">
                                                <svg class="omo-customer__avatar" viewBox="0 0 40 40" width="24" height="24" aria-hidden="true">
                                                    <circle cx="20" cy="20" r="18.5" fill="none" stroke="currentColor" stroke-width="2.6"/>
                                                    <circle cx="20" cy="15.5" r="5.6" fill="currentColor"/>
                                                    <path d="M8.5 31c1.6-5 6-7.4 11.5-7.4S29.9 26 31.5 31A17 17 0 0120 37a17 17 0 01-11.5-6z" fill="currentColor"/>
                                                </svg>
                                                <span class="omo-customer__name">{{ $customer }}</span>
                                            </span>
                                        </td>
                                        <td>{{ $items }} Items</td>
                                        <td class="omo-total">{{ $total }}</td>
                                        <td><span class="omo-pill omo-pill--{{ $statusClass }}">{{ $statusLabel }}</span></td>
                                        <td>{{ $displayDate }}</td>
                                        <td>
                                            <button type="button" class="omo-view-btn" data-order="{{ $orderId }}">View</button>
                                        </td>
                                    </tr>
                                @endforeach
                                <tr class="omo-no-results" id="omoNoResults" style="display:none;">
                                    <td colspan="7">No orders match your filters.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="omo-table-foot">
                        <span id="omoResultCount">Showing 10 out of 10 entries</span>
                        <div class="omo-pager" id="omoPager" aria-label="Orders pagination">
                            <button type="button" id="omoPrevPage" aria-label="Previous page">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6"/></svg>
                            </button>
                            <span class="omo-pager__pages" id="omoPagerPages"></span>
                            <button type="button" id="omoNextPage" aria-label="Next page">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg>
                            </button>
                        </div>
                    </div>
                </section>
            </div>

            {{-- ---------- DRAWER: ORDER DETAILS ---------- --}}
            <aside class="omo-drawer" id="omoDrawer" aria-hidden="true">
                <div class="omo-drawer__inner">

                    <div class="omo-drawer__head">
                        <span class="omo-drawer__title" id="omoDrawerTitle">Order #VN-10245</span>
                        <button type="button" class="omo-drawer__close" id="omoDrawerClose" aria-label="Close order details">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
                        </button>
                    </div>

                    <span class="omo-drawer__badge omo-drawer__badge--new">New Order</span>

                    <div class="omo-drawer__body">

                        {{-- Order Information --}}
                        <section class="omo-section">
                            <h3 class="omo-section__head">
                                <span class="omo-section__icon">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="4"/><path d="M8 8h8M8 12h8M8 16h5"/></svg>
                                </span>
                                Order Information
                            </h3>
                            <dl class="omo-info-grid">
                                <div><dt>Order ID</dt><dd id="omoDrawerOrderId">#VN-10245</dd></div>
                                <div><dt>Order Date</dt><dd>Aug 29, 2024<br>10:24 AM</dd></div>
                                <div><dt>Status</dt><dd><span class="omo-pill omo-pill--new">New</span></dd></div>
                            </dl>
                        </section>

                        {{-- Customer Information --}}
                        <section class="omo-section">
                            <h3 class="omo-section__head">
                                <span class="omo-section__icon">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="3.4"/><path d="M5 20c1.4-4.2 5-6 7-6s5.6 1.8 7 6"/></svg>
                                </span>
                                Customer Information
                            </h3>
                            <dl class="omo-info-grid omo-info-grid--2">
                                <div><dt>Customer Name</dt><dd id="omoDrawerCustomer">Juan Dela Cruz</dd></div>
                                <div><dt>Contact Number</dt><dd>0912 345 6789</dd></div>
                                <div style="grid-column: 1 / -1;"><dt>Delivery Address</dt><dd>123 Mango St., Brgy. San Antonio, Quezon City, Metro Manila</dd></div>
                            </dl>
                        </section>

                        {{-- Order Items --}}
                        <section class="omo-section">
                            <h3 class="omo-section__head">
                                <span class="omo-section__icon">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8l-9-5-9 5 9 5 9-5z"/><path d="M3 8v8l9 5 9-5V8"/><path d="M12 13v8"/></svg>
                                </span>
                                Order Items
                            </h3>
                            <div class="omo-items">
                                <div class="omo-items__head">
                                    <span>Product</span><span>Variation</span><span>Qty</span><span>Unit Price</span><span style="text-align:right">Subtotal</span>
                                </div>
                                <div class="omo-item">
                                    <span class="omo-item__product">
                                        <span class="omo-item__thumb" aria-hidden="true"></span>
                                        <span class="omo-item__name">Wireless Earbuds</span>
                                    </span>
                                    <span>Black</span>
                                    <span>1</span>
                                    <span>₱599.00</span>
                                    <span class="omo-item__sub">₱599.00</span>
                                </div>
                                <div class="omo-item">
                                    <span class="omo-item__product">
                                        <span class="omo-item__thumb" aria-hidden="true"></span>
                                        <span class="omo-item__name">Phone Case</span>
                                    </span>
                                    <span>iPhone 14 / Black</span>
                                    <span>1</span>
                                    <span>₱199.00</span>
                                    <span class="omo-item__sub">₱199.00</span>
                                </div>
                            </div>
                        </section>

                        {{-- Payment --}}
                        <section class="omo-section">
                            <h3 class="omo-section__head">
                                <span class="omo-section__icon">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2.4"/><path d="M2 10h20"/></svg>
                                </span>
                                Payment
                            </h3>
                            <div class="omo-payment">
                                <dl class="omo-payment__method">
                                    <dt>Payment Method</dt>
                                    <dd>GCash</dd>
                                </dl>
                                <div class="omo-payment__lines">
                                    <div class="omo-payment__line"><span>Subtotal</span><span>₱798.00</span></div>
                                    <div class="omo-payment__line"><span>Shipping Fee</span><span>₱60.00</span></div>
                                    <div class="omo-payment__line"><span>Discount</span><span>-₱0.00</span></div>
                                    <div class="omo-payment__line omo-payment__line--total"><span>Total Amount</span><span>₱858.00</span></div>
                                </div>
                            </div>
                        </section>

                    </div>

                    <div class="omo-drawer__actions">
                        <button type="button" class="omo-btn omo-btn--primary" id="omoAccept">Accept Order</button>
                        <button type="button" class="omo-btn omo-btn--ghost" id="omoDecline">Decline Order</button>
                    </div>

                </div>
            </aside>

        </div>
    </div>

    @vite('resources/js/seller/order-management-orders/index.js')

</x-seller-layout>