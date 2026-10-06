{{--
    Buyer order detail.
    Forms and field names are unchanged: orders.cancel (expected_status, reason, reason_details),
    orders.complete, reviews.store (rating, comment).

    Cancel rule shown here: a buyer can cancel only while the order is Placed or Confirmed. Once the seller starts
    Preparing, the button is replaced by an explanation. The server still decides by
    OrderCancellationService::CANCELLABLE_STATUSES, so that constant must change too (see the backend note),
    otherwise a hand-made request could still cancel a Preparing order.
--}}
@php
    use App\Models\Ecommerce\Order;
    use Illuminate\Support\Str;

    $fmt = fn ($amount) => '₱' . number_format($amount, 2);
    $buyerCancellable = ['placed', 'confirmed'];
    $canBuyerCancel = in_array($order->status, $buyerCancellable, true);
    $cancelLocked = ! $canBuyerCancel && ! in_array($order->status, ['completed', 'cancelled', 'returned', 'delivery_failed', 'delivered'], true);
    $reopenCancelModal = $errors->has('reason') || $errors->has('reason_details');

    $seller = $order->seller;
    $shopName = $seller?->sellerDetail?->business_name ?: ($seller?->name ?? 'Vendo shop');
    $label = Order::STATUSES[$order->status] ?? Str::headline($order->status);
    $itemsTotal = $order->items->sum(fn ($item) => $item->quantity * $item->price);

    $steps = ['placed', 'confirmed', 'preparing', 'ready_for_pickup', 'picked_up', 'at_sorting_center', 'sorted'];
    $socRoute = $order->scanEvents()->whereIn('scan_type', ['soc5', 'soc6', 'destination_hub'])->exists()
        || ($order->logistics_center_id && $order->destination_logistics_center_id
            && $order->logistics_center_id !== $order->destination_logistics_center_id
            && ! in_array($order->status, ['in_transit_to_hub', 'at_destination_hub', 'assigned_to_rider', 'out_for_delivery', 'delivered', 'completed'], true));
    if ($socRoute) {
        array_push($steps, 'to_soc5', 'to_soc6');
    }
    if ($order->logistics_center_id !== $order->destination_logistics_center_id) {
        array_push($steps, 'in_transit_to_hub', 'at_destination_hub');
    }
    array_push($steps, 'assigned_to_rider', 'out_for_delivery', 'delivered', 'completed');
    $currentIndex = array_search($order->status, $steps);
    $isTerminalIssue = in_array($order->status, ['cancelled', 'returned', 'delivery_failed'], true);

    $badge = match (true) {
        in_array($order->status, ['cancelled', 'delivery_failed'], true) => 'bg-[#fdf1f3] text-[#a32b43]',
        $order->status === 'returned' => 'bg-[#f3eef4] text-[#6d5d71]',
        in_array($order->status, ['delivered', 'completed'], true) => 'bg-[#eaf5ee] text-[#2e6b46]',
        $order->status === 'placed' => 'bg-[#fbf3dc] text-[#7a5a0c]',
        default => 'bg-[#f5ecf6] text-[#52245b]',
    };
    $card = 'rounded-lg border border-[#eee6ef] bg-white';
    $cardHead = 'rounded-t-lg bg-[#f6f2f7] px-4 py-3 text-[15px] font-semibold text-[#402143] sm:px-5';
    $field = 'mt-1 block w-full rounded-md border-[#e5dce7] bg-white px-3 py-2 text-[13px] text-[#2b1730] focus:border-[#805487] focus:ring-[#805487]';
@endphp

