<x-logistics.shell title="Linehaul" role="province">
    <div class="lg-page"
         x-data="{
            tab: 'send',
            outbound: @js(array_map(fn ($o) => $o + ['rider' => '', 'err' => false], $outbound)),
            inbound: @js(array_map(fn ($i) => $i + ['received' => false], $inbound)),
            dispatch(o) {
                if (!o.rider) { o.err = true; return; }
                this.outbound = this.outbound.filter(x => x.to !== o.to);
                this.$dispatch('lg-toast', { type: 'success', message: o.count + ' parcels dispatched to ' + o.to + ' with ' + o.rider + '.' });
            },
            receive(i) {
                if (i.tone !== 'green' || i.received) return;
                i.received = true;
                this.$dispatch('lg-toast', { type: 'success', message: i.count + ' parcels from ' + i.from + ' received. Sort them next.' });
            },
            get waiting() { return this.inbound.filter(i => !i.received).length; }
         }">
        <div class="lg-page-head">
            <div>
                <h1>Linehaul</h1>
                <p>Send sorted parcels to other province hubs, and receive parcels arriving from them.</p>
            </div>
        </div>

        <nav class="lg-tabs" aria-label="Linehaul">
            <button type="button" class="lg-tab" :aria-pressed="tab === 'send'" @click="tab = 'send'">Ready to send <span class="lg-tab__count" x-text="outbound.length">{{ count($outbound) }}</span></button>
            <button type="button" class="lg-tab" :aria-pressed="tab === 'receive'" @click="tab = 'receive'">Arriving <span class="lg-tab__count" x-text="waiting">{{ count($inbound) }}</span></button>
        </nav>

        <section class="lg-card" x-show="tab === 'send'" aria-label="Ready to send">
            <div class="lg-table-wrap" x-show="outbound.length">
                <table class="lg-table lg-table--stack lg-table--mid">
                    <thead><tr><th>Destination hub</th><th class="is-num">Parcels</th><th>Truck rider</th><th class="is-actions">Actions</th></tr></thead>
                    <tbody>
                        <template x-for="(o, idx) in outbound" :key="o.to">
                            <tr>
                                <td data-label="Destination hub"><div class="lg-cell-title" x-text="o.to"></div><div class="lg-cell-sub" x-text="o.via"></div></td>
                                <td data-label="Parcels" class="is-num" x-text="o.count"></td>
                                <td data-label="Truck rider" style="min-width:220px">
                                    <label class="sr-only" :for="'rider-' + idx">Truck rider for <span x-text="o.to"></span></label>
                                    <select :id="'rider-' + idx" class="lg-select" x-model="o.rider" @change="o.err = false" :aria-invalid="o.err">
                                        <option value="">Choose a truck rider</option>
                                        @foreach ($truckRiders as $t)<option>{{ $t }}</option>@endforeach
                                    </select>
                                    <p class="lg-error" x-show="o.err" x-cloak role="alert" style="margin-top:4px">Choose a truck rider first.</p>
                                </td>
                                <td data-label="Actions" class="is-actions"><button type="button" class="lg-btn lg-btn--sm" @click="dispatch(o)"><x-logistics.icon name="truck" :size="16" /> Dispatch</button></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <x-logistics.empty x-show="!outbound.length" x-cloak icon="truck" title="Nothing to send" text="Sorted parcels bound for other hubs appear here, grouped by destination.">
                <a href="{{ route('lgp.hub.page', ['province', 'sorting']) }}" class="lg-btn lg-btn--outline lg-btn--sm">Go to Parcel Sorting</a>
            </x-logistics.empty>
        </section>

        <section class="lg-card" x-show="tab === 'receive'" x-cloak aria-label="Arriving">
            <div class="lg-table-wrap" x-show="inbound.length">
                <table class="lg-table lg-table--stack lg-table--mid">
                    <thead><tr><th>From hub</th><th class="is-num">Parcels</th><th>Truck rider</th><th>ETA</th><th class="is-actions">Actions</th></tr></thead>
                    <tbody>
                        <template x-for="i in inbound" :key="i.from">
                            <tr>
                                <td data-label="From hub"><div class="lg-cell-title" x-text="i.from"></div></td>
                                <td data-label="Parcels" class="is-num" x-text="i.count"></td>
                                <td data-label="Truck rider" x-text="i.rider"></td>
                                <td data-label="ETA"><span class="lg-pill" :class="'lg-pill--' + (i.received ? 'teal' : i.tone)" x-text="i.received ? 'Received' : i.eta"></span></td>
                                <td data-label="Actions" class="is-actions">
                                    <button type="button" class="lg-btn lg-btn--sm" :disabled="i.tone !== 'green' || i.received" @click="receive(i)"
                                        :title="i.tone !== 'green' ? 'Available when the truck arrives' : null" x-text="i.received ? 'Received' : 'Receive'"></button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <x-logistics.empty x-show="!inbound.length" x-cloak icon="inbox" title="No trucks on the way" text="Trucks heading to this hub are listed here with their arrival time." />
        </section>
    </div>
</x-logistics.shell>
