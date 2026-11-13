{{--
    Province Hub > Cash Remittances (spec section 9). COD cash flows up: Station Hub, Province Hub, Company, Vendo.
    Here the Province Hub confirms batches from its Station Hubs, then sends the verified cash to its Company.
    Fed by ProvinceCashController::index. Posts go to ::receive, ::resolve and ::remit. Confirmations only: no real payment is made.
    Look: the seller pastel lilac system, the same .lgc layer as Company > Cash Remittances. Styles: cash-theme.css plus
    province-cash-theme.css (the sky tokens), pushed last; dialogs carry their own copy of the tokens. No page script: dialogs run in the Alpine scope below.
    Status colors: Pending = butter (waiting), Discrepancy = rose, Confirmed = mint, Sent to Company and pending = sky tile, butter pill.
    Status chips and the reference search are server-side (this list is paged): links and a GET form.
    One click on a batch opens it: confirm receipt, resolve a discrepancy, or read the record.
    Loading: shell skeleton when paging or filtering, spinner on the submit buttons. Errors: top banner, and the dialog
    reopens with what was typed (the hidden _dialog field says which one).
--}}
@php
    $statusLabels = \App\Models\Finance\CashRemittance::STATUSES;

    $money = fn ($value) => '₱'.number_format((float) $value, 2);
    $home = route('lgp.province.cash');
    $tone = ['pending' => 'butter', 'discrepancy' => 'rose', 'received' => 'mint', 'resolved' => 'mint'];

    $items = $incoming->getCollection()->mapWithKeys(fn ($b) => [(string) $b->id => [
        'id' => $b->id,
        'reference' => $b->reference,
        'from' => $b->fromFacility?->name ?? 'Unknown hub',
        'parcels' => (int) $b->items_count,
        'expected' => (float) $b->expected_amount,
        'received' => $b->received_amount !== null ? (float) $b->received_amount : null,
        'status' => $b->status,
        'label' => $statusLabels[$b->status] ?? $b->status,
        'sent' => $b->submitted_at?->format('M j, g:i A'),
        'note' => $b->note,
        'resolution' => $b->resolution_note,
        'by' => $b->verifiedBy?->name,
        'at' => ($b->resolved_at ?? $b->verified_at)?->format('M j, g:i A'),
        'receiveUrl' => route('lgp.province.cash.receive', $b),
        'resolveUrl' => route('lgp.province.cash.resolve', $b),
    ]])->all();

    $chips = [
        'all' => ['All', (int) $statusCounts->sum()],
        'pending' => ['Needs confirmation', (int) ($statusCounts['pending'] ?? 0)],
        'discrepancy' => ['Discrepancy', (int) ($statusCounts['discrepancy'] ?? 0)],
        'confirmed' => ['Confirmed', (int) (($statusCounts['received'] ?? 0) + ($statusCounts['resolved'] ?? 0))],
    ];
    $filtered = $status || $term !== '';
    $readyCount = (int) $ready->parcels;
    $readyAmount = (float) $ready->total;

    // [label, value, icon, tone]
    $tiles = [
        ['Awaiting your confirmation', $money($awaitingConfirm), 'clock', 'butter'],
        ['Open discrepancies', number_format($openDiscrepancies), 'alert', 'rose'],
        ['Verified, ready to send', $money($readyAmount), 'check-circle', 'mint'],
        ['Sent, awaiting Company', $money($awaitingCompany), 'arrow-right', 'sky'],
    ];
@endphp

@push('lg-head')
    @vite(['resources/css/logistics/cash-theme.css', 'resources/css/logistics/province-cash-theme.css'])
@endpush

