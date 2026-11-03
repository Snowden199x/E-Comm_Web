{{--
    Summary cards.
    $cards: [['label', 'value', 'icon' (x-admin.icon name), 'color' (green|orange|red|purple), 'href' (optional)]]
    A card with an href links to the matching tab. Same shape as the Registration and User Management cards:
    tinted fill, matching outline, solid icon chip.
--}}
@php
    $tones = [
        'green' => ['card' => 'border-[#2E8B3E] bg-[#EEF6EC] hover:bg-[#E3F0DF]', 'chip' => 'bg-[#2E8B3E]'],
        'orange' => ['card' => 'border-[#D9795F] bg-[#FBEDE8] hover:bg-[#F8E1D9]', 'chip' => 'bg-[#C96A50]'],
        'red' => ['card' => 'border-[#8D1B26] bg-[#F8E8E9] hover:bg-[#F2D9DB]', 'chip' => 'bg-[#8D1B26]'],
        'purple' => ['card' => 'border-vd-orchid bg-[#F1E9F1] hover:bg-[#E6DEE5]', 'chip' => 'bg-vd-orchid'],
    ];
@endphp

<div class="mb-5 grid grid-cols-2 gap-3 sm:gap-5 lg:grid-cols-4">
    @foreach ($cards as $card)
        @php
            $tone = $tones[$card['color']] ?? $tones['purple'];
            $href = $card['href'] ?? null;
            $tag = $href ? 'a' : 'div';
        @endphp
        <{{ $tag }} @if ($href) href="{{ $href }}" @endif style="--i: {{ $loop->index }}"
            class="sc-card flex items-center gap-3 rounded-[18px] border px-4 py-3 transition-colors duration-200 {{ $tone['card'] }}
                   {{ $href ? 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-plum/40' : '' }}">
            <span class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full text-white {{ $tone['chip'] }}">
                <x-admin.icon :name="$card['icon']" class="h-[18px] w-[18px]" />
            </span>
            <span class="min-w-0 flex-1">
                <span class="block truncate font-display text-[13px] font-medium text-vd-ink">{{ $card['label'] }}</span>
                <span class="block font-display text-lg font-semibold leading-tight tabular-nums text-vd-ink">{{ number_format($card['value']) }}</span>
            </span>
        </{{ $tag }}>
    @endforeach
</div>
