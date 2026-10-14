@php
    // HARDCODED PREVIEW: replace with this station's approved riders and sorted parcels.
    // 'cap' is a demo capacity so the load bars mean something; the real limit is not defined yet.
    $riders = [
        ['id' => 1, 'name' => 'Jun Bautista',  'load' => 14],
        ['id' => 2, 'name' => 'Paolo Garcia',  'load' => 12],
        ['id' => 3, 'name' => 'Nina Castillo', 'load' => 9],
    ];
    $parcels = [
        ['code' => 'VND-10482', 'area' => 'Brgy. Poblacion',   'zone' => 'Zone A', 'kg' => 2.1],
        ['code' => 'VND-10479', 'area' => 'Brgy. Bagumbayan', 'zone' => 'Zone B', 'kg' => 0.8],
        ['code' => 'VND-10477', 'area' => 'Brgy. Patimbao',   'zone' => 'Zone C', 'kg' => 5.4],
        ['code' => 'VND-10484', 'area' => 'Brgy. Poblacion',   'zone' => 'Zone A', 'kg' => 1.5],
    ];
@endphp
<x-logistics.shell title="Delivery Assignments" role="station">
    <div class="lg-page"
         x-data="{
            cap: 16,
            riders: @js($riders),
            parcels: @js($parcels),
            zone: 'all',
            picked: [],
            rider: '',
            get zones() { return [...new Set(this.parcels.map(p => p.zone))].sort(); },
            get shown() { return this.parcels.filter(p => this.zone === 'all' || p.zone === this.zone); },
            get shownPicked() { return this.shown.filter(p => this.picked.includes(p.code)); },
            get allPicked() { return this.shown.length > 0 && this.shownPicked.length === this.shown.length; },
            get chosen() { return this.riders.find(r => String(r.id) === String(this.rider)); },
            get room() { return this.chosen ? this.cap - this.chosen.load : 0; },
            get tooMany() { return !!this.chosen && this.picked.length > this.room; },
            pct(r) { return Math.min(100, Math.round(r.load / this.cap * 100)); },
            tone(r) { return r.load >= this.cap ? 'danger' : (r.load / this.cap >= 0.8 ? 'warn' : 'ok'); },
            setZone(z) { this.zone = z; this.picked = this.picked.filter(c => this.shown.some(p => p.code === c)); },
            toggleAll(on) { this.picked = on ? this.shown.map(p => p.code) : []; },
            assign() {
                if (!this.chosen || !this.picked.length || this.tooMany) return;
                const n = this.picked.length, r = this.chosen;
                r.load += n;
                this.parcels = this.parcels.filter(p => !this.picked.includes(p.code));
                this.picked = [];
                this.rider = '';
                if (this.zone !== 'all' && !this.zones.includes(this.zone)) this.zone = 'all';
                this.$dispatch('lg-toast', { type: 'success', message: n + (n === 1 ? ' parcel' : ' parcels') + ' assigned to ' + r.name + '.' });
            }
         }">
        <div class="lg-page-head">
            <div>
                <h1>Delivery Assignments</h1>
                <p>Give sorted parcels to a rider. Tick the parcels, choose one rider, and assign. Keep one area with one rider where you can.</p>
            </div>
        </div>

        <section aria-labelledby="loadTitle">
            <h2 class="lg-section-title" id="loadTitle" style="margin-bottom:12px">Rider load today</h2>
            <div class="lg-grid" style="grid-template-columns:repeat(auto-fit,minmax(260px,1fr))">
                <template x-for="r in riders" :key="r.id">
                    <div class="lg-card"><div class="lg-card__body">
                        <div class="lg-person">
                            <span class="lg-avatar" aria-hidden="true" x-text="r.name.charAt(0)"></span>
                            <span><strong x-text="r.name"></strong><small x-text="r.load + ' of ' + cap + ' parcels'"></small></span>
                            <span class="lg-pill" style="margin-left:auto" :class="{ 'lg-pill--green': tone(r) === 'ok', 'lg-pill--amber': tone(r) === 'warn', 'lg-pill--red': tone(r) === 'danger' }"
                                  x-text="tone(r) === 'ok' ? 'Has room' : (tone(r) === 'warn' ? 'Near limit' : 'Full')"></span>
                        </div>
                        <div class="lg-meter" :class="'lg-meter--' + tone(r)" style="margin-top:14px" role="progressbar" aria-valuemin="0" :aria-valuemax="cap" :aria-valuenow="r.load" :aria-label="r.name + ' load'">
                            <span :style="'--w:' + pct(r) + '%'"></span>
                        </div>
                    </div></div>
                </template>
            </div>
        </section>

        <div class="lg-tabs" role="group" aria-label="Filter by zone" x-show="parcels.length">
            <button type="button" class="lg-tab" :aria-pressed="zone === 'all'" @click="setZone('all')">All zones <span class="lg-tab__count" x-text="parcels.length"></span></button>
            <template x-for="z in zones" :key="z">
                <button type="button" class="lg-tab" :aria-pressed="zone === z" @click="setZone(z)">
                    <span x-text="z"></span> <span class="lg-tab__count" x-text="parcels.filter(p => p.zone === z).length"></span>
                </button>
            </template>
        </div>

        <section class="lg-card" aria-label="Parcels to assign">
            <div class="lg-table-wrap" x-show="shown.length">
                <table class="lg-table lg-table--stack lg-table--mid">
                    <thead>
                        <tr>
                            <th style="width:44px"><input type="checkbox" class="lg-check" :checked="allPicked" @change="toggleAll($event.target.checked)"
                                x-effect="$el.indeterminate = shownPicked.length > 0 && !allPicked" aria-label="Select all shown parcels"></th>
                            <th>Parcel</th><th>Delivery area</th><th>Zone</th><th class="is-num">Weight</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="p in shown" :key="p.code">
                            <tr :class="{ 'is-selected': picked.includes(p.code) }">
                                <td class="is-check"><input type="checkbox" class="lg-check" :value="p.code" x-model="picked" :aria-label="'Select ' + p.code"></td>
                                <td data-label="Parcel"><strong x-text="p.code"></strong></td>
                                <td data-label="Delivery area" x-text="p.area"></td>
                                <td data-label="Zone"><span class="lg-pill lg-pill--teal" x-text="p.zone"></span></td>
                                <td data-label="Weight" class="is-num" x-text="p.kg.toFixed(1) + ' kg'"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <x-logistics.empty x-show="!parcels.length" x-cloak icon="check-circle" title="All parcels are assigned" text="Sorted parcels waiting for a rider appear here." >
                <a href="{{ route('lgp.hub.page', ['station', 'monitoring']) }}" class="lg-btn lg-btn--outline lg-btn--sm">Watch deliveries</a>
            </x-logistics.empty>
        </section>

        <div class="lg-bulkbar" x-show="picked.length" x-cloak x-transition.opacity.duration.150ms role="region" aria-label="Assign selected parcels">
            <span class="lg-bulkbar__count" x-text="picked.length + ' selected'"></span>
            <span class="lg-bulkbar__meta" x-show="tooMany" x-cloak x-text="chosen ? chosen.name + ' only has room for ' + room + '. Pick fewer parcels or another rider.' : ''"></span>
            <span class="lg-bulkbar__spacer"></span>
            <label class="sr-only" for="asRider">Rider</label>
            <select id="asRider" class="lg-select" x-model="rider">
                <option value="">Choose a rider</option>
                <template x-for="r in riders" :key="r.id">
                    <option :value="r.id" :disabled="r.load >= cap" x-text="r.name + ' (' + r.load + '/' + cap + ')'"></option>
                </template>
            </select>
            <button type="button" class="lg-btn" :disabled="!chosen || tooMany" @click="assign()" x-text="'Assign ' + picked.length"></button>
            <button type="button" class="lg-btn lg-btn--ghost" @click="toggleAll(false)">Clear</button>
        </div>
    </div>
</x-logistics.shell>
