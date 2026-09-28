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
            @case('incoming') Assign an approved pickup courier to parcels routed to this Main Hub. The rider app records physical pickup. @break
            @case('sorting') Confirm Main Hub arrival and sort parcels along their configured route. @break
            @case('delivery') Confirm parcel receipt at this destination Main Hub and assign an approved local delivery rider. @break
            @default Manage pickup, Main Hub sorting, destination receipt, and local delivery assignments.
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
                        <span class="text-sm font-semibold text-[#5c2864]">{{ in_array($order->status, ['to_soc5', 'to_soc6'], true) ? 'Legacy route record' : $order->status_label }}</span>
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
                        @if ($order->status === 'sorted' && $order->logistics_center_id !== $order->destination_logistics_center_id)
                            <div>
                                <dt class="font-semibold text-gray-600">Planned next stop</dt>
                                <dd class="mt-1">{{ $order->nextRouteCheckpoint ? $order->nextRouteCheckpoint->name.' ('.$order->nextRouteCheckpoint->code.')' : ($order->routePlan ? ($order->destinationLogisticsCenter?->business_name ?? 'Destination Main Hub') : 'No active route plan configured') }}</dd>
                                @if ($order->routePlan)
                                    <dd class="mt-1 text-gray-600">Route: {{ $order->routePlan->stops->map(fn ($stop) => $stop->checkpoint?->code)->filter()->implode(' → ') ?: 'Direct to Main Hub' }} → {{ $order->destinationLogisticsCenter?->business_name }}</dd>
                                @endif
                            </div>
                        @endif
                        @if ($order->delivery_courier_id)
                            <div>
                                <dt class="font-semibold text-gray-600">Delivery rider</dt>
                                <dd class="mt-1">{{ $order->deliveryCourier?->name ?? 'Unavailable' }}</dd>
                            </div>
                        @endif
                        @if ($order->linehaul_rider_id)
                            <div>
                                <dt class="font-semibold text-gray-600">Truck Rider</dt>
                                <dd class="mt-1">{{ $order->linehaulRider?->name ?? 'Unavailable' }}</dd>
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
                                <p class="text-sm text-gray-600">Sorted at this Main Hub. Planned next checkpoint: {{ $order->nextRouteCheckpoint ? $order->nextRouteCheckpoint->name.' ('.$order->nextRouteCheckpoint->code.')' : ($order->routePlan ? ($order->destinationLogisticsCenter?->business_name ?? 'destination Main Hub') : 'No active route plan configured for this Main Hub pair') }}. SH names are virtual route checkpoints; the configured plan determines which ones this parcel follows.</p>
                                @if ($order->routePlan)
                                    @if ($order->linehaul_rider_id)
                                        <p class="mt-2 text-sm text-gray-600">Truck Rider assigned: {{ $order->linehaulRider?->name ?? 'Unavailable' }}. The route shown is planned; SH arrival and sorting still require the separate SH scanner.</p>
                                    @endif
                                    <form method="POST" action="{{ route('logistics.dispatch.linehaul-rider', $order) }}" class="mt-3 flex flex-wrap items-end gap-2">
                                        @csrf
                                        <div>
                                            <label for="linehaul-rider-{{ $order->id }}" class="mb-1 block text-sm font-medium">{{ $order->linehaul_rider_id ? 'Reassign Truck Rider' : 'Truck Rider' }}</label>
                                            <select id="linehaul-rider-{{ $order->id }}" name="rider_id" required
                                                class="min-w-52 rounded-lg border-gray-300 text-sm focus:border-[#5c2864] focus:ring-[#5c2864]">
                                                <option value="">Select approved Truck Rider</option>
                                                @foreach ($truckRiders as $truckRider)
                                                    <option value="{{ $truckRider->user_id }}" @selected((int) $order->linehaul_rider_id === (int) $truckRider->user_id)>{{ $truckRider->user->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <button type="submit" @disabled($truckRiders->isEmpty())
                                            class="rounded-lg bg-[#3b1735] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#52234a] focus:outline-none focus:ring-2 focus:ring-[#5c2864] focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                                            {{ $order->linehaul_rider_id ? 'Update assignment' : 'Assign Truck Rider' }}
                                        </button>
                                    </form>
                                    @if ($truckRiders->isEmpty())
                                        <p class="mt-2 text-sm text-gray-600">No approved Truck Rider is linked to this Main Hub. Review applications in Rider Management.</p>
                                    @endif
                                @else
                                    <p class="mt-2 text-sm text-amber-800">No configured route plan is available. Configure and activate a route before assigning a Truck Rider.</p>
                                @endif
                            @else
                                <p class="text-sm text-amber-800">Destination hub is unresolved. An approved center must cover {{ $order->shipping_city ?: 'the buyer city' }}, {{ $order->shipping_province ?: 'the buyer province' }} before transfer.</p>
                            @endif
                        @elseif (in_array($order->status, ['to_soc5', 'to_soc6'], true))
                            <p class="text-sm text-amber-800">This parcel is in a legacy SOC route state. It does not confirm arrival at an SH locality. Do not treat it as an SH scan or sorting update.</p>
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
