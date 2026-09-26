@php
    $isBuyer = $side === 'buyer';
    $partner = $isBuyer ? $seller : $conversation->buyer;
    $featuredOrder = $order ?? $availableOrders->first();
    $shareableOrders = $order ? collect([$order]) : $availableOrders;
    $reportContext = $order ? 'order:'.$order->id : ($isBuyer ? 'seller:'.$seller->id : 'buyer:'.$conversation->buyer_id);
    $reportTargetName = $isBuyer ? ($seller->sellerDetail?->business_name ?: $seller->name) : ($partner?->name ?? 'buyer');
    $backRoute = $isBuyer ? ($order ? route('buyer.orders.show', $order) : route('buyer.sellers.show', $seller)) : route('seller.messages.index');
    $sendRoute = $isBuyer
        ? ($order ? route('buyer.marketplace-messages.store', $order) : route('buyer.marketplace-messages.seller.store', $seller))
        : route('seller.marketplace-messages.store', $conversation);
    $fetchRoute = $conversation
        ? ($isBuyer
            ? ($order ? route('buyer.marketplace-messages.fetch', $order) : route('buyer.marketplace-messages.seller.fetch', $seller))
            : route('seller.marketplace-messages.fetch', $conversation))
        : null;
@endphp
<a class="mc-back" href="{{ $backRoute }}">← {{ $isBuyer ? ($order ? 'Back to order' : 'Back to shop') : 'Back to messages' }}</a>
<div class="mc-shell">
    <aside class="mc-context">
        <p class="mc-eyebrow">{{ $isBuyer ? 'Seller chat' : 'Buyer chat' }}</p>
        <h1>{{ $isBuyer ? ($seller->sellerDetail?->business_name ?: $seller->name) : ($partner?->name ?? 'Buyer') }}</h1>
        @if($isBuyer)
            <a href="{{ route('buyer.sellers.show', $seller) }}" class="mc-order-link">View seller profile ↗</a>
        @elseif($partner)
            <a href="{{ route('seller.buyers.show', $partner) }}" class="mc-order-link">View buyer profile ↗</a>
        @endif
        <p class="mc-context__help">Only you and the other party can read this chat. {{ $isBuyer ? 'You can share an order in the chat when you need help with it.' : 'Order details shared by the buyer appear in the chat.' }}</p>
        <div class="mc-context__actions">
            <button type="button" class="mc-report-button" data-report-open>Report this {{ $isBuyer ? 'seller' : 'buyer' }}</button>
        </div>
    </aside>
    <section class="mc-thread" aria-label="Conversation with {{ $partner?->name ?? 'account' }}">
        <header class="mc-thread__head">
            <a class="mc-thread__identity" href="{{ $isBuyer ? route('buyer.sellers.show', $seller) : route('seller.buyers.show', $partner) }}">
                @if($partner?->profile_picture)<img class="mc-avatar" src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($partner->profile_picture) }}" alt="">
                @else<span class="mc-avatar">{{ mb_strtoupper(mb_substr($partner?->name ?? '?', 0, 1)) }}</span>@endif
                <span><strong>{{ $isBuyer ? ($seller->sellerDetail?->business_name ?: $seller->name) : ($partner?->name ?? 'Account unavailable') }}</strong><small>{{ $order ? 'Order #'.$order->number.' · ' : '' }}{{ $isBuyer ? 'Seller' : 'Buyer' }} · View profile</small></span>
            </a>
        </header>
        @if($order)<div class="mc-order-context">You are discussing order <strong>#{{ $order->number }}</strong>. Messages for this order stay in this conversation.</div>@endif
        @if(session('success'))<p class="mc-notice" role="status">{{ session('success') }}</p>@endif
        <p class="mc-error" id="mcError" role="alert" @if(!$errors->any()) hidden @endif>{{ $errors->first() }}</p>
        <div class="mc-history" id="mcHistory" @if($fetchRoute) data-fetch="{{ $fetchRoute }}" @endif aria-live="polite">
            @forelse($messages as $message)
                <div class="mc-message-row @if($message->sender_id === null) is-system @elseif($message->sender_id === auth()->id()) is-mine @endif" data-message-id="{{ $message->id }}">
                    @if($message->sender_id !== null && $message->sender_id !== auth()->id())
                        @if($message->sender?->profile_picture)<img class="mc-message-avatar" src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($message->sender->profile_picture) }}" alt="">@else<span class="mc-message-avatar">{{ mb_strtoupper(mb_substr($message->sender?->name ?? '?', 0, 1)) }}</span>@endif
                    @endif
                <div class="mc-message @if($message->sender_id === null) is-system @elseif($message->sender_id === auth()->id()) is-mine @endif">
                    <small>{{ $message->sender_id === null ? 'Vendo' : ($message->sender_id === auth()->id() ? 'You' : ($partner?->name ?? 'Other party')) }} · {{ $message->created_at->format('M j, g:i A') }}</small>
                    @if($message->item)<span class="mc-message__item">About: {{ $message->item->product?->name ?? 'Order item' }}</span>@endif
                    @if($message->sharedOrder)
                        @php $sharedItem = $message->sharedOrder->items->first(); @endphp
                        <a class="mc-message__order" href="{{ $isBuyer ? route('buyer.orders.show', $message->sharedOrder) : route('seller.orders.index', ['order' => $message->sharedOrder->id]) }}">
                            <span class="mc-order-card__label">{{ $message->sender_id === null ? 'Order update' : 'Order shared' }}</span>
                            <span class="mc-order-card__product">
                                @if($sharedItem?->product?->images->first()?->path)<img class="mc-order-photo" src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($sharedItem->product->images->first()->path) }}" alt="{{ $sharedItem->product->name }}">@endif
                                <span><strong>{{ $sharedItem?->product?->name ?? 'Order items' }}</strong><small>{{ $message->sharedOrder->items->sum('quantity') }} item(s) · ₱{{ number_format($message->sharedOrder->total_amount, 2) }}</small></span>
                            </span>
                            @if($message->sharedOrder->carrier_tracking_number)<span class="mc-order-card__tracking">{{ $message->sharedOrder->carrier_name ?: 'Courier' }} · Tracking {{ $message->sharedOrder->carrier_tracking_number }}</span>@endif
                            <span class="mc-order-card__footer"><span>#{{ $message->sharedOrder->number }} · {{ $message->sharedOrder->status_label }}</span><small>View order ↗</small></span>
                        </a>
                    @endif
                    @if($message->body)<p>{{ $message->body }}</p>@endif
                    @if($message->attachment_path)<a href="{{ route('marketplace-messages.attachment', $message) }}" target="_blank" rel="noopener"><img src="{{ route('marketplace-messages.attachment', $message) }}" alt="Photo attached to message" loading="lazy"></a>@endif
                </div>
                    @if($message->sender_id === auth()->id())
                        @if($message->sender?->profile_picture)<img class="mc-message-avatar" src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($message->sender->profile_picture) }}" alt="">@else<span class="mc-message-avatar">{{ mb_strtoupper(mb_substr($message->sender?->name ?? '?', 0, 1)) }}</span>@endif
                    @endif
                </div>
            @empty<p class="mc-empty" id="mcEmpty">{{ $isBuyer ? 'Send a message or share an order with the seller.' : 'No messages yet.' }}</p>@endforelse
        </div>
        @if($isBuyer && $shareableOrders->isNotEmpty())
            <form class="mc-share-form" method="POST" action="{{ route('buyer.marketplace-messages.store', $featuredOrder) }}" data-chat-form data-share-form>
                @csrf
                <label for="mcShareOrder">Share an order</label>
                <select id="mcShareOrder" name="shared_order_id" aria-label="Order to share">
                    @foreach($shareableOrders as $availableOrder)<option value="{{ $availableOrder->id }}" data-send-url="{{ route('buyer.marketplace-messages.store', $availableOrder) }}" @selected($featuredOrder?->id === $availableOrder->id)>#{{ $availableOrder->number }} · {{ $availableOrder->status_label }}</option>@endforeach
                </select>
                <button type="submit">Send order</button>
            </form>
        @endif
        <form class="mc-composer" method="POST" action="{{ $sendRoute }}" enctype="multipart/form-data" data-chat-form>
            @csrf
            @if($isBuyer && $selectedItem)<input type="hidden" name="order_item_id" value="{{ $selectedItem->id }}"><span class="mc-selected">Asking about {{ $selectedItem->product?->name ?? 'this item' }}</span>@endif
            <label class="mc-sr-only" for="mcBody">Message</label>
            <textarea id="mcBody" name="body" maxlength="2000" rows="2" placeholder="{{ $isBuyer ? 'Message the seller...' : 'Reply to the buyer...' }}">{{ old('body') }}</textarea>
            <div class="mc-composer__actions"><label>Attach photo <input type="file" name="attachment" accept="image/jpeg,image/png,image/webp"></label><button type="submit">Send message</button></div>
        </form>
    </section>
