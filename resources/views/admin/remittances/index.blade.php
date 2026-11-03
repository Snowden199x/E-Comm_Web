<x-admin.layout title="Cash Remittances">
    {{-- Shared card and row motion (rg- prefix). --}}
    @vite('resources/css/admin/registrations.css')

    @php
        // After a failed save the dialog reopens with what was typed and the error under the field.
        $reopenId = old('_batch') !== null && $batches->contains('id', (int) old('_batch')) ? (string) (int) old('_batch') : '';

        $cards = [
            ['key' => 'pending', 'label' => 'Awaiting confirmation', 'icon' => 'clock'],
            ['key' => 'discrepancy', 'label' => 'Open discrepancies', 'icon' => 'triangle-alert'],
            ['key' => 'confirmed', 'label' => 'Confirmed', 'icon' => 'check-circle'],
        ];

        $field = 'h-10 w-full rounded-lg border bg-white text-sm text-vd-ink transition-colors duration-200 focus:outline-none focus:ring-2';
        $ghost = 'inline-flex h-10 items-center rounded-lg border border-vd-line-strong bg-white px-4 text-[13px] font-medium text-vd-plum transition-all duration-200
                  hover:border-vd-plum hover:bg-vd-tint active:scale-95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-plum/40';
        $solid = 'inline-flex h-10 items-center rounded-lg bg-vd-plum px-5 text-[13px] font-semibold text-white transition-all duration-200
                  hover:bg-vd-plum-hover active:scale-95 disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:bg-vd-plum disabled:active:scale-100
                  focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-plum/40 focus-visible:ring-offset-2';
    @endphp

    {{--
        The status cards filter the table. Search, status and page changes fetch this page again and swap #rm-table.
        Confirming and resolving post normally from the dialog; a failed save reopens the dialog with its errors.
    --}}
    <div class="rg-page mx-auto w-full max-w-[1440px] p-4 sm:p-6 lg:p-7" x-data="{
        // Filters
        q: @js($search),
        status: @js($status),
        page: {{ $batches->currentPage() }},

        // Loading: the skeleton shows only when a request takes longer than 150 ms, so fast loads do not flicker.
        loading: false,
        slow: false,
        failed: false,
        failedMsg: '',
        ctrl: null,
        timer: null,
        slowTimer: null,
        pageUrl: @js(route('admin.remittances.index')),
        receiveUrl: @js(route('admin.remittances.receive', ['remittance' => '__ID__'])),
        resolveUrl: @js(route('admin.remittances.resolve', ['remittance' => '__ID__'])),

        // Dialog (one for both actions: confirm a pending batch, resolve a discrepancy)
        open: false,
        mode: 'confirm',
        trigger: null,
        batch: { id: '', reference: '', company: '', parcels: 0, expected: 0, received: null, note: '' },
        received: @js((string) old('received_amount', '')),
        note: @js((string) old('note', '')),
        resolution: @js((string) old('resolution_note', '')),
        srv: { received: @js($errors->first('received_amount')), note: @js($errors->first('note')), resolution: @js($errors->first('resolution_note')) },
        touched: { received: false, note: false, resolution: false },

        // ----- Filters -----------------------------------------------------------
        get filtered() { return this.q.trim() !== '' || this.status !== 'all'; },
        params() {
            const p = new URLSearchParams();
            if (this.q.trim()) p.set('search', this.q.trim());
            if (this.status !== 'all') p.set('status', this.status);
            if (this.page > 1) p.set('page', this.page);
            return p;
        },
        pick(key) { this.status = this.status === key ? 'all' : key; this.apply(); },
        onSearch() { clearTimeout(this.timer); this.page = 1; this.timer = setTimeout(() => this.load(), 300); },
        apply() { clearTimeout(this.timer); this.page = 1; this.load(); },
        clearAll() { this.q = ''; this.status = 'all'; this.apply(); },
        onTableClick(event) {
            const btn = event.target.closest('[data-page]');
            if (!btn || btn.disabled) return;
            this.page = Number(btn.dataset.page);
            this.load(true);
        },

        // Short, specific messages: what happened and what to do.
        errorText(status) {
            if (!navigator.onLine) return 'You are offline. Reconnect, then select Retry.';
            if (status === 401 || status === 419) return 'Your session ended. Reload the page and sign in again.';
            if (status === 403) return 'This account cannot view remittances. Sign in with an admin account.';
            if (status >= 500) return 'Vendo could not load the batches. Select Retry. If it keeps failing, reload the page.';
            return 'Check your connection, then select Retry.';
        },
        async load(scrollToTable = false) {
            if (this.ctrl) this.ctrl.abort();
            const ctrl = new AbortController();
            this.ctrl = ctrl;
            this.loading = true;
            this.failed = false;
            clearTimeout(this.slowTimer);
            this.slowTimer = setTimeout(() => { this.slow = true; }, 150);

            const query = this.params().toString();
            const url = this.pageUrl + (query ? '?' + query : '');
            try {
                const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }, signal: ctrl.signal });
                if (res.redirected && res.url !== new URL(url, location.origin).href) { window.location.href = res.url; return; }
                if (!res.ok) { const err = new Error('HTTP ' + res.status); err.status = res.status; throw err; }

                const doc = new DOMParser().parseFromString(await res.text(), 'text/html');
                const next = doc.getElementById('rm-table');
                if (!next) throw new Error('Missing region');
                if (ctrl.signal.aborted) return;

                const region = this.$refs.table;
                region.innerHTML = next.innerHTML;
                region.classList.remove('rg-fresh');
                void region.offsetWidth; // restart the row animation
                region.classList.add('rg-fresh');

                history.replaceState(null, '', url);
                if (scrollToTable) region.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            } catch (e) {
                if (e.name === 'AbortError') return;
                this.failedMsg = this.errorText(e.status);
                this.failed = true;
            } finally {
                if (this.ctrl === ctrl) {
                    clearTimeout(this.slowTimer);
                    this.loading = false;
                    this.slow = false;
                    this.ctrl = null;
                }
            }
        },

        // ----- Dialog ----------------------------------------------------------------
        peso(n) { return '₱' + Number(n || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
        amountCheck(v) {
            const s = String(v).trim();
            if (s === '') return 'Enter the amount received, like 1250.50.';
            const n = Number(s);
            if (Number.isNaN(n)) return 'Use numbers only, like 1250.50.';
            if (n < 0) return 'Enter 0 or more.';
            if (!/^\d+(\.\d{1,2})?$/.test(s)) return 'Use at most 2 decimals, like 1250.50.';
            return '';
        },
        // Difference in cents between what was received and what was expected (0 while the amount is invalid).
        get diffCents() {
            if (this.amountCheck(this.received)) return 0;
            return Math.round(Number(this.received) * 100) - Math.round(this.batch.expected * 100);
        },
        get differs() { return this.diffCents !== 0; },
        get receivedError() { return this.srv.received || (this.touched.received ? this.amountCheck(this.received) : ''); },
        get noteError() { return this.srv.note || (this.touched.note && this.differs && this.note.trim() === '' ? 'Add a note explaining the difference.' : ''); },
        get resolutionError() { return this.srv.resolution || (this.touched.resolution && this.resolution.trim().length < 5 ? 'Write at least 5 characters.' : ''); },
        get canConfirm() { return !this.amountCheck(this.received) && (!this.differs || this.note.trim() !== ''); },
        get canResolve() { return this.resolution.trim().length >= 5; },
        get heldDiff() {
            if (this.batch.received === null) return 0;
            return Math.round(this.batch.received * 100) - Math.round(this.batch.expected * 100);
        },
        openDialog(btn, restore = false) {
            const d = btn.dataset;
            this.trigger = btn;
            this.batch = { id: d.id, reference: d.reference, company: d.company, parcels: Number(d.parcels), expected: Number(d.expected), received: d.received === '' ? null : Number(d.received), note: d.note };
            this.mode = d.mode;
            if (!restore) {
                this.received = Number(d.expected).toFixed(2);
                this.note = '';
                this.resolution = '';
                this.srv = { received: '', note: '', resolution: '' };
                this.touched = { received: false, note: false, resolution: false };
            }
            this.open = true;
            this.$nextTick(() => {
                const el = this.mode === 'confirm' ? this.$refs.amount : this.$refs.resolution;
                if (!el) return;
                el.focus();
                if (this.mode === 'confirm') el.select();
            });
        },
        restore(id) {
            const btn = Array.from(document.querySelectorAll('[data-open-batch]')).find((b) => b.dataset.id === id);
            if (btn) this.openDialog(btn, true);
        },
        closeDialog() {
            if (!this.open) return;
            this.open = false;
            if (this.trigger && document.contains(this.trigger)) this.trigger.focus();
        },
    }" @keydown.escape.window="closeDialog()" @if ($reopenId !== '') x-init="$nextTick(() => restore(@js($reopenId)))" @endif>

        {{-- Header --}}
        <div class="mb-5">
            <h1 class="font-display text-xl font-semibold leading-tight text-vd-ink sm:text-2xl">Cash remittances</h1>
            <p class="mt-1 text-sm text-vd-muted">Cash that Logistics Companies send to Vendo: item price plus Vendo's delivery commission.</p>
        </div>

        {{-- Saved, or the action failed. Field errors show in the dialog when it can reopen. --}}
        <x-admin.flash class="mb-5" :except="$reopenId !== '' ? ['received_amount', 'note', 'resolution_note'] : []" />

        {{-- Status cards: they also filter the table. Select the active card again to clear it. --}}
        <div class="mb-5 grid grid-cols-1 gap-3 sm:grid-cols-3 sm:gap-4">
            @foreach ($cards as $card)
                @php $item = $summary[$card['key']]; @endphp
                <button type="button" @click="pick('{{ $card['key'] }}')" style="--i: {{ $loop->index }}"
                    :aria-pressed="status === '{{ $card['key'] }}'"
                    :class="status === '{{ $card['key'] }}' ? 'border-vd-plum bg-[#DDD0DB] ring-2 ring-vd-orchid/30' : 'border-vd-orchid bg-[#E6DEE5] hover:-translate-y-0.5 hover:border-vd-plum hover:bg-[#E0D5DF] hover:shadow-[0_8px_16px_-10px_rgba(59,23,53,0.45)]'"
                    class="rg-card flex items-center gap-3 rounded-[18px] border px-4 py-3 text-left
                           focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-plum/40">
                    <span class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-vd-orchid text-white" aria-hidden="true">
                        <x-admin.icon :name="$card['icon']" class="h-[18px] w-[18px]" />
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate font-display text-[13px] font-medium text-vd-ink">{{ $card['label'] }}</span>
                        <span class="block font-display text-lg font-semibold leading-tight tabular-nums text-vd-ink">₱{{ number_format($item['amount'], 2) }}</span>
                        <span class="block text-xs text-vd-muted">{{ number_format($item['batches']) }} {{ $item['batches'] === 1 ? 'batch' : 'batches' }}</span>
                    </span>
                </button>
            @endforeach
        </div>

        {{-- Search --}}
        <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center">
            <div class="group relative w-full sm:max-w-[400px]">
                <x-admin.icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-vd-plum transition-transform duration-200 group-focus-within:scale-110" />
                <input type="text" x-model="q" @input="onSearch()" @keydown.escape="q = ''; apply()"
                    autocomplete="off" aria-label="Search batches" placeholder="Search reference or Company…"
                    class="h-10 w-full rounded-full border border-vd-orchid bg-vd-paper pl-10 pr-9 text-[13px] text-vd-ink placeholder:text-vd-muted
                           transition-all duration-200 ease-out hover:border-vd-plum hover:bg-white
                           focus:border-vd-plum focus:bg-white focus:outline-none focus:ring-2 focus:ring-vd-plum/20 focus:shadow-[0_4px_12px_-6px_rgba(59,23,53,0.35)]">
                <button type="button" x-show="q !== ''" x-cloak @click="q = ''; apply()" aria-label="Clear search"
                    class="absolute right-2.5 top-1/2 flex h-6 w-6 -translate-y-1/2 items-center justify-center rounded-full text-vd-muted transition-colors duration-200 hover:bg-vd-lavender hover:text-vd-plum">
                    <x-admin.icon name="x" class="h-3.5 w-3.5" />
                </button>
            </div>
            <button type="button" x-show="filtered" x-cloak @click="clearAll()"
                class="inline-flex h-10 items-center justify-center rounded-full px-3 text-[13px] font-medium text-vd-plum transition-colors duration-200 hover:bg-vd-lavender
                       focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-plum/40">
                Clear filters
            </button>
        </div>

        {{-- The table could not load --}}
        <div x-show="failed" x-cloak role="alert"
            class="mb-5 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-vd-danger-line bg-vd-danger-bg p-4 text-sm text-vd-danger-ink">
            <span class="flex items-center gap-3">
                <x-admin.icon name="alert-circle" class="h-[18px] w-[18px] flex-shrink-0" />
                <span><span class="font-semibold">Could not load the batches.</span> <span x-text="failedMsg"></span></span>
            </span>
            <button type="button" @click="load()"
                class="inline-flex h-9 items-center rounded-lg border border-vd-danger-line bg-white px-3 text-[13px] font-semibold text-vd-danger-ink transition-colors duration-200 hover:border-vd-danger-ink">
                Retry
            </button>
        </div>

        {{-- Table --}}
        <div x-show="slow" x-cloak aria-hidden="true">
            <div class="overflow-hidden rounded-xl border border-vd-line bg-white" role="status" aria-label="Loading batches">
                <div class="border-b border-vd-line bg-vd-tint px-5 py-3.5"><div class="vd-skeleton h-3.5 w-full max-w-md"></div></div>
                <div class="divide-y divide-vd-line">
                    @foreach (range(1, 6) as $i)
                        <div class="flex items-center gap-4 px-5 py-3.5">
                            <div class="flex-1 space-y-2">
                                <div class="vd-skeleton h-3.5 w-36"></div>
                                <div class="vd-skeleton h-3 w-28"></div>
                            </div>
                            <div class="vd-skeleton hidden h-3.5 w-28 sm:block"></div>
                            <div class="vd-skeleton h-3.5 w-20"></div>
                            <div class="vd-skeleton h-6 w-20 rounded-full"></div>
                            <div class="vd-skeleton h-8 w-28 flex-shrink-0 rounded-lg"></div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        <div id="rm-table" x-ref="table" x-show="!slow" :aria-busy="loading" aria-live="polite" @click="onTableClick($event)">
            @include('admin.remittances.partials.batches-table')
        </div>

        {{-- Dialog: confirm receipt (pending batch) or resolve (discrepancy) --}}
        <div x-show="open" x-cloak
            x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            @click.self="closeDialog()"
            class="fixed inset-0 z-50 flex items-end justify-center bg-vd-ink/50 p-0 sm:items-center sm:p-4"
            role="dialog" aria-modal="true" aria-labelledby="rm-dialog-title">

            <div @click.stop class="flex max-h-[min(92dvh,720px)] w-full max-w-lg flex-col overflow-hidden rounded-t-2xl border border-vd-line bg-white sm:rounded-2xl">

                {{-- Header --}}
                <div class="flex items-start gap-3 border-b border-vd-line p-4">
                    <div class="min-w-0 flex-1">
                        <h2 id="rm-dialog-title" class="font-display text-base font-semibold leading-snug text-vd-ink" x-text="mode === 'confirm' ? 'Confirm receipt' : 'Resolve discrepancy'"></h2>
                        <p class="mt-0.5 text-[13px] text-vd-muted [overflow-wrap:anywhere]"><span x-text="batch.reference"></span> · <span x-text="batch.company"></span></p>
                    </div>
                    <button type="button" @click="closeDialog()" aria-label="Close dialog"
                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg text-vd-muted transition-colors duration-200 hover:bg-vd-tint hover:text-vd-plum
                               focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-plum/40">
                        <x-admin.icon name="x" class="h-4 w-4" />
                    </button>
                </div>

                {{-- Confirm receipt --}}
                <form x-show="mode === 'confirm'" x-cloak method="POST" :action="receiveUrl.replace('__ID__', batch.id)" novalidate class="flex min-h-0 flex-1 flex-col">
                    @csrf
                    <input type="hidden" name="_batch" :value="batch.id">
                    <input type="hidden" name="_mode" value="confirm">

                    <div class="thin-scroll flex-1 space-y-4 overflow-y-auto p-4 sm:p-5">
                        <div class="grid grid-cols-2 gap-3">
                            <div class="rounded-xl border border-vd-line bg-vd-tint p-3.5">
                                <p class="text-xs text-vd-muted">Expected</p>
                                <p class="mt-1 font-display text-lg font-semibold tabular-nums text-vd-ink" x-text="peso(batch.expected)"></p>
                            </div>
                            <div class="rounded-xl border border-vd-line bg-vd-tint p-3.5">
                                <p class="text-xs text-vd-muted">Parcels</p>
                                <p class="mt-1 font-display text-lg font-semibold tabular-nums text-vd-ink" x-text="batch.parcels"></p>
                            </div>
                        </div>

                        <div>
                            <label for="rmReceived" class="mb-1.5 block text-[13px] font-medium text-vd-ink">Amount received</label>
                            <div class="relative">
                                <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-vd-muted" aria-hidden="true">₱</span>
                                <input id="rmReceived" x-ref="amount" name="received_amount" type="text" inputmode="decimal" autocomplete="off"
                                    x-model="received" @input="srv.received = ''; touched.received = true" @blur="touched.received = true"
                                    :aria-invalid="receivedError ? 'true' : 'false'" aria-describedby="rmReceivedHint"
                                    :class="receivedError ? 'border-vd-danger-ink focus:border-vd-danger-ink focus:ring-vd-danger-ink/20' : 'border-vd-line-strong hover:border-vd-plum-soft focus:border-vd-plum focus:ring-vd-plum/20'"
                                    class="{{ $field }} pl-7 pr-3 tabular-nums">
                            </div>
                            <p id="rmReceivedHint" x-show="receivedError" x-cloak class="mt-1.5 text-xs text-vd-danger-ink" x-text="receivedError"></p>
                        </div>

                        {{-- What will happen with this amount --}}
                        <div x-show="!amountCheck(received)" x-cloak aria-live="polite">
                            <div x-show="!differs" class="flex items-start gap-2 rounded-xl border border-vd-success-line bg-vd-success-bg px-3.5 py-3 text-[13px] text-vd-success-ink">
                                <x-admin.icon name="check-circle" class="mt-0.5 h-4 w-4 flex-shrink-0" />
                                <span>Matches the expected amount. Sellers on this batch are credited when you confirm.</span>
                            </div>
                            <div x-show="differs" x-cloak class="flex items-start gap-2 rounded-xl border border-vd-warning-line bg-vd-warning-bg px-3.5 py-3 text-[13px] text-vd-warning-ink">
                                <x-admin.icon name="triangle-alert" class="mt-0.5 h-4 w-4 flex-shrink-0" />
                                <span><span x-text="peso(Math.abs(diffCents) / 100)"></span> <span x-text="diffCents < 0 ? 'short' : 'over'"></span>. The batch is held until you resolve it, and Sellers are not credited yet.</span>
                            </div>
                        </div>

                        <div x-show="differs" x-cloak>
                            <label for="rmNote" class="mb-1.5 block text-[13px] font-medium text-vd-ink">Why does it differ?</label>
                            <input id="rmNote" name="note" maxlength="500" autocomplete="off" x-model="note" @input="srv.note = ''; touched.note = true" @blur="touched.note = true"
                                :disabled="!differs" :aria-invalid="noteError ? 'true' : 'false'" aria-describedby="rmNoteHint"
                                :class="noteError ? 'border-vd-danger-ink focus:border-vd-danger-ink focus:ring-vd-danger-ink/20' : 'border-vd-line-strong hover:border-vd-plum-soft focus:border-vd-plum focus:ring-vd-plum/20'"
                                class="{{ $field }} px-3">
                            <p id="rmNoteHint" class="mt-1.5 text-xs" :class="noteError ? 'text-vd-danger-ink' : 'text-vd-muted'"
                                x-text="noteError || 'Required. Say what the Company reported.'"></p>
                        </div>
                    </div>

                    <div class="flex flex-col-reverse gap-2 border-t border-vd-line bg-vd-tint px-4 py-3.5 sm:flex-row sm:justify-end sm:px-5">
                        <button type="button" @click="closeDialog()" class="{{ $ghost }} justify-center">Cancel</button>
                        <button type="submit" :disabled="!canConfirm" class="{{ $solid }} justify-center" x-text="differs ? 'Record discrepancy' : 'Confirm receipt'">Confirm receipt</button>
                    </div>
                </form>

                {{-- Resolve discrepancy --}}
                <form x-show="mode === 'resolve'" x-cloak method="POST" :action="resolveUrl.replace('__ID__', batch.id)" novalidate class="flex min-h-0 flex-1 flex-col">
                    @csrf @method('PATCH')
                    <input type="hidden" name="_batch" :value="batch.id">
                    <input type="hidden" name="_mode" value="resolve">

                    <div class="thin-scroll flex-1 space-y-4 overflow-y-auto p-4 sm:p-5">
                        <div class="grid grid-cols-3 gap-3">
                            <div class="rounded-xl border border-vd-line bg-vd-tint p-3.5">
                                <p class="text-xs text-vd-muted">Expected</p>
                                <p class="mt-1 font-display text-base font-semibold tabular-nums text-vd-ink" x-text="peso(batch.expected)"></p>
                            </div>
                            <div class="rounded-xl border border-vd-line bg-vd-tint p-3.5">
                                <p class="text-xs text-vd-muted">Received</p>
                                <p class="mt-1 font-display text-base font-semibold tabular-nums text-vd-ink" x-text="peso(batch.received)"></p>
                            </div>
                            <div class="rounded-xl border border-vd-danger-line bg-vd-danger-bg p-3.5">
                                <p class="text-xs text-vd-danger-ink" x-text="heldDiff < 0 ? 'Short' : 'Over'"></p>
                                <p class="mt-1 font-display text-base font-semibold tabular-nums text-vd-danger-ink" x-text="peso(Math.abs(heldDiff) / 100)"></p>
                            </div>
                        </div>

                        <div x-show="batch.note" x-cloak class="rounded-xl border border-vd-line bg-vd-tint px-3.5 py-3 text-[13px]">
                            <p class="text-xs text-vd-muted">Receipt note</p>
                            <p class="mt-0.5 text-vd-ink [overflow-wrap:anywhere]" x-text="batch.note"></p>
                        </div>

                        <div>
                            <label for="rmResolution" class="mb-1.5 block text-[13px] font-medium text-vd-ink">Resolution note</label>
                            <textarea id="rmResolution" x-ref="resolution" name="resolution_note" rows="3" maxlength="1000"
                                x-model="resolution" @input="srv.resolution = ''; touched.resolution = true" @blur="touched.resolution = true"
                                :aria-invalid="resolutionError ? 'true' : 'false'" aria-describedby="rmResolutionHint"
                                :class="resolutionError ? 'border-vd-danger-ink focus:border-vd-danger-ink focus:ring-vd-danger-ink/20' : 'border-vd-line-strong hover:border-vd-plum-soft focus:border-vd-plum focus:ring-vd-plum/20'"
                                class="w-full resize-none rounded-lg border bg-white px-3 py-2.5 text-sm text-vd-ink transition-colors duration-200 focus:outline-none focus:ring-2"></textarea>
                            <p id="rmResolutionHint" class="mt-1.5 text-xs" :class="resolutionError ? 'text-vd-danger-ink' : 'text-vd-muted'"
                                x-text="resolutionError || 'Say how the difference was settled. The batch then counts in full and Sellers are credited.'"></p>
                        </div>
                    </div>

                    <div class="flex flex-col-reverse gap-2 border-t border-vd-line bg-vd-tint px-4 py-3.5 sm:flex-row sm:justify-end sm:px-5">
                        <button type="button" @click="closeDialog()" class="{{ $ghost }} justify-center">Cancel</button>
                        <button type="submit" :disabled="!canResolve" class="{{ $solid }} justify-center">Resolve discrepancy</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-admin.layout>
