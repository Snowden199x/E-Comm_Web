<x-admin.layout title="Cash Remittances">
    <div class="mx-auto w-full max-w-[1280px] p-4 sm:p-5 lg:p-6">
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Cash Remittances</h2>
            <p class="text-gray-500">Cash each Logistics Company sends Vendo: the item price plus Vendo's delivery commission for confirmed parcels. Confirming a batch records receipt in the system and credits the Sellers on it automatically on their Earnings page; no real payment is made here.</p>
        </div>

        @if (session('success'))<div class="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800" role="status">{{ session('success') }}</div>@endif
        @if ($errors->any())<div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">{{ $errors->first() }}</div>@endif

        <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
            @foreach ([
                ['Awaiting your confirmation', '₱'.number_format($awaiting, 2), 'bg-orange-50 border-orange-500'],
                ['Open discrepancies', number_format($openDiscrepancies), 'bg-red-50 border-red-400'],
                ['Confirmed', '₱'.number_format($confirmed, 2), 'bg-green-50 border-green-500'],
                ['Confirmed batches', number_format($confirmedBatches), 'bg-purple-50 border-[#3b1735]'],
            ] as [$label, $value, $classes])
                <div class="rounded-2xl border-2 p-4 {{ $classes }}">
                    <p class="text-xs text-gray-600">{{ $label }}</p>
                    <p class="text-xl font-bold text-black">{{ $value }}</p>
                </div>
            @endforeach
        </div>

        <div class="rounded-2xl bg-white p-5 shadow-sm">
            <h3 class="mb-3 text-lg font-semibold text-gray-900">Batches from Logistics Companies</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b text-xs uppercase text-gray-500"><tr>
                        <th class="py-2 pr-3">Reference</th><th class="py-2 pr-3">Company</th><th class="py-2 pr-3">Parcels</th><th class="py-2 pr-3">Expected</th><th class="py-2 pr-3">Received</th><th class="py-2 pr-3">Status</th><th class="py-2">Action</th>
                    </tr></thead>
                    <tbody class="divide-y">
                        @forelse ($batches as $batch)
                            @php $tone = $batch->status === 'discrepancy' ? 'bg-red-100 text-red-800' : ($batch->status === 'pending' ? 'bg-amber-100 text-amber-800' : 'bg-green-100 text-green-800'); @endphp
                            <tr class="align-top">
                                <td class="py-3 pr-3"><span class="font-medium">{{ $batch->reference }}</span><div class="text-xs text-gray-500">Sent {{ $batch->submitted_at?->format('M j, g:i A') }}</div></td>
                                <td class="py-3 pr-3">{{ $batch->company?->business_name ?? '—' }}</td>
                                <td class="py-3 pr-3">{{ $batch->items_count }}</td>
                                <td class="py-3 pr-3">₱{{ number_format((float) $batch->expected_amount, 2) }}</td>
                                <td class="py-3 pr-3">{{ $batch->received_amount !== null ? '₱'.number_format((float) $batch->received_amount, 2) : '—' }}</td>
                                <td class="py-3 pr-3"><span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $tone }}">{{ \App\Models\Finance\CashRemittance::STATUSES[$batch->status] ?? $batch->status }}</span></td>
                                <td class="py-3">
                                    @if ($batch->status === 'pending')
                                        <details>
                                            <summary class="inline-block cursor-pointer rounded-xl bg-[#3b1735] px-3 py-1.5 text-xs font-semibold text-white">Confirm receipt</summary>
                                            <form method="POST" action="{{ route('admin.remittances.receive', $batch) }}" class="mt-3 grid gap-3"
                                                  x-data="{ expected: {{ (float) $batch->expected_amount }}, received: '{{ number_format((float) $batch->expected_amount, 2, '.', '') }}',
                                                            get differs() { return Math.round(Number(this.received) * 100) !== Math.round(this.expected * 100) } }">
                                                @csrf
                                                <div>
                                                    <label for="received{{ $batch->id }}" class="mb-1 block text-xs font-medium text-gray-700">Amount received (₱)</label>
                                                    <input id="received{{ $batch->id }}" name="received_amount" type="number" step="0.01" min="0" x-model="received" class="w-full rounded-xl border border-gray-300 px-3 py-2" required>
                                                    <p class="mt-1 text-xs text-gray-500">Expected ₱{{ number_format((float) $batch->expected_amount, 2) }}</p>
                                                </div>
                                                <div x-show="differs" x-cloak>
                                                    <label for="note{{ $batch->id }}" class="mb-1 block text-xs font-medium text-gray-700">Why does it differ?</label>
                                                    <input id="note{{ $batch->id }}" name="note" maxlength="500" :required="differs" class="w-full rounded-xl border border-gray-300 px-3 py-2">
                                                </div>
                                                <div><button type="submit" class="rounded-xl bg-[#3b1735] px-3 py-1.5 text-xs font-semibold text-white">Confirm receipt</button></div>
                                            </form>
                                        </details>
                                    @elseif ($batch->status === 'discrepancy')
                                        @if ($batch->note)<p class="mb-2 text-xs text-gray-500">Receipt note: {{ $batch->note }}</p>@endif
                                        <form method="POST" action="{{ route('admin.remittances.resolve', $batch) }}" class="grid gap-2">
                                            @csrf @method('PATCH')
                                            <label for="resolution{{ $batch->id }}" class="text-xs font-medium text-gray-700">Resolution note</label>
                                            <input id="resolution{{ $batch->id }}" name="resolution_note" required minlength="5" maxlength="1000" class="w-full rounded-xl border border-gray-300 px-3 py-2">
                                            <div><button type="submit" class="rounded-xl bg-[#3b1735] px-3 py-1.5 text-xs font-semibold text-white">Resolve</button></div>
                                        </form>
                                    @else
                                        <span class="text-gray-700">{{ $batch->status === 'resolved' ? $batch->resolution_note : 'Confirmed' }}</span>
                                        <div class="text-xs text-gray-500">{{ $batch->verifiedBy?->name }} · {{ ($batch->resolved_at ?? $batch->verified_at)?->format('M j, g:i A') }}</div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="py-6 text-center text-gray-500">No Logistics Company has sent cash to Vendo yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $batches->links() }}</div>
        </div>
    </div>
</x-admin.layout>
