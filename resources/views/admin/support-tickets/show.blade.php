@use('App\Models\Support\SupportTicket')
<x-admin.layout title="Support ticket #{{ $ticket->id }}">
    @vite('resources/css/admin/support-ticket.css')

    @php
        $pillTone = ['open' => 'pending', 'in_review' => 'plum', 'resolved' => 'success'][$ticket->status] ?? 'neutral';
        $pillIcon = ['open' => 'inbox', 'in_review' => 'hourglass', 'resolved' => 'check-circle'][$ticket->status] ?? null;
        $isResolved = $ticket->status === 'resolved';
        $focus = 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-plum/40';
        $btn = "inline-flex h-10 items-center justify-center gap-2 rounded-[10px] px-4 text-sm font-semibold transition-colors duration-200 {$focus}";
        $btnPrimary = $btn.' bg-vd-plum text-white hover:bg-vd-plum-hover';
        $btnSecondary = $btn.' border border-vd-line-strong bg-vd-tint text-vd-plum hover:bg-vd-lavender';
        $btnSuccess = $btn.' border border-vd-success-line bg-vd-success-bg text-vd-success-ink hover:border-vd-success-ink';
        $replyErrors = ['body', 'attachments', 'attachments.0', 'attachments.1'];
        $fileError = $errors->first('attachments') ?: ($errors->first('attachments.0') ?: $errors->first('attachments.1'));

        $thread = $ticket->messages;
        $fileCount = $thread->sum(fn ($m) => $m->attachments->count());

        // Every file on the ticket, in order. The viewer steps through this list.
        $gallery = $thread->flatMap(fn ($m) => $m->attachments)->map(fn ($a) => [
            'id' => $a->id,
            'name' => $a->original_name,
            'size' => $a->sizeLabel(),
            'kind' => $a->isImage() ? 'image' : ($a->isPdf() ? 'pdf' : 'file'),
            'view' => route('admin.support-tickets.attachments.preview', $a),
            'download' => route('admin.support-tickets.attachments.show', $a),
        ])->values();

        $steps = ['open' => ['Open', 'inbox'], 'in_review' => ['In review', 'hourglass'], 'resolved' => ['Resolved', 'check-circle']];
        $stepIndex = (int) array_search($ticket->status, array_keys($steps), true);

        $dayLabel = function ($date) {
            if (! $date) return '';
            if ($date->isToday()) return 'Today';
            if ($date->isYesterday()) return 'Yesterday';
            return $date->format('M j, Y');
        };
        $lastDay = null;
        $downloadSvg = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 4v11"/><path d="m7 11 5 5 5-5"/><path d="M5 20h14"/></svg>';
    @endphp

    <div class="st-scope mx-auto w-full max-w-[1100px] p-4 sm:p-5 lg:p-6"
        x-data="{
            confirmResolve: false,
            gallery: @js($gallery),
            viewer: { open: false, id: null, loading: false, failed: false, zoom: false },
            copied: '',
            get current() { return this.gallery.find((f) => f.id === this.viewer.id) || null },
            get position() { return this.gallery.findIndex((f) => f.id === this.viewer.id) },
            openFile(id) { this.viewer = { open: true, id: id, loading: true, failed: false, zoom: false }; },
            closeViewer() { this.viewer.open = false; },
            step(n) {
                if (!this.viewer.open || this.gallery.length < 2) return;
                const next = (this.position + n + this.gallery.length) % this.gallery.length;
                this.viewer = { open: true, id: this.gallery[next].id, loading: true, failed: false, zoom: false };
            },
            copy(text, key) {
                const done = (ok) => { this.copied = ok ? key : 'fail'; setTimeout(() => this.copied = '', 2500); };
                if (!navigator.clipboard) return done(false);
                navigator.clipboard.writeText(text).then(() => done(true), () => done(false));
            },
        }"
        x-effect="document.body.classList.toggle('overflow-hidden', viewer.open)"
        @keydown.escape.window="closeViewer()"
        @keydown.arrow-left.window="step(-1)"
        @keydown.arrow-right.window="step(1)">

        <nav class="mb-4 flex items-center gap-2 text-[13px] text-vd-muted" aria-label="Breadcrumb">
            <a href="{{ route('admin.support-tickets.index') }}" x-target.push="main-content" data-skeleton="table" class="font-medium text-vd-plum hover:underline">Support tickets</a>
            <x-admin.icon name="chevron-right" class="h-3.5 w-3.5" /><span>#{{ $ticket->id }}</span>
        </nav>

        <header class="mb-6 flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0">
                <h1 class="font-display text-xl font-semibold leading-tight text-vd-ink [overflow-wrap:anywhere] sm:text-2xl">{{ $ticket->subject }}</h1>
                <div class="mt-3 flex flex-wrap items-center gap-2 text-xs text-vd-muted">
                    <span class="st-chip"><span class="st-tile st-tile--sm"><x-admin.icon name="tag" class="h-3.5 w-3.5" /></span>{{ $ticket->category_label }}</span>
                    @if ($ticket->order)
                        <span class="st-chip"><span class="st-tile st-tile--sm"><x-admin.icon name="package" class="h-3.5 w-3.5" /></span>Order {{ $ticket->order->number }}</span>
                    @endif
                    <span class="inline-flex items-center gap-1.5"><x-admin.icon name="clock" class="h-3.5 w-3.5" />Opened {{ $ticket->created_at?->format('M j, Y g:i A') }}</span>
                </div>
            </div>
            <x-admin.status-pill :tone="$pillTone" :icon="$pillIcon">{{ $ticket->status_label }}</x-admin.status-pill>
        </header>

        {{-- Reply and attachment errors show under their own fields. --}}
        <x-admin.flash class="mb-4" :except="$replyErrors" />

        <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
            <div class="min-w-0 space-y-6">
                {{-- Conversation: Vendo = solid plum bubble, Buyer = tint bubble with a border. --}}
                <section class="overflow-hidden rounded-[14px] border border-vd-line bg-white" aria-label="Conversation">
                    <div class="flex items-center justify-between gap-3 border-b border-vd-line px-5 py-3">
                        <h2 class="font-display text-sm font-semibold text-vd-ink">Conversation</h2>
                        <p class="text-xs text-vd-muted">
                            {{ $thread->count() }} {{ \Illuminate\Support\Str::plural('message', $thread->count()) }}@if ($fileCount) · {{ $fileCount }} {{ \Illuminate\Support\Str::plural('file', $fileCount) }}@endif
                        </p>
                    </div>

                    <div class="thin-scroll max-h-[560px] space-y-4 overflow-y-auto p-5" x-init="$el.scrollTop = $el.scrollHeight">
                        @foreach ($thread as $message)
                            @php $day = $message->created_at?->toDateString(); @endphp
                            @if ($day !== $lastDay)
                                <div class="st-day"><span>{{ $dayLabel($message->created_at) }}</span></div>
                                @php $lastDay = $day; @endphp
                            @endif

                            <article @class(['flex items-end gap-2', 'flex-row-reverse' => $message->is_staff])>
                                @if ($message->is_staff)
                                    <span class="st-staff-avatar" aria-hidden="true">V</span>
                                @else
                                    <x-admin.avatar :user="$message->sender" name="Buyer" size="h-8 w-8" text="text-xs" />
                                @endif

                                <div class="min-w-0 max-w-[85%]">
                                    <p @class(['mb-1 text-[11px] font-medium text-vd-muted', 'text-right' => $message->is_staff])>
                                        {{ $message->sender_label }} · {{ $message->created_at?->format('g:i A') }}
                                    </p>
                                    <div @class([
                                        'rounded-2xl px-4 py-2 text-[13px] leading-5',
                                        'rounded-br-md bg-vd-plum text-white' => $message->is_staff,
                                        'rounded-bl-md border border-vd-line-strong bg-vd-tint text-vd-ink' => ! $message->is_staff,
                                    ])>
                                        <div class="whitespace-pre-line [overflow-wrap:anywhere]">{{ $message->body }}</div>
                                    </div>

                                    @if ($message->attachments->isNotEmpty())
                                        <ul @class(['mt-2 flex flex-wrap gap-2', 'justify-end' => $message->is_staff])>
                                            @foreach ($message->attachments as $attachment)
                                                @php
                                                    $isImage = $attachment->isImage();
                                                    $isPdf = $attachment->isPdf();
                                                @endphp
                                                <li class="st-file">
                                                    <button type="button" class="st-file__open st-hover" @click="openFile({{ $attachment->id }})" aria-label="Preview {{ $attachment->original_name }}">
                                                        <span class="st-file__thumb">
                                                            @if ($isImage)
                                                                <img src="{{ route('admin.support-tickets.attachments.preview', $attachment) }}" alt="" loading="lazy" decoding="async" x-on:error="$el.remove()">
                                                                <span class="st-file__zoom"><x-admin.icon name="zoom-in" class="h-5 w-5" /></span>
                                                            @else
                                                                <x-admin.icon :name="$isPdf ? 'file-text' : 'file'" class="h-6 w-6" />
                                                            @endif
                                                        </span>
                                                        <span class="st-file__text">
                                                            <span class="st-file__name">{{ $attachment->original_name }}</span>
                                                            <span class="st-file__sub">{{ $isImage ? 'Image' : ($isPdf ? 'PDF' : 'File') }} · {{ $attachment->sizeLabel() }}</span>
                                                        </span>
                                                    </button>
                                                    <a href="{{ route('admin.support-tickets.attachments.show', $attachment) }}" class="st-file__dl" download title="Download" aria-label="Download {{ $attachment->original_name }}">{!! $downloadSvg !!}</a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>

                @if (! $isResolved)
                    <section class="rounded-[14px] border border-vd-line bg-white p-5" aria-label="Reply to the buyer"
                        x-data="{
                            files: [], urls: [], dragging: false, sending: false, length: {{ mb_strlen((string) old('body')) }},
                            fileError: @js($fileError), bodyError: @js((string) $errors->first('body')),
                            add(list) {
                                const problems = [];
                                [...list].forEach((file) => {
                                    const type = file.type || (/\.pdf$/i.test(file.name) ? 'application/pdf' : '');
                                    if (!['image/jpeg', 'image/png', 'image/webp', 'application/pdf'].includes(type)) problems.push(file.name + ' is not a supported file. Attach a JPG, PNG, WebP or PDF.');
                                    else if (file.size > 5242880) problems.push(file.name + ' is over 5 MB. Attach a smaller file.');
                                    else if (this.files.length >= 2) problems.push('You can attach 2 files. Remove one to add ' + file.name + '.');
                                    else { this.files.push(file); this.urls.push(type.startsWith('image/') ? URL.createObjectURL(file) : ''); }
                                });
                                this.sync();
                                this.fileError = problems.join(' ');
                            },
                            remove(i) { if (this.urls[i]) URL.revokeObjectURL(this.urls[i]); this.files.splice(i, 1); this.urls.splice(i, 1); this.sync(); this.fileError = ''; },
                            sync() { const t = new DataTransfer(); this.files.forEach((f) => t.items.add(f)); this.$refs.file.files = t.files; },
                            grow() { const el = this.$refs.body; el.style.height = 'auto'; el.style.height = Math.min(el.scrollHeight + 2, 320) + 'px'; this.length = el.value.length; this.bodyError = ''; },
                            check(event) {
                                if (this.$refs.body.value.trim().length < 2) {
                                    event.preventDefault();
                                    this.bodyError = 'Reply is empty. Write your reply, then select Send reply.';
                                    this.$refs.body.focus();
                                    return;
                                }
                                this.sending = true;
                            },
                        }"
                        x-init="if (length) $nextTick(() => grow())"
                        @pageshow.window="sending = false">
                        <div class="flex items-start gap-3">
                            <span class="st-tile"><x-admin.icon name="message" class="h-4 w-4" /></span>
                            <div>
                                <h2 class="font-display text-sm font-semibold text-vd-ink">Reply to the buyer</h2>
                                <p class="mt-1 text-[13px] text-vd-muted">{{ $ticket->status === 'open' ? 'Sending a reply moves this ticket to In review.' : 'The buyer is notified of your reply.' }}</p>
                            </div>
                        </div>

                        <form x-ref="form" action="{{ route('admin.support-tickets.reply', $ticket) }}" method="POST" enctype="multipart/form-data" class="mt-4 space-y-3" novalidate @submit="check($event)">
                            @csrf
                            <div class="st-composer" :class="{ 'is-dragging': dragging }"
                                @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false" @drop.prevent="dragging = false; add($event.dataTransfer.files)">
                                <div x-show="dragging" x-cloak class="st-drop"><x-admin.icon name="paperclip" class="h-4 w-4" />Drop up to 2 files to attach</div>

                                <label for="body" class="sr-only">Message</label>
                                <textarea id="body" name="body" rows="4" maxlength="5000" x-ref="body" @input="grow()"
                                    @keydown.ctrl.enter.prevent="$refs.form.requestSubmit()" @keydown.meta.enter.prevent="$refs.form.requestSubmit()"
                                    :class="bodyError ? 'border-vd-danger-ink' : 'border-vd-line-strong'"
                                    class="block w-full resize-none rounded-[10px] border bg-white px-3 py-2 text-[13px] leading-5 text-vd-ink placeholder:text-vd-muted focus:border-vd-plum focus:outline-none focus:ring-2 focus:ring-vd-plum/20"
                                    placeholder="Explain the next step or ask for the details you need.">{{ old('body') }}</textarea>
                                <div class="mt-1 flex items-start justify-between gap-3">
                                    <p x-show="bodyError" x-cloak x-text="bodyError" class="text-xs text-vd-danger-ink" role="alert">{{ $errors->first('body') }}</p>
                                    <p class="st-count ml-auto" :class="{ 'is-near': length > 4500 }"><span x-text="length.toLocaleString()">0</span> / 5,000</p>
                                </div>
                            </div>

                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <label class="{{ $btnSecondary }} cursor-pointer !h-9 !px-3 !text-[13px] !font-medium focus-within:ring-2 focus-within:ring-vd-plum/40">
                                        <x-admin.icon name="paperclip" class="h-4 w-4" />
                                        Attach a file
                                        <input type="file" name="attachments[]" multiple x-ref="file" class="sr-only"
                                            accept="image/jpeg,image/png,image/webp,application/pdf" @change="add([...$el.files].filter((f) => !files.includes(f)))">
                                    </label>
                                    <template x-for="(file, i) in files" :key="file.name + i">
                                        <span class="st-pick">
                                            <span class="st-pick__thumb">
                                                <img x-show="urls[i]" :src="urls[i]" alt="">
                                                <x-admin.icon x-show="!urls[i]" name="file-text" class="h-4 w-4" />
                                            </span>
                                            <span class="st-pick__name" x-text="file.name"></span>
                                            <button type="button" @click="remove(i)" :aria-label="'Remove ' + file.name"
                                                class="flex h-6 w-6 items-center justify-center rounded-full text-vd-muted transition-colors duration-200 hover:bg-vd-lavender hover:text-vd-plum {{ $focus }}">
                                                <x-admin.icon name="x" class="h-3.5 w-3.5" />
                                            </button>
                                        </span>
                                    </template>
                                    <span x-show="!files.length" class="text-xs text-vd-muted">Drop or pick up to 2 files, 5 MB each. JPG, PNG, WebP or PDF.</span>
                                </div>
                                <p x-show="fileError" x-cloak x-text="fileError" class="mt-2 text-xs text-vd-danger-ink" role="alert">{{ $fileError }}</p>
                            </div>

                            <div class="flex items-center justify-end gap-3">
                                <span class="hidden text-xs text-vd-muted sm:inline">Press Ctrl+Enter to send</span>
                                <button type="submit" class="{{ $btnPrimary }} min-w-[132px] disabled:cursor-wait disabled:opacity-80" :disabled="sending">
                                    <span x-show="sending" x-cloak class="st-spinner" aria-hidden="true"></span>
                                    <x-admin.icon x-show="!sending" name="arrow-right" class="h-4 w-4" />
                                    <span x-text="sending ? 'Sending…' : 'Send reply'">Send reply</span>
                                </button>
                            </div>
                        </form>
                    </section>
                @else
                    <div class="flex items-center gap-3 rounded-[14px] border border-vd-success-line bg-vd-success-bg p-4 text-sm text-vd-success-ink" role="status">
                        <x-admin.icon name="check-circle" class="h-[18px] w-[18px] flex-shrink-0" />
                        <p class="flex-1">This ticket is resolved. Reopen it to send another reply.</p>
                    </div>
                @endif
            </div>

            <aside class="space-y-6 lg:sticky lg:top-6">
                <section class="rounded-[14px] border border-vd-line bg-white p-5" aria-label="Buyer">
                    <h2 class="mb-4 font-display text-sm font-semibold text-vd-ink">Buyer</h2>
                    <div class="flex items-center gap-3">
                        <x-admin.avatar :user="$ticket->user" name="Buyer" size="h-12 w-12" text="text-base" />
                        <div class="min-w-0">
                            <p class="truncate font-medium text-vd-ink">{{ $ticket->user?->name ?? 'Deleted buyer' }}</p>
                            <p class="text-xs text-vd-muted">Buyer</p>
                        </div>
                    </div>

                    @if ($ticket->user?->email || $ticket->user?->phone_number)
                        <ul class="mt-4 space-y-2">
                            @if ($ticket->user?->email)
                                <li class="flex items-center gap-2">
                                    <a href="mailto:{{ $ticket->user->email }}" class="flex min-w-0 flex-1 items-center gap-2 text-[13px] text-vd-ink hover:text-vd-plum">
                                        <span class="st-tile st-tile--sm"><x-admin.icon name="mail" class="h-3.5 w-3.5" /></span>
                                        <span class="truncate">{{ $ticket->user->email }}</span>
                                    </a>
                                    <button type="button" @click="copy(@js($ticket->user->email), 'email')" title="Copy email" aria-label="Copy email"
                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg text-vd-muted transition-colors duration-200 hover:bg-vd-lavender hover:text-vd-plum {{ $focus }}">
                                        <x-admin.icon name="copy" class="h-4 w-4" />
                                    </button>
                                </li>
                            @endif
                            @if ($ticket->user?->phone_number)
                                <li class="flex items-center gap-2">
                                    <a href="tel:{{ $ticket->user->phone_number }}" class="flex min-w-0 flex-1 items-center gap-2 text-[13px] text-vd-ink hover:text-vd-plum">
                                        <span class="st-tile st-tile--sm"><x-admin.icon name="user" class="h-3.5 w-3.5" /></span>
                                        <span class="truncate">{{ $ticket->user->phone_number }}</span>
                                    </a>
                                    <button type="button" @click="copy(@js($ticket->user->phone_number), 'phone')" title="Copy phone number" aria-label="Copy phone number"
                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg text-vd-muted transition-colors duration-200 hover:bg-vd-lavender hover:text-vd-plum {{ $focus }}">
                                        <x-admin.icon name="copy" class="h-4 w-4" />
                                    </button>
                                </li>
                            @endif
                        </ul>
                        <p x-show="copied && copied !== 'fail'" x-cloak class="mt-2 text-xs text-vd-success-ink" role="status">Copied to your clipboard.</p>
                        <p x-show="copied === 'fail'" x-cloak class="mt-2 text-xs text-vd-danger-ink" role="alert">Couldn't copy. Select the text and copy it yourself.</p>
                    @endif
                </section>

                <section class="rounded-[14px] border border-vd-line bg-white p-5" aria-label="Ticket status">
                    <h2 class="mb-4 font-display text-sm font-semibold text-vd-ink">Ticket status</h2>

                    <ol class="st-steps" aria-label="Progress">
                        @foreach ($steps as $key => [$label, $icon])
                            @php $state = $loop->index < $stepIndex ? 'done' : ($loop->index === $stepIndex ? 'current' : 'todo'); @endphp
                            <li class="st-step st-step--{{ $state }}" @if ($state === 'current') aria-current="step" @endif>
                                <span class="st-step__tile"><x-admin.icon :name="$state === 'done' ? 'check' : $icon" class="h-4 w-4" /></span>
                                <span>{{ $label }}</span>
                            </li>
                        @endforeach
                    </ol>

                    <p class="mb-4 mt-4 text-[13px] text-vd-muted">{{ $isResolved ? 'Reopen the ticket if the buyer needs more help.' : 'Resolve the ticket once the buyer has what they need.' }}</p>

                    <div class="flex flex-col gap-2">
                        @if ($ticket->status === 'open')
                            <form action="{{ route('admin.support-tickets.status', $ticket) }}" method="POST">@csrf @method('PATCH')
                                <input type="hidden" name="status" value="in_review">
                                <button type="submit" class="{{ $btnSecondary }} w-full">
                                    <x-admin.icon name="hourglass" class="h-4 w-4" />
                                    Start review
                                </button>
                            </form>
                        @elseif ($ticket->status === 'in_review')
                            <form action="{{ route('admin.support-tickets.status', $ticket) }}" method="POST">@csrf @method('PATCH')
                                <input type="hidden" name="status" value="open">
                                <button type="submit" class="{{ $btnSecondary }} w-full">
                                    <x-admin.icon name="rotate-ccw" class="h-4 w-4" />
                                    Move back to open
                                </button>
                            </form>
                        @endif

                        @if ($isResolved)
                            <form action="{{ route('admin.support-tickets.status', $ticket) }}" method="POST">@csrf @method('PATCH')
                                <input type="hidden" name="status" value="in_review">
                                <button type="submit" class="{{ $btnPrimary }} w-full">
                                    <x-admin.icon name="rotate-ccw" class="h-4 w-4" />
                                    Reopen ticket
                                </button>
                            </form>
                        @else
                            <button type="button" @click="confirmResolve = true" class="{{ $btnSuccess }} w-full">
                                <x-admin.icon name="check-circle" class="h-4 w-4" />
                                Mark as resolved
                            </button>
                        @endif
                    </div>

                    <dl class="mt-4 space-y-2 border-t border-vd-line pt-4 text-[13px]">
                        <div class="flex justify-between gap-3"><dt class="text-vd-muted">Opened</dt><dd class="font-medium text-vd-ink">{{ $ticket->created_at?->format('M j, g:i A') }}</dd></div>
                        @if ($ticket->last_reply_at)
                            <div class="flex justify-between gap-3"><dt class="text-vd-muted">Last activity</dt><dd class="font-medium text-vd-ink">{{ $ticket->last_reply_at->diffForHumans() }}</dd></div>
                        @endif
                        @if ($isResolved && $ticket->closed_at)
                            <div class="flex justify-between gap-3"><dt class="text-vd-muted">Resolved</dt><dd class="font-medium text-vd-ink">{{ $ticket->closed_at->format('M j, g:i A') }}</dd></div>
                        @endif
                    </dl>
                </section>
            </aside>
        </div>

        @unless ($isResolved)
            <x-admin.dialog model="confirmResolve" title="Mark this ticket as resolved?" size="sm">
                <p class="text-sm text-vd-muted">The buyer is notified and can't reply until you reopen the ticket.</p>
                <form action="{{ route('admin.support-tickets.status', $ticket) }}" method="POST" class="mt-6 flex items-center justify-end gap-3">
                    @csrf @method('PATCH')
                    <input type="hidden" name="status" value="resolved">
                    <button type="button" @click="confirmResolve = false" class="{{ $btnSecondary }}">Keep open</button>
                    <button type="submit" class="{{ $btnSuccess }}">Mark as resolved</button>
                </form>
            </x-admin.dialog>
        @endunless

        {{-- File viewer: opens the file in a popup. Download is one click away. --}}
        @if ($gallery->isNotEmpty())
            <div x-show="viewer.open" x-cloak class="st-viewer" role="dialog" aria-modal="true" aria-label="File preview" @click.self="closeViewer()"
                x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
                <div class="st-viewer__panel" @click.stop>
                    <div class="st-viewer__head">
                        <span class="st-tile"><x-admin.icon x-show="current && current.kind === 'image'" name="image" class="h-4 w-4" /><x-admin.icon x-show="!current || current.kind !== 'image'" name="file-text" class="h-4 w-4" /></span>
                        <div class="st-viewer__title">
                            <strong x-text="current ? current.name : ''"></strong>
                            <span x-text="current ? current.size + (gallery.length > 1 ? ' · ' + (position + 1) + ' of ' + gallery.length : '') : ''"></span>
                        </div>
                        <div class="st-viewer__actions">
                            <button type="button" x-show="current && current.kind === 'image' && !viewer.failed" @click="viewer.zoom = !viewer.zoom"
                                class="st-viewer__btn st-viewer__btn--soft" :aria-label="viewer.zoom ? 'Fit to screen' : 'View full size'" :title="viewer.zoom ? 'Fit to screen' : 'View full size'">
                                <x-admin.icon x-show="!viewer.zoom" name="zoom-in" class="h-4 w-4" /><x-admin.icon x-show="viewer.zoom" name="zoom-out" class="h-4 w-4" />
                            </button>
                            <a :href="current ? current.download : '#'" download class="st-viewer__btn" :aria-label="current ? 'Download ' + current.name : 'Download'">
                                {!! $downloadSvg !!}<span class="st-viewer__btn-label">Download</span>
                            </a>
                            <button type="button" class="st-viewer__close" @click="closeViewer()" aria-label="Close preview"><x-admin.icon name="x" class="h-4 w-4" /></button>
                        </div>
                    </div>

                    <div class="st-viewer__stage" :class="{ 'is-image': current && current.kind === 'image' && !viewer.failed }">
                        <div x-show="viewer.loading && !viewer.failed" class="st-viewer__loading" role="status"><span class="st-spinner" style="width:24px;height:24px"></span><span class="sr-only">Loading preview</span></div>

                        <template x-if="viewer.open && current && current.kind === 'image' && !viewer.failed">
                            <img :src="current.view" :alt="current.name" :class="{ 'is-zoomed': viewer.zoom }"
                                @load="viewer.loading = false" x-on:error="viewer.loading = false; viewer.failed = true" @click="viewer.zoom = !viewer.zoom">
                        </template>

                        <template x-if="viewer.open && current && current.kind === 'pdf' && !viewer.failed">
                            <iframe :src="current.view + '#view=FitH'" :title="current.name" @load="viewer.loading = false"></iframe>
                        </template>

                        <div x-show="viewer.failed || (current && current.kind === 'file')" x-cloak class="st-viewer__state">
                            <span class="st-tile st-tile--lg"><x-admin.icon name="alert-circle" class="h-6 w-6" /></span>
                            <h3 x-text="viewer.failed ? 'This file didn\'t load' : 'No preview for this file type'"></h3>
                            <p x-text="viewer.failed ? 'The file may have been removed or your connection dropped. Download it, or close this and try again.' : 'Download the file to open it on your computer.'"></p>
                            <a :href="current ? current.download : '#'" download class="st-viewer__btn">{!! $downloadSvg !!}Download file</a>
                        </div>

                        <button type="button" x-show="gallery.length > 1" @click="step(-1)" class="st-viewer__nav st-viewer__nav--prev" aria-label="Previous file"><x-admin.icon name="chevron-left" class="h-5 w-5" /></button>
                        <button type="button" x-show="gallery.length > 1" @click="step(1)" class="st-viewer__nav st-viewer__nav--next" aria-label="Next file"><x-admin.icon name="chevron-right" class="h-5 w-5" /></button>
                    </div>

                    <p x-show="current && current.kind === 'pdf' && !viewer.failed" class="st-viewer__foot">PDF not showing? <a :href="current ? current.download : '#'" download>Download it instead</a>.</p>
                </div>
            </div>
        @endif
    </div>
</x-admin.layout>
