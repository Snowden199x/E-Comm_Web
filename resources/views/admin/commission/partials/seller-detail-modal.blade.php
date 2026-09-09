<div x-show="sellerDetailOpen" x-cloak
    class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center p-4"
    @click.self="sellerDetailOpen = false">
    <div class="bg-white rounded-2xl p-6 w-full max-w-2xl max-h-[85vh] overflow-y-auto relative" @click.stop
        x-show="sellerDetail">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="font-bold text-lg text-gray-900" x-text="sellerDetail?.seller_name"></h3>
                <p class="text-xs text-gray-400" x-text="sellerDetail?.month_label"></p>
            </div>
            <button type="button" @click="sellerDetailOpen = false"
                class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>

        <div class="grid grid-cols-2 gap-4 mb-5">
            <div class="bg-purple-50 rounded-xl p-4">
                <p class="text-xs text-gray-600">Total Sales</p>
                <p class="text-lg font-bold text-gray-900"
                    x-text="'₱' + Number(sellerDetail?.total_sales ?? 0).toLocaleString(undefined, {minimumFractionDigits: 2})">
                </p>
            </div>
            <div class="bg-green-50 rounded-xl p-4">
                <p class="text-xs text-gray-600">Total Commission</p>
                <p class="text-lg font-bold text-gray-900"
                    x-text="'₱' + Number(sellerDetail?.total_commission ?? 0).toLocaleString(undefined, {minimumFractionDigits: 2})">
                </p>
            </div>
        </div>

        <div class="mb-5" style="height: 220px;">
            <canvas x-ref="sellerChartCanvas"></canvas>
        </div>

        <h4 class="font-bold text-sm text-gray-900 mb-2">Sales per Item</h4>
        <div class="border border-gray-100 rounded-xl overflow-hidden max-h-64 overflow-y-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-500 border-b bg-gray-50">
                        <th class="p-3 font-medium">Product</th>
                        <th class="p-3 font-medium">Qty Sold</th>
                        <th class="p-3 font-medium">Sales</th>
                        <th class="p-3 font-medium">Commission</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="item in sellerDetail?.items ?? []" :key="item.name">
                        <tr class="border-b last:border-0">
                            <td class="p-3 text-gray-900" x-text="item.name"></td>
                            <td class="p-3 text-gray-600" x-text="item.quantity"></td>
                            <td class="p-3 text-gray-900 font-medium"
                                x-text="'₱' + Number(item.sales).toLocaleString(undefined, {minimumFractionDigits: 2})">
                            </td>
                            <td class="p-3 text-green-700 font-semibold"
                                x-text="'₱' + Number(item.commission).toLocaleString(undefined, {minimumFractionDigits: 2})">
                            </td>
                        </tr>
                    </template>
                    <tr x-show="(sellerDetail?.items ?? []).length === 0">
                        <td colspan="4" class="p-6 text-center text-gray-400">No items sold this month.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