</div>
@include('shared.user-report-modal', [
    'reportRole' => $side,
    'reportContext' => $reportContext,
    'reportTargetName' => $reportTargetName,
    'reportStoreUrl' => route($side.'.user-reports.store'),
])
<script>
(() => {
    const box = document.getElementById('mcHistory');
    const error = document.getElementById('mcError');
    let lastId = Number(box.querySelector('[data-message-id]:last-of-type')?.dataset.messageId || 0);
    let loading = false;
    box.scrollTop = box.scrollHeight;
    function orderCard(order, system) {
        const link = document.createElement('a');
        link.className = 'mc-message__order'; link.href = order.url;
        const label = document.createElement('span'); label.className = 'mc-order-card__label'; label.textContent = system ? 'Order update' : 'Order shared';
        const product = document.createElement('span'); product.className = 'mc-order-card__product';
        if (order.image_url) { const photo = document.createElement('img'); photo.className = 'mc-order-photo'; photo.src = order.image_url; photo.alt = order.product_name; product.append(photo); }
        const details = document.createElement('span');
        const title = document.createElement('strong'); title.textContent = order.product_name;
        const total = document.createElement('small'); total.textContent = order.item_count + ' item(s) · ' + order.total;
        details.append(title, total); product.append(details);
        if (order.tracking) { const tracking = document.createElement('span'); tracking.className = 'mc-order-card__tracking'; tracking.textContent = (order.carrier || 'Courier') + ' · Tracking ' + order.tracking; link.append(label, product, tracking); } else { link.append(label, product); }
        const footer = document.createElement('span'); footer.className = 'mc-order-card__footer';
        const status = document.createElement('span'); status.textContent = '#' + order.number + ' · ' + order.status;
        const view = document.createElement('small'); view.textContent = 'View order ↗';
        footer.append(status, view); link.append(footer); return link;
    }
    async function refresh() {
        if (document.hidden || loading || !box.dataset.fetch) return;
        loading = true;
        try {
            const response = await fetch(box.dataset.fetch + '?after=' + lastId, {headers: {'Accept': 'application/json'}});
            if (response.status === 404) { location.assign(@json(route($isBuyer ? 'buyer.messages.index' : 'seller.messages.index'))); return; }
            if (!response.ok) return;
            const data = await response.json();
            const nearBottom = box.scrollHeight - box.scrollTop - box.clientHeight < 100;
            if (data.active_ids) {
                const active = new Set(data.active_ids.map(Number));
                box.querySelectorAll('[data-message-id]').forEach(row => {
                    if (!active.has(Number(row.dataset.messageId))) row.remove();
                });
            }
            data.messages.forEach(message => {
                document.getElementById('mcEmpty')?.remove();
                const line = document.createElement('div');
                line.className = 'mc-message-row' + (message.system ? ' is-system' : (message.mine ? ' is-mine' : '')); line.dataset.messageId = message.id;
                const row = document.createElement('div');
                row.className = 'mc-message' + (message.system ? ' is-system' : (message.mine ? ' is-mine' : ''));
                const avatar = document.createElement(message.avatar ? 'img' : 'span');
                avatar.className = 'mc-message-avatar';
                if (message.avatar) { avatar.src = message.avatar; avatar.alt = ''; }
                else avatar.textContent = message.initial;
                const meta = document.createElement('small');
                meta.textContent = (message.system ? 'Vendo' : (message.mine ? 'You' : @json($partner?->name ?? 'Other party'))) + ' · ' + message.time;
                row.append(meta);
                if (message.item) { const item = document.createElement('span'); item.className = 'mc-message__item'; item.textContent = 'About: ' + message.item; row.append(item); }
                if (message.shared_order) row.append(orderCard(message.shared_order, message.system));
                if (message.body) { const body = document.createElement('p'); body.textContent = message.body; row.append(body); }
                if (message.attachment_url) { const link = document.createElement('a'); const image = document.createElement('img'); link.href = message.attachment_url; link.target = '_blank'; link.rel = 'noopener'; image.src = message.attachment_url; image.alt = 'Photo attached to message'; link.append(image); row.append(link); }
                if (!message.system && !message.mine) line.append(avatar);
                line.append(row);
                if (message.mine) line.append(avatar);
                box.append(line); lastId = message.id;
            });
            if (nearBottom) box.scrollTop = box.scrollHeight;
        } catch (_) {} finally { loading = false; }
    }
    document.querySelectorAll('[data-chat-form]').forEach(form => form.addEventListener('submit', async event => {
        event.preventDefault(); error.hidden = true;
        const button = event.submitter || form.querySelector('button[type="submit"]');
        if (form.matches('[data-share-form]')) form.action = form.querySelector('select').selectedOptions[0].dataset.sendUrl;
        button.disabled = true;
        try {
            const response = await fetch(form.action, {method: 'POST', body: new FormData(form), headers: {'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content}});
            const data = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(Object.values(data.errors || {})[0]?.[0] || data.message || 'Message could not be sent.');
            if (data.show_url && new URL(data.show_url).pathname !== location.pathname) { location.assign(data.show_url); return; }
            if (data.fetch_url) box.dataset.fetch = data.fetch_url;
            if (form.classList.contains('mc-composer')) form.reset();
            await refresh(); box.scrollTop = box.scrollHeight;
        } catch (problem) { error.textContent = problem.message || 'Message could not be sent.'; error.hidden = false; }
        finally { button.disabled = false; }
    }));
    setInterval(refresh, 1000);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) refresh(); });
})();
</script>
