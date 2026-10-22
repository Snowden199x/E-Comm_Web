{{-- PREVIEW ONLY: hard-coded data from routes/logistics_preview.php. Spec section 8 (Station Hub > Incoming Parcels), 6B and 7.
     The Station Hub only RECEIVES and scans parcels here. Sorting is the next page. Parcels come from two sources: seller pickups
     brought in by riders, and the Province Hub transfer trip. A transfer's parcels cannot be received until the trip has departed.
     Interactions change page state only. --}}
@php
    $tripTime = \Carbon\Carbon::parse($trip['scheduled'])->format('g:i A');
@endphp
<x-logistics.shell title="Incoming Parcels" role="station">
    <div class="lg-page"
         x-data="{
            parcels: @js($parcels),
            tab: 'riders',
            q: '',
            scan: '',
            scanMsg: '',
            scanOk: true,
            limit: 25,
            picked: [],
            tabs: {
                riders: p => p.kind === 'rider' && p.stage === 'with_rider',
                transfers: p => p.kind === 'transfer' && p.stage !== 'received',
                received: p => p.stage === 'awaiting_sorting' || p.stage === 'sorted' || p.stage === 'received'
            },
            helps: {
                riders: 'Seller parcels a rider has picked up. Scan each one when the rider drops it off. After the scan it goes to Sorting.',
                transfers: 'Parcels on a Province Hub transfer trip. They can be received once the driver has left the Province Hub.',
                received: 'Parcels this station has scanned in today. Newest first.'
            },
            stageLabels: { with_rider: 'With rider', scheduled: 'Trip not departed', awaiting_sorting: 'Awaiting sorting', sorted: 'Sorted', received: 'Awaiting sorting' },
            stageTones: { with_rider: 'lg-pill--blue', scheduled: 'lg-pill--amber', awaiting_sorting: 'lg-pill--violet', sorted: 'lg-pill--green', received: 'lg-pill--violet' },
            tripDeparted: false,
            tabParcels() {
                const list = this.parcels.filter(this.tabs[this.tab]);
                return this.tab === 'received' ? list.sort((a, b) => b.rcv - a.rcv) : list;
            },
            get matched() {
                const s = this.q.trim().toLowerCase();
                return this.tabParcels().filter(p => !s || [p.code, p.seller, p.rider, p.barangay, p.area, p.from, p.trip].some(v => v && v.toLowerCase().includes(s)));
            },
            get shown() { return this.matched.slice(0, this.limit); },
            count(k) { return this.parcels.filter(this.tabs[k]).length; },
            get canReceive() { return this.tab === 'riders'; },
            isPicked(p) { return this.picked.includes(p.code); },
            toggle(p) { this.isPicked(p) ? this.picked = this.picked.filter(c => c !== p.code) : this.picked.push(p.code); },
            get allPicked() { return this.matched.length > 0 && this.matched.every(p => this.isPicked(p)); },
            toggleAll() { this.picked = this.allPicked ? [] : this.matched.map(p => p.code); },
            setTab(k) { this.tab = k; this.q = ''; this.limit = 25; this.picked = []; },
            receive(codes) {
                const now = new Date();
                const time = now.toLocaleTimeString('en-PH', { hour: 'numeric', minute: '2-digit' });
                let n = 0;
                this.parcels.forEach(p => {
                    if (codes.includes(p.code) && p.stage === 'with_rider') {
                        p.stage = 'awaiting_sorting'; p.time = time; p.rcv = 200 + this.parcels.filter(x => x.rcv > 150).length; n++;
                    }
                });
                this.picked = [];
                return n;
            },
            receiveSelected(codes) {
                const n = this.receive(codes);
                if (n) this.$dispatch('lg-toast', { type: 'success', message: n + (n === 1 ? ' parcel' : ' parcels') + ' received. ' + (n === 1 ? 'It is' : 'They are') + ' now in Sorting.' });
            },
            submitScan() {
                const code = this.scan.trim().toUpperCase();
                if (!code) return;
                const p = this.parcels.find(x => x.code === code);
                this.scan = '';
                if (!p) { this.scanOk = false; this.scanMsg = code + ' is not on its way to this station. Check the number on the label.'; return; }
                if (p.stage === 'awaiting_sorting' || p.stage === 'sorted') { this.scanOk = false; this.scanMsg = code + ' was already received today.'; return; }
                if (p.stage === 'scheduled') { this.scanOk = false; this.scanMsg = code + ' is on trip ' + p.trip + ', which has not left ' + @js($from) + ' yet. Receive it after the driver departs.'; return; }
                this.receive([code]);
                this.scanOk = true; this.scanMsg = code + ' received from ' + p.rider + '. It is now in Sorting.';
            }
         }">
        <div class="lg-page-head">
            <div>
                <h1>Incoming Parcels</h1>
                <p>Parcels arriving at this station from riders and from {{ $from }}. Scan each one to receive it. Sorting comes next.</p>
            </div>
            <div class="lg-page-head__actions">
                <a href="{{ route('lgp.station.sorting') }}" class="lg-btn lg-btn--outline"><x-logistics.icon name="layers" :size="18" /> Sorting</a>
            </div>
        </div>

        <section class="lg-card" aria-labelledby="scanTitle">
            <div class="lg-card__head">
                <div>
                    <h2 class="lg-section-title" id="scanTitle">Receive by tracking number</h2>
                    <p class="lg-section-sub">Scan the label or type the number, then press Enter.</p>
                </div>
            </div>
            <form @submit.prevent="submitScan()" class="lg-toolbar" novalidate>
                <div class="lg-search">
                    <x-logistics.icon name="package" :size="18" />
                    <input type="text" class="lg-input" x-model="scan" placeholder="VND-10501" autocomplete="off" aria-label="Tracking number to receive" aria-describedby="scanResult">
                </div>
                <button type="submit" class="lg-btn" :disabled="!scan.trim()">Receive</button>
            </form>
            <p id="scanResult" class="lg-hint" role="status" aria-live="polite" x-show="scanMsg" x-cloak
               :style="'margin-top:10px;color:' + (scanOk ? 'inherit' : 'var(--lg-red, #b42318)')" x-text="scanMsg"></p>
        </section>

        <div class="lg-note">
            <x-logistics.icon name="truck" :size="18" />
            <span>Transfer <strong>{{ $trip['id'] }}</strong> is scheduled to leave {{ $from }} at {{ $tripTime }} with {{ number_format($trip['parcels']) }} parcels. Only the Province Hub starts, changes or cancels trips. This station receives what arrives.</span>
        </div>

        <nav class="lg-tabs" aria-label="Incoming parcels">
            @foreach (['riders' => 'From riders', 'transfers' => 'From '.$from, 'received' => 'Received today'] as $k => $label)
                <button type="button" class="lg-tab" :aria-pressed="tab === '{{ $k }}'" @click="setTab('{{ $k }}')">
                    {{ $label }} <span class="lg-tab__count" x-text="count('{{ $k }}')"></span>
                </button>
            @endforeach
        </nav>

        <p class="lg-section-sub" x-text="helps[tab]"></p>

        <div class="lg-toolbar">
            <div class="lg-search">
                <x-logistics.icon name="search" :size="18" />
                <input type="search" class="lg-input" x-model="q" @input="limit = 25" placeholder="Tracking number, seller, rider or area" aria-label="Search incoming parcels">
            </div>
            <button type="button" class="lg-btn lg-btn--sm" x-show="canReceive" x-cloak :disabled="!picked.length" @click="receiveSelected(picked)"
                    x-text="'Receive' + (picked.length ? ' (' + picked.length + ')' : ' selected')"></button>
            <span class="lg-toolbar__meta" aria-live="polite" x-text="matched.length + (matched.length === 1 ? ' parcel' : ' parcels')"></span>
        </div>

        <section class="lg-card" aria-label="Parcels">
            <div class="lg-table-wrap" x-show="matched.length">
                <table class="lg-table lg-table--stack lg-table--mid">
                    <thead>
                        <tr>
                            <th x-show="canReceive" style="width:44px"><input type="checkbox" class="lg-check" :checked="allPicked" @change="toggleAll()" aria-label="Select all parcels in this list"></th>
                            <th>Parcel</th>
                            <th x-text="tab === 'transfers' ? 'Came from' : 'Brought by'"></th>
                            <th x-text="tab === 'transfers' ? 'Trip' : 'Seller'"></th>
                            <th>Status</th>
                            <th x-text="tab === 'received' ? 'Received' : 'Picked up'" x-show="tab !== 'transfers'"></th>
                            <th class="is-actions" x-show="canReceive">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="p in shown" :key="p.code">
                            <tr :class="{ 'is-selected': isPicked(p) }">
                                <td x-show="canReceive" class="is-check"><input type="checkbox" class="lg-check" :checked="isPicked(p)" @change="toggle(p)" :aria-label="'Select ' + p.code"></td>
                                <td data-label="Parcel">
                                    <div class="lg-cell-title" x-text="p.code"></div>
                                    <div class="lg-cell-sub" x-text="p.kind === 'transfer' ? 'For ' + p.area : (p.barangay ? 'Brgy. ' + p.barangay : '')" x-show="p.kind === 'transfer' || p.barangay"></div>
                                </td>
                                <td data-label="Source">
                                    <template x-if="p.kind === 'rider'">
                                        <div class="lg-person">
                                            <span class="lg-avatar lg-avatar--sm" aria-hidden="true" x-text="p.rider.charAt(0)"></span>
                                            <span><strong x-text="p.rider"></strong><small x-text="p.vehicle" x-show="p.vehicle"></small></span>
                                        </div>
                                    </template>
                                    <span x-show="p.kind === 'transfer'" x-text="p.from"></span>
                                </td>
                                <td data-label="Seller or trip">
                                    <span x-show="p.kind === 'rider'" x-text="p.seller"></span>
                                    <span x-show="p.kind === 'transfer'" x-text="p.trip"></span>
                                    <div class="lg-cell-sub" x-show="p.street" x-text="p.street"></div>
                                </td>
                                <td data-label="Status"><span class="lg-pill" :class="stageTones[p.stage]" x-text="stageLabels[p.stage]"></span></td>
                                <td data-label="Time" x-show="tab !== 'transfers'">
                                    <span x-text="p.time"></span>
                                    <div class="lg-cell-sub" x-show="p.ago" x-text="p.ago"></div>
                                </td>
                                <td data-label="Action" class="is-actions" x-show="canReceive">
                                    <div class="lg-actions">
                                        <button type="button" class="lg-btn lg-btn--outline lg-btn--sm" @click="receiveSelected([p.code])" :aria-label="'Receive ' + p.code">Receive</button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
                <div class="lg-toolbar" style="justify-content:center;padding:12px" x-show="matched.length > limit">
                    <button type="button" class="lg-btn lg-btn--outline lg-btn--sm" @click="limit += 25" x-text="'Show 25 more (' + (matched.length - limit) + ' left)'"></button>
                </div>
            </div>

            <x-logistics.empty x-show="!matched.length && !q.trim() && tab === 'riders'" x-cloak icon="check-circle" title="No parcels with riders" text="Parcels riders pick up from sellers show up here until you scan them in.">
                <a href="{{ route('lgp.station.pickups') }}" class="lg-btn lg-btn--outline lg-btn--sm">Open pickup requests</a>
            </x-logistics.empty>
            <x-logistics.empty x-show="!matched.length && !q.trim() && tab === 'transfers'" x-cloak icon="truck" title="No transfers on their way" text="Parcels the Province Hub sends to this station appear here once a trip is scheduled." />
            <x-logistics.empty x-show="!matched.length && !q.trim() && tab === 'received'" x-cloak icon="inbox" title="Nothing received yet today" text="Parcels you scan in are listed here." />
            <x-logistics.empty x-show="!matched.length && q.trim()" x-cloak icon="search" title="No parcels match" text="Check the spelling, or look in another tab.">
                <button type="button" class="lg-btn lg-btn--outline lg-btn--sm" @click="q = ''">Clear search</button>
            </x-logistics.empty>
        </section>
    </div>
</x-logistics.shell>
