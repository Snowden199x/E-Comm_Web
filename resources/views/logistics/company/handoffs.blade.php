{{--
    Company > Manual Handoffs (spec section 8A). Parcels handed from this company to another, or received from one.
    Fed by PortalController::companyHandoffs. The Dispatch form posts to CrossCompanyManifestController::store.
    Look: the seller pastel lilac system. Styles: resources/css/logistics/handoffs-theme.css (pushed last, tokens copied from
    seller/delivered-orders.css). No page script: row selection and the dispatch form run in the Alpine scope below.
    Status colors: Waiting at origin = butter, Awaiting receipt = sky, Received = mint, Delayed = butter.
    Status chips and the tracking-number search are server-side (this list is paged), so they are links and a GET form.
    Loading: shell skeleton when paging or filtering, spinner on the Dispatch button. Errors: top banner (flash component).
--}}
@php
    $mineId = (int) $company->id;
    $isEligible = fn ($h) => (int) $h->origin_company_id === $mineId && $h->status === 'awaiting_origin';
    $eligible = $handoffs->getCollection()->filter($isEligible);
    $canDispatch = $eligible->isNotEmpty();

    $routeMap = $eligible->mapWithKeys(fn ($h) => [(string) $h->id => $h->origin_facility_id.'-'.$h->destination_facility_id])->all();
    $originMap = $eligible->mapWithKeys(fn ($h) => [(string) $h->id => (string) $h->origin_facility_id])->all();
    $labelMap = $eligible->mapWithKeys(fn ($h) => [(string) $h->id => ($h->originFacility?->name ?? 'Origin').' to '.($h->destinationFacility?->name ?? 'Destination')])->all();
    $driverHubs = $drivers->mapWithKeys(fn ($d) => [(string) $d->id => (string) $d->province_hub_id])->all();
    $vehicleHubs = $vehicles->mapWithKeys(fn ($v) => [(string) $v->id => (string) $v->province_hub_id])->all();
    $picked = array_values(array_intersect(array_map('strval', (array) old('handoff_ids', [])), array_keys($routeMap)));

    $handoffTone = ['awaiting_origin' => 'butter', 'pending' => 'sky', 'accepted' => 'mint'];
    $handoffLabel = ['awaiting_origin' => 'Waiting at origin', 'pending' => 'Awaiting receipt', 'accepted' => 'Received'];
    $chips = [
        'all' => ['All', (int) $statusCounts->sum()],
        'awaiting_origin' => ['Waiting at origin', (int) ($statusCounts['awaiting_origin'] ?? 0)],
        'pending' => ['Awaiting receipt', (int) ($statusCounts['pending'] ?? 0)],
        'accepted' => ['Received', (int) ($statusCounts['accepted'] ?? 0)],
    ];
    $filtered = $status || $term !== '';
    $manifestCode = fn ($id) => 'CCM-'.str_pad((string) $id, 5, '0', STR_PAD_LEFT);
@endphp

@push('lg-head')
    @vite('resources/css/logistics/handoffs-theme.css')
@endpush

