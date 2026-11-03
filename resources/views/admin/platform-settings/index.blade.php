<x-admin.layout title="Platform settings">
    @vite('resources/css/admin/registrations.css')

    @php
        $focus = 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-plum/40';
        $secondaryBtn = "inline-flex h-8 items-center rounded-lg border border-vd-line-strong bg-white px-3 text-[13px] font-medium text-vd-plum transition-colors duration-200 hover:bg-vd-tint {$focus}";

        // Cards double as the status filter, so the toolbar has no status dropdown.
        $cards = [
            ['key' => 'all', 'label' => 'All announcements', 'value' => $stats['all'], 'icon' => 'megaphone'],
            ['key' => 'published', 'label' => 'Published', 'value' => $stats['published'], 'icon' => 'check-circle'],
            ['key' => 'scheduled', 'label' => 'Scheduled', 'value' => $stats['scheduled'], 'icon' => 'clock'],
            ['key' => 'draft', 'label' => 'Drafts', 'value' => $stats['draft'], 'icon' => 'file-text'],
        ];

        $policyIcons = ['Seller Policy' => 'store', 'Buyer Policy' => 'user', 'Logistics Policy' => 'truck', 'Prohibited Item Policy' => 'ban'];
        $policiesByName = $policies->keyBy('name');

        // A save that failed on the server: reopen that form with what was typed, errors under the fields.
        $form = (string) old('_form');
        $reopenAnnouncement = $errors->any() && str_starts_with($form, 'announcement:');
        $reopenId = $reopenAnnouncement ? substr($form, strlen('announcement:')) : '';
        $oldAnnouncement = [
            'title' => (string) old('title', ''),
            'message' => (string) old('message', ''),
            'audience' => (string) old('audience', 'All Users'),
            'status' => (string) old('status', 'draft'),
            'scheduled_at' => (string) old('scheduled_at', ''),
        ];
        $serverErrors = $reopenAnnouncement ? collect($errors->getMessages())->map(fn ($messages) => $messages[0])->all() : [];

        $greetingValue = $form === 'greeting' ? (string) old('welcome_message', $greeting) : $greeting;
        $greetingError = $form === 'greeting' ? $errors->first('welcome_message') : '';

        // Errors that show under their own field are left out of the banner.
        $inlineErrorKeys = match (true) {
            $reopenAnnouncement => ['title', 'message', 'audience', 'status', 'scheduled_at'],
            str_starts_with($form, 'policy:') => ['version', 'content'],
            $form === 'greeting' => ['welcome_message'],
            default => [],
        };
    @endphp

    <div class="rg-page mx-auto w-full max-w-[1440px] p-4 sm:p-6 lg:p-7" x-data="{
        q: @js($search),
        date: @js($date),
        type: @js($status),
        page: {{ (int) $announcements->currentPage() }},
        loading: false,
        failed: false,
        timer: null,
        ctrl: null,
        tableUrl: @js(route('admin.platform-settings.announcements-table')),
        baseUrl: @js(route('admin.platform-settings.index')),
        storeUrl: @js(route('admin.platform-settings.announcements.store')),
        updateUrl: @js(route('admin.platform-settings.announcements.update', ['announcement' => '__ID__'])),
        deleteUrl: @js(route('admin.platform-settings.announcements.destroy', ['announcement' => '__ID__'])),

        dlg: { open: false, mode: 'create', id: null, confirming: false, action: @js(route('admin.platform-settings.announcements.store')), deleteUrl: '', saved: null },
        f: { title: '', message: '', audience: 'All Users', status: 'draft', scheduled_at: '' },
        server: @js((object) $serverErrors),
        tried: false,

        get filtered() { return this.q.trim() !== '' || this.date !== '' || this.type !== 'all'; },

        params() {
            const p = new URLSearchParams();
            if (this.q.trim()) p.set('search', this.q.trim());
            if (this.date) p.set('date', this.date);
            if (this.type !== 'all') p.set('status', this.type);
            if (this.page > 1) p.set('page', this.page);
            return p;
        },
        onSearch() { clearTimeout(this.timer); this.page = 1; this.timer = setTimeout(() => this.load(), 300); },
        apply() { clearTimeout(this.timer); this.page = 1; this.load(); },
        clearAll() { this.q = ''; this.date = ''; this.type = 'all'; this.apply(); },
        onTableClick(event) {
            const btn = event.target.closest('[data-page]');
            if (!btn || btn.disabled) return;
            this.page = Number(btn.dataset.page);
            this.load(true);
        },

        async load(scrollToTable = false) {
            // The newest request wins; abort the stale one.
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

        // Announcement dialog
        nowLocal() {
            const now = new Date();
            return new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
        },
        openCreate() {
            this.f = { title: '', message: '', audience: 'All Users', status: 'draft', scheduled_at: '' };
            this.dlg = { open: true, mode: 'create', id: null, confirming: false, action: this.storeUrl, deleteUrl: '', saved: null };
            this.server = {};
            this.tried = false;
            setTimeout(() => this.$refs.title?.focus(), 60);
        },
        openEdit(row) {
            this.f = { title: row.title, message: row.message, audience: row.audience, status: row.status, scheduled_at: row.scheduled_at };
            this.dlg = { open: true, mode: 'edit', id: row.id, confirming: false, action: row.updateUrl, deleteUrl: row.deleteUrl, saved: JSON.stringify(this.f) };
            this.server = {};
            this.tried = false;
            setTimeout(() => this.$refs.title?.focus(), 60);
        },
        reopen(old, id) {
            this.f = old;
            const editing = id !== '' && id !== 'new';
            this.dlg = {
                open: true, mode: editing ? 'edit' : 'create', id: editing ? id : null, confirming: false,
                action: editing ? this.updateUrl.replace('__ID__', id) : this.storeUrl,
                deleteUrl: editing ? this.deleteUrl.replace('__ID__', id) : '', saved: null,
            };
            this.tried = false;
        },
        closeAnnouncement() { this.dlg.open = false; },

        get errors() {
            const e = {};
            if (this.f.title.trim() === '') e.title = 'Add a title.';
            if (this.f.message.trim() === '') e.message = 'Write the message people will read.';
            if (this.f.status === 'scheduled') {
                if (this.f.scheduled_at === '') e.scheduled_at = 'Pick the date and time to publish.';
                else if (new Date(this.f.scheduled_at) <= new Date()) e.scheduled_at = 'Pick a date and time in the future, or choose Published to go live now.';
            }
            return e;
        },
        fieldError(name) { return (this.tried && this.errors[name]) || this.server[name] || ''; },
        get dirty() { return this.dlg.mode === 'create' || this.dlg.saved === null || JSON.stringify(this.f) !== this.dlg.saved; },
        get submitLabel() {
            const labels = { draft: 'Save draft', scheduled: 'Schedule announcement', published: 'Publish announcement' };
            const unchanged = this.dlg.mode === 'edit' && this.dlg.saved !== null && this.f.status === JSON.parse(this.dlg.saved).status;
            return unchanged ? 'Save changes' : labels[this.f.status];
        },
        check(event) {
            this.tried = true;
            const first = Object.keys(this.errors)[0];
            if (first) { event.preventDefault(); this.$refs[first]?.focus(); }
        },

        init() {
            if (@js($reopenAnnouncement)) this.reopen(@js($oldAnnouncement), @js($reopenId));
        },
    }">

        {{-- Heading and the page's main action --}}
        <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="font-display text-xl font-semibold leading-tight text-vd-ink sm:text-2xl">Platform settings</h1>
                <p class="mt-1 text-sm text-vd-muted">Announcements, policies and the support chat greeting.</p>
            </div>
            <button type="button" @click="openCreate()"
                class="inline-flex h-10 items-center gap-2 rounded-xl bg-vd-plum px-4 text-[13px] font-semibold text-white transition-colors duration-200 hover:bg-vd-plum-hover {{ $focus }}">
                <x-admin.icon name="plus" class="h-4 w-4" />
                Create announcement
            </button>
        </div>

        <x-admin.flash class="mb-5" :except="$inlineErrorKeys" />

        <div class="grid grid-cols-1 items-start gap-5 xl:grid-cols-3">

            {{-- Announcements --}}
            <div class="min-w-0 xl:col-span-2">
                @include('admin.partials.filter-stat-cards', ['cards' => $cards])

                @include('admin.partials.filter-toolbar', [
                    'searchLabel' => 'Search announcements',
                    'searchPlaceholder' => 'Search title or message',
                    'noDropdown' => true,
                ])

                {{-- The list could not refresh --}}
                <div x-show="failed" x-cloak role="alert"
                    class="mb-5 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-vd-danger-line bg-vd-danger-bg p-4 text-sm text-vd-danger-ink">
                    <span class="flex items-center gap-3">
                        <x-admin.icon name="alert-circle" class="h-[18px] w-[18px] flex-shrink-0" />
                        Couldn't load announcements. Check your connection, then try again.
                    </span>
                    <button type="button" @click="load()"
                        class="inline-flex h-9 items-center rounded-lg border border-vd-danger-line bg-white px-3 text-[13px] font-semibold text-vd-danger-ink transition-colors duration-200 hover:border-vd-danger-ink">
                        Try again
                    </button>
                </div>

                {{-- Table: dimmed with a progress bar while a request is in flight --}}
                <div class="relative">
                    <div x-show="loading" x-cloak class="rg-bar" role="progressbar" aria-label="Loading announcements"></div>
                    <div x-ref="wrap" class="rg-table-wrap" aria-live="polite" :aria-busy="loading" @click="onTableClick($event)">
                        @include('admin.platform-settings.partials.announcements-table')
                    </div>
                </div>
            </div>

            <div class="min-w-0 space-y-5">

                {{-- Policies: the four types, always listed. A missing type has an Add button. --}}
                <section class="rounded-xl border border-vd-line bg-white p-5" aria-labelledby="policies-title">
                    <h2 id="policies-title" class="font-display text-base font-semibold text-vd-ink">Policies</h2>
                    <p class="mt-0.5 text-[13px] text-vd-muted">Users read these in their account.</p>

                    <ul class="mt-3 divide-y divide-vd-line">
                        @foreach ($policyTypes as $type)
                            @php
                                $policy = $policiesByName->get($type);
                                $plain = $policy ? trim(str_replace("\u{00A0}", ' ', html_entity_decode(strip_tags($policy->content ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8'))) : '';
                            @endphp
                            <li class="flex items-center gap-3 py-3">
                                <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl border border-vd-line bg-vd-tint text-vd-plum">
                                    <x-admin.icon :name="$policyIcons[$type]" class="h-5 w-5" />
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium text-vd-ink">{{ $type }}</p>
                                    @if (! $policy)
                                        <p class="text-xs text-vd-muted">Not added</p>
                                    @elseif ($plain === '')
                                        <p class="text-xs font-medium text-vd-warning-ink">Empty. Users can't see it.</p>
                                    @else
                                        <p class="truncate text-xs text-vd-muted">Version {{ $policy->version }} · Updated {{ $policy->updated_at->format('M j, Y') }}</p>
                                    @endif
                                </div>

                                @if ($policy)
                                    <button type="button" @click="$dispatch('open-policy', {{ $policy->id }})" aria-label="Edit {{ $type }}" class="{{ $secondaryBtn }}">Edit</button>
                                @else
                                    <form method="POST" action="{{ route('admin.platform-settings.policies.store') }}">
                                        @csrf
                                        <input type="hidden" name="name" value="{{ $type }}">
                                        <button type="submit" aria-label="Add {{ $type }}" class="{{ $secondaryBtn }}">Add</button>
                                    </form>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </section>

                {{-- Chat greeting --}}
                <section class="rounded-xl border border-vd-line bg-white p-5" aria-labelledby="greeting-title"
                    x-data="{
                        saved: @js($greeting),
                        text: @js($greetingValue),
                        tried: false,
                        serverError: @js($greetingError),
                        get error() {
                            if (this.text.trim() === '') return 'Write a greeting for new chats.';
                            if (this.text.length > 1000) return 'Shorten the greeting to 1,000 characters or fewer.';
                            return this.serverError;
                        },
                        get dirty() { return this.text.trim() !== this.saved.trim(); },
                    }">
                    <h2 id="greeting-title" class="font-display text-base font-semibold text-vd-ink">Chat greeting</h2>
                    <p class="mt-0.5 text-[13px] text-vd-muted">Sent when someone starts a support chat.</p>

                    <form method="POST" action="{{ route('admin.platform-settings.chat-welcome.update') }}" novalidate
                        @submit="tried = true; if (error) $event.preventDefault()"
                        data-draft-key="admin-{{ auth('admin')->id() }}-welcome-message" class="mt-3">
                        @csrf
                        <input type="hidden" name="_form" value="greeting">

                        <label for="greeting-text" class="sr-only">Greeting</label>
                        <textarea id="greeting-text" name="welcome_message" rows="4" maxlength="1000" x-model="text" @input="serverError = ''"
                            :aria-invalid="(tried || serverError !== '') && !!error"
                            :class="(tried || serverError !== '') && error ? 'border-vd-danger-ink' : 'border-vd-line-strong'"
                            class="w-full resize-none rounded-xl border bg-white p-3 text-sm text-vd-ink placeholder:text-vd-muted focus:border-vd-plum focus:outline-none focus:ring-2 focus:ring-vd-plum/20"></textarea>

                        <div class="mt-1.5 flex items-start justify-between gap-3">
                            <p x-show="(tried || serverError !== '') && error" x-text="error" class="text-xs font-medium text-vd-danger-ink"></p>
                            <span class="ml-auto text-xs tabular-nums text-vd-muted" x-text="text.length + ' / 1000'">0 / 1000</span>
                        </div>

                        <button type="submit" :disabled="!dirty"
                            class="mt-3 h-10 rounded-xl bg-vd-plum px-4 text-[13px] font-semibold text-white transition-colors duration-200 hover:bg-vd-plum-hover disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:bg-vd-plum {{ $focus }}">
                            Save greeting
                        </button>
                    </form>
                </section>
            </div>
        </div>

        @include('admin.platform-settings.partials.announcement-dialog')

        @foreach ($policies as $policy)
            @include('admin.platform-settings.partials.policy-dialog', ['policy' => $policy, 'audience' => $policyAudience[$policy->name] ?? 'Users'])
        @endforeach
    </div>
</x-admin.layout>
