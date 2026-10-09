<x-admin.layout title="Commission">
    {{-- Shared Registrations/User Management motion + loading styles (rg- prefix). --}}
    @vite('resources/css/admin/registrations.css')

    @php
        $monthDate = \Carbon\Carbon::parse($month . '-01');
        $rateLabel = rtrim(rtrim(number_format($rate, 2), '0'), '.');
        $initialSearch = request()->string('search')->trim()->toString();
        $effective = $stats['total_sales'] > 0 ? ($stats['total_commission'] / $stats['total_sales']) * 100 : null;
    @endphp

    {{--
        Commission (UI pass 2, 7 Oct).

        What changed, all in the view layer (no controller or route change):
          - The month picker and search now refresh the summary cards AND the table together. Before, only the
            table refreshed, so the cards kept showing the first month that was loaded.
          - The seller breakdown popup called url('commission/seller/..') which resolves to /commission/seller/..
            and misses the /admin prefix, so it silently failed. It now uses the named route.
          - Pagination buttons use data-page (the old Laravel links pointed at the bare partial route after the first refresh).
          - Errors and empty results are shown instead of failing silently; the rate dialog explains that changing the
            rate recalculates every month, and previews the effect before saving.
    --}}
    <div class="rg-page mx-auto w-full max-w-[1280px] p-4 sm:p-6" x-data="{
        // Filters
        q: @js($initialSearch),
        month: @js($month),
        page: {{ $sellers->currentPage() }},
        loading: false,
        failed: false,
        ctrl: null,
        timer: null,
        pageUrl: @js(route('admin.commission.index')),
        detailUrl: @js(route('admin.commission.seller-detail', ['seller' => '__ID__'])),

        // Rate dialog
        editRateOpen: false,
        currentRate: {{ (float) $rate }},
        newRate: {{ (float) $rate }},
        monthSales: {{ (float) $stats['total_sales'] }},

        // Seller breakdown dialog
        sellerDetailOpen: false,
        detailLoading: false,
        detailFailed: false,
        sellerDetail: null,
        detailMeta: { id: null, name: '', avatar: '' },
        sellerChart: null,

        saved: @js(session('confirmation') === 'rate_updated'),

        init() {
            if (this.saved) setTimeout(() => this.saved = false, 6000);
            this.$watch('sellerDetailOpen', (open) => { if (!open && this.sellerChart) { this.sellerChart.destroy(); this.sellerChart = null; } });
        },

        // ----- Month and search ------------------------------------------------
        get monthLabel() {
            const [y, m] = this.month.split('-').map(Number);
            return new Date(y, m - 1, 1).toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
        },
        get isCurrentMonth() {
            const n = new Date();
            return this.month === n.getFullYear() + '-' + String(n.getMonth() + 1).padStart(2, '0');
        },
        shiftMonth(delta) {
            const [y, m] = this.month.split('-').map(Number);
            const d = new Date(y, m - 1 + delta, 1);
            this.month = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0');
            this.apply();
        },
        thisMonth() {
            const n = new Date();
            this.month = n.getFullYear() + '-' + String(n.getMonth() + 1).padStart(2, '0');
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
            this.load();
        },
        onRegionClick(event) {
            const btn = event.target.closest('[data-page]');
            if (!btn || btn.disabled) return;
            this.page = Number(btn.dataset.page);
            this.load(true);
        },

        // Fetches the full page and swaps #cm-region (cards + table) so both always describe the same month.
        async load(scrollToTable = false) {
            if (this.ctrl) this.ctrl.abort();
            const ctrl = new AbortController();
            this.ctrl = ctrl;
            this.loading = true;
            this.failed = false;

            const url = this.pageUrl + '?' + this.params().toString();
            try {
                const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }, signal: ctrl.signal });
                if (res.redirected && res.url !== new URL(url, location.origin).href) { window.location.href = res.url; return; }
                if (!res.ok) throw new Error('HTTP ' + res.status);

                const doc = new DOMParser().parseFromString(await res.text(), 'text/html');
                const next = doc.getElementById('cm-region');
                if (!next) throw new Error('Missing region');
                if (ctrl.signal.aborted) return;

                const region = this.$refs.region;
                region.innerHTML = next.innerHTML;
                this.monthSales = Number(next.dataset.monthSales || 0);
                region.classList.remove('rg-fresh');
                void region.offsetWidth; // restart the row animation
                region.classList.add('rg-fresh');

                history.replaceState(null, '', url);
                if (scrollToTable) region.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            } catch (e) {
                if (e.name === 'AbortError') return;
                this.failed = true;
            } finally {
                if (this.ctrl === ctrl) { this.loading = false; this.ctrl = null; }
            }
        },

        // ----- Rate dialog -------------------------------------------------------
        get rateValid() {
            const n = Number(this.newRate);
            return this.newRate !== '' && !Number.isNaN(n) && n >= 0 && n <= 100;
        },
        get ratePreview() {
            return this.rateValid ? this.monthSales * (Number(this.newRate) / 100) : null;
        },
        openRate() {
            this.newRate = this.currentRate;
            this.editRateOpen = true;
            this.$nextTick(() => this.$refs.rateInput?.focus());
        },
        peso(n) {
            return '₱' + Number(n || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },

        // ----- Seller breakdown dialog ------------------------------------------
        async openSellerDetail(id, name, avatar) {
            this.detailMeta = { id, name, avatar };
            this.sellerDetail = null;
            this.detailFailed = false;
            this.detailLoading = true;
            this.sellerDetailOpen = true;
            try {
                const res = await fetch(this.detailUrl.replace('__ID__', id) + '?month=' + encodeURIComponent(this.month), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                this.sellerDetail = await res.json();
                this.detailLoading = false;
                this.$nextTick(() => this.renderChart());
            } catch (e) {
                this.detailLoading = false;
                this.detailFailed = true;
            }
        },
        renderChart() {
            if (this.sellerChart) this.sellerChart.destroy();
            const ctx = this.$refs.sellerChartCanvas;
            if (!ctx || !this.sellerDetail || !this.sellerDetail.items.length || typeof Chart === 'undefined') return;
            const top = this.sellerDetail.items.slice(0, 8);
            this.sellerChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: top.map(i => i.name.length > 18 ? i.name.slice(0, 17) + '…' : i.name),
                    datasets: [{ label: 'Sales', data: top.map(i => i.sales), backgroundColor: '#5b2963', borderRadius: 6, maxBarThickness: 28 }],
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false }, tooltip: { callbacks: { label: (c) => ' ' + this.peso(c.parsed.x) } } },
                    scales: {
                        x: { beginAtZero: true, grid: { color: '#f3edf4' }, ticks: { callback: (v) => v >= 1000 ? '₱' + (v / 1000) + 'k' : '₱' + v } },
                        y: { grid: { display: false } },
                    },
                },
            });
        },
    }" @keydown.escape.window="editRateOpen = false; sellerDetailOpen = false">

        {{-- Header --}}
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="font-display text-2xl font-semibold text-[#2B1730]">Commission</h1>
                <p class="mt-1 text-sm text-gray-500">What Vendo earns from each seller's completed sales, month by month.</p>
            </div>

            {{-- Month switcher --}}
            <div class="flex flex-wrap items-center gap-2">
                <div class="inline-flex items-center rounded-full border border-[#ddd0e0] bg-white p-1">
                    <button type="button" @click="shiftMonth(-1)" aria-label="Previous month"
                        class="flex h-9 w-9 items-center justify-center rounded-full text-gray-600 transition duration-150 hover:bg-[#F1E9F1]
                               focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40">
                        <x-admin.icon name="chevron-left" class="h-4 w-4" stroke="2.2" />
                    </button>
                    <label class="relative flex h-9 min-w-[10.5rem] cursor-pointer items-center justify-center px-3 text-sm font-medium text-[#2B1730]">
                        <span x-text="monthLabel">{{ $monthDate->format('F Y') }}</span>
                        {{-- The native month picker sits invisibly on top, so the label stays styled. --}}
                        <input type="month" x-model="month" @change="apply()" aria-label="Choose month"
                            class="absolute inset-0 h-full w-full cursor-pointer opacity-0">
                    </label>
                    <button type="button" @click="shiftMonth(1)" aria-label="Next month"
                        class="flex h-9 w-9 items-center justify-center rounded-full text-gray-600 transition duration-150 hover:bg-[#F1E9F1]
                               focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40">
                        <x-admin.icon name="chevron-right" class="h-4 w-4" stroke="2.2" />
                    </button>
                </div>
                <button type="button" x-show="!isCurrentMonth" x-cloak @click="thisMonth()"
                    class="inline-flex h-11 items-center rounded-full px-4 text-sm font-medium text-[#3b1735] transition duration-200 hover:bg-[#F1E9F1]
                           focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40">
                    This month
                </button>
            </div>
        </div>

        {{-- Saved confirmation (the controller already flashes 'rate_updated'; it was never shown) --}}
        <div x-show="saved" x-cloak x-transition.opacity role="status"
            class="mb-4 flex items-center justify-between gap-3 rounded-xl border border-green-600/30 bg-green-50 px-4 py-3 text-sm text-green-800">
            <span class="flex items-center gap-2"><x-admin.icon name="check-circle" class="h-4 w-4" /> Commission rate saved. Every month below now uses {{ $rateLabel }}%.</span>
            <button type="button" @click="saved = false" aria-label="Dismiss" class="rounded-full p-1 hover:bg-green-100"><x-admin.icon name="x" class="h-4 w-4" /></button>
        </div>

        {{-- Cards + table (swapped together on every month/search/page change) --}}
        <div class="relative">
            <div x-show="loading" x-cloak x-transition.opacity class="rg-bar" role="progressbar" aria-label="Loading commission"></div>

            <div id="cm-region" x-ref="region" data-month-sales="{{ (float) $stats['total_sales'] }}"
                class="rg-table-wrap" :aria-busy="loading" @click="onRegionClick($event)">
                @include('admin.commission.partials.summary-cards')

                {{-- Toolbar --}}
                <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="relative w-full sm:max-w-sm">
                        <x-admin.icon name="search" class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                        <input type="search" x-model="q" @input="onSearch()" autocomplete="off" aria-label="Search sellers"
                            placeholder="Search seller name or email"
                            class="h-11 w-full rounded-full border border-[#ddd0e0] bg-white pl-10 pr-4 text-sm text-[#2B1730] placeholder:text-gray-400
                                   transition duration-200 hover:border-[#cdbbd2] focus:border-[#3b1735] focus:outline-none focus:ring-2 focus:ring-[#3b1735]/20">
                    </div>
                    <p class="text-xs text-gray-500">Select a seller to see which products earned the commission.</p>
                </div>

                @include('admin.commission.partials.commission-table')
            </div>

            <div x-show="failed" x-cloak role="alert"
                class="mt-4 flex flex-col items-start justify-between gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 sm:flex-row sm:items-center">
                <span>We couldn't refresh the commission figures. Check your connection and try again.</span>
                <button type="button" @click="load()" class="rounded-full border border-red-300 bg-white px-4 py-1.5 text-[13px] font-medium text-red-700 hover:bg-red-100">Try again</button>
            </div>
        </div>

        @include('admin.commission.partials.edit-rate-modal')
        @include('admin.commission.partials.seller-detail-modal')
    </div>
</x-admin.layout>