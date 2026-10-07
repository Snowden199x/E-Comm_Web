{{--
    Overview table. Rendered inside #sc-region (scTable scope); needs $sellers.
    Clicking a row opens that seller's popup (products, warnings and violations).
    Popup data is loaded with the page's seller query.
--}}
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
                <caption class="sr-only">Sellers and their compliance standing. Select a row to see the seller's products.</caption>
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
                            $score = is_numeric($seller->getAttribute('compliance_score')) ? (int) $seller->getAttribute('compliance_score') : null;
                            $scoreTone = $score === null
                                ? ['bg-gray-300', 'text-gray-500']
                                : ($score >= 80
                                ? ['bg-green-500', 'text-green-700']
                                : ($score >= 50 ? ['bg-amber-500', 'text-amber-700'] : ['bg-red-500', 'text-red-700']));
                            $suspended = $seller->account_status === 'suspended';
                        @endphp
                        <tr style="--i: {{ $loop->index }}" tabindex="0" role="button" aria-label="View products of {{ $seller->name }}"
                            @click="sellerId = {{ $seller->id }}" @keydown.enter="sellerId = {{ $seller->id }}" @keydown.space.prevent="sellerId = {{ $seller->id }}"
                            class="cursor-pointer transition-colors duration-150 hover:bg-[#FBF8FB] focus-visible:bg-[#FBF8FB] focus-visible:outline-none
                                   focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-[#3b1735]/40">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    <span aria-hidden="true" class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-[#EFE4F1] text-[13px] font-semibold text-[#5b2963]">
                                        {{ strtoupper(mb_substr($seller->name, 0, 1)) }}
                                    </span>
                                    <div class="min-w-0">
                                        <p class="max-w-[220px] truncate font-medium text-[#2B1730]" title="{{ $seller->name }}">{{ $seller->name }}</p>
                                        <p class="max-w-[220px] truncate text-xs text-gray-500" title="{{ $seller->email }}">{{ $seller->email }}</p>
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
                                <div class="flex items-center gap-2.5" role="img" aria-label="{{ $score === null ? 'Compliance score not defined' : 'Compliance score '.$score.' percent' }}">
                                    <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-[#F1E9F1]">
                                        @if ($score !== null)<div class="sc-meter h-full rounded-full {{ $scoreTone[0] }}" style="--i: {{ $loop->index }}; width: {{ $score }}%"></div>@endif
                                    </div>
                                    <span class="w-9 text-right text-xs font-semibold tabular-nums {{ $scoreTone[1] }}">{{ $score === null ? '—' : $score.'%' }}</span>
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
                                <a href="{{ route('admin.user-management.index', ['search' => $seller->email]) }}" @click.stop
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

{{-- ============ Seller popups (one per row on this page) ============ --}}
@php
    $productStatus = [
        'approved' => ['Approved', 'bg-green-50 text-green-700'],
        'for_review' => ['Awaiting review', 'bg-amber-50 text-amber-800'],
        'warned' => ['Warned', 'bg-orange-50 text-orange-700'],
        'rejected' => ['Rejected', 'bg-red-50 text-red-700'],
        'draft' => ['Draft', 'bg-gray-100 text-gray-600'],
    ];
@endphp

@foreach ($sellers as $seller)
    @php
        $products = $seller->products;
        $productTotal = $seller->products_count;
        $warnings = $seller->productWarnings;
        $violations = $seller->productViolations;
        $statusCounts = $products->countBy('status');
        $score = is_numeric($seller->getAttribute('compliance_score')) ? (int) $seller->getAttribute('compliance_score') : null;
        $suspended = $seller->account_status === 'suspended';
        $filters = collect([['all', 'All', $products->count()]])
            ->concat($statusCounts->map(fn ($n, $s) => [$s, $productStatus[$s][0] ?? ucfirst(str_replace('_', ' ', $s)), $n])->values());
    @endphp

    <div x-show="sellerId === {{ $seller->id }}" x-cloak
        x-data="{ tab: 'products', filter: 'all' }"
        x-effect="if (sellerId !== {{ $seller->id }}) { tab = 'products'; filter = 'all' } else { $nextTick(() => document.getElementById('sc-seller-close-{{ $seller->id }}')?.focus()) }"
        x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        @click.self="sellerId = null"
        class="fixed inset-0 z-50 flex items-end justify-center bg-[#2B1730]/50 p-0 sm:items-center sm:p-4"
        role="dialog" aria-modal="true" aria-labelledby="sc-seller-title-{{ $seller->id }}">

        <div @click.stop
            class="flex max-h-[min(88dvh,760px)] w-full max-w-3xl flex-col overflow-hidden rounded-t-3xl bg-white shadow-2xl sm:rounded-3xl"
            x-show="sellerId === {{ $seller->id }}"
            x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="translate-y-6 opacity-0 sm:scale-95" x-transition:enter-end="translate-y-0 opacity-100 sm:scale-100"
            x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="translate-y-4 opacity-0">

            {{-- Header --}}
            <div class="flex items-start gap-4 border-b border-[#ece4ec] px-5 py-4 sm:px-6">
                <span aria-hidden="true" class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-[#EFE4F1] text-lg font-semibold text-[#5b2963]">
                    {{ strtoupper(mb_substr($seller->name, 0, 1)) }}
                </span>
                <div class="min-w-0 flex-1">
                    <h3 id="sc-seller-title-{{ $seller->id }}" class="font-display text-lg font-semibold leading-tight text-[#2B1730] [overflow-wrap:anywhere]">{{ $seller->name }}</h3>
                    <p class="text-sm text-gray-500 [overflow-wrap:anywhere]">{{ $seller->email }}</p>
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-medium
                            {{ $suspended ? 'border-red-600/60 bg-red-50 text-red-700' : 'border-green-600/60 bg-green-50 text-green-700' }}">
                            <x-admin.icon :name="$suspended ? 'ban' : 'shield-check'" class="h-3.5 w-3.5" />
                            {{ $suspended ? 'Suspended' : 'Compliant' }}
                        </span>
                        @foreach ($seller->categories as $category)
                            @include('admin.seller-compliance.partials.category-badge', ['category' => $category])
                        @endforeach
                    </div>
                </div>
                <button type="button" id="sc-seller-close-{{ $seller->id }}" @click="sellerId = null" aria-label="Close seller details"
                    class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full text-gray-500 transition-colors duration-150 hover:bg-[#F1E9F1] hover:text-[#3b1735]
                           focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40">
                    <x-admin.icon name="x" class="h-5 w-5" />
                </button>
            </div>

            {{-- Summary --}}
            <div class="grid grid-cols-2 gap-3 border-b border-[#ece4ec] bg-[#FBF8FB] px-5 py-4 sm:grid-cols-4 sm:px-6">
                @foreach ([
                    ['Products', number_format($productTotal), 'text-[#2B1730]'],
                    ['Compliance score', $score === null ? 'Not set' : $score . '%', $score === null ? 'text-gray-500' : ($score >= 80 ? 'text-green-700' : ($score >= 50 ? 'text-amber-700' : 'text-red-700'))],
                    ['Warnings', $seller->product_warnings_count, $seller->product_warnings_count > 0 ? 'text-orange-700' : 'text-gray-500'],
                    ['Violations', $seller->product_violations_count, $seller->product_violations_count > 0 ? 'text-red-700' : 'text-gray-500'],
                ] as [$label, $value, $tone])
                    <div>
                        <p class="text-xs text-gray-500">{{ $label }}</p>
                        <p class="text-xl font-semibold tabular-nums {{ $tone }}">{{ $value }}</p>
                    </div>
                @endforeach
            </div>

            {{-- Tabs --}}
            <div role="tablist" aria-label="Seller details" class="flex gap-1 border-b border-[#ece4ec] px-4 sm:px-5">
                @foreach (['products' => 'Products', 'issues' => 'Warnings and violations'] as $id => $label)
                    <button type="button" role="tab" @click="tab = '{{ $id }}'" :aria-selected="tab === '{{ $id }}'"
                        class="relative px-3 py-3 text-sm font-medium text-gray-500 transition-colors duration-150 hover:text-[#3b1735] aria-selected:text-[#3b1735]
                               after:absolute after:inset-x-3 after:bottom-0 after:h-[3px] after:origin-left after:scale-x-0 after:rounded-t after:bg-[#3b1735] after:transition-transform after:duration-300
                               aria-selected:after:scale-x-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-[#3b1735]/40">
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            <div class="thin-scroll min-h-[220px] flex-1 overflow-y-auto px-5 py-4 sm:px-6">

                {{-- Products --}}
                <div x-show="tab === 'products'">
                    @if ($products->isEmpty())
                        <div class="flex flex-col items-center px-4 py-12 text-center">
                            <span class="mb-3 flex h-14 w-14 items-center justify-center rounded-full bg-[#F1E9F1] text-[#5b2963]"><x-admin.icon name="package" class="h-6 w-6" /></span>
                            <p class="font-display text-base font-semibold text-[#2B1730]">No products yet</p>
                            <p class="mt-1 max-w-xs text-sm text-gray-500">This seller hasn't listed anything. Their products will show here once they do.</p>
                        </div>
                    @else
                        <div class="mb-4 flex flex-wrap gap-2" role="group" aria-label="Filter products by status">
                            @foreach ($filters as [$value, $label, $count])
                                <button type="button" @click="filter = '{{ $value }}'" :aria-pressed="filter === '{{ $value }}'"
                                    class="rounded-full border border-[#ddd0e0] px-3 py-1.5 text-xs font-medium text-gray-600 transition-colors duration-150 hover:bg-[#F7F1F7]
                                           aria-pressed:border-[#3b1735] aria-pressed:bg-[#3b1735] aria-pressed:text-white
                                           focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40">
                                    {{ $label }} <span class="tabular-nums opacity-70">{{ $count }}</span>
                                </button>
                            @endforeach
                        </div>

                        <ul class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            @foreach ($products as $product)
                                @php
                                    $cover = $product->images->first();
                                    $badge = $productStatus[$product->status] ?? [ucfirst(str_replace('_', ' ', (string) $product->status)), 'bg-gray-100 text-gray-600'];
                                @endphp
                                <li x-show="filter === 'all' || filter === '{{ $product->status }}'"
                                    class="flex gap-3 rounded-2xl border border-[#ece4ec] p-3 transition-colors duration-150 hover:border-[#d9c8dd]">
                                    <span class="h-16 w-16 flex-shrink-0 overflow-hidden rounded-xl bg-[#F1E9F1]">
                                        @if ($cover)
                                            <img src="{{ Storage::url($cover->path) }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                        @else
                                            <span class="flex h-full w-full items-center justify-center text-[#b9a4bd]"><x-admin.icon name="image" class="h-6 w-6" /></span>
                                        @endif
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <p class="line-clamp-2 text-sm font-medium leading-snug text-[#2B1730]" title="{{ $product->name }}">{{ $product->name }}</p>
                                        <p class="mt-0.5 text-xs text-gray-500">{{ $product->category?->name ?? 'No category' }}</p>
                                        <div class="mt-1.5 flex flex-wrap items-center gap-x-2.5 gap-y-1">
                                            <span class="text-sm font-semibold tabular-nums text-[#2B1730]">₱{{ number_format($product->price, 2) }}</span>
                                            <span class="text-xs {{ (int) $product->stock === 0 ? 'font-medium text-red-600' : 'text-gray-500' }}">
                                                {{ (int) $product->stock === 0 ? 'Out of stock' : $product->stock . ' in stock' }}
                                            </span>
                                        </div>
                                        <span class="mt-1.5 inline-flex rounded-full px-2 py-0.5 text-[11px] font-medium {{ $badge[1] }}">{{ $badge[0] }}</span>
                                    </div>
                                </li>
                            @endforeach
                        </ul>

                        @if ($productTotal > $products->count())
                            <p class="mt-4 text-center text-xs text-gray-500">Showing the latest {{ $products->count() }} of {{ number_format($productTotal) }} products.</p>
                        @endif
                    @endif
                </div>

                {{-- Warnings and violations --}}
                <div x-show="tab === 'issues'" x-cloak>
                    @if ($warnings->isEmpty() && $violations->isEmpty())
                        <div class="flex flex-col items-center px-4 py-12 text-center">
                            <span class="mb-3 flex h-14 w-14 items-center justify-center rounded-full bg-green-50 text-green-700"><x-admin.icon name="shield-check" class="h-6 w-6" /></span>
                            <p class="font-display text-base font-semibold text-[#2B1730]">Clean record</p>
                            <p class="mt-1 max-w-xs text-sm text-gray-500">No warnings or violations have been recorded for this seller.</p>
                        </div>
                    @else
                        <div class="space-y-5">
                            @foreach ([['Violations', $violations, 'red', 'shield-x'], ['Warnings', $warnings, 'orange', 'triangle-alert']] as [$heading, $rows, $tone, $icon])
                                @if ($rows->isNotEmpty())
                                    <section>
                                        <h4 class="mb-2 text-sm font-semibold text-[#2B1730]">{{ $heading }}</h4>
                                        <ul class="space-y-2">
                                            @foreach ($rows as $row)
                                                <li class="flex gap-3 rounded-xl border border-[#ece4ec] p-3">
                                                    <span class="mt-0.5 flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full {{ $tone === 'red' ? 'bg-red-50 text-red-700' : 'bg-orange-50 text-orange-700' }}">
                                                        <x-admin.icon :name="$icon" class="h-4 w-4" />
                                                    </span>
                                                    <div class="min-w-0">
                                                        <p class="text-sm font-medium text-[#2B1730] [overflow-wrap:anywhere]">{{ $row->reason }}</p>
                                                        @if ($row->details)<p class="mt-0.5 text-sm text-gray-600 [overflow-wrap:anywhere]">{{ $row->details }}</p>@endif
                                                        <p class="mt-1 text-xs text-gray-500">
                                                            {{ $row->created_at->format('M j, Y') }}@if ($row->product) · {{ $row->product->name }}@endif
                                                        </p>
                                                    </div>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </section>
                                @endif
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-[#ece4ec] px-5 py-3.5 sm:px-6">
                <p class="text-xs text-gray-500">Seller since {{ $seller->created_at->format('M j, Y') }}</p>
                <a href="{{ route('admin.user-management.index', ['search' => $seller->email]) }}"
                    class="inline-flex h-10 items-center gap-2 rounded-xl bg-[#3b1735] px-4 text-sm font-medium text-white transition-colors duration-150 hover:bg-[#4d1f45]
                           focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40 focus-visible:ring-offset-2">
                    Manage in User Management <x-admin.icon name="arrow-right" class="h-4 w-4" />
                </a>
            </div>
        </div>
    </div>
@endforeach
