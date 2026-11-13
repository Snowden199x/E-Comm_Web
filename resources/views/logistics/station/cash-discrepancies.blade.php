{{--
    Station Hub > Cash discrepancies (spec section 9). A discrepancy is a Rider receipt where the cash handed in did not match
    what was collected. The parcels stay on hold until it is resolved with a note; then their cash counts in full and can be
    sent to the Province Hub (on Cash collections). Fed by StationCashController::discrepancies. Posts go to ::resolve.
    Look: the seller pastel lilac system, the same .lgc layer as Station Hub > Cash collections. Styles: cash-theme.css plus
    station-discrepancies-theme.css (difference colors, parcel list), pushed last; dialogs carry their own copy of the tokens.
    Status colors: Discrepancy = rose (problem), Resolved = mint (done). Short = rose text, over = butter text.
    Status chips and the search are server-side (this list is paged): links and a GET form. One click on a row opens it.
    Loading: shell skeleton when paging or filtering, spinner on the submit button. Errors: top banner and a message under the
    note field; the dialog reopens with what was typed (hidden _dialog field).
--}}
@php
    $statusLabels = \App\Models\Finance\CashRemittance::STATUSES;
    $money = fn ($value) => '₱'.number_format((float) $value, 2);
    $home = route('lgp.station.cash.discrepancies');
    $cashUrl = route('lgp.station.cash');
    $tone = ['discrepancy' => 'rose', 'resolved' => 'mint'];

    $items = $remittances->getCollection()->mapWithKeys(function ($r) use ($statusLabels) {
        $diff = (float) $r->received_amount - (float) $r->expected_amount;
        $tracking = $r->items->map(fn ($item) => $item->order?->tracking_number)->filter()->values()->all();

        return [(string) $r->id => [
            'id' => $r->id,
            'reference' => $r->reference,
            'rider' => $r->fromRider?->name ?? 'Rider',
            'parcels' => count($tracking) ?: $r->items->count(),
            'tracking' => $tracking,
            'expected' => (float) $r->expected_amount,
            'received' => (float) $r->received_amount,
            'diff' => $diff,
            'status' => $r->status,
            'label' => $statusLabels[$r->status] ?? $r->status,
            'confirmed' => $r->verified_at?->format('M j, g:i A'),
            'note' => $r->note,
            'resolution' => $r->resolution_note,
            'by' => $r->resolvedBy?->name,
            'at' => $r->resolved_at?->format('M j, g:i A'),
            'resolveUrl' => route('lgp.station.cash.resolve', $r),
        ]];
    })->all();

    $open = (int) ($statusCounts['discrepancy'] ?? 0);
    $resolved = (int) ($statusCounts['resolved'] ?? 0);
    $chips = [
        'all' => ['All', $open + $resolved],
        'discrepancy' => ['Open', $open],
        'resolved' => ['Resolved', $resolved],
    ];
    $filtered = $status || $term !== '';

    // [label, value, icon, tone]
    $tiles = [
        ['Open discrepancies', number_format($open), 'alert', $open > 0 ? 'rose' : 'lilac'],
        ['Open difference', $money($openDifference), 'clock', $openDifference > 0 ? 'butter' : 'lilac'],
        ['Resolved', number_format($resolved), 'check-circle', 'mint'],
    ];
@endphp

@push('lg-head')
    @vite(['resources/css/logistics/cash-theme.css', 'resources/css/logistics/station-discrepancies-theme.css'])
@endpush

