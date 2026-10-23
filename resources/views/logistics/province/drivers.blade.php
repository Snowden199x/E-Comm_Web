{{-- Province Hub reviews driver applications submitted through the Transport Driver API. --}}
<x-logistics.shell title="Transport Drivers" role="province">
    <div class="lg-page"
         x-data="{
            drivers: @js($drivers),
            today: @js($today),
            reviewUrl: @js($reviewUrl),
            statusUrl: @js($statusUrl),
            working: false,
            q: '',
            tab: 'active',
            view: null,
            confirm: { open: false, kind: '', d: null, reason: '', error: '' },
            tabs: {
                active: d => d.status === 'available' || d.status === 'off_duty',
                applications: d => d.status === 'pending',
                on_trip: d => d.status === 'on_trip',
                inactive: d => d.status === 'inactive'
            },
            labels: { available: 'Available', on_trip: 'On trip', off_duty: 'Off duty', inactive: 'Inactive', pending: 'Pending' },
            tones: { available: 'lg-pill--green', on_trip: 'lg-pill--blue', off_duty: 'lg-pill--gray', inactive: 'lg-pill--gray', pending: 'lg-pill--amber' },
            get shown() {
                const s = this.q.trim().toLowerCase();
                return this.drivers
                    .filter(d => this.tabs[this.tab](d)
                        && (!s || (d.name + ' ' + d.license + ' ' + d.phone + ' ' + d.email + ' ' + (d.trip || '')).toLowerCase().includes(s)))
                    .sort((a, b) => a.name.localeCompare(b.name));
            },
            count(k) { return this.drivers.filter(this.tabs[k]).length; },
            fmt(iso) { return iso ? new Date(iso + 'T00:00:00').toLocaleDateString('en-PH', { day: 'numeric', month: 'short', year: 'numeric' }) : ''; },
            // Licence check from the spec: it must not be expired, and it is re-checked before every trip is assigned.
            daysLeft(d) { return Math.round((new Date(d.expiry + 'T00:00:00') - new Date(this.today + 'T00:00:00')) / 86400000); },
            expired(d) { return this.daysLeft(d) < 0; },
            expiryNote(d) {
                const n = this.daysLeft(d);
                if (n < 0) return { tone: 'lg-pill--red', text: 'Expired' };
                if (n <= 60) return { tone: 'lg-pill--amber', text: 'Expires in ' + n + (n === 1 ? ' day' : ' days') };
                return null;
            },
            openView(d) { this.view = d; },
            closeView() { this.view = null; },
            openDoc(url) { if (url) window.open(url, '_blank', 'noopener,noreferrer'); },
            async request(url, method, body) {
                const response = await fetch(url, { method, headers: { 'Content-Type': 'application/json', 'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '' }, body: JSON.stringify(body) });
                const payload = await response.json().catch(() => ({}));
                if (!response.ok) throw new Error(payload.message || Object.values(payload.errors || {})[0]?.[0] || 'Could not update Driver.');
                return payload;
            },
            async approve(d) {
                if (this.expired(d)) { this.$dispatch('lg-toast', { type: 'error', message: 'The licence of ' + d.name + ' is expired. It cannot be approved.' }); return; }
                if (this.working) return;
                this.working = true;
                try {
                    const payload = await this.request(this.reviewUrl.replace('__driver__', d.id), 'POST', { decision: 'approve' });
                    Object.assign(d, payload.driver); this.view = null; this.tab = 'active';
                    this.$dispatch('lg-toast', { type: 'success', message: d.name + ' approved.' });
                } catch (error) { this.$dispatch('lg-toast', { type: 'error', message: error.message }); }
                finally { this.working = false; }
            },
            askReject(d) { this.confirm = { open: true, kind: 'reject', d: d, reason: '', error: '' }; },
            askDeactivate(d) { this.confirm = { open: true, kind: 'deactivate', d: d, reason: '', error: '' }; },
            async activate(d) {
                if (this.expired(d)) { this.$dispatch('lg-toast', { type: 'error', message: 'The licence of ' + d.name + ' is expired. Update it before activating.' }); return; }
                if (this.working) return;
                this.working = true;
                try {
                    const payload = await this.request(this.statusUrl.replace('__driver__', d.id), 'PATCH', { status: 'available' });
                    Object.assign(d, payload.driver); this.view = null;
                    this.$dispatch('lg-toast', { type: 'success', message: d.name + ' is active again.' });
                } catch (error) { this.$dispatch('lg-toast', { type: 'error', message: error.message }); }
                finally { this.working = false; }
            },
            async submitConfirm() {
                const c = this.confirm, d = c.d;
                if (this.working) return;
                if (c.kind === 'reject') {
                    if (c.reason.trim().length < 10) { c.error = 'Give a short reason (at least 10 characters). The applicant sees it.'; return; }
                }
                if (d.status === 'on_trip') return;
                this.working = true;
                try {
                    const payload = c.kind === 'reject'
                        ? await this.request(this.reviewUrl.replace('__driver__', d.id), 'POST', { decision: 'reject', reason: c.reason.trim() })
                        : await this.request(this.statusUrl.replace('__driver__', d.id), 'PATCH', { status: 'inactive' });
                    if (c.kind === 'reject') this.drivers = this.drivers.filter(x => x.id !== d.id);
                    else Object.assign(d, payload.driver);
                    c.open = false; this.view = null;
                    this.$dispatch('lg-toast', { type: 'success', message: payload.message });
                } catch (error) { c.error = error.message; }
                finally { this.working = false; }
            }
         }">
        <div class="lg-page-head">
            <div>
                <h1>Transport Drivers</h1>
                <p>Company drivers of {{ $hub['name'] }}. They move parcels between stations and hubs on trips you assign.</p>
            </div>
        </div>

        <nav class="lg-tabs" aria-label="Driver status">
            @foreach (['active' => 'Active', 'applications' => 'Applications', 'on_trip' => 'On trip', 'inactive' => 'Inactive'] as $k => $label)
                <button type="button" class="lg-tab" :aria-pressed="tab === '{{ $k }}'" @click="tab = '{{ $k }}'">
                    {{ $label }} <span class="lg-tab__count" x-text="count('{{ $k }}')"></span>
                </button>
            @endforeach
        </nav>

        <div class="lg-toolbar">
            <div class="lg-search">
                <x-logistics.icon name="search" :size="18" />
                <input type="search" class="lg-input" x-model="q" placeholder="Search name, licence number, phone or trip" aria-label="Search drivers">
            </div>
            <span class="lg-toolbar__meta" aria-live="polite" x-text="shown.length + (shown.length === 1 ? ' driver' : ' drivers')"></span>
        </div>

        <section class="lg-card" aria-label="Transport drivers">
            <div class="lg-table-wrap" x-show="shown.length">
                <table class="lg-table lg-table--stack lg-table--mid">
                    <thead>
                        <tr>
                            <th>Driver</th><th>Licence</th><th>Expires</th><th>Status</th>
                            <th x-show="tab === 'applications'">Applied</th>
                            <th x-show="tab !== 'applications'">Current trip</th>
                            <th x-show="tab !== 'applications'" class="is-num">Trips done</th>
                            <th class="is-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="d in shown" :key="d.id">
                            <tr>
                                <td data-label="Driver"><div class="lg-cell-title" x-text="d.name"></div><div class="lg-cell-sub" x-text="d.phone"></div></td>
                                <td data-label="Licence"><div class="lg-cell-title" x-text="d.license"></div><div class="lg-cell-sub" x-text="d.category"></div></td>
                                <td data-label="Expires">
                                    <div x-text="fmt(d.expiry)"></div>
                                    <template x-if="expiryNote(d)"><span class="lg-pill" :class="expiryNote(d).tone" x-text="expiryNote(d).text"></span></template>
                                </td>
                                <td data-label="Status"><span class="lg-pill" :class="tones[d.status]" x-text="labels[d.status]"></span></td>
                                <td data-label="Applied" x-show="tab === 'applications'" x-text="fmt(d.applied)"></td>
                                <td data-label="Current trip" x-show="tab !== 'applications'" x-text="d.trip || 'None'"></td>
                                <td data-label="Trips done" x-show="tab !== 'applications'" class="is-num" x-text="d.done"></td>
                                <td data-label="Actions" class="is-actions">
                                    <div class="lg-actions">
                                        <button type="button" class="lg-btn lg-btn--outline lg-btn--sm" @click="openView(d)" :aria-label="(d.status === 'pending' ? 'Review ' : 'View ') + d.name">
                                            <x-logistics.icon name="eye" :size="16" />
                                            <span x-text="d.status === 'pending' ? 'Review' : 'View'"></span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <x-logistics.empty x-show="!shown.length && !q.trim() && tab === 'applications'" x-cloak icon="users" title="No applications waiting" text="Drivers apply in the driver app and choose your Province Hub. New applications show up here for review." />
            <x-logistics.empty x-show="!shown.length && !q.trim() && tab !== 'applications'" x-cloak icon="users" title="No drivers in this tab" text="Approved drivers appear here by status." />
            <x-logistics.empty x-show="!shown.length && q.trim()" x-cloak icon="search" title="No drivers match" text="Check the spelling, or look in another tab.">
                <button type="button" class="lg-btn lg-btn--outline lg-btn--sm" @click="q = ''">Clear search</button>
            </x-logistics.empty>
        </section>

        {{-- Driver details. Application review (approve or reject) happens here. --}}
        <div class="lg-overlay" x-show="view" x-cloak @click.self="closeView()" @keydown.escape.window="closeView()">
            <div class="lg-modal" style="width:min(100%,560px)" role="dialog" aria-modal="true" aria-labelledby="drvTitle" x-show="view">
                <div class="lg-modal__head">
                    <div>
                        <h3 id="drvTitle" x-text="view ? view.name : ''"></h3>
                        <p x-show="view"><span class="lg-pill" :class="view ? tones[view.status] : ''" x-text="view ? labels[view.status] : ''"></span></p>
                    </div>
                    <button type="button" class="lg-toast__close" @click="closeView()" aria-label="Close"><x-logistics.icon name="x" :size="18" /></button>
                </div>
                <div class="lg-modal__body lg-modal__body--grid" x-show="view">
                    <template x-if="view && expired(view)">
                        <div class="lg-note lg-note--warn">
                            <x-logistics.icon name="alert" :size="18" />
                            <span>The driver's licence expired on <strong x-text="fmt(view.expiry)"></strong>. An expired licence blocks approval, activation and new trips.</span>
                        </div>
                    </template>
                    <template x-if="view && view.status === 'on_trip'">
                        <div class="lg-note">
                            <x-logistics.icon name="info" :size="18" />
                            <span>On trip <strong x-text="view.trip"></strong>. This driver can be deactivated after the trip ends.</span>
                        </div>
                    </template>

                    <dl class="lg-facts" x-show="view">
                        <div><dt>Phone</dt><dd x-text="view && view.phone"></dd></div>
                        <div><dt>Email</dt><dd x-text="view && view.email"></dd></div>
                        <div><dt>Birthday</dt><dd x-text="view && fmt(view.birthday)"></dd></div>
                        <div><dt>Sex</dt><dd x-text="view && view.sex"></dd></div>
                        <div class="is-wide"><dt>Home address</dt><dd x-text="view && view.address"></dd></div>
                        <div><dt>Licence number</dt><dd x-text="view && view.license"></dd></div>
                        <div><dt>Category</dt><dd x-text="view && view.category"></dd></div>
                        <div><dt>Licence expires</dt><dd x-text="view && fmt(view.expiry)"></dd></div>
                        <div><dt>Completed trips</dt><dd x-text="view && view.done"></dd></div>
                    </dl>

                    <div>
                        <p class="lg-section-sub" style="margin-bottom:8px">Documents</p>
                        <div class="lg-actions" style="flex-wrap:wrap;gap:8px">
                            <button type="button" class="lg-btn lg-btn--outline lg-btn--sm" @click="openDoc(view?.license_url)"><x-logistics.icon name="file" :size="16" /> Driver's licence</button>
                            <button type="button" class="lg-btn lg-btn--outline lg-btn--sm" @click="openDoc(view?.valid_id_url)"><x-logistics.icon name="file" :size="16" /> Valid ID</button>
                        </div>
                    </div>
                </div>
                <div class="lg-modal__foot" x-show="view">
                    <button type="button" class="lg-btn lg-btn--outline" @click="closeView()">Close</button>
                    <template x-if="view && view.status === 'pending'">
                        <span style="display:contents">
                            <button type="button" class="lg-btn lg-btn--danger" @click="askReject(view)">Reject</button>
                            <button type="button" class="lg-btn" :disabled="expired(view)" @click="approve(view)">Approve</button>
                        </span>
                    </template>
                    <template x-if="view && (view.status === 'available' || view.status === 'off_duty')">
                        <button type="button" class="lg-btn lg-btn--danger" @click="askDeactivate(view)">Deactivate</button>
                    </template>
                    <template x-if="view && view.status === 'inactive'">
                        <button type="button" class="lg-btn" :disabled="expired(view)" @click="activate(view)">Activate</button>
                    </template>
                </div>
            </div>
        </div>

        {{-- Reject needs a reason; deactivate is a plain confirm. --}}
        <div class="lg-overlay" x-show="confirm.open" x-cloak @click.self="confirm.open = false" @keydown.escape.window="confirm.open = false">
            <div class="lg-modal" role="dialog" aria-modal="true" aria-labelledby="drvConfirmTitle">
                <div class="lg-modal__head">
                    <div>
                        <h3 id="drvConfirmTitle" x-text="(confirm.kind === 'reject' ? 'Reject application of ' : 'Deactivate ') + (confirm.d ? confirm.d.name : '')"></h3>
                        <p x-text="confirm.kind === 'reject' ? 'The applicant is told their application was not approved.' : 'They will not be assigned to new trips until you activate them again.'"></p>
                    </div>
                    <button type="button" class="lg-toast__close" @click="confirm.open = false" aria-label="Close"><x-logistics.icon name="x" :size="18" /></button>
                </div>
                <div class="lg-modal__body lg-modal__body--grid" x-show="confirm.kind === 'reject'">
                    <div class="lg-field">
                        <label for="drvReason">Reason <span class="lg-req" aria-hidden="true">*</span></label>
                        <textarea id="drvReason" class="lg-textarea" rows="3" maxlength="300" x-model="confirm.reason" placeholder="For example: the licence photo is blurry. Please apply again with a clear photo." :aria-invalid="!!confirm.error" aria-describedby="drvReasonErr"></textarea>
                        <p class="lg-error" id="drvReasonErr" x-show="confirm.error" x-text="confirm.error" role="alert"></p>
                    </div>
                </div>
                <div class="lg-modal__foot">
                    <button type="button" class="lg-btn lg-btn--outline" @click="confirm.open = false">Cancel</button>
                    <button type="button" class="lg-btn lg-btn--danger-solid" @click="submitConfirm()" x-text="confirm.kind === 'reject' ? 'Reject application' : 'Deactivate'"></button>
                </div>
            </div>
        </div>
    </div>
</x-logistics.shell>
