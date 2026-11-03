<x-admin.layout title="Registrations">
    @vite('resources/css/admin/registrations.css')

    @php
        // Read filters defensively: query values can be arrays or junk.
        $initialSearch = request()->string('search')->trim()->toString();
        $initialDate = request()->string('date')->toString();
        $initialType = request()->string('user_type', 'all')->toString();
        $initialType = in_array($initialType, ['all', 'seller', 'buyer', 'logistics_center'], true) ? $initialType : 'all';

        $cards = [
            ['key' => 'all', 'label' => 'Pending Request', 'value' => $stats['pending_request'], 'img' => 'assets/icons/registration/pending-request-icon.svg'],
            ['key' => 'seller', 'label' => 'Pending Sellers', 'value' => $stats['pending_sellers'], 'img' => 'assets/icons/registration/pending-seller-icon.svg'],
            ['key' => 'buyer', 'label' => 'Pending Buyers', 'value' => $stats['pending_buyers'], 'img' => 'assets/icons/registration/pending-buyer-icon.svg'],
            ['key' => 'logistics_center', 'label' => 'Pending Logistics', 'value' => $stats['pending_logistics_centers'], 'img' => 'assets/icons/registration/pending-logistics-icon.svg'],
        ];

    @endphp

    <div class="rg-page mx-auto w-full max-w-[1440px] p-4 sm:p-6 lg:p-7" x-data="{
        q: @js($initialSearch),
        date: @js($initialDate),
        type: @js($initialType),
        page: {{ (int) $registrations->currentPage() }},
        loading: false,
        failed: false,
        timer: null,
        ctrl: null,
        tableUrl: @js(route('admin.registrations.table')),
        baseUrl: @js(route('admin.registrations.index')),

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
    }">

        <div class="mb-5">
            <h1 class="font-display text-xl font-semibold leading-tight text-vd-ink sm:text-2xl">Registration</h1>
            <p class="mt-1 text-sm text-vd-muted">Manage user registration here.</p>
        </div>

        <x-admin.flash class="mb-5" />

        {{-- Pending counts. Selecting a card also filters the table to that user type. --}}
        @include('admin.partials.filter-stat-cards', ['cards' => $cards])

        {{-- Search, date and user type --}}
        @include('admin.partials.filter-toolbar', ['searchLabel' => 'Search registrations'])

        {{-- The table could not refresh --}}
        <div x-show="failed" x-cloak role="alert"
            class="mb-5 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-vd-danger-line bg-vd-danger-bg p-4 text-sm text-vd-danger-ink">
            <span class="flex items-center gap-3">
                <x-admin.icon name="alert-circle" class="h-[18px] w-[18px] flex-shrink-0" />
                Couldn't load registrations. Check your connection, then try again.
            </span>
            <button type="button" @click="load()"
                class="inline-flex h-9 items-center rounded-lg border border-vd-danger-line bg-white px-3 text-[13px] font-semibold text-vd-danger-ink transition-colors duration-200 hover:border-vd-danger-ink">
                Try again
            </button>
        </div>

        {{-- Table: dimmed with a progress bar while a request is in flight --}}
        <div class="relative">
            <div x-show="loading" x-cloak class="rg-bar" role="progressbar" aria-label="Loading registrations"></div>
            <div id="registrations-table-wrap" x-ref="wrap" class="rg-table-wrap" aria-live="polite" :aria-busy="loading" @click="onTableClick($event)">
                @include('admin.registrations.partials.registrations-table')
            </div>
        </div>
    </div>

    @include('shared.live-revision', ['endpoint' => route('admin.live', 'accounts'), 'mode' => 'reload'])
</x-admin.layout>
