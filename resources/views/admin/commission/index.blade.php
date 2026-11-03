<x-admin.layout title="Commission">
    {{-- Shared card and row motion (rg- prefix). --}}
    @vite('resources/css/admin/registrations.css')

    @php
        $monthDate = \Carbon\Carbon::parse($month . '-01');
        $initialSearch = request()->string('search')->trim()->toString();
    @endphp

    {{--
        Month, search and page changes fetch this page again and swap two regions: #cm-cards and #cm-table.
        Both always describe the same month. The seller breakdown comes from the named JSON route.
        The commission uses the rate saved on each order; the rate itself is edited on Delivery Fees.
    --}}
    <div class="rg-page mx-auto w-full max-w-[1440px] p-4 sm:p-6 lg:p-7" x-data="{
        // Filters
        q: @js($initialSearch),
        month: @js($month),
        lastMonth: @js($month),
        page: {{ $sellers->currentPage() }},

        // Loading: the skeleton shows only when a request takes longer than 150 ms, so fast loads do not flicker.
        loading: false,
        slow: false,
        failed: false,
        failedMsg: '',
        ctrl: null,
        timer: null,
        slowTimer: null,
        pageUrl: @js(route('admin.commission.index')),
        detailUrl: @js(route('admin.commission.seller-detail', ['seller' => '__ID__'])),

        // Seller breakdown dialog
        detailOpen: false,
        detailLoading: false,
        detailError: '',
        detailCtrl: null,
        detailTrigger: null,
        sellerDetail: null,
        detailMeta: { id: null, name: '', avatar: '' },

        // ----- Month and search ------------------------------------------------
        monthKey(d) {
            return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0');
        },
        get currentMonth() {
            return this.monthKey(new Date());
        },
        get isCurrentMonth() {
            return this.month >= this.currentMonth;
        },
        get monthLabel() {
            const [y, m] = this.month.split('-').map(Number);
            return new Date(y, m - 1, 1).toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
        },
        shiftMonth(delta) {
            if (delta > 0 && this.isCurrentMonth) return;
            const [y, m] = this.month.split('-').map(Number);
            this.month = this.monthKey(new Date(y, m - 1 + delta, 1));
            this.apply();
        },
        thisMonth() {
            this.month = this.currentMonth;
            this.apply();
        },
        // The native picker can be cleared or set to a future month: put back the last valid month.
        onMonthPick() {
            if (!/^\d{4}-\d{2}$/.test(this.month)) { this.month = this.lastMonth; return; }
            if (this.month > this.currentMonth) this.month = this.currentMonth;
            this.apply();
        },
        params() {
            const p = new URLSearchParams();
            if (this.q.trim()) p.set('search', this.q.trim());
            p.set('month', this.month);
            if (this.page > 1) p.set('page', this.page);
            return p;
        },
        onSearch() {
            clearTimeout(this.timer);
            this.page = 1;
            this.timer = setTimeout(() => this.load(), 300);
        },
        apply() {
            clearTimeout(this.timer);
            this.page = 1;
            this.lastMonth = this.month;
            this.load();
        },
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
            if (status === 403) return 'This account cannot view commission. Sign in with an admin account.';
            if (status >= 500) return 'Vendo could not load the figures. Select Retry. If it keeps failing, reload the page.';
            return 'Check your connection, then select Retry.';
        },

        // Fetches the page and swaps the cards and table together so both always describe the same month.
        async load(scrollToTable = false) {
            if (this.ctrl) this.ctrl.abort();
            const ctrl = new AbortController();
            this.ctrl = ctrl;
            this.loading = true;
            this.failed = false;
            clearTimeout(this.slowTimer);
            this.slowTimer = setTimeout(() => { this.slow = true; }, 150);

            const url = this.pageUrl + '?' + this.params().toString();
            try {
                const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }, signal: ctrl.signal });
                if (res.redirected && res.url !== new URL(url, location.origin).href) { window.location.href = res.url; return; }
                if (!res.ok) { const err = new Error('HTTP ' + res.status); err.status = res.status; throw err; }

                const doc = new DOMParser().parseFromString(await res.text(), 'text/html');
                const cards = doc.getElementById('cm-cards');
                const table = doc.getElementById('cm-table');
                if (!cards || !table) throw new Error('Missing region');
                if (ctrl.signal.aborted) return;

                for (const [el, next] of [[this.$refs.cards, cards], [this.$refs.table, table]]) {
                    el.innerHTML = next.innerHTML;
                    el.classList.remove('rg-fresh');
                    void el.offsetWidth; // restart the row animation
                    el.classList.add('rg-fresh');
                }

                history.replaceState(null, '', url);
                if (scrollToTable) this.$refs.table.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
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

        peso(n) {
            return '₱' + Number(n || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
        shareOf(value) {
            const total = this.sellerDetail ? Number(this.sellerDetail.total_sales) : 0;
            return total > 0 ? Math.round((Number(value) / total) * 100) : 0;
        },

        // ----- Seller breakdown dialog ------------------------------------------
        openFromButton(btn) {
            this.openSellerDetail(btn.dataset.id, btn.dataset.name, btn.dataset.avatar, btn);
        },
        async openSellerDetail(id, name, avatar, trigger = null) {
            if (trigger) this.detailTrigger = trigger;
            if (this.detailCtrl) this.detailCtrl.abort();
            const ctrl = new AbortController();
            this.detailCtrl = ctrl;

            this.detailMeta = { id, name, avatar };
            this.sellerDetail = null;
            this.detailError = '';
            this.detailLoading = true;
            this.detailOpen = true;
            this.$nextTick(() => this.$refs.detailClose && this.$refs.detailClose.focus());

            try {
                const res = await fetch(this.detailUrl.replace('__ID__', id) + '?month=' + encodeURIComponent(this.month), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    signal: ctrl.signal,
                });
                if (!res.ok) { const err = new Error('HTTP ' + res.status); err.status = res.status; throw err; }
                this.sellerDetail = await res.json();
            } catch (e) {
                if (e.name === 'AbortError') return;
                this.detailError = this.errorText(e.status);
            } finally {
                if (this.detailCtrl === ctrl) { this.detailLoading = false; this.detailCtrl = null; }
            }
        },
        closeDetail() {
            if (!this.detailOpen) return;
            if (this.detailCtrl) { this.detailCtrl.abort(); this.detailCtrl = null; }
            this.detailOpen = false;
            this.detailLoading = false;
            if (this.detailTrigger && document.contains(this.detailTrigger)) this.detailTrigger.focus();
        },
    }" @keydown.escape.window="closeDetail()">

        {{-- Header --}}
        <div class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="font-display text-xl font-semibold leading-tight text-vd-ink sm:text-2xl">Commission</h1>
                <p class="mt-1 text-sm text-vd-muted">Commission earned from completed sales, by month.</p>
            </div>

            {{-- Month switcher --}}
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" x-show="!isCurrentMonth" x-cloak @click="thisMonth()"
                    class="inline-flex h-10 items-center rounded-full px-3 text-[13px] font-medium text-vd-plum transition-colors duration-200 hover:bg-vd-lavender
                           focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-plum/40">
                    Go to this month
                </button>
                <div class="inline-flex items-center rounded-full border border-vd-orchid bg-vd-paper p-1">
                    <button type="button" @click="shiftMonth(-1)" aria-label="Show previous month"
                        class="flex h-8 w-8 items-center justify-center rounded-full text-vd-plum transition duration-200 hover:bg-vd-lavender active:scale-95
                               focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-plum/40">
                        <x-admin.icon name="chevron-left" class="h-4 w-4" stroke="2.2" />
                    </button>
                    <label class="relative flex h-8 min-w-[10rem] cursor-pointer items-center justify-center rounded-full px-3 text-[13px] font-medium text-vd-ink transition-colors duration-200 hover:bg-white focus-within:ring-2 focus-within:ring-vd-plum/30">
                        <span x-text="monthLabel">{{ $monthDate->format('F Y') }}</span>
                        {{-- The native month picker sits invisibly on top, so the label stays styled. --}}
                        <input type="month" x-model="month" :max="currentMonth" @change="onMonthPick()" @click="$el.showPicker && $el.showPicker()" aria-label="Choose month"
                            class="absolute inset-0 h-full w-full cursor-pointer opacity-0">
                    </label>
                    <button type="button" @click="shiftMonth(1)" :disabled="isCurrentMonth" aria-label="Show next month"
                        class="flex h-8 w-8 items-center justify-center rounded-full text-vd-plum transition duration-200 hover:bg-vd-lavender active:scale-95
                               disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:bg-transparent disabled:active:scale-100
                               focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-plum/40">
                        <x-admin.icon name="chevron-right" class="h-4 w-4" stroke="2.2" />
                    </button>
                </div>
            </div>
        </div>

        {{-- Summary cards --}}
        <div x-show="slow" x-cloak aria-hidden="true">@include('admin.commission.partials.skeleton', ['part' => 'cards'])</div>
        <div id="cm-cards" x-ref="cards" x-show="!slow" :aria-busy="loading" aria-live="polite">
            @include('admin.commission.partials.summary-cards')
        </div>

        {{-- Search --}}
        <div class="mb-5 group relative w-full sm:max-w-[400px]">
            <x-admin.icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-vd-plum transition-transform duration-200 group-focus-within:scale-110" />
            <input type="text" x-model="q" @input="onSearch()" @keydown.escape="q = ''; apply()"
                autocomplete="off" aria-label="Search sellers" placeholder="Search seller name or email…"
                class="h-10 w-full rounded-full border border-vd-orchid bg-vd-paper pl-10 pr-9 text-[13px] text-vd-ink placeholder:text-vd-muted
                       transition-all duration-200 ease-out hover:border-vd-plum hover:bg-white
                       focus:border-vd-plum focus:bg-white focus:outline-none focus:ring-2 focus:ring-vd-plum/20 focus:shadow-[0_4px_12px_-6px_rgba(59,23,53,0.35)]">
            <button type="button" x-show="q !== ''" x-cloak @click="q = ''; apply()" aria-label="Clear search"
                x-transition:enter="transition duration-150 ease-out" x-transition:enter-start="scale-75 opacity-0" x-transition:enter-end="scale-100 opacity-100"
                x-transition:leave="transition duration-100 ease-in" x-transition:leave-start="scale-100 opacity-100" x-transition:leave-end="scale-75 opacity-0"
                class="absolute right-2.5 top-1/2 flex h-6 w-6 -translate-y-1/2 items-center justify-center rounded-full text-vd-muted transition-colors duration-200 hover:bg-vd-lavender hover:text-vd-plum">
                <x-admin.icon name="x" class="h-3.5 w-3.5" />
            </button>
        </div>

        {{-- The page could not refresh --}}
        <div x-show="failed" x-cloak role="alert"
            class="mb-5 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-vd-danger-line bg-vd-danger-bg p-4 text-sm text-vd-danger-ink">
            <span class="flex items-center gap-3">
                <x-admin.icon name="alert-circle" class="h-[18px] w-[18px] flex-shrink-0" />
                <span><span class="font-semibold">Could not load commission.</span> <span x-text="failedMsg"></span></span>
            </span>
            <button type="button" @click="load()"
                class="inline-flex h-9 items-center rounded-lg border border-vd-danger-line bg-white px-3 text-[13px] font-semibold text-vd-danger-ink transition-colors duration-200 hover:border-vd-danger-ink">
                Retry
            </button>
        </div>

        {{-- Table --}}
        <div x-show="slow" x-cloak aria-hidden="true">@include('admin.commission.partials.skeleton', ['part' => 'table'])</div>
        <div id="cm-table" x-ref="table" x-show="!slow" :aria-busy="loading" aria-live="polite" @click="onTableClick($event)">
            @include('admin.commission.partials.commission-table')
        </div>

        @include('admin.commission.partials.seller-detail-modal')
    </div>
</x-admin.layout>
