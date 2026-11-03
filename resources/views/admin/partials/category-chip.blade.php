{{-- Category chip in the shared category palette (App\Models\Category::colors). Expects $category. --}}
@if ($category)
    <span class="inline-block rounded-full border px-3 py-1 text-xs font-medium"
        style="border-color: {{ $category->colors['border'] }}; background-color: {{ $category->colors['bg'] }}; color: {{ $category->colors['border'] }};">
        {{ $category->name }}
    </span>
@endif
