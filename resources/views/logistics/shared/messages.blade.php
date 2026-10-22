{{-- PREVIEW ONLY: hard-coded conversations from routes/logistics_preview_messages.php, nothing is sent or saved.
     Province Hub and Station Hub > Messages: the same email-style conversations as Company > Messages, read from the hub's side. A conversation is always
     between the Company Admin and one facility, so a hub only sees its own and writes only to the Company Admin. --}}
<x-logistics.shell title="Messages" :role="$role">
    <div class="lg-page"
         x-data="{
            threads: @js($threads),
            me: @js($me),
            facility: @js($facility),
            companyName: @js($companyName),
            filter: 'all',
            q: '',
            sel: null,
            reply: '', replyErr: '',
            compose: { open: false, topic: '', ref: '', priority: 'normal', subject: '', body: '', e: {} },
            filters: {
                all: t => true,
                unread: t => t.unread,
                open: t => t.status === 'open',
                resolved: t => t.status === 'resolved'
            },
            count(k) { return this.threads.filter(this.filters[k]).length; },
            last(t) { return t.messages[t.messages.length - 1]; },
            get shown() {
                const s = this.q.trim().toLowerCase();
                return this.threads
                    .filter(t => this.filters[this.filter](t) && (!s || (t.subject + ' ' + t.topic + ' ' + t.ref[1] + ' ' + t.messages.map(m => m.body).join(' ')).toLowerCase().includes(s)))
                    .sort((a, b) => this.last(b).at.localeCompare(this.last(a).at));
            },
            get cur() { return this.threads.find(t => t.id === this.sel) || null; },
            open(t) { this.sel = t.id; t.unread = false; this.reply = ''; this.replyErr = ''; },
            fmt(iso) {
                const d = new Date(iso), n = new Date();
                const sameDay = d.toDateString() === n.toDateString();
                return sameDay ? d.toLocaleTimeString('en-PH', { hour: 'numeric', minute: '2-digit' })
                               : d.toLocaleString('en-PH', { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' });
            },
            fmtShort(iso) {
                const d = new Date(iso);
                return d.toDateString() === new Date().toDateString() ? d.toLocaleTimeString('en-PH', { hour: 'numeric', minute: '2-digit' }) : d.toLocaleDateString('en-PH', { month: 'short', day: 'numeric' });
            },
            preview(t) { const m = this.last(t); return (m.from === 'hub' ? 'You: ' : '') + m.body.replace(/\s+/g, ' ').slice(0, 90); },
            sendReply() {
                const t = this.cur;
                if (this.reply.trim().length < 3) { this.replyErr = 'Write a message before sending.'; this.$refs.replyBox.focus(); return; }
                t.messages.push({ from: 'hub', name: this.me, at: new Date().toISOString().slice(0, 16), body: this.reply.trim() });
                this.reply = ''; this.replyErr = '';
                this.$dispatch('lg-toast', { type: 'success', message: 'Reply sent to the Company Admin (preview only, nothing is sent).' });
            },
            toggleStatus() {
                const t = this.cur;
                t.status = t.status === 'open' ? 'resolved' : 'open';
                this.$dispatch('lg-toast', { type: 'success', message: t.status === 'resolved' ? 'Conversation marked as resolved.' : 'Conversation reopened.' });
            },
            openCompose() {
                this.compose = { open: true, topic: '', ref: '', priority: 'normal', subject: '', body: '', e: {} };
                this.$nextTick(() => this.$refs.composeTopic.focus());
            },
            sendCompose() {
                const c = this.compose, e = {};
                if (!c.topic) e.topic = 'Choose a topic.';
                if (c.subject.trim().length < 5) e.subject = 'Write a short subject (5+ characters).';
                if (c.body.trim().length < 10) e.body = 'Explain the problem (10+ characters) so the Company Admin can act on it.';
                c.e = e;
                if (Object.keys(e).length) { this.$nextTick(() => { const el = this.$refs.composeForm.querySelector('[aria-invalid=true]'); if (el) el.focus(); }); return; }
                const id = Math.max(0, ...this.threads.map(t => t.id)) + 1;
                const ref = c.ref.trim();
                this.threads.push({
                    id, subject: c.subject.trim(), hub: this.facility, contact: this.me, topic: c.topic,
                    ref: ref ? [/^HT-/i.test(ref) ? 'trip' : 'parcel', ref.toUpperCase()] : ['none', ''], refUrl: null,
                    priority: c.priority, status: 'open', unread: false,
                    messages: [{ from: 'hub', name: this.me, at: new Date().toISOString().slice(0, 16), body: c.body.trim() }]
                });
                this.compose.open = false; this.filter = 'all'; this.q = ''; this.sel = id;
                this.$dispatch('lg-toast', { type: 'success', message: 'Message sent to ' + this.companyName + ', Company Admin (preview only, nothing is sent).' });
            }
         }">
        <div class="lg-page-head">
            <div>
                <h1>Messages</h1>
                <p>Read what the Company Admin writes to {{ $facility }} and write back, or report a problem yourself. Each problem gets its own conversation, so there is a written record.</p>
            </div>
            <div class="lg-page-head__actions">
                <button type="button" class="lg-btn" @click="openCompose()"><x-logistics.icon name="plus" :size="18" /> New message</button>
            </div>
        </div>

        <nav class="lg-tabs" aria-label="Conversation filters">
            @foreach (['all' => 'All', 'unread' => 'Unread', 'open' => 'Open', 'resolved' => 'Resolved'] as $k => $label)
                <button type="button" class="lg-tab" :aria-pressed="filter === '{{ $k }}'" @click="filter = '{{ $k }}'">
                    {{ $label }} <span class="lg-tab__count" x-text="count('{{ $k }}')"></span>
                </button>
            @endforeach
        </nav>

        <div class="lg-toolbar">
            <div class="lg-search">
                <x-logistics.icon name="search" :size="18" />
                <input type="search" class="lg-input" x-model="q" placeholder="Search subject, topic, parcel or trip" aria-label="Search messages">
            </div>
            <span class="lg-toolbar__meta" aria-live="polite" x-text="shown.length + (shown.length === 1 ? ' conversation' : ' conversations')"></span>
        </div>

        <div class="lg-grid" style="grid-template-columns:repeat(auto-fit,minmax(min(100%,380px),1fr));align-items:start">
            {{-- Inbox --}}
            <section class="lg-card" aria-label="Conversations">
                <ul style="margin:0;padding:0;list-style:none" x-show="shown.length">
                    <template x-for="t in shown" :key="t.id">
                        <li style="border-bottom:1px solid var(--lg-line)">
                            <button type="button" class="lg-btn lg-btn--ghost" :aria-current="sel === t.id ? 'true' : null"
                                    :style="'width:100%;height:auto;display:block;text-align:left;padding:14px 20px;border-radius:0;white-space:normal;' + (sel === t.id ? 'background:var(--lg-brand-soft)' : '')"
                                    @click="open(t)">
                                <span style="display:flex;justify-content:space-between;gap:12px;align-items:baseline">
                                    <span class="lg-cell-title" style="font-size:14px" :style="t.unread ? 'font-weight:700' : 'font-weight:500'" x-text="t.subject"></span>
                                    <span class="lg-cell-sub" style="white-space:nowrap" x-text="fmtShort(last(t).at)"></span>
                                </span>
                                <span class="lg-cell-sub" style="display:block;margin-top:4px" x-text="preview(t)"></span>
                                <span style="display:flex;flex-wrap:wrap;gap:6px;margin-top:8px">
                                    <span class="lg-pill lg-pill--blue" x-show="t.unread">Needs your reply</span>
                                    <span class="lg-pill lg-pill--red" x-show="t.priority === 'urgent'">Urgent</span>
                                    <span class="lg-pill lg-pill--green" x-show="t.status === 'resolved'">Resolved</span>
                                    <span class="lg-pill lg-pill--gray" x-text="t.topic"></span>
                                </span>
                            </button>
                        </li>
                    </template>
                </ul>
                <x-logistics.empty x-show="!shown.length && !threads.length" x-cloak icon="inbox" title="No conversations yet" text="When the Company Admin writes to {{ $facility }}, the conversation appears here. You can also write a new message.">
                    <button type="button" class="lg-btn lg-btn--outline lg-btn--sm" @click="openCompose()">New message</button>
                </x-logistics.empty>
                <x-logistics.empty x-show="!shown.length && threads.length" x-cloak icon="search" title="No conversations match" text="Clear the search, pick another filter, or write a new message.">
                    <button type="button" class="lg-btn lg-btn--outline lg-btn--sm" @click="q = ''; filter = 'all'">Clear filters</button>
                </x-logistics.empty>
            </section>

            {{-- Reading pane --}}
            <section class="lg-card" aria-label="Conversation" aria-live="polite">
                <x-logistics.empty x-show="!cur" icon="message" title="Pick a conversation" text="Choose one on the left to read it and reply, or write a new message to the Company Admin." />
                <template x-if="cur">
                    <div>
                        <div class="lg-card__head">
                            <div style="min-width:0">
                                <h2 class="lg-section-title" x-text="cur.subject"></h2>
                                <p class="lg-section-sub" x-text="'With ' + companyName + ', Company Admin'"></p>
                            </div>
                            <button type="button" class="lg-btn lg-btn--outline lg-btn--sm" @click="toggleStatus()">
                                <span x-show="cur.status === 'open'">Mark as resolved</span>
                                <span x-show="cur.status === 'resolved'">Reopen</span>
                            </button>
                        </div>
                        <div class="lg-card__body" style="display:flex;flex-wrap:wrap;gap:8px;padding-top:0">
                            <span class="lg-pill" :class="cur.status === 'resolved' ? 'lg-pill--green' : 'lg-pill--amber'" x-text="cur.status === 'resolved' ? 'Resolved' : 'Open'"></span>
                            <span class="lg-pill lg-pill--red" x-show="cur.priority === 'urgent'">Urgent</span>
                            <span class="lg-pill lg-pill--gray" x-text="cur.topic"></span>
                            <template x-if="cur.ref[0] !== 'none'">
                                <span class="lg-doc-links">
                                    <a class="lg-doc-link" x-show="cur.refUrl" :href="cur.refUrl" x-text="(cur.ref[0] === 'trip' ? 'Trip: ' : 'Parcel: ') + cur.ref[1]"></a>
                                    <span class="lg-pill lg-pill--violet" x-show="!cur.refUrl" x-text="(cur.ref[0] === 'trip' ? 'Trip: ' : 'Parcel: ') + cur.ref[1]"></span>
                                </span>
                            </template>
                        </div>

                        <div>
                            <template x-for="(m, i) in cur.messages" :key="i">
                                <article style="padding:16px 24px;border-top:1px solid var(--lg-line)">
                                    <header style="display:flex;flex-wrap:wrap;justify-content:space-between;gap:4px 12px;margin-bottom:8px">
                                        <div>
                                            <strong x-text="m.name"></strong>
                                            <span class="lg-cell-sub" style="display:block" x-text="m.from === 'company' ? 'Company Admin, to you' : cur.hub + ', to Company Admin'"></span>
                                        </div>
                                        <span class="lg-cell-sub" x-text="fmt(m.at)"></span>
                                    </header>
                                    <p style="white-space:pre-line;line-height:1.6" x-text="m.body"></p>
                                </article>
                            </template>
                        </div>

                        <div style="padding:16px 24px 24px;border-top:1px solid var(--lg-line)">
                            <form @submit.prevent="sendReply()" x-show="cur.status === 'open'" novalidate class="lg-field">
                                <label for="replyBox">Reply to the Company Admin</label>
                                <textarea id="replyBox" x-ref="replyBox" class="lg-textarea" rows="4" maxlength="2000" x-model="reply" placeholder="Write your reply" :aria-invalid="!!replyErr" aria-describedby="replyErr"></textarea>
                                <p class="lg-error" id="replyErr" x-show="replyErr" x-text="replyErr" role="alert"></p>
                                <div><button type="submit" class="lg-btn">Send reply</button></div>
                            </form>
                            <div class="lg-note" x-show="cur.status === 'resolved'">
                                <x-logistics.icon name="check-circle" :size="18" />
                                <span>This conversation is resolved. Reopen it if the problem comes back.</span>
                            </div>
                        </div>
                    </div>
                </template>
            </section>
        </div>

        {{-- New message: a hub writes only to the Company Admin --}}
        <div class="lg-overlay" x-show="compose.open" x-cloak @click.self="compose.open = false" @keydown.escape.window="compose.open = false">
            <div class="lg-modal" style="width:min(100%,600px)" role="dialog" aria-modal="true" aria-labelledby="composeTitle" x-show="compose.open">
                <div class="lg-modal__head">
                    <div>
                        <h3 id="composeTitle">New message</h3>
                        <p>One problem per message, so each one can be marked resolved on its own.</p>
                    </div>
                    <button type="button" class="lg-toast__close" @click="compose.open = false" aria-label="Close"><x-logistics.icon name="x" :size="18" /></button>
                </div>
                <form @submit.prevent="sendCompose()" x-ref="composeForm" novalidate>
                    <div class="lg-modal__body lg-modal__body--grid">
                        <div class="lg-field">
                            <label for="cmTo">To</label>
                            <input id="cmTo" class="lg-input" :value="companyName + ', Company Admin'" readonly>
                        </div>
                        <div class="lg-form-grid">
                            <div class="lg-field">
                                <label for="cmTopic">Topic <span class="lg-req" aria-hidden="true">*</span></label>
                                <select id="cmTopic" x-ref="composeTopic" class="lg-select" x-model="compose.topic" :aria-invalid="!!compose.e.topic" aria-describedby="cmTopicE">
                                    <option value="">Choose a topic</option>
                                    @foreach ($topics as $t)<option value="{{ $t }}">{{ $t }}</option>@endforeach
                                </select>
                                <p class="lg-error" id="cmTopicE" x-show="compose.e.topic" x-text="compose.e.topic" role="alert"></p>
                            </div>
                            <div class="lg-field">
                                <label for="cmPri">Priority</label>
                                <select id="cmPri" class="lg-select" x-model="compose.priority">
                                    <option value="normal">Normal</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>
                        </div>
                        <div class="lg-field">
                            <label for="cmRef">Parcel or trip number</label>
                            <input id="cmRef" class="lg-input" x-model="compose.ref" maxlength="30" placeholder="For example VND-10473 or HT-2042" aria-describedby="cmRefH">
                            <p class="lg-hint" id="cmRefH">Optional. It tells the Company Admin which parcel or trip this is about.</p>
                        </div>
                        <div class="lg-field">
                            <label for="cmSub">Subject <span class="lg-req" aria-hidden="true">*</span></label>
                            <input id="cmSub" class="lg-input" x-model="compose.subject" maxlength="120" :aria-invalid="!!compose.e.subject" aria-describedby="cmSubE">
                            <p class="lg-error" id="cmSubE" x-show="compose.e.subject" x-text="compose.e.subject" role="alert"></p>
                        </div>
                        <div class="lg-field">
                            <label for="cmBody">Message <span class="lg-req" aria-hidden="true">*</span></label>
                            <textarea id="cmBody" class="lg-textarea" rows="6" maxlength="2000" x-model="compose.body" placeholder="What happened, and what do you need from the Company Admin?" :aria-invalid="!!compose.e.body" aria-describedby="cmBodyE"></textarea>
                            <p class="lg-error" id="cmBodyE" x-show="compose.e.body" x-text="compose.e.body" role="alert"></p>
                        </div>
                    </div>
                    <div class="lg-modal__foot">
                        <button type="button" class="lg-btn lg-btn--outline" @click="compose.open = false">Cancel</button>
                        <button type="submit" class="lg-btn">Send message</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-logistics.shell>
