@php
    $open = (int) ($counts['open'] ?? 0);
    $review = (int) ($counts['in_review'] ?? 0);
    $resolved = (int) ($counts['resolved'] ?? 0);
    $all = $open + $review + $resolved;
    $filtered = $status !== '' || $q !== '';
    $tabs = [
        ['', 'All', $all],
        ['open', 'Open', $open],
        ['in_review', 'In review', $review],
        ['resolved', 'Resolved', $resolved],
    ];
    $icons = [
        'open' => 'M21 12a8 8 0 0 1-11.6 7.1L4 21l1.9-5.4A8 8 0 1 1 21 12z|M9 11h6M9 14h4',
        'in_review' => 'M12 7v5l3 2|M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18z',
        'resolved' => 'M5 12.5l4.5 4.5L19 7.5|M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18z',
    ];
@endphp

<x-buyer.layout title="Support — Vendo">
    @vite(['resources/css/buyer/support-theme.css', 'resources/js/buyer/support.js'])

    <div class="sp-page" x-data x-init="
        @if (session('success')) $store.banner.show({ type: 'success', title: @js(session('success')) }); @endif
        @if ($errors->any()) $store.banner.show({ type: 'error', title: 'Could not load requests', text: @js($errors->first()) }); @endif
    ">
        <div class="sp-head">
            <div>
                <h1>Support</h1>
                <p>Ask Vendo about an order, a payment or your account. Replies appear in the same thread.</p>
            </div>
            <a href="{{ route('buyer.support.create') }}" class="sp-btn sp-btn--primary">
                <svg class="sp-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14" /></svg>
                New request
            </a>
        </div>

        <form id="spSearch" class="sp-filters" action="{{ route('buyer.support.index') }}" method="GET" role="search">
            @if ($status !== '')<input type="hidden" name="status" value="{{ $status }}">@endif
            <label class="sp-search">
                <span class="sr-only">Search requests</span>
                <svg class="sp-ico" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7" /><path d="m20 20-3.5-3.5" /></svg>
                <input type="search" name="q" value="{{ $q }}" maxlength="100" placeholder="Search by subject or number, then press Enter" autocomplete="off">
            </label>
        </form>

        <div id="spChips" class="sp-chips" role="group" aria-label="Filter by status">
            @foreach ($tabs as [$key, $label, $total])
                <a data-sp-nav href="{{ route('buyer.support.index', array_filter(['status' => $key, 'q' => $q])) }}"
                    class="sp-chip {{ $status === $key ? 'is-active' : '' }}" @if ($status === $key) aria-current="true" @endif>
                    {{ $label }} <b>{{ $total }}</b>
                </a>
            @endforeach
            @if ($filtered)
                <a data-sp-nav href="{{ route('buyer.support.index') }}" class="sp-chip">Clear filters</a>
            @endif
        </div>

        <section id="spList" aria-labelledby="spListTitle" aria-live="polite">
            <h2 id="spListTitle" class="sr-only">Your support requests</h2>
            <div class="sp-skel" aria-hidden="true"><i></i><i></i><i></i></div>

            @if ($tickets->isEmpty())
                <div class="sp-empty">
                    <span class="sp-empty__icon">
                        <svg class="sp-ico sp-ico--pop" viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $filtered ? 'M11 18a7 7 0 1 0 0-14 7 7 0 0 0 0 14z' : 'M21 12a8 8 0 0 1-11.6 7.1L4 21l1.9-5.4A8 8 0 1 1 21 12z' }}" /><path d="{{ $filtered ? 'm20 20-4-4' : 'M9 11h6M9 14h4' }}" /></svg>
                    </span>
                    @if ($filtered)
                        <strong>No requests match your filters</strong>
                        <p>Try a different word or choose another status.</p>
                        <a data-sp-nav href="{{ route('buyer.support.index') }}" class="sp-btn sp-btn--soft">Clear filters</a>
                    @else
                        <strong>No support requests yet</strong>
                        <p>Send a request when you need help. Every reply appears here.</p>
                        <a href="{{ route('buyer.support.create') }}" class="sp-btn sp-btn--primary">Create request</a>
                    @endif
                </div>
            @else
                <ul class="sp-list">
                    @foreach ($tickets as $ticket)
                        <li>
                            <a href="{{ route('buyer.support.show', $ticket) }}" class="sp-row" style="animation-delay: {{ min($loop->index, 8) * 30 }}ms">
                                <span class="sp-row__icon sp-tone-{{ $ticket->status }}">
                                    <svg class="sp-ico sp-ico--pop" viewBox="0 0 24 24" aria-hidden="true">
                                        @foreach (explode('|', $icons[$ticket->status] ?? $icons['open']) as $path)<path d="{{ $path }}" />@endforeach
                                    </svg>
                                </span>
                                <span class="sp-row__body">
                                    <strong>{{ $ticket->subject }}</strong>
                                    <small>
                                        #{{ $ticket->id }} · {{ $ticket->category_label }}@if ($ticket->order) · {{ $ticket->order->number }}@endif
                                        · {{ $ticket->messages_count }} {{ $ticket->messages_count === 1 ? 'message' : 'messages' }}
                                        · {{ ($ticket->last_reply_at ?? $ticket->created_at)?->diffForHumans() }}
                                    </small>
                                </span>
                                <span class="sp-badge sp-tone-{{ $ticket->status }}">{{ $ticket->status_label }}</span>
                                <span class="sp-btn sp-btn--soft sp-btn--small sp-row__action">View thread
                                    <svg class="sp-ico sp-ico--nudge" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 6 6 6-6 6" /></svg>
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>

                @if ($tickets->hasPages())
                    <div class="sp-pager">
                        <span>Page {{ $tickets->currentPage() }} of {{ $tickets->lastPage() }}</span>
                        <nav aria-label="Pages">
                            @if ($tickets->onFirstPage())
                                <span class="sp-pager__gap" aria-disabled="true">Previous</span>
                            @else
                                <a data-sp-nav href="{{ $tickets->previousPageUrl() }}" rel="prev">Previous</a>
                            @endif
                            @if ($tickets->hasMorePages())
                                <a data-sp-nav href="{{ $tickets->nextPageUrl() }}" rel="next">Next</a>
                            @else
                                <span class="sp-pager__gap" aria-disabled="true">Next</span>
                            @endif
                        </nav>
                    </div>
                @endif
            @endif
        </section>
    </div>
</x-buyer.layout>
