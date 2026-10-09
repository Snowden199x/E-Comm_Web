@php
    // Progress track. Cancelled orders show a notice instead of the track.
    $flow = ['Placed', 'Accepted', 'Packing', 'Ready', 'In transit', 'Delivered'];
    $stage = match (true) {
        $order->status === 'placed' => 0,
        $order->status === 'confirmed' => 1,
        $order->status === 'preparing' => 2,
        $order->status === 'ready_for_pickup' => 3,
        in_array($order->status, \App\Models\Ecommerce\Order::SELLER_GROUPS['completed'], true) => 5,
        $order->seller_group === 'pending' => 4,
        default => null,
    };
    $buyerName = $order->buyer?->name ?? 'Buyer unavailable';
    $initials = \Illuminate\Support\Str::of($buyerName)->explode(' ')->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
    $itemsSubtotal = $order->items->sum(fn ($item) => $item->quantity * $item->price);
    $itemCount = $order->items->sum('quantity');
    $buyerNote = trim((string) ($order->buyer_note ?? ''));      // exists once the checkout note is built (backend-needs-2026-10-07.md)
    $voucherCode = $order->voucher_code ?? null;                 // exists once vouchers are built
    $voucherDiscount = (float) ($order->voucher_discount ?? 0);
    $mode = strtoupper($order->payment_mode ?? 'COD');
