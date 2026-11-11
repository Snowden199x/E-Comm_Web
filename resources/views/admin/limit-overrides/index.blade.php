@php
    $field = 'h-10 w-full rounded-lg border border-vd-line-strong bg-white px-3 text-sm text-vd-ink transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-vd-plum/40';
    $btn = 'inline-flex h-9 items-center rounded-lg bg-vd-plum px-3 text-[12px] font-semibold text-white transition-all duration-200 hover:bg-vd-plum-hover active:scale-95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-plum/40';
    $soft = 'inline-flex h-9 items-center rounded-lg border border-vd-line-strong bg-white px-3 text-[12px] font-medium text-vd-plum transition-all duration-200 hover:border-vd-plum hover:bg-vd-tint';
    $cell = 'border-t border-vd-line px-3 py-3 align-top text-[13px] text-vd-ink';
@endphp
<x-admin.layout title="Limits & Overrides">
    <div class="mx-auto w-full max-w-[1200px] p-4 sm:p-6 lg:p-7">
        <header class="mb-4">
            <h1 class="text-[24px] font-semibold text-vd-ink">Limits &amp; Overrides</h1>
            <p class="mt-1 max-w-[820px] text-[13px] text-vd-muted">
                The Buyer return window is {{ $defaultWindowDays }} days after delivery and a parcel gets
                {{ $maxAttempts }} delivery attempts. Use this page to extend a window or grant one more attempt.
                Every override is recorded in the order timeline with your name and the reason.
            </p>
        </header>

        <x-admin.flash class="mb-4" />

        <form method="GET" action="{{ route('admin.limit-overrides.index') }}" class="mb-4 flex flex-wrap items-end gap-2">
            <div class="min-w-[240px] flex-1">
                <label for="q" class="mb-1 block text-[12px] font-medium text-vd-ink">Find an order</label>
                <input id="q" name="q" value="{{ $q }}" placeholder="VN-000123 or tracking number" class="{{ $field }}">
            </div>
            <button type="submit" class="{{ $btn }}">Search</button>
            @if ($q !== '')
                <a href="{{ route('admin.limit-overrides.index') }}" class="{{ $soft }}">Clear</a>
            @endif
        </form>

        <div class="overflow-x-auto rounded-xl border border-vd-line bg-white">
            <table class="w-full min-w-[900px] text-left">
                <thead class="bg-vd-tint text-[12px] uppercase tracking-wide text-vd-muted">
                    <tr>
                        <th class="px-3 py-2">Order</th>
                        <th class="px-3 py-2">Buyer / Seller</th>
                        <th class="px-3 py-2">Status</th>
                        <th class="px-3 py-2">Delivery attempts</th>
                        <th class="px-3 py-2">Return window</th>
                        <th class="px-3 py-2">Override</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($orders as $order)
                    @php $window = $windows[$order->id] ?? null; @endphp
                    <tr>
                        <td class="{{ $cell }}">
                            <strong>#{{ $order->number }}</strong>
                            <div class="text-[12px] text-vd-muted">{{ $order->tracking_number }}</div>
                        </td>
                        <td class="{{ $cell }}">
                            {{ $order->buyer?->name ?? 'Buyer' }}
                            <div class="text-[12px] text-vd-muted">{{ $order->seller?->name ?? 'Seller' }}</div>
                        </td>
                        <td class="{{ $cell }}">{{ $order->status_label }}</td>
                        <td class="{{ $cell }}">
                            {{ (int) $order->delivery_attempts }} of {{ $maxAttempts + (int) $order->delivery_extra_attempts }}
                            @if ((int) $order->delivery_extra_attempts > 0)
                                <div class="text-[12px] text-vd-muted">+{{ (int) $order->delivery_extra_attempts }} granted</div>
                            @endif
                            @if ($order->delivery_failed_at)
                                <div class="text-[12px] text-vd-muted">Last failure {{ $order->delivery_failed_at->format('M j, Y g:i A') }}</div>
                            @endif
                        </td>
                        <td class="{{ $cell }}">
                            @if ($window)
                                {{ $window->format('M j, Y g:i A') }}
                                @if ($order->return_window_until)
                                    <div class="text-[12px] text-vd-plum">Extended by Admin</div>
                                @endif
                            @else
                                —
                            @endif
                        </td>
                        <td class="{{ $cell }}">
                            <div class="space-y-2">
                                @if (in_array($order->status, ['delivery_failed', 'assigned_to_rider'], true)
                                    && (int) $order->delivery_attempts >= 1
                                    && (int) $order->delivery_extra_attempts < 3)
                                    <details>
                                        <summary class="{{ $soft }} cursor-pointer">Grant one more attempt</summary>
                                        <form action="{{ route('admin.limit-overrides.attempt', $order) }}" method="POST" class="mt-2 space-y-2">
                                            @csrf
                                            <input type="hidden" name="_order" value="{{ $order->id }}">
                                            <label for="attempt-note-{{ $order->id }}" class="block text-[12px] font-medium text-vd-ink">Reason</label>
                                            <textarea id="attempt-note-{{ $order->id }}" name="note" rows="2" maxlength="450" required
                                                class="block w-full rounded-lg border border-vd-line-strong px-3 py-2 text-[13px] text-vd-ink">{{ old('_order') == $order->id ? old('note') : '' }}</textarea>
                                            <button type="submit" class="{{ $btn }}">Grant attempt</button>
                                        </form>
                                    </details>
                                @endif

                                @if ($window && in_array($order->status, \App\Models\Ecommerce\Order::SALES_STATUSES, true))
                                    <details>
                                        <summary class="{{ $soft }} cursor-pointer">Extend return window</summary>
                                        <form action="{{ route('admin.limit-overrides.window', $order) }}" method="POST" class="mt-2 space-y-2">
                                            @csrf
                                            <label for="window-until-{{ $order->id }}" class="block text-[12px] font-medium text-vd-ink">New deadline</label>
                                            <input id="window-until-{{ $order->id }}" type="date" name="until" required
                                                min="{{ now()->addDay()->toDateString() }}"
                                                value="{{ old('until') }}"
                                                class="block w-full rounded-lg border border-vd-line-strong px-3 py-2 text-[13px] text-vd-ink">
                                            <label for="window-note-{{ $order->id }}" class="block text-[12px] font-medium text-vd-ink">Reason</label>
                                            <textarea id="window-note-{{ $order->id }}" name="note" rows="2" maxlength="450" required
                                                class="block w-full rounded-lg border border-vd-line-strong px-3 py-2 text-[13px] text-vd-ink"></textarea>
                                            <button type="submit" class="{{ $btn }}">Extend window</button>
                                        </form>
                                    </details>
                                @endif

                                @if (! in_array($order->status, ['delivery_failed', 'assigned_to_rider'], true)
                                    && ! in_array($order->status, \App\Models\Ecommerce\Order::SALES_STATUSES, true))
                                    <span class="text-[12px] text-vd-muted">No limit to override.</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-3 py-8 text-center text-[13px] text-vd-muted">
                            @if ($q !== '')
                                No order matched “{{ $q }}”.
                            @else
                                No parcel has a failed attempt, a return request or an override yet.
                            @endif
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-admin.layout>
