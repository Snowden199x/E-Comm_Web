@props(['category'])

{{--
    Category chip in the category palette (App\Models\Category::getColorsAttribute: border and text colour, soft fill).
    The colours come from that map, never from user input; an unknown name gets the neutral grey.
      <x-admin.category-chip :category="$category" />
--}}
@php $palette = $category->colors; @endphp

<span {{ $attributes->class(['inline-flex max-w-full items-center rounded-full border px-2.5 py-0.5 text-xs font-medium leading-snug']) }}
    style="border-color: {{ $palette['border'] }}; background-color: {{ $palette['bg'] }}; color: {{ $palette['border'] }};">{{ $category->name }}</span>
