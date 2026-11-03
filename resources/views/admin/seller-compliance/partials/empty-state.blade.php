{{--
    Empty state for the Seller Compliance tables (wraps <x-admin.empty-state>).
    Needs: $icon, $title, $text, $tone (purple|green|orange|red). Optional: $filteredTitle, $filteredText,
    shown instead when a search or filter is active, with a "Clear filters" button (clearAll() from scTable).
--}}
@php
    $isFiltered = request()->filled('search')
        || request()->filled('category_id')
        || (request('date_filter', 'all') !== 'all' && request()->filled('date_filter'))
        || request()->filled('custom_date');
@endphp

<x-admin.empty-state :icon="$icon" :title="$title" :text="$text" :tone="$tone ?? 'purple'" :filtered="$isFiltered"
    :filtered-title="$filteredTitle ?? 'No matches'" :filtered-text="$filteredText ?? 'Change the search or clear the filters.'">
    @if ($isFiltered)
        <button type="button" @click="clearAll()"
            class="inline-flex h-9 items-center rounded-lg border border-vd-line-strong bg-white px-4 text-[13px] font-medium text-vd-plum transition-colors duration-200 hover:bg-vd-tint">
            Clear filters
        </button>
    @endif
</x-admin.empty-state>
