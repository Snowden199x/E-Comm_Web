@php
    // HARDCODED PREVIEW: replace with this hub's pickup requests and inbound parcels.
    $pickups = [
        ['code' => 'VND-10466', 'seller' => 'Hana Shop',     'area' => 'Brgy. Santo Angel', 'items' => 2],
        ['code' => 'VND-10470', 'seller' => 'Tech Corner',   'area' => 'Brgy. Patimbao',    'items' => 1],
        ['code' => 'VND-10473', 'seller' => 'Bloom Florist', 'area' => 'Brgy. Poblacion',   'items' => 3],
    ];
    $arriving = $role === 'province'
        ? [
            ['code' => 'VND-10501', 'from' => 'Santa Cruz Station', 'to' => 'Quezon Hub', 'state' => 'arrived'],
            ['code' => 'VND-10498', 'from' => 'Pagsanjan Station',  'to' => 'Quezon Hub', 'state' => 'on_the_way'],
        ]
        : [
            ['code' => 'VND-10482', 'from' => 'Laguna Hub', 'to' => 'Brgy. Poblacion',   'state' => 'arrived'],
            ['code' => 'VND-10479', 'from' => 'Laguna Hub', 'to' => 'Brgy. Bagumbayan', 'state' => 'on_the_way'],
        ];
    $riders = ['Jun Bautista', 'Paolo Garcia', 'Nina Castillo'];
