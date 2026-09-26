<div class="omo-drawer__inner">
    <div class="omo-drawer__head"><span class="omo-drawer__title">Order #{{ $order->number }}</span><button type="button" class="omo-drawer__close" id="omoDrawerClose" aria-label="Close order details">×</button></div>
    <span class="omo-drawer__badge omo-drawer__badge--new">{{ $order->status_label }}</span>
    <div class="omo-drawer__body">
        <section class="omo-section"><h3 class="omo-section__head">Order Information</h3><dl class="omo-info-grid"><div><dt>Order ID</dt><dd>{{ $order->number }}</dd></div><div><dt>Order Date</dt><dd>{{ $order->created_at->format('M j, Y g:i A') }}</dd></div><div><dt>Status</dt><dd><span class="omo-pill omo-pill--{{ $order->seller_group }}">{{ $order->status_label }}</span></dd></div></dl></section>
        <section class="omo-section"><h3 class="omo-section__head">Customer Information</h3><dl class="omo-info-grid omo-info-grid--2"><div><dt>Customer Name</dt><dd>{{ $order->buyer?->name ?? 'Buyer unavailable' }}</dd></div><div><dt>Contact Number</dt><dd>{{ $order->buyer?->phone_number ?? 'Not provided' }}</dd></div><div style="grid-column:1/-1"><dt>Delivery Address</dt><dd>{{ $order->shipping_address ?: 'Not provided' }}</dd></div><div style="grid-column:1/-1"><dt>Pickup courier</dt><dd>{{ $order->courier?->name ?? 'Awaiting logistics assignment' }}</dd></div>@if($order->delivery_courier_id)<div style="grid-column:1/-1"><dt>Delivery rider</dt><dd>{{ $order->deliveryCourier?->name ?? 'Unavailable' }}</dd></div>@endif</dl></section>
        <section class="omo-section"><h3 class="omo-section__head">Order Items</h3>
            @foreach ($order->items as $item)<div class="omo-item">@if($image=$item->product?->images->first())<img class="omo-item__img" src="{{ asset('storage/'.$image->path) }}" alt="{{ $item->product->name }}">@else<span class="omo-item__img" aria-hidden="true"></span>@endif<div class="omo-item__info"><strong>{{ $item->product?->name ?? 'Product unavailable' }}</strong><span class="omo-item__sub">{{ implode(' / ',array_filter([$item->color,$item->size])) }}</span><span class="omo-item__sub">Qty: {{ $item->quantity }}</span></div><div class="omo-item__price"><span>₱{{ number_format($item->price,2) }} each</span><span class="omo-item__sub">₱{{ number_format($item->quantity*$item->price,2) }}</span></div></div>@endforeach
        </section>
        <section class="omo-section"><h3 class="omo-section__head">Payment</h3><div class="omo-payment"><dl class="omo-payment__method"><dt>Payment Method</dt><dd>{{ strtoupper($order->payment_mode ?? 'Not provided') }}</dd></dl><div class="omo-payment__lines"><div class="omo-payment__line"><span>Items subtotal</span><span>₱{{ number_format($order->items->sum(fn($item)=>$item->quantity*$item->price),2) }}</span></div><div class="omo-payment__line omo-payment__line--total"><span>Total Amount</span><span>₱{{ number_format($order->total_amount,2) }}</span></div></div></div></section>
        @if($order->statusEvents->isNotEmpty())<section class="omo-section"><h3 class="omo-section__head">Order History</h3>@foreach($order->statusEvents as $event)<p class="omo-history"><strong>{{ \App\Models\Ecommerce\Order::STATUSES[$event->to_status]??$event->to_status }}</strong> · {{ $event->created_at->format('M j, g:i A') }}<br>{{ $event->note }}</p>@endforeach</section>@endif
        @if($order->canPrintShippingLabel())
            <a class="omo-btn omo-btn--ghost" href="{{ route('seller.orders.waybill',$order) }}" target="_blank" rel="noopener">Print Shipping Label</a>
        @elseif(in_array($order->status, ['placed', 'confirmed', 'preparing', 'ready_for_pickup'], true))
            <button class="omo-btn omo-btn--ghost" type="button" disabled>Print Shipping Label</button>
            <p class="omo-history">{{ $order->status === 'ready_for_pickup' ? 'Waiting for pickup logistics assignment. Existing ready orders may need routing.' : 'Available after the order is ready for pickup and its pickup logistics is assigned.' }}</p>
        @endif
        <p id="omoActionError" class="omo-error" role="alert" hidden></p>
    </div>
    <form id="omoActionForm" class="omo-drawer__actions" data-url="{{ route('seller.orders.update',$order) }}"><input type="hidden" name="expected_status" value="{{ $order->status }}">
        @if($order->status==='placed')<label class="omo-reason">Reason if declining<textarea name="reason" maxlength="500" placeholder="Explain why this order cannot be fulfilled"></textarea></label><button class="omo-btn omo-btn--primary" name="action" value="accept">Accept Order</button><button class="omo-btn omo-btn--ghost" name="action" value="decline">Decline Order</button>
        @elseif($order->status==='confirmed')<button class="omo-btn omo-btn--primary" name="action" value="prepare">Start Preparing</button>
        @elseif($order->status==='preparing')<button class="omo-btn omo-btn--primary" name="action" value="ready">Mark Ready for Pickup</button>
        @elseif($order->status==='ready_for_pickup')<p class="omo-history">Ready for pickup. Waiting for logistics to assign a rider and for the rider to scan the parcel at pickup.</p>
        @else<p class="omo-history">{{ $order->status_label }} · No seller action required.</p>@endif
    </form>
</div>
