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
                <p>Parcel status counts updated in the last 30 days for this center.</p>
            </div>
            <div class="lg-page-head__actions">
                <button type="button" class="lg-btn lg-btn--outline" data-lg-export="lgStatusTable" data-lg-filename="logistics-status-{{ now()->format('Y-m-d') }}.csv">
                    <x-logistics.icon name="download" :size="18" /> Export CSV
                </button>
                <button type="button" class="lg-btn" data-lg-print>
                    <x-logistics.icon name="printer" :size="18" /> Print / Save as PDF
                </button>
            </div>
        </div>

        <p class="lg-section-sub" style="margin-top: -8px;">Since {{ $since->format('M j, Y') }} &middot; Counts include an order when this center is its origin or destination.</p>

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