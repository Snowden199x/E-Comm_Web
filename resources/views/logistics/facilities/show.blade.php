<x-logistics.layout :title="$facility->name">
    <div class="lg-page" data-facility-scanner data-scan-url="{{ route('logistics.facilities.scans.store', $facility) }}">
        <div class="lg-page-head">
            <div>
                <a href="{{ route('logistics.facilities.index') }}" class="lg-facility-back">← Hubs & Stations</a>
                <h1>{{ $facility->name }}</h1>
                <p>{{ $facility->kind === 'main_hub' ? 'Main Hub' : 'Station' }} · {{ $facility->municipality }}, {{ $facility->province }}</p>
            </div>
            <button type="button" class="lg-btn" data-scanner-open @disabled(! $facility->is_active)>Scan parcel</button>
        </div>

        <div class="lg-card lg-facility-info">
            <div><strong>Operating address</strong><span>{{ $facility->address ?: 'Address not set' }}</span></div>
            <div><strong>Status</strong><span>{{ $facility->is_active ? 'Active' : 'Inactive' }}</span></div>
            <div><strong>Network</strong><span>{{ $center->business_name }} · {{ $center->coverage_province ?: $center->province }}</span></div>
        </div>

        @if ($facility->kind === 'station')
            <section class="lg-card lg-facility-edit" aria-labelledby="stationSettingsTitle">
                <div class="lg-card__head"><h2 id="stationSettingsTitle" class="lg-section-title">Station details</h2></div>
                <form method="POST" action="{{ route('logistics.facilities.update', $facility) }}" class="lg-facility-form">
                    @csrf @method('PATCH')
                    <div class="lg-field"><label for="editName">Name</label><input id="editName" name="name" class="lg-input" value="{{ old('name', $facility->name) }}" maxlength="255" required></div>
                    <div class="lg-field"><label for="editAddress">Address</label><input id="editAddress" name="address" class="lg-input" value="{{ old('address', $facility->address) }}" maxlength="255" required></div>
                    <div class="lg-field"><label for="editState">Availability</label><select id="editState" name="is_active" class="lg-select"><option value="1" @selected($facility->is_active)>Active</option><option value="0" @selected(! $facility->is_active)>Inactive</option></select></div>
                    <button type="submit" class="lg-btn lg-btn--outline">Save details</button>
                </form>
            </section>
        @endif

        <section class="lg-card" aria-labelledby="facilityParcelsTitle">
            <div class="lg-card__head"><div><h2 id="facilityParcelsTitle" class="lg-section-title">Parcels at or heading to this facility</h2><p class="lg-section-sub">Main Hub pickup work and recorded route stops appear here.</p></div></div>
            @if ($orders->count())
                <div class="lg-table-wrap"><table class="lg-table"><thead><tr><th>Parcel</th><th>Seller</th><th>Status</th><th>Next step</th></tr></thead><tbody>
                    @foreach ($orders as $order)
                        @php $stop = $order->facilityStops->firstWhere('facility_id', $facility->id); @endphp
                        <tr><td><strong>{{ $order->number }}</strong><br><span class="lg-muted">{{ $order->tracking_number }}</span></td><td>{{ $order->seller?->name ?: '—' }}</td><td>{{ $order->status_label }}</td><td>{{ $stop ? ($stop->departed_at ? 'Departed' : ($stop->sorted_at ? 'Sorted' : ($stop->arrived_at ? 'Sort parcel' : 'Awaiting arrival'))) : 'Main Hub work' }}</td></tr>
                    @endforeach
                </tbody></table></div>
                {{ $orders->links() }}
            @else
                <div class="lg-empty"><h3>No parcels assigned</h3><p>Parcels appear after the origin Main Hub chooses this facility during sorting.</p></div>
            @endif
        </section>

        <div class="lg-scan-overlay" data-scanner-modal hidden>
            <div class="lg-scan-dialog" role="dialog" aria-modal="true" aria-labelledby="scannerTitle">
                <div class="lg-scan-head"><div><h2 id="scannerTitle">Scan parcel</h2><p>{{ $facility->name }} · {{ $facility->municipality }}</p></div><button type="button" class="lg-scan-close" data-scanner-close aria-label="Close scanner" title="Close scanner"><span aria-hidden="true">×</span></button></div>
                <div class="lg-scan-body">
                    <div class="lg-scan-camera"><video data-scanner-video muted playsinline aria-label="Camera preview"></video><div class="lg-scan-target" aria-hidden="true"></div></div>
                    <p class="lg-scan-help">Hold the shipping label's QR code or barcode inside the frame. Scans process automatically.</p>
                    <p class="lg-scan-message" data-scanner-message role="status">Allow camera access. Scans are recorded automatically.</p>
                    <form class="lg-scan-form" data-scanner-form>
                        <div class="lg-field"><label for="scanTracking">Tracking number</label><input id="scanTracking" class="lg-input" data-scanner-tracking autocomplete="off" required placeholder="Scan label or enter number and press Enter"></div>
                        <div class="lg-scan-actions"><button type="button" class="lg-btn lg-btn--outline" data-scanner-restart>Use camera</button></div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @vite('resources/js/logistics/facility-scanner.js')
</x-logistics.layout>
