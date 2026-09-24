@forelse ($orders as $order)
<tr data-status="{{ $order->seller_group }}" data-customer="{{ $order->buyer?->name ?? '' }}" data-order="{{ $order->number }}" data-date="{{ $order->created_at->toDateString() }}">
    <td class="omo-order-id">{{ $order->number }}</td>
    <td><span class="omo-customer"><svg class="omo-customer__avatar" viewBox="0 0 40 40" width="24" height="24" aria-hidden="true"><circle cx="20" cy="20" r="18.5" fill="none" stroke="currentColor" stroke-width="2.6"/><circle cx="20" cy="15.5" r="5.6" fill="currentColor"/><path d="M8.5 31c1.6-5 6-7.4 11.5-7.4S29.9 26 31.5 31A17 17 0 0120 37a17 17 0 01-11.5-6z" fill="currentColor"/></svg><span class="omo-customer__name">{{ $order->buyer?->name ?? 'Buyer unavailable' }}</span></span></td>
    <td>{{ $order->items_sum_quantity ?? 0 }} Items</td><td class="omo-total">₱{{ number_format($order->total_amount, 2) }}</td>
    <td><span class="omo-pill omo-pill--{{ $order->seller_group }}">{{ $order->status_label }}</span></td><td>{{ $order->created_at->format('M j, Y') }}</td>
    <td><button type="button" class="omo-view-btn" data-order="{{ $order->number }}" data-id="{{ $order->id }}">View</button></td>
</tr>
@empty
<tr><td colspan="7" class="omo-empty">No orders match your filters.</td></tr>
@endforelse
