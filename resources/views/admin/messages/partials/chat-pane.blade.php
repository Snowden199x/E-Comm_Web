{{--
    Messages + composer, used by direct conversations and complaint cases.

    $fetchUrl   GET -> { messages: [...], can_reply: bool }; for cases the page adds ?recipient=<user id>
    $sendUrl    POST (multipart); cases also send recipient_id
    $parties    (cases) collection of users the admin can write to; shows the person switcher
    $defaultWho (cases) user id selected first
    $attachments  true when the composer can attach a file (direct conversations only)
    $peer       name used as the title of attachment previews
--}}
@php
    $parties = $parties ?? collect();
    $attachments = $attachments ?? false;
    $isCase = $parties->isNotEmpty();
    $roleLabel = ['seller' => 'Seller', 'buyer' => 'Buyer', 'logistics_center' => 'Courier'];
@endphp

<div class="flex min-h-0 flex-1 flex-col" x-data="{
    fetchUrl: @js($fetchUrl),
    sendUrl: @js($sendUrl),
    isCase: @js($isCase),
    who: @js($defaultWho ?? null),
    people: @js($parties->mapWithKeys(fn ($p) => [$p->id => $p->name])),
    messages: [],
    loaded: false,
    failed: false,
    canReply: true,
    text: '',
    file: null,
    sending: false,
    error: '',
    fetching: false,
    seq: 0,

    init() {
        this.fetchMessages(true);
        this.poll = setInterval(() => {
            if (!this.$el.isConnected) { clearInterval(this.poll); return; }
            if (!document.hidden) this.fetchMessages(false);
        }, 2500);
    },

    get peerName() { return this.isCase ? (this.people[this.who] || '') : @js($peer ?? '') },

    pick(id) {
        if (this.who === id) return;
        this.who = id;
        this.messages = [];
        this.loaded = false;
        this.failed = false;
        this.error = '';
        this.fetchMessages(true);
    },

    async fetchMessages(scroll) {
        if (this.fetching && !scroll) return;
        const seq = ++this.seq;
        this.fetching = true;
        const box = this.$refs.scroll;
        const nearBottom = !box || (box.scrollHeight - box.scrollTop - box.clientHeight < 100);
        try {
            const res = await fetch(this.fetchUrl + (this.isCase ? '?recipient=' + this.who : ''), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            // The conversation was deleted (here or by someone else): go back to the inbox.
            if (res.status === 404 && !this.isCase) { window.location.assign(@js(route('admin.messages.index'))); return; }
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const data = await res.json();
            if (seq !== this.seq) return;
            const grew = data.messages.length !== this.messages.length;
            this.messages = data.messages;
            this.canReply = data.can_reply !== false;
            this.loaded = true;
            this.failed = false;
            if (scroll || (grew && nearBottom)) this.$nextTick(() => { this.$refs.scroll.scrollTop = this.$refs.scroll.scrollHeight; });
        } catch (e) {
            if (!this.loaded) this.failed = true; // background refreshes fail quietly
        } finally {
            if (seq === this.seq) this.fetching = false;
        }
    },

    attach(event) {
        const f = event.target.files[0];
        event.target.value = '';
        if (!f) return;
        if (f.size > 5 * 1024 * 1024) { this.error = 'The file is over 5 MB. Choose a smaller one.'; return; }
        this.error = '';
        this.file = f;
    },

    async send() {
        if (this.sending || (!this.text.trim() && !this.file)) return;
        this.sending = true;
        this.error = '';
        const fd = new FormData();
        fd.append('body', this.text.trim());
        if (this.file) fd.append('attachment', this.file);
        if (this.isCase) fd.append('recipient_id', this.who);
        try {
            const res = await fetch(this.sendUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: fd,
            });
            if (!res.ok) {
                let msg = 'Couldn\'t send. Check your connection, then try again.';
                if (res.status === 419) msg = 'Your session expired. Refresh the page, then send again.';
                else {
                    try {
                        const j = await res.json();
                        msg = j.errors ? Object.values(j.errors)[0][0] : (j.message || msg);
                    } catch (e) {}
                }
                this.error = msg;
                return;
            }
            this.text = '';
            this.file = null;
            await this.fetchMessages(true);
        } catch (e) {
            this.error = 'Couldn\'t send. Check your connection, then try again.';
        } finally {
            this.sending = false;
        }
    },
}">

    @if ($isCase)
        <div role="tablist" aria-label="Person" class="flex flex-none gap-1 overflow-x-auto border-b border-vd-line px-4 py-2 thin-scroll">
            @foreach ($parties as $person)
                <button type="button" role="tab" :aria-selected="who === {{ $person->id }}" @click="pick({{ $person->id }})"
                    :class="who === {{ $person->id }} ? 'bg-vd-lavender font-semibold text-vd-plum' : 'text-vd-ink hover:bg-vd-tint'"
                    class="inline-flex h-8 flex-shrink-0 items-center gap-1.5 rounded-lg px-3 text-[13px] transition-colors duration-200
                           focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-plum/40">
                    {{ $person->name }}
                    <span class="text-xs font-normal text-vd-muted">{{ $roleLabel[$person->role] ?? ucfirst($person->role) }}</span>
                </button>
            @endforeach
        </div>
    @endif

    <div x-ref="scroll" class="thin-scroll min-h-0 flex-1 space-y-3 overflow-y-auto p-4" role="log" aria-live="polite" aria-label="Messages">

        {{-- First load --}}
        <div x-show="!loaded && !failed" class="space-y-3" role="status">
            <span class="sr-only">Loading messages</span>
            <div class="vd-skeleton h-12 w-2/3 max-w-[320px] !rounded-2xl"></div>
            <div class="vd-skeleton ml-auto h-10 w-1/2 max-w-[260px] !rounded-2xl"></div>
            <div class="vd-skeleton h-14 w-3/5 max-w-[360px] !rounded-2xl"></div>
        </div>

        <div x-show="failed" x-cloak>
            <x-admin.empty-state size="sm" icon="alert-circle" tone="red" title="Couldn't load messages" text="Check your connection, then try again.">
                <button type="button" @click="fetchMessages(true)"
                    class="inline-flex h-9 items-center rounded-lg border border-vd-line-strong bg-white px-4 text-[13px] font-medium text-vd-plum transition-colors duration-200 hover:bg-vd-tint">
                    Try again
                </button>
            </x-admin.empty-state>
        </div>

        <div x-show="loaded && messages.length === 0" x-cloak>
            <x-admin.empty-state size="sm" icon="message" title="No messages yet" text="Write the first message below." />
        </div>

        <template x-for="message in messages" :key="message.id">
            <div class="flex items-end gap-2" :class="message.is_mine ? 'justify-end' : 'justify-start'">
                <span x-show="!message.is_mine" class="h-8 w-8 flex-shrink-0 overflow-hidden rounded-full bg-vd-lavender text-xs font-semibold text-vd-plum">
                    <img x-show="message.avatar" :src="message.avatar" alt="" class="h-8 w-8 object-cover">
                    <span x-show="!message.avatar" class="grid h-8 w-8 place-items-center" x-text="message.initial"></span>
                </span>

                <div class="max-w-[80%] rounded-2xl px-3.5 py-2 sm:max-w-[70%]"
                    :class="message.is_mine ? 'rounded-br-md bg-vd-plum text-white' : 'rounded-bl-md border border-vd-line bg-vd-tint text-vd-ink'">
                    <p class="whitespace-pre-wrap text-sm [overflow-wrap:anywhere]" x-show="message.body" x-text="message.body"></p>

                    <template x-for="a in message.attachments" :key="a.url">
                        <div class="mt-1.5">
                            <template x-if="a.kind === 'image'">
                                <button type="button" @click="$dispatch('open-document', { url: a.url, title: a.name, subtitle: peerName, filename: a.name, kind: 'image' })"
                                    class="block overflow-hidden rounded-lg border border-white/20" :aria-label="'View ' + a.name">
                                    <img :src="a.url" :alt="a.name" loading="lazy" class="max-h-48 max-w-full object-cover">
                                </button>
                            </template>
                            <template x-if="a.kind === 'pdf'">
                                <button type="button"
                                    @click="$dispatch('open-document', { url: a.url, title: a.name, subtitle: peerName, filename: a.name, kind: 'pdf' })"
                                    class="inline-flex max-w-full items-center gap-2 rounded-lg border px-2.5 py-1.5 text-xs"
                                    :class="message.is_mine ? 'border-white/30 text-white hover:bg-white/10' : 'border-vd-line-strong bg-white text-vd-plum hover:bg-vd-tint'">
                                    <x-admin.icon name="file" class="h-4 w-4 flex-shrink-0" />
                                    <span class="truncate" x-text="a.name"></span>
                                </button>
                            </template>
                            <template x-if="a.kind === 'file'">
                                <a :href="a.url" :download="a.name"
                                    class="inline-flex max-w-full items-center gap-2 rounded-lg border px-2.5 py-1.5 text-xs"
                                    :class="message.is_mine ? 'border-white/30 text-white hover:bg-white/10' : 'border-vd-line-strong bg-white text-vd-plum hover:bg-vd-tint'">
                                    <x-admin.icon name="file" class="h-4 w-4 flex-shrink-0" />
                                    <span class="truncate" x-text="a.name"></span>
                                </a>
                            </template>
                        </div>
                    </template>

                    <p class="mt-1 text-[11px]" :class="message.is_mine ? 'text-white/70' : 'text-vd-muted'" x-text="message.created_at"></p>
                </div>
            </div>
        </template>
    </div>

    {{-- Composer --}}
    <div x-show="canReply" class="flex-none border-t border-vd-line p-3">
        <p x-show="error" x-cloak x-text="error" role="alert" class="mb-2 flex items-center gap-1.5 text-xs text-vd-danger-ink"></p>

        <div x-show="file" x-cloak class="mb-2 inline-flex max-w-full items-center gap-2 rounded-lg border border-vd-line-strong bg-vd-tint py-1 pl-2.5 pr-1 text-xs text-vd-ink">
            <x-admin.icon name="file" class="h-4 w-4 flex-shrink-0 text-vd-plum" />
            <span class="truncate" x-text="file && file.name"></span>
            <button type="button" @click="file = null" aria-label="Remove attachment"
                class="flex h-6 w-6 items-center justify-center rounded-md text-vd-muted transition-colors duration-200 hover:bg-white hover:text-vd-plum">
                <x-admin.icon name="x" class="h-3.5 w-3.5" />
            </button>
        </div>

        <div class="flex items-center gap-2">
            @if ($attachments)
                <label class="flex h-10 w-10 flex-shrink-0 cursor-pointer items-center justify-center rounded-full border border-vd-orchid bg-white text-vd-plum transition-colors duration-200 hover:border-vd-plum hover:bg-vd-tint
                              focus-within:ring-2 focus-within:ring-vd-plum/40" title="Attach a file (up to 5 MB)">
                    <x-admin.icon name="paperclip" class="h-4 w-4" />
                    <span class="sr-only">Attach a file</span>
                    <input type="file" class="sr-only" @change="attach($event)">
                </label>
            @endif
            <input type="text" x-model="text" @keydown.enter.prevent="send()" maxlength="2000" autocomplete="off" aria-label="Message"
                :placeholder="isCase ? 'Write to ' + peerName : 'Write a message'"
                class="h-10 min-w-0 flex-1 rounded-full border border-vd-orchid bg-white px-4 text-sm text-vd-ink placeholder:text-vd-muted transition-colors duration-200
                       hover:border-vd-plum focus:border-vd-plum focus:outline-none focus:ring-2 focus:ring-vd-plum/20">
            <button type="button" @click="send()" :disabled="sending || (!text.trim() && !file)"
                class="inline-flex h-10 flex-shrink-0 items-center rounded-full bg-vd-plum px-5 text-sm font-semibold text-white transition-colors duration-200 hover:bg-vd-plum-hover
                       disabled:cursor-not-allowed disabled:opacity-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-plum/40">
                <span x-text="sending ? 'Sending…' : 'Send'">Send</span>
            </button>
        </div>
    </div>

    <div x-show="!canReply" x-cloak class="flex flex-none items-center gap-2 border-t border-vd-line bg-vd-tint px-4 py-3 text-[13px] text-vd-muted">
        <x-admin.icon name="lock" class="h-4 w-4 flex-shrink-0" />
        This case is resolved. You can read the messages but can't send new ones.
    </div>
</div>
