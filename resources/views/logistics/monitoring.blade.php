<x-logistics.layout title="Delivery Monitoring">
    <section class="lg-page">
        <div class="mb-5">
            <h2 class="text-2xl font-semibold text-gray-900">Delivery Monitoring</h2>
            <p class="mt-1 text-sm text-gray-600">Parcels linked to this logistics center, including current mobile rider updates.</p>
        </div>

        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                    <thead class="bg-gray-50 text-xs font-semibold text-gray-600">
                        <tr>
                            <th scope="col" class="px-4 py-3">Order</th>
                            <th scope="col" class="px-4 py-3">Route</th>
                            <th scope="col" class="px-4 py-3">Status</th>
                            <th scope="col" class="px-4 py-3">Assigned operator</th>
                            <th scope="col" class="px-4 py-3">Last update</th>
                            <th scope="col" class="px-4 py-3"><span class="sr-only">Open</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($orders as $order)
                            <tr class="align-top hover:bg-gray-50">
                                <td class="whitespace-nowrap px-4 py-3">
                                    <div class="font-semibold text-gray-900">{{ $order->number }}</div>
                                    <div class="mt-1 text-xs text-gray-500">{{ $order->tracking_number }}</div>
                                </td>
                                <td class="min-w-48 px-4 py-3 text-gray-700">
                                    <div>{{ $order->logisticsCenter?->business_name ?? 'Origin unavailable' }}</div>
                                    <div class="my-1 text-xs text-gray-400">to</div>
                                    <div>{{ $order->destinationLogisticsCenter?->business_name ?? 'Destination unresolved' }}</div>
                                    @if ($order->routePlan)
                                        <div class="mt-2 text-xs text-gray-500">
                                            Planned route: {{ $order->routePlan->stops->map(fn ($stop) => $stop->checkpoint?->code.' · '.$stop->checkpoint?->name)->filter()->implode(' → ') ?: 'Direct to destination Main Hub' }}
                                        </div>
                                        @if ($order->nextRouteCheckpoint)
                                            <div class="mt-1 text-xs font-medium text-[#5c2864]">Next virtual waypoint: {{ $order->nextRouteCheckpoint->code }} · {{ $order->nextRouteCheckpoint->name }}</div>
                                        @endif
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <span class="font-medium text-[#5c2864]">{{ in_array($order->status, ['to_soc5', 'to_soc6'], true) ? 'Legacy route record' : $order->status_label }}</span>
                                </td>
                                <td class="px-4 py-3 text-gray-700">
                                    @if ($order->status === 'out_for_delivery' || $order->delivery_courier_id)
                                        <div>{{ $order->deliveryCourier?->name ?? 'Delivery operator unavailable' }}</div>
                                        <div class="mt-1 text-xs text-gray-500">Delivery</div>
                                    @elseif ($order->courier_id)
                                        <div>{{ $order->courier?->name ?? 'Pickup operator unavailable' }}</div>
                                        <div class="mt-1 text-xs text-gray-500">Pickup / origin transfer</div>
                                    @else
                                        <span class="text-gray-500">Not assigned</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ $order->updated_at->format('M j, Y g:i A') }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right">
                                    <a class="font-medium text-[#5c2864] underline" href="{{ route('logistics.delivery-assignments') }}">Open assignments</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-12 text-center text-gray-600">No active parcel updates for this center.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">{{ $orders->links() }}</div>
    </section>
</x-logistics.layout>
