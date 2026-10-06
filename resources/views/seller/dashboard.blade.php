@php
    // Presentation-only numbers derived from data the controller already supplies.
    $hour = (int) now()->format('G');
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
    $weekSales = array_column($chart, 'sales');
    $weekOrders = array_column($chart, 'orders');
    $chartSales = array_sum($weekSales);
    $chartOrders = array_sum($weekOrders);
    $hasChartData = $chartSales > 0 || $chartOrders > 0;
    $lastWeek = $chart[count($chart) - 1]['sales'] ?? 0;
    $prevWeek = $chart[count($chart) - 2]['sales'] ?? 0;
    $weekDelta = $prevWeek > 0 ? round((($lastWeek - $prevWeek) / $prevWeek) * 100) : null;
    $maxSold = max(1, (int) ($topProducts->max('sold') ?? 1));
    $lowThreshold = \App\Models\Ecommerce\Product::LOW_STOCK_THRESHOLD;
    $unread = $notifications->whereNull('read_at')->count();
    $pillFor = ['new' => 'new', 'pack' => 'pack', 'pickup' => 'pack', 'pending' => 'transit', 'completed' => 'done', 'cancelled' => 'bad', 'returned' => 'bad'];
    $thumb = function ($product) {
        // Only use images that are already loaded, so the dashboard never triggers extra queries.
        $image = $product && $product->relationLoaded('images') ? $product->images->first() : null;
        return $image ? asset('storage/'.$image->path) : null;
    };
