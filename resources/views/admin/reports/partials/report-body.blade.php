{{--
    Everything on the Reports page that changes with the period. Lives inside #rp-region and is swapped in place.
    Needs the variables ReportController::buildReport() already returns:
    $periodLabel, $rate, $salesSummary, $sellers, $totalCommission, $topSeller, $chartLabels, $chartSales, $chartCommission, $view.
    The seller table and the chart are drawn by the reportsPage Alpine scope from the JSON in data-report.
--}}
@php
    $rateText = rtrim(rtrim(number_format($rate, 2), '0'), '.');
    $completedSales = (float) $sellers->sum('sales');
    $placed = (int) $salesSummary['total_orders'];
    $completed = (int) $salesSummary['completed_orders'];
    $completionRate = $placed > 0 ? round(($completed / $placed) * 100) : null;

    $hasTrend = count($chartLabels) >= 2;
    $hasSales = array_sum($chartSales) > 0;
    $bestIndex = $hasSales ? array_search(max($chartSales), $chartSales, true) : null;
    $unit = $view === 'monthly' ? 'month' : 'day';

    $payload = [
        'labels' => $chartLabels,
        'sales' => $chartSales,
        'commission' => $chartCommission,
        'sellers' => $sellers->map(fn ($s) => ['name' => $s['name'], 'sales' => round((float) $s['sales'], 2), 'commission' => round((float) $s['commission'], 2)])->values(),
        'rate' => (float) $rate,
        'periodLabel' => $periodLabel,
    ];

    $kpis = [
        ['Completed sales', '₱' . number_format($completedSales, 2), 'Delivered and completed orders', 'trending-up', 'bg-[#F1E7F3] text-[#5b2963]'],
        ['Commission earned', '₱' . number_format($totalCommission, 2), $rateText . '% of completed sales', 'scale', 'bg-green-50 text-green-700'],
        ['Orders completed', number_format($completed), $placed > 0 ? number_format($placed) . ' placed' . ($completionRate !== null ? ' · ' . $completionRate . '% completed' : '') : 'No orders placed', 'package', 'bg-sky-50 text-sky-700'],
        ['Top seller', $topSeller['name'] ?? '—', $topSeller ? '₱' . number_format($topSeller['sales'], 2) . ' in sales' : 'No completed sales', 'store', 'bg-amber-50 text-amber-700'],
    ];
@endphp

<div data-report="{{ json_encode($payload) }}" class="hidden" aria-hidden="true"></div>

<p class="mb-4 text-sm text-gray-500">
    Showing <span class="font-medium text-[#2B1730]">{{ $periodLabel }}</span> · commission rate {{ $rateText }}%
</p>

{{-- KPI cards --}}
<div class="mb-4 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
    @foreach ($kpis as [$label, $value, $hint, $icon, $tone])
        <div class="rg-card rounded-2xl border border-[#ece4ec] bg-white p-5 shadow-[0_1px_2px_rgba(43,23,48,0.04)]" style="--i: {{ $loop->index }}">
            <div class="flex items-start justify-between gap-3">
                <p class="text-[13px] font-medium text-gray-500">{{ $label }}</p>
                <span class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full {{ $tone }}" aria-hidden="true"><x-admin.icon :name="$icon" class="h-[18px] w-[18px]" /></span>
            </div>
            <p class="mt-3 truncate font-display text-2xl font-semibold tabular-nums text-[#2B1730]" title="{{ $value }}">{{ $value }}</p>
            <p class="mt-1 truncate text-xs text-gray-500" title="{{ $hint }}">{{ $hint }}</p>
        </div>
    @endforeach
</div>

