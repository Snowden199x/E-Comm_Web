{{--
    Station Hub > Cash Collections (spec section 9). COD cash flows up: Rider, Station Hub, Province Hub, Company.
    Here the Station Hub confirms the cash each Rider hands in, then sends the verified cash to its Province Hub.
    Fed by StationCashController::index. Posts go to ::receive and ::remit. Discrepancies are resolved on their own page.
    Confirmations only: no real payment is made.
    Look: the seller pastel lilac system, the same .lgc layer as Company and Province Hub > Cash. Styles: cash-theme.css plus
    station-cash-theme.css (callout tone, parcel pick list), pushed last; dialogs carry their own copy of the tokens.
    Status colors: Pending = butter (waiting), Discrepancy = rose, Confirmed = mint, sent and awaiting = sky tile.
    The rider list is short, so chips with counts and search run in the browser. Receipts and sent batches are the latest 10 each.
    One click on a rider with cash opens Receive cash. Loading: shell skeleton on open, spinner on the submit buttons.
    Errors: top banner, a message under each field, and the dialog reopens with what was typed (hidden _dialog field).
--}}
@php
    $statusLabels = \App\Models\Finance\CashRemittance::STATUSES;
    $money = fn ($value) => '₱'.number_format((float) $value, 2);
    $tone = ['pending' => 'butter', 'discrepancy' => 'rose', 'received' => 'mint', 'resolved' => 'mint'];
    $provinceName = $province?->name ?? 'your Province Hub';

    $readyCount = (int) $ready->parcels;
    $readyAmount = (float) $ready->total;
    $heldAmount = array_sum(array_column($ledger, 'on_hand'));
    $discrepancyUrl = route('lgp.station.cash.discrepancies');

    // One entry per rider, keyed by the courier id the receive route needs.
    $rows = collect($ledger)->mapWithKeys(fn ($row) => [(string) $row['courier_id'] => [
        'id' => (string) $row['courier_id'],
        'name' => $row['name'],
        'onHand' => (float) $row['on_hand'],
        'disputed' => (float) $row['disputed'],
        'parcels' => collect($row['parcels'])->map(fn ($p) => [
            'id' => (string) $p['id'],
            'tracking' => $p['tracking'],
            'amount' => (float) $p['amount'],
            'at' => $p['collected_at'] ? \Illuminate\Support\Carbon::parse($p['collected_at'])->format('M j, g:i A') : 'Not recorded',
        ])->values()->all(),
        'url' => route('lgp.station.cash.receive', $row['courier_id']),
    ]])->all();

    $dialogOld = (string) old('_dialog', '');

    // [label, value, icon, tone]
    $tiles = [
        ['Held by riders', $money($heldAmount), 'bike', 'butter'],
        ['Verified, ready to send', $money($readyAmount), 'check-circle', 'mint'],
        ['Open discrepancies', number_format($openDiscrepancies), 'alert', $openDiscrepancies > 0 ? 'rose' : 'lilac'],
        ['Sent, awaiting Province', $money($awaitingProvince), 'arrow-right', 'sky'],
    ];
@endphp

@push('lg-head')
    @vite(['resources/css/logistics/cash-theme.css', 'resources/css/logistics/station-cash-theme.css'])
@endpush

