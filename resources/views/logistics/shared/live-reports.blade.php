<x-logistics.shell title="Reports" :role="$role">
    <div class="lg-page">
        <div class="lg-page-head">
            <div><h1>Reports</h1><p>Operational counts for {{ $role === 'company' ? 'your company' : ($role === 'province' ? 'your Province Hub and stations' : 'your Station Hub') }}.</p></div>
            <form method="GET" class="lg-page-head__actions">
                <label class="lg-field" for="reportDays"><span>Period</span>
                    <select id="reportDays" name="days" class="lg-select" onchange="this.form.submit()">
                        @foreach ([7, 30, 90] as $option)<option value="{{ $option }}" @selected($days === $option)>Last {{ $option }} days</option>@endforeach
                    </select>
                </label>
                <a href="{{ route('lgp.'.$role.'.reports.export', ['days' => $days]) }}" class="lg-btn lg-btn--outline">Export CSV</a>
            </form>
        </div>
        <div class="lg-grid lg-grid--4">
            @foreach ([['Parcels', $total, 'package'], ['Delivered', $delivered, 'check-circle'], ['In transit', $inTransit, 'truck'], ['Failed delivery', $failed, 'alert']] as [$label, $value, $icon])
                <div class="lg-stat"><span class="lg-stat__icon lg-stat__icon--violet"><x-logistics.icon :name="$icon" :size="24" /></span><div class="lg-stat__body"><p class="lg-stat__label">{{ $label }}</p><p class="lg-stat__value">{{ number_format($value) }}</p></div></div>
            @endforeach
        </div>
        @if ($role === 'company' && $financialSummary)
            <section class="lg-card" aria-labelledby="shipping-financial-heading">
                <div class="lg-card__head">
                    <div>
                        <h2 id="shipping-financial-heading" class="lg-section-title">Shipping settlement</h2>
                        <p class="lg-section-sub">Amounts recorded for delivered parcels in this period. These are accruals and pool records, not completed payouts.</p>
                    </div>
                </div>
                <div class="lg-card__body">
                    <div class="lg-grid lg-grid--4">
                        <div class="lg-stat"><div class="lg-stat__body"><p class="lg-stat__label">Shipping on network parcels</p><p class="lg-stat__value">₱{{ number_format($financialSummary['shipping_fees'], 2) }}</p><p class="lg-section-sub">{{ number_format($financialSummary['settled_orders']) }} settlement records</p></div></div>
                        <div class="lg-stat"><div class="lg-stat__body"><p class="lg-stat__label">Rider / Driver accruals</p><p class="lg-stat__value">₱{{ number_format($financialSummary['rider_accruals'], 2) }}</p><p class="lg-section-sub">{{ number_format($financialSummary['rider_accrual_lines']) }} completed route stops at your facilities</p></div></div>
                        <div class="lg-stat"><div class="lg-stat__body"><p class="lg-stat__label">Logistics pool · single-company routes</p><p class="lg-stat__value">₱{{ number_format($financialSummary['company_pool'], 2) }}</p><p class="lg-section-sub">Shipping remainder after eligible Rider / Driver credits</p></div></div>
                        <div class="lg-stat"><div class="lg-stat__body"><p class="lg-stat__label">Cross-company pool</p><p class="lg-stat__value">₱{{ number_format($financialSummary['cross_company_pool'], 2) }}</p><p class="lg-section-sub">Remainder not assigned between companies</p></div></div>
                    </div>
                    <p class="lg-section-sub">Shipping rule: ₱45 covers up to 2 route stops; each additional stop adds ₱10. A completed stop can accrue ₱10 to its assigned Rider or Transport Driver. The Admin product commission is separate and receives no shipping share.</p>
                    @if ($financialSummary['review_orders'] > 0)
                        <p class="lg-section-sub"><strong>{{ number_format($financialSummary['review_orders']) }} settlement(s)</strong> totaling ₱{{ number_format($financialSummary['review_shipping'], 2) }} are held for route review and have no Rider / Driver credits.</p>
                    @endif
                    <p class="lg-section-sub">Shipping total is the full order fee for parcels involving your network; the same cross-company order fee may appear in each participating company’s report, so do not add those totals together. Financial period uses settlement date; operational parcel tables use order creation date. Cross-company pool figures are informational and are not attributed as company revenue.</p>
                </div>
            </section>
        @endif
        <div class="lg-grid" style="grid-template-columns:repeat(auto-fit,minmax(min(100%,380px),1fr));align-items:start">
            <section class="lg-card"><div class="lg-card__head"><div><h2 class="lg-section-title">Parcel stages</h2><p class="lg-section-sub">Orders created during this period that passed through your network.</p></div></div>
                <div class="lg-table-wrap"><table class="lg-table"><thead><tr><th>Status</th><th class="is-num">Parcels</th></tr></thead><tbody>
                    @forelse ($statusRows as $status => $count)<tr><td>{{ ucfirst(str_replace('_', ' ', $status)) }}</td><td class="is-num">{{ number_format($count) }}</td></tr>@empty<tr><td colspan="2">No parcels in this period.</td></tr>@endforelse
                </tbody></table></div>
            </section>
            <section class="lg-card"><div class="lg-card__head"><h2 class="lg-section-title">Work completed</h2></div>
                <div class="lg-table-wrap"><table class="lg-table"><tbody>
                    <tr><th scope="row">Transport trips</th><td class="is-num">{{ $tripTotal }}</td></tr>
                    <tr><th scope="row">Completed trips</th><td class="is-num">{{ $tripCompleted }}</td></tr>
                    <tr><th scope="row">Scheduled or in transit</th><td class="is-num">{{ $tripActive }}</td></tr>
                    <tr><th scope="row">Pickup tasks</th><td class="is-num">{{ $pickupTasks }}</td></tr>
                    <tr><th scope="row">Delivery tasks</th><td class="is-num">{{ $deliveryTasks }}</td></tr>
                    <tr><th scope="row">Completed Rider tasks</th><td class="is-num">{{ $completedTasks }}</td></tr>
                    <tr><th scope="row">Late trips and manifests</th><td class="is-num">{{ $delayedTrips }}</td></tr>
                    <tr><th scope="row">Cross-company manifests</th><td class="is-num">{{ $manifestTotal }}</td></tr>
                    <tr><th scope="row">Completed cross-company manifests</th><td class="is-num">{{ $manifestCompleted }}</td></tr>
                    <tr><th scope="row">Open parcel exceptions</th><td class="is-num">{{ $openExceptions }}</td></tr>
                </tbody></table></div>
            </section>
        </div>
        <section class="lg-card"><div class="lg-card__head"><div><h2 class="lg-section-title">Facility activity</h2><p class="lg-section-sub">A parcel appears at each facility it visits.</p></div></div>
            <div class="lg-table-wrap"><table class="lg-table lg-table--stack"><thead><tr><th>Facility</th><th>Type</th><th class="is-num">Parcels</th><th class="is-num">Delivered</th><th class="is-num">Failed</th></tr></thead><tbody>
                @forelse ($facilityRows as $row)<tr><td data-label="Facility">{{ $row['name'] }}</td><td data-label="Type">{{ $row['kind'] }}</td><td data-label="Parcels" class="is-num">{{ $row['parcels'] }}</td><td data-label="Delivered" class="is-num">{{ $row['delivered'] }}</td><td data-label="Failed" class="is-num">{{ $row['failed'] }}</td></tr>
                @empty<tr><td colspan="5">No facilities in this scope.</td></tr>@endforelse
            </tbody></table></div>
        </section>
        <div class="lg-grid" style="grid-template-columns:repeat(auto-fit,minmax(min(100%,340px),1fr));align-items:start">
            @foreach ([['Transport Drivers', $driverRows, ['Trips', 'Completed', 'Late'], ['trips', 'completed', 'late']], ['Vehicles', $vehicleRows, ['Trips', 'Completed', 'Late'], ['trips', 'completed', 'late']], ['Riders', $riderRows, ['Tasks', 'Completed', 'Failed'], ['tasks', 'completed', 'failed']]] as [$heading, $rows, $labels, $keys])
                <section class="lg-card"><div class="lg-card__head"><h2 class="lg-section-title">{{ $heading }}</h2></div>
                    <div class="lg-table-wrap"><table class="lg-table"><thead><tr><th>Name</th>@foreach ($labels as $label)<th class="is-num">{{ $label }}</th>@endforeach</tr></thead><tbody>
                        @forelse ($rows as $row)<tr><td>{{ $row['name'] }}</td>@foreach ($keys as $key)<td class="is-num">{{ $row[$key] }}</td>@endforeach</tr>@empty<tr><td colspan="4">No activity in this period.</td></tr>@endforelse
                    </tbody></table></div>
                </section>
            @endforeach
        </div>
        <section class="lg-card"><div class="lg-card__head"><h2 class="lg-section-title">Delivery barangays</h2></div>
            <div class="lg-table-wrap"><table class="lg-table"><thead><tr><th>Barangay</th><th class="is-num">Parcels</th></tr></thead><tbody>
                @forelse ($barangayRows as $barangay => $count)<tr><td>{{ $barangay }}</td><td class="is-num">{{ $count }}</td></tr>@empty<tr><td colspan="2">No barangay data in this period.</td></tr>@endforelse
            </tbody></table></div>
        </section>
    </div>
</x-logistics.shell>
