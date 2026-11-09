@php
    $payLabel = fn ($p) => strlen($p) <= 5 ? strtoupper($p) : \Illuminate\Support\Str::headline($p);
    $track = $order->carrier_tracking_number ?: $order->tracking_number;
    $published = $order->reviews->where('visibility', 'published');
    $feedbackCount = $published->count();
    $avg = $feedbackCount ? round($published->avg('rating'), 1) : null;
@endphp
<div class="do-detail">
    <div class="do-detail__head">
        <span class="do-stat__icon do-tone-mint"><svg class="do-ico do-ico--draw" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8 12.5 3 3 5-6"/></svg></span>
        <div>
            <h3>Order #{{ $order->number }}</h3>
            <p>Delivered {{ $order->delivered_at?->format('M j, Y · g:i A') ?? 'on an unknown date' }}</p>
        </div>
        <span class="do-badge do-badge--mint">{{ $order->status_label }}</span>
    </div>

    <div class="do-detail__actions">
        <a class="do-btn do-btn--primary" href="{{ route('seller.feedback.index', ['order' => $order->id]) }}">
            <svg class="do-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="m12 4 2.4 4.9 5.4.8-3.9 3.8.9 5.4-4.8-2.5-4.8 2.5.9-5.4L4.2 9.7l5.4-.8z"/></svg>
            View feedback{{ $feedbackCount ? ' ('.$feedbackCount.')' : '' }}
        </a>
@if($order->status !== 'placed')
                <a class="do-btn do-btn--soft" href="{{ route('seller.shipments.show', $order) }}">
            <svg class="do-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s7-5.6 7-11a7 7 0 10-14 0c0 5.400 7 11 7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>
            Track shipment
        </a>
        @endif
        @if($track)
            <button type="button" class="do-btn do-btn--soft" data-copy="{{ $track }}" data-copy-label="Tracking number">
                <svg class="do-ico" viewBox="0 0 24 24" aria-hidden="true"><rect x="9" y="9" width="11" height="11" rx="2"/><path d="M5 15V6a2 2 0 012-2h9"/></svg>
                Copy tracking no.
            </button>
        @endif
    </div>

    <dl class="do-facts">
        <div><dt>Customer</dt><dd>{{ $order->buyer?->name ?? 'Buyer removed' }}</dd></div>
        <div><dt>Contact</dt><dd>{{ $order->buyer?->phone_number ?: 'Not provided' }}</dd></div>
        <div><dt>Courier</dt><dd>{{ $order->carrier_name ?? '—' }} · {{ $order->courier?->name ?? 'No rider' }}</dd></div>
        <div><dt>Payment</dt><dd>{{ $order->payment_mode ? $payLabel($order->payment_mode) : '—' }}</dd></div>
        <div><dt>Ordered</dt><dd>{{ $order->created_at->format('M j, Y · g:i A') }}</dd></div>
        <div><dt>Tracking no.</dt><dd>{{ $track ?: '—' }}</dd></div>
        <div class="do-full"><dt>Delivery address</dt><dd>{{ $order->shipping_address ?: 'Not provided' }}</dd></div>
    </dl>

    <section class="do-block">
        <h4>Items</h4>
        <ul class="do-lines">
            @foreach($order->items as $item)
                @php $img = $item->product?->images->first(); $opts = collect([$item->color, $item->size])->filter()->join(' · '); @endphp
                <li>
                    @if($img)<img src="{{ asset('storage/'.$img->path) }}" alt="" loading="lazy">@else<span class="do-item__empty" aria-hidden="true"><svg class="do-ico" viewBox="0 0 24 24"><path d="m12 3 9 5v8l-9 5-9-5V8zM3 8l9 5 9-5M12 13v8"/></svg></span>@endif
                    <span><strong>{{ $item->product?->name ?? 'Product removed' }}</strong><small>{{ $opts ?: 'No options' }} · {{ $item->quantity }} × ₱{{ number_format($item->price, 2) }}</small></span>
                    <strong>₱{{ number_format($item->quantity * $item->price, 2) }}</strong>
                </li>
            @endforeach
        </ul>
        <dl class="do-totals">
            <div><dt>Subtotal</dt><dd>₱{{ number_format($order->total_amount, 2) }}</dd></div>
            @if($order->voucher_discount > 0)<div><dt>Voucher {{ $order->voucher_code }}</dt><dd>−₱{{ number_format($order->voucher_discount, 2) }}</dd></div>@endif
            <div><dt>Shipping</dt><dd>₱{{ number_format($order->shipping_fee, 2) }}</dd></div>
            <div class="do-totals__final"><dt>Total paid</dt><dd>₱{{ number_format($order->payable_total, 2) }}</dd></div>
        </dl>
    </section>

    <section class="do-block">
        <h4>Customer feedback</h4>
        @if($feedbackCount)
            <p class="do-note do-note--good"><span class="do-stars" aria-hidden="true">★</span> {{ $avg }} average from {{ $feedbackCount }} {{ \Illuminate\Support\Str::plural('review', $feedbackCount) }}</p>
        @else
            <p class="do-note">No reviews yet. Buyers can review after delivery.</p>
        @endif
    </section>

    <section class="do-block">
        <h4>Order history</h4>
        @forelse($order->statusEvents as $event)
            <div class="do-step @if($loop->last) is-last @endif">
                <i></i>
                <div>
                    <strong>{{ \App\Models\Ecommerce\Order::STATUSES[$event->to_status] ?? ucfirst(str_replace('_', ' ', $event->to_status)) }}</strong>
                    <small>{{ $event->created_at->format('M j, Y · g:i A') }}</small>
                    @if($event->note)<p>{{ $event->note }}</p>@endif
                </div>
            </div>
        @empty
            <p class="do-note">No status updates recorded for this order.</p>
        @endforelse
    </section>
</div>
