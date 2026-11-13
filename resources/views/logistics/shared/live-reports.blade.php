{{--
    Reports, shared by the Company, Province Hub and Station Hub portals (spec section 8A). Read-only operational counts.
    Fed by PortalReportController::index. Export CSV is ::export (same numbers). The shipping settlement block is Company only.
    (The preview-only views shared/reports, company/reports, hub/reports and reports are not used by the live portal.)
    Look: the seller pastel lilac system. Styles: resources/css/logistics/reports-theme.css (pushed last, tokens copied
    from seller/delivered-orders.css). No page script and no dialogs.
    Status colors: delivered = mint, in transit = sky, failed or cancelled = rose, late or needs review = butter, other = lilac.
    The period chips are links, so one click reloads the page. Loading: shell skeleton. Errors: top banner (a period other
    than 7, 30 or 90 days is sent back with a message). Empty: no activity in the period, and per-table empty lines.
--}}
@php
    $scope = $role === 'company' ? 'your company' : ($role === 'province' ? 'your Province Hub and its stations' : 'your Station Hub');
    $money = fn ($value) => '₱'.number_format((float) $value, 2);
    $rate = fn ($part) => $total > 0 ? round($part / $total * 100) : 0;
    $statusTone = function (string $status): string {
        return match (true) {
            in_array($status, ['delivered', 'completed'], true) => 'mint',
            in_array($status, ['delivery_failed', 'cancelled', 'returned'], true) => 'rose',
            in_array($status, ['in_transit_to_hub', 'in_transit', 'picked_up', 'out_for_delivery'], true) => 'sky',
            default => 'lilac',
        };
    };
    $hasActivity = $total > 0 || $tripTotal > 0 || $manifestTotal > 0 || $pickupTasks > 0 || $deliveryTasks > 0;
    $barangayMax = max(1, (int) $barangayRows->max());
    $periods = [7 => 'Last 7 days', 30 => 'Last 30 days', 90 => 'Last 90 days'];

    // [label, value, icon, tone, share]
    $tiles = [
        ['Parcels', $total, 'package', 'lilac', null],
        ['Delivered', $delivered, 'check-circle', 'mint', $rate($delivered).'% of parcels'],
        ['In transit', $inTransit, 'truck', 'sky', $rate($inTransit).'% of parcels'],
        ['Failed delivery', $failed, 'alert', 'rose', $rate($failed).'% of parcels'],
    ];
    $workGroups = [
        'Transport trips' => [['Trips', $tripTotal, null], ['Completed', $tripCompleted, null], ['Scheduled or in transit', $tripActive, null], ['Late trips and manifests', $delayedTrips, 'butter']],
        'Rider tasks' => [['Pickup tasks', $pickupTasks, null], ['Delivery tasks', $deliveryTasks, null], ['Completed tasks', $completedTasks, null]],
        'Cross-company' => [['Manifests', $manifestTotal, null], ['Completed manifests', $manifestCompleted, null]],
    ];
@endphp

@push('lg-head')
    @vite('resources/css/logistics/reports-theme.css')
@endpush

