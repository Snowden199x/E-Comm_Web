{{--
    Station Hub parcel operations. One view for six pages (route defaults 'mode'): Pickup requests, Task assignment, Delivery queue,
    Incoming parcels, Parcel sorting and Outgoing transfers. Fed by StationOperationsController::index (mode, station, rows, riders, trips).
    The Station Hub receives, sorts and assigns riders; it never creates or changes a transport trip (the Province Hub does).
    Posts go to StationOperationsController::assign and ::declinePickup, which redirect back with a message.
    Look: the seller pastel lilac system, the same .lgh layer as the Province Hub pages. Styles: hubs-theme.css, drivers-theme.css
    (pills, callouts, dialogs) and operations-theme.css, loaded last. Dialogs sit in .lgh-layer and carry their own copy of the tokens.
    Status colors: waiting = butter, in progress = sky, new or sorted = lilac, done = mint, failed or expired = rose.
    Status chips with counts (pickups, delivery queue, tasks and incoming); search runs as you type, across the rows loaded (at most 200).
    One click on a row does its main action (assign a rider) or, when there is none, opens Parcel tracking for that parcel. Each row has a visible button.
    Loading: shell skeleton on open; spinner on the pressed button (admin button-loading.js).
    Empty: nothing on this page yet (one per page, with a button), nothing in a status, and no parcels match the search. Errors: the top banner.
--}}
@php
    $pages = [
        'pickups' => ['Pickup requests', 'Verify each seller pickup, then assign it to a rider from this station.'],
        'tasks' => ['Task assignment', 'Open pickup and delivery tasks held by your riders.'],
        'delivery-queue' => ['Delivery queue', 'Assign sorted parcels to a local rider for final delivery.'],
        'incoming' => ['Incoming parcels', 'Scan arriving parcels with the camera button at the top of the page. Arrival also records sorting.'],
        'sorting' => ['Parcel sorting', 'Parcels scanned and sorted at this station, with their next stop.'],
        'outgoing' => ['Outgoing transfers', 'Sorted parcels waiting for a trip. Assign a driver and the hub they must reach, then scan the departure.'],
    ];
    [$title, $description] = $pages[$mode];

    // Status chips: key => label. The keys match the order status (or the task status) the controller sends in each row's "key".
    $chipSets = [
        'pickups' => ['all' => 'All', 'needs' => 'Needs a rider', 'assigned' => 'Rider assigned', 'picked_up' => 'Picked up'],
        'delivery-queue' => ['all' => 'All', 'at_destination_hub' => 'Ready to assign', 'assigned_to_rider' => 'Assigned', 'out_for_delivery' => 'Out for delivery', 'delivery_failed' => 'Failed'],
        'tasks' => ['all' => 'All', 'assigned' => 'Assigned', 'in_progress' => 'In progress', 'failed' => 'Failed', 'expired' => 'Expired'],
        'incoming' => ['all' => 'All', 'waiting' => 'Awaiting scan', 'received' => 'Received'],
    ];
    $chips = $chipSets[$mode] ?? [];
    // The dashboard links to the delivery queue with ?tab=failed or ?tab=out.
    $tabAlias = ['failed' => 'delivery_failed', 'out' => 'out_for_delivery'];
    $startTab = $tabAlias[request()->query('tab')] ?? request()->query('tab');
    $startTab = array_key_exists((string) $startTab, $chips) ? $startTab : 'all';

    $items = $rows->values()->map(fn ($r) => [
        'id' => $r['id'], 'tracking' => $r['tracking'] ?: 'No tracking number', 'key' => $r['key'] ?? '',
        'contact' => $mode === 'delivery-queue' ? ($r['buyer'] ?? null) : ($r['seller'] ?? null),
        'address' => $r['address'] ?? null, 'status' => $r['status'], 'pickup_status' => $r['pickup_status'] ?? null,
        'rider' => (bool) ($r['rider'] ?? false), 'rider_name' => $r['rider_name'] ?? null, 'type' => $r['type'] ?? null,
        'failure' => $r['failure_reason'] ?? null, 'next' => $r['next'] ?? null,
        'arrived' => $r['arrived'] ?? null, 'sorted' => $r['sorted'] ?? null, 'can_assign' => (bool) ($r['can_assign'] ?? false),
    ])->all();
    $riderList = $riders->map(fn ($rider) => ['id' => $rider->id, 'name' => $rider->user?->name ?? 'Rider', 'vehicle' => $rider->vehicle_type])->values()->all();

    // Pill colors by status key. Anything else is lilac.
    $tones = ['ready_for_pickup' => 'butter', 'picked_up' => 'sky', 'sorted' => 'lilac', 'in_transit_to_hub' => 'sky', 'at_destination_hub' => 'butter',
        'assigned_to_rider' => 'sky', 'out_for_delivery' => 'sky', 'delivery_failed' => 'rose',
        'assigned' => 'butter', 'in_progress' => 'sky', 'failed' => 'rose', 'expired' => 'rose'];

    // Empty states, one per page: [icon, title, hint, button label, url]
    $empties = [
        'pickups' => ['inbox', 'No pickup requests', 'A seller’s pickup request shows here once the parcel is marked ready.', 'Open dashboard', route('lgp.hub.station')],
        'tasks' => ['bike', 'No open tasks', 'Assign a parcel to a rider from Pickup requests or the Delivery queue, and the task shows here.', 'View pickup requests', route('lgp.station.pickups')],
        'delivery-queue' => ['package', 'No parcels to deliver', 'Parcels show here after you receive and sort them at this station.', 'View incoming parcels', route('lgp.station.incoming')],
        'incoming' => ['inbox', 'No incoming parcels', 'Parcels show here when a trip brings them to this station.', 'Track parcels', route('lgp.station.tracking')],
        'sorting' => ['layers', 'No sorted parcels', 'Scan an arriving parcel to receive and sort it. It shows here with its next stop.', 'View incoming parcels', route('lgp.station.incoming')],
        'outgoing' => ['truck', 'No parcels waiting for a trip', 'Sorted parcels that go on to another hub show here.', 'Assign driver', route('lgp.station.trips')],
    ];
    [$emptyIcon, $emptyTitle, $emptyHint, $emptyButton, $emptyUrl] = $empties[$mode];
    $tripTone = ['scheduled' => 'butter', 'in_transit' => 'sky'];

    $col2 = ['pickups' => 'Pickup address', 'delivery-queue' => 'Delivery address', 'incoming' => 'Deliver to', 'tasks' => 'Task', 'sorting' => 'Next stop', 'outgoing' => 'Next stop'][$mode];
    $col4 = ['pickups' => 'Rider', 'delivery-queue' => 'Rider', 'tasks' => 'Rider', 'incoming' => 'Scanned', 'sorting' => 'Sorted', 'outgoing' => null][$mode];
    $canAssignHere = in_array($mode, ['pickups', 'delivery-queue'], true);
