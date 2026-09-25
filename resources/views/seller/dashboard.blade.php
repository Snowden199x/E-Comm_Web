<x-seller.layout title="Seller Dashboard">

    {{-- ============ WELCOME ============ --}}
    <section class="sd-welcome">
        <p class="sd-welcome__hi">Welcome Back,</p>
        <h1 class="sd-welcome__name">{{ auth()->user()->name }}</h1>
        <p class="sd-welcome__sub">Here's what's happening with your store today.</p>
    </section>

    {{-- ============ STATS ROW ============ --}}
    <section class="sd-stats">
        <div class="sd-stat sd-stat--orders">
            <img src="{{ asset('assets/icons/seller/icon.png') }}" alt="" class="sd-stat__icon">
            <div>
                <p class="sd-stat__label">Total Orders</p>
                <p class="sd-stat__value">{{ number_format($stats['total_orders']) }}</p>
            </div>
        </div>
        <div class="sd-stat sd-stat--sales">
            <img src="{{ asset('assets/icons/seller/icon-1.png') }}" alt="" class="sd-stat__icon">
            <div>
                <p class="sd-stat__label">Total Sales</p>
                <p class="sd-stat__value">₱{{ number_format($stats['total_sales'], 2) }}</p>
            </div>
        </div>
        <div class="sd-stat sd-stat--pending">
            <img src="{{ asset('assets/icons/seller/icon-2.png') }}" alt="" class="sd-stat__icon">
            <div>
                <p class="sd-stat__label">Pending Orders</p>
                <p class="sd-stat__value">{{ number_format($stats['pending_orders']) }}</p>
            </div>
        </div>
        <div class="sd-stat sd-stat--ship">
            <img src="{{ asset('assets/icons/seller/icon-3.png') }}" alt="" class="sd-stat__icon">
            <div>
                <p class="sd-stat__label">To Ship</p>
                <p class="sd-stat__value">{{ number_format($stats['to_ship']) }}</p>
            </div>
        </div>
        <div class="sd-stat sd-stat--rating">
            <img src="{{ asset('assets/icons/seller/icon-4.png') }}" alt="" class="sd-stat__icon">
            <div>
                <p class="sd-stat__label">Average Rating</p>
                <p class="sd-stat__value">{{ $stats['average_rating'] === null ? '—' : number_format($stats['average_rating'], 1) }}</p>
                <p class="sd-stat__stars" aria-label="{{ $stats['average_rating'] === null ? 'No reviews' : $stats['average_rating'].' out of 5 stars' }}">{{ $stats['average_rating'] === null ? '☆☆☆☆☆' : str_repeat('★', (int) round($stats['average_rating'])).str_repeat('☆', 5 - (int) round($stats['average_rating'])) }}</p>
                <p class="sd-stat__meta">{{ number_format($stats['review_count']) }} published reviews</p>
            </div>
        </div>
    </section>

    {{-- ============ ROW 1: SALES OVERVIEW + RECENT ORDERS ============ --}}
    <div class="sd-grid sd-grid--top">

        {{-- Sales Overview --}}
        <section class="sd-card sd-card--chart">
            <h2 class="sd-card__title">Sales Overview</h2>

            <div class="sd-chart-legend"><span><i class="sd-chart-legend__sales"></i>Sales</span><span><i class="sd-chart-legend__orders"></i>Orders</span></div>
            <p class="sd-chart-note">Last 6 weeks · Sales from delivered and completed orders.</p>
            <div class="sd-chart-canvas"><canvas id="sellerSalesOverviewChart" role="img" aria-label="Seller sales and orders over the last six weeks"></canvas></div>
        </section>

        {{-- Recent Orders --}}
        <section class="sd-card sd-card--orders">
            <header class="sd-card__head">
                <h2 class="sd-card__title">Recent Orders</h2>
                <a href="{{ route('seller.orders.index') }}" class="sd-link">View all</a>
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

                @forelse ($recentOrders as $order)
                    <li class="sd-orders__row">
                        <svg class="sd-orders__avatar" viewBox="0 0 40 40" width="24" height="24" aria-hidden="true"><circle cx="20" cy="20" r="18.5" fill="none" stroke="currentColor" stroke-width="2.6"/><circle cx="20" cy="15.5" r="5.6" fill="currentColor"/><path d="M8.5 31c1.6-5 6-7.4 11.5-7.4S29.9 26 31.5 31A17 17 0 0120 37a17 17 0 01-11.5-6z" fill="currentColor"/></svg>
                        <a href="{{ route('seller.orders.index',['order'=>$order->id]) }}" class="sd-orders__id">#{{ $order->number }}</a>
                        <span class="sd-orders__date">{{ $order->created_at->format('M j, Y g:i A') }}</span>
                        <span class="sd-orders__buyer">{{ $order->buyer?->name ?? 'Buyer unavailable' }}</span>
                        <span class="sd-orders__amount">₱{{ number_format($order->total_amount,2) }}</span>
                        <span class="sd-pill sd-pill--{{ ['new'=>'new','pack'=>'preparing','pickup'=>'preparing','pending'=>'shipped','completed'=>'delivered','cancelled'=>'new','returned'=>'new'][$order->seller_group] ?? 'new' }}">{{ $order->status_label }}</span>
                    </li>
                @empty<li class="sd-empty">No orders yet.</li>
                @endforelse
            </ul>
        </section>
    </div>

    {{-- ============ ROW 2: TOP SELLING + INVENTORY + NOTIFICATIONS ============ --}}
    <div class="sd-grid sd-grid--bottom">

        {{-- Top Selling Products --}}
        <section class="sd-card sd-card--table">
            <header class="sd-card__head">
                <h2 class="sd-card__title">Top Selling Products</h2>
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
                    @forelse ($topProducts as $item)
                        <tr><td><span class="sd-thumb" aria-hidden="true"></span>{{ $item->product?->name ?? 'Product unavailable' }}</td><td>{{ number_format($item->sold) }}</td><td>₱{{ number_format($item->revenue,2) }}</td></tr>
                    @empty<tr><td colspan="3">No delivered sales yet.</td></tr>@endforelse
                </tbody>
            </table>
        </section>

        {{-- Inventory Alerts --}}
        <section class="sd-card sd-card--inventory">
            <header class="sd-card__head">
                <h2 class="sd-card__title">Inventory Alerts</h2>
                <a href="{{ route('seller.products.index', ['stock_status' => 'alerts']) }}" class="sd-link">View all</a>
            </header>

            <h3 class="sd-inv__group">Low Stock</h3>
            <ul class="sd-inv">
                @forelse ($lowStock as $product)
                    <li><span class="sd-thumb" aria-hidden="true"></span><span class="sd-inv__name">{{ $product->name }}</span><span class="sd-inv__qty">{{ $product->stock }} Left</span></li>
                @empty
                    <li>No low-stock products.</li>
                @endforelse
            </ul>

            <h3 class="sd-inv__group">Out of Stock</h3>
            <ul class="sd-inv">
                @forelse ($outOfStock as $product)
                    <li><span class="sd-thumb" aria-hidden="true"></span><span class="sd-inv__name">{{ $product->name }}</span><span class="sd-inv__qty">0 Left</span></li>
                @empty
                    <li>No out-of-stock products.</li>
                @endforelse
            </ul>
        </section>

        {{-- Notification --}}
        <section class="sd-card sd-card--notif">
            <header class="sd-card__head">
                <h2 class="sd-card__title">Notifications</h2>
                <a href="{{ route('seller.notifications.index') }}" class="sd-link">View all</a>
            </header>

            <ul class="sd-notif" id="sdDashboardNotifications" aria-label="Recent notifications">
                @include('seller.notifications.dashboard')
            </ul>
        </section>
    </div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<script>
