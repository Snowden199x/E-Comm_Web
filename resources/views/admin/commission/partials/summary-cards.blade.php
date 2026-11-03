{{--
    Month summary cards. Rendered inside #cm-cards and swapped on every month change.
    Needs: $stats, $previous, $rate, $month (all passed by CommissionController::index()).
--}}
@php
    $previousLabel = \Carbon\Carbon::parse($month . '-01')->subMonth()->format('M');
    $monthShort = \Carbon\Carbon::parse($month . '-01')->format('F');
    $rateText = rtrim(rtrim(number_format($rate, 2), '0'), '.');

    // Change against last month. Null when last month had nothing to compare with.
    $change = function (float $now, float $before): ?array {
        if ($before <= 0) {
            return null;
        }
        $pct = round((($now - $before) / $before) * 100, 1);
        if ($pct > 0) {
            return ['+' . number_format($pct, 1) . '%', 'trending-up', 'border-vd-success-line bg-vd-success-bg text-vd-success-ink'];
        }
        if ($pct < 0) {
            return ['-' . number_format(abs($pct), 1) . '%', 'trending-down', 'border-vd-danger-line bg-vd-danger-bg text-vd-danger-ink'];
        }

        return ['0%', null, 'border-vd-line-strong bg-vd-tint text-vd-muted'];
    };

    $cards = [
        [
            'label' => 'Completed sales',
            'value' => '₱' . number_format($stats['total_sales'], 2),
            'icon' => 'trending-up',
            'change' => $change((float) $stats['total_sales'], (float) $previous['total_sales']),
        ],
        [
            'label' => 'Commission earned',
            'value' => '₱' . number_format($stats['total_commission'], 2),
            'icon' => 'banknote',
            'change' => $change((float) $stats['total_commission'], (float) $previous['total_commission']),
        ],
    ];

    $chip = 'flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-vd-lavender text-vd-plum';
    $card = 'rg-card rounded-xl border border-vd-line bg-white p-5';
@endphp

<div class="mb-5 grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
    @foreach ($cards as $item)
        <div class="{{ $card }}" style="--i: {{ $loop->index }}">
            <div class="flex items-start justify-between gap-3">
                <p class="text-[13px] font-medium text-vd-muted">{{ $item['label'] }}</p>
                <span class="{{ $chip }}" aria-hidden="true"><x-admin.icon :name="$item['icon']" class="h-[18px] w-[18px]" /></span>
            </div>
            <p class="mt-3 font-display text-2xl font-semibold tabular-nums text-vd-ink">{{ $item['value'] }}</p>
            <div class="mt-1 flex items-center gap-2 text-xs text-vd-muted">
                @if ($item['change'])
                    <span class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 font-medium tabular-nums {{ $item['change'][2] }}">
                        @if ($item['change'][1])
                            <x-admin.icon :name="$item['change'][1]" class="h-3 w-3" stroke="2.2" />
                        @endif
                        {{ $item['change'][0] }}
                    </span>
                    <span>vs {{ $previousLabel }}</span>
                @else
                    <span>No sales in {{ $previousLabel }} to compare</span>
                @endif
            </div>
        </div>
    @endforeach

    {{-- Rate: the edit action sits here because the rate is set on Delivery Fees --}}
    <div class="{{ $card }}" style="--i: 2">
        <div class="flex items-start justify-between gap-3">
            <p class="text-[13px] font-medium text-vd-muted">Commission rate</p>
            <span class="{{ $chip }}" aria-hidden="true"><x-admin.icon name="percent" class="h-[18px] w-[18px]" /></span>
        </div>
        <p class="mt-3 font-display text-2xl font-semibold tabular-nums text-vd-ink">{{ $rateText }}%</p>
        <div class="mt-1 flex items-center justify-between gap-2">
            <span class="text-xs text-vd-muted">New orders only</span>
            <a href="{{ route('admin.delivery-fees.index') }}" x-target.push="main-content sidebar" data-skeleton="form"
                class="inline-flex h-8 items-center rounded-lg border border-vd-line-strong bg-white px-3 text-[13px] font-medium text-vd-plum transition-all duration-200
                       hover:border-vd-plum hover:bg-vd-plum hover:text-white active:scale-95
                       focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-plum/40">
                Edit rate
            </a>
        </div>
    </div>

    {{-- Sellers --}}
    <div class="{{ $card }}" style="--i: 3">
        <div class="flex items-start justify-between gap-3">
            <p class="text-[13px] font-medium text-vd-muted">Sellers with sales</p>
            <span class="{{ $chip }}" aria-hidden="true"><x-admin.icon name="store" class="h-[18px] w-[18px]" /></span>
        </div>
        <p class="mt-3 font-display text-2xl font-semibold tabular-nums text-vd-ink">
            {{ number_format($stats['sellers_with_sales']) }}
            <span class="text-sm font-medium text-vd-muted">of {{ number_format($stats['total_sellers']) }}</span>
        </p>
        <p class="mt-1 text-xs text-vd-muted">in {{ $monthShort }}</p>
    </div>
</div>