<x-logistics.shell title="Cash Remittances" role="province">
    <div class="lgc"
         x-data="{
            items: @js($items),
            dialog: null,
            batchId: null,
            received: @js((string) old('received_amount', '')),
            note: @js((string) old('note', '')),
            resolution: @js((string) old('resolution_note', '')),
            init() {
                // After a failed save the page reloads: open the same batch again with what was typed.
                const was = @js((string) old('_dialog', ''));
                if (was.startsWith('batch:') && this.items[was.slice(6)]) { this.batchId = was.slice(6); this.dialog = 'batch'; }
                this.$watch('dialog', (open) => {
                    const main = document.querySelector('.vd-main');
                    if (main) main.style.overflow = open ? 'hidden' : '';
                });
            },
            get batch() { return this.items[this.batchId] || null; },
            get differs() { return this.batch && Math.round(Number(this.received) * 100) !== Math.round(this.batch.expected * 100); },
            peso(n) { return '₱' + Number(n).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
            openBatch(id) {
                this.batchId = String(id);
                this.received = this.batch.expected.toFixed(2);
                this.note = ''; this.resolution = '';
                this.dialog = 'batch';
            },
            close() { this.dialog = null; }
         }"
         @keydown.escape.window="close()">

        <x-logistics.flash />

        <header class="lgc-head">
            <div>
                <h1>Cash remittances</h1>
                <p>COD cash your Station Hubs send up. Confirm each batch, then send the verified cash to your Company. These are confirmations in the system: no real payment is made here.</p>
            </div>
            @if ($readyCount > 0)
                <button type="button" class="lgc-btn lgc-btn--primary" @click="dialog = 'send'"><x-logistics.icon name="arrow-right" :size="16" /> Send to Company</button>
            @endif
        </header>

        <section class="lgc-tiles" aria-label="Cash at a glance">
            @foreach ($tiles as [$label, $value, $icon, $toneName])
                <div class="lgc-tile">
                    <span class="lgc-tile__icon lgc-tone-{{ $toneName }}"><x-logistics.icon :name="$icon" :size="22" /></span>
                    <span class="lgc-tile__text">{{ $label }}<strong>{{ $value }}</strong></span>
                </div>
            @endforeach
        </section>

        <h2 class="lgc-section">Batches from Station Hubs</h2>

        <div class="lgc-chips" role="group" aria-label="Filter batches by status">
            @foreach ($chips as $key => [$text, $count])
                @php $value = $key === 'all' ? null : $key; @endphp
                <a href="{{ route('lgp.province.cash', array_filter(['status' => $value, 'q' => $term])) }}" class="lgc-chip {{ ($status ?? null) === $value ? 'is-active' : '' }}" @if (($status ?? null) === $value) aria-current="true" @endif>
                    {{ $text }} <span class="lgc-chip__count">{{ $count }}</span>
                </a>
            @endforeach
            @if ($filtered)
                <a href="{{ $home }}" class="lgc-chip lgc-chip--clear"><x-logistics.icon name="x" :size="14" /> Clear filters</a>
            @endif
        </div>

        <form method="GET" action="{{ $home }}" class="lgc-toolbar" role="search">
            @if ($status) <input type="hidden" name="status" value="{{ $status }}"> @endif
            <label class="lgc-search">
                <span class="lgc-sr">Search by batch reference</span>
                <x-logistics.icon name="search" :size="18" />
                <input type="search" name="q" value="{{ $term }}" maxlength="100" autocomplete="off" placeholder="Search by batch reference, then press Enter">
            </label>
            <span class="lgc-meta">{{ number_format($incoming->total()) }} {{ $incoming->total() === 1 ? 'batch' : 'batches' }}</span>
        </form>

        <section class="lgc-card lgc-table-card" aria-label="Batches from Station Hubs">
            @if ($incoming->count())
                <div class="lgc-table-wrap">
                    <table class="lgc-table">
                        <thead><tr><th scope="col">Batch</th><th scope="col">From</th><th scope="col" class="is-num">Parcels</th><th scope="col" class="is-num">Expected</th><th scope="col" class="is-num">Received</th><th scope="col">Status</th><th scope="col"><span class="lgc-sr">Action</span></th></tr></thead>
                        <tbody>
                            @foreach ($incoming as $batch)
                                @php $row = $items[(string) $batch->id]; $label = ['pending' => 'Confirm receipt', 'discrepancy' => 'Resolve'][$batch->status] ?? 'View details'; @endphp
                                <tr class="lgc-row" @click="openBatch({{ $batch->id }})">
                                    <td><strong>{{ $batch->reference }}</strong><small>Sent {{ $row['sent'] }}</small></td>
                                    <td data-label="From">{{ $row['from'] }}</td>
                                    <td data-label="Parcels" class="is-num">{{ number_format($row['parcels']) }}</td>
                                    <td data-label="Expected" class="is-num">{{ $money($row['expected']) }}</td>
                                    <td data-label="Received" class="is-num">{{ $row['received'] !== null ? $money($row['received']) : 'Not yet' }}</td>
                                    <td data-label="Status"><span class="lgc-pill lgc-pill--{{ $tone[$batch->status] ?? 'lilac' }}">{{ $row['label'] }}</span></td>
                                    <td class="lgc-actions">
                                        <button type="button" class="lgc-btn {{ $batch->status === 'pending' ? 'lgc-btn--primary' : 'lgc-btn--soft' }} lgc-btn--small" @click.stop="openBatch({{ $batch->id }})" aria-label="{{ $label }} for {{ $batch->reference }}">
                                            {{ $label }} <x-logistics.icon name="arrow-right" :size="14" class="lgc-nudge" />
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($incoming->hasPages())
                    <nav class="lgc-pager" aria-label="Pages">
                        <span>Page {{ $incoming->currentPage() }} of {{ $incoming->lastPage() }}</span>
                        <div>
                            @if ($incoming->onFirstPage()) <span class="lgc-btn lgc-btn--soft lgc-btn--small is-disabled" aria-disabled="true">Previous</span>
                            @else <a href="{{ $incoming->previousPageUrl() }}" class="lgc-btn lgc-btn--soft lgc-btn--small">Previous</a> @endif
                            @if ($incoming->hasMorePages()) <a href="{{ $incoming->nextPageUrl() }}" class="lgc-btn lgc-btn--soft lgc-btn--small">Next</a>
                            @else <span class="lgc-btn lgc-btn--soft lgc-btn--small is-disabled" aria-disabled="true">Next</span> @endif
                        </div>
                    </nav>
                @endif
            @elseif ($filtered)
                <div class="lgc-empty" role="status">
                    <span class="lgc-empty__icon lgc-tone-lilac"><x-logistics.icon name="search" :size="26" /></span>
                    <strong>No batches match your filters</strong>
                    <p>Check the reference, or pick another status.</p>
                    <a href="{{ $home }}" class="lgc-btn lgc-btn--primary">Clear filters</a>
                </div>
            @else
                <div class="lgc-empty" role="status">
                    <span class="lgc-empty__icon lgc-tone-lilac"><x-logistics.icon name="file" :size="26" /></span>
                    <strong>No batches from Station Hubs yet</strong>
                    <p>A batch appears here when a Station Hub sends up the COD cash its riders collected.</p>
                    <a href="{{ route('lgp.province.stations') }}" class="lgc-btn lgc-btn--primary">View Station Hubs</a>
                </div>
            @endif
        </section>

        <h2 class="lgc-section">Sent to Company</h2>
        <section class="lgc-card lgc-table-card" aria-label="Sent to Company">
            <p class="lgc-note">{{ $readyCount > 0 ? number_format($readyCount).' '.($readyCount === 1 ? 'parcel has' : 'parcels have').' verified cash, '.$money($readyAmount).'. Use Send to Company at the top of the page.' : 'Nothing is ready to send. Confirm a Station Hub batch first.' }}</p>
            @if ($sent->count())
                <div class="lgc-table-wrap">
                    <table class="lgc-table">
                        <thead><tr><th scope="col">Reference</th><th scope="col" class="is-num">Parcels</th><th scope="col" class="is-num">Amount</th><th scope="col">Status</th><th scope="col">Sent</th></tr></thead>
                        <tbody>
                            @foreach ($sent as $batch)
                                <tr>
                                    <td><strong>{{ $batch->reference }}</strong></td>
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
                    <strong>Nothing sent to the Company yet</strong>
                    <p>Batches you send show here until the Company confirms receipt.</p>
                </div>
            @endif
        </section>

        {{-- Batch: confirm receipt, resolve a discrepancy, or read the record --}}
        <div class="lgc-layer" x-show="dialog === 'batch'" x-cloak>
            <div class="lgc-backdrop" @click="close()"></div>
            <div class="lgc-dialog" role="dialog" aria-modal="true" aria-labelledby="batchTitle">
                <template x-if="batch">
                    <div class="lgc-dialog__inner">
                        <header class="lgc-dialog__head">
                            <div>
                                <h2 id="batchTitle" x-text="batch.reference"></h2>
                                <p x-text="batch.from + ' · ' + batch.parcels + (batch.parcels === 1 ? ' parcel' : ' parcels') + ' · sent ' + batch.sent"></p>
                            </div>
                            <span class="lgc-pill" :class="{ 'lgc-pill--butter': batch.status === 'pending', 'lgc-pill--rose': batch.status === 'discrepancy', 'lgc-pill--mint': batch.status === 'received' || batch.status === 'resolved' }" x-text="batch.label"></span>
                            <button type="button" class="lgc-close" @click="close()" aria-label="Close"><svg class="lgc-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg></button>
                        </header>

                        <div class="lgc-dialog__body">
                            <dl class="lgc-facts">
                                <div><dt>Expected</dt><dd x-text="peso(batch.expected)"></dd></div>
                                <div><dt>Received</dt><dd x-text="batch.received !== null ? peso(batch.received) : 'Not yet'"></dd></div>
                                <div class="is-wide" x-show="batch.note"><dt>Receipt note</dt><dd x-text="batch.note"></dd></div>
                                <div class="is-wide" x-show="batch.status === 'resolved' && batch.resolution"><dt>How it was resolved</dt><dd x-text="batch.resolution"></dd></div>
                                <div class="is-wide" x-show="batch.by"><dt>Handled by</dt><dd x-text="batch.by + ' · ' + batch.at"></dd></div>
                            </dl>

                            {{-- Pending: confirm what you received --}}
                            <form method="POST" :action="batch.receiveUrl" class="lgc-form" x-show="batch.status === 'pending'" x-cloak>
                                @csrf
                                <input type="hidden" name="_dialog" :value="'batch:' + batch.id">
                                <div class="lgc-field">
                                    <label for="receivedAmount">Amount received (₱) <span aria-hidden="true">*</span></label>
                                    <input id="receivedAmount" name="received_amount" type="number" step="0.01" min="0" inputmode="decimal" x-model="received" required>
                                </div>
                                <div class="lgc-field" x-show="differs" x-cloak>
                                    <label for="receivedNote">Why does it differ? <span aria-hidden="true">*</span></label>
                                    <input id="receivedNote" name="note" maxlength="500" x-model="note" :required="differs" placeholder="For example: short by one parcel">
                                    <p class="lgc-hint">The batch will be recorded as a discrepancy until you resolve it.</p>
                                </div>
                                <footer class="lgc-dialog__foot">
                                    <span class="lgc-spacer"></span>
                                    <button type="button" class="lgc-btn lgc-btn--soft" @click="close()">Cancel</button>
                                    <button type="submit" class="lgc-btn lgc-btn--primary">Confirm receipt</button>
                                </footer>
                            </form>

                            {{-- Discrepancy: resolve with a note --}}
                            <form method="POST" :action="batch.resolveUrl" class="lgc-form" x-show="batch.status === 'discrepancy'" x-cloak>
                                @csrf @method('PATCH')
                                <input type="hidden" name="_dialog" :value="'batch:' + batch.id">
                                <div class="lgc-field">
                                    <label for="resolutionNote">Resolution note <span aria-hidden="true">*</span></label>
                                    <textarea id="resolutionNote" name="resolution_note" rows="3" minlength="5" maxlength="1000" x-model="resolution" placeholder="Say how it was settled, so the next person knows" required></textarea>
                                    <p class="lgc-hint">Resolving counts the parcels in full, so they can be sent to the Company.</p>
                                </div>
                                <footer class="lgc-dialog__foot">
                                    <span class="lgc-spacer"></span>
                                    <button type="button" class="lgc-btn lgc-btn--soft" @click="close()">Cancel</button>
                                    <button type="submit" class="lgc-btn lgc-btn--primary">Resolve discrepancy</button>
                                </footer>
                            </form>

                            <footer class="lgc-dialog__foot" x-show="batch.status === 'received' || batch.status === 'resolved'">
                                <span class="lgc-spacer"></span>
                                <button type="button" class="lgc-btn lgc-btn--soft" @click="close()">Close</button>
                            </footer>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        {{-- Send to Company: confirm before the batch is created --}}
        <div class="lgc-layer" x-show="dialog === 'send'" x-cloak>
            <div class="lgc-backdrop" @click="close()"></div>
            <div class="lgc-dialog lgc-dialog--sm" role="dialog" aria-modal="true" aria-labelledby="sendTitle">
                <header class="lgc-dialog__head">
                    <div>
                        <h2 id="sendTitle">Send {{ $money($readyAmount) }} to the Company?</h2>
                        <p>{{ number_format($readyCount) }} {{ $readyCount === 1 ? 'parcel' : 'parcels' }} with verified cash. It stays pending until the Company confirms receipt.</p>
                    </div>
                    <button type="button" class="lgc-close" @click="close()" aria-label="Close"><svg class="lgc-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg></button>
                </header>
                <form method="POST" action="{{ route('lgp.province.cash.remit') }}" class="lgc-dialog__body">
                    @csrf
                    <footer class="lgc-dialog__foot">
                        <span class="lgc-spacer"></span>
                        <button type="button" class="lgc-btn lgc-btn--soft" @click="close()">Cancel</button>
                        <button type="submit" class="lgc-btn lgc-btn--primary">Send {{ $money($readyAmount) }}</button>
                    </footer>
                </form>
            </div>
        </div>
    </div>
</x-logistics.shell>
