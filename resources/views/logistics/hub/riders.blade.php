{{--
    Station Hub > Riders. The Station Hub reviews rider applications from its area and activates or deactivates its approved riders.
    Fed by PortalController::stationRiders (riders, and the JSON urls). The page state lives in one Alpine scope.
    Look: the seller pastel lilac system, the same .lgh layer as the Province Hub pages. Styles: hubs-theme.css, drivers-theme.css
    (shared pills and callouts) and riders-theme.css, loaded last. Dialogs sit in .lgh-layer and carry their own copy of the tokens.
    Status colors: Active = mint, Applications = butter, Rejected = rose, Inactive = lilac.
    Search runs as you type; status chips show counts; every row has a visible action. Opens on Applications when any are waiting
    (or on the tab in ?tab=). Loading: shell skeleton on open; spinner on the button that was pressed (admin button-loading.js).
    Empty: no riders at all, nothing in a status, and no riders match the search. Errors: a message under the reason field, in the dialog, or the top banner.
--}}
@push('lg-head')
    @vite(['resources/css/logistics/hubs-theme.css', 'resources/css/logistics/drivers-theme.css', 'resources/css/logistics/riders-theme.css'])
@endpush

<x-logistics.shell title="Riders" :role="$role">
    <div class="lgh"
         x-data="{
            riders: @js($liveRiders),
            reviewUrl: @js($reviewUrl),
            statusUrl: @js($statusUrl),
            q: '',
            tab: 'active',
            working: false,
            confirm: { open: false, kind: '', r: null, reason: '', error: '' },
            labels: { active: 'Active', applications: 'Applications', rejected: 'Rejected', inactive: 'Inactive' },
            emptyTab: {
                active: ['No active riders', 'Approve an application to add a rider who can take tasks.', 'Open applications', 'applications'],
                applications: ['No applications waiting', 'Riders apply in the Rider app and pick your Station Hub. New applications show up here.', 'View active riders', 'active'],
                rejected: ['No rejected applications', 'Applications you reject are kept here.', 'Open applications', 'applications'],
                inactive: ['No inactive riders', 'Riders you deactivate show here. You can activate them again.', 'View active riders', 'active']
            },
            init() {
                const wanted = new URLSearchParams(window.location.search).get('tab');
                this.tab = this.labels[wanted] ? wanted : (this.count('applications') ? 'applications' : 'active');
                // Any popup open: the page behind it does not scroll.
                this.$watch(() => this.confirm.open, (isOpen) => {
                    const main = document.querySelector('.vd-main');
                    if (main) main.style.overflow = isOpen ? 'hidden' : '';
                });
            },
            busy(button, on) { if (button && window.vdButtonLoading) window.vdButtonLoading(button, on); },
            headers() { return { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '' }; },
            get searching() { return this.q.trim() !== ''; },
            get shown() {
                const s = this.q.trim().toLowerCase();
                return this.riders
                    .filter(r => r.status === this.tab && (!s || (r.name + ' ' + r.email + ' ' + r.vehicle + ' ' + r.area).toLowerCase().includes(s)))
                    .sort((a, b) => a.name.localeCompare(b.name));
            },
            count(s) { return this.riders.filter(r => r.status === s).length; },
            clearSearch() { this.q = ''; },
            failure(error, fallback) { return error instanceof TypeError ? 'You are offline. Check your connection and try again.' : (error.message || fallback); },
            async request(url, method, body) {
                const response = await fetch(url, { method, headers: this.headers(), body: JSON.stringify(body) });
                const payload = await response.json().catch(() => ({}));
                if (!response.ok) {
                    const fallback = response.status === 419 ? 'Your session expired. Reload the page and try again.'
                        : (response.status === 403 ? 'This account can’t make that change. Sign in with the Station Hub account.'
                        : (response.status >= 500 ? 'Something went wrong on our side. Try again in a moment.' : 'Could not update the rider. Reload the page and try again.'));
                    throw new Error(payload.message || Object.values(payload.errors || {})[0]?.[0] || fallback);
                }
                return payload;
            },
            async approve(r, event) {
                if (this.working) return;
                const button = event.currentTarget;
                this.working = true; this.busy(button, true);
                try {
                    const payload = await this.request(this.reviewUrl.replace('__rider__', r.id), 'POST', { decision: 'approve' });
                    r.status = payload.status;
                    this.$dispatch('lg-toast', { type: 'success', message: payload.message || (r.name + ' is approved.') });
                } catch (error) { this.$dispatch('lg-toast', { type: 'error', message: this.failure(error, 'Could not approve the rider. Try again.') }); }
                finally { this.working = false; this.busy(button, false); }
            },
            async activate(r, event) {
                if (this.working) return;
                const button = event.currentTarget;
                this.working = true; this.busy(button, true);
                try {
                    const payload = await this.request(this.statusUrl.replace('__rider__', r.id), 'PATCH', { active: true });
                    r.status = payload.status;
                    this.$dispatch('lg-toast', { type: 'success', message: payload.message || (r.name + ' is active again.') });
                } catch (error) { this.$dispatch('lg-toast', { type: 'error', message: this.failure(error, 'Could not activate the rider. Try again.') }); }
                finally { this.working = false; this.busy(button, false); }
            },
            askReject(r) { this.confirm = { open: true, kind: 'reject', r: r, reason: '', error: '' }; this.$nextTick(() => document.getElementById('ridReason')?.focus()); },
            askDeactivate(r) { this.confirm = { open: true, kind: 'deactivate', r: r, reason: '', error: '' }; },
            async submitConfirm(event) {
                const c = this.confirm, r = c.r;
                if (this.working) return;
                if (c.kind === 'reject' && c.reason.trim().length < 5) {
                    c.error = 'Reason is too short. Write at least 5 characters so the applicant knows what to fix.';
                    document.getElementById('ridReason')?.focus();
                    return;
                }
                if (c.kind === 'deactivate' && r.parcels > 0) { c.error = r.name + ' still has ' + r.parcels + (r.parcels === 1 ? ' open parcel.' : ' open parcels.') + ' Reassign them first.'; return; }
                const button = event.currentTarget;
                this.working = true; this.busy(button, true); c.error = '';
                try {
                    const payload = c.kind === 'reject'
                        ? await this.request(this.reviewUrl.replace('__rider__', r.id), 'POST', { decision: 'reject', reason: c.reason.trim() })
                        : await this.request(this.statusUrl.replace('__rider__', r.id), 'PATCH', { active: false });
                    r.status = payload.status;
                    c.open = false;
                    this.$dispatch('lg-toast', { type: 'success', message: payload.message });
                } catch (error) { c.error = this.failure(error, 'Could not save the change. Try again.'); }
                finally { this.working = false; this.busy(button, false); }
            }
         }"
         @keydown.escape.window="confirm.open = false">

        <x-logistics.flash />

        <header class="lgh-head">
            <div>
                <h1>Riders</h1>
                <p>Review rider applications and manage the riders linked to this Station Hub.</p>
            </div>
            <a href="{{ route('lgp.station.tasks') }}" class="lgh-btn lgh-btn--primary">
                <x-logistics.icon name="bike" :size="16" /> Assign tasks
            </a>
        </header>

        {{-- Status chips with counts, then search --}}
        <div class="lgh-chips" role="group" aria-label="Filter riders by status" x-show="riders.length" x-cloak>
            @foreach (['active' => 'Active', 'applications' => 'Applications', 'rejected' => 'Rejected', 'inactive' => 'Inactive'] as $key => $text)
                <button type="button" class="lgh-chip" :class="{ 'is-active': tab === '{{ $key }}' }" :aria-pressed="tab === '{{ $key }}'" @click="tab = '{{ $key }}'">
                    {{ $text }} <span class="lgh-chip__count" x-text="count('{{ $key }}')"></span>
                </button>
            @endforeach
            <button type="button" class="lgh-chip lgh-chip--clear" x-show="searching" x-cloak @click="clearSearch()">
                <x-logistics.icon name="x" :size="14" /> Clear filters
            </button>
        </div>

        <div class="lgh-toolbar" x-show="riders.length" x-cloak>
            <label class="lgh-search">
                <span class="lgh-sr">Search riders</span>
                <x-logistics.icon name="search" :size="18" />
                <input type="search" x-model="q" @keydown.escape="q = ''" @keydown.enter.prevent autocomplete="off" maxlength="100" placeholder="Search name, email, vehicle or area">
            </label>
            <span class="lgh-meta" aria-live="polite" x-text="shown.length + (shown.length === 1 ? ' rider' : ' riders')"></span>
        </div>

        <section class="lgh-card lgh-table-card" aria-label="Riders">
            <div class="lgh-table-wrap" x-show="shown.length" x-cloak>
                <table class="lgh-table lgh-table--riders">
                    <thead>
                        <tr>
                            <th scope="col">Rider</th>
                            <th scope="col">Vehicle</th>
                            <th scope="col">Service area</th>
                            <th scope="col" class="is-num" x-show="tab === 'active'">Open parcels</th>
                            <th scope="col"><span class="lgh-sr">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="r in shown" :key="r.id">
                            <tr class="lgh-row lgh-row--static">
                                <td>
                                    <div class="lgh-person">
                                        <span class="lgh-avatar" aria-hidden="true" x-text="r.name.charAt(0).toUpperCase()"></span>
                                        <span class="lgh-person__text"><strong x-text="r.name"></strong><small x-text="r.email || 'No email'"></small></span>
                                    </div>
                                </td>
                                <td data-label="Vehicle" x-text="r.vehicle"></td>
                                <td data-label="Service area" x-text="r.area || 'Not set'"></td>
                                <td data-label="Open parcels" class="is-num" x-show="tab === 'active'" x-text="r.parcels"></td>
                                <td class="lgh-actions">
                                    <div class="lgh-actions__row">
                                        <template x-if="r.status === 'applications'">
                                            <span style="display: contents">
                                                <button type="button" class="lgh-btn lgh-btn--primary lgh-btn--small" @click="approve(r, $event)" :aria-label="'Approve ' + r.name">Approve rider</button>
                                                <button type="button" class="lgh-btn lgh-btn--danger lgh-btn--small" @click="askReject(r)" :aria-label="'Reject application of ' + r.name">Reject application</button>
                                            </span>
                                        </template>
                                        <template x-if="r.status === 'active'">
                                            <button type="button" class="lgh-btn lgh-btn--danger lgh-btn--small" @click="askDeactivate(r)" :aria-label="'Deactivate ' + r.name">Deactivate rider</button>
                                        </template>
                                        <template x-if="r.status === 'inactive'">
                                            <button type="button" class="lgh-btn lgh-btn--primary lgh-btn--small" @click="activate(r, $event)" :aria-label="'Activate ' + r.name">Activate rider</button>
                                        </template>
                                        <template x-if="r.status === 'rejected'">
                                            <span class="lgh-pill lgh-pill--rose">Rejected</span>
                                        </template>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            {{-- No riders at all --}}
            <div class="lgh-empty" x-show="!riders.length" x-cloak role="status">
                <span class="lgh-empty__icon lgh-tone-lilac"><x-logistics.icon name="bike" :size="26" /></span>
                <strong>No riders yet</strong>
                <p>Riders apply in the Rider app and pick your Station Hub. Their applications show up here for review.</p>
                <a href="{{ route('lgp.hub.station') }}" class="lgh-btn lgh-btn--soft">Open dashboard</a>
            </div>

            {{-- Nothing in this status --}}
            <div class="lgh-empty" x-show="riders.length && !shown.length && !searching" x-cloak role="status">
                <span class="lgh-empty__icon lgh-tone-lilac"><x-logistics.icon name="bike" :size="26" /></span>
                <strong x-text="emptyTab[tab][0]"></strong>
                <p x-text="emptyTab[tab][1]"></p>
                <button type="button" class="lgh-btn lgh-btn--soft" @click="tab = emptyTab[tab][3]" x-text="emptyTab[tab][2]"></button>
            </div>

            {{-- Nothing matches the search --}}
            <div class="lgh-empty" x-show="riders.length && !shown.length && searching" x-cloak role="status">
                <span class="lgh-empty__icon lgh-tone-lilac"><x-logistics.icon name="search" :size="26" /></span>
                <strong>No riders match your search</strong>
                <p>Check the spelling, or search by email, vehicle or area. You are searching in <span x-text="labels[tab].toLowerCase()"></span> only.</p>
                <button type="button" class="lgh-btn lgh-btn--primary" @click="clearSearch()">Clear filters</button>
            </div>
        </section>

        {{-- Reject needs a reason; deactivate is a plain confirm, blocked while the rider has open parcels. --}}
        <div class="lgh-layer" x-show="confirm.open" x-cloak>
            <div class="lgh-backdrop" @click="confirm.open = false"></div>
            <div class="lgh-dialog lgh-dialog--sm" role="dialog" aria-modal="true" aria-labelledby="ridConfirmTitle">
                <header class="lgh-dialog__head">
                    <div>
                        <h2 id="ridConfirmTitle" x-text="(confirm.kind === 'reject' ? 'Reject application of ' : 'Deactivate ') + (confirm.r ? confirm.r.name : '') + '?'"></h2>
                        <p x-text="confirm.kind === 'reject' ? 'The applicant is told their application was not approved, and sees your reason.' : 'They stop receiving new parcels until you activate them again.'"></p>
                    </div>
                    <button type="button" class="lgh-close" @click="confirm.open = false" aria-label="Close"><svg class="lgh-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg></button>
                </header>
                <div class="lgh-dialog__body">
                    <div class="lgh-callout" x-show="confirm.kind === 'deactivate' && confirm.r && confirm.r.parcels > 0" x-cloak>
                        <x-logistics.icon name="alert" :size="18" />
                        <p><strong x-text="confirm.r ? confirm.r.parcels : 0"></strong> open parcels are still assigned to this rider. Reassign them on <a href="{{ route('lgp.station.tasks') }}">Task assignment</a>, then deactivate.</p>
                    </div>
                    <div class="lgh-field" x-show="confirm.kind === 'reject'">
                        <label for="ridReason">Reason <span aria-hidden="true">*</span></label>
                        <textarea id="ridReason" rows="3" maxlength="500" x-model="confirm.reason" @input="confirm.error = ''" placeholder="For example: the licence photo is blurry. Apply again with a clear photo."
                                  :aria-invalid="confirm.kind === 'reject' && !!confirm.error" aria-describedby="ridReasonErr" :class="{ 'has-error': confirm.kind === 'reject' && confirm.error }"></textarea>
                        <p class="lgh-err" id="ridReasonErr" x-show="confirm.kind === 'reject' && confirm.error" x-cloak x-text="confirm.error" role="alert"></p>
                    </div>
                    <div class="lgh-callout lgh-callout--rose" x-show="confirm.kind === 'deactivate' && confirm.error && !(confirm.r && confirm.r.parcels > 0)" x-cloak role="alert">
                        <x-logistics.icon name="alert" :size="18" />
                        <p x-text="confirm.error"></p>
                    </div>
                    <footer class="lgh-dialog__foot">
                        <span class="lgh-spacer"></span>
                        <button type="button" class="lgh-btn lgh-btn--soft" @click="confirm.open = false">Cancel</button>
                        <button type="button" class="lgh-btn lgh-btn--danger-solid" :disabled="confirm.kind === 'deactivate' && confirm.r && confirm.r.parcels > 0" @click="submitConfirm($event)" x-text="confirm.kind === 'reject' ? 'Reject application' : 'Deactivate rider'"></button>
                    </footer>
                </div>
            </div>
        </div>
    </div>
</x-logistics.shell>