(() => {
    const canvas = document.getElementById('sellerSalesOverviewChart');
    if (!canvas || typeof Chart === 'undefined') return;
    const fill = (color, alpha) => {
        const gradient = canvas.getContext('2d').createLinearGradient(0, 0, 0, 260);
        gradient.addColorStop(0, color + alpha);
        gradient.addColorStop(1, color + '05');
        return gradient;
    };
    new Chart(canvas, {
        type: 'line',
        data: {
            labels: @json(array_column($chart, 'label')),
            datasets: [
                {label: 'Sales', data: @json(array_column($chart, 'sales')), borderColor: '#4A2A52', backgroundColor: fill('#8B6E95', '66'), borderWidth: 2, fill: true, tension: .35, pointRadius: 3, pointBackgroundColor: '#4A2A52', pointBorderWidth: 0, yAxisID: 'y'},
                {label: 'Orders', data: @json(array_column($chart, 'orders')), borderColor: '#C97B5F', backgroundColor: fill('#E0916F', '55'), borderWidth: 2, fill: true, tension: .35, pointRadius: 3, pointBackgroundColor: '#C97B5F', pointBorderWidth: 0, yAxisID: 'y1'}
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false, interaction: {mode: 'index', intersect: false},
            animation: {duration: 900, easing: 'easeOutQuart'},
            plugins: {legend: {display: false}, tooltip: {backgroundColor: '#2B1730', padding: 10, cornerRadius: 10, displayColors: true, boxPadding: 4}},
            scales: {
                x: {grid: {display: false}, border: {display: false}, ticks: {color: '#9CA3AF', font: {size: 11}, maxRotation: 0, autoSkip: true, maxTicksLimit: 7}},
                y: {type: 'linear', position: 'left', beginAtZero: true, border: {display: false}, grid: {color: '#F1ECF1'}, ticks: {color: '#9CA3AF', font: {size: 11}, callback: value => value >= 1000 ? value / 1000 + 'k' : value}},
                y1: {type: 'linear', position: 'right', beginAtZero: true, border: {display: false}, grid: {drawOnChartArea: false}, ticks: {color: '#9CA3AF', font: {size: 11}}}
            }
        }
    });
})();
</script>
@include('shared.live-revision', ['endpoint' => route('seller.live', 'dashboard'), 'mode' => 'reload'])
</x-seller.layout>
