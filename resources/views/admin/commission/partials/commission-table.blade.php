{{--
    Commission table. Rendered inside #cm-region; needs $sellers, $rate, $month, and $stats (for the share bar).
    Row click opens that seller's breakdown popup (index.blade.php: openSellerDetail).
    Page buttons carry data-page and are handled by the page scope; the old $sellers->links() pointed at the
    bare /commission/table route after the first refresh.
--}}
@php
    $monthTotal = (float) ($stats['total_sales'] ?? 0);
    $stateLabel = fn ($s) => match (true) {
        $s->status !== 'approved' => ['Not approved', 'border-gray-300 bg-gray-50 text-gray-600'],
        $s->account_status === 'suspended' => ['Suspended', 'border-red-600/60 bg-red-50 text-red-700'],
        $s->account_status === 'deactivated' => ['Deactivated', 'border-[#d9826b]/60 bg-[#FBF1EE] text-[#b4452a]'],
        default => null,
    };
@endphp

<div class="overflow-hidden rounded-2xl border border-[#ece4ec] bg-white shadow-[0_1px_2px_rgba(43,23,48,0.04)]">
    @if ($sellers->isEmpty())
        <div class="flex flex-col items-center px-6 py-16 text-center">
            <span class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-[#F1E9F1] text-[#5b2963]"><x-admin.icon name="store" class="h-7 w-7" /></span>
            <p class="font-display text-base font-semibold text-[#2B1730]">{{ request()->filled('search') ? 'No sellers found' : 'No sellers yet' }}</p>
            <p class="mt-1 max-w-sm text-sm text-gray-500">
                {{ request()->filled('search') ? 'No seller matches that name or email. Check the spelling or clear the search.' : 'Seller commissions will be listed here once sellers join.' }}
            </p>
            @if (request()->filled('search'))
                <button type="button" @click="q = ''; apply()"
                    class="mt-5 inline-flex h-10 items-center rounded-full border border-[#cdbbd2] px-5 text-sm font-medium text-[#3b1735] transition duration-200 hover:bg-[#3b1735] hover:text-white active:scale-95">
                    Clear search
                </button>
            @endif
        </div>
    @else
        <div class="thin-scroll overflow-x-auto">
            <table class="w-full min-w-[760px] text-left text-sm">
                <caption class="sr-only">Seller sales and commission for {{ \Carbon\Carbon::parse($month . '-01')->format('F Y') }}. Select a row to see the product breakdown.</caption>
                <thead>
                    <tr class="border-b border-[#ece4ec] bg-[#FBF8FB] text-[13px] text-gray-500">
                        <th scope="col" class="px-5 py-3 font-medium">Seller</th>
                        <th scope="col" class="px-4 py-3 text-center font-medium">Completed orders</th>
                        <th scope="col" class="px-4 py-3 font-medium">Sales</th>
                        <th scope="col" class="px-4 py-3 text-right font-medium">Commission</th>
                        <th scope="col" class="w-10 px-3 py-3"><span class="sr-only">Details</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#f3edf4]">
                    @foreach ($sellers as $seller)
                        @php
                            $hasSales = (float) $seller->total_sales > 0;
                            $share = $monthTotal > 0 ? min(100, ((float) $seller->total_sales / $monthTotal) * 100) : 0;
                            $state = $stateLabel($seller);
                            $avatarUrl = $seller->profile_picture ? asset('storage/' . ltrim($seller->profile_picture, '/')) : '';
                        @endphp
                        <tr style="--i: {{ $loop->index }}" tabindex="0" role="button" aria-label="See commission breakdown for {{ $seller->name }}"
                            @click="openSellerDetail({{ $seller->id }}, @js($seller->name), @js($avatarUrl))"
                            @keydown.enter.self="openSellerDetail({{ $seller->id }}, @js($seller->name), @js($avatarUrl))"
                            class="cursor-pointer transition-colors duration-150 hover:bg-[#FBF8FB] focus-visible:bg-[#FBF8FB] focus-visible:outline-none
                                   focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-[#3b1735]/40 {{ $hasSales ? '' : 'text-gray-500' }}">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    <x-admin.avatar :user="$seller" />
                                    <div class="min-w-0">
                                        <p class="flex items-center gap-2">
                                            <span class="max-w-[220px] truncate font-medium text-[#2B1730]" title="{{ $seller->name }}">{{ $seller->name }}</span>
                                            @if ($state)
                                                <span class="inline-flex flex-shrink-0 items-center rounded-full border px-2 py-0.5 text-[11px] font-medium {{ $state[1] }}">{{ $state[0] }}</span>
                                            @endif
                                        </p>
                                        <p class="max-w-[260px] truncate text-xs text-gray-500" title="{{ $seller->email }}">{{ $seller->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center tabular-nums text-gray-700">{{ number_format($seller->completed_orders_count) }}</td>
                            <td class="px-4 py-3">
                                <p class="font-medium tabular-nums {{ $hasSales ? 'text-[#2B1730]' : 'text-gray-400' }}">₱{{ number_format($seller->total_sales, 2) }}</p>
                                @if ($hasSales)
                                    <div class="mt-1.5 flex items-center gap-2" title="{{ number_format($share, 1) }}% of this month's sales">
                                        <div class="h-1.5 w-24 overflow-hidden rounded-full bg-[#F1E9F1]">
                                            <div class="h-full rounded-full bg-[#5b2963]" style="width: {{ max(3, round($share)) }}%"></div>
                                        </div>
                                        <span class="text-[11px] tabular-nums text-gray-500">{{ number_format($share, 0) }}%</span>
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right font-semibold tabular-nums {{ $hasSales ? 'text-green-700' : 'text-gray-400' }}">
                                ₱{{ number_format($seller->commission_owed, 2) }}
                            </td>
                            <td class="px-3 py-3 text-gray-400" aria-hidden="true"><x-admin.icon name="chevron-right" class="h-4 w-4" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @include('admin.seller-compliance.partials.pagination', ['paginator' => $sellers])
    @endif
</div>

<p class="mt-3 text-xs leading-relaxed text-gray-500">
    Commission = completed sales × {{ rtrim(rtrim(number_format($rate, 2), '0'), '.') }}%. A sale counts when its order is delivered or completed,
    and it is placed in the month the order was created.
</p>