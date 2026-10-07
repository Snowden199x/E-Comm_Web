@props(['status', 'compact' => false])

@php
    // Display-only tracker built from the order's current status. It reads the
    // status vocabulary only; it does not claim any scan or SH arrival happened.
    $steps = ['Picked up', 'At hub', 'Sorted', 'In transit', 'At destination', 'Out for delivery'];

    $current = match ($status) {
        'ready_for_pickup' => -1,
        'picked_up' => 0,
        'at_sorting_center' => 1,
        'sorted', 'to_soc5', 'to_soc6' => 2,
        'in_transit_to_hub' => 3,
        'at_destination_hub', 'assigned_to_rider' => 4,
        'out_for_delivery' => 5,
        'delivered', 'completed' => 6,
        default => null,
    };
@endphp
@if ($current !== null)
    <ol {{ $attributes->class(['lg-track', 'lg-track--compact' => $compact]) }} aria-label="Parcel progress: {{ $current >= 6 ? 'Delivered' : ($current < 0 ? 'Awaiting pickup' : $steps[$current]) }}">
        @foreach ($steps as $index => $label)
            <li @class(['is-done' => $index < $current, 'is-current' => $index === $current]) @if ($index === $current) aria-current="step" @endif title="{{ $label }}">{{ $label }}</li>
        @endforeach
    </ol>
@endif