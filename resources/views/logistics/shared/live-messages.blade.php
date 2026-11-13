{{--
    Messages, shared by the Company, Province Hub and Station Hub portals (spec section 8A). One conversation is always
    between the Company Admin and one facility. Fed by PortalMessageController::index. Posts go to ::store, ::reply and ::status.
    (The preview-only views company/messages and shared/messages are not used by the live portal.)
    Look: the seller pastel lilac system. Styles: resources/css/logistics/messages-theme.css (pushed last, tokens copied from
    seller/delivered-orders.css, the dialog and panel carry their own copy). No page script: the compose dialog and the
    conversation panel run in the Alpine scope below.
    Status colors: Open = sky, Resolved = mint, Urgent = rose, topic = lilac.
    Status chips and the search are server-side (this list is paged): links and a GET form.
    One click on a row opens the conversation in a side panel (bottom sheet on phones).
    Loading: shell skeleton when paging or filtering, spinner on the send buttons. Errors: top banner, and the dialog or
    panel reopens with what was typed (the hidden _dialog and _thread fields say which one).
--}}
@php
    $isCompany = $role === 'company';
    $home = route('lgp.'.$role.'.messages');
    $meId = (int) auth()->id();
    $topics = ['Parcel', 'Trip', 'Rider', 'Driver', 'Account', 'Other'];

    // What the panel shows for each conversation on this page.
    $items = $threads->getCollection()->mapWithKeys(fn ($t) => [(string) $t->id => [
        'id' => $t->id,
        'subject' => $t->subject,
        'with' => $isCompany ? ($t->facility?->name ?? 'Facility') : 'Company Admin',
        'topic' => $t->topic,
        'urgent' => $t->priority === 'urgent',
        'open' => $t->status === 'open',
        'updated' => $t->updated_at?->format('M j, g:i A'),
        'replyUrl' => route('lgp.'.$role.'.messages.reply', $t),
        'statusUrl' => route('lgp.'.$role.'.messages.status', $t),
        'messages' => $t->messages->sortBy('id')->values()->map(fn ($m) => [
            'who' => $m->sender?->name ?? 'Former user',
            'mine' => (int) $m->sender_id === $meId,
            'at' => $m->created_at?->format('M j, Y g:i A'),
            'body' => $m->body,
        ])->all(),
    ]])->all();

    $reopen = (int) (session('open_thread') ?: old('_thread', 0));
    $chips = [
        'all' => ['All', (int) $statusCounts->sum()],
        'open' => ['Open', (int) ($statusCounts['open'] ?? 0)],
        'resolved' => ['Resolved', (int) ($statusCounts['resolved'] ?? 0)],
    ];
    $filtered = $status || $term !== '';
@endphp

@push('lg-head')
    @vite('resources/css/logistics/messages-theme.css')
@endpush