@endphp
<div class="omo-drawer__inner">
    <div class="omo-drawer__head">
        <div>
            <span class="omo-drawer__eyebrow">Order</span>
            <h2 class="omo-drawer__title">{{ $order->number }}</h2>
            <p class="omo-drawer__meta">Placed {{ $order->created_at->format('M j, Y · g:i A') }}</p>
        </div>
        <button type="button" class="omo-drawer__close" id="omoDrawerClose" aria-label="Close order details">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
        </button>
    </div>

    <div class="omo-drawer__body">
        <div class="omo-hero">
            <div class="omo-hero__left">
                <span class="omo-pill omo-pill--{{ $order->seller_group }}"><i aria-hidden="true"></i>{{ $order->status_label }}</span>
                <span class="omo-hero__chips"><span class="omo-chip">{{ $itemCount }} {{ $itemCount === 1 ? 'item' : 'items' }}</span><span class="omo-chip omo-chip--{{ $mode === 'COD' ? 'cod' : 'paid' }}">{{ $mode }}</span>@if($voucherCode)<span class="omo-chip omo-chip--voucher">Voucher {{ $voucherCode }}</span>@endif</span>
            </div>
            <div class="omo-hero__total"><span>Total</span><strong>₱{{ number_format($order->total_amount, 2) }}</strong></div>
        </div>

        @if($buyerNote !== '')
            <aside class="omo-note" aria-label="Message from the buyer">
                <span class="omo-note__icon" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a8 8 0 01-11.6 7.1L4 20l1-4.6A8 8 0 1121 12z"/></svg></span>
                <div><strong>Message from {{ $order->buyer?->name ?? 'the buyer' }}</strong><p>{{ $buyerNote }}</p></div>
            </aside>
        @endif

        @if($stage !== null)
            <ol class="omo-track" aria-label="Order progress">
                @foreach($flow as $index => $label)
                    <li class="omo-track__step @if($index < $stage) is-done @elseif($index === $stage) is-current @endif" @if($index === $stage) aria-current="step" @endif>
                        <span class="omo-track__dot" aria-hidden="true">@if($index < $stage)<svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"/></svg>@endif</span>
                        <span class="omo-track__label">{{ $label }}</span>
                    </li>
                @endforeach
            </ol>
        @else
            <p class="omo-notice omo-notice--{{ $order->seller_group }}" role="status">This order was {{ strtolower($order->status_label) }}. No further seller action is needed.</p>
        @endif

        <div class="omo-cols">
        <section class="omo-section omo-section--card">
            <h3 class="omo-section__head">Customer</h3>
            <div class="omo-person">
                <span class="omo-customer__avatar omo-customer__avatar--lg" aria-hidden="true">{{ $initials ?: '?' }}</span>
                <div class="omo-person__text">
                    <strong>{{ $buyerName }}</strong>
                    <span>{{ $order->buyer?->phone_number ?? 'No contact number provided' }}</span>
                </div>
                @if($order->buyer)<a class="omo-link" href="{{ route('seller.buyers.show', $order->buyer->id) }}">View profile</a>@endif
            </div>
            <dl class="omo-info-grid omo-info-grid--2">
                <div style="grid-column:1/-1"><dt>Delivery address</dt><dd>{{ $order->shipping_address ?: 'Not provided' }}</dd></div>
                <div><dt>Pickup courier</dt><dd>{{ $order->courier?->name ?? 'Awaiting logistics assignment' }}</dd></div>
                @if($order->delivery_courier_id)<div><dt>Delivery rider</dt><dd>{{ $order->deliveryCourier?->name ?? 'Unavailable' }}</dd></div>@endif
            </dl>
        </section>

        <section class="omo-section omo-section--card">
            <h3 class="omo-section__head">Payment</h3>
            <div class="omo-payment">
                <dl class="omo-payment__method"><dt>Method</dt><dd>{{ $mode }}<small>{{ $mode === 'COD' ? 'Buyer pays the rider on delivery' : 'Paid online' }}</small></dd></dl>
                <div class="omo-payment__lines">
                    <div class="omo-payment__line"><span>Items subtotal</span><span>₱{{ number_format($itemsSubtotal, 2) }}</span></div>
                    @if((float) $order->shipping_fee > 0)<div class="omo-payment__line"><span>Shipping</span><span>₱{{ number_format($order->shipping_fee, 2) }}</span></div>@endif
                    @if($voucherCode && $voucherDiscount > 0)<div class="omo-payment__line omo-payment__line--voucher"><span>Voucher {{ $voucherCode }}</span><span>−₱{{ number_format($voucherDiscount, 2) }}</span></div>@endif
                    <div class="omo-payment__line omo-payment__line--total"><span>Total amount</span><span>₱{{ number_format($order->total_amount, 2) }}</span></div>
                </div>
            </div>
        </section>

        </div>

        <section class="omo-section">
            <h3 class="omo-section__head">Products <span class="omo-count">{{ $itemCount }} {{ $itemCount === 1 ? 'item' : 'items' }}</span></h3>
            <ul class="omo-products">
                @foreach ($order->items as $item)
                    @php
                        $image = $item->product?->images->first();
                        $variation = $item->variant?->label ?: implode(' / ', array_filter([$item->color, $item->size]));
                        $sku = $item->variant?->sku ?: $item->product?->product_code;
                    @endphp
                    <li class="omo-product">
                        @if($image)<img class="omo-product__img" src="{{ asset('storage/'.$image->path) }}" alt="{{ $item->product->name }}" loading="lazy">@else<span class="omo-product__img omo-product__img--empty" aria-hidden="true"></span>@endif
                        <div class="omo-product__info">
                            <strong>{{ $item->product?->name ?? 'Product unavailable' }}</strong>
                            @if($variation)<span class="omo-chip">{{ $variation }}</span>@endif
                            @if($sku)<span class="omo-product__sku">SKU: {{ $sku }}</span>@endif
                            <span class="omo-product__qty">₱{{ number_format($item->price, 2) }} × {{ $item->quantity }}</span>
                        </div>
                        <div class="omo-product__total">₱{{ number_format($item->quantity * $item->price, 2) }}</div>
                    </li>
                @endforeach
            </ul>
        </section>

        @if($order->statusEvents->isNotEmpty())
            <details class="omo-section omo-fold">
                <summary class="omo-section__head">Order history <span class="omo-count">{{ $order->statusEvents->count() }}</span></summary>
                <ol class="omo-timeline">
                    @foreach($order->statusEvents->reverse() as $event)
                        <li class="omo-timeline__item">
                            <strong>{{ \App\Models\Ecommerce\Order::STATUSES[$event->to_status] ?? $event->to_status }}</strong>
                            <time>{{ $event->created_at->format('M j, g:i A') }}</time>
                            @if($event->note)<p>{{ $event->note }}</p>@endif
                        </li>
                    @endforeach
                </ol>
            </details>
        @endif

        <p id="omoActionError" class="omo-error" role="alert" hidden></p>
    </div>

    <form id="omoActionForm" class="omo-drawer__actions" data-url="{{ route('seller.orders.update', $order) }}" data-draft-key="seller-{{ auth()->id() }}-order-action-{{ $order->id }}-{{ $order->status }}" data-draft-ajax>
        <input type="hidden" name="expected_status" value="{{ $order->status }}">
                @if($order->canPrintShippingLabel())
                    <a class="omo-btn omo-btn--ghost" href="{{ route('seller.orders.waybill', $order) }}" data-waybill data-waybill-title="{{ $order->number }}">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9V3h12v6M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v7H6z"/></svg>
                        Print shipping label
                    </a>
                @elseif(in_array($order->status, ['placed', 'confirmed', 'preparing', 'ready_for_pickup'], true))
                    <button class="omo-btn omo-btn--ghost" type="button" disabled>Print shipping label</button>
                    <p class="omo-hint">{{ $order->status === 'ready_for_pickup' ? 'Waiting for pickup logistics assignment. Existing ready orders may need routing.' : 'Available after the order is ready for pickup and its pickup logistics is assigned.' }}</p>
                @endif
        @if($order->status === 'placed')
            <button class="omo-btn omo-btn--primary" name="action" value="accept">Accept order</button>
            <button class="omo-btn omo-btn--ghost" type="button" data-omo-cancel-open="decline">Decline</button>
        @elseif($order->status === 'confirmed')
            <button class="omo-btn omo-btn--primary" name="action" value="prepare">Start preparing</button>
            <button class="omo-btn omo-btn--danger-ghost" type="button" data-omo-cancel-open="cancel">Cancel order</button>
        @elseif($order->status === 'preparing')
            @if($order->pickup_request_status === 'declined')<p class="omo-hint omo-hint--warn" role="status">Pickup request returned by Logistics: {{ $order->pickup_decline_reason }}. Resolve this before marking ready again.</p>@endif
            <button class="omo-btn omo-btn--primary" name="action" value="ready">Mark ready for pickup</button>
            <button class="omo-btn omo-btn--danger-ghost" type="button" data-omo-cancel-open="cancel">Cancel order</button>
        @elseif($order->status === 'ready_for_pickup')
            <p class="omo-hint">Ready for pickup. Waiting for logistics to assign a rider and for the rider to scan the parcel at pickup.</p>
            <button class="omo-btn omo-btn--danger-ghost" type="button" data-omo-cancel-open="cancel">Cancel order</button>
        @else
            <p class="omo-hint">{{ $order->status_label }} · No seller action required.</p>
        @endif

        <div data-omo-cancel-modal hidden class="omo-cancel-modal" role="dialog" aria-modal="true" aria-labelledby="omoCancelTitle" tabindex="-1">
            <div class="omo-cancel-modal__panel">
                <h3 id="omoCancelTitle" class="omo-cancel-modal__title" data-omo-cancel-title>Cancel this order?</h3>
                <p class="omo-cancel-modal__copy">This cancels the order before pickup and restores the reserved stock. The buyer will be notified.</p>
                <label class="omo-cancel-modal__label">Reason
                    <select name="reason" data-omo-cancel-reason>
                        <option value="">Choose a reason</option>
                        @foreach(\App\Services\OrderCancellationService::SELLER_REASONS as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label data-omo-cancel-details-wrap hidden class="omo-cancel-modal__label">Tell us more
                    <textarea name="reason_details" maxlength="450" rows="3" data-omo-cancel-details></textarea>
                </label>
                <div class="omo-cancel-modal__actions">
                    <button type="button" class="omo-btn omo-btn--ghost" data-omo-cancel-close>Keep order</button>
                    <button type="submit" class="omo-btn omo-btn--danger" data-omo-confirm-cancel>Confirm cancellation</button>
                </div>
            </div>
        </div>
    </form>
</div>