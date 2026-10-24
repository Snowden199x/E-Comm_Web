@php
    $pages = [
        'pickups' => ['Pickup Requests', 'Verify and assign seller pickups to Riders at this station.'],
        'tasks' => ['Task Assignment', 'Open pickup and delivery tasks assigned to your Riders.'],
        'delivery-queue' => ['Delivery Queue', 'Assign sorted parcels to a local Rider for final delivery.'],
        'incoming' => ['Incoming Parcels', 'Scan arriving parcels with the camera button in the header. Arrival also records sorting.'],
        'sorting' => ['Parcel Sorting', 'Parcels scanned and sorted at this station, with their next planned stop.'],
        'outgoing' => ['Outgoing Transfers', 'Sorted parcels waiting for a Province Hub trip. Scan departure after the trip is scheduled.'],
    ];
    [$title, $description] = $pages[$mode];
@endphp
<x-logistics.shell :title="$title" role="station">
    <div class="lg-page" x-data="{ assign: null, assignUrl: @js(route('lgp.station.orders.assign', '__order__')) }">
        <div class="lg-page-head">
            <div>
                <h1>{{ $title }}</h1>
                <p>{{ $description }}</p>
            </div>
            @if (in_array($mode, ['incoming', 'sorting', 'outgoing'], true))
                <div class="lg-page-head__actions"><span class="lg-hint">{{ $station->name }}</span></div>
            @endif
        </div>

        @if (session('success'))<div class="lg-note" role="status">{{ session('success') }}</div>@endif
        @if ($errors->any())<div class="lg-note lg-note--warn" role="alert">{{ $errors->first() }}</div>@endif

        <section class="lg-card" aria-label="{{ $title }}">
            <div class="lg-card__head">
                <div><h2 class="lg-section-title">{{ $title }}</h2><p class="lg-section-sub">{{ $rows->count() }} {{ $rows->count() === 1 ? 'parcel' : 'parcels' }}</p></div>
            </div>
            @if ($rows->isNotEmpty())
                <div class="lg-table-wrap"><table class="lg-table lg-table--stack lg-table--mid">
                    <thead><tr><th>Tracking number</th><th>{{ $mode === 'delivery-queue' ? 'Buyer' : 'Seller' }}</th><th>{{ $mode === 'tasks' ? 'Task' : 'Address' }}</th><th>Status</th><th>{{ $mode === 'outgoing' || $mode === 'sorting' ? 'Next stop' : 'Rider / scanned' }}</th><th class="is-actions">Action</th></tr></thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr>
                                <td data-label="Tracking"><strong>{{ $row['tracking'] }}</strong></td>
                                <td data-label="Contact">{{ $mode === 'delivery-queue' ? ($row['buyer'] ?: '—') : ($row['seller'] ?: '—') }}</td>
                                <td data-label="Address or task">{{ $mode === 'tasks' ? ucfirst($row['type']) : ($row['address'] ?: '—') }}</td>
                                <td data-label="Status"><span class="lg-pill lg-pill--gray">{{ $row['status'] }}</span>
                                    @if ($mode === 'pickups' && $row['pickup_status'] === 'pending')<div class="lg-cell-sub">Verification pending</div>@endif
                                </td>
                                <td data-label="Next or scan">
                                    @if (in_array($mode, ['sorting', 'outgoing'], true)){{ $row['next'] }}
                                    @elseif ($mode === 'incoming' || $mode === 'sorting'){{ $row['arrived'] ?: 'Awaiting scan' }}
                                    @elseif ($mode === 'tasks'){{ $row['rider_name'] ?: '—' }}
                                        @if (!empty($row['failure_reason']))<div class="lg-cell-sub">{{ $row['failure_reason'] }}</div>@endif
                                    @else{{ $row['rider'] ? 'Rider assigned' : 'No Rider yet' }}@endif
                                </td>
                                <td data-label="Action" class="is-actions">
                                    @if ($row['can_assign'] && in_array($mode, ['pickups', 'delivery-queue'], true))
                                        <button type="button" class="lg-btn lg-btn--sm" @disabled($riders->isEmpty())
                                            data-tracking="{{ $row['tracking'] }}"
                                            @click="assign = { id: {{ $row['id'] }}, tracking: $el.dataset.tracking, type: '{{ $mode === 'pickups' ? 'pickup' : 'delivery' }}' }">
                                            {{ $row['rider'] ? 'Reassign' : ($mode === 'pickups' ? 'Verify & assign' : 'Assign Rider') }}
                                        </button>
                                        @if ($mode === 'pickups' && $row['pickup_status'] === 'pending' && ! $row['rider'])
                                            <form method="POST" action="{{ route('lgp.station.orders.decline-pickup', $row['id']) }}" class="lg-field" style="margin-top:8px">
                                                @csrf
                                                <label for="decline-{{ $row['id'] }}">If parcel is not ready</label>
                                                <input id="decline-{{ $row['id'] }}" name="reason" class="lg-input" placeholder="Reason for seller" minlength="5" maxlength="500" required>
                                                <button type="submit" class="lg-btn lg-btn--outline lg-btn--sm">Return to seller</button>
                                            </form>
                                        @endif
                                    @else<span class="lg-hint">{{ $mode === 'incoming' && ! $row['arrived'] ? 'Use scanner' : '—' }}</span>@endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table></div>
            @else
                <x-logistics.empty icon="package" :title="'No '.strtolower($title).' yet'" text="Parcels appear here when they reach this station and the required earlier steps are complete." />
            @endif
        </section>

        @if ($mode === 'outgoing' && $trips->isNotEmpty())
            <section class="lg-card" aria-label="Trips from this station">
                <div class="lg-card__head"><h2 class="lg-section-title">Trips from this station</h2></div>
                <div class="lg-table-wrap"><table class="lg-table"><thead><tr><th>Trip</th><th>Destination</th><th>Driver</th><th>Vehicle</th><th>Status</th></tr></thead><tbody>
                    @foreach ($trips as $trip)
                        <tr><td>TRP-{{ str_pad((string) $trip->id, 5, '0', STR_PAD_LEFT) }}</td><td>{{ $trip->destination?->name }}</td><td>{{ $trip->driver?->user?->name }}</td><td>{{ $trip->vehicle?->vehicle_code }}</td><td>{{ ucfirst(str_replace('_', ' ', $trip->status)) }}</td></tr>
                    @endforeach
                </tbody></table></div>
            </section>
        @endif

        @if (in_array($mode, ['pickups', 'delivery-queue'], true))
            <div class="lg-overlay" x-show="assign" x-cloak @click.self="assign = null" @keydown.escape.window="assign = null">
                <div class="lg-modal" style="width:min(100%,560px)" role="dialog" aria-modal="true" aria-labelledby="stationAssignTitle">
                    <form method="POST" :action="assign ? assignUrl.replace('__order__', assign.id) : ''">
                        @csrf
                        <div class="lg-modal__head"><div><h3 id="stationAssignTitle" x-text="assign ? 'Assign ' + assign.tracking : 'Assign Rider'"></h3><p>Choose an approved Rider from {{ $station->name }}.</p></div>
                            <button type="button" class="lg-toast__close" @click="assign = null" aria-label="Close"><x-logistics.icon name="x" :size="18" /></button></div>
                        <div class="lg-modal__body lg-modal__body--grid"><input type="hidden" name="type" :value="assign?.type || ''">
                            <div class="lg-field"><label for="stationRider">Rider</label><select id="stationRider" name="rider_id" class="lg-select" required>
                                <option value="">Choose a Rider</option>
                                @foreach ($riders as $rider)<option value="{{ $rider->id }}">{{ $rider->user?->name }} · {{ $rider->vehicle_type }}</option>@endforeach
                            </select></div>
                        </div>
                        <div class="lg-modal__foot"><button type="button" class="lg-btn lg-btn--outline" @click="assign = null">Cancel</button><button type="submit" class="lg-btn">Assign Rider</button></div>
                    </form>
                </div>
            </div>
        @endif
    </div>
</x-logistics.shell>
