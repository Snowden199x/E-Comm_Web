{{--
    Summary cards.
    $cards: [['label', 'value', 'icon' (x-admin.icon name), 'color' (green|orange|red|purple), 'href' (optional)]]
    A card with an href is a link to the matching tab.
--}}
@php
    $tones = [
        'green' => ['icon' => 'bg-green-50 text-green-700', 'hover' => 'hover:border-green-300', 'ring' => 'focus-visible:ring-green-600/40'],
        'orange' => ['icon' => 'bg-orange-50 text-orange-600', 'hover' => 'hover:border-orange-300', 'ring' => 'focus-visible:ring-orange-500/40'],
        'red' => ['icon' => 'bg-red-50 text-red-700', 'hover' => 'hover:border-red-300', 'ring' => 'focus-visible:ring-red-600/40'],
        'purple' => ['icon' => 'bg-[#F1E7F3] text-[#5b2963]', 'hover' => 'hover:border-[#cdbbd2]', 'ring' => 'focus-visible:ring-[#3b1735]/40'],
    ];
@endphp

<div class="mb-6 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
    @foreach ($cards as $card)
        @php
            $tone = $tones[$card['color']] ?? $tones['purple'];
            $href = $card['href'] ?? null;
            $tag = $href ? 'a' : 'div';
        @endphp
        <{{ $tag }} @if ($href) href="{{ $href }}" @endif style="--i: {{ $loop->index }}"
            class="sc-card group flex items-center gap-3 rounded-2xl border border-[#ece4ec] bg-white p-3.5 shadow-[0_1px_2px_rgba(43,23,48,0.04)] sm:p-4
                   {{ $href ? $tone['hover'] . ' focus-visible:outline-none focus-visible:ring-2 ' . $tone['ring'] : '' }}">
            <span class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-full transition duration-300 ease-vendo group-hover:scale-105 {{ $tone['icon'] }}">
                <x-admin.icon :name="$card['icon']" class="h-[22px] w-[22px]" />
            </span>
            <span class="min-w-0 flex-1">
                <span class="block truncate text-[13px] text-gray-500">{{ $card['label'] }}</span>
                <span class="block text-2xl font-semibold leading-tight tracking-tight text-[#2B1730]">{{ number_format($card['value']) }}</span>
            </span>
            @if ($href)
                <x-admin.icon name="arrow-right" class="h-4 w-4 flex-shrink-0 -translate-x-1 text-gray-300 opacity-0 transition duration-200 group-hover:translate-x-0 group-hover:text-[#3b1735] group-hover:opacity-100" />
            @endif
        </{{ $tag }}>
    @endforeach
</div>