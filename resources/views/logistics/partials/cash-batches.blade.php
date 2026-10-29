{{-- Cash batches sent up to this facility. Variables: $batches (paginator), $role ('province'|'company'), $heading. --}}
<section class="lg-card" aria-labelledby="batchesTitle">
    <div class="lg-card__head"><h2 class="lg-section-title" id="batchesTitle">{{ $heading }}</h2></div>
    <div class="lg-table-wrap"><table class="lg-table lg-table--stack"><thead><tr><th>Reference</th><th>From</th><th>Parcels</th><th>Expected</th><th>Received</th><th>Status</th><th>Action</th></tr></thead><tbody>
        @forelse ($batches as $batch)
            @php $tone = $batch->status === 'discrepancy' ? 'red' : ($batch->status === 'pending' ? 'amber' : 'green'); @endphp
            <tr>
                <td data-label="Reference"><strong>{{ $batch->reference }}</strong><div class="lg-cell-sub">Sent {{ $batch->submitted_at?->format('M j, g:i A') }}</div></td>
                <td data-label="From">{{ $batch->fromFacility?->name ?? '—' }}</td>
                <td data-label="Parcels">{{ $batch->items_count }}</td>
                <td data-label="Expected">₱{{ number_format((float) $batch->expected_amount, 2) }}</td>
                <td data-label="Received">{{ $batch->received_amount !== null ? '₱'.number_format((float) $batch->received_amount, 2) : '—' }}</td>
                <td data-label="Status"><span class="lg-pill lg-pill--{{ $tone }}">{{ \App\Models\Finance\CashRemittance::STATUSES[$batch->status] ?? $batch->status }}</span></td>
                <td data-label="Action">
                    @if ($batch->status === 'pending')
                        <details>
                            <summary class="lg-btn lg-btn--sm" style="display:inline-flex">Confirm receipt</summary>
                            <form method="POST" action="{{ route('lgp.'.$role.'.cash.receive', $batch) }}" class="lg-form-grid"
                                  x-data="{ expected: {{ (float) $batch->expected_amount }}, received: '{{ number_format((float) $batch->expected_amount, 2, '.', '') }}',
                                            get differs() { return Math.round(Number(this.received) * 100) !== Math.round(this.expected * 100) } }">
                                @csrf
                                <div class="lg-field">
                                    <label for="received{{ $batch->id }}">Amount received (₱)</label>
                                    <input id="received{{ $batch->id }}" name="received_amount" type="number" step="0.01" min="0" class="lg-input" x-model="received" required>
                                    <div class="lg-cell-sub">Expected ₱{{ number_format((float) $batch->expected_amount, 2) }}</div>
                                </div>
                                <div class="lg-field" x-show="differs" x-cloak>
                                    <label for="note{{ $batch->id }}">Why does it differ?</label>
                                    <input id="note{{ $batch->id }}" name="note" class="lg-input" maxlength="500" :required="differs">
                                </div>
                                <div class="is-wide"><button type="submit" class="lg-btn lg-btn--sm">Confirm receipt</button></div>
                            </form>
                        </details>
                    @elseif ($batch->status === 'discrepancy')
                        @if ($batch->note)<div class="lg-cell-sub">Receipt note: {{ $batch->note }}</div>@endif
                        <form method="POST" action="{{ route('lgp.'.$role.'.cash.resolve', $batch) }}" class="lg-form-grid">
                            @csrf @method('PATCH')
                            <div class="lg-field"><label for="resolution{{ $batch->id }}">Resolution note</label><input id="resolution{{ $batch->id }}" name="resolution_note" class="lg-input" required minlength="5" maxlength="1000"></div>
                            <div><button class="lg-btn lg-btn--sm" type="submit">Resolve</button></div>
                        </form>
                    @else
                        {{ $batch->status === 'resolved' ? $batch->resolution_note : 'Confirmed' }}
                        <div class="lg-cell-sub">{{ $batch->verifiedBy?->name }} · {{ ($batch->resolved_at ?? $batch->verified_at)?->format('M j, g:i A') }}</div>
                    @endif
                </td>
            </tr>
        @empty<tr><td colspan="7">Nothing has been sent to you yet.</td></tr>@endforelse
    </tbody></table></div>
    {{ $batches->links() }}
</section>