{{-- Secondary figures --}}
<div class="mb-6 grid grid-cols-1 gap-3 rounded-2xl border border-[#ece4ec] bg-[#FBF8FB] p-4 sm:grid-cols-3">
    <div>
        <p class="text-xs text-gray-500">Gross sales <span class="text-gray-400">(orders placed, not cancelled)</span></p>
        <p class="mt-0.5 text-lg font-semibold tabular-nums text-[#2B1730]">₱{{ number_format($salesSummary['gross_sales'], 2) }}</p>
    </div>
    <div>
        <p class="text-xs text-gray-500">Average order value</p>
        <p class="mt-0.5 text-lg font-semibold tabular-nums text-[#2B1730]">₱{{ number_format($salesSummary['average_order_value'], 2) }}</p>
    </div>
    <div>
        <p class="text-xs text-gray-500">Returns / refunds</p>
        <p class="mt-0.5 text-lg font-semibold tabular-nums {{ $salesSummary['return_refund'] > 0 ? 'text-orange-700' : 'text-[#2B1730]' }}">{{ number_format($salesSummary['return_refund']) }}</p>
    </div>
    <p class="text-xs leading-relaxed text-gray-500 sm:col-span-3">
        Gross sales counts every order placed that wasn't cancelled, so it can be higher than completed sales, which only counts delivered and completed orders.
    </p>
</div>

{{-- Trend --}}
<section aria-labelledby="rp-trend-title" class="rg-card mb-6 rounded-2xl border border-[#ece4ec] bg-white p-5 shadow-[0_1px_2px_rgba(43,23,48,0.04)]" style="--i: 4">
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h2 id="rp-trend-title" class="font-display text-base font-semibold text-[#2B1730]" x-text="metric === 'sales' ? 'Sales over time' : 'Commission over time'">Sales over time</h2>
            @if ($bestIndex !== null && $hasTrend)
                <p class="mt-0.5 text-xs text-gray-500">
                    Best {{ $unit }}: <span class="font-medium text-gray-700">{{ $chartLabels[$bestIndex] }}</span>
                    with ₱{{ number_format($chartSales[$bestIndex], 2) }} in sales
                </p>
            @endif
        </div>
        <div role="group" aria-label="Chart metric" class="inline-flex rounded-full border border-[#ddd0e0] p-1">
            @foreach (['sales' => 'Sales', 'commission' => 'Commission'] as $key => $label)
                <button type="button" @click="metric = '{{ $key }}'" :aria-pressed="metric === '{{ $key }}'"
                    :class="metric === '{{ $key }}' ? 'bg-[#3b1735] text-white' : 'text-gray-600 hover:bg-[#F1E9F1]'"
                    class="h-8 rounded-full px-4 text-[13px] font-medium transition duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40">{{ $label }}</button>
            @endforeach
        </div>
    </div>

    @if (! $hasSales)
        <div class="flex flex-col items-center px-4 py-12 text-center">
            <span class="mb-3 flex h-14 w-14 items-center justify-center rounded-full bg-[#F1E9F1] text-[#5b2963]"><x-admin.icon name="trending-up" class="h-6 w-6" /></span>
            <p class="font-display text-base font-semibold text-[#2B1730]">No completed sales in this period</p>
            <p class="mt-1 max-w-sm text-sm text-gray-500">Pick a longer period above to see a trend. Orders show here once they are delivered or completed.</p>
        </div>
    @elseif (! $hasTrend)
        <div class="flex flex-col items-center px-4 py-10 text-center">
            <p class="font-display text-base font-semibold text-[#2B1730]">A trend needs more than one day</p>
            <p class="mt-1 max-w-sm text-sm text-gray-500">Choose <strong class="font-medium">This week</strong>, <strong class="font-medium">This month</strong> or <strong class="font-medium">Full year</strong> to compare periods.</p>
        </div>
    @else
        <div class="relative h-72 w-full"><canvas data-main-chart role="img" aria-label="Chart of sales and commission over the selected period"></canvas></div>
    @endif
</section>

