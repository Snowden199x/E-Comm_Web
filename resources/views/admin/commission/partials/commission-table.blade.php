{{--
    Seller commission table. Rendered inside #cm-table and swapped on every month, search or page change.
    Needs: $sellers, $stats, $rate, $month.
    The "View breakdown" button is the main way in; clicking the row does the same. Page buttons carry
    data-page and are handled by the page scope (index.blade.php: onTableClick).
--}}
@php
    $monthTotal = (float) ($stats['total_sales'] ?? 0);
    $monthLabel = \Carbon\Carbon::parse($month . '-01')->format('F Y');
    $previousMonthLabel = \Carbon\Carbon::parse($month . '-01')->subMonth()->format('F Y');
    $isSearching = request()->filled('search');
    $noSalesAtAll = ! $isSearching && $monthTotal <= 0;

    // Account status chips follow the admin design system: hold = warning, switched off = neutral.
    $stateLabel = fn ($s) => match (true) {
        $s->status !== 'approved' => ['Not approved', 'border-vd-line-strong bg-vd-tint text-vd-muted'],
        $s->account_status === 'suspended' => ['Suspended', 'border-vd-warning-line bg-vd-warning-bg text-vd-warning-ink'],
        $s->account_status === 'deactivated' => ['Deactivated', 'border-vd-line-strong bg-vd-tint text-vd-muted'],
        default => null,
    };
@endphp

<div class="overflow-hidden rounded-xl border border-vd-line bg-white">
    @if ($sellers->isEmpty())
        @if ($isSearching)
            <x-admin.empty-state filtered filteredTitle="No sellers found" filteredText="Check the spelling, or search by email instead.">
                <button type="button" @click="q = ''; apply()"
                    class="inline-flex h-9 items-center rounded-lg border border-vd-line-strong bg-white px-4 text-[13px] font-medium text-vd-plum transition-colors duration-200 hover:bg-vd-tint">
                    Clear search
                </button>
            </x-admin.empty-state>
        @else
            <x-admin.empty-state icon="store" title="No sellers" text="Approved sellers and their commission appear here." />
        @endif
    @elseif ($noSalesAtAll)
        <x-admin.empty-state icon="package" title="No completed sales in {{ $monthLabel }}" text="Delivered and completed orders appear here. Pick another month.">
            <button type="button" @click="shiftMonth(-1)"
                class="inline-flex h-9 items-center rounded-lg border border-vd-line-strong bg-white px-4 text-[13px] font-medium text-vd-plum transition-all duration-200 hover:border-vd-plum hover:bg-vd-plum hover:text-white active:scale-95">
                Go to {{ $previousMonthLabel }}
            </button>
        </x-admin.empty-state>
    @else
        <div class="thin-scroll overflow-x-auto">
            <table class="w-full min-w-[820px] text-left text-sm">
                <caption class="sr-only">Seller sales and commission for {{ $monthLabel }}</caption>
                <thead>
                    <tr class="border-b border-vd-line bg-vd-tint text-[13px] text-vd-muted">
                        <th scope="col" class="px-5 py-3 font-medium">Seller</th>
                        <th scope="col" class="px-4 py-3 text-center font-medium">Orders</th>
                        <th scope="col" class="px-4 py-3 text-right font-medium">Sales</th>
                        <th scope="col" class="px-4 py-3 font-medium">Share</th>
                        <th scope="col" class="px-4 py-3 text-right font-medium">Commission</th>
                        <th scope="col" class="px-5 py-3 text-right font-medium"><span class="sr-only">Action</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-vd-line">
                    @foreach ($sellers as $seller)
                        @php
                            $sales = (float) $seller->total_sales;
                            $commission = (float) $seller->commission_owed;
                            $hasSales = $sales > 0;
                            $share = $monthTotal > 0 ? min(100, ($sales / $monthTotal) * 100) : 0;
                            $state = $stateLabel($seller);
                            $avatarUrl = $seller->profile_picture ? asset('storage/' . ltrim($seller->profile_picture, '/')) : '';
                        @endphp
                        <tr style="--i: {{ $loop->index }}"
                            @click="if (!$event.target.closest('button')) $el.querySelector('[data-open-seller]').click()"
                            class="cursor-pointer transition-colors duration-200 hover:bg-vd-tint">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    <x-admin.avatar :user="$seller" />
                                    <div class="min-w-0">
                                        <p class="flex items-center gap-2">
                                            <span class="max-w-[220px] truncate font-medium text-vd-ink" title="{{ $seller->name }}">{{ $seller->name }}</span>
                                            @if ($state)
                                                <span class="inline-flex flex-shrink-0 items-center rounded-full border px-2 py-0.5 text-[11px] font-medium {{ $state[1] }}">{{ $state[0] }}</span>
                                            @endif
                                        </p>
                                        <p class="max-w-[260px] truncate text-xs text-vd-muted" title="{{ $seller->email }}">{{ $seller->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center tabular-nums {{ $hasSales ? 'text-vd-ink' : 'text-vd-muted' }}">{{ number_format($seller->completed_orders_count) }}</td>
                            <td class="px-4 py-3 text-right tabular-nums {{ $hasSales ? 'text-vd-ink' : 'text-vd-muted' }}">₱{{ number_format($sales, 2) }}</td>
                            <td class="px-4 py-3">
                                @if ($hasSales)
                                    <div class="flex items-center gap-2" title="{{ number_format($share, 1) }}% of this month's sales">
                                        <div class="h-1.5 w-24 overflow-hidden rounded-full bg-vd-lavender">
                                            <div class="h-full rounded-full bg-vd-plum" style="width: {{ max(3, round($share)) }}%"></div>
                                        </div>
                                        <span class="w-9 text-xs tabular-nums text-vd-muted">{{ number_format($share, 0) }}%</span>
                                    </div>
                                @else
                                    <span class="text-xs text-vd-muted">No sales</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right font-semibold tabular-nums {{ $hasSales ? 'text-vd-ink' : 'text-vd-muted' }}">₱{{ number_format($commission, 2) }}</td>
                            <td class="px-5 py-3 text-right">
                                <button type="button" data-open-seller data-id="{{ $seller->id }}" data-name="{{ $seller->name }}" data-avatar="{{ $avatarUrl }}"
                                    @click="openFromButton($el)" aria-haspopup="dialog" aria-label="View commission breakdown for {{ $seller->name }}"
                                    class="inline-flex h-8 items-center rounded-lg border border-vd-line-strong bg-white px-3 text-[13px] font-medium text-vd-plum transition-all duration-200
                                           hover:border-vd-plum hover:bg-vd-plum hover:text-white active:scale-95
                                           focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-plum/40">
                                    View breakdown
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @include('admin.partials.pagination', ['paginator' => $sellers])
    @endif
</div>

<p class="mt-3 text-xs text-vd-muted">Counts delivered and completed orders. Each order uses the rate saved when it was placed.</p>
