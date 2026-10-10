<x-admin.layout title="Reports">
    {{-- Shared Registrations/User Management motion + loading styles (rg- prefix). --}}
    @vite('resources/css/admin/registrations.css')

    @php
        $reportConfig = [
            'indexUrl' => route('admin.reports.index'),
            'previewUrl' => route('admin.reports.preview'),
            'downloadUrl' => route('admin.reports.download'),
            'view' => $view,
            'dateFilter' => $dateFilter === 'monthly' ? 'month' : $dateFilter,
            'customDate' => $customDate ?: now()->toDateString(),
            'year' => (string) $year,
        ];
        $years = range((int) now()->format('Y'), (int) now()->format('Y') - 4);
    @endphp

    {{--
        Reports (UI pass 2, 7 Oct). The script lives in resources/js/admin/reports.js (Alpine component `reportsPage`,
        imported by layout.js). Everything dynamic sits in #rp-region (partials/report-body) so a period change swaps
        only that part. Preview, Download and CSV always act on the period that is on screen; before, the Preview and
        Download menus had their own period list that could disagree with the page.
    --}}
    <div class="rg-page mx-auto w-full max-w-[1280px] p-4 sm:p-6" x-data="reportsPage(@js($reportConfig))"
        @keydown.escape.window="previewOpen = false">

        {{-- Header --}}
        <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h1 class="font-display text-2xl font-semibold text-[#2B1730]">Reports</h1>
                <p class="mt-1 text-sm text-gray-500">Sales and commission for the period you pick. Exports match what you see.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" @click="openPreview()" aria-haspopup="dialog"
                    class="inline-flex h-11 items-center gap-2 rounded-full border border-[#cdbbd2] bg-white px-5 text-sm font-medium text-[#3b1735] transition duration-200
                           hover:bg-[#F7F1F7] active:scale-95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40">
                    <x-admin.icon name="eye" class="h-4 w-4" /> Preview
                </button>
                <button type="button" @click="exportCsv()" :disabled="report.sellers.length === 0"
                    class="inline-flex h-11 items-center gap-2 rounded-full border border-[#cdbbd2] bg-white px-5 text-sm font-medium text-[#3b1735] transition duration-200
                           hover:bg-[#F7F1F7] active:scale-95 disabled:cursor-not-allowed disabled:opacity-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40">
                    <x-admin.icon name="file" class="h-4 w-4" /> Export CSV
                </button>
                <a :href="downloadHref"
                    class="inline-flex h-11 items-center gap-2 rounded-full bg-[#3b1735] px-5 text-sm font-medium text-white transition duration-200 hover:bg-[#2B1730] active:scale-95
                           focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40 focus-visible:ring-offset-2">
                    <x-admin.icon name="file-text" class="h-4 w-4" /> Download PDF
                </a>
            </div>
        </div>

        {{-- Period --}}
        <div class="mb-5 flex flex-col gap-3 lg:flex-row lg:items-center">
            <div role="group" aria-label="Report period" class="thin-scroll inline-flex max-w-full overflow-x-auto rounded-full border border-[#ddd0e0] bg-white p-1">
                <template x-for="p in periods" :key="p.v">
                    <button type="button" @click="setPeriod(p.v)" :aria-pressed="period === p.v"
                        :class="period === p.v ? 'bg-[#3b1735] text-white shadow-sm' : 'text-gray-600 hover:bg-[#F1E9F1]'"
                        class="h-9 flex-shrink-0 whitespace-nowrap rounded-full px-4 text-sm font-medium transition duration-200
                               focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40" x-text="p.l"></button>
                </template>
            </div>

            <div x-show="period === 'custom'" x-cloak x-transition.opacity>
                <label class="sr-only" for="rp-date">Report date</label>
                <input id="rp-date" type="date" x-model="customDate" @change="load()" :max="new Date().toISOString().slice(0, 10)"
                    class="h-11 rounded-full border border-[#ddd0e0] bg-white px-4 text-sm text-[#2B1730] transition duration-200 hover:border-[#cdbbd2]
                           focus:border-[#3b1735] focus:outline-none focus:ring-2 focus:ring-[#3b1735]/20">
            </div>
            <div x-show="period === 'year'" x-cloak x-transition.opacity>
                <label class="sr-only" for="rp-year">Report year</label>
                <select id="rp-year" x-model="year" @change="load()"
                    class="h-11 rounded-full border border-[#ddd0e0] bg-white px-4 text-sm text-[#2B1730] transition duration-200 hover:border-[#cdbbd2]
                           focus:border-[#3b1735] focus:outline-none focus:ring-2 focus:ring-[#3b1735]/20">
                    @foreach ($years as $y)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="relative">
            <div x-show="loading" x-cloak x-transition.opacity class="rg-bar" role="progressbar" aria-label="Loading report"></div>

            <div id="rp-region" x-ref="region" class="rg-table-wrap" :aria-busy="loading">
                @include('admin.reports.partials.report-body')
            </div>

            <div x-show="failed" x-cloak role="alert"
                class="mt-4 flex flex-col items-start justify-between gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 sm:flex-row sm:items-center">
                <span>We couldn't load this report. Check your connection and try again.</span>
                <button type="button" @click="load()" class="rounded-full border border-red-300 bg-white px-4 py-1.5 text-[13px] font-medium text-red-700 hover:bg-red-100">Try again</button>
            </div>
        </div>

        {{-- Preview dialog (same HTML the PDF is built from) --}}
        <div x-show="previewOpen" x-cloak
            x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            @click.self="previewOpen = false"
            class="fixed inset-0 z-50 flex items-end justify-center bg-[#2B1730]/50 p-0 sm:items-center sm:p-4"
            role="dialog" aria-modal="true" aria-labelledby="rp-preview-title">
            <div x-show="previewOpen" @click.stop
                x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="translate-y-6 opacity-0 sm:scale-95" x-transition:enter-end="translate-y-0 opacity-100 sm:scale-100"
                x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="translate-y-4 opacity-0"
                class="flex max-h-[min(92dvh,860px)] w-full max-w-3xl flex-col overflow-hidden rounded-t-3xl bg-white shadow-2xl sm:rounded-3xl">
                <div class="flex items-center justify-between gap-4 border-b border-[#ece4ec] px-5 py-4 sm:px-6">
                    <div>
                        <h3 id="rp-preview-title" class="font-display text-lg font-semibold text-[#2B1730]">Report preview</h3>
                        <p class="text-sm text-gray-500" x-text="report.periodLabel"></p>
                    </div>
                    <button type="button" @click="previewOpen = false" aria-label="Close preview"
                        class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full text-gray-500 transition-colors duration-150 hover:bg-[#F1E9F1] hover:text-[#3b1735]
                               focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40">
                        <x-admin.icon name="x" class="h-5 w-5" />
                    </button>
                </div>
                <div class="thin-scroll flex-1 overflow-y-auto px-5 py-5 sm:px-6">
                    <div x-show="previewLoading" x-cloak class="space-y-3" role="status" aria-label="Loading preview">
                        <div class="h-12 animate-pulse rounded-xl bg-[#F4EEF4]"></div><div class="h-28 animate-pulse rounded-xl bg-[#F4EEF4]"></div><div class="h-40 animate-pulse rounded-xl bg-[#F4EEF4]"></div>
                    </div>
                    <p x-show="previewFailed" x-cloak role="alert" class="rounded-xl bg-red-50 px-4 py-6 text-center text-sm text-red-700">The preview couldn't load. Close this and try again.</p>
                    <div x-show="!previewLoading && !previewFailed" x-html="previewHtml"></div>
                </div>
                <div class="flex flex-col-reverse gap-2 border-t border-[#ece4ec] bg-[#FBF8FB] px-5 py-3.5 sm:flex-row sm:justify-end sm:px-6">
                    <button type="button" @click="previewOpen = false"
                        class="inline-flex h-10 items-center justify-center rounded-full border border-[#d9ccdc] bg-white px-5 text-sm font-medium text-gray-700 transition duration-200 hover:bg-[#F7F1F7] active:scale-95
                               focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40">Close</button>
                    <a :href="downloadHref"
                        class="inline-flex h-10 items-center justify-center gap-1.5 rounded-full bg-[#3b1735] px-5 text-sm font-medium text-white transition duration-200 hover:bg-[#2B1730] active:scale-95
                               focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40 focus-visible:ring-offset-2">
                        <x-admin.icon name="file-text" class="h-4 w-4" /> Download PDF
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-admin.layout>