{{--
    Buyer empty state.

    Usage:
        <x-buyer.empty-state icon="search" title="No products found"
            text="Check the spelling or try a more general term.">
            <a href="..." class="...">Browse all products</a>
        </x-buyer.empty-state>

    Props
        icon     search | box | store | cart | orders | review | chat   (default: box)
        title    short heading, says what is missing
        text     one sentence on why, or what the buyer can do next
        compact  smaller version for use inside a card or a section
    The default slot holds the action buttons (links or buttons), if any.
--}}
@props([
    'icon' => 'box',
    'title',
    'text' => null,
    'compact' => false,
])

<div {{ $attributes->class([
    'flex flex-col items-center text-center',
    'px-5 py-14 sm:py-16' => ! $compact,
    'px-4 py-8' => $compact,
]) }} role="status">

    {{-- Flat illustration: tinted disc, one line icon, two small accents. Solid colours only. --}}
    <svg class="{{ $compact ? 'h-20 w-20' : 'h-28 w-28' }}" viewBox="0 0 112 112" fill="none" aria-hidden="true">
        <circle cx="56" cy="58" r="44" fill="#f3e8f5" />
        <circle cx="92" cy="26" r="5" fill="#e8c874" />
        <circle cx="19" cy="83" r="3.5" fill="#d8bfdc" />
        <rect x="14" y="24" width="7" height="7" rx="1.5" transform="rotate(18 17.5 27.5)" fill="#d8bfdc" />
        <g transform="translate(32 34) scale(2)" stroke="#52245b" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            @switch($icon)
                @case('search')
                    <circle cx="10.5" cy="10.5" r="6" fill="#ffffff" />
                    <path d="M15 15l5 5" />
                    <path d="M8.5 10.5h4" />
                    @break

                @case('store')
                    <path d="M5 12v8h14v-8" fill="#ffffff" />
                    <path d="M3 9l1.6-5h14.8L21 9" fill="#ffffff" />
                    <path d="M3 9a3 3 0 0 0 6 0a3 3 0 0 0 6 0a3 3 0 0 0 6 0" fill="#ffffff" />
                    <path d="M10 20v-4h4v4" />
                    @break

                @case('cart')
                    <path d="M3 4h2.5l2.2 10.5h9.6L19.5 7H6.4" fill="#ffffff" />
                    <circle cx="9.5" cy="19" r="1.3" fill="#ffffff" />
                    <circle cx="16.5" cy="19" r="1.3" fill="#ffffff" />
                    @break

                @case('orders')
                    <path d="M7 3h10a1 1 0 0 1 1 1v17l-3-2-3 2-3-2-3 2V4a1 1 0 0 1 1-1z" fill="#ffffff" />
                    <path d="M9.5 8h5M9.5 12h5" />
                    @break

                @case('review')
                    <path d="M12 3l2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1L3.2 9.5l6.1-.9z" fill="#ffffff" />
                    @break

                @case('chat')
                    <path d="M4 5h16v11H9l-5 4z" fill="#ffffff" />
                    <path d="M8.5 10.5h.01M12 10.5h.01M15.5 10.5h.01" stroke-width="2" />
                    @break

                @default {{-- box --}}
                    <path d="M3 8l9-5 9 5v8l-9 5-9-5z" fill="#ffffff" />
                    <path d="M3 8l9 5 9-5M12 13v8" />
                @endswitch
        </g>
    </svg>

    <h3 class="{{ $compact ? 'mt-2 text-[14px]' : 'mt-4 text-[17px]' }} font-semibold text-[#2b1730]">{{ $title }}</h3>

    @if ($text)
        <p class="{{ $compact ? 'mt-1 text-[12px]' : 'mt-1.5 text-[13px]' }} max-w-[380px] leading-5 text-[#7a6a7e]">{{ $text }}</p>
    @endif

    @if (! $slot->isEmpty())
        <div class="{{ $compact ? 'mt-4' : 'mt-6' }} flex flex-wrap items-center justify-center gap-2.5">
            {{ $slot }}
        </div>
    @endif
</div>