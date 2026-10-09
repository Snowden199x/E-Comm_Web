{{--
    Seller commission breakdown popup. Data comes from admin.commission.seller-detail (JSON, unchanged):
    { seller_name, month_label, items: [{ name, quantity, sales, commission }], total_sales, total_commission }.
    Uses the page scope: sellerDetailOpen, detailLoading, detailFailed, sellerDetail, detailMeta, peso, openSellerDetail.
--}}
<div x-show="sellerDetailOpen" x-cloak
    x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
    x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
    @click.self="sellerDetailOpen = false"
    class="fixed inset-0 z-50 flex items-end justify-center bg-[#2B1730]/50 p-0 sm:items-center sm:p-4"
    role="dialog" aria-modal="true" aria-labelledby="cm-detail-title">

    <div x-show="sellerDetailOpen" @click.stop
        x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="translate-y-6 opacity-0 sm:scale-95" x-transition:enter-end="translate-y-0 opacity-100 sm:scale-100"
        x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="translate-y-4 opacity-0"
        class="flex max-h-[min(90dvh,780px)] w-full max-w-3xl flex-col overflow-hidden rounded-t-3xl bg-white shadow-2xl sm:rounded-3xl">

        {{-- Header --}}
        <div class="flex items-center gap-4 border-b border-[#ece4ec] px-5 py-4 sm:px-6">
            <span class="relative inline-flex h-12 w-12 flex-shrink-0 items-center justify-center overflow-hidden rounded-full bg-[#EFE4F1] text-lg font-semibold text-[#5b2963]"
                x-data="{ broken: false }" x-effect="detailMeta.avatar; broken = false">
                <span aria-hidden="true" x-text="(detailMeta.name || '?').charAt(0).toUpperCase()"></span>
                <img x-show="detailMeta.avatar && !broken" x-bind:src="detailMeta.avatar" alt="" x-on:error="broken = true"
                    class="absolute inset-0 h-full w-full bg-white object-cover">
            </span>
            <div class="min-w-0 flex-1">
                <h3 id="cm-detail-title" class="font-display text-lg font-semibold leading-tight text-[#2B1730] [overflow-wrap:anywhere]" x-text="detailMeta.name"></h3>
                <p class="text-sm text-gray-500" x-text="sellerDetail ? sellerDetail.month_label : monthLabel"></p>
            </div>
            <button type="button" @click="sellerDetailOpen = false" aria-label="Close"
                class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full text-gray-500 transition-colors duration-150 hover:bg-[#F1E9F1] hover:text-[#3b1735]
                       focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40">
                <x-admin.icon name="x" class="h-5 w-5" />
            </button>
        </div>

        <div class="thin-scroll flex-1 overflow-y-auto px-5 py-5 sm:px-6">

            {{-- Loading --}}
            <div x-show="detailLoading" x-cloak class="space-y-3" role="status" aria-label="Loading breakdown">
                <div class="grid grid-cols-2 gap-3"><div class="h-20 animate-pulse rounded-2xl bg-[#F4EEF4]"></div><div class="h-20 animate-pulse rounded-2xl bg-[#F4EEF4]"></div></div>
                <div class="h-40 animate-pulse rounded-2xl bg-[#F4EEF4]"></div>
                <div class="h-24 animate-pulse rounded-2xl bg-[#F4EEF4]"></div>
            </div>

            {{-- Failed (this used to fail silently) --}}
            <div x-show="detailFailed" x-cloak role="alert" class="flex flex-col items-center px-4 py-12 text-center">
                <span class="mb-3 flex h-14 w-14 items-center justify-center rounded-full bg-red-50 text-red-600"><x-admin.icon name="alert-circle" class="h-6 w-6" /></span>
                <p class="font-display text-base font-semibold text-[#2B1730]">Couldn't load this breakdown</p>
                <p class="mt-1 max-w-xs text-sm text-gray-500">Check your connection and try again.</p>
                <button type="button" @click="openSellerDetail(detailMeta.id, detailMeta.name, detailMeta.avatar)"
                    class="mt-5 inline-flex h-10 items-center rounded-full border border-[#cdbbd2] px-5 text-sm font-medium text-[#3b1735] transition duration-200 hover:bg-[#3b1735] hover:text-white active:scale-95">
                    Try again
                </button>
            </div>

            {{-- Loaded --}}
            <div x-show="sellerDetail && !detailLoading && !detailFailed" x-cloak class="space-y-5">
                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-2xl border border-[#ece4ec] bg-[#FBF8FB] p-4">
                        <p class="text-xs text-gray-500">Completed sales</p>
                        <p class="mt-1 font-display text-xl font-semibold tabular-nums text-[#2B1730]" x-text="peso(sellerDetail?.total_sales)"></p>
                    </div>
                    <div class="rounded-2xl border border-green-200 bg-green-50/60 p-4">
                        <p class="text-xs text-gray-500">Commission</p>
                        <p class="mt-1 font-display text-xl font-semibold tabular-nums text-green-700" x-text="peso(sellerDetail?.total_commission)"></p>
                    </div>
                </div>

                {{-- Empty --}}
                <div x-show="sellerDetail && sellerDetail.items.length === 0" x-cloak class="flex flex-col items-center px-4 py-10 text-center">
                    <span class="mb-3 flex h-14 w-14 items-center justify-center rounded-full bg-[#F1E9F1] text-[#5b2963]"><x-admin.icon name="package" class="h-6 w-6" /></span>
                    <p class="font-display text-base font-semibold text-[#2B1730]">No completed sales this month</p>
                    <p class="mt-1 max-w-xs text-sm text-gray-500">Try another month with the arrows on the page.</p>
                </div>

                <div x-show="sellerDetail && sellerDetail.items.length > 0" x-cloak class="space-y-5">
                    <div>
                        <h4 class="mb-2 text-xs font-medium uppercase tracking-wide text-gray-500">Top products by sales</h4>
                        <div class="relative h-56 w-full"><canvas x-ref="sellerChartCanvas" role="img" aria-label="Sales by product"></canvas></div>
                    </div>

                    <div class="overflow-hidden rounded-2xl border border-[#ece4ec]">
                        <div class="thin-scroll overflow-x-auto">
                            <table class="w-full min-w-[480px] text-left text-sm">
                                <caption class="sr-only">Products sold this month</caption>
                                <thead>
                                    <tr class="border-b border-[#ece4ec] bg-[#FBF8FB] text-[13px] text-gray-500">
                                        <th scope="col" class="px-4 py-2.5 font-medium">Product</th>
                                        <th scope="col" class="px-4 py-2.5 text-center font-medium">Qty</th>
                                        <th scope="col" class="px-4 py-2.5 text-right font-medium">Sales</th>
                                        <th scope="col" class="px-4 py-2.5 text-right font-medium">Commission</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[#f3edf4]">
                                    <template x-for="(item, i) in (sellerDetail?.items || [])" :key="i">
                                        <tr>
                                            <td class="max-w-[260px] px-4 py-2.5 text-[#2B1730] [overflow-wrap:anywhere]" x-text="item.name"></td>
                                            <td class="px-4 py-2.5 text-center tabular-nums text-gray-600" x-text="item.quantity"></td>
                                            <td class="px-4 py-2.5 text-right tabular-nums text-gray-700" x-text="peso(item.sales)"></td>
                                            <td class="px-4 py-2.5 text-right font-medium tabular-nums text-green-700" x-text="peso(item.commission)"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-end border-t border-[#ece4ec] bg-[#FBF8FB] px-5 py-3.5 sm:px-6">
            <button type="button" @click="sellerDetailOpen = false"
                class="inline-flex h-10 items-center justify-center rounded-full bg-[#3b1735] px-6 text-sm font-medium text-white transition duration-200 hover:bg-[#2B1730] active:scale-95
                       focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40 focus-visible:ring-offset-2">
                Close
            </button>
        </div>
    </div>
</div>