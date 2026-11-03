{{--
    Footer with entry count and page buttons.
    Needs: $paginator. Buttons carry data-page; the scTable scope handles them (no full reload).
--}}
@php
    $current = $paginator->currentPage();
    $last = $paginator->lastPage();
    $pageWindow = collect([1, $last, $current - 1, $current, $current + 1])
        ->filter(fn ($p) => $p >= 1 && $p <= $last)
        ->unique()->sort()->values();
@endphp

<div class="flex flex-col items-center justify-between gap-3 border-t border-vd-line px-5 py-3.5 sm:flex-row">
    <p class="text-[13px] text-vd-muted">
        Showing <span class="font-medium text-vd-ink">{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}</span>
        of <span class="font-medium text-vd-ink">{{ number_format($paginator->total()) }}</span> entries
    </p>

    @if ($paginator->hasPages())
        <nav aria-label="Pagination" class="flex items-center gap-1.5">
            <button type="button" data-page="{{ $current - 1 }}" @if ($current <= 1) disabled @endif aria-label="Previous page"
                class="flex h-8 w-8 items-center justify-center rounded-lg border border-vd-line-strong text-vd-muted transition duration-150
                       hover:bg-vd-tint disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:bg-transparent
                       focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-plum/40">
                <x-admin.icon name="chevron-left" class="h-4 w-4" stroke="2.2" />
            </button>

            @foreach ($pageWindow as $i => $p)
                @if ($i > 0 && $p - $pageWindow[$i - 1] > 1)
                    <span class="px-1 text-vd-muted" aria-hidden="true">…</span>
                @endif
                <button type="button" data-page="{{ $p }}" aria-label="Page {{ $p }}" @if ($p === $current) aria-current="page" @endif
                    class="flex h-8 min-w-8 items-center justify-center rounded-lg border px-2 text-[13px] font-medium transition duration-150
                           focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-plum/40
                           {{ $p === $current ? 'border-vd-plum bg-vd-plum text-white' : 'border-vd-line-strong text-vd-muted hover:bg-vd-tint' }}">
                    {{ $p }}
                </button>
            @endforeach

            <button type="button" data-page="{{ $current + 1 }}" @if ($current >= $last) disabled @endif aria-label="Next page"
                class="flex h-8 w-8 items-center justify-center rounded-lg border border-vd-line-strong text-vd-muted transition duration-150
                       hover:bg-vd-tint disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:bg-transparent
                       focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-plum/40">
                <x-admin.icon name="chevron-right" class="h-4 w-4" stroke="2.2" />
            </button>
        </nav>
    @endif
</div>