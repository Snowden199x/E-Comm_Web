{{--
    Month summary cards. Lives inside #cm-region so it refreshes with the table.
    Needs: $stats, $rate, $month (all already passed by CommissionController::index()).
    The rate button belongs to the page's Alpine scope (openRate()).
--}}
@php
    $monthLabel = \Carbon\Carbon::parse($month . '-01')->format('F Y');
    $rateText = rtrim(rtrim(number_format($rate, 2), '0'), '.');
    $noSales = (float) $stats['total_sales'] <= 0;
    $cards = [
        ['label' => 'Completed sales', 'value' => '₱' . number_format($stats['total_sales'], 2), 'hint' => $noSales ? 'No completed sales in ' . $monthLabel . ' yet' : 'Delivered and completed orders in ' . $monthLabel,
            'icon' => 'trending-up', 'tone' => 'bg-[#F1E7F3] text-[#5b2963]'],
        ['label' => 'Commission earned', 'value' => '₱' . number_format($stats['total_commission'], 2), 'hint' => $rateText . '% of completed sales',
            'icon' => 'scale', 'tone' => 'bg-green-50 text-green-700'],
    ];
@endphp

<div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
    @foreach ($cards as $card)
        <div class="rg-card rounded-2xl border border-[#ece4ec] bg-white p-5 shadow-[0_1px_2px_rgba(43,23,48,0.04)]" style="--i: {{ $loop->index }}">
            <div class="flex items-start justify-between gap-3">
                <p class="text-[13px] font-medium text-gray-500">{{ $card['label'] }}</p>
                <span class="flex h-9 w-9 items-center justify-center rounded-full {{ $card['tone'] }}" aria-hidden="true"><x-admin.icon :name="$card['icon']" class="h-[18px] w-[18px]" /></span>
            </div>
            <p class="mt-3 font-display text-2xl font-semibold tabular-nums text-[#2B1730]">{{ $card['value'] }}</p>
            <p class="mt-1 text-xs text-gray-500">{{ $card['hint'] }}</p>
        </div>
    @endforeach

    {{-- Rate --}}
    <div class="rg-card rounded-2xl border border-[#ece4ec] bg-white p-5 shadow-[0_1px_2px_rgba(43,23,48,0.04)]" style="--i: 2">
        <div class="flex items-start justify-between gap-3">
            <p class="text-[13px] font-medium text-gray-500">Commission rate</p>
            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-amber-50 text-amber-700" aria-hidden="true"><x-admin.icon name="tag" class="h-[18px] w-[18px]" /></span>
        </div>
        <div class="mt-3 flex items-end justify-between gap-3">
            <p class="font-display text-2xl font-semibold tabular-nums text-[#2B1730]">{{ $rateText }}%</p>
            <button type="button" @click="openRate()" aria-haspopup="dialog"
                class="inline-flex h-9 items-center gap-1.5 rounded-full border border-[#cdbbd2] px-4 text-[13px] font-medium text-[#3b1735] transition duration-200
                       hover:bg-[#3b1735] hover:text-white active:scale-95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40">
                <x-admin.icon name="sliders" class="h-3.5 w-3.5" /> Edit rate
            </button>
        </div>
        <p class="mt-1 text-xs text-gray-500">Applies to every month shown here</p>
    </div>

    {{-- Sellers --}}
    <div class="rg-card rounded-2xl border border-[#ece4ec] bg-white p-5 shadow-[0_1px_2px_rgba(43,23,48,0.04)]" style="--i: 3">
        <div class="flex items-start justify-between gap-3">
            <p class="text-[13px] font-medium text-gray-500">Seller accounts</p>
            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-sky-50 text-sky-700" aria-hidden="true"><x-admin.icon name="store" class="h-[18px] w-[18px]" /></span>
        </div>
        <p class="mt-3 font-display text-2xl font-semibold tabular-nums text-[#2B1730]">{{ number_format($stats['total_sellers']) }}</p>
        <p class="mt-1 text-xs text-gray-500">All sellers, including those without sales</p>
    </div>
</div>