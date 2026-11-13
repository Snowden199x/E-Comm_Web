{{--
    Province Hub > Transport Drivers. The Province Hub reviews driver applications submitted through the Transport Driver API,
    and activates or deactivates approved drivers. Fed by PortalController::provinceDrivers (drivers from driverCard, and the JSON urls).
    The page state lives in one Alpine scope.
    Look: the seller pastel lilac system, the same .lgh layer as Province Hub > Station Hubs. Styles: hubs-theme.css plus drivers-theme.css
    (loaded last; dialogs sit in .lgh-layer and carry their own copy of the tokens).
    Status colors: Available = mint, On trip = sky, Off duty or Inactive = lilac, Needs review or waiting for a Station Hub = butter,
    Rejected or expired licence = rose. Licence due within 60 days = butter.
    Search runs as you type; status chips show counts; one click on a row opens the driver. Each row has a visible button
    (Review application when this hub must decide, otherwise View details). Opens on Applications when any need review.
    Loading: shell skeleton on open; spinner on the button that was pressed (admin button-loading.js).
    Empty: no drivers at all, nothing in a status, and no drivers match the search. Errors: message under the reason field, top banner for the rest.
--}}
@push('lg-head')
    @vite(['resources/css/logistics/hubs-theme.css', 'resources/css/logistics/drivers-theme.css'])
@endpush

