<footer class="ops-pagination">
    <span>Showing {{ $records->firstItem() ?? 0 }}–{{ $records->lastItem() ?? 0 }} of {{ number_format($records->total()) }} entries</span>
    <nav aria-label="Pagination">
        @if($records->onFirstPage())<span aria-disabled="true">‹</span>@else<a href="{{ $records->previousPageUrl() }}" aria-label="Previous page">‹</a>@endif
        @for($page=max(1,$records->currentPage()-2);$page<=min($records->lastPage(),$records->currentPage()+2);$page++)
            <a href="{{ $records->url($page) }}" @if($page===$records->currentPage()) aria-current="page" @endif>{{ $page }}</a>
        @endfor
        @if($records->hasMorePages())<a href="{{ $records->nextPageUrl() }}" aria-label="Next page">›</a>@else<span aria-disabled="true">›</span>@endif
    </nav>
</footer>
