{{-- PREVIEW ONLY: hard-coded data from routes/logistics_preview.php and routes/logistics_preview_station.php. Spec section 8 (Station Hub > Outgoing Transfers).
     Read only. Parcels waiting for a station transfer to the Province Hub, and the trips that start at this station (trip ID, driver, vehicle, status).
     The Station Hub never creates, changes or cancels a trip: only the Province Hub does. There is nothing to submit on this page. --}}
@php
    $statusLabel = ['scheduled' => 'Scheduled', 'in_transit' => 'In transit', 'completed' => 'Completed', 'cancelled' => 'Cancelled'];
    $statusTone = ['scheduled' => 'blue', 'in_transit' => 'amber', 'completed' => 'green', 'cancelled' => 'gray'];
    $onTheRoad = collect($trips)->where('status', 'in_transit');
    $hasScheduled = collect($trips)->contains('status', 'scheduled');
    $towns = collect($waiting)->countBy('town')->sortKeys()->all();
@endphp
<x-logistics.shell title="Outgoing Transfers" role="station">
    <div class="lg-page"
         x-data="{
            parcels: @js($waiting),
            q: '',
            town: '',
            limit: 25,
            get matched() {
                const s = this.q.trim().toLowerCase();
                return this.parcels.filter(p => (!this.town || p.town === this.town)
                    && (!s || [p.code, p.seller, p.town].some(v => v.toLowerCase().includes(s))));
            },
            get shown() { return this.matched.slice(0, this.limit); },
            clear() { this.q = ''; this.town = ''; this.limit = 25; }
         }">
        <div class="lg-page-head">
            <div>
                <h1>Outgoing Transfers</h1>
                <p>Parcels waiting to go to {{ $hub }}, and the trips that carry them. The Province Hub schedules every trip and chooses its driver and vehicle. This page only shows them.</p>
            </div>
            <div class="lg-page-head__actions">
                <a href="{{ route('lgp.station.sorting') }}" class="lg-btn lg-btn--outline"><x-logistics.icon name="layers" :size="18" /> Sorting</a>
            </div>
        </div>

        <div class="lg-grid" style="grid-template-columns:repeat(auto-fit,minmax(240px,1fr))">
            <div class="lg-stat">
                <span class="lg-stat__icon lg-stat__icon--blue"><x-logistics.icon name="package" :size="24" /></span>
                <div class="lg-stat__body">
                    <p class="lg-stat__label">Waiting for a trip</p>
                    <p class="lg-stat__value">{{ number_format(count($waiting)) }}</p>
                </div>
            </div>
            <div class="lg-stat">
                <span class="lg-stat__icon lg-stat__icon--amber"><x-logistics.icon name="truck" :size="24" /></span>
                <div class="lg-stat__body">
                    <p class="lg-stat__label">Parcels on the road to {{ $hub }}</p>
                    <p class="lg-stat__value">{{ number_format($onTheRoad->sum('parcels')) }}</p>
                </div>
            </div>
        </div>

        @if (count($waiting) && ! $hasScheduled)
            <div class="lg-note">
                <x-logistics.icon name="info" :size="18" />
                <span>No trip is scheduled for these {{ number_format(count($waiting)) }} parcels yet. When the Province Hub schedules the next transfer, its trip ID, driver and vehicle show under Trips from this station.</span>
            </div>
        @endif

        <section class="lg-card" aria-labelledby="ogWaitTitle">
            <div class="lg-card__head">
                <div>
                    <h2 class="lg-section-title" id="ogWaitTitle">Waiting for a trip to {{ $hub }}</h2>
                    <p class="lg-section-sub">Every parcel goes to {{ $hub }} first. The town is where it is finally going.</p>
                </div>
            </div>

            <div class="lg-toolbar" x-show="parcels.length">
                <div class="lg-search">
                    <x-logistics.icon name="search" :size="18" />
                    <input type="search" class="lg-input" x-model="q" @input="limit = 25" placeholder="Tracking number, seller or town" aria-label="Search waiting parcels">
                </div>
                <label class="sr-only" for="ogTown">Going to</label>
                <select id="ogTown" class="lg-select" x-model="town" @change="limit = 25">
                    <option value="">All towns</option>
                    @foreach ($towns as $name => $n)
                        <option value="{{ $name }}">{{ $name }} ({{ $n }})</option>
                    @endforeach
                </select>
                <span class="lg-toolbar__meta" aria-live="polite" x-text="matched.length + (matched.length === 1 ? ' parcel' : ' parcels')"></span>
            </div>

            <div class="lg-table-wrap" x-show="matched.length">
                <table class="lg-table lg-table--stack lg-table--mid">
                    <thead><tr><th>Tracking number</th><th>Seller</th><th>Going to</th><th>Waiting</th></tr></thead>
                    <tbody>
                        <template x-for="p in shown" :key="p.code">
                            <tr>
                                <td data-label="Tracking number"><strong x-text="p.code"></strong></td>
                                <td data-label="Seller" x-text="p.seller"></td>
                                <td data-label="Going to"><span x-text="p.town"></span><div class="lg-cell-sub">via {{ $hub }}</div></td>
                                <td data-label="Waiting"><span x-text="p.since"></span><div class="lg-cell-sub" x-text="p.ago"></div></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
                <div class="lg-toolbar" style="justify-content:center;padding:12px" x-show="matched.length > limit" x-cloak>
                    <button type="button" class="lg-btn lg-btn--outline lg-btn--sm" @click="limit += 25" x-text="'Show 25 more (' + (matched.length - limit) + ' left)'"></button>
                </div>
            </div>

            <x-logistics.empty x-show="!parcels.length" x-cloak icon="check-circle" title="No parcels are waiting for a transfer" text="Parcels you sort for another town in Sorting wait here until the Province Hub schedules a trip.">
                <a href="{{ route('lgp.station.sorting') }}" class="lg-btn lg-btn--outline lg-btn--sm">Open Sorting</a>
            </x-logistics.empty>
            <x-logistics.empty x-show="parcels.length && !matched.length" x-cloak icon="search" title="No parcels match" text="Check the spelling or choose another town.">
                <button type="button" class="lg-btn lg-btn--outline lg-btn--sm" @click="clear()">Clear filters</button>
            </x-logistics.empty>
        </section>

        <section class="lg-card" aria-labelledby="ogTripsTitle">
            <div class="lg-card__head">
                <div>
                    <h2 class="lg-section-title" id="ogTripsTitle">Trips from this station</h2>
                    <p class="lg-section-sub">Set by the Province Hub. Drivers scan parcels onto the vehicle in the mobile app.</p>
                </div>
            </div>
            @if (count($trips))
                <div class="lg-table-wrap">
                    <table class="lg-table lg-table--stack lg-table--mid">
                        <thead><tr><th>Trip</th><th>Status</th><th>Driver</th><th>Vehicle</th><th class="is-num">Parcels</th><th>When</th></tr></thead>
                        <tbody>
                            @foreach ($trips as $t)
                                <tr>
                                    <td data-label="Trip"><strong>{{ $t['id'] }}</strong><div class="lg-cell-sub">To {{ $t['destination'] }}</div></td>
                                    <td data-label="Status"><span class="lg-pill lg-pill--{{ $statusTone[$t['status']] }}">{{ $statusLabel[$t['status']] }}</span></td>
                                    <td data-label="Driver">{{ $t['driver'] }}</td>
                                    <td data-label="Vehicle">{{ $t['vehicle'] }}<div class="lg-cell-sub">{{ collect([$t['vehicleType'], $t['plate']])->filter()->implode(' · ') }}</div></td>
                                    <td data-label="Parcels" class="is-num">{{ number_format($t['parcels']) }}</td>
                                    <td data-label="When">{{ $t['when'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <x-logistics.empty icon="truck" title="No trips from this station yet" text="When the Province Hub schedules a transfer for your parcels, its trip ID, driver and vehicle show here." />
            @endif
        </section>
    </div>
</x-logistics.shell>