<x-logistics.shell title="Transport Drivers" role="province">
    <div class="lgh"
         x-data="{
            drivers: @js($drivers),
            today: @js($today),
            reviewUrl: @js($reviewUrl),
            statusUrl: @js($statusUrl),
            documentUrl: @js($documentUrl),
            q: '',
            tab: 'active',
            view: null,
            working: false,
            confirm: { open: false, kind: '', d: null, reason: '', error: '' },
            tabs: {
                active: d => d.status === 'available' || d.status === 'off_duty',
                applications: d => d.status === 'pending',
                on_trip: d => d.status === 'on_trip',
                inactive: d => d.status === 'inactive'
            },
            labels: { available: 'Available', on_trip: 'On trip', off_duty: 'Off duty', inactive: 'Inactive', pending: 'Pending' },
            tones: { available: 'lgh-pill--mint', on_trip: 'lgh-pill--sky', off_duty: 'lgh-pill--lilac', inactive: 'lgh-pill--lilac', pending: 'lgh-pill--butter' },
            init() {
                // Open on Applications when this hub has any to review.
                if (this.drivers.some(d => this.canReview(d))) this.tab = 'applications';
                // Any popup open: the page behind it does not scroll.
                this.$watch(() => this.view !== null || this.confirm.open, (isOpen) => {
                    const main = document.querySelector('.vd-main');
                    if (main) main.style.overflow = isOpen ? 'hidden' : '';
                });
            },
            busy(button, on) { if (button && window.vdButtonLoading) window.vdButtonLoading(button, on); },
            headers() { return { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '' }; },
            // Only the Province Hub reviews province and both applications.
            // A station-only driver is approved by its Station Hub.
            canReview(d) { return d.status === 'pending' && d.reviewed_by_kind === 'province_hub'; },
            waitingOnStation(d) { return d.status === 'pending' && d.reviewed_by_kind === 'station_hub'; },
            statusLabel(d) {
                if (this.waitingOnStation(d)) return 'Waiting for Station Hub';
                if (this.canReview(d)) return 'Needs review';
                if (d.rejected) return 'Rejected';
                return this.labels[d.status] || d.status;
            },
            statusTone(d) {
                if (this.waitingOnStation(d) || this.canReview(d)) return 'lgh-pill--butter';
                if (d.rejected) return 'lgh-pill--rose';
                return this.tones[d.status] || 'lgh-pill--lilac';
            },
            doc(d, kind) { return this.documentUrl.replace('__driver__', d.id).replace('__document__', kind); },
            get searching() { return this.q.trim() !== ''; },
            get shown() {
                const s = this.q.trim().toLowerCase();
                return this.drivers
                    .filter(d => this.tabs[this.tab](d)
                        && (!s || (d.name + ' ' + d.license + ' ' + d.phone + ' ' + d.email + ' ' + (d.trip || '') + ' ' + (d.apply_label || '') + ' ' + (d.station || '')).toLowerCase().includes(s)))
                    .sort((a, b) => a.name.localeCompare(b.name));
            },
            count(k) { return this.drivers.filter(this.tabs[k]).length; },
            clearSearch() { this.q = ''; },
            fmt(iso) { return iso ? new Date(iso + 'T00:00:00').toLocaleDateString('en-PH', { day: 'numeric', month: 'short', year: 'numeric' }) : 'Not set'; },
            // Licence check from the spec: it must not be expired, and it is re-checked before every trip is assigned.
            daysLeft(d) { return d.expiry ? Math.round((new Date(d.expiry + 'T00:00:00') - new Date(this.today + 'T00:00:00')) / 86400000) : -1; },
            expired(d) { return this.daysLeft(d) < 0; },
            expiryNote(d) {
                if (!d.expiry) return { tone: 'lgh-pill--rose', text: 'Not set' };
                const n = this.daysLeft(d);
                if (n < 0) return { tone: 'lgh-pill--rose', text: 'Expired' };
                if (n <= 60) return { tone: 'lgh-pill--butter', text: 'Expires in ' + n + (n === 1 ? ' day' : ' days') };
                return null;
            },
            go(d, event) { if (!event.target.closest('a, button')) this.openView(d); },
            openView(d) { this.view = d; },
            closeView() { this.view = null; },
            closeTop() { if (this.confirm.open) this.confirm.open = false; else if (this.view) this.view = null; },
            openDoc(url) { if (url) window.open(url, '_blank', 'noopener,noreferrer'); },
            failure(error, fallback) { return error instanceof TypeError ? 'You are offline. Check your connection and try again.' : (error.message || fallback); },
            async request(url, method, body) {
                const response = await fetch(url, { method, headers: this.headers(), body: JSON.stringify(body) });
                const payload = await response.json().catch(() => ({}));
                if (!response.ok) {
                    const fallback = response.status === 419 ? 'Your session expired. Reload the page and try again.'
                        : (response.status === 403 ? 'This account can’t make that change. Sign in with the Province Hub account.'
                        : (response.status >= 500 ? 'Something went wrong on our side. Try again in a moment.' : 'Could not update the driver. Reload the page and try again.'));
                    throw new Error(payload.message || Object.values(payload.errors || {})[0]?.[0] || fallback);
                }
                return payload;
            },
            async approve(d, event) {
                if (this.expired(d)) { this.$dispatch('lg-toast', { type: 'error', message: d.name + '’s licence is expired, so it can’t be approved. Ask for a renewed licence.' }); return; }
                if (this.working) return;
                const button = event.currentTarget;
                this.working = true; this.busy(button, true);
                try {
                    const payload = await this.request(this.reviewUrl.replace('__driver__', d.id), 'POST', { decision: 'approve' });
                    Object.assign(d, payload.driver); this.view = null; this.tab = 'active';
                    this.$dispatch('lg-toast', { type: 'success', message: payload.message || (d.name + ' is approved.') });
                } catch (error) { this.$dispatch('lg-toast', { type: 'error', message: this.failure(error, 'Could not approve the driver. Try again.') }); }
                finally { this.working = false; this.busy(button, false); }
            },
            async activate(d, event) {
                if (this.expired(d)) { this.$dispatch('lg-toast', { type: 'error', message: d.name + '’s licence is expired, so they can’t be activated. Ask for a renewed licence.' }); return; }
                if (this.working) return;
                const button = event.currentTarget;
                this.working = true; this.busy(button, true);
                try {
                    const payload = await this.request(this.statusUrl.replace('__driver__', d.id), 'PATCH', { status: 'available' });
                    Object.assign(d, payload.driver); this.view = null; this.tab = 'active';
                    this.$dispatch('lg-toast', { type: 'success', message: payload.message || (d.name + ' is active again.') });
                } catch (error) { this.$dispatch('lg-toast', { type: 'error', message: this.failure(error, 'Could not activate the driver. Try again.') }); }
                finally { this.working = false; this.busy(button, false); }
            },
            askReject(d) { this.confirm = { open: true, kind: 'reject', d: d, reason: '', error: '' }; this.$nextTick(() => document.getElementById('drvReason')?.focus()); },
            askDeactivate(d) { this.confirm = { open: true, kind: 'deactivate', d: d, reason: '', error: '' }; },
            async submitConfirm(event) {
                const c = this.confirm, d = c.d;
                if (this.working) return;
                if (c.kind === 'reject' && c.reason.trim().length < 10) {
                    c.error = 'Reason is too short. Write at least 10 characters so the applicant knows what to fix.';
                    document.getElementById('drvReason')?.focus();
                    return;
                }
                if (d.status === 'on_trip') { c.error = d.name + ' is on a trip. Deactivate them after the trip ends.'; return; }
                const button = event.currentTarget;
                this.working = true; this.busy(button, true); c.error = '';
                try {
                    const payload = c.kind === 'reject'
                        ? await this.request(this.reviewUrl.replace('__driver__', d.id), 'POST', { decision: 'reject', reason: c.reason.trim() })
                        : await this.request(this.statusUrl.replace('__driver__', d.id), 'PATCH', { status: 'inactive' });
                    Object.assign(d, payload.driver);
                    if (c.kind === 'reject') this.tab = 'inactive';
                    c.open = false; this.view = null;
                    this.$dispatch('lg-toast', { type: 'success', message: payload.message });
                } catch (error) { c.error = this.failure(error, 'Could not save the change. Try again.'); }
                finally { this.working = false; this.busy(button, false); }
            }
         }"
         @keydown.escape.window="closeTop()">

        <x-logistics.flash />

        <header class="lgh-head">
            <div>
                <h1>Transport drivers</h1>
                <p>Company drivers of {{ $hub['name'] }}. They move parcels between stations and hubs on trips you assign.</p>
            </div>
            <a href="{{ route('lgp.province.trips.create') }}" class="lgh-btn lgh-btn--primary">
                <x-logistics.icon name="plus" :size="16" /> Create trip
            </a>
        </header>

        @if (count($drivers))
            {{-- Status chips with counts, then search --}}
            <div class="lgh-chips" role="group" aria-label="Filter drivers by status">
                @foreach (['active' => 'Active', 'applications' => 'Applications', 'on_trip' => 'On trip', 'inactive' => 'Inactive'] as $key => $text)
                    <button type="button" class="lgh-chip" :class="{ 'is-active': tab === '{{ $key }}' }" :aria-pressed="tab === '{{ $key }}'" @click="tab = '{{ $key }}'">
                        {{ $text }} <span class="lgh-chip__count" x-text="count('{{ $key }}')"></span>
                    </button>
                @endforeach
                <button type="button" class="lgh-chip lgh-chip--clear" x-show="searching" x-cloak @click="clearSearch()">
                    <x-logistics.icon name="x" :size="14" /> Clear filters
                </button>
            </div>

            <div class="lgh-toolbar">
                <label class="lgh-search">
                    <span class="lgh-sr">Search drivers</span>
                    <x-logistics.icon name="search" :size="18" />
                    <input type="search" x-model="q" @keydown.escape="q = ''" @keydown.enter.prevent autocomplete="off" maxlength="100" placeholder="Search name, licence, phone or trip">
                </label>
                <span class="lgh-meta" aria-live="polite" x-text="shown.length + (shown.length === 1 ? ' driver' : ' drivers')"></span>
            </div>

            <section class="lgh-card lgh-table-card" aria-label="Transport drivers">
                <div class="lgh-table-wrap" x-show="shown.length" x-cloak>
                    <table class="lgh-table lgh-table--drivers">
                        <thead>
                            <tr>
                                <th scope="col">Driver</th>
                                <th scope="col">Applied to</th>
                                <th scope="col">Licence</th>
                                <th scope="col">Expires</th>
                                <th scope="col">Status</th>
                                <th scope="col" class="is-num" x-text="tab === 'applications' ? 'Applied' : 'Trips done'"></th>
                                <th scope="col"><span class="lgh-sr">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="d in shown" :key="d.id">
                                <tr class="lgh-row" @click="go(d, $event)">
                                    <td>
                                        <button type="button" class="lgh-name lgh-name--btn" @click="openView(d)" x-text="d.name"></button>
                                        <small x-text="d.phone || 'No phone'"></small>
                                    </td>
                                    <td data-label="Applied to">
                                        <span x-text="d.apply_label"></span>
                                        <small x-text="d.station ? ('Station: ' + d.station) : 'No Station Hub matched'"></small>
                                    </td>
                                    <td data-label="Licence">
                                        <span x-text="d.license"></span>
                                        <small x-text="d.category"></small>
                                    </td>
                                    <td data-label="Expires">
                                        <span x-text="fmt(d.expiry)"></span>
                                        <template x-if="expiryNote(d)"><small><span class="lgh-pill" :class="expiryNote(d).tone" x-text="expiryNote(d).text"></span></small></template>
                                    </td>
                                    <td data-label="Status">
                                        <span class="lgh-pill" :class="statusTone(d)" x-text="statusLabel(d)"></span>
                                        <template x-if="d.trip"><small x-text="'On ' + d.trip"></small></template>
                                    </td>
                                    <td class="is-num" :data-label="tab === 'applications' ? 'Applied' : 'Trips done'" x-text="tab === 'applications' ? fmt(d.applied) : d.done"></td>
                                    <td class="lgh-actions">
                                        <div class="lgh-actions__row">
                                            <button type="button" class="lgh-btn lgh-btn--small" :class="canReview(d) ? 'lgh-btn--primary' : 'lgh-btn--soft'" @click="openView(d)"
                                                    :aria-label="(canReview(d) ? 'Review application of ' : 'View details for ') + d.name">
                                                <span x-text="canReview(d) ? 'Review application' : 'View details'"></span>
                                                <x-logistics.icon name="arrow-right" :size="14" class="lgh-nudge" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                {{-- Nothing matches the search --}}
                <div class="lgh-empty" x-show="!shown.length && searching" x-cloak role="status">
                    <span class="lgh-empty__icon lgh-tone-lilac"><x-logistics.icon name="search" :size="26" /></span>
                    <strong>No drivers match your search</strong>
                    <p>Check the spelling, or search by licence number, phone or trip. You are searching in <span x-text="{ active: 'active drivers', applications: 'applications', on_trip: 'drivers on a trip', inactive: 'inactive drivers' }[tab]"></span> only.</p>
                    <button type="button" class="lgh-btn lgh-btn--primary" @click="clearSearch()">Clear filters</button>
                </div>

                {{-- Nothing in this status --}}
                <div class="lgh-empty" x-show="!shown.length && !searching && tab === 'applications'" x-cloak role="status">
                    <span class="lgh-empty__icon lgh-tone-lilac"><x-logistics.icon name="users" :size="26" /></span>
                    <strong>No applications waiting</strong>
                    <p>Drivers apply in the Driver app and pick your Province Hub. New applications show up here.</p>
                    <button type="button" class="lgh-btn lgh-btn--soft" @click="tab = 'active'">View active drivers</button>
                </div>
                <div class="lgh-empty" x-show="!shown.length && !searching && tab === 'active'" x-cloak role="status">
                    <span class="lgh-empty__icon lgh-tone-lilac"><x-logistics.icon name="users" :size="26" /></span>
                    <strong>No active drivers</strong>
                    <p>Approve an application to add a driver who can take trips.</p>
                    <button type="button" class="lgh-btn lgh-btn--soft" @click="tab = 'applications'">Open applications</button>
                </div>
                <div class="lgh-empty" x-show="!shown.length && !searching && tab === 'on_trip'" x-cloak role="status">
                    <span class="lgh-empty__icon lgh-tone-sky"><x-logistics.icon name="truck" :size="26" /></span>
                    <strong>No drivers on a trip</strong>
                    <p>A driver shows here while a trip is scheduled or in transit.</p>
                    <a href="{{ route('lgp.province.trips.create') }}" class="lgh-btn lgh-btn--soft"><x-logistics.icon name="plus" :size="16" /> Create trip</a>
                </div>
                <div class="lgh-empty" x-show="!shown.length && !searching && tab === 'inactive'" x-cloak role="status">
                    <span class="lgh-empty__icon lgh-tone-lilac"><x-logistics.icon name="users" :size="26" /></span>
                    <strong>No inactive drivers</strong>
                    <p>Deactivated and rejected drivers show here.</p>
                    <button type="button" class="lgh-btn lgh-btn--soft" @click="tab = 'active'">View active drivers</button>
                </div>
            </section>
        @else
            {{-- No drivers at all --}}
            <section class="lgh-card" aria-label="Transport drivers">
                <div class="lgh-empty" role="status">
                    <span class="lgh-empty__icon lgh-tone-lilac"><x-logistics.icon name="users" :size="26" /></span>
                    <strong>No drivers yet</strong>
                    <p>Drivers apply in the Driver app and pick your Province Hub. Their applications show up here for review.</p>
                    <a href="{{ route('lgp.hub.province') }}" class="lgh-btn lgh-btn--soft">Open dashboard</a>
                </div>
            </section>
        @endif

        {{-- Driver details. Application review (approve or reject) happens here. --}}
        <div class="lgh-layer" x-show="view" x-cloak>
            <div class="lgh-backdrop" @click="closeView()"></div>
            <div class="lgh-dialog lgh-dialog--wide" role="dialog" aria-modal="true" aria-labelledby="drvTitle">
                <header class="lgh-dialog__head">
                    <div>
                        <h2 id="drvTitle" x-text="view ? view.name : ''"></h2>
                        <p><span class="lgh-pill" :class="view ? statusTone(view) : ''" x-text="view ? statusLabel(view) : ''"></span></p>
                    </div>
                    <button type="button" class="lgh-close" @click="closeView()" aria-label="Close"><svg class="lgh-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg></button>
                </header>
                <div class="lgh-dialog__body">
                    <template x-if="view && waitingOnStation(view)">
                        <div class="lgh-callout lgh-callout--butter">
                            <x-logistics.icon name="info" :size="18" />
                            <p>This driver applied to <strong x-text="view.apply_label"></strong>. Its Station Hub decides, so you can only view it here.</p>
                        </div>
                    </template>
                    <template x-if="view && view.rejected">
                        <div class="lgh-callout lgh-callout--rose">
                            <x-logistics.icon name="alert" :size="18" />
                            <p>This application was rejected. The applicant sees your reason in the Driver app.</p>
                        </div>
                    </template>
                    <template x-if="view && !view.rejected && expired(view)">
                        <div class="lgh-callout lgh-callout--rose">
                            <x-logistics.icon name="alert" :size="18" />
                            <p>The licence expired on <strong x-text="fmt(view.expiry)"></strong>. An expired licence blocks approval, activation and new trips. Ask the driver for a renewed licence.</p>
                        </div>
                    </template>
                    <template x-if="view && view.status === 'on_trip'">
                        <div class="lgh-callout lgh-callout--sky">
                            <x-logistics.icon name="truck" :size="18" />
                            <p>On trip <strong x-text="view.trip"></strong>. You can deactivate this driver after the trip ends.</p>
                        </div>
                    </template>

                    <dl class="lgh-facts" x-show="view">
                        <div><dt>Applied to</dt><dd x-text="view && view.apply_label"></dd></div>
                        <div><dt>Station Hub</dt><dd x-text="view && (view.station || 'Not matched') + (view.station_area ? ' · ' + view.station_area : '')"></dd></div>
                        <div><dt>Phone</dt><dd x-text="view && (view.phone || 'Not set')"></dd></div>
                        <div><dt>Email</dt><dd x-text="view && (view.email || 'Not set')"></dd></div>
                        <div><dt>Birthday</dt><dd x-text="view && (view.birthday ? fmt(view.birthday) : '—')"></dd></div>
                        <div><dt>Sex</dt><dd x-text="view && view.sex"></dd></div>
                        <div class="is-wide"><dt>Home address</dt><dd x-text="view && view.address"></dd></div>
                        <div><dt>Licence number</dt><dd x-text="view && view.license"></dd></div>
                        <div><dt>Category</dt><dd x-text="view && view.category"></dd></div>
                        <div><dt>Licence expires</dt><dd x-text="view && fmt(view.expiry)"></dd></div>
                        <div><dt>Completed trips</dt><dd x-text="view && view.done"></dd></div>
                        <div class="is-wide"><dt>Reviewed by</dt><dd x-text="view ? (view.reviewed_by ? view.reviewed_by + (view.reviewed_at ? ' · ' + fmt(view.reviewed_at) : '') : 'Not reviewed yet') : ''"></dd></div>
                    </dl>

                    <div class="lgh-docs">
                        <h3>Documents open in a new tab</h3>
                        <div class="lgh-docs__row">
                            <button type="button" class="lgh-btn lgh-btn--soft lgh-btn--small" @click="openDoc(view && doc(view, 'license'))"><x-logistics.icon name="file" :size="16" /> View driver’s licence</button>
                            <template x-if="view && view.has_valid_id">
                                <button type="button" class="lgh-btn lgh-btn--soft lgh-btn--small" @click="openDoc(doc(view, 'valid-id'))"><x-logistics.icon name="file" :size="16" /> View valid ID</button>
                            </template>
                        </div>
                    </div>

                    <footer class="lgh-dialog__foot lgh-dialog__foot--sticky">
                        <span class="lgh-spacer"></span>
                        <button type="button" class="lgh-btn lgh-btn--soft" @click="closeView()">Close</button>
                        <template x-if="view && canReview(view)">
                            <span style="display: contents">
                                <button type="button" class="lgh-btn lgh-btn--danger" @click="askReject(view)">Reject application</button>
                                <button type="button" class="lgh-btn lgh-btn--primary" :disabled="expired(view)" @click="approve(view, $event)">Approve driver</button>
                            </span>
                        </template>
                        <template x-if="view && !waitingOnStation(view) && (view.status === 'available' || view.status === 'off_duty') && !view.rejected">
                            <button type="button" class="lgh-btn lgh-btn--danger" @click="askDeactivate(view)">Deactivate driver</button>
                        </template>
                        <template x-if="view && !waitingOnStation(view) && view.status === 'inactive' && view.reviewed_by && !view.rejected">
                            <button type="button" class="lgh-btn lgh-btn--primary" :disabled="expired(view)" @click="activate(view, $event)">Activate driver</button>
                        </template>
                    </footer>
                </div>
            </div>
        </div>

        {{-- Reject needs a reason; deactivate is a plain confirm. --}}
        <div class="lgh-layer" x-show="confirm.open" x-cloak style="z-index: 80">
            <div class="lgh-backdrop" @click="confirm.open = false"></div>
            <div class="lgh-dialog lgh-dialog--sm" role="dialog" aria-modal="true" aria-labelledby="drvConfirmTitle">
                <header class="lgh-dialog__head">
                    <div>
                        <h2 id="drvConfirmTitle" x-text="(confirm.kind === 'reject' ? 'Reject application of ' : 'Deactivate ') + (confirm.d ? confirm.d.name : '') + '?'"></h2>
                        <p x-text="confirm.kind === 'reject' ? 'The applicant is told their application was not approved, and sees your reason.' : 'They won’t be assigned to new trips until you activate them again.'"></p>
                    </div>
                    <button type="button" class="lgh-close" @click="confirm.open = false" aria-label="Close"><svg class="lgh-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg></button>
                </header>
                <div class="lgh-dialog__body">
                    <div class="lgh-field" x-show="confirm.kind === 'reject'">
                        <label for="drvReason">Reason <span aria-hidden="true">*</span></label>
                        <textarea id="drvReason" rows="3" maxlength="300" x-model="confirm.reason" @input="confirm.error = ''" placeholder="For example: the licence photo is blurry. Apply again with a clear photo."
                                  :aria-invalid="confirm.kind === 'reject' && !!confirm.error" aria-describedby="drvReasonErr" :class="{ 'has-error': confirm.kind === 'reject' && confirm.error }"></textarea>
                        <p class="lgh-err" id="drvReasonErr" x-show="confirm.kind === 'reject' && confirm.error" x-cloak x-text="confirm.error" role="alert"></p>
                    </div>
                    <div class="lgh-callout lgh-callout--rose" x-show="confirm.kind === 'deactivate' && confirm.error" x-cloak role="alert">
                        <x-logistics.icon name="alert" :size="18" />
                        <p x-text="confirm.error"></p>
                    </div>
                    <footer class="lgh-dialog__foot">
                        <span class="lgh-spacer"></span>
                        <button type="button" class="lgh-btn lgh-btn--soft" @click="confirm.open = false">Cancel</button>
                        <button type="button" class="lgh-btn lgh-btn--danger-solid" @click="submitConfirm($event)" x-text="confirm.kind === 'reject' ? 'Reject application' : 'Deactivate driver'"></button>
                    </footer>
                </div>
            </div>
        </div>
    </div>
</x-logistics.shell>
