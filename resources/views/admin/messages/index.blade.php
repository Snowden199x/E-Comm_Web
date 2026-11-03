<x-admin.layout title="Messages">
    {{-- Shared list motion + loading styles (rg- prefix): progress bar, dimmed list, row settle. --}}
    @vite('resources/css/admin/registrations.css')

    <div class="rg-page mx-auto flex w-full max-w-[1440px] flex-col p-4 sm:p-6 lg:h-[calc(100dvh-72px)] lg:p-7" x-data="{
        // Filters. The tab is a filter too, but the toolbar's Clear filters button leaves it alone.
        q: @js($filters['search']),
        date: @js($filters['date']),
        status: @js($filters['status']),
        tab: @js($filters['tab']),
        counts: @js($counts),
        loading: false,
        failed: false,
        timer: null,
        seq: 0,
        listUrl: @js(route('admin.messages.list')),
        baseUrl: @js(route('admin.messages.index')),

        // Right pane: empty | loading | ready | failed
        activeKey: @js($activeKey),
        pane: @js($activeKey ? 'loading' : 'empty'),
        paneUrl: @js($activeUrl),
        paneSeq: 0,

        init() {
            if (this.activeKey && this.paneUrl) this.openPane(this.paneUrl);
            this.poll = setInterval(() => {
                if (!this.$el.isConnected) { clearInterval(this.poll); return; }
                if (!document.hidden && !this.loading) this.load(true);
            }, 5000);
        },

        get filtered() {
            return this.q.trim() !== '' || this.date !== '' || this.status !== 'all';
        },

        params() {
            const p = new URLSearchParams();
            if (this.q.trim()) p.set('search', this.q.trim());
            if (this.date) p.set('date', this.date);
            if (this.status !== 'all') p.set('status', this.status);
            if (this.tab !== 'all') p.set('tab', this.tab);
            return p;
        },

        onSearch() {
            clearTimeout(this.timer);
            this.timer = setTimeout(() => this.load(), 300);
        },
        apply() {
            clearTimeout(this.timer);
            this.load();
        },
        setTab(tab) {
            this.tab = tab;
            this.apply();
        },
        clearAll() {
            this.q = '';
            this.date = '';
            this.status = 'all';
            this.tab = 'all';
            this.apply();
        },

        // silent = background refresh: no dimming, no error banner, URL untouched.
        async load(silent = false) {
            const seq = ++this.seq;
            if (!silent) { this.loading = true; this.failed = false; }
            const qs = this.params().toString();
            try {
                const res = await fetch(this.listUrl + (qs ? '?' + qs : ''), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
                });
                // Session expired: the login page came back instead of the list.
                if (res.redirected && !res.url.startsWith(this.listUrl)) { window.location.href = res.url; return; }
                if (!res.ok) throw new Error('HTTP ' + res.status);

                const html = await res.text();
                if (seq !== this.seq) return; // a newer request owns the list
                const counts = res.headers.get('X-Counts');
                if (counts) this.counts = JSON.parse(counts);

                const list = this.$refs.list;
                list.innerHTML = html;
                if (!silent) {
                    list.classList.remove('rg-fresh');
                    void list.offsetWidth; // restart the row animation
                    list.classList.add('rg-fresh');
                    history.replaceState(null, '', this.baseUrl + (qs ? '?' + qs : ''));
                }
            } catch (e) {
                if (!silent && seq === this.seq) this.failed = true;
            } finally {
                if (!silent && seq === this.seq) this.loading = false;
            }
        },

        open(key, url) {
            if (this.activeKey === key && this.pane === 'ready') return;
            this.activeKey = key;
            this.openPane(url);
        },
        async openPane(url) {
            const seq = ++this.paneSeq;
            this.paneUrl = url;
            this.pane = 'loading';
            try {
                const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' } });
                if (res.redirected && !res.url.startsWith(url)) { window.location.href = res.url; return; }
                if (!res.ok) throw new Error('HTTP ' + res.status);
                const html = await res.text();
                if (seq !== this.paneSeq) return;
                this.$refs.pane.innerHTML = html;
                this.pane = 'ready';
                this.load(true); // opening marks messages read, so refresh the unread badge
            } catch (e) {
                if (seq === this.paneSeq) this.pane = 'failed';
            }
        },
        back() {
            this.paneSeq++;
            this.activeKey = null;
            this.pane = 'empty';
            this.$refs.pane.innerHTML = '';
        },
    }">

        <div class="mb-5 flex-none">
            <h1 class="font-display text-xl font-semibold leading-tight text-vd-ink sm:text-2xl">Messages</h1>
            <p class="mt-1 text-sm text-vd-muted">Communicate with buyers, sellers, and couriers regarding platform concerns.</p>
        </div>

        {{-- Search, date and status (the shared pill toolbar; the dropdown filters by status here) --}}
        @include('admin.partials.filter-toolbar', [
            'searchLabel' => 'Search messages',
            'searchPlaceholder' => 'Search name, email or message ID…',
            'dropdownModel' => 'status',
            'dropdownAll' => 'All status',
            'dropdownLabel' => 'Filter by status',
            'dropdownOptions' => [
                'all' => ['All status', 'layout-grid'],
                'unread' => ['Unread', 'message'],
                'open' => ['Open', 'clock'],
                'closed' => ['Closed', 'check-circle'],
            ],
        ])

        {{-- Type tabs --}}
        <div role="tablist" aria-label="Message type" class="mb-5 flex flex-none gap-1 overflow-x-auto rounded-xl border border-vd-line bg-white p-1.5 thin-scroll">
            @foreach ([['all', 'All'], ['complaints', 'Complaints'], ['direct', 'Direct Messages']] as [$value, $label])
                <button type="button" role="tab" :aria-selected="tab === '{{ $value }}'" @click="setTab('{{ $value }}')"
                    :class="tab === '{{ $value }}' ? 'bg-vd-lavender font-semibold text-vd-plum' : 'text-vd-ink hover:bg-vd-tint'"
                    class="inline-flex h-9 flex-shrink-0 items-center gap-2 rounded-lg px-4 text-sm transition-colors duration-200
                           focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-plum/40">
                    {{ $label }}
                    <span class="text-xs font-normal text-vd-muted" x-text="counts['{{ $value }}']">{{ $counts[$value] }}</span>
                </button>
            @endforeach
        </div>

        {{-- The list could not refresh --}}
        <div x-show="failed" x-cloak role="alert"
            class="mb-5 flex flex-none flex-wrap items-center justify-between gap-3 rounded-xl border border-vd-danger-line bg-vd-danger-bg p-4 text-sm text-vd-danger-ink">
            <span class="flex items-center gap-3">
                <x-admin.icon name="alert-circle" class="h-[18px] w-[18px] flex-shrink-0" />
                Couldn't load messages. Check your connection, then try again.
            </span>
            <button type="button" @click="load()"
                class="inline-flex h-9 items-center rounded-lg border border-vd-danger-line bg-white px-3 text-[13px] font-semibold text-vd-danger-ink transition-colors duration-200 hover:border-vd-danger-ink">
                Try again
            </button>
        </div>

        <div class="grid min-h-0 flex-1 grid-cols-1 gap-5 lg:grid-cols-[minmax(340px,400px)_minmax(0,1fr)] lg:grid-rows-[minmax(0,1fr)]">

            {{-- List --}}
            <section aria-label="Messages" :class="activeKey ? 'hidden lg:flex' : 'flex'"
                class="relative h-[calc(100dvh-380px)] min-h-[360px] flex-col overflow-hidden rounded-xl border border-vd-line bg-white lg:h-auto lg:min-h-0">
                <div x-show="loading" x-cloak class="rg-bar" role="progressbar" aria-label="Loading messages"></div>
                <div class="grid flex-none grid-cols-[minmax(0,1fr)_88px_minmax(0,0.9fr)] gap-3 border-b border-vd-line bg-vd-tint px-4 py-2.5 text-[13px] font-medium text-vd-muted">
                    <span>Message ID</span><span>Type</span><span>Participant</span>
                </div>
                <div x-ref="list" aria-live="polite" :aria-busy="loading" class="rg-table-wrap thin-scroll min-h-0 flex-1 overflow-y-auto">
                    @include('admin.messages.partials.conversation-list')
                </div>
            </section>

            {{-- Conversation --}}
            <section aria-label="Conversation" :class="activeKey ? 'flex' : 'hidden lg:flex'"
                class="h-[calc(100dvh-180px)] min-h-[420px] flex-col overflow-hidden rounded-xl border border-vd-line bg-white lg:h-auto lg:min-h-0">

                <div x-show="pane === 'empty'" class="flex flex-1 flex-col items-center justify-center gap-3 p-6 text-center">
                    <x-admin.icon name="message" class="h-16 w-16 text-vd-plum-soft" stroke="1.4" />
                    <p class="text-sm font-medium text-vd-muted">Select a message to view</p>
                </div>

                <div x-show="pane === 'loading'" x-cloak role="status" class="flex flex-1 flex-col">
                    <span class="sr-only">Loading conversation</span>
                    <div class="flex items-center gap-3 border-b border-vd-line p-4">
                        <div class="vd-skeleton h-10 w-10 !rounded-full"></div>
                        <div class="space-y-2"><div class="vd-skeleton h-3.5 w-36"></div><div class="vd-skeleton h-3 w-24"></div></div>
                    </div>
                    <div class="flex flex-1 flex-col justify-end gap-3 p-5">
                        <div class="vd-skeleton h-12 w-2/3 max-w-[360px] !rounded-2xl"></div>
                        <div class="vd-skeleton ml-auto h-10 w-1/2 max-w-[300px] !rounded-2xl"></div>
                        <div class="vd-skeleton h-16 w-3/5 max-w-[420px] !rounded-2xl"></div>
                    </div>
                    <div class="border-t border-vd-line p-3"><div class="vd-skeleton h-10 w-full !rounded-full"></div></div>
                </div>

                <div x-show="pane === 'failed'" x-cloak class="flex flex-1 items-center justify-center">
                    <x-admin.empty-state icon="alert-circle" tone="red" title="Couldn't open this conversation" text="Check your connection, then try again.">
                        <button type="button" @click="openPane(paneUrl)"
                            class="inline-flex h-9 items-center rounded-lg border border-vd-line-strong bg-white px-4 text-[13px] font-medium text-vd-plum transition-colors duration-200 hover:bg-vd-tint">
                            Try again
                        </button>
                    </x-admin.empty-state>
                </div>

                <div x-ref="pane" x-show="pane === 'ready'" x-cloak class="flex min-h-0 flex-1 flex-col"></div>
            </section>
        </div>
    </div>
</x-admin.layout>