<x-logistics.shell title="Manual Handoffs" role="company">
    <div class="lgm"
         x-data="{
            selected: @js($picked),
            routes: @js($routeMap),
            origins: @js($originMap),
            labels: @js($labelMap),
            driverHubs: @js($driverHubs),
            vehicleHubs: @js($vehicleHubs),
            driver: @js((string) old('driver_id', '')),
            vehicle: @js((string) old('vehicle_id', '')),
            init() {
                // A new route means a new sending hub: drop a Driver or vehicle that belongs to another hub.
                this.$watch('originId', () => {
                    if (this.driver && this.driverHubs[this.driver] !== this.originId) this.driver = '';
                    if (this.vehicle && this.vehicleHubs[this.vehicle] !== this.originId) this.vehicle = '';
                });
            },
            get routeKey() { return this.selected.length ? this.routes[this.selected[0]] : null; },
            get originId() { return this.selected.length ? this.origins[this.selected[0]] : null; },
            get routeLabel() { return this.selected.length ? this.labels[this.selected[0]] : ''; },
            get sameRoute() { return Object.keys(this.routes).filter(id => this.routes[id] === (this.routeKey ?? this.routes[Object.keys(this.routes)[0]])); },
            get allPicked() { return this.sameRoute.length > 0 && this.sameRoute.every(id => this.selected.includes(id)); },
            blocked(id) { return this.routeKey !== null && this.routes[id] !== this.routeKey; },
            toggleAll() { this.selected = this.allPicked ? [] : [...this.sameRoute]; },
            rowClick(id, event) {
                if (!(id in this.routes) || this.blocked(id) || event.target.closest('input, a, button, label')) return;
                this.selected = this.selected.includes(id) ? this.selected.filter(x => x !== id) : [...this.selected, id];
            }
         }">

        <x-logistics.flash />

        <header class="lgm-head">
            <h1>Manual handoffs</h1>
            <p>Group parcels that share the same sending and receiving Province Hubs into one manifest. The receiving hub scans each parcel to confirm receipt.</p>
        </header>

        <div class="lgm-chips" role="group" aria-label="Filter handoffs by status">
            @foreach ($chips as $key => [$text, $count])
                @php $value = $key === 'all' ? null : $key; @endphp
                <a href="{{ route('lgp.company.handoffs', array_filter(['status' => $value, 'q' => $term])) }}" class="lgm-chip {{ ($status ?? null) === $value ? 'is-active' : '' }}" @if (($status ?? null) === $value) aria-current="true" @endif>
                    {{ $text }} <span class="lgm-chip__count">{{ $count }}</span>
                </a>
            @endforeach
            @if ($filtered)
                <a href="{{ route('lgp.company.handoffs') }}" class="lgm-chip lgm-chip--clear"><x-logistics.icon name="x" :size="14" /> Clear filters</a>
            @endif
        </div>

        <form method="GET" action="{{ route('lgp.company.handoffs') }}" class="lgm-toolbar" role="search">
            @if ($status) <input type="hidden" name="status" value="{{ $status }}"> @endif
            <label class="lgm-search">
                <span class="lgm-sr">Search by tracking number</span>
                <x-logistics.icon name="search" :size="18" />
                <input type="search" name="q" value="{{ $term }}" maxlength="100" autocomplete="off" placeholder="Search by tracking number, then press Enter">
            </label>
            <span class="lgm-meta">{{ number_format($handoffs->total()) }} {{ $handoffs->total() === 1 ? 'handoff' : 'handoffs' }}</span>
        </form>

        <form method="POST" action="{{ route('lgp.company.handoff-manifests.store') }}" class="lgm-form" novalidate>
            @csrf

            @if ($canDispatch)
                {{-- Dispatch bar: opens when parcels are ticked --}}
                <section class="lgm-card lgm-dispatch" x-show="selected.length" x-cloak aria-live="polite">
                    <div class="lgm-dispatch__sum">
                        <span class="lgm-tile lgm-tone-lilac"><x-logistics.icon name="truck" :size="20" /></span>
                        <div>
                            <strong x-text="selected.length + (selected.length === 1 ? ' parcel selected' : ' parcels selected')"></strong>
                            <small x-text="routeLabel"></small>
                        </div>
                    </div>
                    <div class="lgm-dispatch__fields">
                        <div class="lgm-field">
                            <label for="manifestDriver">Transport Driver</label>
                            <select id="manifestDriver" name="driver_id" x-model="driver" required>
                                <option value="">Choose a Driver</option>
                                @foreach ($drivers as $driver)
                                    <option value="{{ $driver->id }}" :disabled="originId !== null && originId !== '{{ $driver->province_hub_id }}'">{{ $driver->user?->name }} · {{ $driver->provinceHub?->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="lgm-field">
                            <label for="manifestVehicle">Vehicle</label>
                            <select id="manifestVehicle" name="vehicle_id" x-model="vehicle" required>
                                <option value="">Choose a vehicle</option>
                                @foreach ($vehicles as $vehicle)
                                    <option value="{{ $vehicle->id }}" :disabled="originId !== null && originId !== '{{ $vehicle->province_hub_id }}'">{{ $vehicle->vehicle_code }} · {{ $vehicle->provinceHub?->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="lgm-field">
                            <label for="manifestArrival">Expected arrival</label>
                            <input id="manifestArrival" name="expected_arrival_at" type="datetime-local" value="{{ old('expected_arrival_at') }}" min="{{ now()->addMinute()->format('Y-m-d\TH:i') }}" required>
                        </div>
                    </div>
                    <p class="lgm-hint">The Driver and vehicle must belong to the sending Province Hub. Options from other hubs are greyed out.</p>
                    <div class="lgm-dispatch__actions">
                        <button type="button" class="lgm-btn lgm-btn--soft" @click="selected = []">Clear selection</button>
                        <button type="submit" class="lgm-btn lgm-btn--primary"><x-logistics.icon name="truck" :size="16" /> Dispatch manifest</button>
                    </div>
                </section>
            @endif

            <section class="lgm-card lgm-table-card" aria-labelledby="queueTitle">
                <div class="lgm-card__head">
                    <h2 id="queueTitle">Handoff queue</h2>
                    <p>@if ($canDispatch) Tick parcels waiting at origin to build a manifest. @else Parcels appear here when a seller and buyer are served by different companies. @endif</p>
                </div>

                @if ($handoffs->count())
                    <div class="lgm-table-wrap">
                        <table class="lgm-table">
                            <thead>
                                <tr>
                                    @if ($canDispatch)
                                        <th scope="col" class="lgm-check"><input type="checkbox" :checked="allPicked" @change="toggleAll()" aria-label="Select all parcels on one route"></th>
                                    @endif
                                    <th scope="col">Parcel</th>
                                    <th scope="col">Route</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Manifest</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($handoffs as $handoff)
                                    @php $can = $isEligible($handoff); $id = (string) $handoff->id; @endphp
                                    <tr @if ($can) class="lgm-row" :class="{ 'is-selected': selected.includes('{{ $id }}'), 'is-blocked': blocked('{{ $id }}') }" @click="rowClick('{{ $id }}', $event)" @endif>
                                        @if ($canDispatch)
                                            <td class="lgm-check">
                                                @if ($can)
                                                    <input type="checkbox" name="handoff_ids[]" value="{{ $id }}" x-model="selected" :disabled="blocked('{{ $id }}')" aria-label="Include {{ $handoff->order?->tracking_number }} in the manifest">
                                                @endif
                                            </td>
                                        @endif
                                        <td><strong>{{ $handoff->order?->tracking_number ?? 'Unknown parcel' }}</strong></td>
                                        <td data-label="Route">
                                            {{ $handoff->originFacility?->name }} to {{ $handoff->destinationFacility?->name }}
                                            <small>{{ $handoff->originCompany?->business_name }} to {{ $handoff->destinationCompany?->business_name }}</small>
                                        </td>
                                        <td data-label="Status"><span class="lgm-pill lgm-pill--{{ $handoffTone[$handoff->status] ?? 'lilac' }}">{{ $handoffLabel[$handoff->status] ?? ucfirst($handoff->status) }}</span></td>
                                        <td data-label="Manifest">{{ $handoff->manifest_id ? $manifestCode($handoff->manifest_id) : 'Not dispatched' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if ($handoffs->hasPages())
                        <nav class="lgm-pager" aria-label="Pages">
                            <span>Page {{ $handoffs->currentPage() }} of {{ $handoffs->lastPage() }}</span>
                            <div>
                                @if ($handoffs->onFirstPage()) <span class="lgm-btn lgm-btn--soft lgm-btn--small is-disabled" aria-disabled="true">Previous</span>
                                @else <a href="{{ $handoffs->previousPageUrl() }}" class="lgm-btn lgm-btn--soft lgm-btn--small">Previous</a> @endif
                                @if ($handoffs->hasMorePages()) <a href="{{ $handoffs->nextPageUrl() }}" class="lgm-btn lgm-btn--soft lgm-btn--small">Next</a>
                                @else <span class="lgm-btn lgm-btn--soft lgm-btn--small is-disabled" aria-disabled="true">Next</span> @endif
                            </div>
                        </nav>
                    @endif
                @elseif ($filtered)
                    <div class="lgm-empty" role="status">
                        <span class="lgm-empty__icon lgm-tone-lilac"><x-logistics.icon name="search" :size="26" /></span>
                        <strong>No handoffs match your filters</strong>
                        <p>Check the tracking number, or pick another status.</p>
                        <a href="{{ route('lgp.company.handoffs') }}" class="lgm-btn lgm-btn--primary">Clear filters</a>
                    </div>
                @else
                    <div class="lgm-empty" role="status">
                        <span class="lgm-empty__icon lgm-tone-lilac"><x-logistics.icon name="truck" :size="26" /></span>
                        <strong>No manual handoffs</strong>
                        <p>A handoff appears here when the seller and buyer locations are served by different companies.</p>
                        <a href="{{ route('lgp.company.tracking') }}" class="lgm-btn lgm-btn--primary">Track parcels</a>
                    </div>
                @endif
            </section>
        </form>

        <section class="lgm-card lgm-table-card" aria-labelledby="manifestTitle">
            <div class="lgm-card__head">
                <h2 id="manifestTitle">Recent manifests</h2>
                <p>The latest 20 manifests you sent or received.</p>
            </div>
            @if ($manifests->count())
                <div class="lgm-table-wrap">
                    <table class="lgm-table">
                        <thead><tr><th scope="col">Manifest</th><th scope="col">Route</th><th scope="col">Driver and vehicle</th><th scope="col">Received</th><th scope="col">Expected arrival</th><th scope="col">Status</th></tr></thead>
                        <tbody>
                            @foreach ($manifests as $manifest)
                                @php
                                    $late = $manifest->status === 'in_transit' && $manifest->expected_arrival_at?->isPast();
                                    $mTone = $late ? 'butter' : match ($manifest->status) { 'in_transit' => 'sky', 'received', 'completed', 'accepted' => 'mint', 'cancelled' => 'rose', default => 'lilac' };
                                    $pct = $manifest->handoffs_count ? round($manifest->accepted_count / $manifest->handoffs_count * 100) : 0;
                                @endphp
                                <tr>
                                    <td><strong>{{ $manifestCode($manifest->id) }}</strong></td>
                                    <td data-label="Route">{{ $manifest->originFacility?->name }} to {{ $manifest->destinationFacility?->name }}</td>
                                    <td data-label="Driver and vehicle">{{ $manifest->driver?->user?->name ?? 'No Driver' }}<small>{{ $manifest->vehicle?->vehicle_code ?? 'No vehicle' }}</small></td>
                                    <td data-label="Received">
                                        <strong class="lgm-num">{{ $manifest->accepted_count }} of {{ $manifest->handoffs_count }}</strong>
                                        <span class="lgm-bar"><i style="--w: {{ $pct }}%"></i></span>
                                    </td>
                                    <td data-label="Expected arrival">{{ $manifest->expected_arrival_at?->format('M j, g:i A') ?? 'Not set' }}</td>
                                    <td data-label="Status"><span class="lgm-pill lgm-pill--{{ $mTone }}">{{ $late ? 'Delayed' : ucfirst(str_replace('_', ' ', $manifest->status)) }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="lgm-empty" role="status">
                    <span class="lgm-empty__icon lgm-tone-sky"><x-logistics.icon name="package" :size="26" /></span>
                    <strong>No manifests yet</strong>
                    <p>Dispatch parcels from the queue to create the first manifest.</p>
                </div>
            @endif
        </section>
    </div>
</x-logistics.shell>
