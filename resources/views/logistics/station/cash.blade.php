<x-logistics.shell title="Cash Collections" role="station">
    <div class="lg-page">
        <div class="lg-page-head"><div><h1>Cash Collections</h1><p>COD cash your Riders collected at delivery. Confirm what each Rider hands in, then send the verified cash to your Province Hub. These are confirmations recorded in the system; no real payment is made here.</p></div></div>
        @if (session('success'))<div class="lg-note" role="status">{{ session('success') }}</div>@endif
        @if ($errors->any())<div class="lg-note lg-note--warn" role="alert">{{ $errors->first() }}</div>@endif

        <section aria-labelledby="cashStats">
            <h2 class="lg-section-title" id="cashStats" style="margin-bottom:12px">Cash at a glance</h2>
            <div class="lg-grid lg-grid--4">
                @foreach ([
                    ['Held by Riders', '₱'.number_format(array_sum(array_column($ledger, 'on_hand')), 2), 'bike', 'amber'],
                    ['Verified, ready to send', '₱'.number_format((float) $ready->total, 2), 'check-circle', 'green'],
                    ['Open discrepancies', number_format($openDiscrepancies), 'alert', 'red'],
                    ['Sent, awaiting Province', '₱'.number_format($awaitingProvince, 2), 'clock', 'blue'],
                ] as $i => [$label, $value, $icon, $tone])
                    <div class="lg-stat lg-rise" style="--i: {{ $i }}">
                        <span class="lg-stat__icon lg-stat__icon--{{ $tone }}"><x-logistics.icon :name="$icon" :size="24" /></span>
                        <div class="lg-stat__body">
                            <p class="lg-stat__label">{{ $label }}</p>
                            <p class="lg-stat__value">{{ $value }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="lg-card" aria-labelledby="riderCashTitle">
            <div class="lg-card__head"><h2 class="lg-section-title" id="riderCashTitle">Rider cash ledger</h2></div>
            <div class="lg-table-wrap"><table class="lg-table lg-table--stack"><thead><tr><th>Rider</th><th>Parcels holding cash</th><th>Cash on hand</th><th>In discrepancy</th><th>Earnings accrued</th><th>Receive cash</th></tr></thead><tbody>
                @forelse ($ledger as $row)
                    <tr>
                        <td data-label="Rider"><strong>{{ $row['name'] }}</strong></td>
                        <td data-label="Parcels holding cash">{{ count($row['parcels']) }}</td>
                        <td data-label="Cash on hand"><strong>₱{{ number_format($row['on_hand'], 2) }}</strong></td>
                        <td data-label="In discrepancy">{{ $row['disputed'] > 0 ? '₱'.number_format($row['disputed'], 2) : '—' }}</td>
                        <td data-label="Earnings accrued">₱{{ number_format($row['earnings'], 2) }}<div class="lg-cell-sub">Paid out by the Company</div></td>
                        <td data-label="Receive cash">
                            @if (count($row['parcels']) > 0)
                                <details>
                                    <summary class="lg-btn lg-btn--sm" style="display:inline-flex">Receive cash</summary>
                                    <form method="POST" action="{{ route('lgp.station.cash.receive', $row['courier_id']) }}" class="lg-form-grid"
                                          x-data="{ items: @js($row['parcels']), picked: @js(array_column($row['parcels'], 'id')), received: '',
                                                    get expected() { return Math.round(this.items.filter(i => this.picked.includes(i.id)).reduce((t, i) => t + i.amount * 100, 0)) / 100 },
                                                    get differs() { return Math.round(Number(this.received) * 100) !== Math.round(this.expected * 100) } }"
                                          x-init="received = expected.toFixed(2); $watch('picked', () => received = expected.toFixed(2))">
                                        @csrf
                                        <div class="lg-field is-wide">
                                            <label>Parcels handed in</label>
                                            @foreach ($row['parcels'] as $parcel)
                                                <label style="display:flex;gap:8px;align-items:center;font-weight:400">
                                                    <input type="checkbox" name="order_ids[]" value="{{ $parcel['id'] }}" x-model="picked">
                                                    <span>{{ $parcel['tracking'] }} · ₱{{ number_format($parcel['amount'], 2) }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                        <div class="lg-field">
                                            <label for="received{{ $row['courier_id'] }}">Amount received (₱)</label>
                                            <input id="received{{ $row['courier_id'] }}" name="received_amount" type="number" step="0.01" min="0" class="lg-input" x-model="received" required>
                                            <div class="lg-cell-sub">Expected ₱<span x-text="expected.toFixed(2)"></span></div>
                                        </div>
                                        <div class="lg-field" x-show="differs" x-cloak>
                                            <label for="note{{ $row['courier_id'] }}">Why does it differ?</label>
                                            <input id="note{{ $row['courier_id'] }}" name="note" class="lg-input" maxlength="500" :required="differs">
                                        </div>
                                        <div class="is-wide"><button type="submit" class="lg-btn lg-btn--sm" :disabled="picked.length === 0">Confirm receipt</button></div>
                                    </form>
                                </details>
                            @else
                                Nothing to receive
                            @endif
                        </td>
                    </tr>
                @empty<tr><td colspan="6">No Rider cash yet. Cash appears here after a Rider completes a COD delivery.</td></tr>@endforelse
            </tbody></table></div>
        </section>

        <section class="lg-card" aria-labelledby="sendProvinceTitle">
            <div class="lg-card__head"><h2 class="lg-section-title" id="sendProvinceTitle">Send to Province Hub</h2></div>
            <div class="lg-card__body">
                @if ($province)
                    <p>{{ number_format((int) $ready->parcels) }} parcel(s) with verified cash, <strong>₱{{ number_format((float) $ready->total, 2) }}</strong>, ready to send to {{ $province->name }}.</p>
                    <form method="POST" action="{{ route('lgp.station.cash.remit') }}">
                        @csrf
                        <button type="submit" class="lg-btn" @disabled((int) $ready->parcels === 0)>Send to {{ $province->name }}</button>
                    </form>
                @else
                    <p>This Station Hub has no Province Hub assigned, so cash cannot be sent up yet.</p>
                @endif
            </div>
            <div class="lg-table-wrap"><table class="lg-table lg-table--stack"><thead><tr><th>Reference</th><th>To</th><th>Parcels</th><th>Amount</th><th>Status</th><th>Sent</th></tr></thead><tbody>
                @forelse ($sent as $batch)
                    <tr>
                        <td data-label="Reference"><strong>{{ $batch->reference }}</strong></td>
                        <td data-label="To">{{ $batch->toFacility?->name }}</td>
                        <td data-label="Parcels">{{ $batch->items_count }}</td>
                        <td data-label="Amount">₱{{ number_format((float) $batch->expected_amount, 2) }}</td>
                        <td data-label="Status"><span class="lg-pill lg-pill--{{ $batch->status === 'pending' ? 'amber' : ($batch->status === 'discrepancy' ? 'red' : 'green') }}">{{ \App\Models\Finance\CashRemittance::STATUSES[$batch->status] ?? $batch->status }}</span></td>
                        <td data-label="Sent">{{ $batch->submitted_at?->format('M j, g:i A') }}</td>
                    </tr>
                @empty<tr><td colspan="6">Nothing sent to the Province Hub yet.</td></tr>@endforelse
            </tbody></table></div>
        </section>

        <section class="lg-card" aria-labelledby="receiptsTitle">
            <div class="lg-card__head"><h2 class="lg-section-title" id="receiptsTitle">Recent receipts from Riders</h2></div>
            <div class="lg-table-wrap"><table class="lg-table lg-table--stack"><thead><tr><th>Reference</th><th>Rider</th><th>Expected</th><th>Received</th><th>Status</th><th>Confirmed</th></tr></thead><tbody>
                @forelse ($receipts as $receipt)
                    @php $receiptTone = $receipt->status === 'discrepancy' ? 'red' : (in_array($receipt->status, ['received', 'resolved'], true) ? 'green' : 'amber'); @endphp
                    <tr>
                        <td data-label="Reference"><strong>{{ $receipt->reference }}</strong></td>
                        <td data-label="Rider">{{ $receipt->fromRider?->name }}</td>
                        <td data-label="Expected">₱{{ number_format((float) $receipt->expected_amount, 2) }}</td>
                        <td data-label="Received">₱{{ number_format((float) $receipt->received_amount, 2) }}</td>
                        <td data-label="Status"><span class="lg-pill lg-pill--{{ $receiptTone }}">{{ \App\Models\Finance\CashRemittance::STATUSES[$receipt->status] ?? $receipt->status }}</span></td>
                        <td data-label="Confirmed">{{ $receipt->verified_at?->format('M j, g:i A') }}</td>
                    </tr>
                @empty<tr><td colspan="6">No receipts yet.</td></tr>@endforelse
            </tbody></table></div>
        </section>
    </div>
</x-logistics.shell>
