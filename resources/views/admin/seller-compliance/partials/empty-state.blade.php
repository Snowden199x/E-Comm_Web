{{--
    Empty state for the Seller Compliance tables.
    Needs: $icon (x-admin.icon name), $title, $text, $tone (purple|green|orange|red).
    Optional: $filteredTitle, $filteredText — shown instead when a search or filter is active.
    The "Clear filters" button uses clearAll() from the surrounding scTable scope.
--}}
@php
    $isFiltered = request()->filled('search')
        || request()->filled('category_id')
        || (request('date_filter', 'all') !== 'all' && request()->filled('date_filter'))
        || request()->filled('custom_date');

    $tones = [
        'purple' => 'bg-[#F1E7F3] text-[#5b2963]',
        'green' => 'bg-green-50 text-green-700',
        'orange' => 'bg-orange-50 text-orange-600',
        'red' => 'bg-red-50 text-red-700',
    ];
    $ringTones = [
        'purple' => 'border-[#cdbbd2]',
        'green' => 'border-green-300',
        'orange' => 'border-orange-300',
        'red' => 'border-red-300',
    ];

    $shownTone = $isFiltered ? 'purple' : ($tone ?? 'purple');
    $shownIcon = $isFiltered ? 'search' : $icon;
    $shownTitle = $isFiltered ? ($filteredTitle ?? 'No results found') : $title;
    $shownText = $isFiltered ? ($filteredText ?? 'Nothing matches these filters. Try a different search or clear the filters.') : $text;
@endphp

<div class="flex flex-col items-center px-6 py-16 text-center">
    <div class="relative mb-5 flex h-20 w-20 items-center justify-center">
        <span aria-hidden="true" class="sc-empty-ring absolute inset-0 rounded-full border-2 {{ $ringTones[$shownTone] }}"></span>
        <span aria-hidden="true" class="sc-empty-ring absolute inset-0 rounded-full border-2 {{ $ringTones[$shownTone] }}"></span>
        <span class="sc-empty-art relative flex h-16 w-16 items-center justify-center rounded-full {{ $tones[$shownTone] }}">
            <x-admin.icon :name="$shownIcon" class="h-8 w-8" stroke="1.5" />
        </span>
    </div>

    <p class="text-base font-semibold text-[#2B1730]">{{ $shownTitle }}</p>
    <p class="mt-1 max-w-sm text-sm text-gray-500">{{ $shownText }}</p>

    @if ($isFiltered)
        <button type="button" @click="clearAll()"
            class="mt-5 inline-flex h-10 items-center gap-2 rounded-full border border-[#cdbbd2] px-5 text-sm font-medium text-[#3b1735]
                   transition duration-200 hover:bg-[#3b1735] hover:text-white active:scale-95
                   focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40 focus-visible:ring-offset-2">
            <x-admin.icon name="x" class="h-4 w-4" /> Clear filters
        </button>
    @endif
</div>