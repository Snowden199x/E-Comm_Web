@use('App\Models\Ecommerce\Order')
<x-admin.layout title="Limits and overrides">
    @php

        $focus = 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-plum/40';
        $btn = "inline-flex h-10 items-center justify-center gap-2 rounded-lg px-4 text-sm font-semibold transition-colors duration-200 {$focus}";
        $btnPrimary = $btn.' bg-vd-plum text-white hover:bg-vd-plum-hover';
        $btnSecondary = $btn.' border border-vd-line-strong bg-white text-vd-ink hover:bg-vd-tint';
        $rowBtn = "inline-flex h-8 items-center rounded-lg border border-vd-line-strong bg-white px-3 text-[13px] font-medium text-vd-plum transition-colors duration-200 hover:bg-vd-plum hover:text-white {$focus}";
        $fieldCls = 'block w-full rounded-lg border bg-white px-3 py-2 text-[13px] text-vd-ink placeholder:text-vd-muted focus:border-vd-plum focus:outline-none focus:ring-2 focus:ring-vd-plum/20';
        $quick = 'rounded-full border border-vd-line-strong bg-vd-tint px-2.5 py-1 text-xs font-medium text-vd-plum transition-colors duration-200 hover:bg-vd-lavender '.$focus;

        // Order status pill: deep red = failed, plum = on its way, green = delivered, grey = anything else.
        $tone = fn (string $status) => $status === 'delivery_failed' ? 'danger' : ($status === 'assigned_to_rider' ? 'plum' : (in_array($status, Order::SALES_STATUSES, true) ? 'success' : 'neutral'));

        $rows = $orders->map(function (Order $order) use ($windows, $maxAttempts) {
            $window = $windows[$order->id] ?? null;
            $canAttempt = in_array($order->status, ['delivery_failed', 'assigned_to_rider'], true)
                && (int) $order->delivery_attempts >= 1 && (int) $order->delivery_extra_attempts < 3;
            $canWindow = $window && in_array($order->status, Order::SALES_STATUSES, true);
            $base = ($window && $window->isFuture() ? $window : now())->copy();
            return [
                'order' => $order, 'window' => $window, 'canAttempt' => $canAttempt, 'canWindow' => $canWindow,
                'payload' => [
                    'id' => $order->id, 'number' => '#'.$order->number, 'buyer' => $order->buyer?->name ?? 'Buyer',
                    'attemptUrl' => route('admin.limit-overrides.attempt', $order), 'windowUrl' => route('admin.limit-overrides.window', $order),
                    'used' => (int) $order->delivery_attempts, 'allowed' => $maxAttempts + (int) $order->delivery_extra_attempts,
                    'deadline' => $window?->format('M j, Y'),
                    'base' => $base->toDateString(),
                    'min' => max(now()->addDay()->startOfDay(), $base->copy()->addDay()->startOfDay())->toDateString(),
                ],
            ];
        });

        // After a refused or invalid submit the popup reopens with what was typed.
        $reopenRow = old('_order') ? $rows->first(fn ($row) => (string) $row['order']->id === (string) old('_order')) : null;
        $reopen = $reopenRow ? ['kind' => old('_kind') === 'window' ? 'window' : 'attempt', 'payload' => $reopenRow['payload'], 'note' => (string) old('note'), 'until' => (string) old('until')] : null;
    @endphp

    <div class="mx-auto w-full max-w-[1280px] p-4 sm:p-5 lg:p-6"
        x-data="{
            attempt: null, win: null, note: '', until: '',
            noteError: @js((string) $errors->first('note')), untilError: @js((string) $errors->first('until')),
            reopen: @js($reopen),
            init() {
                if (this.reopen) {
                    this[this.reopen.kind === 'window' ? 'win' : 'attempt'] = this.reopen.payload;
                    this.note = this.reopen.note; this.until = this.reopen.until;
                }
            },
            openAttempt(p) { this.attempt = p; this.note = ''; this.noteError = ''; },
            openWindow(p) { this.win = p; this.note = ''; this.until = ''; this.noteError = ''; this.untilError = ''; },
            close() { this.attempt = null; this.win = null; },
            plusDays(n) {
                const d = new Date(this.win.base + 'T00:00:00'); d.setDate(d.getDate() + n);
                const pad = (v) => String(v).padStart(2, '0');
                this.until = d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); this.untilError = '';
            },
            checkNote() {
                const n = this.note.trim().length;
                this.noteError = n === 0 ? 'Reason is empty. Say why you are making this exception. It is kept in the order timeline.' : (n < 5 ? 'Reason is too short. Use at least 5 characters, for example what the buyer or rider reported.' : '');
                return !this.noteError;
            },
            submitAttempt(e) { if (!this.checkNote()) { e.preventDefault(); this.$refs.attemptNote.focus(); } },
            submitWindow(e) {
                this.untilError = !this.until ? 'Deadline is empty. Pick the new return deadline.' : (this.until < this.win.min ? 'Deadline is too early. Pick a date after ' + this.win.deadline + '.' : '');
                const noteOk = this.checkNote();
                if (this.untilError || !noteOk) { e.preventDefault(); (this.untilError ? this.$refs.untilField : this.$refs.windowNote).focus(); }
            },
        }"
        @ajax:after.window="document.getElementById('main-content')?.scrollTo({ top: 0 })">

        <div class="mb-4">
            <h1 class="font-display text-xl font-semibold leading-tight text-vd-ink sm:text-2xl">Limits and overrides</h1>
            <p class="mt-1 text-sm text-vd-muted">Make an exception for one order. Every override is recorded in the order timeline with your name and reason.</p>
        </div>

        <x-admin.flash class="mb-4" :except="['q', 'note', 'until']" />

        <div class="mb-5 grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4">
            <div class="flex items-center gap-3 rounded-[18px] border border-vd-orchid bg-[#E6DEE5] px-4 py-3">
                <span class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-vd-orchid text-white"><x-admin.icon name="calendar" class="h-[18px] w-[18px]" /></span>
                <span><span class="block font-display text-[13px] font-medium text-vd-ink">Return window</span><span class="block text-sm text-vd-muted">{{ $defaultWindowDays }} days after delivery</span></span>
            </div>
            <div class="flex items-center gap-3 rounded-[18px] border border-vd-orchid bg-[#E6DEE5] px-4 py-3">
                <span class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-vd-orchid text-white"><x-admin.icon name="package" class="h-[18px] w-[18px]" /></span>
                <span><span class="block font-display text-[13px] font-medium text-vd-ink">Delivery attempts</span><span class="block text-sm text-vd-muted">{{ $maxAttempts }} per parcel, up to 3 extra</span></span>
            </div>
        </div>

        <form method="GET" action="{{ route('admin.limit-overrides.index') }}" x-target.push="main-content" data-skeleton="table" role="search" class="mb-5 flex flex-wrap items-center gap-3">
            <div class="group relative w-full sm:max-w-[400px]">
                <x-admin.icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-vd-plum transition-transform duration-200 group-focus-within:scale-110" />
                <input type="search" name="q" value="{{ $q }}" maxlength="100" autocomplete="off" aria-label="Find an order"
                    placeholder="Find an order by number or tracking number, then press Enter"
                    class="h-10 w-full rounded-full border border-vd-orchid bg-vd-paper pl-10 pr-4 text-[13px] text-vd-ink placeholder:text-vd-muted transition-all duration-200 ease-out hover:border-vd-plum hover:bg-white focus:border-vd-plum focus:bg-white focus:outline-none focus:ring-2 focus:ring-vd-plum/20">
            </div>
            @if ($q !== '')
                <a href="{{ route('admin.limit-overrides.index') }}" x-target.push="main-content" data-skeleton="table"
                    class="inline-flex h-10 items-center justify-center rounded-full px-3 text-[13px] font-medium text-vd-plum transition-colors duration-200 hover:bg-vd-lavender {{ $focus }}">Clear filters</a>
            @endif
        </form>

        <div class="overflow-hidden rounded-xl border border-vd-line bg-white">
            @if ($rows->isEmpty())
                <x-admin.empty-state icon="shield-check" title="No orders need an override" text="Parcels with a failed attempt, a return request or an earlier override appear here. Search for any order by its number."
                    :filtered="$q !== ''" filtered-title="No order matches this search" filtered-text="Check the order number (for example VN-000123) or the tracking number.">
                    @if ($q !== '')
                        <a href="{{ route('admin.limit-overrides.index') }}" x-target.push="main-content" data-skeleton="table"
                            class="inline-flex h-8 items-center justify-center rounded-lg border border-vd-line-strong bg-white px-3 text-[13px] font-medium text-vd-plum transition-colors duration-200 hover:bg-vd-tint {{ $focus }}">Clear filters</a>
                    @endif
                </x-admin.empty-state>
            @else
                <div class="thin-scroll overflow-x-auto">
                    <table class="w-full min-w-[920px] text-left text-[13px]">
                        <caption class="sr-only">Orders that can have a limit overridden.</caption>
                        <thead>
                            <tr class="border-b border-vd-line bg-vd-tint text-xs text-vd-muted">
                                <th scope="col" class="px-4 py-2.5 font-medium">Order</th>
                                <th scope="col" class="px-4 py-2.5 font-medium">Buyer and seller</th>
                                <th scope="col" class="px-4 py-2.5 font-medium">Status</th>
                                <th scope="col" class="px-4 py-2.5 font-medium">Delivery attempts</th>
                                <th scope="col" class="px-4 py-2.5 font-medium">Return window</th>
                                <th scope="col" class="px-4 py-2.5 text-right font-medium">Override</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-vd-line">
                            @foreach ($rows as $row)
                                @php
                                    $order = $row['order']; $window = $row['window']; $payload = $row['payload'];
                                    $closingSoon = $window && $window->isFuture() && $window->lt(now()->addDays(2));
                                @endphp
                                <tr class="transition-colors duration-200 hover:bg-vd-tint">
                                    <td class="px-4 py-3">
                                        <p class="font-medium text-vd-ink">#{{ $order->number }}</p>
                                        <p class="text-xs text-vd-muted">{{ $order->tracking_number ?: 'No tracking number' }}</p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="max-w-[180px] truncate font-medium text-vd-ink">{{ $order->buyer?->name ?? 'Buyer' }}</p>
                                        <p class="max-w-[180px] truncate text-xs text-vd-muted">{{ $order->seller?->name ?? 'Seller' }}</p>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3"><x-admin.status-pill :tone="$tone($order->status)">{{ $order->status_label }}</x-admin.status-pill></td>
                                    <td class="px-4 py-3">
                                        <p class="font-medium text-vd-ink">{{ (int) $order->delivery_attempts }} of {{ $payload['allowed'] }}</p>
                                        <p class="text-xs text-vd-muted">
                                            @if ((int) $order->delivery_extra_attempts > 0)+{{ (int) $order->delivery_extra_attempts }} granted @endif
                                            @if ($order->delivery_failed_at){{ (int) $order->delivery_extra_attempts > 0 ? '· ' : '' }}Last failure {{ $order->delivery_failed_at->format('M j, g:i A') }}@endif
                                        </p>
                                    </td>
                                    <td class="px-4 py-3">
                                        @if ($window)
                                            <p class="font-medium text-vd-ink">{{ $window->format('M j, Y') }}</p>
                                            <p @class(['text-xs', 'font-medium text-vd-warning-ink' => $closingSoon, 'text-vd-muted' => ! $closingSoon])>
                                                {{ $window->isPast() ? 'Closed '.$window->diffForHumans() : 'Closes '.$window->diffForHumans() }}@if ($order->return_window_until) · Extended by Admin @endif
                                            </p>
                                        @else
                                            <span class="text-vd-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex flex-wrap justify-end gap-2">
                                            @if ($row['canAttempt'])
                                                <button type="button" @click="openAttempt(@js($payload))" class="{{ $rowBtn }}" aria-label="Grant one more attempt for order {{ $order->number }}">Grant attempt</button>
                                            @endif
                                            @if ($row['canWindow'])
                                                <button type="button" @click="openWindow(@js($payload))" class="{{ $rowBtn }}" aria-label="Extend the return window for order {{ $order->number }}">Extend window</button>
                                            @endif
                                            @if (! $row['canAttempt'] && ! $row['canWindow'])
                                                <span class="text-xs text-vd-muted">No limit to override</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="border-t border-vd-line px-4 py-3 text-xs text-vd-muted">{{ $q !== '' ? 'Showing up to 20 matches.' : 'Showing the 30 most recent orders with a failed attempt, a return request or an override. Search to find any other order.' }}</p>
            @endif
        </div>

        {{-- Grant one more delivery attempt --}}
        <x-admin.dialog model="attempt !== null" close="close()" title="Grant one more delivery attempt" size="md">
            <template x-if="attempt">
                <form :action="attempt.attemptUrl" method="POST" class="space-y-4" @submit="submitAttempt($event)" novalidate>
                    @csrf
                    <input type="hidden" name="_order" :value="attempt.id"><input type="hidden" name="_kind" value="attempt">
                    <p class="text-[13px] text-vd-muted"><span class="font-medium text-vd-ink" x-text="attempt.number"></span> · <span x-text="attempt.buyer"></span>. It has used <span x-text="attempt.used"></span> of <span x-text="attempt.allowed"></span> attempts. This makes <span class="font-medium text-vd-ink" x-text="attempt.allowed + 1"></span> allowed. The buyer, courier and hub are notified.</p>
                    <div>
                        <label for="attempt-note" class="mb-1.5 block text-xs font-medium text-vd-ink">Reason</label>
                        <div class="mb-2 flex flex-wrap gap-1.5" role="group" aria-label="Quick reasons">
                            <button type="button" class="{{ $quick }}" @click="note = 'The buyer was unreachable and asked for another attempt.'; noteError = ''">Buyer asked for a retry</button>
                            <button type="button" class="{{ $quick }}" @click="note = 'The rider could not find the address. The buyer confirmed it.'; noteError = ''">Address confirmed</button>
                        </div>
                        <textarea id="attempt-note" name="note" rows="3" maxlength="450" x-ref="attemptNote" x-model="note" @input="noteError = ''"
                            :class="noteError ? 'border-vd-danger-ink' : 'border-vd-line-strong'" class="{{ $fieldCls }}" placeholder="What happened and why another attempt is fair."></textarea>
                        <div class="mt-1 flex justify-between gap-3 text-xs"><p class="text-vd-danger-ink" x-text="noteError" x-show="noteError" x-cloak></p><p class="ml-auto tabular-nums text-vd-muted"><span x-text="note.length">0</span> / 450</p></div>
                    </div>
                    <div class="flex items-center justify-end gap-3">
                        <button type="button" @click="close()" class="{{ $btnSecondary }}">Cancel</button>
                        <button type="submit" class="{{ $btnPrimary }}">Grant attempt</button>
                    </div>
                </form>
            </template>
        </x-admin.dialog>

        {{-- Extend the return window --}}
        <x-admin.dialog model="win !== null" close="close()" title="Extend the return window" size="md">
            <template x-if="win">
                <form :action="win.windowUrl" method="POST" class="space-y-4" @submit="submitWindow($event)" novalidate>
                    @csrf
                    <input type="hidden" name="_order" :value="win.id"><input type="hidden" name="_kind" value="window">
                    <p class="text-[13px] text-vd-muted"><span class="font-medium text-vd-ink" x-text="win.number"></span> · <span x-text="win.buyer"></span>. The window now closes <span class="font-medium text-vd-ink" x-text="win.deadline"></span>. The buyer is notified of the new date.</p>
                    <div>
                        <label for="window-until" class="mb-1.5 block text-xs font-medium text-vd-ink">New deadline</label>
                        <div class="mb-2 flex flex-wrap gap-1.5" role="group" aria-label="Quick deadlines">
                            <button type="button" class="{{ $quick }}" @click="plusDays(3)">3 days later</button>
                            <button type="button" class="{{ $quick }}" @click="plusDays(7)">7 days later</button>
                            <button type="button" class="{{ $quick }}" @click="plusDays(14)">14 days later</button>
                        </div>
                        <input id="window-until" type="date" name="until" x-ref="untilField" x-model="until" :min="win.min" @input="untilError = ''"
                            :class="untilError ? 'border-vd-danger-ink' : 'border-vd-line-strong'" class="{{ $fieldCls }} h-10">
                        <p class="mt-1 text-xs text-vd-danger-ink" x-text="untilError" x-show="untilError" x-cloak></p>
                    </div>
                    <div>
                        <label for="window-note" class="mb-1.5 block text-xs font-medium text-vd-ink">Reason</label>
                        <div class="mb-2 flex flex-wrap gap-1.5" role="group" aria-label="Quick reasons">
                            <button type="button" class="{{ $quick }}" @click="note = 'The buyer reported a problem with the item before the window closed.'; noteError = ''">Reported in time</button>
                            <button type="button" class="{{ $quick }}" @click="note = 'The delivery was late, so the buyer had less time to inspect the item.'; noteError = ''">Late delivery</button>
                        </div>
                        <textarea id="window-note" name="note" rows="3" maxlength="450" x-ref="windowNote" x-model="note" @input="noteError = ''"
                            :class="noteError ? 'border-vd-danger-ink' : 'border-vd-line-strong'" class="{{ $fieldCls }}" placeholder="What happened and why more time is fair."></textarea>
                        <div class="mt-1 flex justify-between gap-3 text-xs"><p class="text-vd-danger-ink" x-text="noteError" x-show="noteError" x-cloak></p><p class="ml-auto tabular-nums text-vd-muted"><span x-text="note.length">0</span> / 450</p></div>
                    </div>
                    <div class="flex items-center justify-end gap-3">
                        <button type="button" @click="close()" class="{{ $btnSecondary }}">Cancel</button>
                        <button type="submit" class="{{ $btnPrimary }}">Extend window</button>
                    </div>
                </form>
            </template>
        </x-admin.dialog>
    </div>
</x-admin.layout>
