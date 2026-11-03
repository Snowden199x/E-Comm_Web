{{-- Category chip (shared palette). Expects $category; renders nothing when it is missing. --}}
@if ($category)
    <x-admin.category-chip :category="$category" />
@endif
