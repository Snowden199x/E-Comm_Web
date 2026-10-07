<x-seller.layout title="Delivered Orders">
    @vite(['resources/css/seller/operations.css','resources/js/seller/operations.js'])
    <section class="ops-page">
        <header class="ops-page-head"><div><h1>Delivered Orders</h1><p>Review completed deliveries, order history, and sales outcomes.</p></div></header>
        <div class="ops-stats">
            <div class="ops-stat"><span class="ops-stat-icon">✓</span><span>Completed Orders<strong>{{ number_format($stats['orders']) }}</strong></span></div>
            <div class="ops-stat"><span class="ops-stat-icon">₱</span><span>Gross Product Revenue<strong>₱{{ number_format($stats['revenue'], 2) }}</strong></span></div>
            <div class="ops-stat"><span class="ops-stat-icon">♙</span><span>Customers<strong>{{ number_format($stats['customers']) }}</strong></span></div>
            <div class="ops-stat"><span class="ops-stat-icon">◎</span><span>Completion Rate<strong>{{ $stats['completion_rate'] }}%</strong></span></div>
        </div>

        <form method="GET" action="{{ route('seller.completed-orders.index') }}" class="ops-filters">
            <label class="ops-search"><span class="ops-sr-only">Search delivered orders</span><input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search order, buyer, product, or tracking number" maxlength="100"></label>
            <label><span class="ops-sr-only">Courier</span><select name="courier"><option value="">All couriers</option><option value="0" @selected(($filters['courier'] ?? '') === '0')>Unassigned</option>@foreach($couriers as $courier)<option value="{{ $courier->id }}" @selected(($filters['courier'] ?? '') == $courier->id)>{{ $courier->name }}</option>@endforeach</select></label>
            <label><span class="ops-sr-only">Payment method</span><select name="payment"><option value="">All payment methods</option>@foreach($payments as $payment)<option value="{{ $payment }}" @selected(($filters['payment'] ?? '') === $payment)>{{ strtoupper(str_replace('_', ' ', $payment)) }}</option>@endforeach</select></label>
            <label class="ops-nowrap">From <input type="date" name="from" value="{{ $filters['from'] ?? '' }}"></label>
            <label class="ops-nowrap">To <input type="date" name="to" value="{{ $filters['to'] ?? '' }}"></label>
            <button class="ops-button" type="submit">Apply</button>
            @if(request()->query())<a class="ops-text-link" href="{{ route('seller.completed-orders.index') }}">Clear</a>@endif
        </form>

        @if($errors->any())<p class="ops-alert" role="alert">{{ $errors->first() }}</p>@endif
        @if(session('success'))<p class="ops-alert" role="status">{{ session('success') }}</p>@endif

        <section class="ops-card ops-table-card" aria-label="Delivered orders">
            <div class="ops-table-scroll"><table class="ops-table ops-table--shipments"><thead><tr><th>Order ID</th><th>Customer</th><th>Items</th><th>Total</th><th>Delivered</th><th>Courier</th><th>Status</th><th>Action</th></tr></thead><tbody>
                @forelse($orders as $order)
                    <tr>
                        <td><strong>#{{ $order->number }}</strong><small>{{ $order->tracking_number }}</small></td>
                        <td>{{ $order->buyer?->name ?? 'Buyer unavailable' }}</td>
                        <td>{{ $order->items->sum('quantity') }} items</td>
                        <td>₱{{ number_format($order->total_amount + $order->shipping_fee, 2) }}</td>
                        <td>{{ $order->delivered_at?->format('M j, Y') ?? 'Date unavailable' }}<small>{{ $order->delivered_at?->format('g:i A') ?? '' }}</small></td>
                        <td>{{ $order->carrier_name ?? '—' }}<small>{{ $order->courier?->name ?? 'Unassigned' }}</small></td>
                        <td><span class="ops-badge ops-badge--delivered">{{ $order->status_label }}</span></td>
                        <td><a class="ops-button ops-button--small" href="{{ route('seller.completed-orders.show', $order) }}" data-panel data-panel-title="Order Details">View</a></td>
                    </tr>
                @empty<tr><td colspan="8" class="ops-empty">No delivered orders match these filters.</td></tr>@endforelse
            </tbody></table></div>
            @include('seller.operations.pagination', ['records' => $orders])
        </section>

        <div class="ops-bottom-panels">
            <section class="ops-card"><header class="ops-card-head"><h2>Delivery Performance</h2></header>
                @php
                    $outcomeTotal = array_sum($outcomes);
                    $colors = ['delivered' => '#187744', 'returned' => '#bc7a18', 'cancelled' => '#b53842', 'delivery_failed' => '#81558b'];
                    $deliveredEnd = $outcomeTotal ? round($outcomes['delivered'] / $outcomeTotal * 100, 1) : 0;
                    $returnedEnd = $outcomeTotal ? round(($outcomes['delivered'] + $outcomes['returned']) / $outcomeTotal * 100, 1) : 0;
                    $cancelledEnd = $outcomeTotal ? round(($outcomes['delivered'] + $outcomes['returned'] + $outcomes['cancelled']) / $outcomeTotal * 100, 1) : 0;
                    $chart = $outcomeTotal
                        ? "conic-gradient(#187744 0 {$deliveredEnd}%, #bc7a18 {$deliveredEnd}% {$returnedEnd}%, #b53842 {$returnedEnd}% {$cancelledEnd}%, #81558b {$cancelledEnd}% 100%)"
                        : 'conic-gradient(#e1dce2 0 100%)';
                @endphp
                <div class="ops-chart-row">
                    <div class="ops-donut" style="--chart: {{ $chart }}"><span><strong>{{ $stats['completion_rate'] }}%</strong><small>Delivered</small></span></div>
                    <ul class="ops-chart-key">
                        @foreach(['delivered' => 'Delivered', 'returned' => 'Returned', 'cancelled' => 'Cancelled', 'delivery_failed' => 'Failed Delivery'] as $key => $label)
                            <li><span><i style="background:{{ $colors[$key] }}"></i><span><strong>{{ $label }}</strong><small>{{ number_format($outcomes[$key]) }} · {{ $outcomeTotal ? round($outcomes[$key] / $outcomeTotal * 100) : 0 }}%</small></span></span></li>
                        @endforeach
                    </ul>
                </div>
                <p class="ops-caption">Performance includes the current delivery outcome for the selected date, courier, and payment filters.</p>
            </section>
            <section class="ops-card"><header class="ops-card-head"><h2>Top Products</h2></header>
                @forelse($topProducts as $item)<div class="ops-list-row"><span class="ops-category-symbol">▦</span><span><strong>{{ $item->product?->name ?? 'Unavailable product' }}</strong><small>{{ number_format($item->units_sold) }} units sold</small></span><strong>₱{{ number_format($item->revenue, 2) }}</strong></div>@empty<p class="ops-muted">No completed product sales for this selection.</p>@endforelse
            </section>
        </div>
    </section>
    @include('seller.operations.drawer')
@include('shared.live-revision', ['endpoint' => route('seller.live', 'shipments'), 'mode' => 'reload'])
</x-seller.layout>
