{{-- Warnings table. Rendered inside #sc-region (scTable scope); needs $warnings. --}}
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
                <caption class="sr-only">Warnings issued to sellers</caption>
                <thead>
                    <tr class="border-b border-[#ece4ec] bg-[#FBF8FB] text-[13px] text-gray-500">
                        <th scope="col" class="px-5 py-3 font-medium">Product</th>
                        <th scope="col" class="px-4 py-3 font-medium">Seller</th>
                        <th scope="col" class="px-4 py-3 font-medium">Reason</th>
                        <th scope="col" class="px-4 py-3 font-medium">Details</th>
                        <th scope="col" class="px-5 py-3 font-medium">Date issued</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#f3edf4]">
                    @foreach ($warnings as $warning)
                        <tr style="--i: {{ $loop->index }}" class="transition-colors duration-150 hover:bg-[#FBF8FB]">
                            <td class="px-5 py-3">
                                <p class="max-w-[220px] truncate font-medium text-[#2B1730]">{{ $warning->product->name ?? '—' }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2.5">
                                    <span aria-hidden="true" class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-[#EFE4F1] text-xs font-semibold text-[#5b2963]">
                                        {{ strtoupper(mb_substr($warning->seller->name ?? '?', 0, 1)) }}
                                    </span>
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
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @include('admin.seller-compliance.partials.pagination', ['paginator' => $warnings])
    @endif
</div>