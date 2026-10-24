<x-logistics.shell title="Manual Handoffs" role="company">
    <div class="lg-page">
        <div class="lg-page-head"><div><h1>Cross-company handoffs</h1><p>Choose parcels with the same origin and destination Province Hubs. One manifest records their Driver, vehicle and receiving scans.</p></div></div>
        @if (session('success'))<div class="lg-note" role="status">{{ session('success') }}</div>@endif
        @if ($errors->any())<div class="lg-note lg-note--warn" role="alert">{{ $errors->first() }}</div>@endif
        <section class="lg-card" aria-label="Company handoffs">
            <div class="lg-card__head"><div><h2 class="lg-section-title">Handoff queue</h2><p class="lg-section-sub">The receiving Province Hub scans each parcel to confirm receipt.</p></div></div>
            @if ($handoffs->count())
                @php($canDispatch = $handoffs->contains(fn ($item) => (int) $item->origin_company_id === (int) $company->id && $item->status === 'awaiting_origin'))
                @if ($canDispatch)
                <form method="POST" action="{{ route('lgp.company.handoff-manifests.store') }}">
                    @csrf
                    <div class="lg-card__body lg-form-grid">
                        <div class="lg-field"><label for="manifestDriver">Transport Driver</label><select id="manifestDriver" name="driver_id" class="lg-select" required><option value="">Choose Driver</option>@foreach ($drivers as $driver)<option value="{{ $driver->id }}" @selected((string) old('driver_id') === (string) $driver->id)>{{ $driver->user?->name }} · {{ $driver->provinceHub?->name }}</option>@endforeach</select></div>
                        <div class="lg-field"><label for="manifestVehicle">Vehicle</label><select id="manifestVehicle" name="vehicle_id" class="lg-select" required><option value="">Choose vehicle</option>@foreach ($vehicles as $vehicle)<option value="{{ $vehicle->id }}" @selected((string) old('vehicle_id') === (string) $vehicle->id)>{{ $vehicle->vehicle_code }} · {{ $vehicle->provinceHub?->name }}</option>@endforeach</select></div>
                        <div class="lg-field"><label for="manifestArrival">Expected arrival</label><input id="manifestArrival" name="expected_arrival_at" type="datetime-local" class="lg-input" value="{{ old('expected_arrival_at') }}" min="{{ now()->addMinute()->format('Y-m-d\TH:i') }}" required></div>
                        <div class="lg-field"><label>Manifest</label><button type="submit" class="lg-btn">Dispatch selected parcels</button></div>
                        <p class="lg-section-sub is-wide">The Driver and vehicle must belong to the selected parcels' sending Province Hub.</p>
                    </div>
                @endif
                <div class="lg-table-wrap"><table class="lg-table lg-table--stack lg-table--mid">
                    <thead><tr><th>Select</th><th>Parcel</th><th>Sending company</th><th>Receiving company</th><th>Facilities</th><th>Status</th><th>Manifest</th></tr></thead>
                    <tbody>
                        @foreach ($handoffs as $handoff)
                            <tr>
                                <td data-label="Select">@if ((int) $handoff->origin_company_id === (int) $company->id && $handoff->status === 'awaiting_origin')<input type="checkbox" name="handoff_ids[]" value="{{ $handoff->id }}" @checked(in_array($handoff->id, old('handoff_ids', []))) aria-label="Include {{ $handoff->order?->tracking_number }} in manifest">@else—@endif</td>
                                <td data-label="Parcel"><strong>{{ $handoff->order?->tracking_number }}</strong></td>
                                <td data-label="Sending company">{{ $handoff->originCompany?->business_name }}</td>
                                <td data-label="Receiving company">{{ $handoff->destinationCompany?->business_name }}</td>
                                <td data-label="Facilities">{{ $handoff->originFacility?->name }} → {{ $handoff->destinationFacility?->name }}</td>
                                <td data-label="Status"><span class="lg-pill lg-pill--{{ $handoff->status === 'accepted' ? 'green' : 'amber' }}">{{ match ($handoff->status) { 'awaiting_origin' => 'Waiting at origin', 'pending' => 'Awaiting receipt', 'accepted' => 'Received', default => ucfirst($handoff->status) } }}</span></td>
                                <td data-label="Manifest">{{ $handoff->manifest_id ? 'CCM-'.str_pad((string) $handoff->manifest_id, 5, '0', STR_PAD_LEFT) : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table></div>
                @if ($canDispatch)</form>@endif
                {{ $handoffs->links() }}
            @else
                <x-logistics.empty icon="truck" title="No manual handoffs" text="A handoff appears here when the seller and buyer locations are served by different companies." />
            @endif
        </section>
        <section class="lg-card" aria-label="Recent manifests">
            <div class="lg-card__head"><h2 class="lg-section-title">Recent manifests</h2></div>
            <div class="lg-table-wrap"><table class="lg-table lg-table--stack"><thead><tr><th>Manifest</th><th>Route</th><th>Driver / vehicle</th><th>Received</th><th>Expected arrival</th><th>Status</th></tr></thead><tbody>
                @forelse ($manifests as $manifest)
                    <tr><td data-label="Manifest"><strong>CCM-{{ str_pad((string) $manifest->id, 5, '0', STR_PAD_LEFT) }}</strong></td>
                        <td data-label="Route">{{ $manifest->originFacility?->name }} → {{ $manifest->destinationFacility?->name }}</td>
                        <td data-label="Driver / vehicle">{{ $manifest->driver?->user?->name ?? 'Unassigned' }} · {{ $manifest->vehicle?->vehicle_code ?? '—' }}</td>
                        <td data-label="Received">{{ $manifest->accepted_count }} / {{ $manifest->handoffs_count }}</td>
                        <td data-label="Expected arrival">{{ $manifest->expected_arrival_at?->format('M j, g:i A') ?? '—' }}</td>
                        <td data-label="Status">{{ $manifest->status === 'in_transit' && $manifest->expected_arrival_at?->isPast() ? 'Delayed' : ucfirst(str_replace('_', ' ', $manifest->status)) }}</td></tr>
                @empty<tr><td colspan="6">No cross-company manifests yet.</td></tr>@endforelse
            </tbody></table></div>
        </section>
    </div>
</x-logistics.shell>
