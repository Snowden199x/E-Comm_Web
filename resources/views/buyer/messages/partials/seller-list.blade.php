{{--
    Seller chat list. Rendered inside #buyerSellerChats on the Messages page and re-fetched every ~1.5s
    from buyer.messages.seller-list, so this must stay a plain fragment (no layout, no scripts).
    Data: $sellerConversations (each with seller, order, latestMessage, unread_count).
--}}
@forelse ($sellerConversations as $sellerChat)
    @php
        $seller = $sellerChat->seller;
        $shopName = $seller?->name ?? 'Seller unavailable'; // the controller loads only id, name, profile_picture; do not read sellerDetail here (it would query on every refresh)
        $unread = (int) $sellerChat->unread_count;
        $latest = $sellerChat->latestMessage;
        $preview = $latest?->body ?: ($latest?->shared_order_id ? 'Order shared' : ($latest ? 'Photo' : 'No messages yet'));
        $when = $latest?->created_at?->diffForHumans(null, true, true);
        $url = $sellerChat->order_id
            ? route('buyer.marketplace-messages.show', $sellerChat->order_id)
            : route('buyer.marketplace-messages.seller.show', $sellerChat->seller_id);
    @endphp
    <a href="{{ $url }}"
        class="group relative flex items-center gap-3 border-b border-[#f3ecf4] px-4 py-3.5 transition-colors duration-200 last:border-0 hover:bg-[#faf5fa] {{ $unread ? 'bg-[#fbf6fc]' : 'bg-white' }}">
        @if ($unread)<span class="absolute left-0 top-0 h-full w-[3px] bg-[#805487]" aria-hidden="true"></span>@endif

        @if ($seller?->profile_picture)
            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($seller->profile_picture) }}" alt="" loading="lazy" class="h-11 w-11 flex-shrink-0 rounded-full border border-[#eee6ef] bg-white object-cover">
        @else
            <span class="grid h-11 w-11 flex-shrink-0 place-items-center rounded-full bg-[#805487] text-[15px] font-semibold text-white">{{ mb_strtoupper(mb_substr($shopName, 0, 1)) }}</span>
        @endif

        <span class="min-w-0 flex-1">
            <span class="flex items-baseline justify-between gap-2">
                <strong class="block truncate text-[13px] {{ $unread ? 'font-semibold text-[#2b1730]' : 'font-medium text-[#3d2a42]' }} transition-colors duration-200 group-hover:text-[#805487]">{{ $shopName }}</strong>
                @if ($when)<small class="flex-shrink-0 text-[11px] text-[#8a7a8e]">{{ $when }}</small>@endif
            </span>
            <span class="mt-0.5 block truncate text-[12px] leading-4 {{ $unread ? 'font-medium text-[#3d2a42]' : 'text-[#7a6a7e]' }}">{{ $preview }}</span>
            <span class="mt-1 inline-block max-w-full truncate rounded bg-[#f3eef4] px-1.5 text-[11px] leading-[18px] text-[#6d5d71]">{{ $sellerChat->order ? 'Order #' . $sellerChat->order->number : 'Shop inquiry' }}</span>
        </span>

        @if ($unread)
            <span class="grid h-5 min-w-[20px] flex-shrink-0 place-items-center rounded-full bg-[#402143] px-1.5 text-[11px] font-semibold leading-none text-white" aria-label="{{ $unread }} unread">{{ $unread > 99 ? '99+' : $unread }}</span>
        @endif
    </a>
@empty
    <div class="px-6 py-10 text-center">
        <span class="mx-auto grid h-12 w-12 place-items-center rounded-full bg-[#f3e8f5] text-[#805487]">
            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 5h16v11H9l-5 4z" /></svg>
        </span>
        <p class="mt-3 text-[13px] font-medium text-[#2b1730]">No seller chats yet</p>
        <p class="mx-auto mt-0.5 max-w-[240px] text-[12px] leading-5 text-[#7a6a7e]">Open an order and choose "Ask about item", or visit a shop and tap Chat, to start one.</p>
        <a href="{{ route('buyer.orders.index') }}" class="mt-4 inline-flex h-9 items-center rounded-md border border-[#805487] px-4 text-[12px] font-medium text-[#52245b] transition-colors duration-200 hover:bg-[#f5ecf6]">Go to My Orders</a>
    </div>
@endforelse