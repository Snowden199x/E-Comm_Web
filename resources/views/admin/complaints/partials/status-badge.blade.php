{{-- Outlined status pill (as in the mockup). Needs $complaint. Text colours are darkened for contrast. --}}
@php
    $statusText = ['open' => '#92400E', 'in_review' => '#1D4ED8', 'resolved' => '#166534'][$complaint->status] ?? '#444444';
@endphp
<span class="inline-flex items-center whitespace-nowrap rounded-full border px-3 py-0.5 text-xs font-medium"
    style="background-color: {{ $complaint->status_colors['bg'] }}; border-color: {{ $complaint->status_colors['border'] }}; color: {{ $statusText }};">
    {{ $complaint->status_colors['label'] }}
</span>