<x-seller.layout title="Messages">
    @vite('resources/css/seller/workspace.css')
    <section class="sw-page">
        <header class="sw-heading"><div><h1>Messages</h1><p>Reply to buyers about their orders or contact Vendo Support.</p></div></header>
        @if(session('success'))<p class="sw-notice" role="status">{{ session('success') }}</p>@endif
        @if($errors->any())<p class="sw-error" role="alert">{{ $errors->first() }}</p>@endif
        <div class="sw-inbox">
            <aside class="sw-inbox__list"><div class="sw-inbox__list-head"><strong>Conversations</strong></div>
                <div id="sellerCustomerChats">@include('seller.messages.partials.customer-list')</div>
                <p class="sw-inbox__group">Support</p>
                <div class="sw-inbox__entry is-active"><span class="sw-inbox__initial">V</span><span><strong>Vendo Support</strong><small>{{ $conversation?->status === 'open' ? 'Open conversation' : 'Start a conversation' }}</small></span></div>
            </aside>
            <section class="sw-inbox__thread" aria-label="Vendo Support conversation">
                <header class="sw-inbox__thread-head"><div><strong>Vendo Support</strong><small>Account and order help</small></div>
                    @if($conversation?->status === 'open')<form method="POST" action="{{ route('seller.messages.close', $conversation) }}">@csrf<button class="sw-text-button" type="submit">Close conversation</button></form>@endif
                </header>
                @if($conversation?->status === 'open')
                    <div class="sw-inbox__history" id="swMessages" data-fetch="{{ route('seller.messages.fetch', $conversation) }}" aria-live="polite"></div>
                    @if($conversation->status === 'open')
                        <form class="sw-inbox__composer" id="swMessageForm" action="{{ route('seller.messages.store', $conversation) }}" method="POST" enctype="multipart/form-data">
                            @csrf<label class="sw-sr-only" for="swMessageBody">Message</label><textarea id="swMessageBody" name="body" rows="2" maxlength="2000" placeholder="Write a message..."></textarea>
                            <div class="sw-inbox__composer-actions"><label class="sw-file">Attach image or PDF <input type="file" name="attachment" accept=".jpg,.jpeg,.png,.webp,.pdf"></label><button class="sw-button" type="submit">Send</button></div>
                            <p class="sw-error" id="swMessageError" role="alert" hidden></p>
                        </form>
                    @else<p class="sw-inbox__closed">This conversation is closed. Reopen it to send a message.</p>@endif
                @else
                    <div class="sw-support-start">
                        <p>Start a new conversation with Vendo Support.</p>
                        @foreach(['General Inquiry', 'Raise a Concern', 'Other'] as $reason)
                            <form action="{{ route('seller.messages.start') }}" method="POST">
                                @csrf<input type="hidden" name="reason" value="{{ $reason }}">
                                <button type="submit">{{ $reason }}</button>
                            </form>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>
    </section>
    <script>
    (() => {
        async function updateCustomerChats() {
            if (document.hidden) return;
            try {
                const response = await fetch(@json(route('seller.messages.customer-list')), {headers: {'Accept': 'text/html'}});
                if (response.ok) document.getElementById('sellerCustomerChats').innerHTML = await response.text();
            } catch (_) {}
        }
        setInterval(updateCustomerChats, 1500);
        document.addEventListener('visibilitychange', () => { if (!document.hidden) updateCustomerChats(); });
    })();
    </script>
    @if($conversation?->status === 'open')
    <script>
    (() => {
        const box = document.getElementById('swMessages');
        const form = document.getElementById('swMessageForm');
        const error = document.getElementById('swMessageError');
        let lastId = 0;
        let loading = false;
        const addMessage = message => {
            const row = document.createElement('div');
            row.className = 'sw-message' + (message.system ? ' is-system' : (message.mine ? ' is-mine' : ''));
            row.dataset.messageId = message.id;
            const meta = document.createElement('small');
            meta.textContent = message.sender + ' · ' + message.time;
            row.append(meta);
            if (message.body) { const body = document.createElement('p'); body.textContent = message.body; row.append(body); }
            message.attachments.forEach(item => {
                const link = document.createElement('a');
                link.href = item.url; link.target = '_blank'; link.rel = 'noopener'; link.textContent = 'Attachment: ' + item.name;
                row.append(link);
            });
            if (message.mine) {
                const remove = document.createElement('button');
                remove.type = 'button'; remove.className = 'sw-message__delete'; remove.textContent = 'Delete message';
                remove.dataset.deleteMessage = @json(url('/seller/messages')) + '/' + @json($conversation->id) + '/messages/' + message.id;
                row.append(remove);
            }
            box.append(row);
        };
        async function load() {
            if (loading || document.hidden) return;
            loading = true;
            try {
                const nearBottom = box.scrollHeight - box.scrollTop - box.clientHeight < 100;
                const response = await fetch(box.dataset.fetch + '?after=' + lastId, {headers: {'Accept': 'application/json'}});
                if (!response.ok) return;
                const data = await response.json();
                if (data.active_ids) {
                    const active = new Set(data.active_ids.map(Number));
                    box.querySelectorAll('[data-message-id]').forEach(row => {
                        if (!active.has(Number(row.dataset.messageId))) row.remove();
                    });
                }
                data.messages.forEach(message => { addMessage(message); lastId = message.id; });
                if (nearBottom || lastId === 0) box.scrollTop = box.scrollHeight;
            } finally { loading = false; }
        }
        if (form) form.addEventListener('submit', async event => {
            event.preventDefault(); error.hidden = true;
            const button = form.querySelector('button[type=submit]');
            button.disabled = true;
            try {
                const response = await fetch(form.action, {method: 'POST', body: new FormData(form), headers: {'Accept': 'application/json'}});
                if (!response.ok) {
                    const data = await response.json();
                    error.textContent = data.message || 'Message could not be sent.';
                    error.hidden = false; return;
                }
                form.reset(); await load(); box.scrollTop = box.scrollHeight;
            } catch (_) { error.textContent = 'Connection lost. Please try again.'; error.hidden = false; }
            finally { button.disabled = false; }
        });
        load(); setInterval(load, 1000); document.addEventListener('visibilitychange', () => { if (!document.hidden) load(); });
    })();
    </script>
    @endif
</x-seller.layout>