<x-logistics.shell title="Cash Discrepancies" role="station">
    <div class="lgc"
         x-data="{
            items: @js($items),
            dialog: null,
            itemId: null,
            resolution: @js((string) old('resolution_note', '')),
            err: @js($errors->first('resolution_note')),
            init() {
                // After a failed save the page reloads: open the same receipt again with what was typed.
                const was = @js((string) old('_dialog', ''));
                if (was.startsWith('rem:') && this.items[was.slice(4)]) { this.itemId = was.slice(4); this.dialog = 'item'; }
                this.$watch('dialog', (open) => {
                    const main = document.querySelector('.vd-main');
                    if (main) main.style.overflow = open ? 'hidden' : '';
                });
            },
            get item() { return this.items[this.itemId] || null; },
            peso(n) { return '₱' + Number(n).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
            open(id) {
                this.itemId = String(id);
                this.resolution = ''; this.err = '';
                this.dialog = 'item';
            },
            check() {
                if (this.resolution.trim().length < 5) {
                    this.err = this.resolution.trim() === '' ? 'Resolution note is empty. Say how the difference was settled, so the next person knows.' : 'Resolution note is too short. Use at least 5 characters to say how it was settled.';
                    this.$nextTick(() => this.$refs.noteInput?.focus());
                    return false;
                }
                return true;
            },
            close() { this.dialog = null; }
         }"
         @keydown.escape.window="close()">

        <x-logistics.flash />

        <header class="lgc-head">
            <div>
                <h1>Cash discrepancies</h1>
                <p>Receipts where the cash a rider handed in did not match what was collected. Resolve each one with a note. Once resolved, its cash counts in full and can be sent to your Province Hub.</p>
            </div>
            <a href="{{ $cashUrl }}" class="lgc-btn lgc-btn--soft"><x-logistics.icon name="arrow-right" :size="16" /> Open cash collections</a>
        </header>

        <section class="lgc-tiles lgc-tiles--3" aria-label="Discrepancies at a glance">
            @foreach ($tiles as [$label, $value, $icon, $toneName])
                <div class="lgc-tile">
                    <span class="lgc-tile__icon lgc-tone-{{ $toneName }}"><x-logistics.icon :name="$icon" :size="22" /></span>
                    <span class="lgc-tile__text">{{ $label }}<strong>{{ $value }}</strong></span>
                </div>
            @endforeach
        </section>

        <div class="lgc-chips" role="group" aria-label="Filter discrepancies by status">
            @foreach ($chips as $key => [$text, $count])
                @php $value = $key === 'all' ? null : $key; @endphp
                <a href="{{ route('lgp.station.cash.discrepancies', array_filter(['status' => $value, 'q' => $term])) }}" class="lgc-chip {{ ($status ?? null) === $value ? 'is-active' : '' }}" @if (($status ?? null) === $value) aria-current="true" @endif>
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
                <span class="lgc-sr">Search by receipt reference or rider</span>
                <x-logistics.icon name="search" :size="18" />
                <input type="search" name="q" value="{{ $term }}" maxlength="100" autocomplete="off" placeholder="Search by reference or rider, then press Enter">
            </label>
            <span class="lgc-meta">{{ number_format($remittances->total()) }} {{ $remittances->total() === 1 ? 'receipt' : 'receipts' }}</span>
        </form>

        <section class="lgc-card lgc-table-card" aria-label="Cash discrepancies">
            @if ($remittances->count())
                <div class="lgc-table-wrap">
                    <table class="lgc-table">
                        <thead><tr><th scope="col">Receipt</th><th scope="col">Rider</th><th scope="col" class="is-num">Expected</th><th scope="col" class="is-num">Received</th><th scope="col">Status</th><th scope="col"><span class="lgc-sr">Action</span></th></tr></thead>
                        <tbody>
                            @foreach ($remittances as $remittance)
                                @php
                                    $row = $items[(string) $remittance->id];
                                    $isOpen = $remittance->status === 'discrepancy';
                                    $short = $row['diff'] < 0;
                                @endphp
                                <tr class="lgc-row" @click="open({{ $remittance->id }})">
                                    <td><strong>{{ $remittance->reference }}</strong><small>Confirmed {{ $row['confirmed'] }}</small></td>
                                    <td data-label="Rider">{{ $row['rider'] }}<small>{{ number_format($row['parcels']) }} {{ $row['parcels'] === 1 ? 'parcel' : 'parcels' }}</small></td>
                                    <td data-label="Expected" class="is-num">{{ $money($row['expected']) }}</td>
                                    <td data-label="Received" class="is-num">{{ $money($row['received']) }}<small class="lgc-diff-sub {{ $short ? 'lgc-diff-sub--short' : 'lgc-diff-sub--over' }}">{{ $money(abs($row['diff'])) }} {{ $short ? 'short' : 'over' }}</small></td>
                                    <td data-label="Status"><span class="lgc-pill lgc-pill--{{ $tone[$remittance->status] ?? 'lilac' }}">{{ $row['label'] }}</span></td>
                                    <td class="lgc-actions">
                                        <button type="button" class="lgc-btn {{ $isOpen ? 'lgc-btn--primary' : 'lgc-btn--soft' }} lgc-btn--small" @click.stop="open({{ $remittance->id }})" aria-label="{{ $isOpen ? 'Resolve' : 'View details for' }} {{ $remittance->reference }}">
                                            {{ $isOpen ? 'Resolve' : 'View details' }} <x-logistics.icon name="arrow-right" :size="14" class="lgc-nudge" />
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($remittances->hasPages())
                    <nav class="lgc-pager" aria-label="Pages">
                        <span>Page {{ $remittances->currentPage() }} of {{ $remittances->lastPage() }}</span>
                        <div>
                            @if ($remittances->onFirstPage()) <span class="lgc-btn lgc-btn--soft lgc-btn--small is-disabled" aria-disabled="true">Previous</span>
                            @else <a href="{{ $remittances->previousPageUrl() }}" class="lgc-btn lgc-btn--soft lgc-btn--small">Previous</a> @endif
                            @if ($remittances->hasMorePages()) <a href="{{ $remittances->nextPageUrl() }}" class="lgc-btn lgc-btn--soft lgc-btn--small">Next</a>
                            @else <span class="lgc-btn lgc-btn--soft lgc-btn--small is-disabled" aria-disabled="true">Next</span> @endif
                        </div>
                    </nav>
                @endif
            @elseif ($term !== '')
                <div class="lgc-empty" role="status">
                    <span class="lgc-empty__icon lgc-tone-lilac"><x-logistics.icon name="search" :size="26" /></span>
                    <strong>No discrepancies match your filters</strong>
                    <p>Check the reference or the rider’s name, or pick another status.</p>
                    <a href="{{ $home }}" class="lgc-btn lgc-btn--primary">Clear filters</a>
                </div>
            @elseif ($status === 'discrepancy')
                <div class="lgc-empty" role="status">
                    <span class="lgc-empty__icon lgc-tone-mint"><x-logistics.icon name="check-circle" :size="26" /></span>
                    <strong>No open discrepancies</strong>
                    <p>Every cash difference has been resolved.</p>
                    <a href="{{ $home }}" class="lgc-btn lgc-btn--primary">View all discrepancies</a>
                </div>
            @elseif ($status === 'resolved')
                <div class="lgc-empty" role="status">
                    <span class="lgc-empty__icon lgc-tone-lilac"><x-logistics.icon name="file" :size="26" /></span>
                    <strong>Nothing resolved yet</strong>
                    <p>Resolved discrepancies show here, with how each was settled.</p>
                    <a href="{{ $home }}" class="lgc-btn lgc-btn--primary">View all discrepancies</a>
                </div>
            @else
                <div class="lgc-empty" role="status">
                    <span class="lgc-empty__icon lgc-tone-mint"><x-logistics.icon name="check-circle" :size="26" /></span>
                    <strong>No cash discrepancies</strong>
                    <p>A receipt shows here when a rider hands in a different amount than was collected.</p>
                    <a href="{{ $cashUrl }}" class="lgc-btn lgc-btn--primary">Open cash collections</a>
                </div>
            @endif
        </section>

        {{-- One receipt: resolve it with a note, or read how it was settled --}}
        <div class="lgc-layer" x-show="dialog === 'item'" x-cloak>
            <div class="lgc-backdrop" @click="close()"></div>
            <div class="lgc-dialog" role="dialog" aria-modal="true" aria-labelledby="discTitle">
                <template x-if="item">
                    <div class="lgc-dialog__inner">
                        <header class="lgc-dialog__head">
                            <div>
                                <h2 id="discTitle" x-text="item.reference"></h2>
                                <p x-text="item.rider + ' · ' + item.parcels + (item.parcels === 1 ? ' parcel' : ' parcels') + ' · confirmed ' + item.confirmed"></p>
                            </div>
                            <span class="lgc-pill" :class="item.status === 'discrepancy' ? 'lgc-pill--rose' : 'lgc-pill--mint'" x-text="item.label"></span>
                            <button type="button" class="lgc-close" @click="close()" aria-label="Close"><svg class="lgc-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg></button>
                        </header>

                        <div class="lgc-dialog__body">
                            <dl class="lgc-facts">
                                <div><dt>Expected</dt><dd x-text="peso(item.expected)"></dd></div>
                                <div><dt>Received</dt><dd x-text="peso(item.received)"></dd></div>
                                <div class="is-wide"><dt>Difference</dt><dd :class="item.diff < 0 ? 'lgc-diff--short' : 'lgc-diff--over'" x-text="peso(Math.abs(item.diff)) + (item.diff < 0 ? ' short' : ' over')"></dd></div>
                                <div class="is-wide" x-show="item.note"><dt>Receipt note</dt><dd x-text="item.note"></dd></div>
                                <div class="is-wide" x-show="item.tracking.length"><dt>Parcels</dt><dd><ul class="lgc-parcels"><template x-for="t in item.tracking" :key="t"><li x-text="t"></li></template></ul></dd></div>
                                <div class="is-wide" x-show="item.status === 'resolved' && item.resolution"><dt>How it was resolved</dt><dd x-text="item.resolution"></dd></div>
                                <div class="is-wide" x-show="item.status === 'resolved' && item.by"><dt>Resolved by</dt><dd x-text="item.by + (item.at ? ' · ' + item.at : '')"></dd></div>
                            </dl>

                            <form method="POST" :action="item.resolveUrl" class="lgc-form" x-show="item.status === 'discrepancy'" x-cloak novalidate @submit="if (!check()) $event.preventDefault()">
                                @csrf @method('PATCH')
                                <input type="hidden" name="_dialog" :value="'rem:' + item.id">
                                <div class="lgc-field">
                                    <label for="resolutionNote">Resolution note <span aria-hidden="true">*</span></label>
                                    <textarea id="resolutionNote" name="resolution_note" rows="3" maxlength="1000" x-ref="noteInput" x-model="resolution" @input="err = ''" required
                                              placeholder="Say how it was settled, so the next person knows" :class="{ 'has-error': err }" :aria-invalid="!!err" aria-describedby="resolutionErr"></textarea>
                                    <p class="lgc-err" id="resolutionErr" x-show="err" x-cloak x-text="err" role="alert"></p>
                                    <p class="lgc-hint">Resolving counts the parcels in full, so their cash can be sent to your Province Hub.</p>
                                </div>
                                <footer class="lgc-dialog__foot">
                                    <span class="lgc-spacer"></span>
                                    <button type="button" class="lgc-btn lgc-btn--soft" @click="close()">Cancel</button>
                                    <button type="submit" class="lgc-btn lgc-btn--primary">Resolve discrepancy</button>
                                </footer>
                            </form>

                            <footer class="lgc-dialog__foot" x-show="item.status === 'resolved'" x-cloak>
                                <span class="lgc-spacer"></span>
                                <button type="button" class="lgc-btn lgc-btn--soft" @click="close()">Close</button>
                            </footer>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</x-logistics.shell>