@endphp
<x-logistics.shell title="Incoming Parcels" :role="$role">
    <div class="lg-page"
         x-data="{
            pickups: @js(array_map(fn ($p) => $p + ['rider' => '', 'err' => false], $pickups)),
            arriving: @js($arriving),
            open: false,
            target: null,
            reason: '',
            err: false,
            assign(p) {
                if (!p.rider) { p.err = true; return; }
                this.pickups = this.pickups.filter(x => x.code !== p.code);
                this.$dispatch('lg-toast', { type: 'success', message: p.code + ' verified. ' + p.rider + ' will collect it from ' + p.seller + '.' });
            },
            notReady(p) {
                this.target = p; this.reason = ''; this.err = false; this.open = true;
                this.$nextTick(() => this.$refs.reason && this.$refs.reason.focus());
            },
            sendBack() {
                if (this.reason.trim().length < 5) { this.err = true; return; }
                const p = this.target;
                this.pickups = this.pickups.filter(x => x.code !== p.code);
                this.open = false;
                this.$dispatch('lg-toast', { type: 'info', message: p.code + ' sent back to ' + p.seller + ' with your reason. They can mark it ready again.' });
            },
            receive(a) {
                if (a.state !== 'arrived') return;
                a.state = 'received';
                this.$dispatch('lg-toast', { type: 'success', message: a.code + ' received at this hub.' });
            }
         }">
        <div class="lg-page-head">
            <div>
                <h1>Incoming Parcels</h1>
                <p>Verify seller pickups, then confirm parcels as they arrive at this hub.</p>
            </div>
        </div>

        <section class="lg-card" aria-labelledby="pkTitle">
            <div class="lg-card__head">
                <div>
                    <h2 class="lg-section-title" id="pkTitle">Pickup requests <span class="lg-tab__count" x-text="pickups.length">{{ count($pickups) }}</span></h2>
                    <p class="lg-section-sub">Sellers waiting for a rider. Check the package is ready, then choose who collects it.</p>
                </div>
            </div>
            <div class="lg-table-wrap" x-show="pickups.length">
                <table class="lg-table lg-table--stack lg-table--mid">
                    <thead><tr><th>Parcel</th><th>Seller</th><th>Pickup area</th><th class="is-num">Items</th><th>Rider</th><th class="is-actions">Actions</th></tr></thead>
                    <tbody>
                        <template x-for="(p, i) in pickups" :key="p.code">
                            <tr>
                                <td data-label="Parcel"><strong x-text="p.code"></strong></td>
                                <td data-label="Seller" x-text="p.seller"></td>
                                <td data-label="Pickup area" x-text="p.area"></td>
                                <td data-label="Items" class="is-num" x-text="p.items"></td>
                                <td data-label="Rider" style="min-width:200px">
                                    <label class="sr-only" :for="'pk' + i">Rider for <span x-text="p.code"></span></label>
                                    <select :id="'pk' + i" class="lg-select" x-model="p.rider" @change="p.err = false" :aria-invalid="!!p.err">
                                        <option value="">Choose a rider</option>
                                        @foreach ($riders as $r)<option>{{ $r }}</option>@endforeach
                                    </select>
                                    <p class="lg-error" x-show="p.err" x-cloak role="alert" style="margin-top:4px">Choose a rider first.</p>
                                </td>
                                <td data-label="Actions" class="is-actions">
                                    <div class="lg-actions">
                                        <button type="button" class="lg-btn lg-btn--sm" @click="assign(p)">Verify &amp; assign</button>
                                        <button type="button" class="lg-btn lg-btn--outline lg-btn--sm" @click="notReady(p)">Not ready</button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <x-logistics.empty x-show="!pickups.length" x-cloak icon="check-circle" title="No pickup requests" text="When a seller marks an order ready, the request appears here for you to verify." />
        </section>

        <section class="lg-card" aria-labelledby="arTitle">
            <div class="lg-card__head">
                <div>
                    <h2 class="lg-section-title" id="arTitle">Arriving at this hub</h2>
                    <p class="lg-section-sub">Confirm each parcel when it gets here. Parcels still on the way cannot be marked yet.</p>
                </div>
            </div>
            <div class="lg-table-wrap" x-show="arriving.length">
                <table class="lg-table lg-table--stack lg-table--mid">
                    <thead><tr><th>Parcel</th><th>From</th><th>Going to</th><th>Status</th><th class="is-actions">Actions</th></tr></thead>
                    <tbody>
                        <template x-for="a in arriving" :key="a.code">
                            <tr>
                                <td data-label="Parcel"><strong x-text="a.code"></strong></td>
                                <td data-label="From" x-text="a.from"></td>
                                <td data-label="Going to" x-text="a.to"></td>
                                <td data-label="Status">
                                    <span class="lg-pill" :class="{ 'lg-pill--green': a.state === 'arrived', 'lg-pill--blue': a.state === 'on_the_way', 'lg-pill--teal': a.state === 'received' }"
                                          x-text="{ arrived: 'Arrived', on_the_way: 'On the way', received: 'Received' }[a.state]"></span>
                                </td>
                                <td data-label="Actions" class="is-actions">
                                    <button type="button" class="lg-btn lg-btn--sm" :disabled="a.state !== 'arrived'" @click="receive(a)"
                                        :title="a.state === 'on_the_way' ? 'Waiting for the arrival scan' : null"
                                        x-text="a.state === 'received' ? 'Received' : 'Mark arrived'"></button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <x-logistics.empty x-show="!arriving.length" x-cloak icon="inbox" title="Nothing on the way" text="Parcels headed to this hub are listed here with their latest status." />
        </section>

        {{-- Not ready: reason required, parcel goes back to the seller --}}
        <div class="lg-overlay" x-show="open" x-cloak @click.self="open = false" @keydown.escape.window="open = false">
            <div class="lg-modal" role="dialog" aria-modal="true" aria-labelledby="nrTitle">
                <form @submit.prevent="sendBack()" novalidate>
                    <div class="lg-modal__head">
                        <div>
                            <h3 id="nrTitle">Package not ready</h3>
                            <p x-text="target ? target.code + ' goes back to ' + target.seller + ' so they can fix it.' : ''"></p>
                        </div>
                        <button type="button" class="lg-toast__close" @click="open = false" aria-label="Close"><x-logistics.icon name="x" :size="18" /></button>
                    </div>
                    <div class="lg-modal__body lg-modal__body--grid">
                        <div class="lg-field">
                            <label for="nrReason">What needs fixing? <span class="lg-req" aria-hidden="true">*</span></label>
                            <textarea id="nrReason" class="lg-textarea" x-ref="reason" x-model="reason" maxlength="500" rows="3"
                                :aria-invalid="err" aria-describedby="nrErr" placeholder="For example: the box is open and needs to be re-sealed."></textarea>
                            <p class="lg-error" id="nrErr" x-show="err" x-cloak role="alert"><x-logistics.icon name="alert" :size="14" /> Enter at least 5 characters so the seller knows what to fix.</p>
                        </div>
                    </div>
                    <div class="lg-modal__foot">
                        <button type="button" class="lg-btn lg-btn--outline" @click="open = false">Cancel</button>
                        <button type="submit" class="lg-btn">Send back to seller</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-logistics.shell>
