@php
    $card = 'rounded-lg border border-[#eee6ef] bg-white';
    $primary = 'inline-flex h-10 items-center justify-center rounded-md bg-[#402143] px-5 text-[13px] font-medium text-white transition-all duration-300 ease-vendo hover:bg-[#52245b] active:scale-[0.97]';
    $open = (int) ($counts['open'] ?? 0);
    $review = (int) ($counts['in_review'] ?? 0);
    $resolved = (int) ($counts['resolved'] ?? 0);
@endphp

<x-buyer.layout title="Complaints — Vendo">
    <div class="mx-auto max-w-[1040px] px-3 pb-10 pt-4 sm:px-4">
        <div class="vb-reveal flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-[20px] font-semibold text-[#2b1730]">Complaints</h1>
                <p class="mt-0.5 max-w-[640px] text-[13px] leading-5 text-[#7a6a7e]">Reports you sent to Vendo about an order, a shop or a delivery. Admin reviews each case and replies in the case messages.</p>
            </div>
            <a href="{{ route('buyer.orders.index') }}" class="{{ $primary }}">Go to my orders</a>
        </div>

        <div class="vb-reveal mt-4 grid gap-3 sm:grid-cols-3" style="--i: 1">
            @foreach ([['Open', $open, '#B45309', '#FEF3C7'], ['In review', $review, '#2563EB', '#DBEAFE'], ['Resolved', $resolved, '#15803D', '#DCFCE7']] as [$label, $total, $ink, $tint])
                <div class="{{ $card }} flex items-center gap-3 p-4">
                    <span class="grid h-10 w-10 place-items-center rounded-full text-[15px] font-semibold" style="background: {{ $tint }}; color: {{ $ink }};">{{ $total }}</span>
                    <div>
                        <p class="text-[12px] font-medium uppercase tracking-wide text-[#9a8a9d]">{{ $label }}</p>
                        <p class="text-[13px] text-[#3d2a42]">{{ $total === 1 ? '1 complaint' : $total . ' complaints' }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        <section class="vb-reveal mt-4" style="--i: 2" aria-labelledby="complaints-list-title">
            <h2 id="complaints-list-title" class="sr-only">Your complaints</h2>

            @if ($complaints->isEmpty())
                <div class="{{ $card }} px-5 py-12 text-center">
                    <span class="mx-auto grid h-12 w-12 place-items-center rounded-full bg-[#f3e8f5] text-[#805487]">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3l7 3v5c0 5-3 8.5-7 10-4-1.5-7-5-7-10V6z" /><path d="M12 9v3M12 15.5h.01" /></svg>
                    </span>
                    <p class="mt-3 text-[14px] font-semibold text-[#2b1730]">No complaints yet</p>
                    <p class="mx-auto mt-1 max-w-[380px] text-[13px] leading-5 text-[#7a6a7e]">You can report a problem from an order or from a chat with a Seller. Your case and its replies will appear here.</p>
                    <div class="mt-4 flex flex-wrap justify-center gap-2">
                        <a href="{{ route('buyer.orders.index') }}" class="{{ $primary }}">View orders</a>
                        <a href="{{ route('buyer.messages.index') }}" class="inline-flex h-10 items-center rounded-md border border-[#e5dce7] px-5 text-[13px] font-medium text-[#3d2a42] transition-colors duration-200 hover:border-[#c9a9ce] hover:bg-[#faf5fa]">Open messages</a>
                    </div>
                </div>
            @else
                <ul class="space-y-3">
                    @foreach ($complaints as $complaint)
                        @php
                            $status = $complaint->status_colors;
                            $type = $complaint->type_colors;
                        @endphp
                        <li class="{{ $card }} p-4 sm:p-5">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="rounded px-2 py-0.5 text-[11px] font-semibold" style="background: {{ $type['bg'] }}; color: {{ $type['border'] }};">{{ $complaint->type }}</span>
                                        <span class="rounded px-2 py-0.5 text-[11px] font-semibold" style="background: {{ $status['bg'] }}; color: {{ $status['border'] }};">{{ $status['label'] }}</span>
                                        @if ($complaint->kind === 'user_report')
                                            <span class="text-[11px] text-[#9a8a9d]">Account report</span>
                                        @endif
                                    </div>
                                    <p class="mt-2 line-clamp-3 text-[13px] leading-5 text-[#3d2a42]">{{ $complaint->description }}</p>
                                    <p class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-[#8a7a8e]">
                                        <span>Filed {{ $complaint->created_at?->format('M j, Y g:i A') }}</span>
                                        @if ($complaint->respondent)
                                            <span>About {{ $complaint->respondent->name }}</span>
                                        @endif
                                        @if ($complaint->evidences_count)
                                            <span>{{ $complaint->evidences_count }} {{ \Illuminate\Support\Str::plural('attachment', $complaint->evidences_count) }}</span>
                                        @endif
                                    </p>
                                </div>
                                <div class="flex flex-shrink-0 flex-wrap items-center gap-2">
                                    @if ($complaint->order)
                                        <a href="{{ route('buyer.orders.show', $complaint->order) }}"
                                            class="inline-flex h-9 items-center rounded-md border border-[#e5dce7] px-3.5 text-[12px] font-medium text-[#3d2a42] transition-colors duration-200 hover:border-[#c9a9ce] hover:bg-[#faf5fa]">Order {{ $complaint->order->number }}</a>
                                    @endif
                                    <a href="{{ route('buyer.case-messages.show', $complaint) }}"
                                        class="inline-flex h-9 items-center rounded-md border border-[#805487] px-3.5 text-[12px] font-medium text-[#52245b] transition-colors duration-200 hover:bg-[#f5ecf6]">Case messages</a>
                                </div>
                            </div>

                            @if ($complaint->decision)
                                <p class="mt-3 rounded-md border border-[#c9e5d3] bg-[#eaf5ee] px-3 py-2 text-[12px] leading-5 text-[#2e6b46]"><span class="font-semibold">Admin decision:</span> {{ $complaint->decision }}</p>
                            @endif
                        </li>
                    @endforeach
                </ul>

                <div class="mt-4">{{ $complaints->links() }}</div>
            @endif
        </section>
    </div>
</x-buyer.layout>
