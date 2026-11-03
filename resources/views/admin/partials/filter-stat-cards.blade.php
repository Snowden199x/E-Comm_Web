{{--
    Count cards that also filter the table below (Registrations, User Management).
    Orchid outline, soft grey-mauve fill, solid orchid icon chip. The active card gets a plum border and a
    ring, and clicking it again clears the filter. Must sit inside the page's Alpine scope (uses `type` and `apply()`).

    $cards: [['key' => user type, 'label', 'value', 'img' => asset path of a ready-made round icon (optional),
              'icon' => x-admin.icon name drawn white on an orchid circle (used when there is no img)]]
--}}
<div class="mb-5 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
    @foreach ($cards as $card)
        <button type="button" @click="type = type === '{{ $card['key'] }}' ? 'all' : '{{ $card['key'] }}'; apply()" style="--i: {{ $loop->index }}"
            :aria-pressed="type === '{{ $card['key'] }}'"
            :class="type === '{{ $card['key'] }}' ? 'border-vd-plum bg-[#DDD0DB] ring-2 ring-vd-orchid/30' : 'border-vd-orchid bg-[#E6DEE5] hover:-translate-y-0.5 hover:border-vd-plum hover:bg-[#E0D5DF]'"
            class="rg-card flex items-center gap-3 rounded-[18px] border px-4 py-3 text-left
                   focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-plum/40">
            @if (! empty($card['img']))
                <img src="{{ asset($card['img']) }}" alt="" width="36" height="36" class="h-9 w-9 flex-shrink-0">
            @else
                <span class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-vd-orchid text-white">
                    <x-admin.icon :name="$card['icon'] ?? 'package'" class="h-[18px] w-[18px]" />
                </span>
            @endif
            <span class="min-w-0 flex-1">
                <span class="block truncate font-display text-[13px] font-medium text-vd-ink">{{ $card['label'] }}</span>
                <span class="block font-display text-lg font-semibold leading-tight tabular-nums text-vd-ink">{{ number_format($card['value']) }}</span>
            </span>
        </button>
    @endforeach
</div>
