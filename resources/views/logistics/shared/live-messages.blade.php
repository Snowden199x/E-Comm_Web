<x-logistics.shell title="Messages" :role="$role">
    <div class="lg-page" x-data="{ compose: false, open: null }">
        <div class="lg-page-head"><div><h1>Messages</h1><p>Conversations between your {{ $role === 'company' ? 'company and facilities' : 'facility and Company Admin' }}.</p></div><div class="lg-page-head__actions"><button type="button" class="lg-btn" @click="compose = true"><x-logistics.icon name="plus" :size="18" /> New message</button></div></div>
        @if (session('success'))<div class="lg-note" role="status">{{ session('success') }}</div>@endif
        @if ($errors->any())<div class="lg-note" role="alert">{{ $errors->first() }}</div>@endif
        <section class="lg-card" aria-label="Conversations">
            @forelse ($threads as $thread)
                <div style="padding:18px 24px;border-bottom:1px solid var(--lg-line)">
                    <div style="display:flex;justify-content:space-between;align-items:start;gap:14px">
                        <button type="button" style="text-align:left;background:none;border:0;color:inherit;cursor:pointer;padding:0" @click="open = open === {{ $thread->id }} ? null : {{ $thread->id }}" :aria-expanded="open === {{ $thread->id }}">
                            <strong>{{ $thread->subject }}</strong><span class="lg-cell-sub" style="display:block">{{ $thread->facility?->name }} · {{ $thread->topic }} · {{ $thread->updated_at?->diffForHumans() }}</span>
                        </button>
                        <span class="lg-pill {{ $thread->status === 'open' ? 'lg-pill--amber' : 'lg-pill--green' }}">{{ ucfirst($thread->status) }}</span>
                    </div>
                    <div x-show="open === {{ $thread->id }}" x-cloak style="padding-top:16px">
                        @foreach ($thread->messages as $message)
                            <article style="padding:12px 0;border-top:1px solid var(--lg-line)"><div style="display:flex;justify-content:space-between;gap:12px"><strong>{{ $message->sender?->name ?? 'Former user' }}</strong><span class="lg-cell-sub">{{ $message->created_at?->format('M j, Y g:i A') }}</span></div><p style="white-space:pre-wrap;line-height:1.6;margin-top:7px">{{ $message->body }}</p></article>
                        @endforeach
                        <form method="POST" action="{{ route('lgp.'.$role.'.messages.status', $thread) }}" style="margin:14px 0">@csrf @method('PATCH')<input type="hidden" name="status" value="{{ $thread->status === 'open' ? 'resolved' : 'open' }}"><button class="lg-btn lg-btn--outline lg-btn--sm" type="submit">{{ $thread->status === 'open' ? 'Mark resolved' : 'Reopen' }}</button></form>
                        @if ($thread->status === 'open')
                            <form method="POST" action="{{ route('lgp.'.$role.'.messages.reply', $thread) }}" class="lg-field">@csrf<label for="reply-{{ $thread->id }}">Reply</label><textarea id="reply-{{ $thread->id }}" name="body" class="lg-textarea" rows="3" maxlength="4000" required></textarea><div><button type="submit" class="lg-btn">Send reply</button></div></form>
                        @endif
                    </div>
                </div>
            @empty
                <x-logistics.empty icon="message" title="No conversations yet" text="Create a message to contact the other side of your network." />
            @endforelse
        </section>
        <div style="margin-top:16px">{{ $threads->links() }}</div>
        <div class="lg-overlay" x-show="compose" x-cloak @click.self="compose = false" @keydown.escape.window="compose = false">
            <div class="lg-modal" style="width:min(100%,600px)" role="dialog" aria-modal="true" aria-labelledby="newMessageTitle" x-show="compose">
                <div class="lg-modal__head"><div><h2 id="newMessageTitle">New message</h2><p>Use one conversation per issue.</p></div><button type="button" class="lg-toast__close" @click="compose = false" aria-label="Close"><x-logistics.icon name="x" :size="18" /></button></div>
                <form method="POST" action="{{ route('lgp.'.$role.'.messages.store') }}">@csrf
                    <div class="lg-modal__body lg-modal__body--grid">
                        @if ($role === 'company')<div class="lg-field"><label for="messageFacility">Facility</label><select id="messageFacility" name="facility_id" class="lg-select" required><option value="">Choose a facility</option>@foreach ($facilities as $option)<option value="{{ $option->id }}">{{ $option->name }}</option>@endforeach</select></div>
                        @else <p class="lg-section-sub">To: {{ $facility->company?->business_name }} Company Admin</p> @endif
                        <div class="lg-field"><label for="messageTopic">Topic</label><select id="messageTopic" name="topic" class="lg-select" required>@foreach (['Parcel', 'Trip', 'Rider', 'Driver', 'Account', 'Other'] as $topic)<option value="{{ $topic }}">{{ $topic }}</option>@endforeach</select></div>
                        <div class="lg-field"><label for="messagePriority">Priority</label><select id="messagePriority" name="priority" class="lg-select"><option value="normal">Normal</option><option value="urgent">Urgent</option></select></div>
                        <div class="lg-field is-wide"><label for="messageSubject">Subject</label><input id="messageSubject" name="subject" class="lg-input" required minlength="5" maxlength="160"></div>
                        <div class="lg-field is-wide"><label for="messageBody">Message</label><textarea id="messageBody" name="body" class="lg-textarea" rows="5" required minlength="10" maxlength="4000"></textarea></div>
                    </div>
                    <div class="lg-modal__foot"><button type="button" class="lg-btn lg-btn--outline" @click="compose = false">Cancel</button><button type="submit" class="lg-btn">Send message</button></div>
                </form>
            </div>
        </div>
    </div>
</x-logistics.shell>
