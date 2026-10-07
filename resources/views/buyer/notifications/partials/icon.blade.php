{{--
    Notification type icon (round, tinted). Used by the Notifications page and the header dropdown.
    Params: $notification, $size (optional Tailwind size for the circle, default h-10 w-10)
    The type is guessed from notification->type (order, message, review, announcement, warning), general bell otherwise.
--}}
@php
    $type = \Illuminate\Support\Str::lower((string) $notification->type);
    $k = match (true) {
        \Illuminate\Support\Str::contains($type, ['warning', 'violation', 'suspend']) => 'warning',
        \Illuminate\Support\Str::contains($type, ['announcement', 'policy']) => 'announcement',
        \Illuminate\Support\Str::contains($type, ['review', 'rating']) => 'review',
        \Illuminate\Support\Str::contains($type, ['message', 'chat']) => 'message',
        \Illuminate\Support\Str::contains($type, ['order', 'shipment', 'delivery', 'cancel']) => 'order',
        default => 'general',
    };
    $tone = [
        'order' => 'bg-[#f5ecf6] text-[#52245b]',
        'message' => 'bg-[#e8f1fb] text-[#1f5a99]',
        'review' => 'bg-[#fbf3dc] text-[#7a5a0c]',
        'announcement' => 'bg-[#eaf5ee] text-[#2e6b46]',
        'warning' => 'bg-[#fdf1f3] text-[#a32b43]',
        'general' => 'bg-[#f3eef4] text-[#6d5d71]',
    ][$k];
    $box = $size ?? 'h-10 w-10';
@endphp
<span class="grid {{ $box }} flex-shrink-0 place-items-center rounded-full {{ $tone }}" data-kind="{{ $k }}" aria-hidden="true">
    <svg class="h-[50%] w-[50%]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        @switch($k)
            @case('order') <path d="M3 8l9-5 9 5v8l-9 5-9-5z" /><path d="M3 8l9 5 9-5M12 13v8" /> @break
            @case('message') <path d="M4 5h16v11H9l-5 4z" /> @break
            @case('review') <path d="M12 3l2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1L3.2 9.5l6.1-.9z" /> @break
            @case('announcement') <path d="M4 10v4h3l7 4V6L7 10z" /><path d="M17.5 9a4 4 0 0 1 0 6" /> @break
            @case('warning') <path d="M12 4 3 20h18z" /><path d="M12 10v4M12 17.5h.01" /> @break
            @default <path d="M6 16V11a6 6 0 0 1 12 0v5l1.5 2h-15z" /><path d="M10 20a2 2 0 0 0 4 0" />
        @endswitch
    </svg>
</span>