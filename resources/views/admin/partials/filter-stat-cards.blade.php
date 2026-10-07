{{--
    Stat cards that also filter the table below (Registrations, User Management).
    Same look and motion as the Seller Compliance cards: tinted round icon, staggered rise,
    lift on hover. Must sit inside the page's Alpine scope (uses `type` and `apply()`).

    $cards: [['key' => user type, 'label', 'value', 'icon' (x-admin.icon name), 'tone' (purple|plum|terracotta|gold)]]
--}}
@php
    $tones = [
        'purple' => 'bg-[#F1E7F3] text-[#5b2963]',
        'plum' => 'bg-[#EFE4F1] text-[#3b1735]',
        'terracotta' => 'bg-[#F8E6DE] text-[#B4573B]',
        'gold' => 'bg-[#F6EDC9] text-[#7A5A00]',
    ];
@endphp

<div class="mb-6 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
    @foreach ($cards as $card)
        <button type="button" @click="type = '{{ $card['key'] }}'; apply()" style="--i: {{ $loop->index }}"
            :aria-pressed="type === '{{ $card['key'] }}'"
            :class="type === '{{ $card['key'] }}'
                ? 'border-[#3b1735] bg-[#F7F1F7] ring-1 ring-[#3b1735]/20'
                : 'border-[#ece4ec] bg-white hover:border-[#cdbbd2]'"
            class="rg-card group flex items-center gap-3 rounded-2xl border p-3.5 text-left shadow-[0_1px_2px_rgba(43,23,48,0.04)]
                   focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40 sm:p-4">
            <span class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-full transition duration-300 ease-vendo group-hover:scale-105 {{ $tones[$card['tone']] ?? $tones['purple'] }}">
                <x-admin.icon :name="$card['icon']" class="h-[22px] w-[22px]" />
            </span>
            <span class="min-w-0 flex-1">
                <span class="block truncate text-[13px] text-gray-500">{{ $card['label'] }}</span>
                <span class="block text-2xl font-semibold leading-tight tracking-tight text-[#2B1730]">{{ number_format($card['value']) }}</span>
            </span>
        </button>
    @endforeach
</div>