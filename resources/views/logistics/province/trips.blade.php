{{--
    Province Hub > Transport Trips. Station transfers and inter-province trips run by this hub. Fed by PortalController::provinceTrips
    (trips from tripCard, the current time, and the JSON urls). The page state lives in one Alpine scope.
    Look: the seller pastel lilac system, the same .lgh layer as Province Hub > Transport Drivers. Styles: hubs-theme.css, drivers-theme.css
    (pills, callouts, facts, details dialog) and trips-theme.css, loaded last. Dialogs sit in .lgh-layer and carry their own copy of the tokens.
    Status colors: Scheduled = butter (waiting), In transit = sky, Completed = mint, Cancelled = rose.
    Status chips with counts; the type is a select that applies on change; search runs as you type. One click on a row opens the trip.
    Each row has a visible primary button: Record departure for a scheduled trip, Record arrival for a trip in transit, otherwise View details.
    Loading: shell skeleton on open; spinner on the button that was pressed (admin button-loading.js).
    Empty: no trips at all, nothing in a status, and no trips match the filters. Errors: inside the dialog that was open, or in the top banner.
--}}
@push('lg-head')
    @vite(['resources/css/logistics/hubs-theme.css', 'resources/css/logistics/drivers-theme.css', 'resources/css/logistics/trips-theme.css'])
@endpush

