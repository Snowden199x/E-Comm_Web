{{--
    Rendered on first load and again by GET admin.registrations.table.
    It expects the Alpine scope from registrations/index.blade.php (filtered, clearAll).
    Pagination uses data-page buttons handled by that scope, so page links never
    navigate to the bare /registrations/table partial.
--}}
@php
    $roleChip = [
        'seller' => 'bg-[#F1E7F3] text-[#5b2963]',
        'buyer' => 'bg-[#FBF0E1] text-[#8a5614]',
        'logistics_center' => 'bg-[#E4F1EE] text-[#2b6258]',
    ];

    $current = $registrations->currentPage();
    $last = $registrations->lastPage();
    // 1 … current-1 current current+1 … last
    $pageWindow = collect([1, $last, $current - 1, $current, $current + 1])
        ->filter(fn ($p) => $p >= 1 && $p <= $last)
        ->unique()->sort()->values();
@endphp

<div class="overflow-hidden rounded-2xl border border-[#ece4ec] bg-white shadow-[0_1px_2px_rgba(43,23,48,0.04)]">

    @if ($registrations->isEmpty())
        <div class="flex flex-col items-center px-6 py-16 text-center">
            <span class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-[#F1E7F3] text-[#5b2963]">
                <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 13V7a2 2 0 00-2-2H6a2 2 0 00-2 2v6m16 0v4a2 2 0 01-2 2H6a2 2 0 01-2-2v-4m16 0h-4.5a1 1 0 00-.9.55l-.7 1.4a1 1 0 01-.9.55h-2a1 1 0 01-.9-.55l-.7-1.4a1 1 0 00-.9-.55H4" />
                </svg>
            </span>
            <p class="text-base font-semibold text-[#2B1730]">No pending registrations</p>
            <p class="mt-1 max-w-sm text-sm text-gray-500" x-cloak
                x-text="filtered
                    ? 'Nothing matches these filters. Try a different name, date, or user type.'
                    : 'New applications show up here as soon as someone signs up.'">
                New applications show up here as soon as someone signs up.
            </p>
            <button type="button" x-show="filtered" x-cloak @click="clearAll()"
                class="mt-5 inline-flex h-10 items-center rounded-full border border-[#cdbbd2] px-5 text-sm font-medium text-[#3b1735]
                       transition duration-200 hover:bg-[#3b1735] hover:text-white active:scale-95">
                Clear filters
            </button>
        </div>
    @else
        <div class="overflow-x-auto thin-scroll">
            <table class="w-full min-w-[820px] text-left text-sm">
                <caption class="sr-only">Pending registrations</caption>
                <thead>
                    <tr class="border-b border-[#ece4ec] bg-[#FBF8FB] text-[13px] text-gray-500">
                        <th scope="col" class="px-5 py-3 font-medium">Applicant</th>
                        <th scope="col" class="px-4 py-3 font-medium">User Type</th>
                        <th scope="col" class="px-4 py-3 font-medium">Email</th>
                        <th scope="col" class="px-4 py-3 font-medium">Number</th>
                        <th scope="col" class="px-4 py-3 font-medium">Date Applied</th>
                        <th scope="col" class="px-5 py-3 text-right font-medium">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#f3edf4]">
                    @foreach ($registrations as $reg)
                        <tr style="--i: {{ $loop->index }}" class="transition-colors duration-150 hover:bg-[#FBF8FB]">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    <span aria-hidden="true"
                                        class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-[#EFE4F1] text-[13px] font-semibold text-[#5b2963]">
                                        {{ strtoupper(mb_substr($reg->name, 0, 1)) }}
                                    </span>
                                    <a href="{{ route('admin.registrations.show', $reg) }}" x-target.push="main-content sidebar"
                                        class="max-w-[220px] truncate font-medium text-[#2B1730] hover:text-[#3b1735] hover:underline">
                                        {{ $reg->name }}
                                    </a>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium {{ $roleChip[$reg->role] ?? 'bg-gray-100 text-gray-600' }}">
                                    {{ ucwords(str_replace('_', ' ', $reg->role)) }}
                                </span>
                            </td>
                            <td class="max-w-[240px] truncate px-4 py-3 text-gray-600">{{ $reg->email }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ $reg->phone_number ?? '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ $reg->created_at->format('M j, Y') }}</td>
                            <td class="px-5 py-3 text-right">
                                <a href="{{ route('admin.registrations.show', $reg) }}" x-target.push="main-content sidebar"
                                    aria-label="View application from {{ $reg->name }}"
                                    class="inline-flex h-8 items-center rounded-full border border-[#cdbbd2] px-4 text-[13px] font-medium text-[#3b1735]
                                           transition duration-200 hover:bg-[#3b1735] hover:text-white active:scale-95
                                           focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40">
                                    View
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="flex flex-col items-center justify-between gap-3 border-t border-[#ece4ec] px-5 py-3.5 sm:flex-row">
            <p class="text-[13px] text-gray-500">
                Showing <span class="font-medium text-gray-700">{{ $registrations->firstItem() }}–{{ $registrations->lastItem() }}</span>
                of <span class="font-medium text-gray-700">{{ number_format($registrations->total()) }}</span> entries
            </p>

            @if ($registrations->hasPages())
                <nav aria-label="Pagination" class="flex items-center gap-1.5">
                    <button type="button" data-page="{{ $current - 1 }}" @if ($current <= 1) disabled @endif aria-label="Previous page"
                        class="flex h-8 w-8 items-center justify-center rounded-lg border border-[#e2d6e5] text-gray-600 transition duration-150
                               hover:bg-[#F7F1F7] disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:bg-transparent">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                    </button>

                    @foreach ($pageWindow as $i => $p)
                        @if ($i > 0 && $p - $pageWindow[$i - 1] > 1)
                            <span class="px-1 text-gray-400" aria-hidden="true">…</span>
                        @endif
                        <button type="button" data-page="{{ $p }}" aria-label="Page {{ $p }}"
                            @if ($p === $current) aria-current="page" @endif
                            class="flex h-8 min-w-8 items-center justify-center rounded-lg border px-2 text-[13px] font-medium transition duration-150
                                   {{ $p === $current
                                       ? 'border-[#3b1735] bg-[#3b1735] text-white'
                                       : 'border-[#e2d6e5] text-gray-600 hover:bg-[#F7F1F7]' }}">
                            {{ $p }}
                        </button>
                    @endforeach

                    <button type="button" data-page="{{ $current + 1 }}" @if ($current >= $last) disabled @endif aria-label="Next page"
                        class="flex h-8 w-8 items-center justify-center rounded-lg border border-[#e2d6e5] text-gray-600 transition duration-150
                               hover:bg-[#F7F1F7] disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:bg-transparent">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                    </button>
                </nav>
            @endif
        </div>
    @endif
</div>