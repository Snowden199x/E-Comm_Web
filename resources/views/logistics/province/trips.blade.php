{{-- Transport trips shown here come from the authenticated Province Hub controller. --}}
<x-logistics.shell title="Transport Trips" role="province">
    <div class="lg-page"
         x-data="{
            trips: @js($trips),
            now: @js($now),
            createUrl: @js($createUrl),
            cancelUrl: @js($cancelUrl),
            departUrl: @js($departUrl),
            arriveUrl: @js($arriveUrl),
            working: false,
            filter: 'all',
            q: '',
            view: null,
            confirm: { open: false, t: null, reason: '', error: '' },
            filters: {
                all: t => true,
                station_transfer: t => t.type === 'station_transfer',
                inter_province: t => t.type === 'inter_province',
                scheduled: t => t.status === 'scheduled',
                in_transit: t => t.status === 'in_transit',
                completed: t => t.status === 'completed',
                cancelled: t => t.status === 'cancelled'
            },
            typeLabels: { station_transfer: 'Station transfer', inter_province: 'Inter-province' },
            labels: { scheduled: 'Scheduled', in_transit: 'In transit', completed: 'Completed', cancelled: 'Cancelled' },
            tones: { scheduled: 'lg-pill--blue', in_transit: 'lg-pill--amber', completed: 'lg-pill--green', cancelled: 'lg-pill--gray' },
            rank(t) { return t.status === 'in_transit' ? 0 : (t.status === 'scheduled' ? 1 : 2); },
            get shown() {
                const s = this.q.trim().toLowerCase();
                return this.trips
                    .filter(t => this.filters[this.filter](t)
                        && (!s || (t.id + ' ' + t.origin + ' ' + t.destination + ' ' + t.driver + ' ' + t.vehicle).toLowerCase().includes(s)))
                    // Running and upcoming trips first (soonest departure first), then past trips (latest first).
                    .sort((a, b) => this.rank(a) - this.rank(b) || (this.rank(a) < 2 ? a.scheduled.localeCompare(b.scheduled) : b.scheduled.localeCompare(a.scheduled)));
            },
            count(k) { return this.trips.filter(this.filters[k]).length; },
            fmt(iso) { return iso ? new Date(iso).toLocaleString('en-PH', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' }) : ''; },
            openView(t) { this.view = t; },
            closeView() { this.view = null; },
            askCancel(t) { this.confirm = { open: true, t: t, reason: '', error: '' }; },
            async submitCancel() {
                const c = this.confirm, t = c.t;
                if (t.status !== 'scheduled') return;
                if (c.reason.trim().length < 5) { c.error = 'Give a short reason so the stations know why the trip was cancelled.'; return; }
                try {
                    const response = await fetch(this.cancelUrl.replace('__trip__', t.key), {
                        method: 'PATCH',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '' },
                        body: JSON.stringify({ reason: c.reason.trim() })
                    });
                    const payload = await response.json();
                    if (!response.ok) throw new Error(payload.message || 'Could not cancel trip.');
                    Object.assign(t, payload.trip);
                    c.open = false; this.view = null;
                    this.$dispatch('lg-toast', { type: 'success', message: payload.message });
                } catch (error) { c.error = error.message; }
            },
            async changeStatus(t, action) {
                if (this.working) return;
                this.working = true;
                try {
                    const url = (action === 'depart' ? this.departUrl : this.arriveUrl).replace('__trip__', t.key);
                    const response = await fetch(url, { method: 'PATCH', headers: {
                        'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || ''
                    }});
                    const payload = await response.json().catch(() => ({}));
                    if (!response.ok) throw new Error(payload.message || 'Could not update trip.');
                    Object.assign(t, payload.trip);
                    this.$dispatch('lg-toast', { type: 'success', message: payload.message });
                } catch (error) { this.$dispatch('lg-toast', { type: 'error', message: error.message }); }
                finally { this.working = false; }
            }
         }">
        <div class="lg-page-head">
            <div>
                <h1>Transport Trips</h1>
                <p>Station transfers and inter-province trips run by {{ $hub['name'] }}.</p>
            </div>
            <div class="lg-page-head__actions">
                <a href="{{ $createUrl }}" class="lg-btn"><x-logistics.icon name="plus" :size="18" /> Create trip</a>
            </div>
        </div>

        <nav class="lg-tabs" aria-label="Trip filters">
            @foreach (['all' => 'All', 'station_transfer' => 'Station transfers', 'inter_province' => 'Inter-province', 'scheduled' => 'Scheduled', 'in_transit' => 'In transit', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $k => $label)
                <button type="button" class="lg-tab" :aria-pressed="filter === '{{ $k }}'" @click="filter = '{{ $k }}'">
                    {{ $label }} <span class="lg-tab__count" x-text="count('{{ $k }}')"></span>
                </button>
            @endforeach
        </nav>

        <div class="lg-toolbar">
            <div class="lg-search">
                <x-logistics.icon name="search" :size="18" />
                <input type="search" class="lg-input" x-model="q" placeholder="Search trip, facility, driver or vehicle" aria-label="Search trips">
            </div>
            <span class="lg-toolbar__meta" aria-live="polite" x-text="shown.length + (shown.length === 1 ? ' trip' : ' trips')"></span>
        </div>

        <section class="lg-card" aria-label="Transport trips">
            <div class="lg-table-wrap" x-show="shown.length">
                <table class="lg-table lg-table--stack lg-table--mid">
                    <thead>
                        <tr>
                            <th>Trip</th><th>Type</th><th>Route</th><th>Driver</th><th>Vehicle</th>
                            <th class="is-num">Parcels</th><th>Departure</th><th>Status</th><th class="is-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="t in shown" :key="t.id">
                            <tr>
                                <td data-label="Trip"><div class="lg-cell-title" x-text="t.id"></div></td>
                                <td data-label="Type" x-text="typeLabels[t.type]"></td>
                                <td data-label="Route"><div x-text="t.origin"></div><div class="lg-cell-sub" x-text="'to ' + t.destination"></div></td>
                                <td data-label="Driver" x-text="t.driver"></td>
                                <td data-label="Vehicle" x-text="t.vehicle"></td>
                                <td data-label="Parcels" class="is-num" x-text="t.parcels"></td>
                                <td data-label="Departure">
                                    <div x-text="fmt(t.departed || t.scheduled)"></div>
                                    <div class="lg-cell-sub" x-text="t.departed ? 'Departed' : 'Scheduled'"></div>
                                </td>
                                <td data-label="Status"><span class="lg-pill" :class="tones[t.status]" x-text="labels[t.status]"></span></td>
                                <td data-label="Actions" class="is-actions">
                                    <div class="lg-actions">
                                        <button type="button" class="lg-btn lg-btn--outline lg-btn--sm" @click="openView(t)" :aria-label="'View ' + t.id"><x-logistics.icon name="eye" :size="16" /> View</button>
                        <button type="button" class="lg-btn lg-btn--danger lg-btn--sm" x-show="t.status === 'scheduled' && t.can_cancel" @click="askCancel(t)" :aria-label="'Cancel ' + t.id">Cancel</button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <x-logistics.empty x-show="!trips.length" x-cloak icon="calendar" title="No trips yet" text="Create a trip to move consolidated parcels between a Station Hub and your hub, or to another Province Hub.">
                <a href="{{ $createUrl }}" class="lg-btn lg-btn--sm">Create trip</a>
            </x-logistics.empty>
            <x-logistics.empty x-show="trips.length && !shown.length" x-cloak icon="search" title="No trips match" text="Check the spelling, or pick another filter.">
                <button type="button" class="lg-btn lg-btn--outline lg-btn--sm" @click="q = ''; filter = 'all'">Clear filters</button>
            </x-logistics.empty>
        </section>

        {{-- Trip details with its progress. A scheduled trip can be cancelled; one already in transit cannot. --}}
        <div class="lg-overlay" x-show="view" x-cloak @click.self="closeView()" @keydown.escape.window="closeView()">
            <div class="lg-modal" style="width:min(100%,560px)" role="dialog" aria-modal="true" aria-labelledby="tripTitle" x-show="view">
                <div class="lg-modal__head">
                    <div>
                        <h3 id="tripTitle" x-text="view ? view.id + ' · ' + typeLabels[view.type] : ''"></h3>
                        <p x-show="view"><span class="lg-pill" :class="view ? tones[view.status] : ''" x-text="view ? labels[view.status] : ''"></span></p>
                    </div>
                    <button type="button" class="lg-toast__close" @click="closeView()" aria-label="Close"><x-logistics.icon name="x" :size="18" /></button>
                </div>
                <div class="lg-modal__body lg-modal__body--grid" x-show="view">
                    <template x-if="view && view.status === 'in_transit'">
                        <div class="lg-note">
                            <x-logistics.icon name="info" :size="18" />
                            <span>Scan every parcel at the destination, then record trip arrival.</span>
                        </div>
                    </template>
                    <template x-if="view && view.status === 'cancelled'">
                        <div class="lg-note lg-note--warn">
                            <x-logistics.icon name="alert" :size="18" />
                            <span><strong>Cancelled.</strong> <span x-text="view.note"></span></span>
                        </div>
                    </template>
                    <dl class="lg-facts" x-show="view">
                        <div><dt>From</dt><dd x-text="view && view.origin"></dd></div>
                        <div><dt>To</dt><dd x-text="view && view.destination"></dd></div>
                        <div><dt>Driver</dt><dd x-text="view && view.driver"></dd></div>
                        <div><dt>Vehicle</dt><dd x-text="view && view.vehicle"></dd></div>
                        <div><dt>Parcels loaded</dt><dd x-text="view && view.parcels"></dd></div>
                        <div><dt>Scheduled departure</dt><dd x-text="view && fmt(view.scheduled)"></dd></div>
                    </dl>
                    <div class="is-wide" x-show="view && view.manifest && view.manifest.length">
                        <strong>Manifest</strong>
                        <ul class="lg-cell-sub" style="margin-top:8px;max-height:120px;overflow:auto">
                            <template x-for="code in (view?.manifest || [])" :key="code"><li x-text="code"></li></template>
                        </ul>
                    </div>
                    <template x-if="view && view.status !== 'cancelled'">
                        <ol class="lg-journey" style="padding:0">
                            <li :class="view.status === 'scheduled' ? 'is-current' : ''">
                                <div class="lg-journey__what">Scheduled</div>
                                <div class="lg-journey__meta" x-text="'Departure planned ' + fmt(view.scheduled)"></div>
                            </li>
                            <li :class="view.status === 'in_transit' ? 'is-current' : ''">
                                <div class="lg-journey__what">Departed</div>
                                <div class="lg-journey__meta" x-text="view.departed ? 'Departure recorded ' + fmt(view.departed) : 'Scan every manifest parcel for departure first'"></div>
                            </li>
                            <li :class="view.status === 'completed' ? 'is-current' : ''">
                                <div class="lg-journey__what">Arrived</div>
                                <div class="lg-journey__meta" x-text="view.arrived ? 'Received at ' + view.destination + ' ' + fmt(view.arrived) : 'Not yet received at ' + view.destination"></div>
                            </li>
                        </ol>
                    </template>
                </div>
                <div class="lg-modal__foot" x-show="view">
                    <button type="button" class="lg-btn lg-btn--outline" @click="closeView()">Close</button>
                    <template x-if="view && view.status === 'scheduled'">
                        <button type="button" class="lg-btn lg-btn--danger" x-show="view.can_cancel" @click="askCancel(view)">Cancel trip</button>
                    </template>
                    <button type="button" class="lg-btn" x-show="view && view.status === 'scheduled' && view.can_depart" :disabled="working" @click="changeStatus(view, 'depart')">Record departure</button>
                    <button type="button" class="lg-btn" x-show="view && view.status === 'in_transit' && view.can_arrive" :disabled="working" @click="changeStatus(view, 'arrive')">Record arrival</button>
                </div>
            </div>
        </div>

        {{-- Cancel asks for a reason. It releases the driver, the vehicle and the loaded parcels. --}}
        <div class="lg-overlay" x-show="confirm.open" x-cloak @click.self="confirm.open = false" @keydown.escape.window="confirm.open = false">
            <div class="lg-modal" role="dialog" aria-modal="true" aria-labelledby="tripCancelTitle">
                <div class="lg-modal__head">
                    <div>
                        <h3 id="tripCancelTitle" x-text="'Cancel ' + (confirm.t ? confirm.t.id : 'trip')"></h3>
                        <p>The driver, the vehicle and the loaded parcels are released.</p>
                    </div>
                    <button type="button" class="lg-toast__close" @click="confirm.open = false" aria-label="Close"><x-logistics.icon name="x" :size="18" /></button>
                </div>
                <div class="lg-modal__body lg-modal__body--grid">
                    <div class="lg-field">
                        <label for="tripReason">Reason <span class="lg-req" aria-hidden="true">*</span></label>
                        <textarea id="tripReason" class="lg-textarea" rows="3" maxlength="200" x-model="confirm.reason" placeholder="For example: the destination station is closed that day" :aria-invalid="!!confirm.error" aria-describedby="tripReasonErr"></textarea>
                        <p class="lg-error" id="tripReasonErr" x-show="confirm.error" x-text="confirm.error" role="alert"></p>
                    </div>
                </div>
                <div class="lg-modal__foot">
                    <button type="button" class="lg-btn lg-btn--outline" @click="confirm.open = false">Keep trip</button>
                    <button type="button" class="lg-btn lg-btn--danger-solid" @click="submitCancel()">Cancel trip</button>
                </div>
            </div>
        </div>
    </div>
</x-logistics.shell>
