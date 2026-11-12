@php
    use App\Models\Ecommerce\Order;

    $events = $order->statusEvents->sortBy('id');
    $firstAt = fn (array $statuses) => $events->first(fn ($e) => in_array($e->to_status, $statuses, true))?->created_at;
    $stageDates = [
        1 => $firstAt(['confirmed']) ?? $firstAt(['preparing']),
        2 => $firstAt(['ready_for_pickup']),
        3 => $firstAt(['picked_up', 'at_sorting_center', 'sorted', 'to_soc5', 'to_soc6', 'in_transit_to_hub', 'at_destination_hub']),
        4 => $firstAt(['assigned_to_rider', 'out_for_delivery', 'delivery_failed']),
        5 => ($order->delivered_at ? \Illuminate\Support\Carbon::parse($order->delivered_at) : null) ?? $firstAt(['delivered', 'completed']),
    ];

    $beforePickup = in_array($order->status, ['confirmed', 'preparing', 'ready_for_pickup'], true);
    $rider = $order->courier;
    $canTrack = ! in_array($order->status, ['delivered', 'completed', 'cancelled', 'returned'], true);
    $group = $order->shipment_group;
    $pillFor = ['to_ship' => 'pack', 'in_transit' => 'pending', 'delivered' => 'completed', 'cancelled' => 'cancelled'];
    $cancelNote = $events->last(fn ($e) => $e->to_status === 'cancelled')?->note;
    $mode = strtoupper($order->payment_mode ?? 'COD');
    $subtotal = $order->items->sum(fn ($item) => $item->quantity * $item->price);
    $voucherCode = $order->voucher_code ?? null;
    $voucherDiscount = (float) ($order->voucher_discount ?? 0);
    $buyerNote = trim((string) ($order->buyer_note ?? ''));
    $variation = fn ($item) => implode(' / ', array_filter([$item->color, $item->size])) ?: null;
    $hub = $order->logisticsCenter;
    $destination = $order->destinationLogisticsCenter;

    // What the seller should know or do right now.
    $next = match (true) {
        in_array($order->status, ['confirmed', 'preparing'], true) => ['info', 'Pack this order', 'Pack the items, then mark the order ready for pickup in Orders.'],
        $order->status === 'ready_for_pickup' && ! $order->courier_id => ['wait', 'Waiting for a pickup rider', 'Logistics will assign a rider soon. The label unlocks after pickup is assigned.'],
        $order->status === 'ready_for_pickup' => ['info', 'Hand the parcel to '.($rider?->name ?? 'the rider'), 'Print the label and attach it to the parcel for the rider to scan.'],
        $order->status === 'delivery_failed' => ['danger', 'Delivery attempt failed', 'Logistics will retry and the buyer is notified. You do not need to do anything.'],
        in_array($order->status, ['assigned_to_rider', 'out_for_delivery'], true) => ['info', 'On its way to the buyer', 'A rider is delivering this parcel. You do not need to do anything.'],
        $group === 'in_transit' => ['info', 'Moving between hubs', 'This page updates at each scan.'],
        $group === 'delivered' => ['success', 'Delivered', $stageDates[5] ? 'Delivered on '.$stageDates[5]->format('M j, Y \a\t g:i A').'.' : 'This parcel reached the buyer.'],
        $group === 'cancelled' => ['danger', 'Order cancelled', $cancelNote ?: 'This order was cancelled before pickup.'],
        default => ['info', $order->status_label, ''],
    };
@endphp

