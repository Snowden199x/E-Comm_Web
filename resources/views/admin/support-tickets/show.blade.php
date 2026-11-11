@php
    $field = 'h-10 w-full rounded-lg border border-vd-line-strong bg-white px-3 text-sm text-vd-ink transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-vd-plum/40';
    $btn = 'inline-flex h-10 items-center rounded-lg bg-vd-plum px-4 text-[13px] font-semibold text-white transition-all duration-200 hover:bg-vd-plum-hover active:scale-95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-plum/40';
@endphp
<x-admin.layout title="Support ticket #{{ $ticket->id }}">
    <div class="mx-auto w-full max-w-[980px] p-4 sm:p-6 lg:p-7">
        <nav class="mb-3 text-[13px] text-vd-muted" aria-label="Breadcrumb">
            <a href="{{ route('admin.support-tickets.index') }}" class="font-medium text-vd-plum hover:underline">Support Tickets</a>
            <span class="px-1">/</span>
            <span>#{{ $ticket->id }}</span>
        </nav>

        <header class="mb-4 flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-[22px] font-semibold text-vd-ink">{{ $ticket->subject }}</h1>
                <p class="mt-1 text-[13px] text-vd-muted">
                    {{ $ticket->category_label }} ·
                    {{ $ticket->user?->name ?? 'Buyer' }} ({{ $ticket->user?->email }})
                    @if ($ticket->order)
                        · order {{ $ticket->order->number }}
                    @endif
                    · opened {{ $ticket->created_at?->format('M j, Y g:i A') }}
                </p>
            </div>
            <span class="inline-flex rounded-full px-3 py-1 text-[12px] font-semibold
                {{ $ticket->status === 'open' ? 'bg-[#fef3c7] text-[#92400e]' : ($ticket->status === 'in_review' ? 'bg-[#dbeafe] text-[#1d4ed8]' : 'bg-[#dcfce7] text-[#15803d]') }}">
                {{ $ticket->status_label }}
            </span>
        </header>

        <x-admin.flash class="mb-4" />

        <section class="mb-4 rounded-xl border border-vd-line bg-white" aria-label="Conversation">
            <div class="max-h-[520px] space-y-3 overflow-y-auto p-4">
                @foreach ($ticket->messages as $message)
                    <div class="flex {{ $message->is_staff ? 'justify-end' : 'justify-start' }}">
                        <div class="max-w-[80%] rounded-xl px-3.5 py-2.5 text-[13px] leading-5
                            {{ $message->is_staff ? 'bg-vd-plum text-white' : 'bg-vd-tint text-vd-ink' }}">
                            <p class="mb-1 text-[11px] font-semibold uppercase tracking-wide {{ $message->is_staff ? 'text-white/80' : 'text-vd-muted' }}">
                                {{ $message->sender_label }} · {{ $message->created_at?->format('M j, g:i A') }}
                            </p>
                            <div class="whitespace-pre-line">{{ $message->body }}</div>
                            @if ($message->attachments->isNotEmpty())
                                <ul class="mt-1 space-y-1 text-[12px]">
                                    @foreach ($message->attachments as $attachment)
                                        <li>
                                            <a href="{{ route('admin.support-tickets.attachments.show', $attachment) }}"
                                                class="underline underline-offset-2 {{ $message->is_staff ? 'text-white' : 'text-vd-plum' }}">
                                                {{ $attachment->original_name }}
                                                <span class="opacity-75">({{ $attachment->sizeLabel() }})</span>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        @if ($ticket->status !== 'resolved')
            <section class="mb-4 rounded-xl border border-vd-line bg-white p-4" aria-label="Reply to the buyer">
                <h2 class="mb-2 text-[14px] font-semibold text-vd-ink">Reply to the buyer</h2>
                <form action="{{ route('admin.support-tickets.reply', $ticket) }}" method="POST" enctype="multipart/form-data" class="space-y-2">
                    @csrf
                    <label for="body" class="sr-only">Message</label>
                    <textarea id="body" name="body" rows="4" required maxlength="5000"
                        class="block w-full rounded-lg border border-vd-line-strong px-3 py-2 text-[13px] text-vd-ink"
                        placeholder="Explain the next step or ask for the details you need.">{{ old('body') }}</textarea>
                    @error('body')<p class="text-[12px] text-[#a32b43]">{{ $message }}</p>@enderror
                    <div>
                        <label for="attachments" class="mb-1 block text-[12px] font-medium text-vd-ink">Attach a photo or PDF <span class="font-normal text-vd-muted">(optional, up to 2 files, 5 MB each)</span></label>
                        <input id="attachments" type="file" name="attachments[]" multiple accept="image/jpeg,image/png,image/webp,application/pdf"
                            class="block w-full rounded-lg border border-vd-line-strong px-3 py-2 text-[12px] text-vd-ink">
                        @foreach ($errors->get('attachments.*') as $messages)
                            @foreach ($messages as $errorText)<p class="text-[12px] text-[#a32b43]">{{ $errorText }}</p>@endforeach
                        @endforeach
                    </div>
                    <button type="submit" class="{{ $btn }}">Send reply</button>
                </form>
            </section>
        @else
            <p class="mb-4 rounded-xl border border-vd-line bg-vd-tint px-4 py-3 text-[13px] text-vd-muted">
                This ticket is resolved. Change its status below to reply again.
            </p>
        @endif

        <section class="rounded-xl border border-vd-line bg-white p-4" aria-label="Ticket status">
            <h2 class="mb-2 text-[14px] font-semibold text-vd-ink">Ticket status</h2>
            <form action="{{ route('admin.support-tickets.status', $ticket) }}" method="POST" class="flex flex-wrap items-end gap-2">
                @csrf
                @method('PATCH')
                <div class="min-w-[200px]">
                    <label for="status" class="mb-1 block text-[12px] font-medium text-vd-ink">Status</label>
                    <select id="status" name="status" class="{{ $field }}">
                        @foreach (\App\Models\Support\SupportTicket::STATUSES as $key => $text)
                            <option value="{{ $key }}" @selected($ticket->status === $key)>{{ $text }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="{{ $btn }}">Save status</button>
            </form>
        </section>
    </div>
</x-admin.layout>
