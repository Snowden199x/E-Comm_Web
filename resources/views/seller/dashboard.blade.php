<x-seller-layout title="Seller Dashboard">

    {{-- ============ WELCOME ============ --}}
    <section class="sd-welcome">
        <p class="sd-welcome__hi">Welcome Back,</p>
        <h1 class="sd-welcome__name">Juan Dela Cruz</h1>
        <p class="sd-welcome__sub">Here's what's happening with your store today.</p>
    </section>

    {{-- ============ STATS ROW ============ --}}
    <section class="sd-stats">
        <div class="sd-stat sd-stat--orders">
            <img src="{{ asset('assets/icons/seller/icon.png') }}" alt="" class="sd-stat__icon">
            <div>
                <p class="sd-stat__label">Total Orders</p>
                <p class="sd-stat__value">1,256</p>
            </div>
        </div>
        <div class="sd-stat sd-stat--sales">
            <img src="{{ asset('assets/icons/seller/icon-1.png') }}" alt="" class="sd-stat__icon">
            <div>
                <p class="sd-stat__label">Total Sales</p>
                <p class="sd-stat__value">₱1,256</p>
            </div>
        </div>
        <div class="sd-stat sd-stat--pending">
            <img src="{{ asset('assets/icons/seller/icon-2.png') }}" alt="" class="sd-stat__icon">
            <div>
                <p class="sd-stat__label">Pending Orders</p>
                <p class="sd-stat__value">1,256</p>
            </div>
        </div>
        <div class="sd-stat sd-stat--ship">
            <img src="{{ asset('assets/icons/seller/icon-3.png') }}" alt="" class="sd-stat__icon">
            <div>
                <p class="sd-stat__label">To Ship</p>
                <p class="sd-stat__value">1,256</p>
            </div>
        </div>
        <div class="sd-stat sd-stat--rating">
            <img src="{{ asset('assets/icons/seller/icon-4.png') }}" alt="" class="sd-stat__icon">
            <div>
                <p class="sd-stat__label">Average Rating</p>
                <p class="sd-stat__value">4.8</p>
                <p class="sd-stat__stars" aria-label="4.8 out of 5 stars">★★★★★</p>
                <p class="sd-stat__meta">Based on 69 reviews</p>
            </div>
        </div>
    </section>

    {{-- ============ ROW 1: SALES OVERVIEW + RECENT ORDERS ============ --}}
    <div class="sd-grid sd-grid--top">

        {{-- Sales Overview --}}
        <section class="sd-card sd-card--chart">
            <h2 class="sd-card__title">Sales Overview</h2>

            <svg class="sd-chart" viewBox="0 0 540 250" role="img" aria-label="Sales and orders from May 1 to May 31">
                <defs>
                    <linearGradient id="gradSales" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0" stop-color="#8E4FA3" stop-opacity=".45"/>
                        <stop offset="1" stop-color="#8E4FA3" stop-opacity="0"/>
                    </linearGradient>
                    <linearGradient id="gradOrders" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0" stop-color="#C0603F" stop-opacity=".55"/>
                        <stop offset="1" stop-color="#C0603F" stop-opacity="0"/>
                    </linearGradient>
                </defs>

                {{-- Legend --}}
                <circle cx="118" cy="12" r="7" fill="#3E1E52"/>
                <text x="132" y="16" class="sd-chart__legend">Sales</text>
                <circle cx="208" cy="12" r="7" fill="#C0603F"/>
                <text x="222" y="16" class="sd-chart__legend">Orders</text>

                {{-- Left axis (sales, PHP) --}}
                <g class="sd-chart__axis" text-anchor="end">
                    <text x="38" y="40">100k</text>
                    <text x="38" y="74">80k</text>
                    <text x="38" y="108">60k</text>
                    <text x="38" y="142">40k</text>
                    <text x="38" y="176">20k</text>
                    <text x="38" y="210">0</text>
                </g>

                {{-- Right axis (orders) --}}
                <g class="sd-chart__axis" text-anchor="start">
                    <text x="494" y="44">250</text>
                    <text x="494" y="78">200</text>
                    <text x="494" y="112">150</text>
                    <text x="494" y="146">100</text>
                    <text x="494" y="180">50</text>
                    <text x="494" y="214">0</text>
                </g>

                {{-- Orders area (behind) --}}
                <polygon fill="url(#gradOrders)" points="60,200.6 94.2,194.4 128.3,178.8 162.5,191 196.7,151.6 230.8,163.8 265,161.1 299.2,116.2 333.3,135.3 367.5,124.4 401.7,156.4 435.8,148.2 470,146.2 470,206 60,206"/>
                <polyline fill="none" stroke="#C0603F" stroke-width="1.6" stroke-linejoin="round" points="60,200.6 94.2,194.4 128.3,178.8 162.5,191 196.7,151.6 230.8,163.8 265,161.1 299.2,116.2 333.3,135.3 367.5,124.4 401.7,156.4 435.8,148.2 470,146.2"/>

                {{-- Sales area (front, translucent) --}}
                <polygon fill="url(#gradSales)" points="60,185.6 94.2,172 128.3,148.2 162.5,160.1 196.7,115.9 230.8,131.2 265,141.4 299.2,85.3 333.3,110.8 367.5,87 401.7,131.2 435.8,117.6 470,112.5 470,206 60,206"/>
                <polyline fill="none" stroke="#3E1E52" stroke-width="1.6" stroke-linejoin="round" points="60,185.6 94.2,172 128.3,148.2 162.5,160.1 196.7,115.9 230.8,131.2 265,141.4 299.2,85.3 333.3,110.8 367.5,87 401.7,131.2 435.8,117.6 470,112.5"/>

                {{-- Data points: orders --}}
                <g fill="#C0603F">
                    <circle cx="60" cy="200.6" r="3"/><circle cx="94.2" cy="194.4" r="3"/><circle cx="128.3" cy="178.8" r="3"/>
                    <circle cx="162.5" cy="191" r="3"/><circle cx="196.7" cy="151.6" r="3"/><circle cx="230.8" cy="163.8" r="3"/>
                    <circle cx="265" cy="161.1" r="3"/><circle cx="299.2" cy="116.2" r="3"/><circle cx="333.3" cy="135.3" r="3"/>
                    <circle cx="367.5" cy="124.4" r="3"/><circle cx="401.7" cy="156.4" r="3"/><circle cx="435.8" cy="148.2" r="3"/>
                    <circle cx="470" cy="146.2" r="3"/>
                </g>
                {{-- Data points: sales --}}
                <g fill="#3E1E52">
                    <circle cx="60" cy="185.6" r="3"/><circle cx="94.2" cy="172" r="3"/><circle cx="128.3" cy="148.2" r="3"/>
                    <circle cx="162.5" cy="160.1" r="3"/><circle cx="196.7" cy="115.9" r="3"/><circle cx="230.8" cy="131.2" r="3"/>
                    <circle cx="265" cy="141.4" r="3"/><circle cx="299.2" cy="85.3" r="3"/><circle cx="333.3" cy="110.8" r="3"/>
                    <circle cx="367.5" cy="87" r="3"/><circle cx="401.7" cy="131.2" r="3"/><circle cx="435.8" cy="117.6" r="3"/>
                    <circle cx="470" cy="112.5" r="3"/>
                </g>

                {{-- X axis --}}
                <g class="sd-chart__axis" text-anchor="middle">
                    <text x="60" y="240">May 1</text>
                    <text x="128.3" y="240">May 6</text>
                    <text x="196.7" y="240">May 11</text>
                    <text x="265" y="240">May 16</text>
                    <text x="333.3" y="240">May 21</text>
                    <text x="401.7" y="240">May 26</text>
                    <text x="470" y="240">May 31</text>
                </g>
            </svg>
        </section>

        {{-- Recent Orders --}}
        <section class="sd-card sd-card--orders">
            <header class="sd-card__head">
                <h2 class="sd-card__title">Recent Orders</h2>
                <a href="#" class="sd-link">View all</a>
            </header>

            <ul class="sd-orders">
                <li class="sd-orders__header" aria-hidden="true">
                    <span></span>
                    <span>Order Number</span>
                    <span>Date</span>
                    <span>Buyer</span>
                    <span>Total</span>
                    <span>Status</span>
                </li>

                @foreach ([
                    ['New', 'new'],
                    ['Preparing', 'preparing'],
                    ['Shipped', 'shipped'],
                    ['Delivered', 'delivered'],
                    ['New', 'new'],
                    ['New', 'new'],
                    ['New', 'new'],
                ] as [$label, $class])
                    <li class="sd-orders__row">
                        <svg class="sd-orders__avatar" viewBox="0 0 40 40" width="24" height="24" aria-hidden="true">
                            <circle cx="20" cy="20" r="18.5" fill="none" stroke="currentColor" stroke-width="2.6"/>
                            <circle cx="20" cy="15.5" r="5.6" fill="currentColor"/>
                            <path d="M8.5 31c1.6-5 6-7.4 11.5-7.4S29.9 26 31.500 31A17 17 0 0120 37a17 17 0 01-11.500-6z" fill="currentColor"/>
                        </svg>
                        <a href="#" class="sd-orders__id">#ORD-000128</a>
                        <span class="sd-orders__date">May 21, 2024&nbsp; 10:24 AM</span>
                        <span class="sd-orders__buyer">Juan Reyez</span>
                        <span class="sd-orders__amount">₱1,250.00</span>
                        <span class="sd-pill sd-pill--{{ $class }}">{{ $label }}</span>
                    </li>
                @endforeach
            </ul>
        </section>
    </div>

    {{-- ============ ROW 2: TOP SELLING + INVENTORY + NOTIFICATIONS ============ --}}
    <div class="sd-grid sd-grid--bottom">

        {{-- Top Selling Products --}}
        <section class="sd-card sd-card--table">
            <header class="sd-card__head">
                <h2 class="sd-card__title">Top Selling Products</h2>
                <a href="#" class="sd-link">View all</a>
            </header>

            <table class="sd-table">
                <thead>
                    <tr>
                        <th scope="col">Product</th>
                        <th scope="col">Sold</th>
                        <th scope="col">Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ([
                        ['Wireless Earbuds',      128, '₱25,600.00'],
                        ['Stainless Steel Tumbler', 96, '₱14,000.00'],
                        ['Minimalist Backpack',    74, '₱11,840.00'],
                        ['Phone Holder Stand',     56, '₱6,160.00'],
                        ['LED Desk Lamp',          42, '₱4,830.00'],
                        ['Wireless Earbuds',      128, '₱25,600.00'],
                        ['Wireless Earbuds',      128, '₱25,600.00'],
                    ] as [$name, $sold, $revenue])
                        <tr>
                            <td>
                                <span class="sd-thumb" aria-hidden="true"></span>
                                {{ $name }}
                            </td>
                            <td>{{ $sold }}</td>
                            <td>{{ $revenue }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>

        {{-- Inventory Alerts --}}
        <section class="sd-card sd-card--inventory">
            <header class="sd-card__head">
                <h2 class="sd-card__title">Inventory Alerts</h2>
                <a href="#" class="sd-link">Manage</a>
            </header>

            <h3 class="sd-inv__group">Low Stock</h3>
            <ul class="sd-inv">
                <li><span class="sd-thumb" aria-hidden="true"></span><span class="sd-inv__name">Minimalist Backpack</span><span class="sd-inv__qty">5 Left</span></li>
                <li><span class="sd-thumb" aria-hidden="true"></span><span class="sd-inv__name">Stainless Steel Tumbler</span><span class="sd-inv__qty">10 Left</span></li>
                <li><span class="sd-thumb" aria-hidden="true"></span><span class="sd-inv__name">LED Desk Lamp</span><span class="sd-inv__qty">8 Left</span></li>
            </ul>

            <h3 class="sd-inv__group">Out of Stock</h3>
            <ul class="sd-inv">
                <li><span class="sd-thumb" aria-hidden="true"></span><span class="sd-inv__name">Wireless Earbuds</span><span class="sd-inv__qty">0 Left</span></li>
                <li><span class="sd-thumb" aria-hidden="true"></span><span class="sd-inv__name">Wireless Earbuds</span><span class="sd-inv__qty">0 Left</span></li>
            </ul>
        </section>

        {{-- Notification --}}
        <section class="sd-card sd-card--notif">
            <header class="sd-card__head">
                <h2 class="sd-card__title">Notification</h2>
                <a href="#" class="sd-link">View all</a>
            </header>

            <ul class="sd-notif">
                <li>
                    <span class="sd-notif__icon sd-notif__icon--gold" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11v2a1 1 0 001 1h2l5 4V6L6 10H4a1 1 0 00-1 1z"/><path d="M15.5 9a4 4 0 010 6M18 6.500a8 8 0 010 11"/></svg>
                    </span>
                    <div>
                        <p class="sd-notif__title">Payout will be process on May 20, 2026</p>
                        <p class="sd-notif__desc">Your earnings will be transferred to your registered payout account.</p>
                        <p class="sd-notif__date">May 18, 2026</p>
                    </div>
                </li>
                <li>
                    <span class="sd-notif__icon sd-notif__icon--pink" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20.600 13.400l-7.200 7.200a2 2 0 01-2.800 0L3 13V3h10l7.600 7.600a2 2 0 010 2.800z"/><circle cx="7.500" cy="7.500" r="1.200"/></svg>
                    </span>
                    <div>
                        <p class="sd-notif__title">Mega Sale: May 25 – May 31</p>
                        <p class="sd-notif__desc">Join the biggest sale event and boost your store visibility!</p>
                        <p class="sd-notif__date">May 17, 2026</p>
                    </div>
                </li>
            </ul>
        </section>
    </div>

</x-seller-layout>