{{-- ============ SUMMARY ============ --}}
<div class="sh-d-top">
    <div class="sh-d-top__row">
        <h3 class="ops-detail-number">{{ $order->number }}</h3>
        <span class="omo-pill omo-pill--{{ $pillFor[$group] ?? 'pack' }}"><i aria-hidden="true"></i>{{ Order::SHIPMENT_LABELS[$group] ?? $group }}</span>
        <span class="sh-d-status">{{ $order->status_label }}</span>
    </div>
    <p class="sh-d-sub">Ordered {{ $order->created_at->format('M j, Y · g:i A') }}</p>
    @include('seller.shipments.partials.progress', ['order' => $order, 'size' => 'lg', 'stageDates' => $stageDates])

    <div class="sh-next sh-next--{{ $next[0] }}" role="status">
        <strong>{{ $next[1] }}</strong>
        @if($next[2])<span>{{ $next[2] }}</span>@endif
    </div>

    <div class="sh-d-actions">
        @if($order->canPrintShippingLabel())
            <a class="ops-button ops-button--primary" href="{{ route('seller.orders.waybill', $order) }}" data-waybill data-waybill-title="{{ $order->number }}">Print shipping label</a>
        @elseif($beforePickup)
            <button class="ops-button" type="button" disabled title="Unlocks after you mark the order ready and pickup is assigned">Print shipping label</button>
        @endif
        @if($beforePickup)
            <a class="ops-button" href="{{ route('seller.orders.index', ['order' => $order->id]) }}">View order</a>
            <button class="ops-button ops-button--danger" type="button" data-ops-modal-open="#ops-cancel-dialog-{{ $order->id }}">Cancel order</button>
        @endif
    </div>
</div>

