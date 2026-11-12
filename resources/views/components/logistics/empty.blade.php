@props([
    'icon' => 'package',
    'title',
    'text' => null,
    'tone' => 'purple',
    'size' => 'md',
    'filtered' => false,
    'filteredTitle' => 'No matches',
    'filteredText' => 'Change the search or clear the filters.',
])

{{--
    Shared empty state, drawn like the Admin one (.vd-empty in resources/css/admin/motion.css): a bordered icon chip,
    a title, one line, then actions. Say what is missing, then what fills it, then offer an action that starts with a verb.

      <x-logistics.empty icon="map-pin" title="No Province Hubs" text="Add one to start routing parcels.">
          <a href="..." class="lg-btn lg-btn--sm">Add Province Hub</a>
      </x-logistics.empty>

    Props
      icon      any x-logistics.icon name
      tone      purple (default) | green | orange | red | gray   (colours the chip only)
      size      sm (side panels, cards in a grid) | md (tables, cards) | lg (a whole page)
      filtered  true while a search or filter is active: shows filteredTitle/filteredText with a search icon
    The default slot holds the actions. Alpine attributes (x-show, x-cloak) pass through to the root.
--}}
@php
    $shownTone = $filtered ? 'purple' : $tone;
    $shownIcon = $filtered ? 'search' : $icon;
    $shownTitle = $filtered ? $filteredTitle : $title;
    $shownText = $filtered ? $filteredText : $text;
@endphp

<div {{ $attributes->class(['vd-empty', 'vd-empty--'.$size]) }} data-tone="{{ $shownTone }}" role="status">
    <span class="vd-empty__disc" aria-hidden="true">
        <x-logistics.icon :name="$shownIcon" :size="24" />
    </span>

    <p class="vd-empty__title">{{ $shownTitle }}</p>

    @if ($shownText)
        <p class="vd-empty__text">{{ $shownText }}</p>
    @endif

    @if (! $slot->isEmpty())
        <div class="vd-empty__actions">{{ $slot }}</div>
    @endif
</div>
