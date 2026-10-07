<div class="sw-stats">
    <div class="sw-stat"><span>Gross product revenue</span><strong>₱{{ number_format($stats['gross'], 2) }}</strong></div>
    <div class="sw-stat"><span>Delivered orders</span><strong>{{ number_format($stats['orders']) }}</strong></div>
    <div class="sw-stat"><span>Units sold</span><strong>{{ number_format($stats['units']) }}</strong></div>
    <div class="sw-stat"><span>Average order value</span><strong>{{ $stats['average'] === null ? '—' : '₱'.number_format($stats['average'], 2) }}</strong></div>
</div>
<div class="sw-outcomes"><span>Other outcomes in this period:</span><span>Returned {{ $outcomes['returned'] }}</span><span>Cancelled {{ $outcomes['cancelled'] }}</span><span>Failed delivery {{ $outcomes['delivery_failed'] }}</span></div>
<div class="sw-grid">
    <section class="sw-card"><h2>Sales over time</h2><p class="sw-muted">Based on recorded delivery times.</p>
        <div class="sw-bars" role="img" aria-label="Sales by {{ $period === 'today' ? 'hour' : ($period === 'year' ? 'month' : 'day') }}">
            @foreach($chart as $point)
                <div class="sw-bars__item" title="{{ $point['label'] }}: ₱{{ number_format($point['value'], 2) }}"><span class="sw-bars__bar" style="height: {{ max(3, round($point['value'] / $chartMax * 100)) }}%"></span><small>{{ $point['label'] }}</small></div>
            @endforeach
        </div>
    </section>
    <section class="sw-card"><h2>Top products</h2>
        @forelse($topProducts as $item)
            <div class="sw-row"><span>{{ $item->product?->name ?? 'Unavailable product' }}<small>{{ number_format($item->units_sold) }} units</small></span><strong>₱{{ number_format($item->revenue, 2) }}</strong></div>
        @empty<p class="sw-empty">No delivered product sales for this period.</p>@endforelse
    </section>
</div>
<section class="sw-card"><div class="sw-card__title"><h2>Delivered orders</h2><span>Shipping charged: ₱{{ number_format($stats['shipping'], 2) }}</span></div>
    <div class="sw-table-wrap"><table class="sw-table"><thead><tr><th>Order</th><th>Buyer</th><th>Delivered</th><th>Product subtotal</th><th>Shipping</th></tr></thead><tbody>
        @forelse($orders as $order)<tr><td><a class="sw-link" href="{{ route('seller.completed-orders.show', $order) }}">#{{ $order->number }}</a></td><td>{{ $order->buyer?->name ?? 'Buyer unavailable' }}</td><td>{{ $order->delivered_at?->format('M j, Y g:i A') ?? '—' }}</td><td>₱{{ number_format($order->total_amount, 2) }}</td><td>₱{{ number_format($order->shipping_fee, 2) }}</td></tr>
        @empty<tr><td colspan="5" class="sw-empty">No delivered orders in this period.</td></tr>@endforelse
    </tbody></table></div>
    @if($orders instanceof \Illuminate\Contracts\Pagination\Paginator)<div class="sw-pagination">{{ $orders->links() }}</div>@endif
</section>
