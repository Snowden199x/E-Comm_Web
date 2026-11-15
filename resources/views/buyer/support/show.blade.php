<x-buyer.layout title="{{ $ticket->subject }} — Support">
    @vite(['resources/css/buyer/support-theme.css', 'resources/js/buyer/support.js'])

    <div class="sp-page sp-page--thread" x-data x-init="
        @if (session('success')) $store.banner.show({ type: 'success', title: @js(session('success')) }); @endif
        @if ($errors->any()) $store.banner.show({ type: 'error', title: @js($errors->has('status') ? 'Could not resolve request' : 'Reply not sent'), text: @js($errors->has('status') ? $errors->first('status') : 'Fix the highlighted fields, then select Send reply again.') }); @endif
    ">
        <nav class="sp-crumb" aria-label="Breadcrumb">
            <a href="{{ route('buyer.support.index') }}">Support</a><span>/</span><span>#{{ $ticket->id }}</span>
        </nav>

        <header class="sp-head">
            <div>
                <h1>{{ $ticket->subject }}</h1>
                <div class="sp-meta">
                    <span>{{ $ticket->category_label }}</span>
                    @if ($ticket->order)
                        <i></i><a href="{{ route('buyer.orders.show', $ticket->order_id) }}">{{ $ticket->order->number }}</a>
                    @endif
                    <i></i><span>Opened {{ $ticket->created_at?->format('M j, Y g:i A') }}</span>
                </div>
            </div>
            <span class="sp-badge sp-tone-{{ $ticket->status }}">{{ $ticket->status_label }}</span>
        </header>

        <section class="sp-card" aria-label="Conversation">
            <div class="sp-thread">
                @foreach ($ticket->messages as $message)
                    <div class="sp-msg {{ $message->is_staff ? 'sp-msg--staff' : 'sp-msg--me' }}">
                        <div class="sp-msg__bubble" style="animation-delay: {{ min($loop->index, 8) * 30 }}ms">
                            <span class="sp-msg__who">{{ $message->sender_label }} · {{ $message->created_at?->format('M j, g:i A') }}</span>
                            <div class="sp-msg__text">{{ $message->body }}</div>
                            @if ($message->attachments->isNotEmpty())
                                <ul class="sp-msg__files">
                                    @foreach ($message->attachments as $attachment)
                                        <li>
                                            <a href="{{ route('buyer.support.attachments.show', $attachment) }}">
                                                <svg class="sp-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z" /><path d="M14 3v5h5" /></svg>
                                                {{ $attachment->original_name }} ({{ $attachment->sizeLabel() }})
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        @if ($ticket->status !== 'resolved')
            <form action="{{ route('buyer.support.reply', $ticket) }}" method="POST" enctype="multipart/form-data" class="sp-card sp-form" data-sp-validate novalidate>
                @csrf
                <div class="sp-field {{ $errors->has('body') ? 'has-error' : '' }}">
                    <label for="body">Add to this request</label>
                    <textarea id="body" name="body" rows="4" required maxlength="5000" data-sp-count="bodyCount"
                        placeholder="Add the missing detail or answer Vendo's question."
                        data-sp-min="2" data-sp-empty="Reply is empty. Write your message, then select Send reply."
                        data-sp-short="Reply is too short. Add at least 2 characters.">{{ old('body') }}</textarea>
                    <div class="sp-hint"><span></span><span id="bodyCount">0 / 5000</span></div>
                    @error('body')<p class="sp-error">{{ $message }}</p>@enderror
                </div>

                <div class="sp-field {{ $errors->has('attachments') || $errors->has('attachments.*') ? 'has-error' : '' }}" data-sp-files>
                    <span class="sp-label">Attachments <small>(optional)</small></span>
                    <label class="sp-drop">
                        <span class="sp-drop__icon">
                            <svg class="sp-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="m21 12-8.6 8.6a5 5 0 0 1-7-7l8.6-8.6a3.3 3.3 0 0 1 4.7 4.7l-8.6 8.6a1.7 1.7 0 0 1-2.4-2.4l7.9-7.9" /></svg>
                        </span>
                        <span><strong>Add a photo or PDF</strong><small>Up to 2 files, 5 MB each. JPG, PNG, WebP or PDF.</small></span>
                        <input type="file" name="attachments[]" multiple accept="image/jpeg,image/png,image/webp,application/pdf">
                    </label>
                    <ul class="sp-files" aria-live="polite"></ul>
                    <p class="sp-error" data-sp-files-error @if (! $errors->has('attachments') && ! $errors->has('attachments.*')) hidden @endif>
                        {{ $errors->first('attachments') ?: $errors->first('attachments.*') }}
                    </p>
                </div>

                <div class="sp-actions">
                    <button type="submit" class="sp-btn sp-btn--primary">
                        <svg class="sp-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M22 2 11 13M22 2l-7 20-4-9-9-4z" /></svg>
                        Send reply
                    </button>
                </div>
            </form>

            <div class="sp-solved">
                <p>Got what you needed? Close this request.</p>
                <button type="button" class="sp-btn sp-btn--soft" data-sp-open="spResolve">
                    <svg class="sp-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12.5 4.5 4.5L19 7.5" /></svg>
                    Mark as resolved
                </button>
            </div>

            <dialog id="spResolve" class="sp-dialog" aria-labelledby="spResolveTitle">
                <form action="{{ route('buyer.support.close', $ticket) }}" method="POST" class="sp-dialog__body">
                    @csrf
                    <div class="sp-dialog__top">
                        <span class="sp-dialog__icon">
                            <svg class="sp-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18z" /><path d="m8.5 12.5 2.5 2.5 4.5-5" /></svg>
                        </span>
                        <button type="button" class="sp-dialog__close" data-sp-close aria-label="Close">
                            <svg class="sp-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" /></svg>
                        </button>
                    </div>
                    <div>
                        <h2 id="spResolveTitle">Mark this request as resolved?</h2>
                        <p>You can't reply after this. Open a new request if you need more help.</p>
                    </div>
                    <div class="sp-dialog__actions">
                        <button type="button" class="sp-btn sp-btn--soft" data-sp-close>Keep open</button>
                        <button type="submit" class="sp-btn sp-btn--primary">Mark as resolved</button>
                    </div>
                </form>
            </dialog>
        @else
            <div class="sp-closed" role="status">
                <svg class="sp-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18z" /><path d="m8.5 12.5 2.5 2.5 4.5-5" /></svg>
                <p>This request is resolved. Open a new request if you still need help.</p>
                <a href="{{ route('buyer.support.create') }}" class="sp-btn sp-btn--soft sp-btn--small">New request</a>
            </div>
        @endif
    </div>
</x-buyer.layout>
