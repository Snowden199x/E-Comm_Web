@php
    $money = fn ($amount) => '₱'.number_format((float) $amount, 2);
@endphp
<x-seller.layout title="Returns">
    @vite(['resources/css/seller/operations.css', 'resources/css/seller/delivered-orders.css'])
    <section class="ops-page do-page">
        <header class="do-head">
            <div>
                <h1>Returns</h1>
                <p>Buyer return requests. Approving one takes the order back, restores the stock and records the refund.</p>
            </div>
            <a class="do-btn do-btn--soft" href="{{ route('seller.completed-orders.index') }}">View delivered orders</a>
        </header>

        @if (session('success'))
            <div class="do-alert do-alert--success" role="status">
                <div><p>{{ session('success') }}</p></div>
            </div>
        @endif
        @if ($errors->any())
            <div class="do-alert do-alert--error" role="alert">
                <div>@foreach ($errors->all() as $message)<p>{{ $message }}</p>@endforeach</div>
            </div>
        @endif

        <section class="do-card do-table-card" aria-label="Return requests waiting for your decision">
            <h2 class="do-card__head">Waiting for your decision <span class="er-count">{{ $pending->count() }}</span></h2>
            <div class="ops-table-scroll">
                <table class="do-table">
                    <thead><tr><th>Order</th><th>Buyer</th><th>Reason</th><th>Requested</th><th>Refund if approved</th><th>Decision</th></tr></thead>
                    <tbody>
                    @forelse ($pending as $request)
                        <tr>
                            <td>
                                <strong>#{{ $request->order?->number }}</strong>
                                <small>{{ $request->order?->status_label }}</small>
                            </td>
                            <td>{{ $request->buyer?->name ?? 'Buyer' }}</td>
                            <td>
                                {{ $request->reason_label }}
                                @if (filled($request->details))<small>{{ $request->details }}</small>@endif
                            </td>
                            <td>{{ $request->created_at?->format('M j, Y g:i A') }}</td>
                            <td><strong>{{ $request->order ? $money($request->order->payable_total) : '—' }}</strong></td>
                            <td>
                                <div class="flex flex-wrap items-start gap-2">
                                    <form action="{{ route('seller.returns.approve', $request) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="do-btn do-btn--primary do-btn--small">Approve return</button>
                                    </form>
                                    <details class="w-full">
                                        <summary class="do-btn do-btn--soft do-btn--small" style="display:inline-flex">Decline</summary>
                                        <form action="{{ route('seller.returns.reject', $request) }}" method="POST" class="mt-2 space-y-2">
                                            @csrf
                                            @method('PATCH')
                                            <label for="note-{{ $request->id }}" class="block text-[12px] font-medium text-[#402143]">Reason for the buyer</label>
                                            <textarea id="note-{{ $request->id }}" name="decision_note" rows="2" maxlength="450" required
                                                class="block w-full rounded-md border border-[#e5dce7] px-3 py-2 text-[13px] text-[#2b1730]">{{ old('decision_note') }}</textarea>
                                            <button type="submit" class="do-btn do-btn--danger-soft do-btn--small">Send decline</button>
                                        </form>
                                    </details>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="do-empty-cell">
                            <div class="do-empty">
                                <span class="do-empty__icon do-tone-mint"><svg class="do-ico" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8 12.5 3 3 5-6"/></svg></span>
                                <strong>No return requests waiting</strong>
                                <p>New requests appear here and in your notifications.</p>
                            </div>
                        </td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="do-card do-table-card" aria-label="Handled return requests">
            <h2 class="do-card__head">Recently handled</h2>
            <div class="ops-table-scroll">
                <table class="do-table">
                    <thead><tr><th>Order</th><th>Buyer</th><th>Decision</th><th>Return trip</th><th>Refund</th><th>Note</th><th>When</th></tr></thead>
                    <tbody>
                    @forelse ($decided as $request)
                        <tr>
                            <td><strong>#{{ $request->order?->number }}</strong></td>
                            <td>{{ $request->buyer?->name ?? 'Buyer' }}</td>
                            <td>
                                @if ($request->status === 'approved')
                                    <span class="do-badge do-badge--mint">Approved</span>
                                @else
                                    <span class="do-badge do-badge--lilac">Declined</span>
                                @endif
                            </td>
                            <td>
                                @if ($request->status === 'approved')
                                    <span class="do-badge do-badge--lilac">{{ $request->return_status_label }}</span>
                                    @if ($request->return_status === 'picked_up')
                                        <form action="{{ route('seller.returns.received', $request) }}" method="POST" class="mt-2">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="do-btn do-btn--soft do-btn--small">Mark received</button>
                                        </form>
                                    @endif
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $request->status === 'approved' ? $money($request->refund_cents / 100) : '—' }}</td>
                            <td>{{ $request->decision_note ?? '—' }}</td>
                            <td>{{ $request->decided_at?->format('M j, Y g:i A') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="do-empty-cell"><div class="do-empty"><strong>Nothing handled yet</strong><p>Approved and declined requests are kept here.</p></div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </section>
</x-seller.layout>
