{{-- Warnings table. Rendered inside #sc-region (scTable scope); needs $warnings.
    Clicking a row opens that warning's details popup (issue-detail-modal). --}}
@php
    // Details popups: load what they show in a few batched queries per page (not per row).
    $items = $warnings->getCollection();
    $items->loadMissing(['product.images', 'product.category']);
    $sellerIds = $items->pluck('seller_id')->unique()->filter()->values();
    $warnCounts = \App\Models\Compliance\ProductWarning::whereIn('seller_id', $sellerIds)->selectRaw('seller_id, COUNT(*) as c')->groupBy('seller_id')->pluck('c', 'seller_id');
    $violCounts = \App\Models\Compliance\ProductViolation::whereIn('seller_id', $sellerIds)->selectRaw('seller_id, COUNT(*) as c')->groupBy('seller_id')->pluck('c', 'seller_id');
@endphp

<div class="sc-rows overflow-hidden rounded-2xl border border-[#ece4ec] bg-white shadow-[0_1px_2px_rgba(43,23,48,0.04)]">
    @if ($warnings->isEmpty())
        @include('admin.seller-compliance.partials.empty-state', [
            'icon' => 'triangle-alert',
            'tone' => 'orange',
            'title' => 'No warnings issued',
            'text' => 'Warnings you issue while reviewing products will be listed here.',
            'filteredTitle' => 'No warnings found',
            'filteredText' => 'No warning matches this seller or date. Try a wider range or clear the filters.',
        ])
    @else
        <div class="thin-scroll overflow-x-auto">
            <table class="w-full min-w-[860px] text-left text-sm">
                <caption class="sr-only">Warnings issued to sellers. Select a row to see its details.</caption>
                <thead>
                    <tr class="border-b border-[#ece4ec] bg-[#FBF8FB] text-[13px] text-gray-500">
                        <th scope="col" class="px-5 py-3 font-medium">Product</th>
                        <th scope="col" class="px-4 py-3 font-medium">Seller</th>
                        <th scope="col" class="px-4 py-3 font-medium">Reason</th>
                        <th scope="col" class="px-4 py-3 font-medium">Details</th>
                        <th scope="col" class="px-5 py-3 font-medium">Date issued</th>
                        <th scope="col" class="w-10 px-3 py-3"><span class="sr-only">Details</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#f3edf4]">
                    @foreach ($warnings as $warning)
                        <tr style="--i: {{ $loop->index }}" tabindex="0" role="button" aria-label="View warning details for {{ $warning->product->name ?? 'this product' }}"
                            @click="detailId = {{ $warning->id }}" @keydown.enter="detailId = {{ $warning->id }}" @keydown.space.prevent="detailId = {{ $warning->id }}"
                            class="cursor-pointer transition-colors duration-150 hover:bg-[#FBF8FB] focus-visible:bg-[#FBF8FB] focus-visible:outline-none
                                   focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-[#3b1735]/40">
                            <td class="px-5 py-3">
                                <p class="max-w-[220px] truncate font-medium text-[#2B1730]">{{ $warning->product->name ?? '—' }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2.5">
                                    <x-admin.avatar :user="$warning->seller" size="h-8 w-8" text="text-xs" />
                                    <span class="max-w-[160px] truncate text-gray-700">{{ $warning->seller->name ?? '—' }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center gap-1.5 rounded-full border border-orange-500/50 bg-orange-50 px-2.5 py-0.5 text-xs font-medium text-orange-700">
                                    <x-admin.icon name="triangle-alert" class="h-3.5 w-3.5 flex-shrink-0" />
                                    <span class="max-w-[200px] truncate">{{ $warning->reason }}</span>
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <p class="line-clamp-2 max-w-xs text-gray-500" title="{{ $warning->details }}">{{ $warning->details }}</p>
                            </td>
                            <td class="whitespace-nowrap px-5 py-3">
                                <p class="text-gray-700">{{ $warning->created_at->format('M d, Y') }}</p>
                                <p class="text-xs text-gray-500">{{ $warning->created_at->format('g:i A') }}</p>
                            </td>
                            <td class="px-3 py-3 text-gray-400" aria-hidden="true"><x-admin.icon name="chevron-right" class="h-4 w-4" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @include('admin.seller-compliance.partials.pagination', ['paginator' => $warnings])

        @foreach ($warnings as $warning)
            @include('admin.seller-compliance.partials.issue-detail-modal', [
                'item' => $warning,
                'kind' => 'warning',
                'warnCount' => (int) ($warnCounts[$warning->seller_id] ?? 0),
                'violCount' => (int) ($violCounts[$warning->seller_id] ?? 0),
            ])
        @endforeach
    @endif
</div>