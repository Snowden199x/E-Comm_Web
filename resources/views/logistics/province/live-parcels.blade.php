{{--
    Province Hub > Parcel Operations (route lgp.province.parcels). Read-only list of the parcels routed through this Province Hub,
    one row per order stop. Fed by PortalController::provinceParcels (rows, stage counts, the chosen stage and search, and two urls).
    Staff receive and sort parcels with the facility scanner, not on this page, and the next stop comes from each order's route.
    Look: the seller pastel lilac system, the same .lgh layer as Province Hub > Station Hubs. Styles: hubs-theme.css plus parcels-theme.css (loaded last).
    Status colors: Incoming = sky, Sorting = lilac, Outgoing (waiting for a trip) = butter, Departed = mint.
    Stage chips with counts and search are server-side, because the list is paged: chips are links, search runs on Enter.
    One click on a row opens Parcel Tracking for that parcel; every row has a visible Track parcel button.
    Loading: the shell skeleton on every filter or page change. Empty: no parcels at all, nothing in a stage, and no parcels match the search.
    Errors: a search over 100 characters is sent back by the controller and shown in the top banner.
--}}
@php
    $stageChips = ['all' => 'All', 'incoming' => 'Incoming', 'sorting' => 'Sorting', 'outgoing' => 'Outgoing', 'departed' => 'Departed'];
    $stageTones = ['Incoming' => 'lgh-pill--sky', 'Sorting' => 'lgh-pill--lilac', 'Outgoing' => 'lgh-pill--butter', 'Departed' => 'lgh-pill--mint'];
    $stageHints = [
        'all' => 'Every parcel routed through this hub.',
        'incoming' => 'On a trip to this hub. Scan a parcel on arrival to receive it.',
        'sorting' => 'Received here and waiting to be sorted.',
        'outgoing' => 'Sorted and waiting for a trip to its next stop.',
        'departed' => 'Already handed over to its next stop.',
    ];
    $stageEmpty = [
        'incoming' => ['No parcels on their way', 'Parcels from your stations and other Province Hubs show up here while their trip is running.'],
        'sorting' => ['Nothing to sort', 'Received parcels wait here until they are sorted.'],
        'outgoing' => ['No parcels waiting for a trip', 'Sorted parcels show up here until they go on a trip.'],
        'departed' => ['No parcels handed over yet', 'Parcels this hub has sent on show up here.'],
    ];
    $searching = $q !== '';
    $filtered = $searching || $stage !== 'all';
@endphp
@push('lg-head')
    @vite(['resources/css/logistics/hubs-theme.css', 'resources/css/logistics/parcels-theme.css'])
@endpush

