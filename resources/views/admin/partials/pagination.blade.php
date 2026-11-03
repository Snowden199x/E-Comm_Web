{{--
    Footer for in-place tables: "Showing 1–8 of 24" and page buttons.
    Buttons carry data-page; the page's Alpine scope fetches that page (no navigation).
    Needs $paginator (a LengthAwarePaginator that has items).
    Pass asLinks => true on pages the server renders (no in-place fetch): the buttons become links that swap
    #main-content, and the current query string (filters, tab) is kept by the paginator's withQueryString().
--}}
@php
    $asLinks = $asLinks ?? false;
    $current = $paginator->currentPage();
    $last = $paginator->lastPage();
    // 1 … current-1 current current+1 … last
    $pageWindow = collect([1, $last, $current - 1, $current, $current + 1])
        ->filter(fn ($p) => $p >= 1 && $p <= $last)
        ->unique()->sort()->values();

    $pageBtn = 'flex h-8 min-w-8 items-center justify-center rounded-lg border px-2 text-[13px] font-medium transition-colors duration-200
                focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-plum/40';
    $arrowBtn = $pageBtn . ' w-8 border-vd-line-strong text-vd-ink hover:bg-vd-tint disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:bg-transparent';
@endphp

<div class="flex flex-col items-center justify-between gap-3 border-t border-vd-line px-5 py-3 sm:flex-row">
    <p class="text-[13px] text-vd-muted">
        Showing <span class="font-medium text-vd-ink">{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}</span>
        of <span class="font-medium text-vd-ink">{{ number_format($paginator->total()) }}</span>
    </p>

    @if ($paginator->hasPages() && $asLinks)
        <nav aria-label="Pagination" class="flex items-center gap-1.5">
            @if ($current > 1)
                <a href="{{ $paginator->url($current - 1) }}" x-target.push="main-content" aria-label="Previous page" class="{{ $arrowBtn }}">
                    <x-admin.icon name="chevron-left" class="h-4 w-4" />
                </a>
            @else
                <span aria-disabled="true" class="{{ $arrowBtn }} cursor-not-allowed opacity-40"><x-admin.icon name="chevron-left" class="h-4 w-4" /></span>
            @endif

            @foreach ($pageWindow as $i => $p)
                @if ($i > 0 && $p - $pageWindow[$i - 1] > 1)
                    <span class="px-1 text-vd-muted" aria-hidden="true">…</span>
                @endif
                <a href="{{ $paginator->url($p) }}" x-target.push="main-content" aria-label="Page {{ $p }}"
                    @if ($p === $current) aria-current="page" @endif
                    class="{{ $pageBtn }} {{ $p === $current ? 'border-vd-plum bg-vd-plum text-white' : 'border-vd-line-strong text-vd-ink hover:bg-vd-tint' }}">
                    {{ $p }}
                </a>
            @endforeach

            @if ($current < $last)
                <a href="{{ $paginator->url($current + 1) }}" x-target.push="main-content" aria-label="Next page" class="{{ $arrowBtn }}">
                    <x-admin.icon name="chevron-right" class="h-4 w-4" />
                </a>
            @else
                <span aria-disabled="true" class="{{ $arrowBtn }} cursor-not-allowed opacity-40"><x-admin.icon name="chevron-right" class="h-4 w-4" /></span>
            @endif
        </nav>
    @elseif ($paginator->hasPages())
        <nav aria-label="Pagination" class="flex items-center gap-1.5">
            <button type="button" data-page="{{ $current - 1 }}" @if ($current <= 1) disabled @endif aria-label="Previous page" class="{{ $arrowBtn }}">
                <x-admin.icon name="chevron-left" class="h-4 w-4" />
            </button>

            @foreach ($pageWindow as $i => $p)
                @if ($i > 0 && $p - $pageWindow[$i - 1] > 1)
                    <span class="px-1 text-vd-muted" aria-hidden="true">…</span>
                @endif
                <button type="button" data-page="{{ $p }}" aria-label="Page {{ $p }}"
                    @if ($p === $current) aria-current="page" @endif
                    class="{{ $pageBtn }} {{ $p === $current ? 'border-vd-plum bg-vd-plum text-white' : 'border-vd-line-strong text-vd-ink hover:bg-vd-tint' }}">
                    {{ $p }}
                </button>
            @endforeach

            <button type="button" data-page="{{ $current + 1 }}" @if ($current >= $last) disabled @endif aria-label="Next page" class="{{ $arrowBtn }}">
                <x-admin.icon name="chevron-right" class="h-4 w-4" />
            </button>
        </nav>
    @endif
</div>
