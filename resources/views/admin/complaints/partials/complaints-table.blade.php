{{--
    Case list. Rendered inside #cs-region (casesPage scope) and swapped on every search, filter and page change.
    Needs: $complaints (paginator). The case drawers are rendered here too, so new rows always have one.
--}}
@php
    $filtered = request()->hasAny(['search', 'type', 'status', 'kind', 'custom_date'])
        || request('date_filter', 'all') !== 'all';
@endphp

<div class="cs-rows overflow-hidden rounded-2xl border border-[#ece4ec] bg-white shadow-[0_1px_2px_rgba(43,23,48,0.04)]">
    @if ($complaints->isEmpty())
        <div class="flex flex-col items-center px-6 py-16 text-center">
            @if ($filtered)
                <span class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-[#F1E9F1] text-[#5b2963]">
                    <x-admin.icon name="search" class="h-6 w-6" />
                </span>
                <h3 class="font-display text-base font-semibold text-[#2B1730]">No cases match these filters</h3>
                <p class="mt-1.5 max-w-sm text-sm text-gray-500">Try a different name, case type or date range, or clear the filters to see everything.</p>
                <button type="button" @click="clearAll()"
                    class="mt-5 inline-flex h-10 items-center gap-2 rounded-xl bg-[#3b1735] px-4 text-sm font-medium text-white transition-colors duration-150 hover:bg-[#4d1f45]
                           focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40 focus-visible:ring-offset-2">
                    <x-admin.icon name="x" class="h-4 w-4" /> Clear filters
                </button>
            @else
                <span class="cs-float mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-[#F1E9F1] text-[#5b2963]">
                    <x-admin.icon name="scale" class="h-7 w-7" />
                </span>
                <h3 class="font-display text-base font-semibold text-[#2B1730]">No complaints yet</h3>
                <p class="mt-1.5 max-w-sm text-sm text-gray-500">When a buyer or seller files an order complaint or reports an account, the case will appear here for you to review.</p>
                <button type="button" @click="sample = true; drawerId = null; $nextTick(() => document.getElementById('cs-sample')?.scrollIntoView({ block: 'center', behavior: 'smooth' }))"
                    class="mt-5 inline-flex h-10 items-center gap-2 rounded-xl border border-[#ddd0e0] px-4 text-sm font-medium text-[#3b1735] transition-colors duration-150 hover:bg-[#F7F1F7]
                           focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40">
                    <x-admin.icon name="eye" class="h-4 w-4" /> Preview a sample case
                </button>
            @endif
        </div>
    @else
        <div class="thin-scroll overflow-x-auto">
            <table class="w-full min-w-[820px] text-left text-sm">
                <caption class="sr-only">Complaints and disputes. Select a row to preview the case.</caption>
                <thead>
                    <tr class="border-b border-[#ece4ec] text-[13px] text-[#2B1730]">
                        <th scope="col" class="px-5 py-3.5 text-[15px] font-semibold">Complaint ID</th>
                        <th scope="col" class="px-4 py-3.5 text-[15px] font-semibold">Parties</th>
                        <th scope="col" class="px-4 py-3.5 text-center text-[15px] font-semibold">Type</th>
                        <th scope="col" class="px-4 py-3.5 text-center text-[15px] font-semibold">Status</th>
                        <th scope="col" class="px-5 py-3.5 text-right text-[15px] font-semibold">Date Filed</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#f3edf4]">
                    @foreach ($complaints as $complaint)
                        @include('admin.complaints.partials.complaint-row', ['complaint' => $complaint, 'index' => $loop->index])
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Shown by complaints.js when the status/kind tabs hide every row on this page --}}
        <div x-show="clientEmpty" x-cloak class="border-t border-[#ece4ec] px-6 py-10 text-center">
            <p class="text-sm font-medium text-[#2B1730]">No cases on this page match that tab</p>
            <button type="button" @click="setStatus(''); setKind('')" class="mt-2 text-sm font-medium text-[#3b1735] underline underline-offset-2">Show all cases</button>
        </div>

        @include('admin.seller-compliance.partials.pagination', ['paginator' => $complaints])
    @endif
</div>

@foreach ($complaints as $complaint)
    @include('admin.complaints.partials.preview-modal', ['complaint' => $complaint])
@endforeach