@endphp
<x-seller.layout title="Seller Dashboard">

    {{-- ============ WELCOME ============ --}}
    <section class="db-welcome">
        <div>
            <p class="db-welcome__hi">{{ $greeting }},</p>
            <h1 class="db-welcome__name">{{ auth()->user()->name }}</h1>
            <p class="db-welcome__sub">Here's what's happening with your store today.</p>
        </div>
        <div class="db-welcome__actions">
            <span class="db-date"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4.5" width="18" height="16" rx="2.5"/><path d="M3 9.5h18M8 3v3M16 3v3"/></svg>{{ now()->format('l, F j') }}</span>
            <a class="db-btn db-btn--ghost" href="{{ route('seller.orders.index') }}">View orders</a>
            <a class="db-btn db-btn--primary" href="{{ route('seller.products.create') }}">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>Add product
            </a>
        </div>
    </section>

    {{-- ============ STATS ============ --}}
    <section class="db-stats" aria-label="Store summary">
        <a class="db-stat db-stat--orders" href="{{ route('seller.orders.index') }}" style="--i:0">
            <span class="db-stat__icon"><img src="{{ asset('assets/icons/seller/icon.png') }}" alt=""></span>
            <span class="db-stat__body">
                <span class="db-stat__label">Total Orders</span>
                <span class="db-stat__value" data-countup="{{ $stats['total_orders'] }}">{{ number_format($stats['total_orders']) }}</span>
                <span class="db-stat__hint">All time</span>
            </span>
        </a>
        <a class="db-stat db-stat--sales" href="{{ route('seller.reports.index') }}" style="--i:1">
            <span class="db-stat__icon"><img src="{{ asset('assets/icons/seller/icon-1.png') }}" alt=""></span>
            <span class="db-stat__body">
                <span class="db-stat__label">Total Sales</span>
                <span class="db-stat__value" data-countup="{{ $stats['total_sales'] }}" data-money>₱{{ number_format($stats['total_sales'], 2) }}</span>
                @if($weekDelta !== null)
                    <span class="db-delta {{ $weekDelta >= 0 ? 'is-up' : 'is-down' }}">{{ $weekDelta >= 0 ? '▲' : '▼' }} {{ abs($weekDelta) }}% <small>vs last week</small></span>
                @else
                    <span class="db-stat__hint">Delivered &amp; completed</span>
                @endif
            </span>
        </a>
        <a class="db-stat db-stat--pending" href="{{ route('seller.orders.index', ['status' => 'new']) }}" style="--i:2">
            <span class="db-stat__icon"><img src="{{ asset('assets/icons/seller/icon-2.png') }}" alt=""></span>
            <span class="db-stat__body">
                <span class="db-stat__label">Pending Orders</span>
                <span class="db-stat__value" data-countup="{{ $stats['pending_orders'] }}">{{ number_format($stats['pending_orders']) }}</span>
                <span class="db-stat__hint @if($stats['pending_orders'] > 0) is-alert @endif">{{ $stats['pending_orders'] > 0 ? 'Waiting for you to accept' : 'Nothing waiting' }}</span>
            </span>
        </a>
        <a class="db-stat db-stat--ship" href="{{ route('seller.orders.index', ['status' => 'pack']) }}" style="--i:3">
            <span class="db-stat__icon"><img src="{{ asset('assets/icons/seller/icon-3.png') }}" alt=""></span>
            <span class="db-stat__body">
                <span class="db-stat__label">To Ship</span>
                <span class="db-stat__value" data-countup="{{ $stats['to_ship'] }}">{{ number_format($stats['to_ship']) }}</span>
                <span class="db-stat__hint">Accepted, packing or ready</span>
            </span>
        </a>
        <a class="db-stat db-stat--rating" href="{{ route('seller.feedback.index') }}" style="--i:4">
            <span class="db-stat__icon"><img src="{{ asset('assets/icons/seller/icon-4.png') }}" alt=""></span>
            <span class="db-stat__body">
                <span class="db-stat__label">Average Rating</span>
                <span class="db-stat__value">{{ $stats['average_rating'] === null ? '—' : number_format($stats['average_rating'], 1) }}</span>
                <span class="db-stars" role="img" aria-label="{{ $stats['average_rating'] === null ? 'No reviews yet' : $stats['average_rating'].' out of 5 stars' }}">
                    @for($star = 1; $star <= 5; $star++)<i class="@if($stats['average_rating'] !== null && $star <= round($stats['average_rating'])) is-on @endif">★</i>@endfor
                    <small>{{ number_format($stats['review_count']) }} {{ $stats['review_count'] === 1 ? 'review' : 'reviews' }}</small>
                </span>
            </span>
        </a>
    </section>

    {{-- ============ ROW 1: SALES OVERVIEW + RECENT ORDERS ============ --}}
    <div class="db-grid db-grid--top">

        <section class="db-card db-card--chart" style="--i:5">
            <header class="db-card__head">
                <div>
                    <h2 class="db-card__title">Sales Overview</h2>
                    <p class="db-card__sub">Last 6 weeks · sales count delivered and completed orders</p>
                </div>
                <div class="db-legend" role="group" aria-label="Chart series">
                    <button type="button" class="db-legend__item is-on" data-series="0" aria-pressed="true"><i class="db-dot db-dot--sales"></i>Sales</button>
                    <button type="button" class="db-legend__item is-on" data-series="1" aria-pressed="true"><i class="db-dot db-dot--orders"></i>Orders</button>
                </div>
            </header>

            <div class="db-kpis">
                <div class="db-kpi"><span>6-week sales</span><strong>₱{{ number_format($chartSales, 2) }}</strong></div>
                <div class="db-kpi"><span>Orders received</span><strong>{{ number_format($chartOrders) }}</strong></div>
                <div class="db-kpi"><span>Avg. per week</span><strong>₱{{ number_format($chartSales / max(1, count($chart)), 2) }}</strong></div>
            </div>

            <div class="db-chart">
                <canvas id="sellerSalesOverviewChart" role="img" aria-label="Seller sales and orders over the last six weeks"></canvas>
                @unless($hasChartData)
                    <div class="db-chart__empty"><strong>No sales data yet</strong><span>Your chart fills in as orders are placed and delivered.</span></div>
                @endunless
            </div>
        </section>

        <section class="db-card db-card--orders" style="--i:6">
            <header class="db-card__head">
                <div>
                    <h2 class="db-card__title">Recent Orders</h2>
                    <p class="db-card__sub">Your latest {{ $recentOrders->count() ?: '' }} orders</p>
                </div>
                <a href="{{ route('seller.orders.index') }}" class="db-link">View all</a>
            </header>

            @if($recentOrders->isEmpty())
                <div class="db-empty">
                    <svg width="46" height="46" viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22l20-10 20 10v22L32 54 12 44z"/><path d="M12 22l20 10 20-10M32 32v22"/></svg>
                    <strong>No orders yet</strong>
                    <span>When buyers purchase your products, their orders will appear here.</span>
                    <a class="db-btn db-btn--ghost" href="{{ route('seller.products.index') }}">Check your products</a>
                </div>
            @else
                <div class="db-orders" role="table" aria-label="Recent orders">
                    <div class="db-orders__row db-orders__row--head" role="row">
                        <span role="columnheader">Order</span><span role="columnheader">Buyer</span><span role="columnheader">Total</span><span role="columnheader">Status</span>
                    </div>
                    @foreach ($recentOrders as $order)
                        @php
                            $buyerName = $order->buyer?->name ?? 'Buyer unavailable';
                            $initials = \Illuminate\Support\Str::of($buyerName)->explode(' ')->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
                        @endphp
                        <a class="db-orders__row" role="row" href="{{ route('seller.orders.index', ['order' => $order->id]) }}" style="--i: {{ $loop->index }}">
                            <span class="db-orders__order" role="cell">
                                <strong>{{ $order->number }}</strong>
                                <small>{{ $order->created_at->format('M j, g:i A') }}</small>
                            </span>
                            <span class="db-orders__buyer" role="cell"><i aria-hidden="true">{{ $initials ?: '?' }}</i><span>{{ $buyerName }}</span></span>
                            <span class="db-orders__total" role="cell">₱{{ number_format($order->total_amount, 2) }}</span>
                            <span role="cell"><span class="db-pill db-pill--{{ $pillFor[$order->seller_group] ?? 'new' }}"><i aria-hidden="true"></i>{{ $order->status_label }}</span></span>
                        </a>
                    @endforeach
                </div>
            @endif
        </section>
    </div>

    {{-- ============ ROW 2: TOP SELLING + INVENTORY + NOTIFICATIONS ============ --}}
    <div class="db-grid db-grid--bottom">

        <section class="db-card db-card--top" style="--i:7">
            <header class="db-card__head">
                <div>
                    <h2 class="db-card__title">Top Selling Products</h2>
                    <p class="db-card__sub">Ranked by units sold</p>
                </div>
            </header>

            @if($topProducts->isEmpty())
                <div class="db-empty db-empty--compact">
                    <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 17l6-6 4 4 8-8M15 7h6v6"/></svg>
                    <strong>No delivered sales yet</strong>
                    <span>Your best sellers will be ranked here after your first delivery.</span>
                </div>
            @else
                <ol class="db-top">
                    @foreach ($topProducts as $item)
                        @php $src = $thumb($item->product); @endphp
                        <li class="db-top__row" style="--i: {{ $loop->index }}">
                            <span class="db-rank db-rank--{{ min($loop->iteration, 4) }}">{{ $loop->iteration }}</span>
                            @if($src)<img class="db-thumb" src="{{ $src }}" alt="" loading="lazy">@else<span class="db-thumb db-thumb--empty" aria-hidden="true"></span>@endif
                            <div class="db-top__main">
                                <strong>{{ $item->product?->name ?? 'Product unavailable' }}</strong>
                                <span class="db-bar" aria-hidden="true"><i style="--w: {{ round(($item->sold / $maxSold) * 100) }}%"></i></span>
                            </div>
                            <div class="db-top__nums">
                                <strong>₱{{ number_format($item->revenue, 2) }}</strong>
                                <small>{{ number_format($item->sold) }} sold</small>
                            </div>
                        </li>
                    @endforeach
                </ol>
            @endif
        </section>

        <section class="db-card db-card--inventory" style="--i:8">
            <header class="db-card__head">
                <div>
                    <h2 class="db-card__title">Inventory Alerts</h2>
                    <p class="db-card__sub">Approved products that need restocking</p>
                </div>
                <a href="{{ route('seller.products.index', ['stock_status' => 'alerts']) }}" class="db-link">View all</a>
            </header>

            @if($lowStock->isEmpty() && $outOfStock->isEmpty())
                <div class="db-empty db-empty--compact db-empty--good">
                    <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M8 12.5l2.7 2.7L16 9.5"/></svg>
                    <strong>Everything is stocked</strong>
                    <span>No low-stock or sold-out products right now.</span>
                </div>
            @else
                @if($outOfStock->isNotEmpty())
                    <h3 class="db-group db-group--bad">Out of stock <span>{{ $outOfStock->count() }}</span></h3>
                    <ul class="db-inv">
                        @foreach ($outOfStock as $product)
                            @php $src = $thumb($product); @endphp
                            <li><a href="{{ route('seller.products.show', $product) }}">
                                @if($src)<img class="db-thumb db-thumb--sm" src="{{ $src }}" alt="" loading="lazy">@else<span class="db-thumb db-thumb--sm db-thumb--empty" aria-hidden="true"></span>@endif
                                <span class="db-inv__name">{{ $product->name }}</span>
                                <span class="db-chip db-chip--bad">Sold out</span>
                            </a></li>
                        @endforeach
                    </ul>
                @endif
                @if($lowStock->isNotEmpty())
                    <h3 class="db-group db-group--warn">Low stock <span>{{ $lowStock->count() }}</span></h3>
                    <ul class="db-inv">
                        @foreach ($lowStock as $product)
                            @php $src = $thumb($product); @endphp
                            <li><a href="{{ route('seller.products.show', $product) }}">
                                @if($src)<img class="db-thumb db-thumb--sm" src="{{ $src }}" alt="" loading="lazy">@else<span class="db-thumb db-thumb--sm db-thumb--empty" aria-hidden="true"></span>@endif
                                <span class="db-inv__main">
                                    <span class="db-inv__name">{{ $product->name }}</span>
                                    <span class="db-bar db-bar--warn" aria-hidden="true"><i style="--w: {{ min(100, round(($product->stock / max(1, $lowThreshold)) * 100)) }}%"></i></span>
                                </span>
                                <span class="db-chip db-chip--warn">{{ $product->stock }} left</span>
                            </a></li>
                        @endforeach
                    </ul>
                @endif
            @endif
        </section>

        <section class="db-card db-card--notif" style="--i:9">
            <header class="db-card__head">
                <div>
                    <h2 class="db-card__title">Notifications @if($unread)<span class="db-count">{{ $unread }} new</span>@endif</h2>
                    <p class="db-card__sub">Latest activity on your store</p>
                </div>
                <a href="{{ route('seller.notifications.index') }}" class="db-link">View all</a>
            </header>
            <ul class="db-notifs" id="sdDashboardNotifications" aria-label="Recent notifications">
                @include('seller.notifications.dashboard')
            </ul>
        </section>
    </div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<script>
