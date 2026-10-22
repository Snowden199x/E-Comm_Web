{{-- PREVIEW ONLY: hard-coded data from routes/logistics_preview.php. Spec section 8 (Station Hub > Pickup Requests) and 6A (rider tasks).
     Seller pickups for this station. The Station Hub verifies each request and assigns a pickup TASK to a rider; riders never claim parcels.
     Pickup counts per rider agree with the Station Dashboard (open pickup tasks = assigned + picked up). Interactions change page state only. --}}
@php
    $tabs = [
        'unassigned' => ['Needs a rider', 'All pickups have a rider', 'New seller pickup requests for this station show up here until you assign a rider.', 'check-circle'],
        'assigned' => ['Rider assigned', 'No pickups waiting for a rider to arrive', 'Pickups you assign are listed here until the rider scans the parcel.', 'bike'],
        'picked_up' => ['Picked up', 'No parcels are on their way to the station', 'Parcels a rider has collected from a seller appear here until they are received.', 'package'],
    ];
    $counts = collect($requests)->countBy('status');
    $barangays = collect($requests)->pluck('barangay')->unique()->sort()->values()->all();
@endphp
<x-logistics.shell title="Pickup Requests" role="station">
    <div class="lg-page"
         x-data="{
            tab: '{{ ($counts['unassigned'] ?? 0) ? 'unassigned' : 'assigned' }}',
            q: '',
            brgy: '',
            requests: @js($requests),
            riders: @js($riders),
            open: false,
            target: null,
            riderId: '',
            get shown() {
                const q = this.q.trim().toLowerCase();
                return this.requests.filter(r => r.status === this.tab
                    && (!this.brgy || r.barangay === this.brgy)
                    && (!q || [r.code, r.seller, r.street, r.barangay].some(v => v.toLowerCase().includes(q))));
            },
            count(s) { return this.requests.filter(r => r.status === s).length; },
            rider(id) { return this.riders.find(x => x.id === id); },
            covers(x, r) { return x.barangays.split(', ').includes(r.barangay); },
            get isReassign() { return !!this.target && this.target.status === 'assigned'; },
            get options() {
                const t = this.target;
                if (!t) return [];
                return this.riders
                    .filter(x => x.id !== t.rider_id)
                    .map(x => ({ ...x, near: this.covers(x, t), off: x.status === 'Off duty' }))
                    .sort((a, b) => (a.off - b.off) || (b.near - a.near) || ((a.pickup + a.delivery) - (b.pickup + b.delivery)));
            },
            get chosen() { return this.riders.find(x => x.id === this.riderId); },
            ask(r) {
                this.target = r; this.riderId = ''; this.open = true;
                this.$nextTick(() => { const el = this.$el.querySelector('input[name=pkRider]:not(:disabled)'); if (el) el.focus(); });
            },
            confirm() {
                if (!this.chosen) return;
                const r = this.target, to = this.chosen, from = this.rider(r.rider_id), again = this.isReassign;
                if (from) from.pickup--;
                to.pickup++;
                r.rider_id = to.id;
                r.status = 'assigned';
                this.open = false;
                this.$dispatch('lg-toast', { type: 'success', message: r.code + (again ? ' reassigned to ' : ' assigned to ') + to.name + '. It is now in their task list.' });
                if (!this.count(this.tab)) { const next = ['unassigned', 'assigned', 'picked_up'].find(k => this.count(k)); if (next) this.tab = next; }
            }
         }">
        <div class="lg-page-head">
            <div>
                <h1>Pickup Requests</h1>
                <p>Seller pickups waiting for a rider. Choose a rider for each request. Only this station assigns pickup tasks.</p>
            </div>
            <div class="lg-page-head__actions">
                <a href="{{ route('lgp.station.tasks') }}" class="lg-btn lg-btn--outline"><x-logistics.icon name="bike" :size="18" /> Task assignment</a>
            </div>
        </div>

        <nav class="lg-tabs" aria-label="Pickup status">
            @foreach ($tabs as $k => [$label])
                <button type="button" class="lg-tab" :aria-pressed="tab === '{{ $k }}'" @click="tab = '{{ $k }}'">
                    {{ $label }} <span class="lg-tab__count" x-text="count('{{ $k }}')">{{ $counts[$k] ?? 0 }}</span>
                </button>
            @endforeach
        </nav>

        <div class="lg-toolbar">
            <div class="lg-search">
                <x-logistics.icon name="search" :size="18" />
                <input type="search" class="lg-input" x-model="q" placeholder="Tracking number, seller or address" aria-label="Search pickup requests">
            </div>
            <label class="sr-only" for="pkBrgy">Barangay</label>
            <select id="pkBrgy" class="lg-select" x-model="brgy">
                <option value="">All barangays</option>
                @foreach ($barangays as $b)<option value="{{ $b }}">{{ $b }}</option>@endforeach
            </select>
            <span class="lg-toolbar__meta" aria-live="polite" x-text="shown.length + (shown.length === 1 ? ' request' : ' requests')"></span>
        </div>

        <div class="lg-note">
            <x-logistics.icon name="info" :size="18" />
            <span>You can reassign a pickup until the rider scans the parcel. After the rider drops it off, it is received and scanned in <a href="{{ route('lgp.station.incoming') }}">Incoming Parcels</a>.</span>
        </div>

        <section class="lg-card" aria-live="polite" aria-label="Pickup requests">
            <div class="lg-table-wrap" x-show="shown.length">
                <table class="lg-table lg-table--stack lg-table--mid">
                    <thead>
                        <tr>
                            <th>Tracking number</th><th>Seller</th><th>Pickup address</th><th>Barangay</th><th>Requested</th><th>Status</th><th>Rider</th>
                            <th class="is-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="r in shown" :key="r.code">
                            <tr>
                                <td data-label="Tracking number"><strong x-text="r.code"></strong></td>
                                <td data-label="Seller"><strong x-text="r.seller"></strong><div class="lg-cell-sub" x-text="r.phone"></div></td>
                                <td data-label="Pickup address" x-text="r.street"></td>
                                <td data-label="Barangay" x-text="r.barangay"></td>
                                <td data-label="Requested"><span x-text="r.time"></span><div class="lg-cell-sub" x-text="r.ago"></div></td>
                                <td data-label="Status">
                                    <span class="lg-pill" :class="{ 'lg-pill--amber': r.status === 'unassigned', 'lg-pill--teal': r.status === 'assigned', 'lg-pill--blue': r.status === 'picked_up' }"
                                          x-text="r.status === 'unassigned' ? 'Needs a rider' : (r.status === 'assigned' ? 'Rider assigned' : 'Picked up')"></span>
                                </td>
                                <td data-label="Rider">
                                    <template x-if="rider(r.rider_id)">
                                        <div class="lg-person">
                                            <span class="lg-avatar lg-avatar--sm" aria-hidden="true" x-text="rider(r.rider_id).name.charAt(0)"></span>
                                            <span><strong x-text="rider(r.rider_id).name"></strong><small x-text="rider(r.rider_id).vehicle"></small></span>
                                        </div>
                                    </template>
                                    <span class="lg-hint" x-show="!rider(r.rider_id)">Not assigned</span>
                                </td>
                                <td data-label="Actions" class="is-actions">
                                    <div class="lg-actions">
                                        <button type="button" class="lg-btn lg-btn--sm" x-show="r.status === 'unassigned'" @click="ask(r)" :aria-label="'Assign a rider to ' + r.code">Assign</button>
                                        <button type="button" class="lg-btn lg-btn--outline lg-btn--sm" x-show="r.status === 'assigned'" @click="ask(r)" :aria-label="'Reassign ' + r.code">Reassign</button>
                                        <span class="lg-hint" x-show="r.status === 'picked_up'">With the rider. Receive it when it arrives.</span>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            @foreach ($tabs as $k => [$label, $emptyTitle, $emptyText, $emptyIcon])
                <x-logistics.empty x-show="tab === '{{ $k }}' && !shown.length && !q.trim() && !brgy" x-cloak :icon="$emptyIcon" :title="$emptyTitle" :text="$emptyText" />
            @endforeach
            <x-logistics.empty x-show="(q.trim() || brgy) && !shown.length" x-cloak icon="search" title="No requests match" text="Check the spelling, change the barangay, or look in another tab.">
                <button type="button" class="lg-btn lg-btn--outline lg-btn--sm" @click="q = ''; brgy = ''">Clear filters</button>
            </x-logistics.empty>
        </section>

        {{-- Assign or reassign: pick one rider. Riders who serve the pickup barangay are listed first. Off-duty riders cannot be chosen. --}}
        <div class="lg-overlay" x-show="open" x-cloak @click.self="open = false" @keydown.escape.window="open = false">
            <div class="lg-modal" style="width:min(100%,560px)" role="dialog" aria-modal="true" aria-labelledby="pkDlgTitle">
                <form @submit.prevent="confirm()" novalidate>
                    <div class="lg-modal__head">
                        <div>
                            <h3 id="pkDlgTitle" x-text="(isReassign ? 'Reassign ' : 'Assign a rider to ') + (target ? target.code : '')"></h3>
                            <p x-text="target ? target.seller + ', ' + target.street + ', Brgy. ' + target.barangay + '.' : ''"></p>
                        </div>
                        <button type="button" class="lg-toast__close" @click="open = false" aria-label="Close"><x-logistics.icon name="x" :size="18" /></button>
                    </div>
                    <div class="lg-modal__body">
                        <p class="lg-hint" x-text="'Riders who serve ' + (target ? target.barangay : 'this barangay') + ' are listed first, then the lightest workload. Off-duty riders cannot be chosen.'"></p>
                        <div role="radiogroup" aria-label="Rider" style="display:grid;gap:8px">
                            <template x-for="x in options" :key="x.id">
                                <label class="lg-radio" :style="x.off ? 'opacity:.55;cursor:not-allowed' : ''">
                                    <input type="radio" name="pkRider" :value="x.id" x-model.number="riderId" :disabled="x.off">
                                    <span style="flex:1;min-width:0">
                                        <strong x-text="x.name"></strong>
                                        <span class="lg-hint" style="display:block" x-text="x.vehicle + ' · ' + x.barangays"></span>
                                        <span class="lg-hint" style="display:block" x-text="x.pickup + ' pickup · ' + x.delivery + ' delivery · ' + (x.pickup + x.delivery) + ' total'"></span>
                                    </span>
                                    <span style="display:grid;gap:4px;justify-items:end">
                                        <span class="lg-pill" :class="{ 'lg-pill--green': x.status === 'Available', 'lg-pill--blue': x.status === 'On route', 'lg-pill--gray': x.status === 'Off duty' }" x-text="x.status"></span>
                                        <span class="lg-pill lg-pill--teal" x-show="x.near && !x.off">Serves this area</span>
                                    </span>
                                </label>
                            </template>
                        </div>
                        <p class="lg-hint" x-show="!chosen">Choose a rider to continue.</p>
                    </div>
                    <div class="lg-modal__foot">
                        <button type="button" class="lg-btn lg-btn--outline" @click="open = false">Cancel</button>
                        <button type="submit" class="lg-btn" :disabled="!chosen" x-text="isReassign ? 'Reassign pickup' : 'Assign pickup'"></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-logistics.shell>