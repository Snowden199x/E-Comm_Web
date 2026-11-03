<x-admin.layout title="Delivery Fees">
    {{-- Shared card and row motion (rg- prefix). --}}
    @vite('resources/css/admin/registrations.css')

    @php
        $savedVendo = number_format((float) $setting->vendo_delivery_commission_rate, 2, '.', '');
        $savedSeller = number_format((float) $setting->seller_commission_rate, 2, '.', '');
        $vendoStart = old('vendo_delivery_commission_rate', $savedVendo);
        $sellerStart = old('seller_commission_rate', $savedSeller);

        $input = 'h-10 w-full rounded-lg border bg-white pl-3 pr-9 text-sm tabular-nums text-vd-ink transition-colors duration-200
                  focus:outline-none focus:ring-2 disabled:cursor-not-allowed disabled:bg-vd-tint disabled:text-vd-muted';
        $chip = 'flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-vd-lavender text-vd-plum';
        $card = 'rg-card rounded-xl border border-vd-line bg-white p-5';
    @endphp

    {{--
        The rates form posts normally (a spinner shows on Save rates). The preview and the unsaved-changes bar
        follow what is typed. The distributions table pages in place so typed rates are not lost.
    --}}
    <div class="rg-page mx-auto w-full max-w-[1440px] p-4 sm:p-6 lg:p-7" x-data="{
        // Rates form
        vendo: @js((string) $vendoStart),
        seller: @js((string) $sellerStart),
        saved: { vendo: @js($savedVendo), seller: @js($savedSeller) },
        srv: { vendo: @js($errors->first('vendo_delivery_commission_rate')), seller: @js($errors->first('seller_commission_rate')) },
        touched: { vendo: false, seller: false },

        // Preview example
        fee: 100, rider: 10, item: 500,

        // Distributions table
        page: {{ $history->currentPage() }},
        loading: false,
        slow: false,
        failed: false,
        failedMsg: '',
        ctrl: null,
        slowTimer: null,
        pageUrl: @js(route('admin.delivery-fees.index')),

        // ----- Rates ---------------------------------------------------------
        check(v) {
            const s = String(v).trim();
            if (s === '') return 'Enter a rate from 0 to 100.';
            const n = Number(s);
            if (Number.isNaN(n)) return 'Use numbers only, like 7.5.';
            if (n < 0 || n > 100) return 'Enter a rate from 0 to 100.';
            if (!/^\d+(\.\d{1,2})?$/.test(s)) return 'Use at most 2 decimals, like 7.25.';
            return '';
        },
        get vendoError() { return this.srv.vendo || (this.touched.vendo ? this.check(this.vendo) : ''); },
        get sellerError() { return this.srv.seller || (this.touched.seller ? this.check(this.seller) : ''); },
        get hasError() { return !!(this.check(this.vendo) || this.check(this.seller)); },
        get dirty() { return Number(this.vendo) !== Number(this.saved.vendo) || Number(this.seller) !== Number(this.saved.seller); },
        rate(v) { const n = Number(v); return (Number.isFinite(n) ? String(n) : '?') + '%'; },
        get changes() {
            const out = [];
            if (Number(this.vendo) !== Number(this.saved.vendo)) out.push('Delivery fee commission ' + this.rate(this.saved.vendo) + ' → ' + this.rate(this.vendo));
            if (Number(this.seller) !== Number(this.saved.seller)) out.push('Seller commission ' + this.rate(this.saved.seller) + ' → ' + this.rate(this.seller));
            return out;
        },
        discard() {
            this.vendo = this.saved.vendo;
            this.seller = this.saved.seller;
            this.srv = { vendo: '', seller: '' };
            this.touched = { vendo: false, seller: false };
        },

        // ----- Preview (in cents, same rounding as the server) -------------------
        peso(cents) { return '₱' + (cents / 100).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
        clamp(v) { const n = Number(v); return Number.isFinite(n) ? Math.min(100, Math.max(0, n)) : 0; },
        pct(cents, rate) { return Math.floor((cents * Math.round(this.clamp(rate) * 100) + 5000) / 10000); },
        get remaining() { return (this.fee - this.rider) * 100; },
        get vendoCents() { return this.pct(this.remaining, this.vendo); },
        get logisticsCents() { return this.remaining - this.vendoCents; },
        get sellerCents() { return this.pct(this.item * 100, this.seller); },
        show(cents, field) { return this.check(this[field]) ? '—' : this.peso(cents); },

        // ----- Distributions table --------------------------------------------
        onTableClick(event) {
            const btn = event.target.closest('[data-page]');
            if (!btn || btn.disabled) return;
            this.page = Number(btn.dataset.page);
            this.load();
        },
        errorText(status) {
            if (!navigator.onLine) return 'You are offline. Reconnect, then select Retry.';
            if (status === 401 || status === 419) return 'Your session ended. Reload the page and sign in again.';
            if (status === 403) return 'This account cannot view delivery fees. Sign in with an admin account.';
            if (status >= 500) return 'Vendo could not load the table. Select Retry. If it keeps failing, reload the page.';
            return 'Check your connection, then select Retry.';
        },
        async load() {
            if (this.ctrl) this.ctrl.abort();
            const ctrl = new AbortController();
            this.ctrl = ctrl;
            this.loading = true;
            this.failed = false;
            clearTimeout(this.slowTimer);
            this.slowTimer = setTimeout(() => { this.slow = true; }, 150);

            const url = this.pageUrl + '?page=' + this.page;
            try {
                const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }, signal: ctrl.signal });
                if (res.redirected && res.url !== new URL(url, location.origin).href) { window.location.href = res.url; return; }
                if (!res.ok) { const err = new Error('HTTP ' + res.status); err.status = res.status; throw err; }

                const doc = new DOMParser().parseFromString(await res.text(), 'text/html');
                const next = doc.getElementById('df-table');
                if (!next) throw new Error('Missing region');
                if (ctrl.signal.aborted) return;

                const region = this.$refs.table;
                region.innerHTML = next.innerHTML;
                region.classList.remove('rg-fresh');
                void region.offsetWidth; // restart the row animation
                region.classList.add('rg-fresh');

                history.replaceState(null, '', url);
                region.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
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
    }">

        {{-- Header --}}
        <div class="mb-5">
            <h1 class="font-display text-xl font-semibold leading-tight text-vd-ink sm:text-2xl">Delivery fees</h1>
            <p class="mt-1 text-sm text-vd-muted">Set Vendo's commission rates and see how each delivery fee was split.</p>
        </div>

        {{-- Saved, or the form failed. Field errors show under the field instead. --}}
        <x-admin.flash class="mb-5" :except="['vendo_delivery_commission_rate', 'seller_commission_rate']" />

        {{-- Summary cards --}}
        <div class="mb-5 grid grid-cols-1 gap-5 sm:grid-cols-3">
            @foreach ([
                ['Delivery commission earned', '₱' . number_format($totalCommission, 2), 'banknote', 'All time'],
                ['Delivery commission this month', '₱' . number_format($monthCommission, 2), 'calendar', now()->format('F Y')],
                ['Parcels split', number_format($parcels), 'truck', 'Delivered with saved rates'],
            ] as [$label, $value, $icon, $hint])
                <div class="{{ $card }}" style="--i: {{ $loop->index }}">
                    <div class="flex items-start justify-between gap-3">
                        <p class="text-[13px] font-medium text-vd-muted">{{ $label }}</p>
                        <span class="{{ $chip }}" aria-hidden="true"><x-admin.icon :name="$icon" class="h-[18px] w-[18px]" /></span>
                    </div>
                    <p class="mt-3 font-display text-2xl font-semibold tabular-nums text-vd-ink">{{ $value }}</p>
                    <p class="mt-1 text-xs text-vd-muted">{{ $hint }}</p>
                </div>
            @endforeach
        </div>

        {{-- Rates and preview --}}
        <div class="mb-8 grid grid-cols-1 gap-5 lg:grid-cols-3">
            <form method="POST" action="{{ route('admin.delivery-fees.update') }}" novalidate
                class="rg-card flex flex-col overflow-hidden rounded-xl border border-vd-line bg-white lg:col-span-2" style="--i: 3">
                @csrf @method('PUT')

                <div class="p-5">
                    <h2 class="font-display text-base font-semibold text-vd-ink">Commission rates</h2>
                    <p class="mt-1 text-sm text-vd-muted">
                        New orders use these rates. Existing orders keep theirs.
                        @if ($setting->updatedBy)
                            Last changed by {{ $setting->updatedBy->name }}, {{ $setting->updated_at?->format('M j, Y, g:i A') }}.
                        @endif
                    </p>

                    <div class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div>
                            <label for="vendoRate" class="mb-1.5 block text-[13px] font-medium text-vd-ink">Delivery fee commission</label>
                            <div class="relative">
                                <input id="vendoRate" name="vendo_delivery_commission_rate" type="text" inputmode="decimal" autocomplete="off"
                                    x-model="vendo" @input="srv.vendo = ''; touched.vendo = true" @blur="touched.vendo = true" @disabled(! $canEdit)
                                    :aria-invalid="vendoError ? 'true' : 'false'" aria-describedby="vendoHint"
                                    :class="vendoError ? 'border-vd-danger-ink focus:border-vd-danger-ink focus:ring-vd-danger-ink/20' : 'border-vd-line-strong hover:border-vd-plum-soft focus:border-vd-plum focus:ring-vd-plum/20'"
                                    class="{{ $input }}">
                                <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-sm text-vd-muted" aria-hidden="true">%</span>
                            </div>
                            <p id="vendoHint" class="mt-1.5 text-xs" :class="vendoError ? 'text-vd-danger-ink' : 'text-vd-muted'"
                                x-text="vendoError || 'Vendo\'s share of the delivery fee after Rider pay.'"></p>
                        </div>

                        <div>
                            <label for="sellerRate" class="mb-1.5 block text-[13px] font-medium text-vd-ink">Seller commission</label>
                            <div class="relative">
                                <input id="sellerRate" name="seller_commission_rate" type="text" inputmode="decimal" autocomplete="off"
                                    x-model="seller" @input="srv.seller = ''; touched.seller = true" @blur="touched.seller = true" @disabled(! $canEdit)
                                    :aria-invalid="sellerError ? 'true' : 'false'" aria-describedby="sellerHint"
                                    :class="sellerError ? 'border-vd-danger-ink focus:border-vd-danger-ink focus:ring-vd-danger-ink/20' : 'border-vd-line-strong hover:border-vd-plum-soft focus:border-vd-plum focus:ring-vd-plum/20'"
                                    class="{{ $input }}">
                                <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-sm text-vd-muted" aria-hidden="true">%</span>
                            </div>
                            <p id="sellerHint" class="mt-1.5 text-xs" :class="sellerError ? 'text-vd-danger-ink' : 'text-vd-muted'"
                                x-text="sellerError || 'Vendo\'s share of the item price, taken from the Seller.'"></p>
                        </div>
                    </div>
                </div>

                {{-- Footer: what will change, then the actions --}}
                <div class="mt-auto flex flex-col gap-3 border-t border-vd-line bg-vd-tint px-5 py-3.5 sm:flex-row sm:items-center sm:justify-between">
                    @if ($canEdit)
                        <p class="text-[13px] text-vd-muted" aria-live="polite">
                            <span x-show="!dirty">No changes to save.</span>
                            <span x-show="dirty" x-cloak class="font-medium text-vd-ink" x-text="changes.join(' · ')"></span>
                        </p>
                        <div class="flex items-center gap-2">
                            <button type="button" x-show="dirty" x-cloak @click="discard()"
                                class="inline-flex h-10 items-center rounded-lg border border-vd-line-strong bg-white px-4 text-[13px] font-medium text-vd-plum transition-all duration-200
                                       hover:border-vd-plum hover:bg-vd-tint active:scale-95
                                       focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-plum/40">
                                Discard changes
                            </button>
                            <button type="submit" :disabled="!dirty || hasError"
                                class="inline-flex h-10 items-center rounded-lg bg-vd-plum px-5 text-[13px] font-semibold text-white transition-all duration-200
                                       hover:bg-vd-plum-hover active:scale-95 disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:bg-vd-plum disabled:active:scale-100
                                       focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-plum/40 focus-visible:ring-offset-2">
                                Save rates
                            </button>
                        </div>
                    @else
                        <p class="flex items-center gap-2 text-[13px] text-vd-muted">
                            <x-admin.icon name="lock" class="h-4 w-4 flex-shrink-0" />
                            Only a super admin can change these rates. Ask one to update them.
                        </p>
                    @endif
                </div>
            </form>

            {{-- Preview --}}
            <section class="rg-card rounded-xl border border-vd-line bg-white p-5" style="--i: 4" aria-labelledby="previewTitle">
                <h2 id="previewTitle" class="font-display text-base font-semibold text-vd-ink">Preview</h2>
                <p class="mt-1 text-sm text-vd-muted">Updates as you type. Each Company sets its own Rider pay.</p>

                <dl class="mt-4 text-sm">
                    <div class="flex justify-between py-1.5"><dt class="text-vd-muted">Delivery fee</dt><dd class="tabular-nums text-vd-ink" x-text="peso(fee * 100)"></dd></div>
                    <div class="flex justify-between py-1.5"><dt class="text-vd-muted">Rider pay</dt><dd class="tabular-nums text-vd-ink" x-text="peso(rider * 100)"></dd></div>
                    <div class="flex justify-between py-1.5"><dt class="text-vd-muted">Remaining</dt><dd class="tabular-nums text-vd-ink" x-text="peso(remaining)"></dd></div>
                    <div class="flex justify-between py-1.5"><dt class="font-medium text-vd-ink">Vendo commission</dt><dd class="font-semibold tabular-nums text-vd-ink" x-text="show(vendoCents, 'vendo')"></dd></div>
                    <div class="flex justify-between py-1.5"><dt class="text-vd-muted">Logistics Company</dt><dd class="tabular-nums text-vd-ink" x-text="show(logisticsCents, 'vendo')"></dd></div>
                </dl>

                <dl class="mt-3 border-t border-vd-line pt-3 text-sm">
                    <div class="flex justify-between py-1.5"><dt class="text-vd-muted">Item price</dt><dd class="tabular-nums text-vd-ink" x-text="peso(item * 100)"></dd></div>
                    <div class="flex justify-between py-1.5"><dt class="font-medium text-vd-ink">Seller commission</dt><dd class="font-semibold tabular-nums text-vd-ink" x-text="show(sellerCents, 'seller')"></dd></div>
                    <div class="flex justify-between py-1.5"><dt class="text-vd-muted">Seller keeps</dt><dd class="tabular-nums text-vd-ink" x-text="show(item * 100 - sellerCents, 'seller')"></dd></div>
                </dl>
            </section>
        </div>

        {{-- Distributions --}}
        <div class="mb-4">
            <h2 class="font-display text-base font-semibold text-vd-ink">Delivery fee distributions</h2>
            <p class="mt-1 text-sm text-vd-muted">One row per delivered parcel. Orders placed before saved rates are not listed.</p>
        </div>

        {{-- The table could not load --}}
        <div x-show="failed" x-cloak role="alert"
            class="mb-5 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-vd-danger-line bg-vd-danger-bg p-4 text-sm text-vd-danger-ink">
            <span class="flex items-center gap-3">
                <x-admin.icon name="alert-circle" class="h-[18px] w-[18px] flex-shrink-0" />
                <span><span class="font-semibold">Could not load the table.</span> <span x-text="failedMsg"></span></span>
            </span>
            <button type="button" @click="load()"
                class="inline-flex h-9 items-center rounded-lg border border-vd-danger-line bg-white px-3 text-[13px] font-semibold text-vd-danger-ink transition-colors duration-200 hover:border-vd-danger-ink">
                Retry
            </button>
        </div>

        <div x-show="slow" x-cloak aria-hidden="true">
            <div class="overflow-hidden rounded-xl border border-vd-line bg-white" role="status" aria-label="Loading distributions">
                <div class="border-b border-vd-line bg-vd-tint px-5 py-3.5"><div class="vd-skeleton h-3.5 w-full max-w-md"></div></div>
                <div class="divide-y divide-vd-line">
                    @foreach (range(1, 6) as $i)
                        <div class="flex items-center gap-4 px-5 py-3.5">
                            <div class="vd-skeleton h-3.5 w-28"></div>
                            <div class="vd-skeleton ml-auto h-3.5 w-20"></div>
                            <div class="vd-skeleton hidden h-3.5 w-16 sm:block"></div>
                            <div class="vd-skeleton hidden h-3.5 w-20 md:block"></div>
                            <div class="vd-skeleton hidden h-3.5 w-24 md:block"></div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        <div id="df-table" x-ref="table" x-show="!slow" :aria-busy="loading" aria-live="polite" @click="onTableClick($event)">
            @include('admin.delivery-fees.partials.distributions-table')
        </div>
    </div>
</x-admin.layout>
