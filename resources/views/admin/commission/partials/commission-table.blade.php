<div class="bg-white rounded-2xl p-5 shadow-sm overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr class="text-left text-gray-500 border-b">
                <th class="pb-3 font-medium">Seller</th>
                <th class="pb-3 font-medium text-center">Completed Orders</th>
                <th class="pb-3 font-medium text-center">Total Sales
                    ({{ \Carbon\Carbon::parse($month . '-01')->format('F Y') }})</th>
                <th class="pb-3 font-medium text-center">Commission
                    ({{ rtrim(rtrim(number_format($rate, 2), '0'), '.') }}%)</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($sellers as $seller)
                <tr class="border-b last:border-0 cursor-pointer hover:bg-gray-50"
                    @click="openSellerDetail({{ $seller->id }})">
                    <td class="py-3">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center text-xs font-semibold text-gray-600">
                                {{ strtoupper(substr($seller->name, 0, 1)) }}
                            </div>
                            <span class="text-gray-900">{{ $seller->name }}</span>
                        </div>
                    </td>
                    <td class="py-3 text-gray-600 text-center">{{ $seller->completed_orders_count }}</td>
                    <td class="py-3 text-gray-900 font-medium text-center">₱{{ number_format($seller->total_sales, 2) }}
                    </td>
                    <td class="py-3 text-green-700 font-semibold text-center">
                        ₱{{ number_format($seller->commission_owed, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="py-8 text-center text-gray-400">No sellers found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    @if ($sellers->hasPages())
        <div class="flex items-center justify-between mt-4 pt-4 border-t">
            <p class="text-xs text-gray-500">Showing {{ $sellers->count() }} out of {{ $sellers->total() }} entries</p>
            <div>{{ $sellers->links() }}</div>
        </div>
    @endif
</div>
