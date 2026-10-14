@php
    $isProvince = $role === 'province';
    $where = $isProvince ? 'hub' : 'station';
    // Where a sorted parcel goes next: next hub (province) or delivery zone (station).
    $next = $isProvince ? ['Quezon Hub', 'Batangas Hub', 'Cavite Hub', 'Stay: Laguna stations'] : ['Zone A (Poblacion)', 'Zone B (Bagumbayan)', 'Zone C (Patimbao)'];
    // HARDCODED PREVIEW: replace with parcels at this hub that are waiting to be sorted.
    $parcels = [
        ['code' => 'VND-10482', 'from' => 'Laguna Hub', 'area' => 'Brgy. Poblacion',    'kg' => 2.1],
        ['code' => 'VND-10479', 'from' => 'Laguna Hub', 'area' => 'Brgy. Bagumbayan',  'kg' => 0.8],
        ['code' => 'VND-10477', 'from' => 'Laguna Hub', 'area' => 'Brgy. Patimbao',    'kg' => 5.4],
        ['code' => 'VND-10469', 'from' => 'Quezon Hub', 'area' => 'Brgy. Santo Angel', 'kg' => 1.2],
    ];
@endphp
<x-logistics.shell title="Parcel Sorting" :role="$role">
    <div class="lg-page"
         x-data="{
            parcels: @js($parcels),
            picked: [],
            dest: '',
            get allPicked() { return this.parcels.length > 0 && this.picked.length === this.parcels.length; },
            get kg() { return this.parcels.filter(p => this.picked.includes(p.code)).reduce((n, p) => n + p.kg, 0).toFixed(1); },
            toggleAll(on) { this.picked = on ? this.parcels.map(p => p.code) : []; },
            sort() {
                if (!this.picked.length || !this.dest) return;
                const n = this.picked.length;
                this.parcels = this.parcels.filter(p => !this.picked.includes(p.code));
                this.picked = [];
                this.$dispatch('lg-toast', { type: 'success', message: n + (n === 1 ? ' parcel' : ' parcels') + ' sorted to ' + this.dest + '.' });
                this.dest = '';
            }
         }">
        <div class="lg-page-head">
            <div>
                <h1>Parcel Sorting</h1>
                <p>{{ $isProvince ? 'Group parcels by the next hub they should go to.' : 'Group parcels into delivery zones so each rider gets one area.' }}</p>
            </div>
            <div class="lg-page-head__actions">
                <span class="lg-pill lg-pill--violet" x-text="parcels.length + ' waiting'">{{ count($parcels) }} waiting</span>
            </div>
        </div>

        <section class="lg-card" aria-label="Parcels to sort">
            <div class="lg-table-wrap" x-show="parcels.length">
                <table class="lg-table lg-table--stack lg-table--mid">
                    <thead>
                        <tr>
                            <th style="width:44px">
                                <input type="checkbox" class="lg-check" :checked="allPicked" @change="toggleAll($event.target.checked)"
                                    x-effect="$el.indeterminate = picked.length > 0 && !allPicked" aria-label="Select all parcels">
                            </th>
                            <th>Parcel</th><th>From</th><th>Destination area</th><th class="is-num">Weight</th><th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="p in parcels" :key="p.code">
                            <tr :class="{ 'is-selected': picked.includes(p.code) }">
                                <td class="is-check"><input type="checkbox" class="lg-check" :value="p.code" x-model="picked" :aria-label="'Select ' + p.code"></td>
                                <td data-label="Parcel"><strong x-text="p.code"></strong></td>
                                <td data-label="From" x-text="p.from"></td>
                                <td data-label="Destination area" x-text="p.area"></td>
                                <td data-label="Weight" class="is-num" x-text="p.kg.toFixed(1) + ' kg'"></td>
                                <td data-label="Status"><span class="lg-pill lg-pill--violet">At sorting</span></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <x-logistics.empty x-show="!parcels.length" x-cloak icon="check-circle" title="Everything is sorted" :text="'New parcels that arrive at this '.$where.' will appear here.'">
                <a href="{{ route('lgp.hub.page', [$role, 'incoming']) }}" class="lg-btn lg-btn--outline lg-btn--sm">Go to Incoming Parcels</a>
            </x-logistics.empty>
        </section>

        {{-- Bulk bar: appears once something is selected; the destination is required --}}
        <div class="lg-bulkbar" x-show="picked.length" x-cloak x-transition.opacity.duration.150ms role="region" aria-label="Sort selected parcels">
            <span class="lg-bulkbar__count" x-text="picked.length + ' selected'"></span>
            <span class="lg-bulkbar__meta" x-text="kg + ' kg total'"></span>
            <span class="lg-bulkbar__spacer"></span>
            <label class="sr-only" for="sortDest">{{ $isProvince ? 'Send to' : 'Delivery zone' }}</label>
            <select id="sortDest" class="lg-select" x-model="dest">
                <option value="">{{ $isProvince ? 'Choose next hub' : 'Choose delivery zone' }}</option>
                @foreach ($next as $n)<option>{{ $n }}</option>@endforeach
            </select>
            <button type="button" class="lg-btn" :disabled="!dest" @click="sort()" x-text="'Mark ' + picked.length + ' as sorted'"></button>
            <button type="button" class="lg-btn lg-btn--ghost" @click="toggleAll(false)">Clear</button>
        </div>
    </div>
</x-logistics.shell>
