<x-logistics.shell title="Linehaul" role="province">
    <div class="lg-page" x-data="{ tab: 'send' }">
        <div class="lg-page-head">
            <div>
                <h1>Linehaul</h1>
                <p>Send sorted parcels to other province hubs, and receive parcels arriving from them.</p>
            </div>
        </div>

        <nav class="lg-tabs" aria-label="Linehaul">
            <button type="button" class="lg-tab" :aria-current="tab === 'send' ? 'page' : null" @click="tab = 'send'">Ready to send <span class="lg-tab__count">{{ count($outbound) }}</span></button>
            <button type="button" class="lg-tab" :aria-current="tab === 'receive' ? 'page' : null" @click="tab = 'receive'">Arriving <span class="lg-tab__count">{{ count($inbound) }}</span></button>
        </nav>

        <section class="lg-card" x-show="tab === 'send'" aria-label="Ready to send">
            <div class="lg-table-wrap">
                <table class="lg-table">
                    <thead><tr><th>Destination hub</th><th class="is-num">Parcels</th><th>Truck rider</th><th></th></tr></thead>
                    <tbody>
                    @foreach ($outbound as $o)
                        <tr>
                            <td><div class="lg-cell-title">{{ $o['to'] }}</div><div class="lg-cell-sub">{{ $o['via'] }}</div></td>
                            <td class="is-num">{{ $o['count'] }}</td>
                            <td>
                                <label class="sr-only" for="rider-{{ $loop->index }}">Truck rider</label>
                                <select id="rider-{{ $loop->index }}" class="lg-select">
                                    @foreach ($truckRiders as $t)<option>{{ $t }}</option>@endforeach
                                </select>
                            </td>
                            <td><button type="button" class="lg-btn lg-btn--sm">Dispatch</button></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <section class="lg-card" x-show="tab === 'receive'" x-cloak aria-label="Arriving">
            <div class="lg-table-wrap">
                <table class="lg-table">
                    <thead><tr><th>From hub</th><th class="is-num">Parcels</th><th>Truck rider</th><th>ETA</th><th></th></tr></thead>
                    <tbody>
                    @foreach ($inbound as $i)
                        <tr>
                            <td><div class="lg-cell-title">{{ $i['from'] }}</div></td>
                            <td class="is-num">{{ $i['count'] }}</td>
                            <td>{{ $i['rider'] }}</td>
                            <td><span class="lg-pill lg-pill--{{ $i['tone'] }}">{{ $i['eta'] }}</span></td>
                            <td><button type="button" class="lg-btn lg-btn--sm">Receive</button></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-logistics.shell>