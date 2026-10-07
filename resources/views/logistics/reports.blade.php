@php
    $metrics = [
        ['Updated parcels', $total, 'package', ''],
        ['Ready for pickup', $readyForPickup, 'clock', 'amber'],
        ['Out for delivery', $outForDelivery, 'truck', 'blue'],
        ['Delivered', $delivered, 'check-circle', 'green'],
        ['Exceptions', $exceptions, 'alert', 'red'],
    ];
@endphp
<x-logistics.layout title="Reports">
    <div class="lg-page">

        <div class="lg-page-head">
            <div>
                <h1>Logistics Reports</h1>
                <p>Parcel status counts for the selected period at this center.</p>
            </div>
            <div class="lg-page-head__actions">
                <a class="lg-btn lg-btn--outline" href="{{ route('logistics.reports.export', ['format' => 'csv', 'date_from' => $since->toDateString(), 'date_to' => $until->toDateString()]) }}"><x-logistics.icon name="download" :size="18" /> Export CSV for Excel</a>
                <a class="lg-btn" href="{{ route('logistics.reports.export', ['format' => 'pdf', 'date_from' => $since->toDateString(), 'date_to' => $until->toDateString()]) }}"><x-logistics.icon name="printer" :size="18" /> Download PDF</a>
            </div>
        </div>

        <form method="GET" action="{{ route('logistics.reports') }}" class="lg-toolbar">
            <label class="lg-label">From <input class="lg-input" type="date" name="date_from" value="{{ $since->toDateString() }}" required></label>
            <label class="lg-label">To <input class="lg-input" type="date" name="date_to" value="{{ $until->toDateString() }}" required></label>
            <button type="submit" class="lg-btn lg-btn--sm">Apply period</button>
        </form>
        <p class="lg-section-sub">{{ $since->format('M j, Y') }} to {{ $until->format('M j, Y') }} &middot; Counts include an order when this center is its origin or destination.</p>

        <section aria-label="Key figures">
            <dl class="lg-grid lg-grid--5" style="margin: 0;">
                @foreach ($metrics as $index => [$label, $value, $icon, $tone])
                    <div class="lg-stat lg-rise" style="--i: {{ $index }}">
                        <span class="lg-stat__icon {{ $tone ? 'lg-stat__icon--'.$tone : '' }}"><x-logistics.icon :name="$icon" :size="22" /></span>
                        <div>
                            <dt class="lg-stat__label">{{ $label }}</dt>
                            <dd class="lg-stat__value" style="margin-left: 0;" data-count="{{ $value }}">{{ number_format($value) }}</dd>
                        </div>
                    </div>
                @endforeach
            </dl>
        </section>

        <div class="lg-grid lg-grid--2">
            <section class="lg-card" aria-labelledby="lgAreaTitle">
                <div class="lg-card__head"><h2 class="lg-section-title" id="lgAreaTitle">By delivery area</h2></div>
                <div class="lg-table-wrap"><table class="lg-table"><thead><tr><th>Area</th><th class="is-num">Parcels</th></tr></thead><tbody>
                    @forelse($areaCounts as $row)<tr><td>{{ $row->shipping_city ?: 'Unknown area' }}</td><td class="is-num">{{ number_format($row->total) }}</td></tr>
                    @empty<tr><td colspan="2">No parcels in this period.</td></tr>@endforelse
                </tbody></table></div>
            </section>
            <section class="lg-card" aria-labelledby="lgRiderReportTitle">
                <div class="lg-card__head"><h2 class="lg-section-title" id="lgRiderReportTitle">By delivery rider</h2></div>
                <div class="lg-table-wrap"><table class="lg-table"><thead><tr><th>Rider</th><th class="is-num">Parcels</th></tr></thead><tbody>
                    @forelse($riderCounts as $row)<tr><td>{{ $row->deliveryCourier?->name ?: 'Unavailable rider' }}</td><td class="is-num">{{ number_format($row->total) }}</td></tr>
                    @empty<tr><td colspan="2">No assigned delivery riders in this period.</td></tr>@endforelse
                </tbody></table></div>
            </section>
        </div>

        <section class="lg-card" aria-labelledby="lgStatusTitle">
            <div class="lg-card__head">
                <div>
                    <h2 class="lg-section-title" id="lgStatusTitle">Parcel counts by current status</h2>
                    <p class="lg-section-sub">Order snapshots, not independent scan analytics.</p>
                </div>
            </div>

            @if ($statusCounts->isNotEmpty())
                <div class="lg-table-wrap">
                    <table class="lg-table" id="lgStatusTable">
                        <thead>
                            <tr>
                                <th scope="col">Status</th>
                                <th scope="col" class="lg-no-export" style="width: 40%;"><span class="sr-only">Share of parcels</span></th>
                                <th scope="col" class="is-num">Parcels</th>
                                <th scope="col" class="is-num">Share</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($statusCounts as $row)
                                @php $share = $total > 0 ? round(($row->total / $total) * 100) : 0; @endphp
                                <tr>
                                    <th scope="row" style="font-weight: 500; text-align: left;">
                                        <x-logistics.status :status="$row->status === 'legacy_route_record' ? 'to_soc5' : $row->status" />
                                    </th>
                                    <td class="lg-no-export" aria-hidden="true">
                                        <div class="lg-bar"><span style="width: {{ max($share, 2) }}%;"></span></div>
                                    </td>
                                    <td class="is-num">{{ number_format($row->total) }}</td>
                                    <td class="is-num lg-muted">{{ $share }}%</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="lg-empty">
                    <span class="lg-empty__icon"><x-logistics.icon name="bar-chart" :size="30" /></span>
                    <h3>No parcel status updates in this period</h3>
                    <p>Counts appear here once parcels linked to this center change status.</p>
                </div>
            @endif
        </section>

    </div>
</x-logistics.layout>
