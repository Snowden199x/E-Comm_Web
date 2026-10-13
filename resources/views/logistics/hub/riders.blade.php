@php
    // HARDCODED PREVIEW: replace with this hub's riders (center-scoped query).
    $riders = [
        ['id' => 1, 'name' => 'Jun Bautista',  'email' => 'jun@mail.com',   'vehicle' => 'Motorcycle', 'area' => 'Santa Cruz', 'parcels' => 14, 'status' => 'active'],
        ['id' => 2, 'name' => 'Paolo Garcia',  'email' => 'paolo@mail.com', 'vehicle' => 'Motorcycle', 'area' => 'Pagsanjan',  'parcels' => 12, 'status' => 'active'],
        ['id' => 3, 'name' => 'Nina Castillo', 'email' => 'nina@mail.com',  'vehicle' => 'Tricycle',   'area' => 'Santa Cruz', 'parcels' => 9,  'status' => 'active'],
        ['id' => 4, 'name' => 'Kris Mendoza',  'email' => 'kris@mail.com',  'vehicle' => 'Motorcycle', 'area' => 'Santa Cruz', 'parcels' => 0,  'status' => 'applications'],
        ['id' => 5, 'name' => 'Dante Ruiz',    'email' => 'dante@mail.com', 'vehicle' => 'Bicycle',    'area' => 'Santa Cruz', 'parcels' => 0,  'status' => 'applications'],
        ['id' => 6, 'name' => 'Leo Tan',       'email' => 'leo@mail.com',   'vehicle' => 'Motorcycle', 'area' => 'Liliw',      'parcels' => 0,  'status' => 'applications'],
        ['id' => 7, 'name' => 'Mia Cruz',      'email' => 'mia@mail.com',   'vehicle' => 'Motorcycle', 'area' => 'Santa Cruz', 'parcels' => 0,  'status' => 'rejected'],
        ['id' => 8, 'name' => 'Rey Santos',    'email' => 'rey@mail.com',   'vehicle' => 'Tricycle',   'area' => 'Santa Cruz', 'parcels' => 0,  'status' => 'inactive'],
    ];
    $tabs = [
        'active' => ['Active', 'No active riders', 'Approve an application to add your first rider.'],
        'applications' => ['Applications', 'No pending applications', 'New rider applications from your area show up here for review.'],
        'rejected' => ['Rejected', 'No rejected applications', 'Applications you reject are kept here.'],
        'inactive' => ['Inactive', 'No inactive riders', 'Riders you deactivate are listed here and can be reactivated.'],
    ];
