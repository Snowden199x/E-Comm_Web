@php
    $card = 'rounded-lg border border-[#eee6ef] bg-white';
    $primary = 'inline-flex h-10 items-center justify-center rounded-md bg-[#402143] px-5 text-[13px] font-medium text-white transition-all duration-300 ease-vendo hover:bg-[#52245b] active:scale-[0.97]';
    $open = (int) ($counts['open'] ?? 0);
    $review = (int) ($counts['in_review'] ?? 0);
    $resolved = (int) ($counts['resolved'] ?? 0);
@endphp

<x-buyer.layout title="Support — Vendo">
    <div class="mx-auto max-w-[1040px] px-3 pb-10 pt-4 sm:px-4">
        <div class="vb-reveal flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-[20px] font-semibold text-[#2b1730]">Support</h1>
                <p class="mt-0.5 max-w-[640px] text-[13px] leading-5 text-[#7a6a7e]">Ask Vendo about an order, a payment, your account or anything else. Replies appear in the same thread.</p>
            </div>
            <a href="{{ route('buyer.support.create') }}" class="{{ $primary }}">New request</a>
        </div>

        @if (session('success'))
            <div class="vb-reveal mt-3 rounded-lg border border-[#cfe8d8] bg-[#eaf5ee] px-4 py-3 text-[13px] text-[#2e6b46]" role="status" style="--i: 1">{{ session('success') }}</div>
        @endif

        <div class="vb-reveal mt-4 grid gap-3 sm:grid-cols-3" style="--i: 1">
            @foreach ([['Open', $open, '#B45309', '#FEF3C7'], ['In review', $review, '#2563EB', '#DBEAFE'], ['Resolved', $resolved, '#15803D', '#DCFCE7']] as [$label, $total, $ink, $tint])
                <div class="{{ $card }} flex items-center gap-3 p-4">
                    <span class="grid h-10 w-10 place-items-center rounded-full text-[15px] font-semibold" style="background: {{ $tint }}; color: {{ $ink }};">{{ $total }}</span>
                    <div>
                        <p class="text-[12px] font-medium uppercase tracking-wide text-[#9a8a9d]">{{ $label }}</p>
                        <p class="text-[13px] text-[#3d2a42]">{{ $total === 1 ? '1 request' : $total . ' requests' }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        <section class="vb-reveal mt-4" style="--i: 2" aria-labelledby="support-list-title">
            <h2 id="support-list-title" class="sr-only">Your support requests</h2>

            @if ($tickets->isEmpty())
                <div class="{{ $card }} px-5 py-12 text-center">
                    <span class="mx-auto grid h-12 w-12 place-items-center rounded-full bg-[#f3e8f5] text-[#805487]">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a8 8 0 0 1-11.6 7.1L4 21l1.9-5.4A8 8 0 1 1 21 12z" /><path d="M9 11h6M9 14h4" /></svg>
                    </span>
                    <p class="mt-3 text-[14px] font-semibold text-[#2b1730]">No support requests yet</p>
                    <p class="mx-auto mt-1 max-w-[380px] text-[13px] leading-5 text-[#7a6a7e]">Send a request when you need help from Vendo. You will see every reply here.</p>
                    <a href="{{ route('buyer.support.create') }}" class="mt-4 {{ $primary }}">Create a request</a>
                </div>
            @else
                <div class="space-y-2">
                    @foreach ($tickets as $ticket)
                        <a href="{{ route('buyer.support.show', $ticket) }}" class="{{ $card }} block p-4 transition-colors duration-200 hover:border-[#c9a9ce]">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <div>
                                    <p class="text-[14px] font-semibold text-[#2b1730]">{{ $ticket->subject }}</p>
                                    <p class="mt-0.5 text-[12px] text-[#7a6a7e]">
                                        #{{ $ticket->id }} · {{ $ticket->category_label }}
                                        @if ($ticket->order) · {{ $ticket->order->number }} @endif
                                        · {{ $ticket->messages_count }} {{ $ticket->messages_count === 1 ? 'message' : 'messages' }}
                                    </p>
                                </div>
                                <span class="rounded-full px-2 py-0.5 text-[12px] font-medium
                                    {{ $ticket->status === 'open' ? 'bg-[#FEF3C7] text-[#B45309]' : ($ticket->status === 'in_review' ? 'bg-[#DBEAFE] text-[#2563EB]' : 'bg-[#DCFCE7] text-[#15803D]') }}">
                                    {{ $ticket->status_label }}
                                </span>
                            </div>
                            <p class="mt-2 text-[12px] text-[#9a8a9d]">
                                Last activity {{ $ticket->last_reply_at?->diffForHumans() ?? $ticket->created_at?->diffForHumans() }}
                            </p>
                        </a>
                    @endforeach
                </div>
                <div class="mt-4">{{ $tickets->links() }}</div>
            @endif
        </section>
    </div>
</x-buyer.layout>
