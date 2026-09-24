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
                <p class="sd-stat__value">—</p>
                <p class="sd-stat__stars" aria-label="No rating data">☆☆☆☆☆</p>
                <p class="sd-stat__meta">Ratings not available yet</p>
            </div>
        </div>
    </section>

    {{-- ============ ROW 1: SALES OVERVIEW + RECENT ORDERS ============ --}}
    <div class="sd-grid sd-grid--top">

        {{-- Sales Overview --}}
        <section class="sd-card sd-card--chart">
            <h2 class="sd-card__title">Sales Overview</h2>

            @php
                $salesMax = max(100, ceil(max(array_column($chart, 'sales')) / 100) * 100);
                $ordersMax = max(5, ceil(max(array_column($chart, 'orders')) / 5) * 5);
            @endphp
            <p class="sd-chart-note">{{ now()->format('F Y') }} · Sales from delivered and completed orders.</p>
            <svg class="sd-chart" viewBox="0 0 540 250" role="img" aria-label="Sales and orders for {{ now()->format('F Y') }}">
                <circle cx="118" cy="12" r="7" fill="#3E1E52"/><text x="132" y="16" class="sd-chart__legend">Sales</text>
                <circle cx="208" cy="12" r="7" fill="#C0603F"/><text x="222" y="16" class="sd-chart__legend">Orders</text>
                @for ($i=0;$i<=5;$i++)<text x="48" y="{{ 44+$i*33.2 }}" text-anchor="end" class="sd-chart__axis">{{ number_format($salesMax*(5-$i)/5) }}</text><text x="488" y="{{ 44+$i*33.2 }}" class="sd-chart__axis">{{ number_format($ordersMax*(5-$i)/5) }}</text>@endfor
                @foreach (['orders'=>[$ordersMax,'#C0603F','gradOrders'],'sales'=>[$salesMax,'#3E1E52','gradSales']] as $metric=>[$max,$color,$gradient])
                    @php $coords=collect($chart)->map(fn($row,$i)=>[60+($i*410/max(1,count($chart)-1)),206-($row[$metric]/$max*166),$row]);$points=$coords->map(fn($v)=>round($v[0],2).','.round($v[1],2))->implode(' ');$lastX=$coords->last()[0]; @endphp
                    <polygon fill="none" stroke="{{ $color }}" points="{{ $points }}"/><polyline fill="none" stroke="{{ $color }}" stroke-width="1.6" points="{{ $points }}"/>
                    @foreach ($coords as [$x,$y,$row])<circle cx="{{ $x }}" cy="{{ $y }}" r="2.5" fill="{{ $color }}"><title>{{ $row['label'] }}: {{ $metric==='sales'?'₱':'' }}{{ number_format($row[$metric],2) }}</title></circle>@endforeach
                @endforeach
                @foreach ($chart as $i=>$row)@if($i===0||$i===count($chart)-1||$i%max(1,(int)ceil(count($chart)/6))===0)<text x="{{ 60+($i*410/max(1,count($chart)-1)) }}" y="240" text-anchor="middle" class="sd-chart__axis">{{ $row['label'] }}</text>@endif @endforeach
            </svg>
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
                <h2 class="sd-card__title">Notification</h2>
            </header>

            <ul class="sd-notif">
                @if ($stats['pending_orders'])<li><div><p class="sd-notif__title"><a href="{{ route('seller.orders.index',['status'=>'new']) }}">{{ $stats['pending_orders'] }} new order(s) awaiting acceptance</a></p><p class="sd-notif__desc">Review and prepare your orders.</p></div></li>@endif
                @foreach ($notifications as $notification)<li><div><p class="sd-notif__title">{{ $notification->title }}</p><p class="sd-notif__desc">{{ $notification->message }}</p><p class="sd-notif__date">{{ $notification->created_at->format('M j, Y') }}</p></div></li>@endforeach
                @foreach ($announcements as $announcement)<li><div><p class="sd-notif__title">{{ $announcement->title }}</p><p class="sd-notif__desc">{{ $announcement->message }}</p><p class="sd-notif__date">{{ $announcement->created_at->format('M j, Y') }}</p></div></li>@endforeach
                @if (!$stats['pending_orders'] && $notifications->isEmpty() && $announcements->isEmpty())<li>No notifications yet.</li>@endif
            </ul>
        </section>
    </div>

</x-seller.layout>