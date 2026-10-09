{{--
    Shipment progress.
      size 'sm' (list):   four icon nodes — To ship › In transit › Out for delivery › Delivered
      size 'lg' (modal):  five bars — Packing › Ready › In transit › Out for delivery › Delivered, with the date each stage was reached
    Expects $order; optional $size, and $stageDates (index 1–5 => Carbon|null) for 'lg'.
--}}
@php
    use App\Models\Ecommerce\Order;

    $size = $size ?? 'sm';
    $status = $order->status;
    $inTransit = ['picked_up', 'at_sorting_center', 'sorted', 'to_soc5', 'to_soc6', 'in_transit_to_hub', 'at_destination_hub'];
    $lastMile = ['assigned_to_rider', 'out_for_delivery', 'delivery_failed'];
    $isDelivered = in_array($status, Order::SHIPMENT_GROUPS['delivered'], true);
@endphp
@if($status === 'cancelled')
    <p class="sh-closed sh-closed--cancelled">Shipment cancelled</p>
@elseif($status === 'returned')
    <p class="sh-closed">Shipment returned</p>
@elseif($size === 'sm')
    @php
        $labels = ['To ship', 'In transit', 'Out for delivery', 'Delivered'];
        $stage = match (true) {
            $isDelivered => 4,
            in_array($status, $lastMile, true) => 3,
            in_array($status, $inTransit, true) => 2,
            default => 1,
        };
        $paths = [
            '<path d="M21 8l-9-5-9 5v8l9 5 9-5z"/><path d="M3 8l9 5 9-5M12 13v8"/>',
            '<path d="M3 7h11v9H3zM14 10h4l3 3v3h-7z"/><circle cx="7" cy="18" r="1.6"/><circle cx="17" cy="18" r="1.6"/>',
            '<circle cx="6" cy="17" r="2.5"/><circle cx="18" cy="17" r="2.5"/><path d="M8.5 17H14l2-8h-3M16 9h2"/>',
            '<path d="M21 8l-9-5-9 5v8l9 5 9-5z"/><path d="M8.5 12.5l2.5 2.5 4.5-5"/>',
        ];
    @endphp
    <ol class="sh-nodes" role="img" aria-label="Stage {{ $stage }} of 4: {{ $labels[$stage - 1] }}{{ $status === 'delivery_failed' ? ' (delivery attempt failed)' : '' }}">
        @foreach($labels as $index => $label)
            @php $n = $index + 1; $done = $n < $stage || ($isDelivered && $n === 4); @endphp
            <li @class(['is-done' => $done, 'is-current' => $n === $stage && ! $done, 'is-warn' => $status === 'delivery_failed' && $n === $stage])>
                <span class="sh-node" aria-hidden="true">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $done ? '<path d="M5 12.5l4.5 4.5L19 7.5"/>' : $paths[$index] !!}</svg>
                </span>
                <span class="sh-node-label">{{ $label }}</span>
            </li>
        @endforeach
    </ol>
@else
    @php
        $labels = ['Packing', 'Ready', 'In transit', 'Out for delivery', 'Delivered'];
        $stage = match (true) {
            in_array($status, ['confirmed', 'preparing'], true) => 1,
            $status === 'ready_for_pickup' => 2,
            $isDelivered => 5,
            in_array($status, $lastMile, true) => 4,
            in_array($status, $inTransit, true) => 3,
            default => 0,
        };
        $stageDates = $stageDates ?? [];
    @endphp
    <ol class="sh-steps" role="img" aria-label="Stage {{ $stage }} of 5: {{ $labels[$stage - 1] ?? 'Not started' }}">
        @foreach($labels as $index => $label)
            @php $n = $index + 1; $date = $stageDates[$n] ?? null; @endphp
            <li @class(['is-done' => $n < $stage || $stage === 5, 'is-current' => $n === $stage && $stage !== 5, 'is-warn' => $status === 'delivery_failed' && $n === $stage])>
                <span>{{ $label }}</span>
                @if($date)<em>{{ $date->format('M j') }}</em>@endif
            </li>
        @endforeach
    </ol>
@endif