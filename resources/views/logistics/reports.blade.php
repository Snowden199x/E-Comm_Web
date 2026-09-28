<x-logistics.layout title="Reports">
    <section class="lg-page">
        <div class="mb-5">
            <h2 class="text-2xl font-semibold text-gray-900">Logistics Reports</h2>
            <p class="mt-1 text-sm text-gray-600">Parcel status counts updated in the last 30 days for this center.</p>
        </div>

        <p class="mb-4 text-xs text-gray-500">Since {{ $since->format('M j, Y') }} · Counts include an order when this center is its origin or destination.</p>

        <dl class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
            @foreach ([
                ['label' => 'Updated parcels', 'value' => $total],
                ['label' => 'Ready for pickup', 'value' => $readyForPickup],
                ['label' => 'Out for delivery', 'value' => $outForDelivery],
                ['label' => 'Delivered', 'value' => $delivered],
                ['label' => 'Exceptions', 'value' => $exceptions],
            ] as $metric)
                <div class="rounded-lg border border-gray-200 bg-white p-4">
                    <dt class="text-sm text-gray-600">{{ $metric['label'] }}</dt>
                    <dd class="mt-2 text-2xl font-semibold text-gray-900">{{ number_format($metric['value']) }}</dd>
                </div>
            @endforeach
        </dl>

        <div class="mt-6 overflow-hidden rounded-lg border border-gray-200 bg-white">
            <div class="border-b border-gray-200 px-4 py-3">
                <h3 class="font-semibold text-gray-900">Parcel counts by current status</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                    <thead class="bg-gray-50 text-xs font-semibold text-gray-600">
                        <tr><th scope="col" class="px-4 py-3">Status</th><th scope="col" class="px-4 py-3 text-right">Parcels</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($statusCounts as $row)
                            <tr>
                                <th scope="row" class="px-4 py-3 font-medium text-gray-800">{{ $row->status === 'legacy_route_record' ? 'Legacy route record' : (\App\Models\Ecommerce\Order::STATUSES[$row->status] ?? ucfirst(str_replace('_', ' ', $row->status))) }}</th>
                                <td class="px-4 py-3 text-right text-gray-700">{{ number_format($row->total) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="px-4 py-10 text-center text-gray-600">No parcel status updates in this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</x-logistics.layout>
