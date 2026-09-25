<div class="ops-product ops-product--large"><span class="ops-category-symbol">✓</span><span><h3>Order #{{ $order->number }}</h3><small>{{ $order->status_label }}</small></span></div>
<dl class="ops-info">
    <div><dt>Order date</dt><dd>{{ $order->created_at->format('M j, Y g:i A') }}</dd></div>
    <div><dt>Delivered</dt><dd>{{ $order->delivered_at?->format('M j, Y g:i A') ?? 'Date unavailable' }}</dd></div>
    <div><dt>Customer</dt><dd>{{ $order->buyer?->name ?? 'Buyer unavailable' }}</dd></div>
    <div><dt>Contact</dt><dd>{{ $order->buyer?->phone_number ?? '—' }}</dd></div>
    <div><dt>Courier</dt><dd>{{ $order->carrier_name ?? '—' }} · {{ $order->courier?->name ?? 'Unassigned' }}</dd></div>
    <div><dt>Tracking</dt><dd>{{ $order->carrier_tracking_number ?: $order->tracking_number }}</dd></div>
    <div class="ops-full"><dt>Delivery address</dt><dd>{{ $order->shipping_address ?: '—' }}</dd></div>
</dl>
<section class="ops-section"><h3>Items</h3>
    <div class="ops-table-scroll"><table class="ops-table ops-table--small"><thead><tr><th>Product</th><th>Options</th><th>Qty</th><th>Subtotal</th></tr></thead><tbody>
        @foreach($order->items as $item)<tr><td>{{ $item->product?->name ?? 'Unavailable product' }}</td><td>{{ collect([$item->color, $item->size])->filter()->join(' · ') ?: '—' }}</td><td>{{ $item->quantity }}</td><td>₱{{ number_format($item->quantity * $item->price, 2) }}</td></tr>@endforeach
    </tbody></table></div>
    <dl class="ops-totals"><div><dt>Product subtotal</dt><dd>₱{{ number_format($order->total_amount, 2) }}</dd></div><div><dt>Shipping fee</dt><dd>₱{{ number_format($order->shipping_fee, 2) }}</dd></div><div><dt>Total charged</dt><dd>₱{{ number_format($order->total_amount + $order->shipping_fee, 2) }}</dd></div></dl>
</section>
<section class="ops-section"><h3>Order history</h3>
    @forelse($order->statusEvents as $event)<div class="ops-event"><strong>{{ \App\Models\Ecommerce\Order::STATUSES[$event->to_status] ?? ucfirst(str_replace('_', ' ', $event->to_status)) }}</strong><small>{{ $event->created_at->format('M j, Y g:i A') }}</small>@if($event->note)<p>{{ $event->note }}</p>@endif</div>@empty<p class="ops-muted">No status history is available.</p>@endforelse
</section>
<section class="ops-section"><h3>Customer feedback</h3>
    @php $feedbackCount = $order->reviews->where('visibility', 'published')->count(); @endphp
    <p class="ops-muted">{{ $feedbackCount ? $feedbackCount.' product review(s) for this order.' : 'No published feedback for this order yet.' }}</p>
    <a class="ops-button ops-button--primary" href="{{ route('seller.feedback.index', ['order' => $order->id]) }}">View customer feedback</a>
</section>
<section class="ops-section"><div class="ops-row-actions"><a class="ops-button" href="{{ route('seller.shipments.show', $order) }}">View tracking details</a></div></section>
