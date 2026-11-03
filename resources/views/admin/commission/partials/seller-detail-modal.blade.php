{{--
    Seller commission breakdown dialog. Data comes from admin.commission.seller-detail (JSON):
    { seller_name, month_label, items: [{ name, quantity, sales, commission }], total_sales, total_commission, average_rate }.
    Uses the page scope: detailOpen, detailLoading, detailError, sellerDetail, detailMeta, peso, openSellerDetail, closeDetail.
--}}
<div x-show="detailOpen" x-cloak
    x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
    x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
    @click.self="closeDetail()"
    class="fixed inset-0 z-50 flex items-end justify-center bg-vd-ink/50 p-0 sm:items-center sm:p-4"
    role="dialog" aria-modal="true" aria-labelledby="cm-detail-title">

    <div @click.stop
        class="flex max-h-[min(90dvh,760px)] w-full max-w-2xl flex-col overflow-hidden rounded-t-2xl border border-vd-line bg-white sm:rounded-2xl">

        {{-- Header --}}
        <div class="flex items-center gap-3 border-b border-vd-line p-4">
            <span class="relative inline-flex h-10 w-10 flex-shrink-0 items-center justify-center overflow-hidden rounded-full bg-vd-lavender text-sm font-semibold text-vd-plum"
                x-data="{ broken: false }" x-effect="detailMeta.avatar; broken = false">
                <span aria-hidden="true" x-text="(detailMeta.name || '?').charAt(0).toUpperCase()"></span>
                <img x-show="detailMeta.avatar && !broken" x-bind:src="detailMeta.avatar" alt="" x-on:error="broken = true"
                    class="absolute inset-0 h-full w-full bg-white object-cover">
            </span>
            <div class="min-w-0 flex-1">
                <h2 id="cm-detail-title" class="font-display text-base font-semibold leading-snug text-vd-ink [overflow-wrap:anywhere]" x-text="detailMeta.name"></h2>
                <p class="text-[13px] text-vd-muted" x-text="sellerDetail ? sellerDetail.month_label : monthLabel"></p>
            </div>
            <button type="button" x-ref="detailClose" @click="closeDetail()" aria-label="Close breakdown"
                class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg text-vd-muted transition-colors duration-200 hover:bg-vd-tint hover:text-vd-plum
                       focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-plum/40">
                <x-admin.icon name="x" class="h-4 w-4" />
            </button>
        </div>

        <div class="thin-scroll flex-1 overflow-y-auto p-4 sm:p-5">

            {{-- Loading --}}
            <div x-show="detailLoading" x-cloak class="space-y-5" role="status" aria-label="Loading breakdown">
                <div class="grid grid-cols-3 gap-3">
                    <template x-for="n in 3" :key="n"><div class="vd-skeleton h-[72px] rounded-xl"></div></template>
                </div>
                <div class="space-y-3">
                    <template x-for="n in 4" :key="n"><div class="vd-skeleton h-9 w-full"></div></template>
                </div>
            </div>

            {{-- Failed --}}
            <div x-show="detailError" x-cloak role="alert">
                <div class="vd-empty vd-empty--sm" data-tone="red">
                    <span class="vd-empty__disc" aria-hidden="true"><x-admin.icon name="alert-circle" class="h-6 w-6" /></span>
                    <p class="vd-empty__title">Could not load this breakdown</p>
                    <p class="vd-empty__text" x-text="detailError"></p>
                    <div class="vd-empty__actions">
                        <button type="button" @click="openSellerDetail(detailMeta.id, detailMeta.name, detailMeta.avatar)"
                            class="inline-flex h-9 items-center rounded-lg border border-vd-line-strong bg-white px-4 text-[13px] font-medium text-vd-plum transition-all duration-200 hover:border-vd-plum hover:bg-vd-plum hover:text-white active:scale-95">
                            Retry
                        </button>
                    </div>
                </div>
            </div>

            {{-- Loaded --}}
            <div x-show="sellerDetail && !detailLoading && !detailError" x-cloak class="space-y-5">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div class="rounded-xl border border-vd-line bg-vd-tint p-4">
                        <p class="text-xs text-vd-muted">Completed sales</p>
                        <p class="mt-1 font-display text-lg font-semibold tabular-nums text-vd-ink" x-text="peso(sellerDetail?.total_sales)"></p>
                    </div>
                    <div class="rounded-xl border border-vd-line bg-vd-tint p-4">
                        <p class="text-xs text-vd-muted">Commission</p>
                        <p class="mt-1 font-display text-lg font-semibold tabular-nums text-vd-ink" x-text="peso(sellerDetail?.total_commission)"></p>
                    </div>
                    <div class="rounded-xl border border-vd-line bg-vd-tint p-4">
                        <p class="text-xs text-vd-muted">Average rate</p>
                        <p class="mt-1 font-display text-lg font-semibold tabular-nums text-vd-ink" x-text="sellerDetail?.average_rate != null ? sellerDetail.average_rate + '%' : '—'"></p>
                    </div>
                </div>

                {{-- Empty --}}
                <div x-show="sellerDetail && sellerDetail.items.length === 0" x-cloak>
                    <div class="vd-empty vd-empty--sm" role="status">
                        <span class="vd-empty__disc" aria-hidden="true"><x-admin.icon name="package" class="h-6 w-6" /></span>
                        <p class="vd-empty__title">No completed sales</p>
                        <p class="vd-empty__text">This seller has no delivered orders in this month. Pick another month on the page.</p>
                    </div>
                </div>

                <div x-show="sellerDetail && sellerDetail.items.length > 0" x-cloak class="overflow-hidden rounded-xl border border-vd-line">
                    <div class="thin-scroll overflow-x-auto">
                        <table class="w-full min-w-[460px] text-left text-sm">
                            <caption class="sr-only">Products sold this month</caption>
                            <thead>
                                <tr class="border-b border-vd-line bg-vd-tint text-[13px] text-vd-muted">
                                    <th scope="col" class="px-4 py-3 font-medium">Product</th>
                                    <th scope="col" class="px-4 py-3 text-center font-medium">Qty</th>
                                    <th scope="col" class="px-4 py-3 text-right font-medium">Sales</th>
                                    <th scope="col" class="px-4 py-3 text-right font-medium">Commission</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-vd-line">
                                <template x-for="(item, i) in (sellerDetail?.items || [])" :key="i">
                                    <tr>
                                        <td class="max-w-[240px] px-4 py-3 text-vd-ink [overflow-wrap:anywhere]">
                                            <p x-text="item.name"></p>
                                            <div class="mt-1.5 h-1.5 w-24 overflow-hidden rounded-full bg-vd-lavender" :title="shareOf(item.sales) + '% of sales'">
                                                <div class="h-full rounded-full bg-vd-plum" :style="'width:' + Math.max(3, shareOf(item.sales)) + '%'"></div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-center tabular-nums text-vd-muted" x-text="item.quantity"></td>
                                        <td class="px-4 py-3 text-right tabular-nums text-vd-ink" x-text="peso(item.sales)"></td>
                                        <td class="px-4 py-3 text-right font-semibold tabular-nums text-vd-ink" x-text="peso(item.commission)"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
