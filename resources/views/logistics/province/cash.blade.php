<x-logistics.shell title="Cash Remittances" role="province">
    <div class="lg-page">
        <div class="lg-page-head"><div><h1>Cash Remittances</h1><p>COD cash your Station Hubs send up. Confirm each batch, then send the verified cash to your Company. These are confirmations recorded in the system; no real payment is made here.</p></div></div>
        @if (session('success'))<div class="lg-note" role="status">{{ session('success') }}</div>@endif
        @if ($errors->any())<div class="lg-note lg-note--warn" role="alert">{{ $errors->first() }}</div>@endif

        <section aria-labelledby="cashStats">
            <h2 class="lg-section-title" id="cashStats" style="margin-bottom:12px">Cash at a glance</h2>
            <div class="lg-grid lg-grid--4">
                @foreach ([
                    ['Awaiting your confirmation', '₱'.number_format($awaitingConfirm, 2), 'clock', 'amber'],
                    ['Verified, ready to send', '₱'.number_format((float) $ready->total, 2), 'check-circle', 'green'],
                    ['Open discrepancies', number_format($openDiscrepancies), 'alert', 'red'],
                    ['Sent, awaiting Company', '₱'.number_format($awaitingCompany, 2), 'arrow-right', 'blue'],
                ] as $i => [$label, $value, $icon, $tone])
                    <div class="lg-stat lg-rise" style="--i: {{ $i }}">
                        <span class="lg-stat__icon lg-stat__icon--{{ $tone }}"><x-logistics.icon :name="$icon" :size="24" /></span>
                        <div class="lg-stat__body"><p class="lg-stat__label">{{ $label }}</p><p class="lg-stat__value">{{ $value }}</p></div>
                    </div>
                @endforeach
            </div>
        </section>

        @include('logistics.partials.cash-batches', ['batches' => $incoming, 'role' => 'province', 'heading' => 'Batches from Station Hubs'])

        <section class="lg-card" aria-labelledby="sendCompanyTitle">
            <div class="lg-card__head"><h2 class="lg-section-title" id="sendCompanyTitle">Send to Company</h2></div>
            <div class="lg-card__body">
                <p>{{ number_format((int) $ready->parcels) }} parcel(s) with verified cash, <strong>₱{{ number_format((float) $ready->total, 2) }}</strong>, ready to send to the Company.</p>
                <form method="POST" action="{{ route('lgp.province.cash.remit') }}">
                    @csrf
                    <button type="submit" class="lg-btn" @disabled((int) $ready->parcels === 0)>Send to Company</button>
                </form>
            </div>
            <div class="lg-table-wrap"><table class="lg-table lg-table--stack"><thead><tr><th>Reference</th><th>Parcels</th><th>Amount</th><th>Status</th><th>Sent</th></tr></thead><tbody>
                @forelse ($sent as $batch)
                    <tr>
                        <td data-label="Reference"><strong>{{ $batch->reference }}</strong></td>
                        <td data-label="Parcels">{{ $batch->items_count }}</td>
                        <td data-label="Amount">₱{{ number_format((float) $batch->expected_amount, 2) }}</td>
                        <td data-label="Status"><span class="lg-pill lg-pill--{{ $batch->status === 'pending' ? 'amber' : ($batch->status === 'discrepancy' ? 'red' : 'green') }}">{{ \App\Models\Finance\CashRemittance::STATUSES[$batch->status] ?? $batch->status }}</span></td>
                        <td data-label="Sent">{{ $batch->submitted_at?->format('M j, g:i A') }}</td>
                    </tr>
                @empty<tr><td colspan="5">Nothing sent to the Company yet.</td></tr>@endforelse
            </tbody></table></div>
        </section>
    </div>
</x-logistics.shell>