@endphp
@push('lg-head')
    @vite(['resources/css/logistics/hubs-theme.css', 'resources/css/logistics/drivers-theme.css', 'resources/css/logistics/operations-theme.css'])
@endpush

<x-logistics.shell :title="$title" role="station">
    <div class="lgh"
         x-data="{
            mode: @js($mode),
            rows: @js($items),
            riders: @js($riderList),
            assignUrl: @js(route('lgp.station.orders.assign', '__order__')),
            declineUrl: @js(route('lgp.station.orders.decline-pickup', '__order__')),
            trackUrl: @js(route('lgp.station.tracking')),
            tones: @js($tones),
            q: '',
            tab: @js($startTab),
            assign: null,
            rider: '',
            decline: null,
            reason: '',
            errors: {},
            init() {
                this.$watch(() => this.assign !== null || this.decline !== null, (open) => {
                    const main = document.querySelector('.vd-main');
                    if (main) main.style.overflow = open ? 'hidden' : '';
                });
            },
            get searching() { return this.q.trim() !== ''; },
            get filtered() { return this.searching || this.tab !== 'all'; },
            inTab(r, t) {
                if (t === 'all') return true;
                if (this.mode === 'pickups') return t === 'needs' ? (r.key === 'ready_for_pickup' && !r.rider) : (t === 'assigned' ? (r.key === 'ready_for_pickup' && r.rider) : r.key === 'picked_up');
                if (this.mode === 'incoming') return t === 'waiting' ? !r.arrived : !!r.arrived;
                return r.key === t;
            },
            get shown() {
                const s = this.q.trim().toLowerCase();
                return this.rows.filter(r => this.inTab(r, this.tab) && (!s || [r.tracking, r.contact, r.address, r.rider_name, r.next].join(' ').toLowerCase().includes(s)));
            },
            count(t) { return this.rows.filter(r => this.inTab(r, t)).length; },
            clearFilters() { this.q = ''; this.tab = 'all'; },
            track(r) { return this.trackUrl + '?q=' + encodeURIComponent(r.tracking); },
            tone(r) { return this.tones[r.key] || 'lilac'; },
            primary(r) { return this.rows && ['pickups', 'delivery-queue'].includes(this.mode) && r.can_assign; },
            go(r, event) {
                if (event.target.closest('a, button')) return;
                if (this.primary(r) && this.riders.length) this.openAssign(r); else window.location.assign(this.track(r));
            },
            openAssign(r) { this.assign = r; this.rider = ''; this.errors = {}; this.$nextTick(() => document.getElementById('stationRider')?.focus()); },
            openDecline(r) { this.decline = r; this.reason = ''; this.errors = {}; this.$nextTick(() => document.getElementById('declineReason')?.focus()); },
            closeTop() { this.assign = null; this.decline = null; },
            // Browser check before the form is sent. A failed check stops it and stops the button spinner.
            checkAssign(event) {
                this.errors = {};
                if (!this.rider) this.errors.rider = 'Rider is not chosen. Pick a rider from the list.';
                this.stopIfInvalid(event);
            },
            checkDecline(event) {
                this.errors = {};
                if (this.reason.trim().length < 5) this.errors.reason = 'Reason is too short. Write at least 5 characters so the seller knows what to fix.';
                this.stopIfInvalid(event);
            },
            stopIfInvalid(event) {
                if (!Object.keys(this.errors).length) return;
                event.preventDefault();
                event.target.querySelectorAll('button[type=submit]').forEach((b) => window.vdButtonLoading && window.vdButtonLoading(b, false));
                this.$nextTick(() => event.target.querySelector('[aria-invalid=true]')?.focus());
            }
         }"
         @keydown.escape.window="closeTop()">

        <x-logistics.flash />

        <header class="lgh-head">
            <div>
                <h1>{{ $title }}</h1>
                <p>{{ $station->name }}. {{ $description }}</p>
            </div>
            @if (in_array($mode, ['outgoing', 'delivery-queue'], true))
                {{-- A driver can be sent to another hub from here, next to the local rider delivery work. --}}
                <a href="{{ route('lgp.station.trips') }}" class="lgh-btn {{ $mode === 'outgoing' ? 'lgh-btn--primary' : 'lgh-btn--soft' }}"><x-logistics.icon name="truck" :size="16" /> Assign driver</a>
            @endif
        </header>

        @if ($canAssignHere && $riders->isEmpty() && count($items))
            <div class="lgh-callout" role="status">
                <x-logistics.icon name="alert" :size="18" />
                <p>No rider is available to assign. Approve a rider, or set one to active, then come back. <a href="{{ route('lgp.station.riders') }}">Open riders</a></p>
            </div>
        @endif

        @if (count($items))
            @if ($chips)
                <div class="lgh-chips" role="group" aria-label="Filter by status">
                    @foreach ($chips as $key => $text)
                        <button type="button" class="lgh-chip" :class="{ 'is-active': tab === '{{ $key }}' }" :aria-pressed="tab === '{{ $key }}'" @click="tab = '{{ $key }}'">
                            {{ $text }} <span class="lgh-chip__count" x-text="count('{{ $key }}')"></span>
                        </button>
                    @endforeach
                    <button type="button" class="lgh-chip lgh-chip--clear" x-show="searching" x-cloak @click="q = ''"><x-logistics.icon name="x" :size="14" /> Clear search</button>
                </div>
            @endif

            <div class="lgh-toolbar">
                <label class="lgh-search">
                    <span class="lgh-sr">Search parcels</span>
                    <x-logistics.icon name="search" :size="18" />
                    <input type="search" x-model="q" @keydown.escape="q = ''" @keydown.enter.prevent autocomplete="off" maxlength="100" placeholder="Search tracking number, name or address">
                </label>
                <span class="lgh-meta" aria-live="polite" x-text="shown.length + (shown.length === 1 ? ' parcel' : ' parcels')"></span>
            </div>

            <section class="lgh-card lgh-table-card" aria-label="{{ $title }}">
                <div class="lgh-table-wrap" x-show="shown.length" x-cloak>
                    <table class="lgh-table lgh-table--ops">
                        <thead>
                            <tr>
                                <th scope="col">Parcel</th>
                                <th scope="col">{{ $col2 }}</th>
                                <th scope="col">Status</th>
                                @if ($col4)<th scope="col">{{ $col4 }}</th>@endif
                                <th scope="col"><span class="lgh-sr">Action</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(r, i) in shown" :key="mode + r.id + '-' + i">
                                <tr class="lgh-row" @click="go(r, $event)">
                                    <td>
                                        <a :href="track(r)" class="lgh-name" x-text="r.tracking"></a>
                                        <small x-show="r.contact" x-text="r.contact"></small>
                                    </td>
                                    <td data-label="{{ $col2 }}">
                                        @if ($mode === 'tasks')
                                            <span x-text="r.type === 'pickup' ? 'Pickup' : 'Delivery'"></span>
                                            <small x-show="r.address" x-text="r.address"></small>
                                        @elseif (in_array($mode, ['sorting', 'outgoing'], true))
                                            <span x-text="r.next"></span>
                                        @else
                                            <span x-text="r.address || 'Not set'"></span>
                                        @endif
                                    </td>
                                    <td data-label="Status">
                                        <span class="lgh-pill" :class="'lgh-pill--' + tone(r)" x-text="r.status"></span>
                                        <small x-show="mode === 'pickups' && r.pickup_status === 'pending'">Verification pending</small>
                                        <small x-show="r.failure" x-text="r.failure"></small>
                                    </td>
                                    @if ($col4)
                                        <td data-label="{{ $col4 }}">
                                            @if ($mode === 'tasks')
                                                <span x-text="r.rider_name || 'Not assigned'"></span>
                                            @elseif ($mode === 'incoming')
                                                <span x-text="r.arrived || 'Awaiting scan'"></span>
                                            @elseif ($mode === 'sorting')
                                                <span x-text="r.sorted || 'Not sorted'"></span>
                                            @else
                                                <span class="lgh-pill" :class="r.rider ? 'lgh-pill--mint' : (r.can_assign ? 'lgh-pill--butter' : 'lgh-pill--lilac')"
                                                      x-text="r.rider ? 'Rider assigned' : (r.can_assign ? 'Needs a rider' : 'Not ready')"></span>
                                            @endif
                                        </td>
                                    @endif
                                    <td class="lgh-actions">
                                        <div class="lgh-actions__row">
                                            <template x-if="primary(r)">
                                                <button type="button" class="lgh-btn lgh-btn--small" :class="r.rider ? 'lgh-btn--soft' : 'lgh-btn--primary'" :disabled="!riders.length" @click="openAssign(r)"
                                                        :title="riders.length ? '' : 'No rider is available. Approve a rider first.'" :aria-label="(r.rider ? 'Reassign rider for ' : (mode === 'pickups' ? 'Verify and assign ' : 'Assign rider to ')) + r.tracking">
                                                    <span x-text="r.rider ? 'Reassign rider' : (mode === 'pickups' ? 'Verify and assign' : 'Assign rider')"></span>
                                                </button>
                                            </template>
                                            <template x-if="mode === 'pickups' && primary(r) && r.pickup_status === 'pending' && !r.rider">
                                                <button type="button" class="lgh-btn lgh-btn--small lgh-btn--danger" @click="openDecline(r)" :aria-label="'Return ' + r.tracking + ' to the seller'">Return to seller</button>
                                            </template>
                                            <template x-if="!primary(r)">
                                                <a :href="track(r)" class="lgh-btn lgh-btn--small lgh-btn--soft" :aria-label="'Track parcel ' + r.tracking">
                                                    Track parcel <x-logistics.icon name="arrow-right" :size="14" class="lgh-nudge" />
                                                </a>
                                            </template>
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
                    <strong>No parcels match your search</strong>
                    <p>Check the tracking number for typos, or search by name or address.<span x-show="tab !== 'all'"> You are searching in one status only.</span></p>
                    <button type="button" class="lgh-btn lgh-btn--primary" @click="clearFilters()">Clear filters</button>
                </div>

                {{-- Nothing in this status --}}
                <div class="lgh-empty" x-show="!shown.length && !searching && tab !== 'all'" x-cloak role="status">
                    <span class="lgh-empty__icon lgh-tone-lilac"><x-logistics.icon name="{{ $emptyIcon }}" :size="26" /></span>
                    <strong>Nothing in this status</strong>
                    <p>Parcels move here as they reach this step. Pick another status to see the rest.</p>
                    <button type="button" class="lgh-btn lgh-btn--soft" @click="tab = 'all'">Show all parcels</button>
                </div>
            </section>
        @else
            <section class="lgh-card" aria-label="{{ $title }}">
                <div class="lgh-empty" role="status">
                    <span class="lgh-empty__icon lgh-tone-lilac"><x-logistics.icon :name="$emptyIcon" :size="26" /></span>
                    <strong>{{ $emptyTitle }}</strong>
                    <p>{{ $emptyHint }}</p>
                    <a href="{{ $emptyUrl }}" class="lgh-btn lgh-btn--soft">{{ $emptyButton }}</a>
                </div>
            </section>
        @endif

        @if ($mode === 'outgoing' && $trips->isNotEmpty())
            <h2 class="lgh-section">Trips from this station</h2>
            <section class="lgh-card lgh-table-card" aria-label="Trips from this station">
                <div class="lgh-table-wrap">
                    <table class="lgh-table lgh-table--trips-out">
                        <thead><tr><th scope="col">Trip</th><th scope="col">Destination</th><th scope="col">Driver and vehicle</th><th scope="col">Status</th></tr></thead>
                        <tbody>
                            @foreach ($trips as $trip)
                                <tr>
                                    <td><span class="lgh-name">TRP-{{ str_pad((string) $trip->id, 5, '0', STR_PAD_LEFT) }}</span></td>
                                    <td data-label="Destination">{{ $trip->destination?->name ?? 'Not set' }}</td>
                                    <td data-label="Driver and vehicle">{{ $trip->driver?->user?->name ?? 'Not assigned' }}<small>{{ $trip->vehicle?->vehicle_code ?? 'No vehicle' }}</small></td>
                                    <td data-label="Status"><span class="lgh-pill lgh-pill--{{ $tripTone[$trip->status] ?? 'lilac' }}">{{ ucfirst(str_replace('_', ' ', $trip->status)) }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        @if ($canAssignHere)
            {{-- Assign a rider --}}
            <div class="lgh-layer" x-show="assign" x-cloak>
                <div class="lgh-backdrop" @click="assign = null"></div>
                <div class="lgh-dialog lgh-dialog--sm" role="dialog" aria-modal="true" aria-labelledby="assignTitle">
                    <header class="lgh-dialog__head">
                        <div>
                            <h2 id="assignTitle" x-text="assign ? (assign.rider ? 'Reassign ' : 'Assign ') + assign.tracking : 'Assign rider'"></h2>
                            <p x-text="mode === 'pickups' ? 'Assigning also verifies the pickup. The seller is told who is coming.' : 'The rider gets this delivery in the Rider app.'"></p>
                        </div>
                        <button type="button" class="lgh-close" @click="assign = null" aria-label="Close"><svg class="lgh-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg></button>
                    </header>
                    <form method="POST" :action="assign ? assignUrl.replace('__order__', assign.id) : ''" class="lgh-dialog__body lgh-form" novalidate @submit="checkAssign($event)">
                        @csrf
                        <input type="hidden" name="type" value="{{ $mode === 'pickups' ? 'pickup' : 'delivery' }}">
                        <div class="lgh-field">
                            <label for="stationRider">Rider <span aria-hidden="true">*</span></label>
                            <select id="stationRider" name="rider_id" x-model="rider" @change="errors.rider = ''" :aria-invalid="!!errors.rider" aria-describedby="stationRiderErr" :class="{ 'has-error': errors.rider }">
                                <option value="">Choose a rider</option>
                                <template x-for="r in riders" :key="r.id"><option :value="String(r.id)" x-text="r.name + ' · ' + r.vehicle"></option></template>
                            </select>
                            <p class="lgh-err" id="stationRiderErr" x-show="errors.rider" x-cloak x-text="errors.rider" role="alert"></p>
                            <p class="lgh-hint">Only approved, active riders of {{ $station->name }} are listed.</p>
                        </div>
                        <footer class="lgh-dialog__foot">
                            <span class="lgh-spacer"></span>
                            <button type="button" class="lgh-btn lgh-btn--soft" @click="assign = null">Cancel</button>
                            <button type="submit" class="lgh-btn lgh-btn--primary" x-text="assign && assign.rider ? 'Reassign rider' : 'Assign rider'"></button>
                        </footer>
                    </form>
                </div>
            </div>

            {{-- Return a pickup to the seller --}}
            @if ($mode === 'pickups')
                <div class="lgh-layer" x-show="decline" x-cloak style="z-index: 80">
                    <div class="lgh-backdrop" @click="decline = null"></div>
                    <div class="lgh-dialog lgh-dialog--sm" role="dialog" aria-modal="true" aria-labelledby="declineTitle">
                        <header class="lgh-dialog__head">
                            <div>
                                <h2 id="declineTitle" x-text="decline ? 'Return ' + decline.tracking + ' to the seller?' : 'Return to seller'"></h2>
                                <p>Use this when the parcel isn’t ready. The seller gets your reason and marks it ready again.</p>
                            </div>
                            <button type="button" class="lgh-close" @click="decline = null" aria-label="Close"><svg class="lgh-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg></button>
                        </header>
                        <form method="POST" :action="decline ? declineUrl.replace('__order__', decline.id) : ''" class="lgh-dialog__body lgh-form" novalidate @submit="checkDecline($event)">
                            @csrf
                            <div class="lgh-field">
                                <label for="declineReason">Reason for the seller <span aria-hidden="true">*</span></label>
                                <textarea id="declineReason" name="reason" rows="3" maxlength="500" x-model="reason" @input="errors.reason = ''" placeholder="For example: the parcel is not packed or the label is missing"
                                          :aria-invalid="!!errors.reason" aria-describedby="declineReasonErr" :class="{ 'has-error': errors.reason }"></textarea>
                                <p class="lgh-err" id="declineReasonErr" x-show="errors.reason" x-cloak x-text="errors.reason" role="alert"></p>
                            </div>
                            <footer class="lgh-dialog__foot">
                                <span class="lgh-spacer"></span>
                                <button type="button" class="lgh-btn lgh-btn--soft" @click="decline = null">Keep request</button>
                                <button type="submit" class="lgh-btn lgh-btn--danger-solid">Return to seller</button>
                            </footer>
                        </form>
                    </div>
                </div>
            @endif
        @endif
    </div>
</x-logistics.shell>
