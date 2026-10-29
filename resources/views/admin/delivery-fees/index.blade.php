<x-admin.layout title="Delivery Fees">
    @php
        $vendoStart = old('vendo_delivery_commission_rate', number_format((float) $setting->vendo_delivery_commission_rate, 2, '.', ''));
        $sellerStart = old('seller_commission_rate', number_format((float) $setting->seller_commission_rate, 2, '.', ''));
    @endphp
    <div class="mx-auto w-full max-w-[1280px] p-4 sm:p-5 lg:p-6" x-data="{
        vendo: @js((string) $vendoStart), seller: @js((string) $sellerStart),
        fee: 100, rider: 10, item: 500,
        peso(cents) { return '₱' + (cents / 100).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
        pct(cents, rate) { return Math.floor((cents * Math.round(Number(rate || 0) * 100) + 5000) / 10000); },
        get remaining() { return (this.fee - this.rider) * 100; },
        get vendoCents() { return this.pct(this.remaining, this.vendo); },
        get logisticsCents() { return this.remaining - this.vendoCents; },
        get sellerCommissionCents() { return this.pct(this.item * 100, this.seller); },
    }">
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Delivery Fees</h2>
            <p class="text-gray-500">Set Vendo's commission rates and monitor how each delivery fee was split between the Rider, Vendo and the Logistics Company.</p>
        </div>

        @if (session('success'))<div class="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800" role="status">{{ session('success') }}</div>@endif
        @if ($errors->any())<div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">{{ $errors->first() }}</div>@endif

        <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
            @foreach ([
                ['Delivery commission earned', '₱'.number_format($totalCommission, 2), 'bg-green-50 border-green-500'],
                ['Delivery commission this month', '₱'.number_format($monthCommission, 2), 'bg-purple-50 border-[#3b1735]'],
                ['Parcels split', number_format($parcels), 'bg-orange-50 border-orange-500'],
                ['Seller commission now', rtrim(rtrim(number_format((float) $setting->seller_commission_rate, 2), '0'), '.').'%', 'bg-yellow-50 border-yellow-400'],
            ] as [$label, $value, $classes])
                <div class="rounded-2xl border-2 p-4 {{ $classes }}">
                    <p class="text-xs text-gray-600">{{ $label }}</p>
                    <p class="text-xl font-bold text-black">{{ $value }}</p>
                </div>
            @endforeach
        </div>

        <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="rounded-2xl bg-white p-5 shadow-sm lg:col-span-2">
                <h3 class="text-lg font-semibold text-gray-900">Commission rates</h3>
                <p class="mb-4 text-sm text-gray-500">Each order saves the rates in force when it is placed, so a change here applies to new orders only.
                    @if ($setting->updatedBy) Last changed by {{ $setting->updatedBy->name }}, {{ $setting->updated_at?->format('M j, Y g:i A') }}.@endif</p>
                <form method="POST" action="{{ route('admin.delivery-fees.update') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    @csrf @method('PUT')
                    <div>
                        <label for="vendoRate" class="mb-1 block text-sm font-medium text-gray-700">Delivery fee commission (%)</label>
                        <input id="vendoRate" name="vendo_delivery_commission_rate" type="number" step="0.01" min="0" max="100" x-model="vendo" @disabled(! $canEdit)
                               class="w-full rounded-xl border border-gray-300 px-3 py-2 disabled:bg-gray-100" required>
                        <p class="mt-1 text-xs text-gray-500">Vendo's share of the delivery fee after the Rider's pay is deducted.</p>
                    </div>
                    <div>
                        <label for="sellerRate" class="mb-1 block text-sm font-medium text-gray-700">Seller commission (%)</label>
                        <input id="sellerRate" name="seller_commission_rate" type="number" step="0.01" min="0" max="100" x-model="seller" @disabled(! $canEdit)
                               class="w-full rounded-xl border border-gray-300 px-3 py-2 disabled:bg-gray-100" required>
                        <p class="mt-1 text-xs text-gray-500">Vendo's share of the item price, taken from the Seller's earnings.</p>
                    </div>
                    <div class="sm:col-span-2">
                        @if ($canEdit)
                            <button type="submit" class="rounded-xl bg-[#3b1735] px-4 py-2 text-sm font-semibold text-white hover:opacity-90">Save rates</button>
                        @else
                            <p class="text-sm text-gray-500">Only a super admin can change these rates.</p>
                        @endif
                    </div>
                </form>
            </div>

            <div class="rounded-2xl bg-white p-5 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900">Preview</h3>
                <p class="mb-3 text-sm text-gray-500">Example: ₱100 delivery fee and ₱10 Rider pay (each Company sets its own). Updates as you type.</p>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between"><dt>Delivery fee</dt><dd x-text="peso(fee * 100)"></dd></div>
                    <div class="flex justify-between"><dt>Rider (fixed)</dt><dd x-text="peso(rider * 100)"></dd></div>
                    <div class="flex justify-between"><dt>Remaining</dt><dd x-text="peso(remaining)"></dd></div>
                    <div class="flex justify-between"><dt>Vendo commission</dt><dd class="font-semibold" x-text="peso(vendoCents)"></dd></div>
                    <div class="flex justify-between"><dt>Logistics Company</dt><dd x-text="peso(logisticsCents)"></dd></div>
                    <hr class="my-2">
                    <div class="flex justify-between"><dt>On a ₱500 item, Seller commission</dt><dd class="font-semibold" x-text="peso(sellerCommissionCents)"></dd></div>
                    <div class="flex justify-between"><dt>Seller keeps</dt><dd x-text="peso(item * 100 - sellerCommissionCents)"></dd></div>
                </dl>
            </div>
        </div>

        <div class="rounded-2xl bg-white p-5 shadow-sm">
            <h3 class="text-lg font-semibold text-gray-900">Delivery fee distributions</h3>
            <p class="mb-3 text-sm text-gray-500">One row per delivered parcel placed with saved rates. Older orders use the earlier per-stop rule and are not listed.</p>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b text-xs uppercase text-gray-500"><tr>
                        <th class="py-2 pr-3">Order</th><th class="py-2 pr-3">Delivery fee</th><th class="py-2 pr-3">Rider</th><th class="py-2 pr-3">Vendo</th><th class="py-2 pr-3">Logistics</th><th class="py-2 pr-3">Rates used</th><th class="py-2">Settled</th>
                    </tr></thead>
                    <tbody class="divide-y">
                        @forelse ($history as $row)
                            <tr>
                                <td class="py-2 pr-3 font-medium">{{ $row->order_number }}@if ($row->status === 'review')<span class="ml-2 rounded-full bg-amber-100 px-2 py-0.5 text-xs text-amber-800">Needs review</span>@endif</td>
                                <td class="py-2 pr-3">₱{{ number_format((float) $row->shipping_fee, 2) }}</td>
                                <td class="py-2 pr-3">₱{{ number_format((float) $row->rider_total, 2) }}</td>
                                <td class="py-2 pr-3 font-semibold">₱{{ number_format((float) $row->vendo_commission, 2) }}</td>
                                <td class="py-2 pr-3">₱{{ number_format((float) $row->logistics_remainder, 2) }}</td>
                                <td class="py-2 pr-3 text-gray-500">₱{{ number_format((float) $row->rider_pay_rate, 2) }} rider, {{ rtrim(rtrim(number_format((float) $row->vendo_delivery_commission_rate, 2), '0'), '.') }}% Vendo</td>
                                <td class="py-2">{{ \Illuminate\Support\Carbon::parse($row->settled_at)->format('M j, g:i A') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="py-6 text-center text-gray-500">No delivery fees have been split yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $history->links() }}</div>
        </div>
    </div>
</x-admin.layout>
