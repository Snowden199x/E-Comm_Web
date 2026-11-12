<x-seller.layout title="Messages">
    @vite(['resources/css/seller/workspace.css', 'resources/css/seller/messages.css', 'resources/js/seller/messages.js'])
    <section class="sw-page sm-page">
        <header class="sw-heading"><div><h1>Messages</h1><p>Reply to buyers, answer complaint cases, or contact Vendo Support.</p></div></header>
        @if(session('success'))<p hidden data-flash="{{ session('success') }}"></p>@endif
        @if($errors->any())<p class="sw-error" role="alert">{{ $errors->first() }} Check the details and try again.</p>@endif
        <div class="sw-inbox">
            <aside class="sw-inbox__list">
                <label class="sm-search">
                    <span class="sw-sr-only">Search chats</span>
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="m16 16 4 4"/></svg>
                    <input type="search" id="smSearch" placeholder="Search chats" autocomplete="off">
                </label>
                <div id="sellerCustomerChats">@include('seller.messages.partials.customer-list')</div>
                <p class="sm-list-empty" id="smNoMatch" hidden>No chats match your search.</p>
                @if($tab !== 'complaints')
                    <p class="sw-inbox__group">Support</p>
                    <div class="sw-inbox__entry sm-entry is-active"><span class="sw-inbox__initial">V</span><span><strong>Vendo Support</strong><small>{{ $conversation?->status === 'open' ? 'Open conversation' : 'Start a conversation' }}</small></span></div>
                @endif
            </aside>
            @if($tab === 'complaints')
                <section class="sw-inbox__thread" aria-label="Complaint messages">
                    <div class="sm-empty">
                        <span class="sm-empty__icon"><svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 21V4M5 4h11l-2 4 2 4H5"/></svg></span>
                        <strong>{{ $caseThreads->isEmpty() ? 'No complaint messages yet' : 'Select a case to read it' }}</strong>
                        <p>{{ $caseThreads->isEmpty() ? 'Vendo Support writes here when it needs details about a complaint.' : 'Messages from Vendo Support about your cases open here.' }}</p>
                        <a class="sw-button" href="{{ route('seller.complaints.index') }}">View complaints</a>
                    </div>
                </section>
            @else
            <section class="sw-inbox__thread" aria-label="Vendo Support conversation">
                <header class="sw-inbox__thread-head"><div><strong>Vendo Support</strong><small>Account and order help</small></div>
                    @if($conversation?->status === 'open')<div class="sw-inbox__head-actions"><form method="POST" action="{{ route('seller.messages.close', $conversation) }}">@csrf<button class="sm-link sm-link--danger" type="submit">End conversation</button></form></div>@endif
                </header>
                @if($conversation?->status === 'open')
                    <div class="sw-inbox__history" id="swMessages" data-fetch="{{ route('seller.messages.fetch', $conversation) }}" aria-live="polite"><div class="sm-skeleton" data-skeleton role="status" aria-label="Loading messages"><i></i><i></i><i></i></div></div>
                    @if($conversation->status === 'open')
                        <form class="sw-inbox__composer" id="swMessageForm" action="{{ route('seller.messages.store', $conversation) }}" method="POST" enctype="multipart/form-data" data-draft-key="seller-{{ auth()->id() }}-support-{{ $conversation->id }}" data-draft-ajax>
                            @csrf<label class="sw-sr-only" for="swMessageBody">Message</label><textarea id="swMessageBody" name="body" rows="2" maxlength="2000" placeholder="Write a message. Press Ctrl+Enter to send."></textarea>
                            <p class="sw-error" id="swMessageError" role="alert" hidden></p>
                            <div class="sw-inbox__composer-actions">
                                <div class="sm-attach-row">
                                    <label class="sm-attach"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m21 11-8.500 8.500a5 5 0 01-7-7L14 4a3.500 3.500 0 015 5l-8.500 8.500a2 2 0 01-3-3L15 7"/></svg>Attach file<input type="file" name="attachment" accept=".jpg,.jpeg,.png,.webp,.pdf" data-file-input></label>
                                    <span class="sm-file" data-file-chip hidden><span data-file-name></span><button type="button" data-file-clear aria-label="Remove file">×</button></span>
                                </div>
                                <button class="sw-button" type="submit">
                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 3 10 14M21 3l-7 18-4-7-7-4z"/></svg>
                                    Send message
                                </button>
                            </div>
                        </form>
                    @else<p class="sw-inbox__closed">This conversation is closed. Reopen it to send a message.</p>@endif
                @else
                    <div class="sm-start">
                        <span class="sm-empty__icon sm-empty__icon--lilac"><svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a8 8 0 01-11.600 7.100L4 20l1-4.600A8 8 0 1121 12z"/></svg></span>
                        <strong>Contact Vendo Support</strong>
                        <p>Pick a topic to start a conversation.</p>
                        @foreach(['General Inquiry' => 'Questions about your account or orders', 'Raise a Concern' => 'Report a problem you ran into', 'Other' => 'Anything else you need help with'] as $reason => $hint)
                            <form action="{{ route('seller.messages.start') }}" method="POST">
                                @csrf<input type="hidden" name="reason" value="{{ $reason }}">
                                <button type="submit" class="sm-topic"><strong>{{ $reason }}</strong><small>{{ $hint }}</small></button>
                            </form>
                        @endforeach
                    </div>
                @endif
            </section>
            @endif
        </div>
    </section>
    <script>
    (() => {
        async function updateCustomerChats() {
            if (document.hidden) return;
            try {
                const response = await fetch(@json(route('seller.messages.customer-list', ['tab' => $tab])), {headers: {'Accept': 'text/html'}});
                if (response.ok) document.getElementById('sellerCustomerChats').innerHTML = await response.text();
            } catch (_) {}
        }
        setInterval(updateCustomerChats, 1500);
        document.addEventListener('visibilitychange', () => { if (!document.hidden) updateCustomerChats(); });
    })();
    </script>
    @if($tab !== 'complaints' && $conversation?->status === 'open')
    <script>
    (() => {
        const box = document.getElementById('swMessages');
        const form = document.getElementById('swMessageForm');
        const error = document.getElementById('swMessageError');
        let lastId = 0;
        let loading = false;
        const addMessage = message => {
            const line = document.createElement('div');
            line.className = 'sw-message-line' + (message.mine ? ' is-mine' : '') + (message.system ? ' is-system' : '');
            line.dataset.messageId = message.id;
            const row = document.createElement('div');
            row.className = 'sw-message' + (message.system ? ' is-system' : (message.mine ? ' is-mine' : ''));
            const avatar = document.createElement(message.avatar ? 'img' : 'span');
            avatar.className = 'sw-message-avatar';
            if (message.avatar) { avatar.src = message.avatar; avatar.alt = ''; }
            else avatar.textContent = message.initial;
            const meta = document.createElement('small');
            meta.textContent = message.sender + ' · ' + message.time;
            row.append(meta);
            if (message.body) { const body = document.createElement('p'); body.textContent = message.body; row.append(body); }
            message.attachments.forEach(item => {
                const link = document.createElement('a');
                link.href = item.url; link.target = '_blank'; link.rel = 'noopener'; link.textContent = 'Attachment: ' + item.name;
                row.append(link);
            });
            if (!message.system && !message.mine) line.append(avatar);
            line.append(row);
            if (message.mine) line.append(avatar);
            box.append(line);
        };
        async function load() {
            if (loading || document.hidden) return;
            loading = true;
            try {
                const nearBottom = box.scrollHeight - box.scrollTop - box.clientHeight < 100;
                const response = await fetch(box.dataset.fetch + '?after=' + lastId, {headers: {'Accept': 'application/json'}});
                if (response.status === 404) { location.assign(@json(route('seller.messages.index'))); return; }
                if (!response.ok) return;
                const data = await response.json();
                if (data.active_ids) {
                    const active = new Set(data.active_ids.map(Number));
                    box.querySelectorAll('[data-message-id]').forEach(row => {
                        if (!active.has(Number(row.dataset.messageId))) row.remove();
                    });
                }
                box.querySelector('[data-skeleton]')?.remove();
                data.messages.forEach(message => { addMessage(message); lastId = message.id; });
                if (nearBottom || lastId === 0) box.scrollTop = box.scrollHeight;
            } catch (_) {
                const loadingBox = box.querySelector('[data-skeleton]');
                if (loadingBox) loadingBox.outerHTML = '<div class="sm-loaderror" role="alert"><span>Messages did not load. Check your connection, then try again.</span><button type="button" class="sm-link" data-retry>Try again</button></div>';
            } finally { loading = false; }
        }
        if (form) form.addEventListener('submit', async event => {
            event.preventDefault(); error.hidden = true;
            const button = form.querySelector('button[type=submit]');
            button.disabled = true; button.classList.add('is-loading');
            try {
                const response = await fetch(form.action, {method: 'POST', body: new FormData(form), headers: {'Accept': 'application/json'}});
                if (!response.ok) {
                    const data = await response.json();
                    error.textContent = data.message || 'Message did not send. Check your connection and try again.';
                    error.hidden = false; return;
                }
                form.dispatchEvent(new Event('vendo:draft-committed'));
                form.reset(); await load(); box.scrollTop = box.scrollHeight;
            } catch (_) { error.textContent = 'Connection lost. Check your internet and send again.'; error.hidden = false; }
            finally { button.disabled = false; button.classList.remove('is-loading'); }
        });
        box.addEventListener('click', event => { if (event.target.closest('[data-retry]')) { box.innerHTML = '<div class="sm-skeleton" data-skeleton role="status" aria-label="Loading messages"><i></i><i></i><i></i></div>'; load(); } });
        load(); setInterval(load, 1000); document.addEventListener('visibilitychange', () => { if (!document.hidden) load(); });
    })();
    </script>
    @endif
</x-seller.layout>
