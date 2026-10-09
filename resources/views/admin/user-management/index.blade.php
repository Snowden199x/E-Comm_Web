<x-admin.layout title="User Management">
    {{-- Shared Registrations/User Management motion + loading styles (rg- prefix). --}}
    @vite('resources/css/admin/registrations.css')

    @php
        $initialSearch = request()->string('search')->trim()->toString();
        $initialDate = request()->string('date')->toString();
        $initialType = request()->string('user_type', 'all')->toString();
        $initialType = in_array($initialType, ['all', 'seller', 'buyer', 'logistics_center'], true) ? $initialType : 'all';

        // One Status filter replaces the old "Rejected users" toggle. "rejected" still sends the existing
        // `rejected=1` parameter; active / suspended / deactivated send `account_status` (see the backend notes).
        $initialStatus = $showRejected ? 'rejected' : request()->string('account_status', 'all')->toString();
        $initialStatus = in_array($initialStatus, ['all', 'active', 'suspended', 'deactivated', 'rejected'], true) ? $initialStatus : 'all';

        $cards = [
            ['key' => 'all', 'label' => 'Total Users', 'value' => $stats['total_users'], 'icon' => 'users', 'tone' => 'purple'],
            ['key' => 'seller', 'label' => 'Sellers', 'value' => $stats['sellers'], 'icon' => 'store', 'tone' => 'plum'],
            ['key' => 'buyer', 'label' => 'Buyers', 'value' => $stats['buyers'], 'icon' => 'user', 'tone' => 'gold'],
            ['key' => 'logistics_center', 'label' => 'Logistics Centers', 'value' => $stats['logistics_centers'], 'icon' => 'package', 'tone' => 'terracotta'],
        ];
    @endphp

    <div class="rg-page mx-auto w-full max-w-[1280px] p-4 sm:p-6" x-data="{
        // Modals (profile + actions). Their markup lives inside #um-region.
        openId: null, suspendId: null, deactivateId: null, activateId: null,

        // Filters
        q: @js($initialSearch),
        date: @js($initialDate),
        type: @js($initialType),
        status: @js($initialStatus),
        statusOpen: false,
        // Set to true once UserManagementController::filteredUsers() honors `account_status`
        // (docs/design/2026-10-07-admin-uiux-backend-needs.md). Until then the Status filter for
        // Active / Suspended / Deactivated can only hide rows on the page that is showing.
        serverStatusFilter: false,
        page: {{ (int) $users->currentPage() }},
        loading: false,
        failed: false,
        typeOpen: false,
        timer: null,
        ctrl: null,
        pageUrl: @js(route('admin.user-management.index')),
        types: [
            { v: 'all', l: 'All users' },
            { v: 'seller', l: 'Sellers' },
            { v: 'buyer', l: 'Buyers' },
            { v: 'logistics_center', l: 'Logistics centers' },
        ],
        statuses: [
            { v: 'all', l: 'All statuses', dot: 'bg-gray-300' },
            { v: 'active', l: 'Active', dot: 'bg-green-500' },
            { v: 'suspended', l: 'Suspended', dot: 'bg-red-500' },
            { v: 'deactivated', l: 'Deactivated', dot: 'bg-[#d9826b]' },
            { v: 'rejected', l: 'Rejected', dot: 'bg-orange-500' },
        ],

        get rejected() {
            return this.status === 'rejected';
        },
        get statusLabel() {
            const s = this.statuses.find(s => s.v === this.status);
            return s ? s.l : 'All statuses';
        },
        get statusDot() {
            const s = this.statuses.find(s => s.v === this.status);
            return s ? s.dot : 'bg-gray-300';
        },
        // True when the Status filter can only act on the rows already on screen.
        get statusPageOnly() {
            return !this.serverStatusFilter && ['active', 'suspended', 'deactivated'].includes(this.status);
        },
        // Row visibility. Once the server filters by status every row matches, so this is always true.
        statusMatch(state) {
            return !this.statusPageOnly || state === this.status;
        },

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
            return this.q.trim() !== '' || this.date !== '' || this.type !== 'all' || this.status !== 'all';
        },

        params() {
            const p = new URLSearchParams();
            if (this.q.trim()) p.set('search', this.q.trim());
            if (this.date) p.set('date', this.date);
            if (this.type !== 'all') p.set('user_type', this.type);
            if (this.status === 'rejected') p.set('rejected', '1');
            else if (this.status !== 'all') p.set('account_status', this.status);
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
            this.status = 'all';
            this.apply();
        },
        pickStatus(value) {
            this.status = value;
            this.statusOpen = false;
            this.apply();
        },
        pickDate() {
            const el = this.$refs.date;
            if (el.showPicker) el.showPicker(); else el.click();
        },
        pickType(value) {
            this.type = value;
            this.typeOpen = false;
            this.statusOpen = false;
            this.apply();
        },
        onRegionClick(event) {
            const btn = event.target.closest('[data-page]');
            if (!btn || btn.disabled) return;
            this.page = Number(btn.dataset.page);
            this.load(true);
        },

        // Fetches the same page and swaps only #um-region, so the table and the
        // per-user modals always describe the same rows (no full page reload).
        async load(scrollToTable = false) {
            if (this.ctrl) this.ctrl.abort();
            const ctrl = new AbortController();
            this.ctrl = ctrl;
            this.loading = true;
            this.failed = false;

            const qs = this.params().toString();
            const url = this.pageUrl + (qs ? '?' + qs : '');
            try {
                const res = await fetch(url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
                    signal: ctrl.signal,
                });
                if (res.redirected && res.url !== new URL(url, location.origin).href) { window.location.href = res.url; return; }
                if (!res.ok) throw new Error('HTTP ' + res.status);

                const doc = new DOMParser().parseFromString(await res.text(), 'text/html');
                const next = doc.getElementById('um-region');
                if (!next) throw new Error('Missing region');
                if (ctrl.signal.aborted) return;

                this.openId = this.suspendId = this.deactivateId = this.activateId = null;
                this.statusOpen = false;
                const region = this.$refs.region;
                region.innerHTML = next.innerHTML;
                region.classList.remove('rg-fresh');
                void region.offsetWidth; // restart the row animation
                region.classList.add('rg-fresh');

                history.replaceState(null, '', url);
                if (scrollToTable) region.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            } catch (e) {
                if (e.name !== 'AbortError') this.failed = true;
            } finally {
                if (this.ctrl === ctrl) this.loading = false;
            }
        },
    }" @keydown.escape.window="typeOpen = false; statusOpen = false">

        {{-- Header --}}
        <div class="mb-6">
            <h1 class="font-display text-2xl font-semibold text-[#2B1730]">User Management</h1>
            <p class="mt-1 text-sm text-gray-500">Manage and control buyer, seller, and logistics center accounts.</p>
        </div>

        {{-- Counts. Each card also filters the table to that user type. --}}
        @include('admin.partials.filter-stat-cards', ['cards' => $cards])

        {{-- Search + filters --}}
        <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">

            <div class="relative w-full lg:max-w-md">
                <img src="{{ asset('assets/icons/user-management/search-icon.svg') }}" alt=""
                    class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 opacity-60">
                <input type="text" x-model="q" @input="onSearch()" @keydown.escape="q = ''; onSearch()"
                    autocomplete="off" aria-label="Search users" placeholder="Search by name or email"
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
                    <button type="button" @click="pickDate()" aria-label="Filter by date joined"
                        class="flex h-full items-center gap-2 rounded-full pl-4 text-sm text-[#2B1730] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40"
                        :class="date ? 'pr-2' : 'pr-4'">
                        <img src="{{ asset('assets/icons/user-management/date-filter-icon.svg') }}" alt="" class="h-4 w-4">
                        <span x-text="dateLabel"></span>
                    </button>
                    <button type="button" x-show="date" x-cloak @click="date = ''; apply()" aria-label="Clear date filter"
                        class="mr-2 flex h-6 w-6 items-center justify-center rounded-full text-gray-400 transition hover:bg-[#F1E9F1] hover:text-[#3b1735]">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18" /></svg>
                    </button>
                    <input type="date" x-ref="date" x-model="date" @change="apply()" tabindex="-1" aria-hidden="true"
                        class="pointer-events-none absolute bottom-0 left-0 h-px w-px opacity-0">
                </div>

                {{-- User type --}}
                <div class="relative" @click.outside="typeOpen = false">
                    <button type="button" @click="typeOpen = !typeOpen" aria-haspopup="listbox" :aria-expanded="typeOpen"
                        class="inline-flex h-11 items-center gap-2 rounded-full border border-[#ddd0e0] bg-white pl-4 pr-3.5 text-sm text-[#2B1730]
                               transition duration-200 hover:border-[#cdbbd2] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40">
                        <span x-text="typeLabel"></span>
                        <img src="{{ asset('assets/icons/user-management/down-arrow-icon.svg') }}" alt=""
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

                {{-- Status --}}
                <div class="relative" @click.outside="statusOpen = false">
                    <button type="button" @click="statusOpen = !statusOpen; typeOpen = false" aria-haspopup="listbox" :aria-expanded="statusOpen"
                        :class="status !== 'all' ? 'border-[#3b1735] bg-[#F7F1F7]' : 'border-[#ddd0e0] bg-white hover:border-[#cdbbd2]'"
                        class="inline-flex h-11 items-center gap-2 rounded-full border pl-4 pr-3.5 text-sm text-[#2B1730] transition duration-200
                               focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40">
                        <span class="h-2.5 w-2.5 flex-shrink-0 rounded-full" :class="statusDot" aria-hidden="true"></span>
                        <span x-text="statusLabel">All statuses</span>
                        <x-admin.icon name="chevron-down" class="h-3.5 w-3.5 text-gray-500 transition-transform duration-200 ease-vendo" x-bind:class="statusOpen ? 'rotate-180' : ''" />
                    </button>

                    <div x-show="statusOpen" x-cloak role="listbox" aria-label="Account status"
                        x-transition:enter="transition duration-150 ease-out"
                        x-transition:enter-start="opacity-0 -translate-y-1 scale-95"
                        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                        x-transition:leave="transition duration-100 ease-in"
                        x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                        class="absolute right-0 z-30 mt-2 w-56 origin-top-right rounded-2xl border border-[#ece4ec] bg-white p-1.5
                               shadow-[0_18px_40px_-20px_rgba(43,23,48,0.4)]">
                        <template x-for="s in statuses" :key="s.v">
                            <button type="button" role="option" :aria-selected="status === s.v" @click="pickStatus(s.v)"
                                :class="status === s.v ? 'bg-[#F7F1F7] font-medium text-[#3b1735]' : 'text-gray-700 hover:bg-[#FBF8FB]'"
                                class="flex w-full items-center justify-between gap-3 rounded-xl px-3 py-2 text-left text-sm transition-colors duration-150">
                                <span class="flex items-center gap-2.5">
                                    <span class="h-2.5 w-2.5 rounded-full" :class="s.dot" aria-hidden="true"></span>
                                    <span x-text="s.l"></span>
                                </span>
                                <svg x-show="status === s.v" class="h-4 w-4 text-[#3b1735]" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                            </button>
                        </template>
                    </div>
                </div>

                <button type="button" x-show="filtered" x-cloak @click="clearAll()"
                    class="inline-flex h-11 items-center gap-1.5 rounded-full px-3.5 text-sm font-medium text-[#3b1735] transition duration-200 hover:bg-[#F1E9F1]
                           focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40">
                    <x-admin.icon name="x" class="h-4 w-4" /> Clear
                </button>
            </div>
        </div>

        <div x-show="failed" x-cloak x-transition.opacity role="alert"
            class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-2.5 text-sm text-red-700">
            <span>Couldn't load users. Check your connection and try again.</span>
            <button type="button" @click="load()" class="font-semibold underline underline-offset-2 hover:text-red-800">Try again</button>
        </div>

        {{-- Honest note while the server cannot filter by account status yet (see serverStatusFilter above). --}}
        <p x-show="statusPageOnly" x-cloak x-transition.opacity role="status"
            class="mb-3 flex items-start gap-2 rounded-xl border border-[#F3D9A6] bg-[#FDF3E2] px-3.5 py-2.5 text-[13px] text-[#8a5614]">
            <x-admin.icon name="info" class="mt-0.5 h-4 w-4 flex-shrink-0" />
            <span>The status filter currently hides rows on this page only. The page count and other pages are not filtered yet.</span>
        </p>

        {{-- Table + per-user modals (swapped together on every filter/page change) --}}
        <div class="relative">
            <div x-show="loading" x-cloak x-transition.opacity class="rg-bar" role="progressbar" aria-label="Loading users"></div>
            <div id="um-region" x-ref="region" class="rg-table-wrap" :aria-busy="loading" @click="onRegionClick($event)">
                @include('admin.user-management.partials.users-table')

                @foreach ($users as $u)
                    @include('admin.user-management.partials.profile-modal', ['user' => $u])
                    @include('admin.user-management.partials.suspend-modal', ['user' => $u])
                    @include('admin.user-management.partials.deactivate-modal', ['user' => $u])
                    @include('admin.user-management.partials.activate-modal', ['user' => $u])
                @endforeach
            </div>
        </div>

        @include('admin.user-management.partials.confirmation-modal')
    </div>

    @include('shared.live-revision', ['endpoint' => route('admin.live', 'accounts'), 'mode' => 'reload'])
</x-admin.layout>