<x-logistics.shell title="Messages" :role="$role">
    <div class="lgi"
         x-data="{
            items: @js($items),
            dialog: null,
            threadId: null,
            compose: { facility: @js((string) old('facility_id', '')), topic: @js((string) old('topic', 'Parcel')), priority: @js((string) old('priority', 'normal')), subject: @js((string) old('subject', '')), body: @js((string) old('body', '')) },
            reply: @js((string) old('_thread', '') !== '' ? (string) old('body', '') : ''),
            init() {
                // After a save the page reloads: open the same dialog or conversation again.
                if (@js((string) old('_dialog', '')) === 'compose') this.dialog = 'compose';
                else if (@js($reopen) && this.items[@js($reopen)]) { this.threadId = String(@js($reopen)); this.dialog = 'thread'; this.toEnd(); }
                this.$watch('dialog', (open) => {
                    const main = document.querySelector('.vd-main');
                    if (main) main.style.overflow = open ? 'hidden' : '';
                });
            },
            get thread() { return this.items[this.threadId] || null; },
            openThread(id) { this.threadId = String(id); this.reply = ''; this.dialog = 'thread'; this.toEnd(); },
            openCompose() { this.dialog = 'compose'; },
            close() { this.dialog = null; },
            toEnd() { this.$nextTick(() => { const box = this.$refs.thread; if (box) box.scrollTop = box.scrollHeight; }); }
         }"
         @keydown.escape.window="close()">

        <x-logistics.flash />

        <header class="lgi-head">
            <div>
                <h1>Messages</h1>
                <p>Conversations between your {{ $isCompany ? 'company and facilities' : 'facility and Company Admin' }}. Use one conversation per issue.</p>
            </div>
            <button type="button" class="lgi-btn lgi-btn--primary" @click="openCompose()"><x-logistics.icon name="plus" :size="16" /> New message</button>
        </header>

        <div class="lgi-chips" role="group" aria-label="Filter conversations by status">
            @foreach ($chips as $key => [$text, $count])
                @php $value = $key === 'all' ? null : $key; @endphp
                <a href="{{ route('lgp.'.$role.'.messages', array_filter(['status' => $value, 'q' => $term])) }}" class="lgi-chip {{ ($status ?? null) === $value ? 'is-active' : '' }}" @if (($status ?? null) === $value) aria-current="true" @endif>
                    {{ $text }} <span class="lgi-chip__count">{{ $count }}</span>
                </a>
            @endforeach
            @if ($filtered)
                <a href="{{ $home }}" class="lgi-chip lgi-chip--clear"><x-logistics.icon name="x" :size="14" /> Clear filters</a>
            @endif
        </div>

        <form method="GET" action="{{ $home }}" class="lgi-toolbar" role="search">
            @if ($status) <input type="hidden" name="status" value="{{ $status }}"> @endif
            <label class="lgi-search">
                <span class="lgi-sr">Search conversations</span>
                <x-logistics.icon name="search" :size="18" />
                <input type="search" name="q" value="{{ $term }}" maxlength="100" autocomplete="off" placeholder="{{ $isCompany ? 'Search subject or facility, then press Enter' : 'Search subject, then press Enter' }}">
            </label>
            <span class="lgi-meta">{{ number_format($threads->total()) }} {{ $threads->total() === 1 ? 'conversation' : 'conversations' }}</span>
        </form>

        <section class="lgi-card lgi-table-card" aria-label="Conversations">
            @if ($threads->count())
                <div class="lgi-table-wrap">
                    <table class="lgi-table">
                        <thead><tr><th scope="col">Conversation</th><th scope="col">{{ $isCompany ? 'Facility' : 'With' }}</th><th scope="col">Topic</th><th scope="col">Status</th><th scope="col">Updated</th><th scope="col"><span class="lgi-sr">Action</span></th></tr></thead>
                        <tbody>
                            @foreach ($threads as $thread)
                                @php $row = $items[(string) $thread->id]; $last = collect($row['messages'])->last(); @endphp
                                <tr class="lgi-row" @click="openThread({{ $thread->id }})">
                                    <td>
                                        <strong>{{ $thread->subject }}</strong>
                                        @if ($last)<small class="lgi-clamp">{{ $last['who'] }}: {{ \Illuminate\Support\Str::limit($last['body'], 90) }}</small>@endif
                                    </td>
                                    <td data-label="{{ $isCompany ? 'Facility' : 'With' }}">{{ $row['with'] }}</td>
                                    <td data-label="Topic">
                                        <span class="lgi-pill lgi-pill--lilac">{{ $thread->topic }}</span>
                                        @if ($row['urgent'])<span class="lgi-pill lgi-pill--rose">Urgent</span>@endif
                                    </td>
                                    <td data-label="Status"><span class="lgi-pill lgi-pill--{{ $row['open'] ? 'sky' : 'mint' }}">{{ $row['open'] ? 'Open' : 'Resolved' }}</span></td>
                                    <td data-label="Updated">{{ $thread->updated_at?->diffForHumans() }}</td>
                                    <td class="lgi-actions">
                                        <button type="button" class="lgi-btn lgi-btn--soft lgi-btn--small" @click.stop="openThread({{ $thread->id }})" aria-label="Open conversation {{ $thread->subject }}">
                                            Open conversation <x-logistics.icon name="arrow-right" :size="14" class="lgi-nudge" />
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($threads->hasPages())
                    <nav class="lgi-pager" aria-label="Pages">
                        <span>Page {{ $threads->currentPage() }} of {{ $threads->lastPage() }}</span>
                        <div>
                            @if ($threads->onFirstPage()) <span class="lgi-btn lgi-btn--soft lgi-btn--small is-disabled" aria-disabled="true">Previous</span>
                            @else <a href="{{ $threads->previousPageUrl() }}" class="lgi-btn lgi-btn--soft lgi-btn--small">Previous</a> @endif
                            @if ($threads->hasMorePages()) <a href="{{ $threads->nextPageUrl() }}" class="lgi-btn lgi-btn--soft lgi-btn--small">Next</a>
                            @else <span class="lgi-btn lgi-btn--soft lgi-btn--small is-disabled" aria-disabled="true">Next</span> @endif
                        </div>
                    </nav>
                @endif
            @elseif ($filtered)
                <div class="lgi-empty" role="status">
                    <span class="lgi-empty__icon lgi-tone-lilac"><x-logistics.icon name="search" :size="26" /></span>
                    <strong>No conversations match your filters</strong>
                    <p>Check the spelling, or pick another status.</p>
                    <a href="{{ $home }}" class="lgi-btn lgi-btn--primary">Clear filters</a>
                </div>
            @else
                <div class="lgi-empty" role="status">
                    <span class="lgi-empty__icon lgi-tone-lilac"><x-logistics.icon name="message" :size="26" /></span>
                    <strong>No conversations yet</strong>
                    <p>Send a message to reach {{ $isCompany ? 'one of your facilities' : 'your Company Admin' }}. Every reply stays on record.</p>
                    <button type="button" class="lgi-btn lgi-btn--primary" @click="openCompose()"><x-logistics.icon name="plus" :size="16" /> New message</button>
                </div>
            @endif
        </section>

        {{-- New message --}}
        <div class="lgi-layer" x-show="dialog === 'compose'" x-cloak>
            <div class="lgi-backdrop" @click="close()"></div>
            <div class="lgi-dialog" role="dialog" aria-modal="true" aria-labelledby="composeTitle">
                <header class="lgi-dialog__head">
                    <div>
                        <h2 id="composeTitle">New message</h2>
                        <p>{{ $isCompany ? 'Pick the facility this is about. Use one conversation per issue.' : 'To: '.($facility->company?->business_name ?? 'Your company').' Company Admin' }}</p>
                    </div>
                    <button type="button" class="lgi-close" @click="close()" aria-label="Close"><svg class="lgi-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg></button>
                </header>
                @if ($isCompany && $facilities->isEmpty())
                    <div class="lgi-dialog__body">
                        <div class="lgi-empty lgi-empty--compact" role="status">
                            <span class="lgi-empty__icon lgi-tone-lilac"><x-logistics.icon name="building" :size="26" /></span>
                            <strong>No facility can receive messages</strong>
                            <p>A facility needs an active manager account first. Create a Province Hub, then message its manager.</p>
                            <a href="{{ route('lgp.company.hubs') }}" class="lgi-btn lgi-btn--primary">Go to Province Hubs</a>
                        </div>
                    </div>
                @else
                    <form method="POST" action="{{ route('lgp.'.$role.'.messages.store') }}" class="lgi-dialog__body lgi-form">
                        @csrf
                        <input type="hidden" name="_dialog" value="compose">
                        @if ($isCompany)
                            <div class="lgi-field">
                                <label for="messageFacility">Facility <span aria-hidden="true">*</span></label>
                                <select id="messageFacility" name="facility_id" x-model="compose.facility" required>
                                    <option value="">Choose a facility</option>
                                    @foreach ($facilities as $option)
                                        <option value="{{ $option->id }}">{{ $option->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="lgi-form__row">
                            <div class="lgi-field">
                                <label for="messageTopic">Topic <span aria-hidden="true">*</span></label>
                                <select id="messageTopic" name="topic" x-model="compose.topic" required>
                                    @foreach ($topics as $topic)<option value="{{ $topic }}">{{ $topic }}</option>@endforeach
                                </select>
                            </div>
                            <div class="lgi-field">
                                <label for="messagePriority">Priority</label>
                                <select id="messagePriority" name="priority" x-model="compose.priority">
                                    <option value="normal">Normal</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>
                        </div>
                        <div class="lgi-field">
                            <label for="messageSubject">Subject <span aria-hidden="true">*</span></label>
                            <input id="messageSubject" name="subject" x-model="compose.subject" minlength="5" maxlength="160" placeholder="What is this about?" autocomplete="off" required>
                        </div>
                        <div class="lgi-field">
                            <label for="messageBody">Message <span aria-hidden="true">*</span></label>
                            <textarea id="messageBody" name="body" rows="5" x-model="compose.body" minlength="10" maxlength="4000" placeholder="Give the details the other side needs to act" required></textarea>
                        </div>
                        <footer class="lgi-dialog__foot">
                            <span class="lgi-spacer"></span>
                            <button type="button" class="lgi-btn lgi-btn--soft" @click="close()">Cancel</button>
                            <button type="submit" class="lgi-btn lgi-btn--primary">Send message</button>
                        </footer>
                    </form>
                @endif
            </div>
        </div>

        {{-- Conversation panel --}}
        <div class="lgi-layer lgi-layer--side" x-show="dialog === 'thread'" x-cloak>
            <div class="lgi-backdrop" @click="close()"></div>
            <aside class="lgi-panel" role="dialog" aria-modal="true" aria-labelledby="threadTitle">
                <template x-if="thread">
                    <div class="lgi-panel__inner">
                        <header class="lgi-panel__head">
                            <div>
                                <h2 id="threadTitle" x-text="thread.subject"></h2>
                                <p x-text="thread.with + ' · ' + thread.topic + (thread.urgent ? ' · Urgent' : '')"></p>
                            </div>
                            <span class="lgi-pill" :class="thread.open ? 'lgi-pill--sky' : 'lgi-pill--mint'" x-text="thread.open ? 'Open' : 'Resolved'"></span>
                            <button type="button" class="lgi-close" @click="close()" aria-label="Close"><svg class="lgi-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg></button>
                        </header>

                        <div class="lgi-thread" x-ref="thread">
                            <template x-for="(m, i) in thread.messages" :key="i">
                                <article class="lgi-msg" :class="{ 'is-mine': m.mine }">
                                    <div class="lgi-msg__meta"><strong x-text="m.mine ? 'You' : m.who"></strong><span x-text="m.at"></span></div>
                                    <p x-text="m.body"></p>
                                </article>
                            </template>
                        </div>

                        <footer class="lgi-panel__foot">
                            <form method="POST" :action="thread.replyUrl" class="lgi-reply" x-show="thread.open">
                                @csrf
                                <input type="hidden" name="_thread" :value="thread.id">
                                <label for="replyBody" class="lgi-sr">Reply</label>
                                <textarea id="replyBody" name="body" rows="3" maxlength="4000" x-model="reply" placeholder="Write your reply" required></textarea>
                                <button type="submit" class="lgi-btn lgi-btn--primary">Send reply</button>
                            </form>
                            <p class="lgi-hint" x-show="!thread.open">This conversation is resolved. Reopen it to reply.</p>
                            <form method="POST" :action="thread.statusUrl" class="lgi-status">
                                @csrf @method('PATCH')
                                <input type="hidden" name="_thread" :value="thread.id">
                                <input type="hidden" name="status" :value="thread.open ? 'resolved' : 'open'">
                                <button type="submit" class="lgi-btn lgi-btn--soft" x-text="thread.open ? 'Mark resolved' : 'Reopen conversation'"></button>
                            </form>
                        </footer>
                    </div>
                </template>
            </aside>
        </div>
    </div>
</x-logistics.shell>
