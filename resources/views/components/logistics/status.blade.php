@props(['status'])

@php
    $legacy = in_array($status, ['to_soc5', 'to_soc6'], true);

    $tone = match ($status) {
        'ready_for_pickup' => 'amber',
        'picked_up', 'in_transit_to_hub' => 'blue',
        'at_sorting_center', 'sorted' => 'violet',
        'at_destination_hub', 'assigned_to_rider' => 'teal',
        'out_for_delivery' => 'brand',
        'delivered', 'completed' => 'green',
        'delivery_failed', 'returned' => 'red',
        default => 'gray',
    };

    $label = $legacy
        ? 'Legacy route record'
        : (\App\Models\Ecommerce\Order::STATUSES[$status] ?? ucfirst(str_replace('_', ' ', (string) $status)));
@endphp
<span {{ $attributes->class(['lg-pill', 'lg-pill--'.$tone]) }}>{{ $label }}</span>