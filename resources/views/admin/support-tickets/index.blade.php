@php
    $field = 'h-10 w-full rounded-lg border border-vd-line-strong bg-white px-3 text-sm text-vd-ink transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-vd-plum/40';
    $btn = 'inline-flex h-10 items-center rounded-lg bg-vd-plum px-4 text-[13px] font-semibold text-white transition-all duration-200 hover:bg-vd-plum-hover active:scale-95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-plum/40';
    $open = (int) ($counts['open'] ?? 0);
    $review = (int) ($counts['in_review'] ?? 0);
    $resolved = (int) ($counts['resolved'] ?? 0);
@endphp
<x-admin.layout title="Support Tickets">
    <div class="mx-auto w-full max-w-[1200px] p-4 sm:p-6 lg:p-7">
        <header class="mb-4 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-[24px] font-semibold text-vd-ink">Support Tickets</h1>
                <p class="mt-1 max-w-[720px] text-[13px] text-vd-muted">
                    Questions and problems Buyers send to Vendo directly, separate from a complaint about a Seller.
                    Replying moves an open ticket to In review; resolving it notifies the Buyer.
                </p>
            </div>
            <a href="{{ route('admin.support-tickets.index', ['status' => 'open']) }}" class="rounded-lg border border-vd-line-strong px-4 py-2 text-[13px] font-medium text-vd-plum transition-colors hover:border-vd-plum hover:bg-vd-tint">
                {{ $open }} open
            </a>
        </header>

        <x-admin.flash class="mb-4" />

        <div class="mb-4 grid grid-cols-3 gap-3">
            @foreach ([['Open', $open, 'admin.support-tickets.index', ''], ['In review', $review, 'admin.support-tickets.index', 'in_review'], ['Resolved', $resolved, 'admin.support-tickets.index', 'resolved']] as [$labelText, $total, $routeName, $statusKey])
                <a href="{{ route($routeName, array_filter(['status' => $statusKey])) }}"
                   class="rounded-xl border {{ $status === $statusKey ? 'border-vd-plum bg-vd-tint' : 'border-vd-line bg-white' }} p-4 transition-colors hover:border-vd-plum">
                    <p class="text-[12px] font-medium uppercase tracking-wide text-vd-muted">{{ $labelText }}</p>
                    <p class="mt-1 text-[22px] font-semibold text-vd-ink">{{ number_format($total) }}</p>
                </a>
            @endforeach
        </div>

        <form method="GET" action="{{ route('admin.support-tickets.index') }}" class="mb-4 grid gap-3 rounded-xl border border-vd-line bg-white p-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="lg:col-span-2">
                <label for="q" class="mb-1 block text-[12px] font-medium text-vd-ink">Search</label>
                <input id="q" name="q" value="{{ $q }}" placeholder="Ticket id, subject, buyer or email" class="{{ $field }}">
            </div>
            <div>
                <label for="category" class="mb-1 block text-[12px] font-medium text-vd-ink">Topic</label>
                <select id="category" name="category" class="{{ $field }}">
                    <option value="">All topics</option>
                    @foreach (\App\Models\Support\SupportTicket::CATEGORIES as $key => $text)
                        <option value="{{ $key }}" @selected($category === $key)>{{ $text }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="{{ $btn }}">Filter</button>
                @if ($q !== '' || $category !== '' || $status !== '')
                    <a href="{{ route('admin.support-tickets.index') }}" class="inline-flex h-10 items-center rounded-lg border border-vd-line-strong px-4 text-[13px] font-medium text-vd-plum hover:border-vd-plum hover:bg-vd-tint">Clear</a>
                @endif
            </div>
            @if (isset(\App\Models\Support\SupportTicket::STATUSES[$status]))
                <input type="hidden" name="status" value="{{ $status }}">
            @endif
        </form>

        <div class="overflow-x-auto rounded-xl border border-vd-line bg-white">
            <table class="w-full min-w-[900px] text-left">
                <thead class="bg-vd-tint text-[12px] uppercase tracking-wide text-vd-muted">
                    <tr>
                        <th class="px-3 py-2">Ticket</th>
                        <th class="px-3 py-2">Buyer</th>
                        <th class="px-3 py-2">Topic</th>
                        <th class="px-3 py-2">Status</th>
                        <th class="px-3 py-2">Last reply</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($tickets as $ticket)
                    <tr class="hover:bg-vd-tint/50">
                        <td class="border-t border-vd-line px-3 py-3 align-top text-[13px]">
                            <a href="{{ route('admin.support-tickets.show', $ticket) }}" class="font-semibold text-vd-plum hover:underline">
                                #{{ $ticket->id }} · {{ $ticket->subject }}
                            </a>
                            <div class="text-[12px] text-vd-muted">{{ $ticket->messages_count }} {{ $ticket->messages_count === 1 ? 'message' : 'messages' }}</div>
                        </td>
                        <td class="border-t border-vd-line px-3 py-3 align-top text-[13px]">
                            {{ $ticket->user?->name ?? 'Buyer' }}
                            <div class="text-[12px] text-vd-muted">{{ $ticket->user?->email }}</div>
                        </td>
                        <td class="border-t border-vd-line px-3 py-3 align-top text-[13px]">{{ $ticket->category_label }}</td>
                        <td class="border-t border-vd-line px-3 py-3 align-top text-[13px]">
                            <span class="inline-flex rounded-full px-2 py-0.5 text-[12px] font-medium
                                {{ $ticket->status === 'open' ? 'bg-[#fef3c7] text-[#92400e]' : ($ticket->status === 'in_review' ? 'bg-[#dbeafe] text-[#1d4ed8]' : 'bg-[#dcfce7] text-[#15803d]') }}">
                                {{ $ticket->status_label }}
                            </span>
                        </td>
                        <td class="border-t border-vd-line px-3 py-3 align-top text-[13px]">
                            {{ $ticket->last_reply_at?->diffForHumans() ?? $ticket->created_at?->diffForHumans() }}
                            <div class="text-[12px] text-vd-muted">{{ $ticket->created_at?->format('M j, Y') }}</div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-3 py-10 text-center text-[13px] text-vd-muted">
                            {{ $q !== '' || $category !== '' || $status !== '' ? 'No ticket matched these filters.' : 'No buyer has opened a support ticket yet.' }}
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if ($tickets->hasPages())
            <div class="mt-4">{{ $tickets->links() }}</div>
        @endif
    </div>
</x-admin.layout>
