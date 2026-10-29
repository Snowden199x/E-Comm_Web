<x-logistics.shell title="Cash Remittances" role="company">
    <div class="lg-page">
        <div class="lg-page-head"><div><h1>Cash Remittances</h1><p>COD cash your Province Hubs send up. Confirm each batch, then send Vendo what it is owed: the item price plus Vendo's delivery commission. You keep your delivery fee share and hold the Riders' pay for payout. These are confirmations recorded in the system; no real payment is made here.</p></div></div>
        @if (session('success'))<div class="lg-note" role="status">{{ session('success') }}</div>@endif
        @if ($errors->any())<div class="lg-note lg-note--warn" role="alert">{{ $errors->first() }}</div>@endif

        <section aria-labelledby="cashStats">
            <h2 class="lg-section-title" id="cashStats" style="margin-bottom:12px">Cash at a glance</h2>
            <div class="lg-grid lg-grid--4">
                @foreach ([
                    ['Awaiting your confirmation', '₱'.number_format($awaitingConfirm, 2), 'clock', 'amber'],
                    ['Open discrepancies', number_format($openDiscrepancies), 'alert', 'red'],
                    ['Due to Vendo', '₱'.number_format($dueCents / 100, 2), 'check-circle', 'green'],
                    ['Sent, awaiting Vendo', '₱'.number_format($awaitingVendo, 2), 'arrow-right', 'blue'],
                ] as $i => [$label, $value, $icon, $tone])
                    <div class="lg-stat lg-rise" style="--i: {{ $i }}">
                        <span class="lg-stat__icon lg-stat__icon--{{ $tone }}"><x-logistics.icon :name="$icon" :size="24" /></span>
                        <div class="lg-stat__body"><p class="lg-stat__label">{{ $label }}</p><p class="lg-stat__value">{{ $value }}</p></div>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="lg-card" aria-labelledby="earningsTitle">
            <div class="lg-card__head"><h2 class="lg-section-title" id="earningsTitle">Delivery fee split on cash received</h2></div>
            <div class="lg-card__body">
                <div class="lg-grid lg-grid--3">
                    <div><p class="lg-stat__label">Your earnings</p><p class="lg-stat__value">₱{{ number_format((float) $settled->company, 2) }}</p></div>
                    <div><p class="lg-stat__label">Rider pay you hold for payout</p><p class="lg-stat__value">₱{{ number_format((float) $settled->rider, 2) }}</p></div>
                    <div><p class="lg-stat__label">Vendo's delivery commission</p><p class="lg-stat__value">₱{{ number_format((float) $settled->vendo, 2) }}</p></div>
                </div>
                <p class="lg-cell-sub">Counted only for parcels whose cash you have confirmed. Rider salaries and pickup work are paid outside this system.</p>
            </div>
        </section>

        @include('logistics.partials.cash-batches', ['batches' => $incoming, 'role' => 'company', 'heading' => 'Batches from Province Hubs'])

        <section class="lg-card" aria-labelledby="sendVendoTitle">
            <div class="lg-card__head"><h2 class="lg-section-title" id="sendVendoTitle">Send to Vendo</h2></div>
            <div class="lg-card__body">
                <p>{{ number_format($dueCount) }} parcel(s) with confirmed cash. Vendo is owed <strong>₱{{ number_format($dueCents / 100, 2) }}</strong>.</p>
                <form method="POST" action="{{ route('lgp.company.cash.remit') }}">
                    @csrf
                    <button type="submit" class="lg-btn" @disabled($dueCount === 0)>Send to Vendo</button>
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
                @empty<tr><td colspan="5">Nothing sent to Vendo yet.</td></tr>@endforelse
            </tbody></table></div>
        </section>
    </div>
</x-logistics.shell>
