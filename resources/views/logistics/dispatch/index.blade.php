@php
    $pageTitle = match ($lane) {
        'incoming' => 'Incoming Parcel Management',
        'sorting' => 'Parcel Sorting',
        'delivery' => 'Delivery assignments',
        default => 'Dispatch',
    };
@endphp
<x-logistics.layout :title="$pageTitle">
    <div class="lg-page mx-auto max-w-6xl px-4 py-7 sm:px-6">
        <h2 class="text-2xl font-bold">{{ $pageTitle }}</h2>
        <p class="mt-1 text-sm text-gray-600">@switch($lane)
            @case('incoming') Assign pickup riders and receive parcels from local sellers. @break
            @case('sorting') Sort received parcels. The assigned pickup rider scans SOC5 and SOC6 in order for inter-hub parcels. @break
            @case('delivery') Confirm destination hub receipts and assign local delivery riders. @break
            @default Manage pickup, sorting, hub handoff, and delivery assignments.
        @endswitch</p>

        @if (session('success'))
            <div role="status" class="mt-5 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('success') }}
            </div>
        @endif
        @if ($errors->any())
            <div role="alert" class="mt-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="mt-6 divide-y divide-gray-200 rounded-lg border border-gray-200 bg-white">
            @forelse ($orders as $order)
                <article class="p-4 sm:p-5">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 class="font-semibold">{{ $order->number }}</h2>
                            <p class="mt-1 text-xs text-gray-500">Tracking {{ $order->tracking_number }}</p>
                        </div>
                        <span class="text-sm font-semibold text-[#5c2864]">{{ $order->status_label }}</span>
                    </div>
                    <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="font-semibold text-gray-600">Pickup from</dt>
                            <dd class="mt-1">{{ $order->seller?->sellerDetail?->business_name ?? $order->seller?->name }}</dd>
                            <dd class="text-gray-600">{{ implode(', ', array_filter([
                                $order->seller?->sellerDetail?->house_no,
                                $order->seller?->sellerDetail?->street,
                                $order->seller?->sellerDetail?->barangay,
                                $order->seller?->sellerDetail?->municipality,
                                $order->seller?->sellerDetail?->province,
                            ])) }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-gray-600">Deliver to</dt>
                            <dd class="mt-1 break-words">{{ $order->shipping_address }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-gray-600">Pickup courier</dt>
                            <dd class="mt-1">{{ $order->courier?->name ?? 'Not assigned' }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-gray-600">Destination hub</dt>
                            <dd class="mt-1">{{ $order->destinationLogisticsCenter?->business_name ?? 'Unresolved: no unique approved center for this destination' }}</dd>
                        </div>
                        @if ($order->delivery_courier_id)
                            <div>
                                <dt class="font-semibold text-gray-600">Delivery rider</dt>
                                <dd class="mt-1">{{ $order->deliveryCourier?->name ?? 'Unavailable' }}</dd>
                            </div>
                        @endif
                    </dl>

                    <div class="mt-5 border-t border-gray-100 pt-4">
                        @if ($order->status === 'ready_for_pickup' && ! $order->courier_id)
                            <form method="POST" action="{{ route('logistics.dispatch.courier', $order) }}" class="flex flex-wrap items-end gap-2">
                                @csrf
                                <div>
                                    <label for="courier-{{ $order->id }}" class="mb-1 block text-sm font-medium">Pickup courier</label>
                                    <select id="courier-{{ $order->id }}" name="courier_id" required
                                        class="min-w-52 rounded-lg border-gray-300 text-sm focus:border-[#5c2864] focus:ring-[#5c2864]">
                                        <option value="">Select approved courier</option>
                                        @foreach ($couriers as $courier)
                                            <option value="{{ $courier->user_id }}">{{ $courier->user->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button type="submit" @disabled($couriers->isEmpty())
                                    class="rounded-lg bg-[#3b1735] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#52234a] focus:outline-none focus:ring-2 focus:ring-[#5c2864] focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                                    Assign courier
                                </button>
                            </form>
                            @if ($couriers->isEmpty())
                                <p class="mt-2 text-sm text-gray-600">No approved rider is linked to this hub. <a class="font-semibold text-[#5c2864] underline" href="{{ route('logistics.dashboard') }}">Open Rider Management</a> to review applications.</p>
                            @endif
                        @elseif ($order->status === 'ready_for_pickup')
                            <p class="text-sm text-gray-600">Assigned rider: {{ $order->courier?->name }}. Ask the seller to print the shipping label, then scan its QR or barcode in the rider app at pickup.</p>
                        @elseif ($order->status === 'picked_up')
                            <form method="POST" action="{{ route('logistics.dispatch.arrive', $order) }}">
                                @csrf
                                <button type="submit" class="rounded-lg bg-[#3b1735] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#52234a] focus:outline-none focus:ring-2 focus:ring-[#5c2864] focus:ring-offset-2">
                                    Confirm arrival at center
                                </button>
                            </form>
                        @elseif ($order->status === 'at_sorting_center')
                            <form method="POST" action="{{ route('logistics.dispatch.sort', $order) }}">
                                @csrf
                                <button type="submit" class="rounded-lg bg-[#3b1735] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#52234a] focus:outline-none focus:ring-2 focus:ring-[#5c2864] focus:ring-offset-2">
                                    Mark sorted
                                </button>
                            </form>
                        @elseif ($order->status === 'sorted' && $order->destination_logistics_center_id !== $center->id)
                            @if ($order->destination_logistics_center_id)
                                <p class="text-sm text-gray-600">Sorted. The assigned pickup rider must scan the label to start the SOC5 → SOC6 → destination hub route.</p>
                            @else
                                <p class="text-sm text-amber-800">Destination hub is unresolved. An approved center must cover {{ $order->shipping_city ?: 'the buyer city' }}, {{ $order->shipping_province ?: 'the buyer province' }} before transfer.</p>
                            @endif
                        @elseif (in_array($order->status, ['to_soc5', 'to_soc6'], true))
                            <p class="text-sm text-gray-600">{{ $order->status === 'to_soc5' ? 'SOC5 is next. The assigned pickup rider scans the label again at the SOC5 route checkpoint.' : 'SOC6 is next. The assigned pickup rider scans the label again before it heads to the destination hub.' }}</p>
                        @elseif ($order->status === 'in_transit_to_hub' && $order->destination_logistics_center_id === $center->id)
                            <form method="POST" action="{{ route('logistics.dispatch.receive', $order) }}">
                                @csrf
                                <button type="submit" class="rounded-lg bg-[#3b1735] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#52234a]">Confirm receipt at hub</button>
                            </form>
                        @elseif (($order->status === 'sorted' && $order->logistics_center_id === $center->id && $order->destination_logistics_center_id === $center->id) || $order->status === 'at_destination_hub')
                            <form method="POST" action="{{ route('logistics.dispatch.delivery-rider', $order) }}" class="flex flex-wrap items-end gap-2">
                                @csrf
                                <div>
                                    <label for="delivery-courier-{{ $order->id }}" class="mb-1 block text-sm font-medium">Delivery rider</label>
                                    <select id="delivery-courier-{{ $order->id }}" name="courier_id" required
                                        class="min-w-52 rounded-lg border-gray-300 text-sm focus:border-[#5c2864] focus:ring-[#5c2864]">
                                        <option value="">Select approved rider</option>
                                        @foreach ($couriers as $courier)
                                            <option value="{{ $courier->user_id }}">{{ $courier->user->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button type="submit" @disabled($couriers->isEmpty())
                                    class="rounded-lg bg-[#3b1735] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#52234a] focus:outline-none focus:ring-2 focus:ring-[#5c2864] focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                                    Assign delivery rider
                                </button>
                            </form>
                            @if ($couriers->isEmpty())
                                <p class="mt-2 text-sm text-gray-600">Approve a rider for this center to enable assignment.</p>
                            @endif
                        @else
                            <p class="text-sm text-gray-600">{{ match ($order->status) {
                                'in_transit_to_hub' => 'Waiting for the destination hub to confirm receipt.',
                                'out_for_delivery' => 'The assigned delivery rider scanned this parcel for delivery.',
                                default => 'Delivery rider assigned. Waiting for the rider scan.',
                            } }}</p>
                        @endif
                    </div>
                </article>
            @empty
                <div class="p-8 text-sm text-gray-600">
                    <p>No parcels in this section right now.</p>
                    @if ($lane === 'incoming')
                        <p class="mt-2">Ready orders appear here when the seller's location matches this approved hub. If an older order is missing, ask the web operator to run <code>php artisan orders:route-ready</code>.</p>
                    @endif
                </div>
            @endforelse
        </div>
        <div class="mt-5">{{ $orders->links() }}</div>
    </div>
</x-logistics.layout>
