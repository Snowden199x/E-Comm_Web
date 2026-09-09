<div class="flex items-center gap-3 mb-6 pb-4 border-b border-gray-100">
    <img src="{{ asset('assets/branding/vendo-logo@2x.png') }}" alt="Vendo" class="h-10">
    <div>
        <p class="font-bold text-gray-900">Vendo {{ $view === 'monthly' ? 'Yearly' : 'Sales' }} Report</p>
        <p class="text-xs text-gray-400">{{ $periodLabel }}</p>
    </div>
</div>

<h3 class="font-bold text-gray-900 mb-3">Sales Summary</h3>
<div class="grid grid-cols-2 sm:grid-cols-3 gap-4 mb-6">
    <div class="bg-gray-50 rounded-xl p-4">
        <p class="text-xs text-gray-500">Gross Sales</p>
        <p class="text-lg font-bold text-gray-900">₱{{ number_format($salesSummary['gross_sales'], 2) }}</p>
    </div>
    <div class="bg-gray-50 rounded-xl p-4">
        <p class="text-xs text-gray-500">Total Orders</p>
        <p class="text-lg font-bold text-gray-900">{{ number_format($salesSummary['total_orders']) }}</p>
    </div>
    <div class="bg-gray-50 rounded-xl p-4">
        <p class="text-xs text-gray-500">Average Order Value</p>
        <p class="text-lg font-bold text-gray-900">₱{{ number_format($salesSummary['average_order_value'], 2) }}</p>
    </div>
    <div class="bg-gray-50 rounded-xl p-4">
        <p class="text-xs text-gray-500">Completed Orders</p>
        <p class="text-lg font-bold text-gray-900">{{ number_format($salesSummary['completed_orders']) }}</p>
    </div>
    <div class="bg-gray-50 rounded-xl p-4">
        <p class="text-xs text-gray-500">Return/Refund</p>
        <p class="text-lg font-bold text-gray-900">{{ number_format($salesSummary['return_refund']) }}</p>
    </div>
</div>

<h3 class="font-bold text-gray-900 mb-3">{{ $breakdownTitle }}</h3>
<table class="w-full text-sm mb-6">
    <thead>
        <tr class="text-left text-gray-500 border-b">
            <th class="pb-2 font-medium">{{ $view === 'monthly' ? 'Month' : 'Day' }}</th>
            <th class="pb-2 font-medium text-right">Sales</th>
            <th class="pb-2 font-medium text-right">Commission</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($chartLabels as $i => $label)
            <tr class="border-b last:border-0">
                <td class="py-2 text-gray-900">{{ $label }}</td>
                <td class="py-2 text-gray-700 text-right">₱{{ number_format($chartSales[$i], 2) }}</td>
                <td class="py-2 text-green-700 font-semibold text-right">₱{{ number_format($chartCommission[$i], 2) }}
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="3" class="py-6 text-center text-gray-400">No data recorded.</td>
            </tr>
        @endforelse
    </tbody>
</table>

<h3 class="font-bold text-gray-900 mb-3">Commission Report by Seller
    ({{ rtrim(rtrim(number_format($rate, 2), '0'), '.') }}%)</h3>
<table class="w-full text-sm mb-2">
    <thead>
        <tr class="text-left text-gray-500 border-b">
            <th class="pb-2 font-medium">Seller</th>
            <th class="pb-2 font-medium text-right">Sales</th>
            <th class="pb-2 font-medium text-right">Commission</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($sellers as $seller)
            <tr class="border-b last:border-0">
                <td class="py-2 text-gray-900">{{ $seller['name'] }}</td>
                <td class="py-2 text-gray-700 text-right">₱{{ number_format($seller['sales'], 2) }}</td>
                <td class="py-2 text-green-700 font-semibold text-right">₱{{ number_format($seller['commission'], 2) }}
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="3" class="py-6 text-center text-gray-400">No sales recorded.</td>
            </tr>
        @endforelse
    </tbody>
    @if ($sellers->isNotEmpty())
        <tfoot>
            <tr class="border-t-2 border-gray-200 font-bold">
                <td class="py-2 text-gray-900">Total</td>
                <td class="py-2 text-right"></td>
                <td class="py-2 text-green-700 text-right">₱{{ number_format($totalCommission, 2) }}</td>
            </tr>
        </tfoot>
    @endif
</table