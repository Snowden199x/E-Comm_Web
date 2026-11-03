@props([
    'icon' => 'inbox',
    'title',
    'text' => null,
    'tone' => 'purple',
    'size' => 'md',
    'filtered' => false,
    'filteredTitle' => 'No matches',
    'filteredText' => 'Change the search or clear the filters.',
    'cols' => null,
])

{{--
    Empty state for lists, tables and panels (static, no images, no gradients). Say what is missing, then what fills it, then offer an action (label starts with a verb).

      <x-admin.empty-state icon="inbox" title="No registrations" text="New sign-ups appear here." />

    Props
      icon      any x-admin.icon name
      tone      purple (default) | green | orange | red | gray
      size      sm (dropdowns, side panels) | md (tables, cards) | lg (a whole page)
      filtered  true when a search/filter is active: shows filteredTitle/filteredText with a search-x icon instead
      cols      set to the table's column count to render inside <tr><td colspan>: no wrapper row needed
    The default slot holds the actions (buttons or links), e.g. a "Clear filters" button.
--}}
@php
    $shownTone = $filtered ? 'purple' : $tone;
    $shownIcon = $filtered ? 'search-x' : $icon;
    $shownTitle = $filtered ? $filteredTitle : $title;
    $shownText = $filtered ? $filteredText : $text;
@endphp

@if ($cols)
    <tr>
        <td colspan="{{ $cols }}" class="p-0">
@endif

<div {{ $attributes->class(['vd-empty', 'vd-empty--' . $size]) }} data-tone="{{ $shownTone }}" role="status">
    <span class="vd-empty__disc" aria-hidden="true">
        <x-admin.icon :name="$shownIcon" size="24" stroke="1.75" class="h-6 w-6" />
    </span>

    <p class="vd-empty__title">{{ $shownTitle }}</p>

    @if ($shownText)
        <p class="vd-empty__text">{{ $shownText }}</p>
    @endif

    @if (! $slot->isEmpty())
        <div class="vd-empty__actions">{{ $slot }}</div>
    @endif
</div>

@if ($cols)
        </td>
    </tr>
@endif