<x-buyer.layout :title="'Order ' . ($order->number ?? ('#' . $order->id)) . ' | Vendo'">
    <div class="vb-enter mx-auto max-w-[1100px] px-3 pb-14 pt-4 sm:px-4"
        x-data="{ cancelOpen: @js($reopenCancelModal), reason: @js(old('reason', '')) }">

        <a href="{{ route('buyer.orders.index') }}" class="inline-flex items-center gap-1 text-[13px] font-medium text-[#805487] transition-colors duration-200 hover:text-[#402143]">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 6-6 6 6 6" /></svg>
            My orders
        </a>

        <div class="mt-2 flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-[20px] font-semibold text-[#2b1730]">Order {{ $order->number ?? ('#' . $order->id) }}</h1>
                <span class="rounded-full px-3 py-1 text-[12px] font-medium {{ $badge }}">{{ $label }}</span>
            </div>
            @if ($canBuyerCancel)
                <button type="button" @click="cancelOpen = true"
                    class="inline-flex h-9 items-center rounded-md border border-[#e3b5be] px-4 text-[13px] font-medium text-[#a32b43] transition-colors duration-200 hover:bg-[#fdf1f3]">Cancel order</button>
            @endif
        </div>
        <p class="mt-1 text-[12px] text-[#7a6a7e]">Placed on {{ $order->created_at->format('M j, Y g:i A') }}</p>

        @if (session('success'))
            <p class="mt-3 rounded-md border border-[#c9e5d3] bg-[#eaf5ee] px-3 py-2.5 text-[13px] text-[#2e6b46]" role="status">{{ session('success') }}</p>
        @endif
        @if ($errors->any())
            <p class="mt-3 rounded-md border border-[#f0c9d0] bg-[#fdf1f3] px-3 py-2.5 text-[13px] text-[#a32b43]" role="alert">{{ $errors->first() }}</p>
        @endif

        @if ($cancelLocked)
            <div class="mt-3 flex items-start gap-3 rounded-lg border border-[#eadfec] bg-[#faf5fa] px-4 py-3 text-[13px] text-[#5b4a60]">
                <svg class="mt-0.5 h-[18px] w-[18px] flex-shrink-0 text-[#805487]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="11" width="14" height="9" rx="2" /><path d="M8 11V8a4 4 0 0 1 8 0v3" /></svg>
                <p>This order can no longer be cancelled because the seller has started preparing it. If something is wrong, <a href="{{ route('buyer.marketplace-messages.show', $order) }}" class="font-medium text-[#52245b] underline underline-offset-2">chat with the seller</a>.</p>
            </div>
        @endif

        <!-- Progress -->
        @if ($isTerminalIssue)
            <div class="{{ $card }} mt-3 px-4 py-4 text-[13px] text-[#5b4a60] sm:px-5">
                This order is <strong class="text-[#2b1730]">{{ Str::lower($label) }}</strong>.
                @if ($order->status === 'cancelled') Reserved stock was returned to the seller. @endif
            </div>
        @else
            <section class="{{ $card }} mt-3 overflow-x-auto px-4 py-5 sm:px-5" aria-label="Order progress">
                <ol class="flex items-start" style="min-width: {{ max(640, count($steps) * 84) }}px">
                    @foreach ($steps as $i => $step)
                        @php $done = $currentIndex !== false && $i <= $currentIndex; $now = $currentIndex !== false && $i === $currentIndex; @endphp
                        <li class="relative flex flex-1 flex-col items-center text-center" @if ($now) aria-current="step" @endif>
                            @if ($i < count($steps) - 1)
                                <span class="absolute left-1/2 top-3 h-0.5 w-full {{ $currentIndex !== false && $i < $currentIndex ? 'bg-[#805487]' : 'bg-[#eadfec]' }}" aria-hidden="true"></span>
                            @endif
                            <span class="relative z-10 grid h-6 w-6 place-items-center rounded-full text-[11px] font-semibold {{ $done ? 'bg-[#805487] text-white' : 'bg-[#eadfec] text-[#9a8a9d]' }} {{ $now ? 'ring-4 ring-[#e9d8ec]' : '' }}">
                                @if ($done && ! $now)
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 5 5 9-10" /></svg>
                                @else
                                    {{ $i + 1 }}
                                @endif
                            </span>
                            @if ($step === 'completed' && $order->status === 'completed')
                                <a href="#rate-products" class="mt-2 px-1 text-[11px] font-medium text-[#52245b] underline underline-offset-2">Rate products</a>
                            @else
                                <span class="mt-2 px-1 text-[11px] leading-tight {{ $done ? 'font-medium text-[#2b1730]' : 'text-[#9a8a9d]' }}">{{ $step === 'completed' ? 'Rate products' : (Order::STATUSES[$step] ?? Str::headline($step)) }}</span>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </section>
        @endif

        <div class="mt-3 grid items-start gap-3 lg:grid-cols-[minmax(0,1fr)_320px]">
            <div class="min-w-0 space-y-3">

                <!-- Items -->
                <section id="rate-products" class="{{ $card }} scroll-mt-32">
                    <h2 class="{{ $cardHead }}">Items in this order</h2>
                    <ul class="divide-y divide-[#f1e8f2] px-4 sm:px-5">
                        @foreach ($order->items as $item)
                            @php
                                $image = $item->product?->images?->first();
                                $options = array_filter([$item->variant?->label, $item->color ? 'Color: ' . $item->color : null, $item->size ? 'Size: ' . $item->size : null]);
                            @endphp
                            <li class="py-4">
                                <div class="flex gap-3">
                                    <span class="h-16 w-16 flex-shrink-0 overflow-hidden rounded-md border border-[#eee6ef] bg-[#faf7fb]">
                                        @if ($image)<img src="{{ asset('storage/' . $image->path) }}" alt="" loading="lazy" class="h-full w-full object-cover">@endif
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        @if ($item->product)
                                            <a href="{{ route('buyer.products.show', $item->product) }}" class="line-clamp-2 text-[13px] leading-[18px] text-[#2b1730] transition-colors duration-200 hover:text-[#805487]">{{ $item->product->name }}</a>
                                        @else
                                            <span class="text-[13px] text-[#7a6a7e]">Product no longer available</span>
                                        @endif
                                        @if ($options)<p class="mt-0.5 text-[12px] text-[#8a7a8e]">{{ implode(', ', $options) }}</p>@endif
                                        <p class="mt-1 text-[12px] text-[#7a6a7e]">{{ $fmt($item->price) }} each, quantity {{ $item->quantity }}</p>
                                        <a href="{{ route('buyer.marketplace-messages.show', ['order' => $order, 'item' => $item->id]) }}" class="mt-1.5 inline-block text-[12px] font-medium text-[#805487] underline-offset-2 hover:underline">Ask about this item</a>
                                    </div>
                                    <p class="flex-shrink-0 text-[13px] font-semibold text-[#52245b]">{{ $fmt($item->quantity * $item->price) }}</p>
                                </div>

                                @if ($order->status === 'completed')
                                    @if ($item->review)
                                        <div class="mt-3 rounded-md bg-[#f6f2f7] p-3 text-[13px]">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="font-medium text-[#402143]">Your review</span>
                                                <x-buyer.stars :rating="$item->review->rating" />
                                            </div>
                                            <p class="mt-1 whitespace-pre-line text-[#3d2a42]">{{ $item->review->comment }}</p>
                                            @if ($item->review->visibility === 'hidden')<p class="mt-2 text-[12px] text-[#7a6a7e]">This review is hidden while it is being reviewed.</p>@endif
                                            @if ($item->review->reply)
                                                <div class="mt-2 border-l-2 border-[#805487] pl-3"><p class="font-medium text-[#402143]">Seller reply</p><p class="text-[#5b4a60]">{{ $item->review->reply->body }}</p></div>
                                            @endif
                                        </div>
                                    @else
                                        <form action="{{ route('buyer.reviews.store', $item) }}" method="POST" class="mt-3 rounded-md bg-[#f6f2f7] p-3" data-draft-key="buyer-{{ auth()->id() }}-review-{{ $item->id }}">
                                            @csrf
                                            <label class="block text-[13px] font-medium text-[#402143]">Rate this product
                                                <select name="rating" required class="{{ $field }} sm:w-48">
                                                    <option value="">Choose a rating</option>
                                                    @foreach ([5 => '5 stars', 4 => '4 stars', 3 => '3 stars', 2 => '2 stars', 1 => '1 star'] as $value => $starLabel)
                                                        <option value="{{ $value }}" @selected(old('rating') == $value)>{{ $starLabel }}</option>
                                                    @endforeach
                                                </select>
                                            </label>
                                            <label class="mt-3 block text-[13px] font-medium text-[#402143]">Your review
                                                <textarea name="comment" required minlength="10" maxlength="2000" rows="3" class="{{ $field }}" placeholder="Tell other buyers what you think of this product">{{ old('comment') }}</textarea>
                                            </label>
                                            <button type="submit" class="mt-3 inline-flex h-9 items-center rounded-md bg-[#402143] px-4 text-[13px] font-medium text-white transition-colors duration-200 hover:bg-[#52245b]">Submit review</button>
                                        </form>
                                    @endif
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </section>

                <!-- Updates -->
                <section class="{{ $card }}">
                    <h2 class="{{ $cardHead }}">Order updates</h2>
                    @if ($order->statusEvents->isNotEmpty())
                        <ol class="space-y-0 px-4 py-4 sm:px-5">
                            @foreach ($order->statusEvents->sortByDesc('created_at') as $event)
                                <li class="relative flex gap-3 pb-4 last:pb-0">
                                    @unless ($loop->last)<span class="absolute left-[5px] top-4 h-full w-px bg-[#eadfec]" aria-hidden="true"></span>@endunless
                                    <span class="relative z-10 mt-1.5 h-[11px] w-[11px] flex-shrink-0 rounded-full {{ $loop->first ? 'bg-[#805487] ring-4 ring-[#e9d8ec]' : 'bg-[#d8c4dc]' }}" aria-hidden="true"></span>
                                    <div class="min-w-0 text-[13px]">
                                        <p class="font-medium text-[#2b1730]">{{ Order::STATUSES[$event->to_status] ?? Str::headline($event->to_status) }}</p>
                                        <p class="text-[12px] text-[#8a7a8e]">{{ $event->created_at->format('M j, Y g:i A') }}</p>
                                        @if (filled($event->note))<p class="mt-0.5 text-[#5b4a60]">{{ $event->note }}</p>@endif
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    @else
                        <x-buyer.empty-state icon="orders" compact title="No updates yet" text="Updates appear here when the seller or logistics changes the order." />
                    @endif
                </section>
            </div>

            <aside class="space-y-3 lg:sticky lg:top-[124px]">
                @if ($order->status === 'delivered')
                    <form action="{{ route('buyer.orders.complete', $order) }}" method="POST" class="{{ $card }} p-4">
                        @csrf
                        <p class="text-[13px] text-[#5b4a60]">Your order was delivered. Confirm that you received it to finish the order and rate your items.</p>
                        <button class="mt-3 inline-flex h-10 w-full items-center justify-center rounded-md bg-[#2e6b46] text-[13px] font-medium text-white transition-colors duration-200 hover:bg-[#265a3b]">Confirm order received</button>
                    </form>
                @endif

                <section class="{{ $card }}">
                    <h2 class="{{ $cardHead }}">Order summary</h2>
                    <dl class="space-y-2.5 p-4 text-[13px] sm:p-5">
                        <div class="flex justify-between gap-3"><dt class="text-[#7a6a7e]">Items</dt><dd>{{ $fmt($itemsTotal) }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-[#7a6a7e]">Shipping fee</dt><dd>{{ $fmt($order->shipping_fee ?? 0) }}</dd></div>
                        <div class="flex items-baseline justify-between gap-3 border-t border-[#f1e8f2] pt-3"><dt class="font-semibold text-[#2b1730]">Total</dt><dd class="text-[20px] font-semibold leading-none text-[#52245b]">{{ $fmt($order->total_amount) }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-[#7a6a7e]">Payment</dt><dd>Cash on delivery</dd></div>
                    </dl>
                </section>

                <section class="{{ $card }}">
                    <h2 class="{{ $cardHead }}">Delivery address</h2>
                    <p class="break-words p-4 text-[13px] leading-6 text-[#3d2a42] sm:p-5">{{ $order->shipping_address }}</p>
                </section>

                @if ($seller)
                    <section class="{{ $card }}">
                        <h2 class="{{ $cardHead }}">Sold by</h2>
                        <div class="p-4 sm:p-5">
                            <a href="{{ route('buyer.sellers.show', $seller) }}" class="text-[14px] font-semibold text-[#2b1730] transition-colors duration-200 hover:text-[#805487]">{{ $shopName }}</a>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <a href="{{ route('buyer.marketplace-messages.show', $order) }}" class="inline-flex h-9 items-center rounded-md border border-[#805487] px-3.5 text-[13px] font-medium text-[#52245b] transition-colors duration-200 hover:bg-[#f5ecf6]">Chat with seller</a>
                                <a href="{{ route('buyer.sellers.show', $seller) }}" class="inline-flex h-9 items-center rounded-md border border-[#e5dce7] px-3.5 text-[13px] font-medium text-[#3d2a42] transition-colors duration-200 hover:bg-[#faf5fa]">View shop</a>
                            </div>
                        </div>
                    </section>
                @endif

                <section class="{{ $card }}">
                    <h2 class="{{ $cardHead }}">Shipment tracking</h2>
                    <dl class="space-y-2 p-4 text-[13px] sm:p-5">
                        <div><dt class="text-[12px] text-[#8a7a8e]">Vendo reference</dt><dd class="break-all text-[#2b1730]">{{ $order->tracking_number }}</dd></div>
                        @if ($order->carrier_name)<div><dt class="text-[12px] text-[#8a7a8e]">Carrier</dt><dd class="text-[#2b1730]">{{ $order->carrier_name }}</dd></div>@endif
                        @if ($order->carrier_tracking_number)<div><dt class="text-[12px] text-[#8a7a8e]">Carrier tracking</dt><dd class="break-all text-[#2b1730]">{{ $order->carrier_tracking_number }}</dd></div>@endif
                        <div><dt class="text-[12px] text-[#8a7a8e]">Estimated delivery</dt>
                            <dd class="text-[#2b1730]">@if ($order->estimated_delivery_from && $order->estimated_delivery_to){{ $order->estimated_delivery_from->format('M j, Y') }} to {{ $order->estimated_delivery_to->format('M j, Y') }}@else Not yet scheduled @endif</dd></div>
                    </dl>
                </section>

                @if (! in_array($order->status, ['completed', 'cancelled', 'returned', 'delivery_failed'], true))
                    <p class="px-1 text-[12px] text-[#8a7a8e]">You can rate each product after you confirm that you received the order.</p>
                @endif
            </aside>
        </div>

        <!-- Cancel dialog (only rendered while the order can still be cancelled) -->
        @if ($canBuyerCancel)
            <div x-show="cancelOpen" x-cloak class="fixed inset-0 z-[100] flex items-end justify-center sm:items-center sm:p-4"
                @keydown.escape.window="cancelOpen = false" x-effect="document.body.style.overflow = cancelOpen ? 'hidden' : ''">
                <div x-show="cancelOpen" @click="cancelOpen = false"
                    x-transition:enter="transition-opacity duration-300 ease-vendo" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                    x-transition:leave="transition-opacity duration-200 ease-vendo" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                    class="absolute inset-0 bg-[#1b0b1e]/50"></div>
                <section x-show="cancelOpen" role="dialog" aria-modal="true" aria-labelledby="buyer-cancel-title"
                    x-transition:enter="transition duration-300 ease-vendo" x-transition:enter-start="translate-y-6 opacity-0 sm:scale-95" x-transition:enter-end="translate-y-0 opacity-100 sm:scale-100"
                    x-transition:leave="transition duration-200 ease-vendo" x-transition:leave-start="translate-y-0 opacity-100 sm:scale-100" x-transition:leave-end="translate-y-6 opacity-0 sm:scale-95"
                    class="relative w-full rounded-t-2xl bg-white p-5 shadow-2xl sm:max-w-md sm:rounded-2xl sm:p-6">
                    <h2 id="buyer-cancel-title" class="text-[16px] font-semibold text-[#2b1730]">Cancel this order?</h2>
                    <p class="mt-1 text-[13px] text-[#5b4a60]">You can cancel until the seller starts preparing your order. After that, it cannot be cancelled. Choose a reason to continue.</p>
                    <form action="{{ route('buyer.orders.cancel', $order) }}" method="POST" class="mt-4 space-y-4">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="expected_status" value="{{ $order->status }}">
                        <label class="block text-[13px] font-medium text-[#402143]">Reason
                            <select name="reason" x-model="reason" required class="{{ $field }}">
                                <option value="">Choose a reason</option>
                                @foreach (\App\Services\OrderCancellationService::BUYER_REASONS as $key => $reasonLabel)
                                    <option value="{{ $key }}">{{ $reasonLabel }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label x-show="reason === 'other'" x-cloak class="block text-[13px] font-medium text-[#402143]">Tell us more
                            <textarea name="reason_details" maxlength="450" rows="3" :required="reason === 'other'" class="{{ $field }}">{{ old('reason_details') }}</textarea>
                        </label>
                        <div class="flex justify-end gap-2.5">
                            <button type="button" @click="cancelOpen = false" class="inline-flex h-10 items-center rounded-md border border-[#e5dce7] px-4 text-[13px] font-medium text-[#3d2a42] transition-colors duration-200 hover:bg-[#faf5fa]">Keep order</button>
                            <button type="submit" class="inline-flex h-10 items-center rounded-md bg-[#a32b43] px-4 text-[13px] font-medium text-white transition-colors duration-200 hover:bg-[#8c2438]">Cancel order</button>
                        </div>
                    </form>
                </section>
            </div>
        @endif
    </div>
    @include('shared.live-revision', ['endpoint' => route('buyer.live', 'orders'), 'mode' => 'reload'])
</x-buyer.layout>