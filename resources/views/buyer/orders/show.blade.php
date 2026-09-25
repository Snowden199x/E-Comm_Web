<x-buyer.layout>
    <div class="max-w-3xl mx-auto p-4 sm:p-5 lg:p-6">
        <h2 class="text-2xl font-bold text-gray-900 mb-2">Order #{{ $order->id }}</h2>
        @if($order->seller)
            <p class="mb-4 text-sm text-gray-600">Seller: <a href="{{ route('buyer.sellers.show', $order->seller) }}" class="font-semibold text-[#5b2963] hover:underline">{{ $order->seller->sellerDetail?->business_name ?: $order->seller->name }}</a></p>
        @endif

        @php
            $steps = ['placed','confirmed','preparing','ready_for_pickup','picked_up','at_sorting_center','sorted','assigned_to_rider','out_for_delivery','delivered','completed'];
            $currentIndex = array_search($order->status, $steps);
            $isTerminalIssue = in_array($order->status, ['cancelled', 'returned', 'delivery_failed']);
        @endphp

        @if ($isTerminalIssue)
            <span class="inline-block text-xs px-2 py-1 rounded-full bg-red-100 text-red-700 capitalize mb-6">{{ $order->status }}</span>
        @else
            <div class="bg-white rounded-2xl p-5 shadow-sm mb-4 overflow-x-auto">
                <div class="flex items-center min-w-[980px]">
                    @foreach ($steps as $i => $step)
                        <div class="flex-1 flex flex-col items-center relative">
                            <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold z-10
                                {{ $i <= $currentIndex ? 'bg-[#3b1735] text-white' : 'bg-gray-200 text-gray-400' }}">
                                {{ $i + 1 }}
                            </div>
                            <span class="text-[0.65rem] text-center mt-2 capitalize {{ $i <= $currentIndex ? 'text-gray-900 font-medium' : 'text-gray-400' }}">
                                {{ str_replace('_', ' ', $step) }}
                            </span>
                            @if ($i < count($steps) - 1)
                                <div class="absolute top-3 left-1/2 w-full h-0.5 {{ $i < $currentIndex ? 'bg-[#3b1735]' : 'bg-gray-200' }}"></div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if (session('success'))<p class="mb-4 text-green-700">{{ session('success') }}</p>@endif
        @if ($errors->any())<p class="mb-4 text-sm text-red-700" role="alert">{{ $errors->first() }}</p>@endif
        @if($order->seller)
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-white p-5 shadow-sm">
                <div><p class="text-xs text-gray-500">Sold by</p><p class="font-semibold text-gray-900">{{ $order->seller->sellerDetail?->business_name ?? $order->seller->name }}</p></div>
                <a href="{{ route('buyer.marketplace-messages.show', $order) }}" class="rounded-lg border border-[#3b1735] px-4 py-2 text-sm font-semibold text-[#3b1735] hover:bg-[#f7eef6]">Chat with seller</a>
            </div>
        @endif
        @if ($order->statusEvents->isNotEmpty())
            <div class="bg-white rounded-2xl p-5 shadow-sm mb-4"><h3 class="font-semibold mb-2">Order updates</h3>
                @foreach ($order->statusEvents as $event)
                    <p class="text-sm mb-2"><strong>{{ \App\Models\Ecommerce\Order::STATUSES[$event->to_status] ?? $event->to_status }}</strong> · {{ $event->created_at->format('M j, Y g:i A') }}<br>{{ $event->note }}</p>
                @endforeach
            </div>
        @endif
        <div class="bg-white rounded-2xl p-5 shadow-sm">
            @foreach ($order->items as $item)
                <div class="py-3 border-b last:border-0">
                    <div class="flex justify-between text-sm">
                        <span>{{ $item->product?->name ?? 'Product unavailable' }} {{ $item->color }} {{ $item->size }} x{{ $item->quantity }}</span>
                        <span>₱{{ number_format($item->quantity * $item->price, 2) }}</span>
                    </div>
                    <a href="{{ route('buyer.marketplace-messages.show', ['order' => $order, 'item' => $item->id]) }}" class="mt-2 inline-block text-xs font-semibold text-[#6a3373] hover:underline">Ask about item</a>
                    @if ($order->status === 'completed')
                        @if ($item->review)
                            <div class="mt-3 rounded-lg bg-gray-50 p-3 text-sm">
                                <p class="font-medium text-gray-900">Your review · <span class="text-amber-500" aria-label="{{ $item->review->rating }} out of 5 stars">{{ str_repeat('★', $item->review->rating) }}{{ str_repeat('☆', 5 - $item->review->rating) }}</span></p>
                                <p class="mt-1 text-gray-700">{{ $item->review->comment }}</p>
                                @if ($item->review->visibility === 'hidden')<p class="mt-2 text-xs text-gray-500">This review is hidden while it is being reviewed.</p>@endif
                                @if ($item->review->reply)<div class="mt-2 border-l-2 border-[#3b1735] pl-3"><p class="font-medium text-gray-800">Seller reply</p><p>{{ $item->review->reply->body }}</p></div>@endif
                            </div>
                        @else
                            <form action="{{ route('buyer.reviews.store', $item) }}" method="POST" class="mt-3 rounded-lg bg-gray-50 p-3">
                                @csrf
                                <label class="block text-sm font-medium text-gray-800">Rate this product
                                    <select name="rating" required class="mt-1 block rounded border-gray-300 text-sm">
                                        <option value="">Choose a rating</option>
                                        @foreach ([5 => '5 stars', 4 => '4 stars', 3 => '3 stars', 2 => '2 stars', 1 => '1 star'] as $value => $label)
                                            <option value="{{ $value }}" @selected(old('rating') == $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <label class="mt-3 block text-sm font-medium text-gray-800">Your review
                                    <textarea name="comment" required minlength="10" maxlength="2000" rows="3" class="mt-1 block w-full rounded border-gray-300 text-sm" placeholder="Share details about the product">{{ old('comment') }}</textarea>
                                </label>
                                <button type="submit" class="mt-3 rounded-lg bg-[#3b1735] px-4 py-2 text-sm font-medium text-white">Submit review</button>
                            </form>
                        @endif
                    @endif
                </div>
            @endforeach
            <div class="flex justify-between font-bold mt-3">
                <span>Total</span>
                <span>₱{{ number_format($order->total_amount, 2) }}</span>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 shadow-sm mt-4 text-sm text-gray-600">
            <p class="font-medium text-gray-900 mb-1">Delivery Address</p>
            <p>{{ $order->shipping_address }}</p>
        </div>

        <section class="bg-white rounded-2xl p-5 shadow-sm mt-4 text-sm text-gray-600">
            <h3 class="font-semibold text-gray-900 mb-2">Shipment Tracking</h3>
            <p>Vendo reference: {{ $order->tracking_number }}</p>
            @if($order->carrier_name)<p>Carrier: {{ $order->carrier_name }}</p>@endif
            @if($order->carrier_tracking_number)<p>Carrier tracking: {{ $order->carrier_tracking_number }}</p>@endif
            <p>Estimated delivery: @if($order->estimated_delivery_from && $order->estimated_delivery_to){{ $order->estimated_delivery_from->format('M j, Y') }} – {{ $order->estimated_delivery_to->format('M j, Y') }}@else Not yet scheduled @endif</p>
        </section>

        @if ($order->status === 'delivered')
            <form action="{{ route('buyer.orders.complete', $order) }}" method="POST" class="mt-4">
                @csrf
                <button class="w-full bg-[#3b1735] text-white py-3 rounded-lg font-medium hover:bg-[#4d1f45]">
                    Confirm Order Received
                </button>
            </form>
        @endif
        @if ($order->status !== 'completed')
            <p class="mt-4 text-sm text-gray-500">You can review each product after confirming that you received the order.</p>
        @endif
    </div>
@include('shared.live-revision', ['endpoint' => route('buyer.live', 'orders'), 'mode' => 'reload'])
</x-buyer.layout>