{{-- Seller ranking --}}
<section aria-labelledby="rp-sellers-title" class="rg-card overflow-hidden rounded-2xl border border-[#ece4ec] bg-white shadow-[0_1px_2px_rgba(43,23,48,0.04)]" style="--i: 5">
    <div class="flex flex-col gap-3 border-b border-[#ece4ec] px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 id="rp-sellers-title" class="font-display text-base font-semibold text-[#2B1730]">Sellers ranked by sales</h2>
            <p class="mt-0.5 text-xs text-gray-500">Only sellers with completed sales in this period are listed.</p>
        </div>
        <div class="relative w-full sm:w-64" x-show="report.sellers.length > 0" x-cloak>
            <x-admin.icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
            <input type="search" x-model="q" autocomplete="off" aria-label="Search sellers in this report" placeholder="Search sellers"
                class="h-10 w-full rounded-full border border-[#ddd0e0] bg-white pl-10 pr-4 text-sm text-[#2B1730] placeholder:text-gray-400 transition duration-200
                       hover:border-[#cdbbd2] focus:border-[#3b1735] focus:outline-none focus:ring-2 focus:ring-[#3b1735]/20">
        </div>
    </div>

    @if ($sellers->isEmpty())
        <div class="flex flex-col items-center px-6 py-14 text-center">
            <span class="mb-3 flex h-14 w-14 items-center justify-center rounded-full bg-[#F1E9F1] text-[#5b2963]"><x-admin.icon name="store" class="h-6 w-6" /></span>
            <p class="font-display text-base font-semibold text-[#2B1730]">No seller sales yet</p>
            <p class="mt-1 max-w-sm text-sm text-gray-500">Sellers appear here once one of their orders is delivered or completed in this period.</p>
        </div>
    @else
        <div class="thin-scroll overflow-x-auto">
            <table class="w-full min-w-[640px] text-left text-sm">
                <caption class="sr-only">Sellers ranked by completed sales for {{ $periodLabel }}</caption>
                <thead>
                    <tr class="border-b border-[#ece4ec] bg-[#FBF8FB] text-[13px] text-gray-500">
                        <th scope="col" class="w-14 px-5 py-3 font-medium">#</th>
                        @foreach ([['name', 'Seller', 'text-left'], ['sales', 'Sales', 'text-left'], ['commission', 'Commission', 'text-right']] as [$key, $label, $align])
                            <th scope="col" class="px-4 py-3 font-medium {{ $align }}" :aria-sort="ariaSort('{{ $key }}')">
                                <button type="button" @click="sortBy('{{ $key }}')"
                                    class="inline-flex items-center gap-1 rounded-md transition-colors duration-150 hover:text-[#3b1735] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40"
                                    :class="sortKey === '{{ $key }}' ? 'text-[#3b1735]' : ''">
                                    {{ $label }}
                                    <span aria-hidden="true" class="text-[10px]" x-text="sortKey === '{{ $key }}' ? (sortDir === 'asc' ? '▲' : '▼') : ''"></span>
                                </button>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#f3edf4]">
                    <template x-for="(r, i) in rows" :key="r.rank">
                        <tr class="transition-colors duration-150 hover:bg-[#FBF8FB]">
                            <td class="px-5 py-3 tabular-nums text-gray-500" x-text="r.rank"></td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <span aria-hidden="true" class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-[#EFE4F1] text-xs font-semibold text-[#5b2963]" x-text="(r.name || '?').charAt(0).toUpperCase()"></span>
                                    <span class="max-w-[240px] truncate font-medium text-[#2B1730]" :title="r.name" x-text="r.name"></span>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <p class="font-medium tabular-nums text-[#2B1730]" x-text="peso(r.sales)"></p>
                                <div class="mt-1.5 flex items-center gap-2">
                                    <div class="h-1.5 w-28 overflow-hidden rounded-full bg-[#F1E9F1]"><div class="h-full rounded-full bg-[#5b2963]" :style="`width: ${Math.max(3, Math.round(r.share))}%`"></div></div>
                                    <span class="text-[11px] tabular-nums text-gray-500" x-text="Math.round(r.share) + '%'"></span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right font-semibold tabular-nums text-green-700" x-text="peso(r.commission)"></td>
                        </tr>
                    </template>
                    <tr x-show="rows.length === 0" x-cloak>
                        <td colspan="4" class="px-5 py-10 text-center text-sm text-gray-500">No seller matches “<span x-text="q"></span>”.</td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr class="border-t border-[#ece4ec] bg-[#FBF8FB] text-sm font-semibold text-[#2B1730]">
                        <td class="px-5 py-3" colspan="2">Total</td>
                        <td class="px-4 py-3 tabular-nums" x-text="peso(totalSales)"></td>
                        <td class="px-4 py-3 text-right tabular-nums text-green-700" x-text="peso(totalCommission)"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @endif
</section>

<p class="mt-3 text-xs leading-relaxed text-gray-500">
    Sales and commission count delivered and completed orders only, using the current {{ $rateText }}% rate for every period shown.
</p>