<x-logistics.shell title="Transport Trips" role="province">
    <div class="lgh"
         x-data="{
            trips: @js($trips),
            createUrl: @js($createUrl),
            cancelUrl: @js($cancelUrl),
            departUrl: @js($departUrl),
            arriveUrl: @js($arriveUrl),
            trackingUrl: @js($trackingUrl),
            q: '',
            tab: 'all',
            type: '',
            view: null,
            alert: '',
            working: false,
            confirm: { open: false, t: null, reason: '', error: '', alert: '' },
            typeLabels: { station_transfer: 'Station transfer', inter_province: 'Inter-province' },
            labels: { scheduled: 'Scheduled', in_transit: 'In transit', completed: 'Completed', cancelled: 'Cancelled' },
            tones: { scheduled: 'lgh-pill--butter', in_transit: 'lgh-pill--sky', completed: 'lgh-pill--mint', cancelled: 'lgh-pill--rose' },
            tabEmpty: {
                scheduled: ['No scheduled trips', 'A trip shows here from the moment you create it until you record its departure.'],
                in_transit: ['No trips in transit', 'A trip shows here after you record its departure, until it arrives.'],
                completed: ['No completed trips', 'A trip shows here after you record its arrival.'],
                cancelled: ['No cancelled trips', 'A trip you cancel shows here, with your reason.']
            },
            init() {
                // Any popup open: the page behind it does not scroll.
                this.$watch(() => this.view !== null || this.confirm.open, (isOpen) => {
                    const main = document.querySelector('.vd-main');
                    if (main) main.style.overflow = isOpen ? 'hidden' : '';
                });
            },
            busy(button, on) { if (button && window.vdButtonLoading) window.vdButtonLoading(button, on); },
            headers(json) { const h = { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '' }; if (json) h['Content-Type'] = 'application/json'; return h; },
            failure(error, fallback) { return error instanceof TypeError ? 'You are offline. Check your connection and try again.' : (error.message || fallback); },
            async request(url, body) {
                const response = await fetch(url, { method: 'PATCH', headers: this.headers(!!body), body: body ? JSON.stringify(body) : undefined });
                const payload = await response.json().catch(() => ({}));
                if (!response.ok) {
                    const fallback = response.status === 419 ? 'Your session expired. Reload the page and try again.'
                        : (response.status === 403 ? 'This account can’t change trips. Sign in with the Province Hub account.'
                        : (response.status >= 500 ? 'Something went wrong on our side. Try again in a moment.' : 'Could not update the trip. Reload the page and try again.'));
                    throw new Error(Object.values(payload.errors || {})[0]?.[0] || payload.message || fallback);
                }
                return payload;
            },
            rank(t) { return t.status === 'in_transit' ? 0 : (t.status === 'scheduled' ? 1 : 2); },
            get searching() { return this.q.trim() !== ''; },
            get filtered() { return this.searching || this.tab !== 'all' || this.type !== ''; },
            get shown() {
                const s = this.q.trim().toLowerCase();
                return this.trips
                    .filter(t => (this.tab === 'all' || t.status === this.tab) && (!this.type || t.type === this.type)
                        && (!s || (t.id + ' ' + t.origin + ' ' + t.destination + ' ' + t.driver + ' ' + t.vehicle).toLowerCase().includes(s)))
                    // Running and upcoming trips first (soonest departure first), then past trips (latest first).
                    .sort((a, b) => this.rank(a) - this.rank(b) || (this.rank(a) < 2 ? (a.scheduled || '').localeCompare(b.scheduled || '') : (b.scheduled || '').localeCompare(a.scheduled || '')));
            },
            count(k) { return k === 'all' ? this.trips.length : this.trips.filter(t => t.status === k).length; },
            clearFilters() { this.q = ''; this.tab = 'all'; this.type = ''; },
            fmt(iso) { return iso ? new Date(iso).toLocaleString('en-PH', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' }) : 'Not set'; },
            canDepart(t) { return t.status === 'scheduled' && t.can_depart; },
            canArrive(t) { return t.status === 'in_transit' && t.can_arrive; },
            go(t, event) { if (!event.target.closest('a, button')) this.openView(t); },
            openView(t) { this.alert = ''; this.view = t; },
            closeView() { this.view = null; },
            closeTop() { if (this.confirm.open) this.confirm.open = false; else if (this.view) this.view = null; },
            askCancel(t) { this.confirm = { open: true, t: t, reason: '', error: '', alert: '' }; this.$nextTick(() => document.getElementById('tripReason')?.focus()); },
            async submitCancel(event) {
                const c = this.confirm, t = c.t;
                if (this.working) return;
                if (t.status !== 'scheduled') { c.alert = t.id + ' is no longer scheduled, so it can’t be cancelled. Close this and check its status.'; return; }
                if (c.reason.trim().length < 5) {
                    c.error = 'Reason is too short. Write at least 5 characters so the stations know why the trip was cancelled.';
                    document.getElementById('tripReason')?.focus();
                    return;
                }
                const button = event.currentTarget;
                this.working = true; this.busy(button, true); c.error = ''; c.alert = '';
                try {
                    const payload = await this.request(this.cancelUrl.replace('__trip__', t.key), { reason: c.reason.trim() });
                    Object.assign(t, payload.trip);
                    c.open = false; this.view = null;
                    this.$dispatch('lg-toast', { type: 'success', message: payload.message });
                } catch (error) { c.alert = this.failure(error, 'Could not cancel the trip. Try again.'); }
                finally { this.working = false; this.busy(button, false); }
            },
            async changeStatus(t, action, event) {
                if (this.working) return;
                const button = event.currentTarget;
                this.working = true; this.busy(button, true); this.alert = '';
                try {
                    const payload = await this.request((action === 'depart' ? this.departUrl : this.arriveUrl).replace('__trip__', t.key));
                    Object.assign(t, payload.trip);
                    this.$dispatch('lg-toast', { type: 'success', message: payload.message });
                } catch (error) {
                    const message = this.failure(error, 'Could not update the trip. Try again.');
                    if (this.view === t) this.alert = message;
                    else this.$dispatch('lg-toast', { type: 'error', message: message });
                } finally { this.working = false; this.busy(button, false); }
            }
         }"
         @keydown.escape.window="closeTop()">

        <x-logistics.flash />

        <header class="lgh-head">
            <div>
                <h1>Transport trips</h1>
                <p>Station transfers and inter-province trips run by {{ $hub['name'] }}.</p>
            </div>
            <a href="{{ $createUrl }}" class="lgh-btn lgh-btn--primary">
                <x-logistics.icon name="plus" :size="16" /> Create trip
            </a>
        </header>

        @if (count($trips))
            {{-- Status chips with counts, then search and type --}}
            <div class="lgh-chips" role="group" aria-label="Filter trips by status">
                @foreach (['all' => 'All', 'scheduled' => 'Scheduled', 'in_transit' => 'In transit', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $key => $text)
                    <button type="button" class="lgh-chip" :class="{ 'is-active': tab === '{{ $key }}' }" :aria-pressed="tab === '{{ $key }}'" @click="tab = '{{ $key }}'">
                        {{ $text }} <span class="lgh-chip__count" x-text="count('{{ $key }}')"></span>
                    </button>
                @endforeach
                <button type="button" class="lgh-chip lgh-chip--clear" x-show="filtered" x-cloak @click="clearFilters()">
                    <x-logistics.icon name="x" :size="14" /> Clear filters
                </button>
            </div>

            <div class="lgh-toolbar">
                <label class="lgh-search">
                    <span class="lgh-sr">Search trips</span>
                    <x-logistics.icon name="search" :size="18" />
                    <input type="search" x-model="q" @keydown.escape="q = ''" @keydown.enter.prevent autocomplete="off" maxlength="100" placeholder="Search trip, hub, driver or vehicle">
                </label>
                <div class="lgh-select">
                    <label class="lgh-sr" for="tripType">Filter by type</label>
                    <select id="tripType" x-model="type">
                        <option value="">All types</option>
                        <option value="station_transfer">Station transfers</option>
                        <option value="inter_province">Inter-province</option>
                    </select>
                </div>
                <span class="lgh-meta" aria-live="polite" x-text="shown.length + (shown.length === 1 ? ' trip' : ' trips')"></span>
            </div>

            <section class="lgh-card lgh-table-card" aria-label="Transport trips">
                <div class="lgh-table-wrap" x-show="shown.length" x-cloak>
                    <table class="lgh-table lgh-table--trips">
                        <thead>
                            <tr>
                                <th scope="col">Trip</th>
                                <th scope="col">Route</th>
                                <th scope="col">Driver and vehicle</th>
                                <th scope="col" class="is-num">Parcels</th>
                                <th scope="col">Departure</th>
                                <th scope="col">Status</th>
                                <th scope="col"><span class="lgh-sr">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="t in shown" :key="t.id">
                                <tr class="lgh-row" @click="go(t, $event)">
                                    <td>
                                        <button type="button" class="lgh-name lgh-name--btn" @click="openView(t)" x-text="t.id"></button>
                                        <small x-text="typeLabels[t.type] || t.type"></small>
                                    </td>
                                    <td data-label="Route">
                                        <span x-text="t.origin"></span>
                                        <small x-text="'to ' + t.destination"></small>
                                    </td>
                                    <td data-label="Driver and vehicle">
                                        <span x-text="t.driver"></span>
                                        <small x-text="t.vehicle"></small>
                                    </td>
                                    <td class="is-num" data-label="Parcels" x-text="t.parcels"></td>
                                    <td data-label="Departure">
                                        <span x-text="fmt(t.departed || t.scheduled)"></span>
                                        <small x-text="t.departed ? 'Departed' : 'Scheduled'"></small>
                                    </td>
                                    <td data-label="Status"><span class="lgh-pill" :class="tones[t.status]" x-text="labels[t.status]"></span></td>
                                    <td class="lgh-actions">
                                        <div class="lgh-actions__row">
                                            <template x-if="canDepart(t) || canArrive(t)">
                                                <button type="button" class="lgh-btn lgh-btn--small lgh-btn--primary" @click="changeStatus(t, canDepart(t) ? 'depart' : 'arrive', $event)"
                                                        :aria-label="(canDepart(t) ? 'Record departure of ' : 'Record arrival of ') + t.id">
                                                    <span x-text="canDepart(t) ? 'Record departure' : 'Record arrival'"></span>
                                                </button>
                                            </template>
                                            <button type="button" class="lgh-btn lgh-btn--small" :class="(canDepart(t) || canArrive(t)) ? 'lgh-btn--ghost' : 'lgh-btn--soft'" @click="openView(t)" :aria-label="'View details for ' + t.id">
                                                View details <x-logistics.icon name="arrow-right" :size="14" class="lgh-nudge" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                {{-- Nothing matches the filters --}}
                <div class="lgh-empty" x-show="!shown.length && searching || !shown.length && type !== ''" x-cloak role="status">
                    <span class="lgh-empty__icon lgh-tone-lilac"><x-logistics.icon name="search" :size="26" /></span>
                    <strong>No trips match your filters</strong>
                    <p>Check the spelling, or search by trip, hub, driver or vehicle. Try another type or status too.</p>
                    <button type="button" class="lgh-btn lgh-btn--primary" @click="clearFilters()">Clear filters</button>
                </div>

                {{-- Nothing in this status --}}
                <div class="lgh-empty" x-show="!shown.length && !searching && type === '' && tab !== 'all'" x-cloak role="status">
                    <span class="lgh-empty__icon" :class="tab === 'in_transit' ? 'lgh-tone-sky' : 'lgh-tone-lilac'"><x-logistics.icon name="truck" :size="26" /></span>
                    <strong x-text="(tabEmpty[tab] || [''])[0]"></strong>
                    <p x-text="(tabEmpty[tab] || ['', ''])[1]"></p>
                    <a href="{{ $createUrl }}" class="lgh-btn lgh-btn--soft" x-show="tab === 'scheduled'"><x-logistics.icon name="plus" :size="16" /> Create trip</a>
                    <button type="button" class="lgh-btn lgh-btn--soft" x-show="tab !== 'scheduled'" @click="tab = 'all'">Show all trips</button>
                </div>
            </section>
        @else
            {{-- No trips at all --}}
            <section class="lgh-card" aria-label="Transport trips">
                <div class="lgh-empty" role="status">
                    <span class="lgh-empty__icon lgh-tone-lilac"><x-logistics.icon name="calendar" :size="26" /></span>
                    <strong>No trips yet</strong>
                    <p>Create a trip to move sorted parcels between a Station Hub and your hub, or to another Province Hub.</p>
                    <a href="{{ $createUrl }}" class="lgh-btn lgh-btn--primary"><x-logistics.icon name="plus" :size="16" /> Create trip</a>
                </div>
            </section>
        @endif

        {{-- Trip details with its progress. A scheduled trip can be cancelled; one already in transit cannot. --}}
        <div class="lgh-layer" x-show="view" x-cloak>
            <div class="lgh-backdrop" @click="closeView()"></div>
            <div class="lgh-dialog lgh-dialog--wide" role="dialog" aria-modal="true" aria-labelledby="tripTitle">
                <header class="lgh-dialog__head">
                    <div>
                        <h2 id="tripTitle" x-text="view ? view.id + ' · ' + (typeLabels[view.type] || view.type) : ''"></h2>
                        <p><span class="lgh-pill" :class="view ? tones[view.status] : ''" x-text="view ? labels[view.status] : ''"></span></p>
                    </div>
                    <button type="button" class="lgh-close" @click="closeView()" aria-label="Close"><svg class="lgh-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg></button>
                </header>
                <div class="lgh-dialog__body">
                    <div class="lgh-callout lgh-callout--rose" x-show="alert" x-cloak role="alert">
                        <x-logistics.icon name="alert" :size="18" />
                        <p x-text="alert"></p>
                    </div>
                    <template x-if="view && view.status === 'scheduled'">
                        <div class="lgh-callout lgh-callout--lilac">
                            <x-logistics.icon name="info" :size="18" />
                            <p>Scan every parcel for departure at <strong x-text="view.origin"></strong>, then record the departure.</p>
                        </div>
                    </template>
                    <template x-if="view && view.status === 'in_transit'">
                        <div class="lgh-callout lgh-callout--sky">
                            <x-logistics.icon name="truck" :size="18" />
                            <p>Scan every parcel at <strong x-text="view.destination"></strong>, then record the arrival.</p>
                        </div>
                    </template>
                    <template x-if="view && view.status === 'cancelled'">
                        <div class="lgh-callout lgh-callout--rose">
                            <x-logistics.icon name="alert" :size="18" />
                            <p><strong>Cancelled.</strong> <span x-text="view.note || 'No reason was recorded.'"></span></p>
                        </div>
                    </template>

                    <dl class="lgh-facts" x-show="view">
                        <div><dt>From</dt><dd x-text="view && view.origin"></dd></div>
                        <div><dt>To</dt><dd x-text="view && view.destination"></dd></div>
                        <div><dt>Driver</dt><dd x-text="view && view.driver"></dd></div>
                        <div><dt>Vehicle</dt><dd x-text="view && view.vehicle"></dd></div>
                        <div><dt>Parcels loaded</dt><dd x-text="view && view.parcels"></dd></div>
                        <div><dt>Scheduled departure</dt><dd x-text="view && fmt(view.scheduled)"></dd></div>
                    </dl>

                    <div class="lgh-docs" x-show="view && view.manifest && view.manifest.length" x-cloak>
                        <h3>Manifest · open a parcel to track it</h3>
                        <ul class="lgh-manifest">
                            <template x-for="code in (view ? view.manifest : [])" :key="code">
                                <li><a :href="trackingUrl + '?q=' + encodeURIComponent(code)" x-text="code"></a></li>
                            </template>
                        </ul>
                    </div>

                    <template x-if="view && view.status !== 'cancelled'">
                        <ol class="lgh-steps">
                            <li :class="view.status === 'scheduled' ? 'is-current' : 'is-done'">
                                <strong>Scheduled</strong>
                                <small x-text="'Departure planned ' + fmt(view.scheduled)"></small>
                            </li>
                            <li :class="view.status === 'in_transit' ? 'is-current' : (view.status === 'completed' ? 'is-done' : '')">
                                <strong>Departed</strong>
                                <small x-text="view.departed ? 'Departure recorded ' + fmt(view.departed) : 'Not departed yet'"></small>
                            </li>
                            <li :class="view.status === 'completed' ? 'is-done' : ''">
                                <strong>Arrived</strong>
                                <small x-text="view.arrived ? 'Received at ' + view.destination + ' ' + fmt(view.arrived) : 'Not received at ' + view.destination + ' yet'"></small>
                            </li>
                        </ol>
                    </template>

                    <footer class="lgh-dialog__foot lgh-dialog__foot--sticky">
                        <span class="lgh-spacer"></span>
                        <button type="button" class="lgh-btn lgh-btn--soft" @click="closeView()">Close</button>
                        <template x-if="view && view.status === 'scheduled' && view.can_cancel">
                            <button type="button" class="lgh-btn lgh-btn--danger" @click="askCancel(view)">Cancel trip</button>
                        </template>
                        <template x-if="view && canDepart(view)">
                            <button type="button" class="lgh-btn lgh-btn--primary" @click="changeStatus(view, 'depart', $event)">Record departure</button>
                        </template>
                        <template x-if="view && canArrive(view)">
                            <button type="button" class="lgh-btn lgh-btn--primary" @click="changeStatus(view, 'arrive', $event)">Record arrival</button>
                        </template>
                    </footer>
                </div>
            </div>
        </div>

        {{-- Cancel asks for a reason. It releases the driver, the vehicle and the loaded parcels. --}}
        <div class="lgh-layer" x-show="confirm.open" x-cloak style="z-index: 80">
            <div class="lgh-backdrop" @click="confirm.open = false"></div>
            <div class="lgh-dialog lgh-dialog--sm" role="dialog" aria-modal="true" aria-labelledby="tripCancelTitle">
                <header class="lgh-dialog__head">
                    <div>
                        <h2 id="tripCancelTitle" x-text="'Cancel ' + (confirm.t ? confirm.t.id : 'trip') + '?'"></h2>
                        <p>The driver, the vehicle and the loaded parcels are released.</p>
                    </div>
                    <button type="button" class="lgh-close" @click="confirm.open = false" aria-label="Close"><svg class="lgh-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg></button>
                </header>
                <div class="lgh-dialog__body">
                    <div class="lgh-field">
                        <label for="tripReason">Reason <span aria-hidden="true">*</span></label>
                        <textarea id="tripReason" rows="3" maxlength="200" x-model="confirm.reason" @input="confirm.error = ''" placeholder="For example: the destination station is closed that day"
                                  :aria-invalid="!!confirm.error" aria-describedby="tripReasonErr" :class="{ 'has-error': confirm.error }"></textarea>
                        <p class="lgh-err" id="tripReasonErr" x-show="confirm.error" x-cloak x-text="confirm.error" role="alert"></p>
                    </div>
                    <div class="lgh-callout lgh-callout--rose" x-show="confirm.alert" x-cloak role="alert">
                        <x-logistics.icon name="alert" :size="18" />
                        <p x-text="confirm.alert"></p>
                    </div>
                    <footer class="lgh-dialog__foot">
                        <span class="lgh-spacer"></span>
                        <button type="button" class="lgh-btn lgh-btn--soft" @click="confirm.open = false">Keep trip</button>
                        <button type="button" class="lgh-btn lgh-btn--danger-solid" @click="submitCancel($event)">Cancel trip</button>
                    </footer>
                </div>
            </div>
        </div>
    </div>
</x-logistics.shell>
