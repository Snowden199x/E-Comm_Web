{{--
    My Orders.
    Works with what OrderController@index already passes ($orders: all of the buyer's orders, newest first).
    Tabs filter in the browser, so there is no extra request. Product photos and the shop name are read lazily
    here; see the backend note about eager loading items.product.images and seller.sellerDetail.
--}}
@php
    use App\Models\Ecommerce\Order;
    use Illuminate\Support\Str;

    $fmt = fn ($amount) => '₱' . number_format($amount, 2);

    // Tabs -> the order statuses each one covers
    $tabs = [
        'all' => ['label' => 'All', 'statuses' => null],
        'placed' => ['label' => 'Placed', 'statuses' => ['placed']],
        'to_ship' => ['label' => 'To ship', 'statuses' => Order::SHIPMENT_GROUPS['to_ship']],
        'in_transit' => ['label' => 'In transit', 'statuses' => Order::SHIPMENT_GROUPS['in_transit']],
        'delivered' => ['label' => 'Delivered', 'statuses' => Order::SHIPMENT_GROUPS['delivered']],
        'cancelled' => ['label' => 'Cancelled and returned', 'statuses' => ['cancelled', 'returned']],
    ];
    $tabOf = function (string $status) use ($tabs) {
        foreach ($tabs as $key => $tab) {
            if ($tab['statuses'] && in_array($status, $tab['statuses'], true)) return $key;
        }
        return 'all';
    };
    $counts = collect($tabs)->map(fn ($tab, $key) => $key === 'all' ? $orders->count() : $orders->filter(fn ($o) => $tabOf($o->status) === $key)->count());

    $badge = function (string $status) {
        return match (true) {
            $status === 'cancelled', $status === 'delivery_failed' => 'bg-[#fdf1f3] text-[#a32b43]',
            $status === 'returned' => 'bg-[#f3eef4] text-[#6d5d71]',
            in_array($status, ['delivered', 'completed'], true) => 'bg-[#eaf5ee] text-[#2e6b46]',
            $status === 'placed' => 'bg-[#fbf3dc] text-[#7a5a0c]',
            default => 'bg-[#f5ecf6] text-[#52245b]',
        };
    };
@endphp

<x-buyer.layout title="My Orders | Vendo">
    <div class="vb-enter mx-auto max-w-[1000px] px-3 pb-14 pt-4 sm:px-4" x-data="{ tab: 'all' }">

        <div class="flex flex-wrap items-end justify-between gap-2">
            <div>
                <h1 class="text-[20px] font-semibold text-[#2b1730]">My orders</h1>
                <p class="mt-0.5 text-[13px] text-[#7a6a7e]">Track your orders, cancel them before the seller starts preparing, and rate what you receive.</p>
            </div>
        </div>

        @if (session('success'))
            <p class="mt-3 rounded-md border border-[#c9e5d3] bg-[#eaf5ee] px-3 py-2.5 text-[13px] text-[#2e6b46]" role="status">{{ session('success') }}</p>
        @endif

        @if ($orders->isEmpty())
            <div class="mt-4 rounded-lg border border-[#eee6ef] bg-white">
                <x-buyer.empty-state icon="orders" title="You have no orders yet" text="When you place an order it shows up here, with live updates as the seller and logistics move it along.">
                    <a href="{{ route('buyer.products.index') }}" class="inline-flex h-10 items-center rounded-md bg-[#402143] px-5 text-[13px] font-medium text-white transition-colors duration-200 hover:bg-[#52245b]">Start shopping</a>
                </x-buyer.empty-state>
            </div>
        @else
            <!-- Status tabs -->
            <div class="vb-no-scrollbar mt-4 flex gap-1 overflow-x-auto rounded-lg border border-[#eee6ef] bg-white p-1" role="tablist" aria-label="Order status">
                @foreach ($tabs as $key => $tab)
                    <button type="button" role="tab" @click="tab = '{{ $key }}'" :aria-selected="tab === '{{ $key }}'"
                        :class="tab === '{{ $key }}' ? 'bg-[#402143] text-white' : 'text-[#5b4a60] hover:bg-[#f5ecf6]'"
                        class="flex h-9 flex-shrink-0 items-center gap-1.5 rounded-md px-3.5 text-[13px] font-medium transition-colors duration-200 ease-vendo">
                        {{ $tab['label'] }}
                        @if ($counts[$key])
                            <span class="rounded-full px-1.5 text-[11px] leading-[18px]" :class="tab === '{{ $key }}' ? 'bg-white/20' : 'bg-[#f0e9f2]'">{{ $counts[$key] }}</span>
                        @endif
                    </button>
                @endforeach
            </div>

            <div class="mt-3 space-y-3">
                @foreach ($orders as $order)
                    @php
                        $seller = $order->seller;
                        $shopName = $seller?->sellerDetail?->business_name ?: ($seller?->name ?? 'Vendo shop');
                        $label = Order::STATUSES[$order->status] ?? Str::headline($order->status);
                        $group = $tabOf($order->status);
                        $shown = $order->items->take(2);
                        $more = $order->items->count() - $shown->count();
                        $orderNo = $order->number ?? ('#' . $order->id);
                    @endphp
                    <article x-show="tab === 'all' || tab === '{{ $group }}'" x-transition.opacity.duration.200ms
                        class="rounded-lg border border-[#eee6ef] bg-white">
                        <header class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2 border-b border-[#f1e8f2] px-4 py-3 sm:px-5">
                            <div class="flex min-w-0 items-center gap-2 text-[13px]">
                                <svg class="h-4 w-4 flex-shrink-0 text-[#805487]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 9l1.6-5h14.8L21 9" /><path d="M3 9a3 3 0 0 0 6 0a3 3 0 0 0 6 0a3 3 0 0 0 6 0" /><path d="M5 12v8h14v-8" /></svg>
                                @if ($seller)
                                    <a href="{{ route('buyer.sellers.show', $seller) }}" class="truncate font-semibold text-[#402143] transition-colors duration-200 hover:text-[#805487]">{{ $shopName }}</a>
                                @else
                                    <span class="truncate font-semibold text-[#402143]">{{ $shopName }}</span>
                                @endif
                            </div>
                            <div class="flex items-center gap-3 text-[12px] text-[#7a6a7e]">
                                <span>Order {{ $orderNo }}</span>
                                <span class="rounded-full px-2.5 py-0.5 font-medium {{ $badge($order->status) }}">{{ $label }}</span>
                            </div>
                        </header>

                        <a href="{{ route('buyer.orders.show', $order) }}" class="block px-4 py-3 transition-colors duration-200 hover:bg-[#fcfafc] sm:px-5">
                            <ul class="space-y-3">
                                @foreach ($shown as $item)
                                    @php
                                        $image = $item->product?->images?->first();
                                        $options = array_filter([$item->variant?->label, $item->color ? 'Color: ' . $item->color : null, $item->size ? 'Size: ' . $item->size : null]);
                                    @endphp
                                    <li class="flex gap-3">
                                        <span class="h-14 w-14 flex-shrink-0 overflow-hidden rounded-md border border-[#eee6ef] bg-[#faf7fb]">
                                            @if ($image)<img src="{{ asset('storage/' . $image->path) }}" alt="" loading="lazy" class="h-full w-full object-cover">@endif
                                        </span>
                                        <span class="min-w-0 flex-1">
                                            <span class="line-clamp-1 block text-[13px] text-[#2b1730]">{{ $item->product?->name ?? 'Product no longer available' }}</span>
                                            @if ($options)<span class="mt-0.5 block text-[12px] text-[#8a7a8e]">{{ implode(', ', $options) }}</span>@endif
                                            <span class="mt-0.5 block text-[12px] text-[#7a6a7e]">Quantity {{ $item->quantity }}</span>
                                        </span>
                                        <span class="flex-shrink-0 text-[13px] text-[#52245b]">{{ $fmt($item->price) }}</span>
                                    </li>
                                @endforeach
                            </ul>
                            @if ($more > 0)
                                <p class="mt-3 text-[12px] text-[#805487]">and {{ $more }} more {{ Str::plural('item', $more) }}</p>
                            @endif
                        </a>

                        <footer class="flex flex-wrap items-center justify-between gap-3 border-t border-[#f1e8f2] px-4 py-3 sm:px-5">
                            <p class="text-[13px] text-[#5b4a60]">{{ $order->items->sum('quantity') }} {{ Str::plural('item', $order->items->sum('quantity')) }}, total
                                <span class="ml-1 text-[16px] font-semibold text-[#52245b]">{{ $fmt($order->total_amount) }}</span>
                            </p>
                            <div class="flex flex-wrap gap-2">
                                @if ($seller)
                                    <a href="{{ route('buyer.marketplace-messages.show', $order) }}"
                                        class="inline-flex h-9 items-center rounded-md border border-[#e5dce7] px-3.5 text-[13px] font-medium text-[#3d2a42] transition-colors duration-200 hover:border-[#c9a9ce] hover:bg-[#faf5fa]">Chat with seller</a>
                                @endif
                                <a href="{{ route('buyer.orders.show', $order) }}"
                                    class="inline-flex h-9 items-center rounded-md px-4 text-[13px] font-medium text-white transition-colors duration-200 {{ $order->status === 'delivered' ? 'bg-[#2e6b46] hover:bg-[#265a3b]' : 'bg-[#402143] hover:bg-[#52245b]' }}">
                                    {{ $order->status === 'delivered' ? 'Confirm receipt' : ($order->status === 'completed' ? 'View and rate' : 'View details') }}
                                </a>
                            </div>
                        </footer>
                    </article>
                @endforeach
            </div>

            <!-- Empty tab -->
            @foreach ($tabs as $key => $tab)
                @if ($key !== 'all' && ! $counts[$key])
                    <div x-show="tab === '{{ $key }}'" x-cloak class="mt-3 rounded-lg border border-[#eee6ef] bg-white">
                        <x-buyer.empty-state icon="orders" compact title="No orders in {{ Str::lower($tab['label']) }}" text="Orders move here automatically as their status changes." />
                    </div>
                @endif
            @endforeach
        @endif
    </div>
    @include('shared.live-revision', ['endpoint' => route('buyer.live', 'orders'), 'mode' => 'reload'])
</x-buyer.layout>