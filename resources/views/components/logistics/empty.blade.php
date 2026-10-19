@props(['icon' => 'package', 'title', 'text' => null])

{{-- Shared empty state. Put action buttons in the slot. Alpine attributes (x-show, x-cloak) pass through to the root. --}}
<div {{ $attributes->class(['lg-empty']) }}>
    <span class="lg-empty__icon"><x-logistics.icon :name="$icon" :size="28" /></span>
    <h3>{{ $title }}</h3>
    @if ($text)<p>{{ $text }}</p>@endif
    {{ $slot }}
</div>
