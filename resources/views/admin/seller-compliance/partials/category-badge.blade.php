@if ($category)
    <span class="inline-block px-3 py-1 rounded-full text-xs font-medium border"
        style="border-color: {{ $category->colors['border'] }}; background-color: {{ $category->colors['bg'] }}; color: {{ $category->colors['border'] }};">
        {{ $category->name }}
    </span>
@endif