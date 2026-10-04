@if ($paginator->hasPages())
    <nav class="lg-pager" role="navigation" aria-label="Pagination">
        <p>Showing {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ number_format($paginator->total()) }}</p>

        <ul>
            @if ($paginator->onFirstPage())
                <li><span aria-disabled="true">Previous</span></li>
            @else
                <li><a href="{{ $paginator->previousPageUrl() }}" rel="prev">Previous</a></li>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li><span aria-disabled="true">{{ $element }}</span></li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li><span aria-current="page">{{ $page }}</span></li>
                        @else
                            <li><a href="{{ $url }}" aria-label="Go to page {{ $page }}">{{ $page }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <li><a href="{{ $paginator->nextPageUrl() }}" rel="next">Next</a></li>
            @else
                <li><span aria-disabled="true">Next</span></li>
            @endif
        </ul>
    </nav>
@endif