<x-logistics.shell title="Cash Collections" role="station">
    <div class="lgc"
         x-data="{
            rows: @js($rows),
            tab: 'all',
            q: '',
            dialog: null,
            riderId: null,
            picked: [],
            received: '',
            note: '',
            err: { parcels: '', received: '', note: '' },
            init() {
                // After a failed save the page reloads: open the same rider again with what was typed.
                const was = @js($dialogOld);
                if (was.startsWith('rider:') && this.rows[was.slice(6)]) {
                    this.riderId = was.slice(6);
                    this.picked = @js(array_map('strval', (array) old('order_ids', []))).filter(id => this.rider.parcels.some(p => p.id === id));
                    this.received = @js((string) old('received_amount', ''));
                    this.note = @js((string) old('note', ''));
                    this.err = { parcels: @js($errors->first('order_ids')), received: @js($errors->first('received_amount')), note: @js($errors->first('note')) };
                    this.dialog = 'receive';
                }
                // Ticking or unticking parcels changes what the rider should hand in.
                this.$watch('picked', () => { if (this.dialog === 'receive') { this.received = this.expected.toFixed(2); this.err.parcels = ''; this.err.received = ''; } });
                this.$watch('dialog', (open) => {
                    const main = document.querySelector('.vd-main');
                    if (main) main.style.overflow = open ? 'hidden' : '';
                });
            },
            get all() { return Object.values(this.rows); },
            get shown() {
                const s = this.q.trim().toLowerCase();
                return this.all.filter(r => (this.tab === 'all' || (this.tab === 'holding' && r.onHand > 0) || (this.tab === 'disputed' && r.disputed > 0))
                    && (!s || r.name.toLowerCase().includes(s)));
            },
            get filtered() { return this.q.trim() !== '' || this.tab !== 'all'; },
            count(k) { return k === 'all' ? this.all.length : this.all.filter(r => k === 'holding' ? r.onHand > 0 : r.disputed > 0).length; },
            visible(id) { return this.shown.some(r => r.id === id); },
            clearFilters() { this.q = ''; this.tab = 'all'; },
            get rider() { return this.rows[this.riderId] || null; },
            get expected() { return this.rider ? Math.round(this.rider.parcels.filter(p => this.picked.includes(p.id)).reduce((t, p) => t + p.amount * 100, 0)) / 100 : 0; },
            get diff() { return Math.round(Number(this.received) * 100) - Math.round(this.expected * 100); },
            get differs() { return this.received !== '' && this.diff !== 0; },
            get allPicked() { return this.rider && this.picked.length === this.rider.parcels.length; },
            peso(n) { return '₱' + Number(n).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
            openReceive(id) {
                this.riderId = String(id);
                this.picked = this.rider.parcels.map(p => p.id);
                this.received = this.expected.toFixed(2);
                this.note = '';
                this.err = { parcels: '', received: '', note: '' };
                this.dialog = 'receive';
                this.$nextTick(() => this.$refs.receivedInput?.focus());
            },
            toggleAll() { this.picked = this.allPicked ? [] : this.rider.parcels.map(p => p.id); },
            check() {
                const e = { parcels: '', received: '', note: '' };
                if (!this.picked.length) e.parcels = 'No parcels selected. Tick the parcels the rider handed in.';
                if (this.received === '' || isNaN(Number(this.received))) e.received = 'Amount received is empty. Enter the cash you counted, in pesos.';
                else if (Number(this.received) < 0) e.received = 'Amount received is below zero. Enter 0 or more.';
                else if (this.differs && !this.note.trim()) e.note = 'Amount differs from what was collected. Add a note that explains the difference.';
                this.err = e;
                if (e.parcels || e.received || e.note) { this.$nextTick(() => document.querySelector('[data-receive-form] [aria-invalid=true]')?.focus()); return false; }
                return true;
            },
            close() { this.dialog = null; }
         }"
         @keydown.escape.window="close()">

        <x-logistics.flash />

        <header class="lgc-head">
            <div>
                <h1>Cash collections</h1>
                <p>COD cash your riders collected at delivery. Confirm what each rider hands in, then send the verified cash to {{ $provinceName }}. These are confirmations in the system: no real payment is made here.</p>
            </div>
            @if ($province && $readyCount > 0)
                <button type="button" class="lgc-btn lgc-btn--primary" @click="dialog = 'send'"><x-logistics.icon name="arrow-right" :size="16" /> Send to {{ $province->name }}</button>
            @endif
        </header>

        @if ($openDiscrepancies > 0 || ! $province)
            <div class="lgc-stack">
                @if ($openDiscrepancies > 0)
                    <div class="lgc-callout lgc-callout--rose" role="alert">
                        <x-logistics.icon name="alert" :size="18" />
                        <p>{{ number_format($openDiscrepancies) }} rider {{ $openDiscrepancies === 1 ? 'receipt has' : 'receipts have' }} a cash difference. Resolve {{ $openDiscrepancies === 1 ? 'it' : 'them' }} so that cash can be sent. <a href="{{ $discrepancyUrl }}">Resolve discrepancies</a></p>
                    </div>
                @endif
                @if (! $province)
                    <div class="lgc-callout" role="status">
                        <x-logistics.icon name="info" :size="18" />
                        <p>This Station Hub has no Province Hub assigned, so verified cash can’t be sent up yet. Ask your company admin to assign one.</p>
                    </div>
                @endif
            </div>
        @endif

        <section class="lgc-tiles" aria-label="Cash at a glance">
            @foreach ($tiles as [$label, $value, $icon, $toneName])
                <div class="lgc-tile">
                    <span class="lgc-tile__icon lgc-tone-{{ $toneName }}"><x-logistics.icon :name="$icon" :size="22" /></span>
                    <span class="lgc-tile__text">{{ $label }}<strong>{{ $value }}</strong></span>
                </div>
            @endforeach
        </section>

        {{-- Rider cash --}}
        <h2 class="lgc-section">Rider cash</h2>

        <div class="lgc-chips" role="group" aria-label="Filter riders by cash" x-show="all.length" x-cloak>
            @foreach (['all' => 'All', 'holding' => 'Holding cash', 'disputed' => 'In discrepancy'] as $key => $text)
                <button type="button" class="lgc-chip" :class="{ 'is-active': tab === '{{ $key }}' }" :aria-pressed="tab === '{{ $key }}'" @click="tab = '{{ $key }}'">
                    {{ $text }} <span class="lgc-chip__count" x-text="count('{{ $key }}')"></span>
                </button>
            @endforeach
            <button type="button" class="lgc-chip lgc-chip--clear" x-show="filtered" x-cloak @click="clearFilters()"><x-logistics.icon name="x" :size="14" /> Clear filters</button>
        </div>

        <div class="lgc-toolbar" x-show="all.length" x-cloak>
            <label class="lgc-search">
                <span class="lgc-sr">Search riders</span>
                <x-logistics.icon name="search" :size="18" />
                <input type="search" x-model="q" @keydown.escape="q = ''" @keydown.enter.prevent maxlength="100" autocomplete="off" placeholder="Search by rider name">
            </label>
            <span class="lgc-meta" aria-live="polite" x-text="shown.length + (shown.length === 1 ? ' rider' : ' riders')"></span>
        </div>

        <section class="lgc-card lgc-table-card" aria-label="Rider cash">
            @if (count($ledger))
                <div class="lgc-table-wrap" x-show="shown.length" x-cloak>
                    <table class="lgc-table">
                        <thead><tr><th scope="col">Rider</th><th scope="col" class="is-num">Cash on hand</th><th scope="col" class="is-num">In discrepancy</th><th scope="col" class="is-num">Earnings accrued</th><th scope="col"><span class="lgc-sr">Action</span></th></tr></thead>
                        <tbody>
                            @foreach ($ledger as $row)
                                @php $id = (string) $row['courier_id']; $parcelCount = count($row['parcels']); @endphp
                                <tr class="lgc-row {{ $parcelCount ? '' : 'is-static' }}" x-show="visible('{{ $id }}')" @if ($parcelCount) @click="openReceive('{{ $id }}')" @endif>
                                    <td><strong>{{ $row['name'] }}</strong><small>{{ $parcelCount ? number_format($parcelCount).' '.($parcelCount === 1 ? 'parcel' : 'parcels').' holding cash' : 'No cash on hand' }}</small></td>
                                    <td data-label="Cash on hand" class="is-num"><strong>{{ $money($row['on_hand']) }}</strong></td>
                                    <td data-label="In discrepancy" class="is-num">{{ $row['disputed'] > 0 ? $money($row['disputed']) : 'None' }}</td>
                                    <td data-label="Earnings accrued" class="is-num">{{ $money($row['earnings']) }}</td>
                                    <td class="lgc-actions">
                                        @if ($parcelCount)
                                            <button type="button" class="lgc-btn lgc-btn--primary lgc-btn--small" @click.stop="openReceive('{{ $id }}')" aria-label="Receive cash from {{ $row['name'] }}">
                                                Receive cash <x-logistics.icon name="arrow-right" :size="14" class="lgc-nudge" />
                                            </button>
                                        @else
                                            <span class="lgc-muted">Nothing to receive</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="lgc-note lgc-note--foot" x-show="shown.length" x-cloak>Earnings accrued are paid out by the Company, not collected here.</p>

                <div class="lgc-empty" x-show="!shown.length" x-cloak role="status">
                    <span class="lgc-empty__icon lgc-tone-lilac"><x-logistics.icon name="search" :size="26" /></span>
                    <strong>No riders match your filters</strong>
                    <p>Check the spelling, or pick another cash status.</p>
                    <button type="button" class="lgc-btn lgc-btn--primary" @click="clearFilters()">Clear filters</button>
                </div>
            @else
                <div class="lgc-empty" role="status">
                    <span class="lgc-empty__icon lgc-tone-lilac"><x-logistics.icon name="bike" :size="26" /></span>
                    <strong>No rider cash yet</strong>
                    <p>Cash appears here after a rider completes a COD delivery.</p>
                    <a href="{{ route('lgp.station.riders') }}" class="lgc-btn lgc-btn--primary">View riders</a>
                </div>
            @endif
        </section>

        {{-- Sent to the Province Hub --}}
        <h2 class="lgc-section">Sent to {{ $provinceName }}</h2>
        <section class="lgc-card lgc-table-card" aria-label="Sent to the Province Hub">
            <p class="lgc-note">{{ $readyCount > 0 ? number_format($readyCount).' '.($readyCount === 1 ? 'parcel has' : 'parcels have').' verified cash, '.$money($readyAmount).($province ? '. Use Send to '.$province->name.' at the top of the page.' : '.') : 'Nothing is ready to send. Confirm cash from a rider first.' }}</p>
            @if ($sent->count())
                <div class="lgc-table-wrap">
                    <table class="lgc-table">
                        <thead><tr><th scope="col">Reference</th><th scope="col">To</th><th scope="col" class="is-num">Parcels</th><th scope="col" class="is-num">Amount</th><th scope="col">Status</th><th scope="col">Sent</th></tr></thead>
                        <tbody>
                            @foreach ($sent as $batch)
                                <tr>
                                    <td><strong>{{ $batch->reference }}</strong></td>
                                    <td data-label="To">{{ $batch->toFacility?->name ?? 'Province Hub' }}</td>
                                    <td data-label="Parcels" class="is-num">{{ number_format($batch->items_count) }}</td>
                                    <td data-label="Amount" class="is-num">{{ $money($batch->expected_amount) }}</td>
                                    <td data-label="Status"><span class="lgc-pill lgc-pill--{{ $tone[$batch->status] ?? 'lilac' }}">{{ $statusLabels[$batch->status] ?? $batch->status }}</span></td>
                                    <td data-label="Sent">{{ $batch->submitted_at?->format('M j, g:i A') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="lgc-empty lgc-empty--compact" role="status">
                    <span class="lgc-empty__icon lgc-tone-sky"><x-logistics.icon name="arrow-right" :size="26" /></span>
                    <strong>Nothing sent yet</strong>
                    <p>Batches you send show here until the Province Hub confirms receipt.</p>
                </div>
            @endif
        </section>

        {{-- Recent receipts from riders --}}
        <h2 class="lgc-section">Recent receipts from riders</h2>
        <section class="lgc-card lgc-table-card" aria-label="Recent receipts from riders">
            @if ($receipts->count())
                <div class="lgc-table-wrap">
                    <table class="lgc-table">
                        <thead><tr><th scope="col">Receipt</th><th scope="col">Rider</th><th scope="col" class="is-num">Expected</th><th scope="col" class="is-num">Received</th><th scope="col">Status</th><th scope="col"><span class="lgc-sr">Action</span></th></tr></thead>
                        <tbody>
                            @foreach ($receipts as $receipt)
                                <tr>
                                    <td><strong>{{ $receipt->reference }}</strong><small>Confirmed {{ $receipt->verified_at?->format('M j, g:i A') }}</small></td>
                                    <td data-label="Rider">{{ $receipt->fromRider?->name ?? 'Rider' }}</td>
                                    <td data-label="Expected" class="is-num">{{ $money($receipt->expected_amount) }}</td>
                                    <td data-label="Received" class="is-num">{{ $money($receipt->received_amount) }}</td>
                                    <td data-label="Status"><span class="lgc-pill lgc-pill--{{ $tone[$receipt->status] ?? 'lilac' }}">{{ $statusLabels[$receipt->status] ?? $receipt->status }}</span></td>
                                    <td class="lgc-actions">
                                        @if ($receipt->status === 'discrepancy')
                                            <a href="{{ $discrepancyUrl }}" class="lgc-btn lgc-btn--soft lgc-btn--small" aria-label="Resolve discrepancy {{ $receipt->reference }}">Resolve <x-logistics.icon name="arrow-right" :size="14" class="lgc-nudge" /></a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="lgc-empty lgc-empty--compact" role="status">
                    <span class="lgc-empty__icon lgc-tone-lilac"><x-logistics.icon name="file" :size="26" /></span>
                    <strong>No receipts yet</strong>
                    <p>Each time you confirm a rider’s cash, the receipt shows here.</p>
                </div>
            @endif
        </section>

        {{-- Receive cash from one rider --}}
        <div class="lgc-layer" x-show="dialog === 'receive'" x-cloak>
            <div class="lgc-backdrop" @click="close()"></div>
            <div class="lgc-dialog" role="dialog" aria-modal="true" aria-labelledby="receiveTitle">
                <template x-if="rider">
                    <div class="lgc-dialog__inner">
                        <header class="lgc-dialog__head">
                            <div>
                                <h2 id="receiveTitle" x-text="'Receive cash from ' + rider.name"></h2>
                                <p x-text="rider.parcels.length + (rider.parcels.length === 1 ? ' parcel' : ' parcels') + ' holding ' + peso(rider.onHand)"></p>
                            </div>
                            <button type="button" class="lgc-close" @click="close()" aria-label="Close"><svg class="lgc-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg></button>
                        </header>

                        <form method="POST" :action="rider.url" class="lgc-dialog__body lgc-form" data-receive-form novalidate @submit="if (!check()) $event.preventDefault()">
                            @csrf
                            <input type="hidden" name="_dialog" :value="'rider:' + rider.id">

                            <div class="lgc-picks__head">
                                <strong class="lgc-picks__title">Parcels handed in</strong>
                                <button type="button" class="lgc-btn lgc-btn--soft lgc-btn--small" @click="toggleAll()" x-text="allPicked ? 'Clear selection' : 'Select all'"></button>
                            </div>
                            <ul class="lgc-picks" aria-describedby="picksErr">
                                <template x-for="p in rider.parcels" :key="p.id">
                                    <li>
                                        <label class="lgc-pick">
                                            <input type="checkbox" name="order_ids[]" :value="p.id" x-model="picked">
                                            <span><strong x-text="p.tracking"></strong><small x-text="'Collected ' + p.at"></small></span>
                                            <b x-text="peso(p.amount)"></b>
                                        </label>
                                    </li>
                                </template>
                            </ul>
                            <p class="lgc-err" id="picksErr" x-show="err.parcels" x-cloak x-text="err.parcels" role="alert"></p>

                            <div class="lgc-field">
                                <label for="receivedAmount">Amount received (₱) <span aria-hidden="true">*</span></label>
                                <input id="receivedAmount" name="received_amount" type="number" step="0.01" min="0" inputmode="decimal" x-ref="receivedInput" x-model="received" @input="err.received = ''; err.note = ''" required
                                       :class="{ 'has-error': err.received }" :aria-invalid="!!err.received" aria-describedby="receivedErr receivedDiff">
                                <p class="lgc-err" id="receivedErr" x-show="err.received" x-cloak x-text="err.received" role="alert"></p>
                                <p class="lgc-diff" id="receivedDiff" x-show="received !== '' && picked.length" x-cloak
                                   :class="differs ? (diff < 0 ? 'lgc-diff--short' : 'lgc-diff--over') : 'lgc-diff--ok'"
                                   x-text="differs ? peso(Math.abs(diff) / 100) + (diff < 0 ? ' short of ' : ' over ') + peso(expected) : 'Matches the ' + peso(expected) + ' collected.'"></p>
                            </div>

                            <div class="lgc-field" x-show="differs" x-cloak>
                                <label for="receivedNote">Why does it differ? <span aria-hidden="true">*</span></label>
                                <input id="receivedNote" name="note" maxlength="500" x-model="note" @input="err.note = ''" placeholder="For example: short by one parcel"
                                       :class="{ 'has-error': err.note }" :aria-invalid="!!err.note" aria-describedby="receivedNoteErr">
                                <p class="lgc-err" id="receivedNoteErr" x-show="err.note" x-cloak x-text="err.note" role="alert"></p>
                                <p class="lgc-hint">The receipt is recorded as a discrepancy until you resolve it.</p>
                            </div>

                            <footer class="lgc-dialog__foot">
                                <span class="lgc-spacer"></span>
                                <button type="button" class="lgc-btn lgc-btn--soft" @click="close()">Cancel</button>
                                <button type="submit" class="lgc-btn lgc-btn--primary">Confirm receipt</button>
                            </footer>
                        </form>
                    </div>
                </template>
            </div>
        </div>

        {{-- Send to the Province Hub: confirm before the batch is created --}}
        @if ($province)
            <div class="lgc-layer" x-show="dialog === 'send'" x-cloak>
                <div class="lgc-backdrop" @click="close()"></div>
                <div class="lgc-dialog lgc-dialog--sm" role="dialog" aria-modal="true" aria-labelledby="sendTitle">
                    <header class="lgc-dialog__head">
                        <div>
                            <h2 id="sendTitle">Send {{ $money($readyAmount) }} to {{ $province->name }}?</h2>
                            <p>{{ number_format($readyCount) }} {{ $readyCount === 1 ? 'parcel' : 'parcels' }} with verified cash. It stays pending until the Province Hub confirms receipt.</p>
                        </div>
                        <button type="button" class="lgc-close" @click="close()" aria-label="Close"><svg class="lgc-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg></button>
                    </header>
                    <form method="POST" action="{{ route('lgp.station.cash.remit') }}" class="lgc-dialog__body">
                        @csrf
                        <footer class="lgc-dialog__foot">
                            <span class="lgc-spacer"></span>
                            <button type="button" class="lgc-btn lgc-btn--soft" @click="close()">Cancel</button>
                            <button type="submit" class="lgc-btn lgc-btn--primary">Send {{ $money($readyAmount) }}</button>
                        </footer>
                    </form>
                </div>
            </div>
        @endif
    </div>
</x-logistics.shell>