(() => {
    const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;

    // Count-up for the headline numbers (the server already rendered the final values, so this is purely cosmetic).
    if (!reduce) {
        document.querySelectorAll('[data-countup]').forEach(node => {
            const target = Number(node.dataset.countup);
            if (!Number.isFinite(target) || target <= 0) return;
            const money = node.hasAttribute('data-money');
            const format = value => (money ? '₱' : '') + value.toLocaleString(undefined, money ? { minimumFractionDigits: 2, maximumFractionDigits: 2 } : { maximumFractionDigits: 0 });
            const start = performance.now();
            const duration = 900;
            const tick = now => {
                const progress = Math.min(1, (now - start) / duration);
                const eased = 1 - Math.pow(1 - progress, 3);
                node.textContent = format(money ? target * eased : Math.round(target * eased));
                if (progress < 1) requestAnimationFrame(tick); else node.textContent = format(target);
            };
            node.textContent = format(0);
            requestAnimationFrame(tick);
        });
    }

    const canvas = document.getElementById('sellerSalesOverviewChart');
    if (!canvas || typeof Chart === 'undefined') return;
    Chart.defaults.font.family = "'Poppins', system-ui, sans-serif";

    const gradient = (color, top, bottom) => (context) => {
        const { chart } = context;
        if (!chart.chartArea) return color + '22';
        const g = chart.ctx.createLinearGradient(0, chart.chartArea.top, 0, chart.chartArea.bottom);
        g.addColorStop(0, color + top);
        g.addColorStop(1, color + bottom);
        return g;
    };
    const peso = value => '₱' + Number(value).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    const chart = new Chart(canvas, {
        type: 'line',
        data: {
            labels: @json(array_column($chart, 'label')),
            datasets: [
                { label: 'Sales', data: @json($weekSales), borderColor: '#512258', backgroundColor: gradient('#805487', '55', '05'), borderWidth: 2.5, fill: true, tension: .38, pointRadius: 4, pointHoverRadius: 6, pointBackgroundColor: '#fff', pointBorderColor: '#512258', pointBorderWidth: 2, yAxisID: 'y' },
                { label: 'Orders', data: @json($weekOrders), borderColor: '#c28a2c', backgroundColor: gradient('#e3b45a', '40', '05'), borderWidth: 2.5, fill: true, tension: .38, pointRadius: 4, pointHoverRadius: 6, pointBackgroundColor: '#fff', pointBorderColor: '#c28a2c', pointBorderWidth: 2, yAxisID: 'y1' },
            ],
        },
        options: {
            responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false },
            animation: reduce ? false : { duration: 1000, easing: 'easeOutQuart' },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#2b1730', padding: 12, cornerRadius: 10, boxPadding: 5, titleFont: { weight: '600' },
                    callbacks: { label: ctx => ` ${ctx.dataset.label}: ${ctx.datasetIndex === 0 ? peso(ctx.parsed.y) : ctx.parsed.y}` },
                },
            },
            scales: {
                x: { grid: { display: false }, border: { display: false }, ticks: { color: '#8a8590', font: { size: 12 }, maxRotation: 0 } },
                y: { type: 'linear', position: 'left', beginAtZero: true, border: { display: false }, grid: { color: '#f1ecf1' }, ticks: { color: '#8a8590', font: { size: 12 }, maxTicksLimit: 5, callback: value => value >= 1000 ? value / 1000 + 'k' : value } },
                y1: { type: 'linear', position: 'right', beginAtZero: true, border: { display: false }, grid: { drawOnChartArea: false }, ticks: { color: '#8a8590', font: { size: 12 }, maxTicksLimit: 5, precision: 0 } },
            },
        },
    });

    // Legend buttons toggle each series.
    document.querySelectorAll('.db-legend__item').forEach(button => button.addEventListener('click', () => {
        const index = Number(button.dataset.series);
        const visible = !chart.isDatasetVisible(index);
        chart.setDatasetVisibility(index, visible);
        button.classList.toggle('is-on', visible);
        button.setAttribute('aria-pressed', String(visible));
        chart.update();
    }));
})();
</script>
@include('shared.live-revision', ['endpoint' => route('seller.live', 'dashboard'), 'mode' => 'reload'])
</x-seller.layout>