@if($beforePickup)
    <dialog id="ops-cancel-dialog-{{ $order->id }}" class="ops-cancel-dialog" aria-labelledby="ops-cancel-title-{{ $order->id }}">
        <form method="POST" action="{{ route('seller.shipments.update', $order) }}" data-operation class="ops-form" data-draft-key="seller-{{ auth()->id() }}-cancel-shipment-{{ $order->id }}" data-draft-ajax>
            @csrf
            @method('PATCH')
            <input type="hidden" name="action" value="cancel">
            <input type="hidden" name="expected_status" value="{{ $order->status }}">
            <h3 id="ops-cancel-title-{{ $order->id }}">Cancel this order?</h3>
            <p class="ops-muted">The buyer and rider are notified, and reserved stock goes back to your inventory.</p>
            <label>Reason
                <select name="reason" required>
                    <option value="">Choose a reason</option>
                    @foreach(\App\Services\OrderCancellationService::SELLER_REASONS as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label data-ops-cancel-details-wrap hidden>Add details
                <textarea name="reason_details" maxlength="450" rows="3"></textarea>
            </label>
            <p class="ops-alert" data-form-error role="alert" hidden></p>
            <div class="ops-cancel-dialog__actions">
                <button class="ops-button" type="button" data-ops-modal-close>Keep order</button>
                <button class="ops-button ops-button--danger" type="submit">Confirm cancellation</button>
            </div>
        </form>
    </dialog>
@endif

@if($order->canSellerReschedulePickup() || $order->canSellerReportPickup())
    <section class="ops-section">
        <h3>Pickup schedule</h3>
        @if($order->pickup_attempts > 0)
            <p class="ops-muted">{{ $order->pickup_attempts }} pickup assignment(s) expired before a rider scan.</p>
        @endif
        @if($order->canSellerReschedulePickup())
            <details class="ops-action-details">
                <summary>Move pickup ({{ $order->pickupReschedulesLeft() }} left)</summary>
                <form method="POST" action="{{ route('seller.orders.pickup-reschedule', $order) }}" data-operation class="ops-form" data-draft-ajax>
                    @csrf
                    @method('PATCH')
                    <label>New pickup date and time
                        <input type="datetime-local" name="pickup_scheduled_at" required
                            value="{{ ($order->pickup_scheduled_at ?? now()->addDay()->setTime(9, 0))->format('Y-m-d\TH:i') }}">
                    </label>
                    <p class="ops-caption">You can move a pickup up to {{ \App\Models\Ecommerce\Order::PICKUP_RESCHEDULE_LIMIT }} times per order, and only before the rider scans the parcel.</p>
                    <p class="ops-alert" data-form-error role="alert" hidden></p>
                    <button class="ops-button ops-button--primary">Save new pickup time</button>
                </form>
            </details>
        @endif
        @if($order->canSellerReportPickup())
            <details class="ops-action-details">
                <summary>Report a pickup problem to logistics</summary>
                <form method="POST" action="{{ route('seller.orders.pickup-report', $order) }}" data-operation class="ops-form" data-draft-ajax>
                    @csrf
                    <label>What happened?
                        <textarea name="details" rows="3" minlength="10" maxlength="600" required placeholder="e.g. The assigned rider did not arrive during the pickup window."></textarea>
                    </label>
                    <p class="ops-caption">The pickup hub is notified and reviews the open assignment. Report once per pickup.</p>
                    <p class="ops-alert" data-form-error role="alert" hidden></p>
                    <button class="ops-button ops-button--danger">Send report to logistics</button>
                </form>
            </details>
        @endif
    </section>
@endif

{{-- ============ MESSAGE FROM BUYER ============ --}}
@if($buyerNote !== '')
    <section class="ops-section sh-note">
        <h3>Note from the buyer</h3>
        <blockquote>{{ $buyerNote }}</blockquote>
    </section>
@endif

{{-- ============ DELIVERY ============ --}}
<section class="ops-section">
    <h3>Delivery</h3>
    <div class="sh-route" aria-label="Delivery route">
        <div><span>Pickup hub</span><strong>{{ $hub?->business_name ?? 'Awaiting assignment' }}</strong>@if($hub)<small>{{ implode(', ', array_filter([$hub->municipality, $hub->province])) }}</small>@endif</div>
        <i aria-hidden="true">→</i>
        <div><span>Destination</span><strong>{{ $destination?->business_name ?? ($order->shipping_city ?: 'Pending') }}</strong><small>{{ $destination ? implode(', ', array_filter([$destination->municipality, $destination->province])) : ($order->shipping_province ?: 'Hub assignment pending') }}</small></div>
    </div>
    <dl class="ops-info">
        <div><dt>Pickup schedule</dt><dd>{{ $order->pickup_scheduled_at?->format('M j, Y · g:i A') ?? 'Not scheduled' }}@if($order->pickup_reschedule_count > 0)<small> · moved {{ $order->pickup_reschedule_count }} of {{ \App\Models\Ecommerce\Order::PICKUP_RESCHEDULE_LIMIT }} times</small>@endif</dd></div>
        <div><dt>Pickup rider</dt><dd>{{ $rider?->name ?? 'Awaiting assignment' }}</dd></div>
        <div><dt>Delivery rider</dt><dd>{{ $order->deliveryCourier?->name ?? 'Awaiting assignment' }}</dd></div>
        <div><dt>Carrier</dt><dd>{{ $order->carrier_name ?: 'Not recorded' }}</dd></div>
        <div><dt>Estimated delivery</dt><dd>{{ ($order->estimated_delivery_from && $order->estimated_delivery_to) ? $order->estimated_delivery_from->format('M j').' – '.$order->estimated_delivery_to->format('M j, Y') : 'Not scheduled' }}</dd></div>
        <div class="ops-full"><dt>Vendo tracking reference</dt><dd class="sh-track"><code>{{ $order->tracking_number }}</code><button type="button" class="sh-copy" data-sh-copy="{{ $order->tracking_number }}">Copy</button></dd></div>
        @if($order->carrier_tracking_number)
            <div class="ops-full"><dt>Carrier tracking number</dt><dd class="sh-track"><code>{{ $order->carrier_tracking_number }}</code><button type="button" class="sh-copy" data-sh-copy="{{ $order->carrier_tracking_number }}">Copy</button></dd></div>
        @endif
    </dl>

    @if($canTrack)
        <details class="ops-action-details">
            <summary>Update carrier details</summary>
            <form method="POST" action="{{ route('seller.shipments.tracking', $order) }}" data-operation class="ops-form" data-draft-key="seller-{{ auth()->id() }}-tracking-{{ $order->id }}-{{ $order->revision }}" data-draft-ajax>
                @csrf
                @method('PATCH')
                <input type="hidden" name="expected_status" value="{{ $order->status }}">
                <input type="hidden" name="expected_revision" value="{{ $order->revision }}">
                <p class="ops-caption">Enter the details your carrier gave you. The assigned rider and shipping fee stay the same.</p>
                <label>Carrier name<input name="carrier_name" maxlength="100" value="{{ $order->carrier_name }}"></label>
                <label>Carrier tracking number<input name="carrier_tracking_number" maxlength="100" value="{{ $order->carrier_tracking_number }}"></label>
                <div class="ops-form-grid">
                    <label>Estimated delivery from<input type="date" name="estimated_delivery_from" value="{{ $order->estimated_delivery_from?->format('Y-m-d') }}"></label>
                    <label>Estimated delivery to<input type="date" name="estimated_delivery_to" value="{{ $order->estimated_delivery_to?->format('Y-m-d') }}"></label>
                </div>
                <p class="ops-alert" data-form-error role="alert" hidden></p>
                <button class="ops-button ops-button--primary">Save carrier details</button>
            </form>
        </details>
    @endif
</section>

{{-- ============ CUSTOMER ============ --}}
<section class="ops-section">
    <h3>Customer</h3>
    <dl class="ops-info">
        <div><dt>Name</dt><dd>{{ $order->buyer?->name ?? 'Buyer unavailable' }}</dd></div>
        <div>
            <dt>Contact number</dt>
            <dd class="sh-track">
                @if($order->buyer?->phone_number)<span>{{ $order->buyer->phone_number }}</span><button type="button" class="sh-copy" data-sh-copy="{{ $order->buyer->phone_number }}">Copy</button>@else Not provided @endif
            </dd>
        </div>
        <div class="ops-full">
            <dt>Delivery address</dt>
            <dd class="sh-address-row"><span>{{ $order->shipping_address ?: 'Not provided' }}</span>@if($order->shipping_address)<button type="button" class="sh-copy" data-sh-copy="{{ $order->shipping_address }}">Copy</button>@endif</dd>
        </div>
    </dl>
</section>

{{-- ============ ITEMS & PAYMENT ============ --}}
<section class="ops-section">
    <h3>Items <span class="sh-count">{{ $order->items->sum('quantity') }}</span></h3>
    <ul class="sh-items">
        @foreach($order->items as $item)
            <li>
                <span class="sh-items__img">@include('seller.operations.image', ['product' => $item->product])</span>
                <span class="sh-items__body">
                    <strong>{{ $item->product?->name ?? 'Product unavailable' }}</strong>
                    @if($variation($item))<small>{{ $variation($item) }}</small>@endif
                    <small>{{ $item->quantity }} × ₱{{ number_format($item->price, 2) }}</small>
                </span>
                <b>₱{{ number_format($item->quantity * $item->price, 2) }}</b>
            </li>
        @endforeach
    </ul>
    <dl class="ops-totals">
        <div><dt>Items subtotal</dt><dd>₱{{ number_format($subtotal, 2) }}</dd></div>
        <div><dt>Shipping fee</dt><dd>₱{{ number_format($order->shipping_fee, 2) }}</dd></div>
        @if($voucherCode && $voucherDiscount > 0)
            <div class="sh-voucher-line"><dt>Voucher <code>{{ $voucherCode }}</code></dt><dd>−₱{{ number_format($voucherDiscount, 2) }}</dd></div>
        @endif
        <div><dt>Total amount</dt><dd>₱{{ number_format($order->payable_total, 2) }}</dd></div>
    </dl>
    <p class="sh-payline">
        Payment: <b class="{{ $mode === 'COD' ? 'sh-pay--cod' : 'sh-pay--paid' }}">{{ $mode }}</b>
        <span>{{ $mode === 'COD' ? 'The rider collects ₱'.number_format($order->payable_total, 2).' on delivery.' : 'Paid online.' }}</span>
    </p>
</section>

{{-- ============ HISTORY ============ --}}
<section class="ops-section">
    <h3>Shipment history</h3>
    @forelse($events->reverse() as $event)
        <div class="sh-event">
            <strong>{{ Order::STATUSES[$event->to_status] ?? $event->to_status }}</strong>
            <small>{{ $event->created_at->format('M j, Y · g:i A') }} · {{ $event->created_at->diffForHumans() }}</small>
            @if($event->note)<p>{{ $event->note }}</p>@endif
        </div>
    @empty
        <p class="ops-muted">No status updates yet.</p>
    @endforelse
</section>
