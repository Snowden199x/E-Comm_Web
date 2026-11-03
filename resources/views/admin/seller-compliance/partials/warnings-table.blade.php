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

<div class="sc-rows overflow-hidden rounded-xl border border-vd-line bg-white">
    @if ($warnings->isEmpty())
        @include('admin.seller-compliance.partials.empty-state', [
            'icon' => 'triangle-alert',
            'tone' => 'orange',
            'title' => 'No warnings issued',
            'text' => 'Warnings you issue appear here.',
            'filteredTitle' => 'No warnings found',
            'filteredText' => 'Try a different seller or date, or clear the filters.',
        ])
    @else
        <div class="thin-scroll overflow-x-auto">
            <table class="w-full min-w-[860px] text-center text-sm">
                <caption class="sr-only">Warnings issued to sellers. Select a row to see its details.</caption>
                <thead>
                    <tr class="border-b border-vd-line bg-vd-tint text-[13px] text-vd-muted">
                        <th scope="col" class="px-5 py-3 text-left font-medium">Product</th>
                        <th scope="col" class="px-4 py-3 font-medium">Seller</th>
                        <th scope="col" class="px-4 py-3 font-medium">Reason</th>
                        <th scope="col" class="px-4 py-3 font-medium">Details</th>
                        <th scope="col" class="px-5 py-3 font-medium">Date issued</th>
                        <th scope="col" class="px-4 py-3 font-medium">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-vd-line">
                    @foreach ($warnings as $warning)
                        <tr style="--i: {{ $loop->index }}"
                            @click="detailId = {{ $warning->id }}"
                            class="cursor-pointer transition-colors duration-150 hover:bg-vd-tint">
                            <td class="px-5 py-3 text-left">
                                <p class="max-w-[220px] truncate font-medium text-vd-ink">{{ $warning->product->name ?? '—' }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center gap-2.5">
                                    <x-admin.avatar :user="$warning->seller" size="h-8 w-8" text="text-xs" />
                                    <span class="max-w-[160px] truncate text-vd-ink">{{ $warning->seller->name ?? '—' }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <x-admin.status-pill tone="warning" icon="triangle-alert"><span class="max-w-[200px] truncate">{{ $warning->reason }}</span></x-admin.status-pill>
                            </td>
                            <td class="px-4 py-3">
                                <p class="mx-auto line-clamp-2 max-w-xs text-vd-muted" title="{{ $warning->details }}">{{ $warning->details }}</p>
                            </td>
                            <td class="whitespace-nowrap px-5 py-3">
                                <p class="text-vd-ink">{{ $warning->created_at->format('M d, Y') }}</p>
                                <p class="text-xs text-vd-muted">{{ $warning->created_at->format('g:i A') }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <button type="button" aria-haspopup="dialog" @click.stop="detailId = {{ $warning->id }}" aria-label="View warning for {{ $warning->product->name ?? 'this product' }}"
                                    class="inline-flex h-8 items-center justify-center rounded-lg border border-vd-line-strong bg-white px-3 text-[13px] font-medium text-vd-plum transition-all duration-200 hover:border-vd-plum hover:bg-vd-plum hover:text-white active:scale-95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-plum/40">View</button>
                            </td>
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