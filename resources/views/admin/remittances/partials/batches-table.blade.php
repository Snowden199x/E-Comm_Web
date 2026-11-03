{{--
    Cash batches from Logistics Companies. Rendered inside #rm-table and swapped on every search, filter or page change.
    Needs: $batches (paginator), $status, $search.
    Row buttons carry their batch as data-* and open the dialog in index.blade.php (openDialog).
    Page buttons carry data-page and are handled by the page scope (onTableClick).
--}}
@php
    $isFiltered = $status !== 'all' || $search !== '';

    // Status chips follow the admin design system: waiting = warning, problem = danger, done = success.
    $chip = fn (string $s) => match ($s) {
        'pending' => 'border-vd-warning-line bg-vd-warning-bg text-vd-warning-ink',
        'discrepancy' => 'border-vd-danger-line bg-vd-danger-bg text-vd-danger-ink',
        default => 'border-vd-success-line bg-vd-success-bg text-vd-success-ink',
    };

    $primary = 'inline-flex h-8 items-center rounded-lg bg-vd-plum px-3 text-[13px] font-semibold text-white transition-all duration-200
                hover:bg-vd-plum-hover active:scale-95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-plum/40 focus-visible:ring-offset-2';
@endphp

<div class="overflow-hidden rounded-xl border border-vd-line bg-white">
    @if ($batches->isEmpty())
        @if ($isFiltered)
            <x-admin.empty-state filtered filteredTitle="No batches found" filteredText="Check the reference or Company name, or pick another status.">
                <button type="button" @click="clearAll()"
                    class="inline-flex h-9 items-center rounded-lg border border-vd-line-strong bg-white px-4 text-[13px] font-medium text-vd-plum transition-colors duration-200 hover:bg-vd-tint">
                    Clear filters
                </button>
            </x-admin.empty-state>
        @else
            <x-admin.empty-state icon="banknote" title="No batches yet" text="Batches appear here when a Logistics Company sends cash to Vendo." />
        @endif
    @else
        <div class="thin-scroll overflow-x-auto">
            <table class="w-full min-w-[900px] text-left text-sm">
                <caption class="sr-only">Cash batches sent by Logistics Companies</caption>
                <thead>
                    <tr class="border-b border-vd-line bg-vd-tint text-[13px] text-vd-muted">
                        <th scope="col" class="px-5 py-3 font-medium">Reference</th>
                        <th scope="col" class="px-4 py-3 font-medium">Company</th>
                        <th scope="col" class="px-4 py-3 text-center font-medium">Parcels</th>
                        <th scope="col" class="px-4 py-3 text-right font-medium">Expected</th>
                        <th scope="col" class="px-4 py-3 text-right font-medium">Received</th>
                        <th scope="col" class="px-4 py-3 font-medium">Status</th>
                        <th scope="col" class="px-5 py-3 font-medium">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-vd-line">
                    @foreach ($batches as $batch)
                        @php
                            $expected = (float) $batch->expected_amount;
                            $received = $batch->received_amount !== null ? (float) $batch->received_amount : null;
                            $diff = $received !== null ? round($received - $expected, 2) : 0;
                            $companyName = $batch->company?->business_name ?? '—';
                            $label = \App\Models\Finance\CashRemittance::STATUSES[$batch->status] ?? $batch->status;
                            $done = in_array($batch->status, ['received', 'resolved'], true);
                            $doneBy = $batch->status === 'resolved' ? $batch->resolvedBy?->name : $batch->verifiedBy?->name;
                            $doneAt = $batch->status === 'resolved' ? $batch->resolved_at : $batch->verified_at;
                        @endphp
                        <tr style="--i: {{ $loop->index }}" class="align-top transition-colors duration-200 hover:bg-vd-tint">
                            <td class="whitespace-nowrap px-5 py-3">
                                <p class="font-medium text-vd-ink">{{ $batch->reference }}</p>
                                <p class="text-xs text-vd-muted">Sent {{ $batch->submitted_at?->format('M j, Y, g:i A') }}</p>
                            </td>
                            <td class="px-4 py-3 text-vd-ink"><span class="block max-w-[220px] truncate" title="{{ $companyName }}">{{ $companyName }}</span></td>
                            <td class="px-4 py-3 text-center tabular-nums text-vd-muted">{{ number_format($batch->items_count) }}</td>
                            <td class="px-4 py-3 text-right tabular-nums text-vd-ink">₱{{ number_format($expected, 2) }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">
                                @if ($received !== null)
                                    <p class="text-vd-ink">₱{{ number_format($received, 2) }}</p>
                                    @if ($diff != 0)
                                        <p class="text-xs {{ $batch->status === 'discrepancy' ? 'font-medium text-vd-danger-ink' : 'text-vd-muted' }}">
                                            ₱{{ number_format(abs($diff), 2) }} {{ $diff < 0 ? 'short' : 'over' }}
                                        </p>
                                    @endif
                                @else
                                    <span class="text-vd-muted">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium {{ $chip($batch->status) }}">{{ $label }}</span>
                            </td>
                            <td class="px-5 py-3">
                                @if ($batch->status === 'pending' || $batch->status === 'discrepancy')
                                    <button type="button" data-open-batch
                                        data-id="{{ $batch->id }}" data-reference="{{ $batch->reference }}" data-company="{{ $companyName }}"
                                        data-parcels="{{ $batch->items_count }}" data-expected="{{ number_format($expected, 2, '.', '') }}"
                                        data-received="{{ $received !== null ? number_format($received, 2, '.', '') : '' }}" data-note="{{ $batch->note }}"
                                        data-mode="{{ $batch->status === 'pending' ? 'confirm' : 'resolve' }}"
                                        @click="openDialog($el)" aria-haspopup="dialog"
                                        aria-label="{{ $batch->status === 'pending' ? 'Confirm receipt' : 'Resolve' }} for {{ $batch->reference }}"
                                        class="{{ $primary }}">
                                        {{ $batch->status === 'pending' ? 'Confirm receipt' : 'Resolve' }}
                                    </button>
                                @elseif ($done)
                                    <p class="text-vd-ink">{{ $batch->status === 'resolved' ? 'Resolved' : 'Confirmed' }}@if ($doneBy) by {{ $doneBy }}@endif</p>
                                    <p class="text-xs text-vd-muted">{{ $doneAt?->format('M j, Y, g:i A') }}</p>
                                    @if ($batch->status === 'resolved' && $batch->resolution_note)
                                        <p class="mt-1 max-w-[240px] truncate text-xs text-vd-muted" title="{{ $batch->resolution_note }}">{{ $batch->resolution_note }}</p>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @include('admin.partials.pagination', ['paginator' => $batches])
    @endif
</div>

<p class="mt-3 text-xs text-vd-muted">Confirming a batch records receipt in Vendo. No real payment is made here.</p>