<x-logistics.shell title="Parcel Operations" role="province">
    <div class="lgh" x-data @keydown.escape.window="$refs.search && $refs.search.blur()">

        <x-logistics.flash />

        <header class="lgh-head">
            <div>
                <h1>Parcel operations</h1>
                <p>Parcels routed through {{ $hub->name }}. Scan a parcel on arrival to receive and sort it, then create a trip for its next stop.</p>
            </div>
            <a href="{{ $createUrl }}" class="lgh-btn lgh-btn--primary">
                <x-logistics.icon name="plus" :size="16" /> Create trip
            </a>
        </header>

        @if ($counts['all'] > 0)
            {{-- Stage chips with counts, then search (Enter to search) --}}
            <nav class="lgh-chips" aria-label="Filter parcels by stage">
                @foreach ($stageChips as $key => $text)
                    <a href="{{ route('lgp.province.parcels', array_filter(['stage' => $key === 'all' ? null : $key, 'q' => $q ?: null])) }}"
                       class="lgh-chip {{ $stage === $key ? 'is-active' : '' }}" @if ($stage === $key) aria-current="true" @endif>
                        {{ $text }} <span class="lgh-chip__count">{{ number_format($counts[$key]) }}</span>
                    </a>
                @endforeach
                @if ($filtered)
                    <a href="{{ route('lgp.province.parcels') }}" class="lgh-chip lgh-chip--clear"><x-logistics.icon name="x" :size="14" /> Clear filters</a>
                @endif
            </nav>

            <div class="lgh-toolbar">
                <form method="GET" action="{{ route('lgp.province.parcels') }}" class="lgh-search" role="search">
                    @if ($stage !== 'all')<input type="hidden" name="stage" value="{{ $stage }}">@endif
                    <label class="lgh-sr" for="parcelSearch">Search parcels</label>
                    <x-logistics.icon name="search" :size="18" />
                    <input id="parcelSearch" x-ref="search" type="search" name="q" value="{{ $q }}" maxlength="100" autocomplete="off" placeholder="Search tracking number. Press Enter">
                </form>
                <span class="lgh-meta" aria-live="polite">
                    {{ number_format($rows->total()) }} {{ $rows->total() === 1 ? 'parcel' : 'parcels' }} · {{ $stageHints[$stage] }}
                </span>
            </div>

            <section class="lgh-card lgh-table-card" aria-label="Parcels">
                @if ($rows->count())
                    <div class="lgh-table-wrap">
                        <table class="lgh-table lgh-table--parcels">
                            <thead>
                                <tr>
                                    <th scope="col">Parcel</th>
                                    <th scope="col">Stage</th>
                                    <th scope="col">Next stop</th>
                                    <th scope="col">Received</th>
                                    <th scope="col">Departed</th>
                                    <th scope="col"><span class="lgh-sr">Actions</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rows as $row)
                                    <tr class="lgh-row" data-href="{{ $row['url'] }}">
                                        <td>
                                            <a href="{{ $row['url'] }}" class="lgh-name">{{ $row['tracking'] }}</a>
                                            <small>{{ $row['status'] }}</small>
                                        </td>
                                        <td data-label="Stage"><span class="lgh-pill {{ $stageTones[$row['stage']] ?? 'lgh-pill--lilac' }}">{{ $row['stage'] }}</span></td>
                                        <td data-label="Next stop">{{ $row['next'] }}</td>
                                        <td data-label="Received">{{ $row['arrived'] ?? 'Not yet' }}</td>
                                        <td data-label="Departed">{{ $row['departed'] ?? 'Not yet' }}</td>
                                        <td class="lgh-actions">
                                            <div class="lgh-actions__row">
                                                <a href="{{ $row['url'] }}" class="lgh-btn lgh-btn--small lgh-btn--soft" aria-label="Track parcel {{ $row['tracking'] }}">
                                                    Track parcel <x-logistics.icon name="arrow-right" :size="14" class="lgh-nudge" />
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="lgh-pager">{{ $rows->links('logistics.partials.pagination') }}</div>
                @elseif ($searching)
                    {{-- Nothing matches the search --}}
                    <div class="lgh-empty" role="status">
                        <span class="lgh-empty__icon lgh-tone-lilac"><x-logistics.icon name="search" :size="26" /></span>
                        <strong>No parcels match your search</strong>
                        <p>Check the tracking number for typos.@if ($stage !== 'all') You are searching in one stage only.@endif</p>
                        <a href="{{ route('lgp.province.parcels') }}" class="lgh-btn lgh-btn--primary">Clear filters</a>
                    </div>
                @else
                    {{-- Nothing in this stage --}}
                    <div class="lgh-empty" role="status">
                        <span class="lgh-empty__icon lgh-tone-lilac"><x-logistics.icon name="package" :size="26" /></span>
                        <strong>{{ $stageEmpty[$stage][0] ?? 'No parcels' }}</strong>
                        <p>{{ $stageEmpty[$stage][1] ?? '' }}</p>
                        @if ($stage === 'outgoing')
                            <a href="{{ $createUrl }}" class="lgh-btn lgh-btn--soft"><x-logistics.icon name="plus" :size="16" /> Create trip</a>
                        @else
                            <a href="{{ route('lgp.province.parcels') }}" class="lgh-btn lgh-btn--soft">Show all parcels</a>
                        @endif
                    </div>
                @endif
            </section>
        @else
            {{-- No parcels at all --}}
            <section class="lgh-card" aria-label="Parcels">
                <div class="lgh-empty" role="status">
                    <span class="lgh-empty__icon lgh-tone-lilac"><x-logistics.icon name="package" :size="26" /></span>
                    <strong>No parcels yet</strong>
                    <p>Parcels show up here once an order is routed through {{ $hub->name }}.</p>
                    <a href="{{ $trackingUrl }}" class="lgh-btn lgh-btn--soft">Open parcel tracking</a>
                </div>
            </section>
        @endif
    </div>

    <script>
        // One click on a row opens Parcel Tracking. Links and buttons keep their own click.
        document.querySelectorAll('.lgh-table--parcels tr[data-href]').forEach(function (row) {
            row.addEventListener('click', function (event) {
                if (event.target.closest('a, button')) return;
                if (event.metaKey || event.ctrlKey || event.shiftKey) { window.open(row.dataset.href, '_blank', 'noopener'); return; }
                window.location.assign(row.dataset.href);
            });
        });
    </script>
</x-logistics.shell>
