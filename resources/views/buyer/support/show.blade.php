@php
    $card = 'rounded-lg border border-[#eee6ef] bg-white';
    $field = 'mt-1 block w-full rounded-md border border-[#e5dce7] bg-white px-3 py-2 text-[13px] text-[#2b1730] transition-colors duration-200 focus:border-[#805487] focus:outline-none';
@endphp

<x-buyer.layout title="{{ $ticket->subject }} — Support">
    <div class="mx-auto max-w-[860px] px-3 pb-10 pt-4 sm:px-4">
        <nav class="vb-reveal mb-3 text-[13px] text-[#7a6a7e]" aria-label="Breadcrumb">
            <a href="{{ route('buyer.support.index') }}" class="font-medium text-[#52245b] hover:underline">Support</a>
            <span class="px-1">/</span><span>#{{ $ticket->id }}</span>
        </nav>

        <header class="vb-reveal mb-3 flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-[20px] font-semibold text-[#2b1730]">{{ $ticket->subject }}</h1>
                <p class="mt-1 text-[12px] text-[#7a6a7e]">
                    {{ $ticket->category_label }}
                    @if ($ticket->order)
                        · <a href="{{ route('buyer.orders.show', $ticket->order_id) }}" class="font-medium text-[#52245b] hover:underline">{{ $ticket->order->number }}</a>
                    @endif
                    · opened {{ $ticket->created_at?->format('M j, Y g:i A') }}
                </p>
            </div>
            <span class="rounded-full px-3 py-1 text-[12px] font-semibold
                {{ $ticket->status === 'open' ? 'bg-[#FEF3C7] text-[#B45309]' : ($ticket->status === 'in_review' ? 'bg-[#DBEAFE] text-[#2563EB]' : 'bg-[#DCFCE7] text-[#15803D]') }}">
                {{ $ticket->status_label }}
            </span>
        </header>

        @if (session('success'))
            <div class="vb-reveal mb-3 rounded-lg border border-[#cfe8d8] bg-[#eaf5ee] px-4 py-3 text-[13px] text-[#2e6b46]" role="status">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="vb-reveal mb-3 rounded-lg border border-[#f3d3da] bg-[#fdf1f3] px-4 py-3 text-[13px] text-[#a32b43]" role="alert">
                @foreach ($errors->all() as $message)<p>{{ $message }}</p>@endforeach
            </div>
        @endif

        <section class="{{ $card }} vb-reveal mb-3 p-4" aria-label="Conversation">
            <div class="space-y-3">
                @foreach ($ticket->messages as $message)
                    <div class="flex {{ $message->is_staff ? 'justify-start' : 'justify-end' }}">
                        <div class="max-w-[85%] rounded-xl px-3.5 py-2.5 text-[13px] leading-5
                            {{ $message->is_staff ? 'bg-[#f3eaf5] text-[#2b1730]' : 'bg-[#402143] text-white' }}">
                            <p class="mb-1 text-[11px] font-semibold uppercase tracking-wide {{ $message->is_staff ? 'text-[#805487]' : 'text-white/75' }}">
                                {{ $message->sender_label }} · {{ $message->created_at?->format('M j, g:i A') }}
                            </p>
                            <div class="whitespace-pre-line">{{ $message->body }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        @if ($ticket->status !== 'resolved')
            <section class="{{ $card }} vb-reveal mb-3 p-4" aria-label="Add a reply">
                <h2 class="text-[14px] font-semibold text-[#2b1730]">Add to this request</h2>
                <form action="{{ route('buyer.support.reply', $ticket) }}" method="POST" class="mt-2">
                    @csrf
                    <label for="body" class="sr-only">Message</label>
                    <textarea id="body" name="body" rows="4" required maxlength="5000" class="{{ $field }}" placeholder="Add the missing detail or answer Vendo's question.">{{ old('body') }}</textarea>
                    @error('body')<p class="mt-1 text-[12px] text-[#a32b43]">{{ $message }}</p>@enderror
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        <button type="submit" class="inline-flex h-10 items-center justify-center rounded-md bg-[#402143] px-5 text-[13px] font-medium text-white transition-all duration-300 ease-vendo hover:bg-[#52245b] active:scale-[0.97]">Send reply</button>
                    </div>
                </form>
            </section>

            <form action="{{ route('buyer.support.close', $ticket) }}" method="POST" class="vb-reveal" onsubmit="return confirm('Mark this request as resolved?');">
                @csrf
                <button type="submit" class="text-[13px] font-medium text-[#7a6a7e] underline decoration-[#d9ccdb] underline-offset-4 transition-colors hover:text-[#52245b]">This is solved — mark as resolved</button>
            </form>
        @else
            <p class="{{ $card }} vb-reveal px-4 py-3 text-[13px] text-[#7a6a7e]">
                This request is resolved. Open a new request if you still need help.
            </p>
        @endif
    </div>
</x-buyer.layout>
