<x-admin.layout title="User management">
    {{-- Shared Registrations/User Management motion + loading styles (rg- prefix). --}}
    @vite('resources/css/admin/registrations.css')

    @php
        $initialSearch = request()->string('search')->trim()->toString();
        $initialDate = request()->string('date')->toString();
        $initialType = request()->string('user_type', 'all')->toString();
        $initialType = in_array($initialType, ['all', 'seller', 'buyer', 'logistics_center'], true) ? $initialType : 'all';

        // One Status filter. "rejected" sends rejected=1; active / suspended / deactivated send account_status.
        $initialStatus = $showRejected ? 'rejected' : request()->string('account_status', 'all')->toString();
        $initialStatus = in_array($initialStatus, ['all', 'active', 'suspended', 'deactivated', 'rejected'], true) ? $initialStatus : 'all';
        $initialDeletion = (bool) ($showDeletionRequests ?? false);

        $cards = [
            ['key' => 'all', 'label' => 'Total Users', 'value' => $stats['total_users'], 'icon' => 'users'],
            ['key' => 'seller', 'label' => 'Sellers', 'value' => $stats['sellers'], 'icon' => 'store'],
            ['key' => 'buyer', 'label' => 'Buyers', 'value' => $stats['buyers'], 'icon' => 'user'],
            ['key' => 'logistics_center', 'label' => 'Courier', 'value' => $stats['logistics_centers'], 'icon' => 'truck'],
        ];

    @endphp

    <div class="rg-page mx-auto w-full max-w-[1440px] p-4 sm:p-6 lg:p-7" x-data="{
        // Dialogs (profile + actions). Their markup lives inside #um-region.
        openId: null, suspendId: null, deactivateId: null, activateId: null, deletionId: null,

        // Filters
        q: @js($initialSearch),
        date: @js($initialDate),
        type: @js($initialType),
        status: @js($initialStatus),
        deletion: @js($initialDeletion),
        page: {{ (int) $users->currentPage() }},
        loading: false,
        failed: false,
        timer: null,
        ctrl: null,
        pageUrl: @js(route('admin.user-management.index')),

        get filtered() {
            return this.q.trim() !== '' || this.date !== '' || this.type !== 'all' || this.status !== 'all' || this.deletion;
        },

        params() {
            const p = new URLSearchParams();
            if (this.q.trim()) p.set('search', this.q.trim());
            if (this.date) p.set('date', this.date);
            if (this.type !== 'all') p.set('user_type', this.type);
            if (this.status === 'rejected') p.set('rejected', '1');
            else if (this.status !== 'all') p.set('account_status', this.status);
            if (this.deletion) p.set('deletion', '1');
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
            this.deletion = false;
            this.apply();
        },
        onRegionClick(event) {
            const btn = event.target.closest('[data-page]');
            if (!btn || btn.disabled) return;
            this.page = Number(btn.dataset.page);
            this.load(true);
        },

        // Fetches the same page and swaps only #um-region, so the table and the
        // per-user dialogs always describe the same rows (no full page reload).
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

                this.openId = this.suspendId = this.deactivateId = this.activateId = this.deletionId = null;
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
    }">

        <div class="mb-5">
            <h1 class="font-display text-xl font-semibold leading-tight text-vd-ink sm:text-2xl">User Management</h1>
            <p class="mt-1 text-sm text-vd-muted">Manage and control user accounts here.</p>
        </div>

        <x-admin.flash class="mb-5" />

        {{-- Counts. Selecting a card also filters the table to that user type. --}}
        @include('admin.partials.filter-stat-cards', ['cards' => $cards])

        {{-- Search, date, user type, rejected users and pending deletion requests --}}
        @include('admin.partials.filter-toolbar', [
            'searchLabel' => 'Search users',
            'rejectedToggle' => true,
            'deletionToggle' => true,
            'pendingDeletionCount' => $pendingDeletions,
        ])

        {{-- The table could not refresh --}}
        <div x-show="failed" x-cloak role="alert"
            class="mb-5 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-vd-danger-line bg-vd-danger-bg p-4 text-sm text-vd-danger-ink">
            <span class="flex items-center gap-3">
                <x-admin.icon name="alert-circle" class="h-[18px] w-[18px] flex-shrink-0" />
                Couldn't load users. Check your connection, then try again.
            </span>
            <button type="button" @click="load()"
                class="inline-flex h-9 items-center rounded-lg border border-vd-danger-line bg-white px-3 text-[13px] font-semibold text-vd-danger-ink transition-colors duration-200 hover:border-vd-danger-ink">
                Try again
            </button>
        </div>

        {{-- Table + per-user dialogs (swapped together on every filter or page change) --}}
        <div class="relative">
            <div x-show="loading" x-cloak class="rg-bar" role="progressbar" aria-label="Loading users"></div>
            <div id="um-region" x-ref="region" class="rg-table-wrap" aria-live="polite" :aria-busy="loading" @click="onRegionClick($event)">
                @include('admin.user-management.partials.users-table')

                @foreach ($users as $u)
                    @include('admin.user-management.partials.profile-modal', ['user' => $u])
                    @include('admin.user-management.partials.suspend-modal', ['user' => $u])
                    @include('admin.user-management.partials.deactivate-modal', ['user' => $u])
                    @include('admin.user-management.partials.activate-modal', ['user' => $u])
                    @include('admin.user-management.partials.deletion-request-modal', ['user' => $u])
                @endforeach
            </div>
        </div>
    </div>

    @include('shared.live-revision', ['endpoint' => route('admin.live', 'accounts'), 'mode' => 'reload'])
</x-admin.layout>