@endphp
<x-logistics.shell title="Rider Management" :role="$role">
    <div class="lg-page"
         x-data="{
            tab: 'active',
            q: '',
            riders: @js($riders),
            open: false,
            target: null,
            action: '',
            reason: '',
            err: false,
            get shown() {
                const q = this.q.trim().toLowerCase();
                return this.riders.filter(r => r.status === this.tab && (!q || r.name.toLowerCase().includes(q) || r.email.toLowerCase().includes(q)));
            },
            count(s) { return this.riders.filter(r => r.status === s).length; },
            ask(r, action) {
                this.target = r; this.action = action; this.reason = ''; this.err = false;
                if (action === 'reject' || action === 'deactivate') {
                    this.open = true;
                    this.$nextTick(() => this.$refs.focusMe && this.$refs.focusMe.focus());
                } else { this.submit(); }
            },
            submit() {
                if (this.action === 'reject' && this.reason.trim().length < 5) { this.err = true; return; }
                const r = this.target;
                const done = {
                    approve: ['active', r.name + ' approved. They can now take deliveries.'],
                    reject: ['rejected', r.name + ' rejected. They will see your reason.'],
                    deactivate: ['inactive', r.name + ' deactivated.'],
                    activate: ['active', r.name + ' is active again.'],
                }[this.action];
                r.status = done[0];
                this.open = false;
                this.$dispatch('lg-toast', { type: 'success', message: done[1] });
            }
         }">
        <div class="lg-page-head">
            <div>
                <h1>Rider Management</h1>
                <p>Review applicants and manage riders linked to this hub.</p>
            </div>
            <div class="lg-search" style="max-width:300px">
                <x-logistics.icon name="search" :size="18" />
                <input type="search" class="lg-input" x-model="q" placeholder="Search name or email" aria-label="Search riders">
            </div>
        </div>

        <nav class="lg-tabs" aria-label="Rider status">
            @foreach ($tabs as $k => [$label])
                <button type="button" class="lg-tab" :aria-pressed="tab === '{{ $k }}'" @click="tab = '{{ $k }}'">
                    {{ $label }} <span class="lg-tab__count" x-text="count('{{ $k }}')">{{ collect($riders)->where('status', $k)->count() }}</span>
                </button>
            @endforeach
        </nav>

        <section class="lg-card" aria-live="polite" aria-label="Riders">
            <div class="lg-table-wrap" x-show="shown.length">
                <table class="lg-table lg-table--stack lg-table--mid">
                    <thead>
                        <tr>
                            <th>Rider</th><th>Vehicle</th><th>Area</th>
                            <th class="is-num" x-show="tab === 'active'">Active parcels</th>
                            <th class="is-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="r in shown" :key="r.id">
                            <tr>
                                <td data-label="Rider">
                                    <div class="lg-person">
                                        <span class="lg-avatar lg-avatar--sm" aria-hidden="true" x-text="r.name.charAt(0)"></span>
                                        <span><strong x-text="r.name"></strong><small x-text="r.email"></small></span>
                                    </div>
                                </td>
                                <td data-label="Vehicle" x-text="r.vehicle"></td>
                                <td data-label="Area" x-text="r.area"></td>
                                <td data-label="Active parcels" class="is-num" x-show="tab === 'active'" x-text="r.parcels"></td>
                                <td data-label="Actions" class="is-actions">
                                    <div class="lg-actions">
                                        <template x-if="tab === 'applications'">
                                            <div class="lg-actions">
                                                <span class="lg-doc-links">
                                                    <a class="lg-doc-link" href="#">Valid ID</a><a class="lg-doc-link" href="#">License</a><a class="lg-doc-link" href="#">OR / CR</a>
                                                </span>
                                                <button type="button" class="lg-btn lg-btn--sm" @click="ask(r, 'approve')">Approve</button>
                                                <button type="button" class="lg-btn lg-btn--danger lg-btn--sm" @click="ask(r, 'reject')">Reject</button>
                                            </div>
                                        </template>
                                        <template x-if="tab === 'active'">
                                            <button type="button" class="lg-btn lg-btn--outline lg-btn--sm" @click="ask(r, 'deactivate')">Deactivate</button>
                                        </template>
                                        <template x-if="tab === 'inactive'">
                                            <button type="button" class="lg-btn lg-btn--outline lg-btn--sm" @click="ask(r, 'activate')">Activate</button>
                                        </template>
                                        <template x-if="tab === 'rejected'">
                                            <span class="lg-pill lg-pill--red">Rejected</span>
                                        </template>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            @foreach ($tabs as $k => [$label, $emptyTitle, $emptyText])
                <x-logistics.empty x-show="tab === '{{ $k }}' && !shown.length && !q.trim()" x-cloak icon="users" :title="$emptyTitle" :text="$emptyText" />
            @endforeach
            <x-logistics.empty x-show="q.trim() && !shown.length" x-cloak icon="search" title="No riders match your search" text="Check the spelling, or look in another tab.">
                <button type="button" class="lg-btn lg-btn--outline lg-btn--sm" @click="q = ''">Clear search</button>
            </x-logistics.empty>
        </section>

        {{-- Confirm: reject (reason required) or deactivate --}}
        <div class="lg-overlay" x-show="open" x-cloak @click.self="open = false" @keydown.escape.window="open = false">
            <div class="lg-modal" role="dialog" aria-modal="true" aria-labelledby="riderDlgTitle">
                <form @submit.prevent="submit()" novalidate>
                    <div class="lg-modal__head">
                        <div>
                            <h3 id="riderDlgTitle" x-text="action === 'reject' ? 'Reject application' : 'Deactivate rider'"></h3>
                            <p x-show="action === 'reject'" x-text="'Tell ' + (target ? target.name : 'the applicant') + ' why. They will see this.'"></p>
                            <p x-show="action === 'deactivate'" x-text="(target ? target.name : 'This rider') + ' will stop receiving new parcels.'"></p>
                        </div>
                        <button type="button" class="lg-toast__close" @click="open = false" aria-label="Close"><x-logistics.icon name="x" :size="18" /></button>
                    </div>
                    <div class="lg-modal__body lg-modal__body--grid">
                        <div class="lg-note lg-note--warn" x-show="action === 'deactivate' && target && target.parcels > 0">
                            <x-logistics.icon name="alert" :size="18" />
                            <span><strong x-text="target ? target.parcels : 0"></strong> parcels are still assigned. Reassign them before deactivating.</span>
                        </div>
                        <div class="lg-field" x-show="action === 'reject'">
                            <label for="rejectReason">Reason <span class="lg-req" aria-hidden="true">*</span></label>
                            <textarea id="rejectReason" class="lg-textarea" x-ref="focusMe" x-model="reason" maxlength="500" rows="3"
                                :aria-invalid="err" aria-describedby="rejectErr" placeholder="For example: the license photo is unreadable."></textarea>
                            <p class="lg-error" id="rejectErr" x-show="err" x-cloak role="alert"><x-logistics.icon name="alert" :size="14" /> Enter at least 5 characters so the applicant knows what to fix.</p>
                        </div>
                    </div>
                    <div class="lg-modal__foot">
                        <button type="button" class="lg-btn lg-btn--outline" @click="open = false">Cancel</button>
                        <button type="submit" class="lg-btn lg-btn--danger-solid" x-text="action === 'reject' ? 'Reject application' : 'Deactivate'"></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-logistics.shell>
