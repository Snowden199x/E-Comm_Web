{{-- Overview table. Rendered inside #sc-region (scTable scope); needs $sellers. --}}
<div class="sc-rows overflow-hidden rounded-2xl border border-[#ece4ec] bg-white shadow-[0_1px_2px_rgba(43,23,48,0.04)]">
    @if ($sellers->isEmpty())
        @include('admin.seller-compliance.partials.empty-state', [
            'icon' => 'store',
            'title' => 'No sellers yet',
            'text' => 'Approved sellers and their compliance scores will appear here.',
            'filteredTitle' => 'No sellers found',
            'filteredText' => 'No seller matches that name or email. Check the spelling or clear the search.',
        ])
    @else
        <div class="thin-scroll overflow-x-auto">
            <table class="w-full min-w-[860px] text-left text-sm">
                <caption class="sr-only">Sellers and their compliance standing</caption>
                <thead>
                    <tr class="border-b border-[#ece4ec] bg-[#FBF8FB] text-[13px] text-gray-500">
                        <th scope="col" class="px-5 py-3 font-medium">Seller</th>
                        <th scope="col" class="px-4 py-3 font-medium">Category</th>
                        <th scope="col" class="px-4 py-3 font-medium">Compliance score</th>
                        <th scope="col" class="px-4 py-3 text-center font-medium">Warnings</th>
                        <th scope="col" class="px-4 py-3 text-center font-medium">Violations</th>
                        <th scope="col" class="px-4 py-3 font-medium">Status</th>
                        <th scope="col" class="w-12 px-4 py-3"><span class="sr-only">Manage</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#f3edf4]">
                    @foreach ($sellers as $seller)
                        @php
                            $score = (int) $seller->compliance_score;
                            $scoreTone = $score >= 80
                                ? ['bg-green-500', 'text-green-700']
                                : ($score >= 50 ? ['bg-amber-500', 'text-amber-700'] : ['bg-red-500', 'text-red-700']);
                            $suspended = $seller->account_status === 'suspended';
                        @endphp
                        <tr style="--i: {{ $loop->index }}" class="transition-colors duration-150 hover:bg-[#FBF8FB]">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    <span aria-hidden="true" class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-[#EFE4F1] text-[13px] font-semibold text-[#5b2963]">
                                        {{ strtoupper(mb_substr($seller->name, 0, 1)) }}
                                    </span>
                                    <div class="min-w-0">
                                        <p class="max-w-[220px] truncate font-medium text-[#2B1730]">{{ $seller->name }}</p>
                                        <p class="max-w-[220px] truncate text-xs text-gray-500">{{ $seller->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex max-w-[260px] flex-wrap gap-1.5">
                                    @forelse ($seller->categories as $category)
                                        @include('admin.seller-compliance.partials.category-badge', ['category' => $category])
                                    @empty
                                        <span class="text-gray-400">—</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="w-48 px-4 py-3">
                                <div class="flex items-center gap-2.5" role="img" aria-label="Compliance score {{ $score }} percent">
                                    <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-[#F1E9F1]">
                                        <div class="sc-meter h-full rounded-full {{ $scoreTone[0] }}" style="--i: {{ $loop->index }}; width: {{ $score }}%"></div>
                                    </div>
                                    <span class="w-9 text-right text-xs font-semibold tabular-nums {{ $scoreTone[1] }}">{{ $score }}%</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex min-w-7 items-center justify-center rounded-full px-2 py-0.5 text-xs font-semibold tabular-nums
                                    {{ $seller->product_warnings_count > 0 ? 'bg-orange-50 text-orange-700' : 'bg-gray-50 text-gray-400' }}">
                                    {{ $seller->product_warnings_count }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex min-w-7 items-center justify-center rounded-full px-2 py-0.5 text-xs font-semibold tabular-nums
                                    {{ $seller->product_violations_count > 0 ? 'bg-red-50 text-red-700' : 'bg-gray-50 text-gray-400' }}">
                                    {{ $seller->product_violations_count }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-medium
                                    {{ $suspended ? 'border-red-600/60 bg-red-50 text-red-700' : 'border-green-600/60 bg-green-50 text-green-700' }}">
                                    <x-admin.icon :name="$suspended ? 'ban' : 'shield-check'" class="h-3.5 w-3.5" />
                                    {{ $suspended ? 'Suspended' : 'Compliant' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.user-management.index', ['search' => $seller->email]) }}"
                                    aria-label="Manage {{ $seller->name }} in User Management" title="Manage in User Management"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-full text-gray-400 transition duration-150 hover:bg-[#F1E9F1] hover:text-[#3b1735]
                                           focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40">
                                    <x-admin.icon name="arrow-right" class="h-4 w-4" />
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @include('admin.seller-compliance.partials.pagination', ['paginator' => $sellers])
    @endif
</div>