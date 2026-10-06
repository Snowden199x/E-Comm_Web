{{--
    Five SVG stars, filled up to the rounded rating.
    Usage: <x-buyer.stars :rating="4.5" size="h-4 w-4" />
--}}
@props(['rating' => 0, 'size' => 'h-3.5 w-3.5'])

@php $filled = (int) round((float) $rating); @endphp

<span {{ $attributes->class(['inline-flex items-center gap-0.5']) }} role="img"
    aria-label="{{ number_format((float) $rating, 1) }} out of 5 stars">
    @for ($i = 1; $i <= 5; $i++)
        <svg class="{{ $size }} {{ $i <= $filled ? 'text-[#e0b84a]' : 'text-[#e3d9e5]' }}" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2.5l2.9 6.1 6.6.8-4.9 4.6 1.3 6.6L12 17.3l-5.9 3.3 1.3-6.6L2.5 9.4l6.6-.8z" /></svg>
    @endfor
</span>