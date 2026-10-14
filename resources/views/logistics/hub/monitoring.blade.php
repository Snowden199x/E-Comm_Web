@php
    // HARDCODED PREVIEW: replace with this hub's active and recent deliveries.
    $rows = [
        ['code' => 'VND-10440', 'buyer' => 'Ana Lim',   'area' => 'Brgy. Poblacion',    'rider' => 'Jun Bautista',  'status' => 'out',    'scan' => '10:42 AM, left hub'],
        ['code' => 'VND-10441', 'buyer' => 'Ben Cruz',  'area' => 'Brgy. Bagumbayan',  'rider' => 'Paolo Garcia',  'status' => 'out',    'scan' => '10:55 AM, near drop-off'],
        ['code' => 'VND-10433', 'buyer' => 'Cara Diaz', 'area' => 'Brgy. Patimbao',    'rider' => 'Nina Castillo', 'status' => 'done',   'scan' => '9:30 AM, signed'],
        ['code' => 'VND-10429', 'buyer' => 'Dan Ortiz', 'area' => 'Brgy. Santo Angel', 'rider' => 'Jun Bautista',  'status' => 'failed', 'scan' => '9:05 AM, no one home'],
        ['code' => 'VND-10421', 'buyer' => 'Eve Ramos', 'area' => 'Brgy. Poblacion',    'rider' => 'Paolo Garcia',  'status' => 'done',   'scan' => '8:48 AM, signed'],
    ];
    $riders = ['Jun Bautista', 'Paolo Garcia', 'Nina Castillo'];
@endphp
<x-logistics.shell title="Delivery Monitoring" :role="$role">
    <div class="lg-page"
         x-data="{
            rows: @js($rows),
            status: 'all',
            rider: 'all',
            q: '',
            label: { out: 'Out for delivery', done: 'Delivered', failed: 'Delivery failed', retry: 'Retry scheduled', returning: 'Returning to seller' },
            tone: { out: 'brand', done: 'green', failed: 'red', retry: 'amber', returning: 'gray' },
            get shown() {
                const q = this.q.trim().toLowerCase();
                return this.rows.filter(r =>
                    (this.status === 'all' || r.status === this.status) &&
                    (this.rider === 'all' || r.rider === this.rider) &&
                    (!q || r.code.toLowerCase().includes(q) || r.buyer.toLowerCase().includes(q)));
            },
            n(s) { return this.rows.filter(r => r.status === s).length; },
            clear() { this.status = 'all'; this.rider = 'all'; this.q = ''; },
            retry(r) { r.status = 'retry'; r.scan = 'Just now, retry scheduled'; this.$dispatch('lg-toast', { type: 'success', message: r.code + ' will be delivered again.' }); },
            giveBack(r) { r.status = 'returning'; r.scan = 'Just now, returning to seller'; this.$dispatch('lg-toast', { type: 'info', message: r.code + ' is going back to the seller.' }); }
         }">
        <div class="lg-page-head">
            <div>
                <h1>Delivery Monitoring</h1>
                <p>Watch parcels go out and come back. Failed deliveries need a decision.</p>
            </div>
        </div>

        <div class="lg-note lg-note--warn" x-show="n('failed') > 0" role="status">
            <x-logistics.icon name="alert" :size="18" />
            <span><strong x-text="n('failed')"></strong> failed <span x-text="n('failed') === 1 ? 'delivery needs' : 'deliveries need'"></span> a decision. Retry it or send it back to the seller.</span>
        </div>

        <div class="lg-tabs" role="group" aria-label="Filter by status">
            <button type="button" class="lg-tab" :aria-pressed="status === 'all'" @click="status = 'all'">All <span class="lg-tab__count" x-text="rows.length"></span></button>
            <button type="button" class="lg-tab" :aria-pressed="status === 'out'" @click="status = 'out'">Out for delivery <span class="lg-tab__count" x-text="n('out')"></span></button>
            <button type="button" class="lg-tab" :aria-pressed="status === 'done'" @click="status = 'done'">Delivered <span class="lg-tab__count" x-text="n('done')"></span></button>
            <button type="button" class="lg-tab" :aria-pressed="status === 'failed'" @click="status = 'failed'">Failed <span class="lg-tab__count" x-text="n('failed')"></span></button>
        </div>

        <div class="lg-toolbar">
            <div class="lg-search">
                <x-logistics.icon name="search" :size="18" />
                <input type="search" class="lg-input" x-model="q" placeholder="Search parcel code or buyer" aria-label="Search parcel code or buyer">
            </div>
            <select class="lg-select" x-model="rider" aria-label="Filter by rider">
                <option value="all">All riders</option>
                @foreach ($riders as $r)<option>{{ $r }}</option>@endforeach
            </select>
            <span class="lg-toolbar__meta" aria-live="polite" x-text="shown.length + (shown.length === 1 ? ' parcel' : ' parcels')"></span>
        </div>

        <section class="lg-card" aria-label="Deliveries">
            <div class="lg-table-wrap" x-show="shown.length">
                <table class="lg-table lg-table--stack lg-table--mid">
                    <thead><tr><th>Parcel</th><th>Buyer</th><th>Area</th><th>Rider</th><th>Status</th><th>Last scan</th><th class="is-actions">Actions</th></tr></thead>
                    <tbody>
                        <template x-for="r in shown" :key="r.code">
                            <tr>
                                <td data-label="Parcel"><strong x-text="r.code"></strong></td>
                                <td data-label="Buyer" x-text="r.buyer"></td>
                                <td data-label="Area" x-text="r.area"></td>
                                <td data-label="Rider" x-text="r.rider"></td>
                                <td data-label="Status"><span class="lg-pill" :class="'lg-pill--' + tone[r.status]" x-text="label[r.status]"></span></td>
                                <td data-label="Last scan" x-text="r.scan"></td>
                                <td data-label="Actions" class="is-actions">
                                    <div class="lg-actions" x-show="r.status === 'failed'">
                                        <button type="button" class="lg-btn lg-btn--sm" @click="retry(r)"><x-logistics.icon name="refresh" :size="16" /> Retry</button>
                                        <button type="button" class="lg-btn lg-btn--outline lg-btn--sm" @click="giveBack(r)"><x-logistics.icon name="undo" :size="16" /> Return to seller</button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <x-logistics.empty x-show="!shown.length" x-cloak icon="search" title="No parcels match" text="Try a different status, rider, or search.">
                <button type="button" class="lg-btn lg-btn--outline lg-btn--sm" @click="clear()">Clear filters</button>
            </x-logistics.empty>
        </section>
    </div>
</x-logistics.shell>
