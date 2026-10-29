<x-logistics.shell title="Cash Discrepancies" role="station">
    <div class="lg-page">
        <div class="lg-page-head"><div><h1>Cash Discrepancies</h1><p>Receipts where the cash handed in by a Rider did not match what was collected. Parcels stay on hold until the discrepancy is resolved with a note. Once resolved, their cash counts in full and can be sent to the Province Hub.</p></div></div>
        @if (session('success'))<div class="lg-note" role="status">{{ session('success') }}</div>@endif
        @if ($errors->any())<div class="lg-note lg-note--warn" role="alert">{{ $errors->first() }}</div>@endif

        <section class="lg-card" aria-labelledby="discrepancyTitle">
            <div class="lg-card__head"><h2 class="lg-section-title" id="discrepancyTitle">Discrepancies</h2></div>
            <div class="lg-table-wrap"><table class="lg-table lg-table--stack"><thead><tr><th>Reference</th><th>Rider and parcels</th><th>Expected</th><th>Received</th><th>Difference</th><th>Status</th><th>Resolution</th></tr></thead><tbody>
                @forelse ($remittances as $remittance)
                    @php $difference = (float) $remittance->received_amount - (float) $remittance->expected_amount; @endphp
                    <tr>
                        <td data-label="Reference"><strong>{{ $remittance->reference }}</strong><div class="lg-cell-sub">{{ $remittance->verified_at?->format('M j, g:i A') }}</div></td>
                        <td data-label="Rider and parcels">{{ $remittance->fromRider?->name }}<div class="lg-cell-sub">{{ $remittance->items->map(fn ($item) => $item->order?->tracking_number)->filter()->join(', ') }}</div>@if ($remittance->note)<div class="lg-cell-sub">Receipt note: {{ $remittance->note }}</div>@endif</td>
                        <td data-label="Expected">₱{{ number_format((float) $remittance->expected_amount, 2) }}</td>
                        <td data-label="Received">₱{{ number_format((float) $remittance->received_amount, 2) }}</td>
                        <td data-label="Difference"><strong>{{ $difference < 0 ? '−' : '+' }}₱{{ number_format(abs($difference), 2) }}</strong> {{ $difference < 0 ? 'short' : 'over' }}</td>
                        <td data-label="Status"><span class="lg-pill lg-pill--{{ $remittance->status === 'discrepancy' ? 'red' : 'green' }}">{{ \App\Models\Finance\CashRemittance::STATUSES[$remittance->status] ?? $remittance->status }}</span></td>
                        <td data-label="Resolution">
                            @if ($remittance->status === 'discrepancy')
                                <form method="POST" action="{{ route('lgp.station.cash.resolve', $remittance) }}" class="lg-form-grid">
                                    @csrf @method('PATCH')
                                    <div class="lg-field"><label for="resolution{{ $remittance->id }}">Resolution note</label><input id="resolution{{ $remittance->id }}" name="resolution_note" class="lg-input" required minlength="5" maxlength="1000"></div>
                                    <div><button class="lg-btn lg-btn--sm" type="submit">Resolve</button></div>
                                </form>
                            @else
                                {{ $remittance->resolution_note }}<div class="lg-cell-sub">{{ $remittance->resolvedBy?->name }} · {{ $remittance->resolved_at?->format('M j, g:i A') }}</div>
                            @endif
                        </td>
                    </tr>
                @empty<tr><td colspan="7">No cash discrepancies.</td></tr>@endforelse
            </tbody></table></div>
            {{ $remittances->links() }}
        </section>
    </div>
</x-logistics.shell>
