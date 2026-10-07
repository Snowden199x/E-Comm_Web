<x-admin.layout title="Registrations">
    @vite('resources/css/admin/registrations.css')

    @php
        // Read filters defensively: query values can be arrays or junk.
        $initialSearch = request()->string('search')->trim()->toString();
        $initialDate = request()->string('date')->toString();
        $initialType = request()->string('user_type', 'all')->toString();
        $initialType = in_array($initialType, ['all', 'seller', 'buyer', 'logistics_center'], true) ? $initialType : 'all';

        $cards = [
            ['key' => 'all', 'label' => 'Pending Requests', 'value' => $stats['pending_request'], 'icon' => 'clock', 'tone' => 'purple'],
            ['key' => 'seller', 'label' => 'Pending Sellers', 'value' => $stats['pending_sellers'], 'icon' => 'store', 'tone' => 'plum'],
            ['key' => 'buyer', 'label' => 'Pending Buyers', 'value' => $stats['pending_buyers'], 'icon' => 'user', 'tone' => 'gold'],
            ['key' => 'logistics_center', 'label' => 'Pending Logistics', 'value' => $stats['pending_logistics_centers'], 'icon' => 'package', 'tone' => 'terracotta'],
        ];
    @endphp

    <div class="rg-page mx-auto w-full max-w-[1280px] p-4 sm:p-6" x-data="{
        q: @js($initialSearch),
        date: @js($initialDate),
        type: @js($initialType),
        page: {{ (int) $registrations->currentPage() }},
        loading: false,
        failed: false,
        typeOpen: false,
        timer: null,
        ctrl: null,
        tableUrl: @js(route('admin.registrations.table')),
        baseUrl: @js(route('admin.registrations.index')),
        types: [
            { v: 'all', l: 'All users' },
            { v: 'seller', l: 'Sellers' },
            { v: 'buyer', l: 'Buyers' },
            { v: 'logistics_center', l: 'Logistics centers' },
        ],

        get typeLabel() {
            const t = this.types.find(t => t.v === this.type);
            return t ? t.l : 'All users';
        },
        get dateLabel() {
            if (!this.date) return 'All dates';
            const [y, m, d] = this.date.split('-').map(Number);
            return new Date(y, m - 1, d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        },
        get filtered() {
            return this.q.trim() !== '' || this.date !== '' || this.type !== 'all';
        },

        params() {
            const p = new URLSearchParams();
            if (this.q.trim()) p.set('search', this.q.trim());
            if (this.date) p.set('date', this.date);
            if (this.type !== 'all') p.set('user_type', this.type);
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
        clearAll() {
            this.q = '';
            this.date = '';
            this.type = 'all';
            this.apply();
        },
        pickDate() {
            const el = this.$refs.date;
            if (el.showPicker) el.showPicker(); else el.click();
        },
        pickType(value) {
            this.type = value;
            this.typeOpen = false;
            this.apply();
        },
        onTableClick(event) {
            const btn = event.target.closest('[data-page]');
            if (!btn || btn.disabled) return;
            this.page = Number(btn.dataset.page);
            this.load(true);
        },

        async load(scrollToTable = false) {
            // Newer request wins; abort the stale one.
            if (this.ctrl) this.ctrl.abort();
            const ctrl = new AbortController();
            this.ctrl = ctrl;
            this.loading = true;
            this.failed = false;

            const qs = this.params().toString();
            try {
                const res = await fetch(this.tableUrl + (qs ? '?' + qs : ''), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    signal: ctrl.signal,
                });
                // Session expired: the login page came back instead of the table.
                if (res.redirected) { window.location.href = res.url; return; }
                if (!res.ok) throw new Error('HTTP ' + res.status);

                const html = await res.text();
                if (ctrl.signal.aborted) return;

                const wrap = this.$refs.wrap;
                wrap.innerHTML = html;
                wrap.classList.remove('rg-fresh');
                void wrap.offsetWidth; // restart the row animation
                wrap.classList.add('rg-fresh');

                history.replaceState(null, '', this.baseUrl + (qs ? '?' + qs : ''));
                if (scrollToTable) wrap.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            } catch (e) {
                if (e.name !== 'AbortError') this.failed = true;
            } finally {
                if (this.ctrl === ctrl) this.loading = false;
            }
        },
    }" @keydown.escape.window="typeOpen = false">

        {{-- Header --}}
        <div class="mb-6">
            <h1 class="font-display text-2xl font-semibold text-[#2B1730]">Registrations</h1>
            <p class="mt-1 text-sm text-gray-500">Review and approve new buyer, seller, and logistics center applications.</p>
        </div>

        {{-- Pending counts. Each card also filters the table to that user type. --}}
        @include('admin.partials.filter-stat-cards', ['cards' => $cards])

        @if (session('success'))
            <div role="status" class="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-2.5 text-sm text-green-700">
                {{ session('success') }}
            </div>
        @endif

        {{-- Search + filters --}}
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

            <div class="relative w-full sm:max-w-md">
                <img src="{{ asset('assets/icons/registration/search-icon.svg') }}" alt=""
                    class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 opacity-60">
                <input type="text" x-model="q" @input="onSearch()" @keydown.escape="q = ''; onSearch()"
                    autocomplete="off" aria-label="Search registrations" placeholder="Search by name or email"
                    class="h-11 w-full rounded-full border border-[#ddd0e0] bg-white pl-10 pr-10 text-sm text-[#2B1730] placeholder:text-gray-400
                           transition duration-200 focus:border-[#3b1735] focus:outline-none focus:ring-2 focus:ring-[#3b1735]/20">
                <button type="button" x-show="q !== ''" x-cloak @click="q = ''; apply()" aria-label="Clear search"
                    x-transition:enter="transition duration-150" x-transition:enter-start="opacity-0 scale-75"
                    x-transition:enter-end="opacity-100 scale-100"
                    class="absolute right-2.5 top-1/2 flex h-6 w-6 -translate-y-1/2 items-center justify-center rounded-full text-gray-400 transition hover:bg-[#F1E9F1] hover:text-[#3b1735]">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18" /></svg>
                </button>
            </div>

            <div class="flex flex-wrap items-center gap-2.5">

                {{-- Date --}}
                <div class="relative inline-flex h-11 items-center rounded-full border border-[#ddd0e0] bg-white transition duration-200 hover:border-[#cdbbd2]">
                    <button type="button" @click="pickDate()" aria-label="Filter by date applied"
                        class="flex h-full items-center gap-2 rounded-full pl-4 text-sm text-[#2B1730] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40"
                        :class="date ? 'pr-2' : 'pr-4'">
                        <img src="{{ asset('assets/icons/registration/date-filter-icon.svg') }}" alt="" class="h-4 w-4">
                        <span x-text="dateLabel"></span>
                    </button>
                    <button type="button" x-show="date" x-cloak @click="date = ''; apply()" aria-label="Clear date filter"
                        class="mr-2 flex h-6 w-6 items-center justify-center rounded-full text-gray-400 transition hover:bg-[#F1E9F1] hover:text-[#3b1735]">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18" /></svg>
                    </button>
                    {{-- Native picker, anchored under the pill; the pill above is what people see. --}}
                    <input type="date" x-ref="date" x-model="date" @change="apply()" tabindex="-1" aria-hidden="true"
                        class="pointer-events-none absolute bottom-0 left-0 h-px w-px opacity-0">
                </div>

                {{-- User type --}}
                <div class="relative" @click.outside="typeOpen = false">
                    <button type="button" @click="typeOpen = !typeOpen" aria-haspopup="listbox" :aria-expanded="typeOpen"
                        class="inline-flex h-11 items-center gap-2 rounded-full border border-[#ddd0e0] bg-white pl-4 pr-3.5 text-sm text-[#2B1730]
                               transition duration-200 hover:border-[#cdbbd2] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40">
                        <span x-text="typeLabel"></span>
                        <img src="{{ asset('assets/icons/registration/down-arrow-icon.svg') }}" alt=""
                            class="h-3 w-3 transition-transform duration-200 ease-vendo" :class="typeOpen ? 'rotate-180' : ''">
                    </button>

                    <div x-show="typeOpen" x-cloak role="listbox" aria-label="User type"
                        x-transition:enter="transition duration-150 ease-out"
                        x-transition:enter-start="opacity-0 -translate-y-1 scale-95"
                        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                        x-transition:leave="transition duration-100 ease-in"
                        x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                        class="absolute right-0 z-30 mt-2 w-52 origin-top-right rounded-2xl border border-[#ece4ec] bg-white p-1.5
                               shadow-[0_18px_40px_-20px_rgba(43,23,48,0.4)]">
                        <template x-for="t in types" :key="t.v">
                            <button type="button" role="option" :aria-selected="type === t.v" @click="pickType(t.v)"
                                :class="type === t.v ? 'bg-[#F7F1F7] font-medium text-[#3b1735]' : 'text-gray-700 hover:bg-[#FBF8FB]'"
                                class="flex w-full items-center justify-between rounded-xl px-3 py-2 text-left text-sm transition-colors duration-150">
                                <span x-text="t.l"></span>
                                <svg x-show="type === t.v" class="h-4 w-4 text-[#3b1735]" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        {{-- Couldn't refresh the table --}}
        <div x-show="failed" x-cloak x-transition.opacity role="alert"
            class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-2.5 text-sm text-red-700">
            <span>Couldn't load registrations. Check your connection and try again.</span>
            <button type="button" @click="load()" class="font-semibold underline underline-offset-2 hover:text-red-800">Try again</button>
        </div>

        {{-- Table region --}}
        <div class="relative">
            <div x-show="loading" x-cloak x-transition.opacity class="rg-bar" role="progressbar" aria-label="Loading registrations"></div>
            <div id="registrations-table-wrap" x-ref="wrap" class="rg-table-wrap" :aria-busy="loading" @click="onTableClick($event)">
                @include('admin.registrations.partials.registrations-table')
            </div>
        </div>
    </div>

    @include('shared.live-revision', ['endpoint' => route('admin.live', 'accounts'), 'mode' => 'reload'])
</x-admin.layout>