<x-logistics.shell title="Reports" :role="$role">
    <div class="lgr">

        <x-logistics.flash />

        <header class="lgr-head">
            <div>
                <h1>Reports</h1>
                <p>Operational counts for {{ $scope }}.</p>
            </div>
            <a href="{{ route('lgp.'.$role.'.reports.export', ['days' => $days]) }}" class="lgr-btn lgr-btn--primary" download>
                <x-logistics.icon name="arrow-right" :size="16" class="lgr-down" /> Export CSV
            </a>
        </header>

        <div class="lgr-chips" role="group" aria-label="Report period">
            @foreach ($periods as $value => $text)
                <a href="{{ route('lgp.'.$role.'.reports', ['days' => $value]) }}" class="lgr-chip {{ $days === $value ? 'is-active' : '' }}" @if ($days === $value) aria-current="true" @endif>{{ $text }}</a>
            @endforeach
        </div>

        @if ($openExceptions > 0)
            <div class="lgr-callout" role="status">
                <x-logistics.icon name="alert" :size="18" />
                <p><strong>{{ number_format($openExceptions) }}</strong> open parcel {{ $openExceptions === 1 ? 'exception needs' : 'exceptions need' }} action. Scans stay paused until {{ $openExceptions === 1 ? 'it is' : 'they are' }} resolved.</p>
                <a href="{{ route('lgp.'.$role.'.exceptions', ['status' => 'open']) }}" class="lgr-btn lgr-btn--soft lgr-btn--small">View exceptions <x-logistics.icon name="arrow-right" :size="14" class="lgr-nudge" /></a>
            </div>
        @endif

        @if (! $hasActivity)
            <section class="lgr-card lgr-empty" role="status">
                <span class="lgr-empty__icon lgr-tone-lilac"><x-logistics.icon name="bar-chart" :size="26" /></span>
                <strong>No activity in the last {{ $days }} days</strong>
                <p>{{ $days < 90 ? 'Try a longer period to see earlier parcels, trips and tasks.' : 'Parcels, trips and tasks show here once your network handles them.' }}</p>
                @if ($days < 90)
                    <a href="{{ route('lgp.'.$role.'.reports', ['days' => 90]) }}" class="lgr-btn lgr-btn--primary">Show last 90 days</a>
                @else
                    <a href="{{ $role === 'company' ? route('lgp.company') : route('lgp.hub.'.$role) }}" class="lgr-btn lgr-btn--primary">Go to dashboard</a>
                @endif
            </section>
        @else
            <section class="lgr-tiles" aria-label="Totals">
                @foreach ($tiles as [$label, $value, $icon, $tone, $share])
                    <div class="lgr-tile">
                        <span class="lgr-tile__icon lgr-tone-{{ $tone }}"><x-logistics.icon :name="$icon" :size="22" /></span>
                        <span class="lgr-tile__text">{{ $label }}<strong>{{ number_format($value) }}</strong>@if ($share)<small>{{ $share }}</small>@endif</span>
                    </div>
                @endforeach
            </section>

            @if ($role === 'company' && $financialSummary)
                <section class="lgr-card" aria-labelledby="settleTitle">
                    <div class="lgr-card__head">
                        <h2 id="settleTitle">Shipping settlement</h2>
                        <p>Amounts recorded for delivered parcels in this period. These are accruals and pool records, not completed payouts.</p>
                    </div>
                    <div class="lgr-money">
                        <div><span>Shipping on network parcels</span><strong>{{ $money($financialSummary['shipping_fees']) }}</strong><small>{{ number_format($financialSummary['settled_orders']) }} settlement records</small></div>
                        <div><span>Rider and Driver accruals</span><strong>{{ $money($financialSummary['rider_accruals']) }}</strong><small>{{ number_format($financialSummary['rider_accrual_lines']) }} completed route stops at your facilities</small></div>
                        <div><span>Pool on single-company routes</span><strong>{{ $money($financialSummary['company_pool']) }}</strong><small>Shipping left after Rider and Driver credits</small></div>
                        <div><span>Cross-company pool</span><strong>{{ $money($financialSummary['cross_company_pool']) }}</strong><small>Left over and not assigned between companies</small></div>
                    </div>
                    @if ($financialSummary['review_orders'] > 0)
                        <div class="lgr-callout lgr-callout--inner" role="status">
                            <x-logistics.icon name="alert" :size="18" />
                            <p><strong>{{ number_format($financialSummary['review_orders']) }}</strong> {{ $financialSummary['review_orders'] === 1 ? 'settlement' : 'settlements' }} worth {{ $money($financialSummary['review_shipping']) }} {{ $financialSummary['review_orders'] === 1 ? 'is' : 'are' }} held for route review and have no Rider or Driver credits.</p>
                        </div>
                    @endif
                    <details class="lgr-rules">
                        <summary>How these numbers work</summary>
                        <ul>
                            <li>Shipping costs ₱45 for up to 2 route stops, plus ₱10 for each extra stop. A completed stop can accrue ₱10 to its Rider or Transport Driver. The Admin product commission is separate and gets no shipping share.</li>
                            <li>Shipping on network parcels is the full order fee. A cross-company order can show the same fee in each company’s report, so do not add those totals together.</li>
                            <li>Money figures use the settlement date. Parcel tables use the order creation date. Cross-company pool figures are for information only and are not company revenue.</li>
                        </ul>
                    </details>
                </section>
            @endif

            <div class="lgr-grid">
                <section class="lgr-card" aria-labelledby="stageTitle">
                    <div class="lgr-card__head">
                        <h2 id="stageTitle">Parcel stages</h2>
                        <p>Orders created in this period that passed through your network.</p>
                    </div>
                    @if ($statusRows->count())
                        <ul class="lgr-stages">
                            @foreach ($statusRows as $status => $count)
                                @php $tone = $statusTone((string) $status); @endphp
                                <li>
                                    <span class="lgr-pill lgr-pill--{{ $tone }}">{{ ucfirst(str_replace('_', ' ', (string) $status)) }}</span>
                                    <span class="lgr-bar lgr-bar--{{ $tone }}"><i style="--w: {{ $rate($count) }}%"></i></span>
                                    <strong>{{ number_format($count) }}</strong>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="lgr-muted">No parcels in this period.</p>
                    @endif
                </section>

                <section class="lgr-card" aria-labelledby="workTitle">
                    <div class="lgr-card__head"><h2 id="workTitle">Work completed</h2></div>
                    <div class="lgr-work">
                        @foreach ($workGroups as $group => $rows)
                            <div>
                                <h3>{{ $group }}</h3>
                                <dl>
                                    @foreach ($rows as [$label, $value, $flag])
                                        <div><dt>{{ $label }}</dt><dd @if ($flag && $value > 0) class="lgr-flag lgr-flag--{{ $flag }}" @endif>{{ number_format($value) }}</dd></div>
                                    @endforeach
                                </dl>
                            </div>
                        @endforeach
                    </div>
                </section>
            </div>

            <section class="lgr-card lgr-table-card" aria-labelledby="facTitle">
                <div class="lgr-card__head lgr-card__head--pad">
                    <h2 id="facTitle">Facility activity</h2>
                    <p>A parcel counts at every facility it visits.</p>
                </div>
                @if ($facilityRows->count())
                    <div class="lgr-table-wrap">
                        <table class="lgr-table">
                            <thead><tr><th scope="col">Facility</th><th scope="col" class="is-num">Parcels</th><th scope="col" class="is-num">Delivered</th><th scope="col" class="is-num">Failed</th></tr></thead>
                            <tbody>
                                @foreach ($facilityRows as $row)
                                    <tr>
                                        <td><strong>{{ $row['name'] }}</strong><small>{{ $row['kind'] }}</small></td>
                                        <td data-label="Parcels" class="is-num">{{ number_format($row['parcels']) }}</td>
                                        <td data-label="Delivered" class="is-num">{{ number_format($row['delivered']) }}</td>
                                        <td data-label="Failed" class="is-num">@if ($row['failed'] > 0)<span class="lgr-pill lgr-pill--rose">{{ number_format($row['failed']) }}</span>@else 0 @endif</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="lgr-muted lgr-muted--pad">No facilities in this scope.</p>
                @endif
            </section>

            <div class="lgr-grid lgr-grid--3">
                @foreach ([
                    ['Transport Drivers', $driverRows, ['Trips', 'Completed', 'Late'], ['trips', 'completed', 'late'], 'late'],
                    ['Vehicles', $vehicleRows, ['Trips', 'Completed', 'Late'], ['trips', 'completed', 'late'], 'late'],
                    ['Riders', $riderRows, ['Tasks', 'Completed', 'Failed'], ['tasks', 'completed', 'failed'], 'failed'],
                ] as [$heading, $rows, $labels, $keys, $warnKey])
                    <section class="lgr-card lgr-table-card" aria-label="{{ $heading }}">
                        <div class="lgr-card__head lgr-card__head--pad"><h2>{{ $heading }}</h2></div>
                        @if ($rows->count())
                            <div class="lgr-table-wrap lgr-table-wrap--scroll">
                                <table class="lgr-table lgr-table--compact">
                                    <thead><tr><th scope="col">Name</th>@foreach ($labels as $label)<th scope="col" class="is-num">{{ $label }}</th>@endforeach</tr></thead>
                                    <tbody>
                                        @foreach ($rows as $row)
                                            <tr>
                                                <td><strong>{{ $row['name'] }}</strong></td>
                                                @foreach ($keys as $key)
                                                    <td class="is-num">@if ($key === $warnKey && $row[$key] > 0)<span class="lgr-pill lgr-pill--{{ $warnKey === 'late' ? 'butter' : 'rose' }}">{{ $row[$key] }}</span>@else {{ $row[$key] }} @endif</td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="lgr-muted lgr-muted--pad">No activity in this period.</p>
                        @endif
                    </section>
                @endforeach
            </div>

            <section class="lgr-card" aria-labelledby="brgyTitle">
                <div class="lgr-card__head">
                    <h2 id="brgyTitle">Delivery barangays</h2>
                    @if ($barangayRows->count() > 10)<p>Top 10 of {{ number_format($barangayRows->count()) }}. Export the CSV for the full list.</p>@endif
                </div>
                @if ($barangayRows->count())
                    <ul class="lgr-ranks">
                        @foreach ($barangayRows->take(10) as $barangay => $count)
                            <li>
                                <span>{{ $barangay }}</span>
                                <span class="lgr-bar lgr-bar--lilac"><i style="--w: {{ round($count / $barangayMax * 100) }}%"></i></span>
                                <strong>{{ number_format($count) }}</strong>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="lgr-muted">No barangay data in this period.</p>
                @endif
            </section>
        @endif
    </div>
</x-logistics.shell>
