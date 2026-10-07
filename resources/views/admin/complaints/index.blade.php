<x-admin.layout title="Complaints and Disputes">
    @vite('resources/css/admin/complaints.css')

    @php
        $config = [
            'url' => route('admin.complaints.table'),
            'pageUrl' => url()->current(),
            'page' => max(1, (int) request('page', 1)),
            'filters' => [
                'search' => request()->string('search')->toString(),
                'type' => request()->string('type')->toString(),
                'date_filter' => request()->string('date_filter', 'all')->toString(),
                'custom_date' => request()->string('custom_date')->toString(),
                'status' => request()->string('status')->toString(),
                'kind' => request()->string('kind')->toString(),
            ],
        ];

        // Stat cards (from the mockup). Each one also filters the list by status.
        // Class strings are written out in full so Tailwind can see them.
        $cards = [
            ['', 'Total Complaints', $stats['total'], 'scale',
                'border-[#E6D79B] bg-[#FFFCF1]', 'border-[#C9A227] ring-2 ring-[#C9A227]/25', 'bg-[#F5E9B8] text-[#8A6A00]'],
            ['open', 'Open Cases', $stats['open'], 'inbox',
                'border-[#EBB19A] bg-[#FFF7F3]', 'border-[#C9603F] ring-2 ring-[#C9603F]/25', 'bg-[#F8D6C8] text-[#B4573B]'],
            ['in_review', 'In Progress', $stats['in_progress'], 'hourglass',
                'border-[#C2A7CE] bg-[#FAF6FC]', 'border-[#7D4E8F] ring-2 ring-[#7D4E8F]/25', 'bg-[#E9DBEF] text-[#6A3B7A]'],
            ['resolved', 'Resolved', $stats['resolved'], 'check-circle',
                'border-[#9BCFAB] bg-[#F4FBF6]', 'border-[#2F8F4E] ring-2 ring-[#2F8F4E]/25', 'bg-[#D4EDDC] text-[#166534]'],
        ];

        $kinds = ['' => 'All', 'complaint' => 'Order complaints', 'user_report' => 'Account reports'];

        $pill = 'inline-flex h-11 items-center gap-2 rounded-full border border-[#ddd0e0] bg-white pl-4 pr-3.5 text-sm text-[#2B1730] transition duration-200
                 hover:border-[#cdbbd2] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40';
        $menu = 'absolute right-0 z-30 mt-2 origin-top-right rounded-2xl border border-[#ece4ec] bg-white p-1.5 shadow-[0_18px_40px_-20px_rgba(43,23,48,0.4)]';
        $menuIn = 'transition duration-150 ease-out';
        $item = 'flex w-full items-center justify-between gap-3 rounded-xl px-3 py-2 text-left text-sm transition-colors duration-150';
    @endphp

    <div class="mx-auto w-full max-w-[1280px] p-4 sm:p-6" x-data="casesPage(@js($config))"
        @keydown.escape.window="dateOpen = false; typeOpen = false; if (drawerId !== null) drawerId = null"
        x-effect="document.body.classList.toggle('overflow-hidden', anyDialog)">

        <!-- Header -->
        <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="font-display text-2xl font-semibold text-[#2B1730]">Manage Complaints and Disputes</h1>
                <p class="mt-1 text-sm text-gray-500">Review complaint details and coordinate with buyer, seller, and courier.</p>
            </div>
            <button type="button" @click="sample = !sample; drawerId = null"
                :aria-pressed="sample"
                class="inline-flex h-10 items-center gap-2 rounded-xl border border-[#ddd0e0] bg-white px-4 text-sm font-medium text-[#3b1735] transition-colors duration-150 hover:bg-[#F7F1F7]
                       focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40">
                <x-admin.icon name="eye" class="h-4 w-4" />
                <span x-text="sample ? 'Hide sample case' : 'View a sample case'">View a sample case</span>
            </button>
        </div>

        <!-- Stat cards: also filter the list by status -->
        <div class="mb-5 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4" role="group" aria-label="Filter by status">
            @foreach ($cards as [$value, $label, $count, $icon, $idle, $selected, $chip])
                <button type="button" @click="setStatus('{{ $value }}')" style="--i: {{ $loop->index }}"
                    :aria-pressed="f.status === '{{ $value }}'"
                    :class="f.status === '{{ $value }}' ? '{{ $selected }}' : '{{ $idle }} hover:brightness-[0.98]'"
                    class="cs-card flex items-center gap-3 rounded-2xl border-2 p-3.5 text-left sm:p-4
                           focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40">
                    <span class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-full {{ $chip }}">
                        <x-admin.icon :name="$icon" class="h-[22px] w-[22px]" />
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-[13px] text-gray-600">{{ $label }}</span>
                        <span class="block text-2xl font-semibold leading-tight tracking-tight tabular-nums text-[#2B1730]">{{ number_format($count) }}</span>
                    </span>
                </button>
            @endforeach
        </div>

        <!-- Search + filters -->
        <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">

            <div class="relative w-full lg:max-w-md">
                <x-admin.icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                <input type="text" x-model="f.search" @input="onSearch()" @keydown.escape="f.search = ''; onSearch()"
                    autocomplete="off" aria-label="Search seller name or email" placeholder="Search seller name or email…"
                    class="h-11 w-full rounded-full border border-[#ddd0e0] bg-white pl-10 pr-10 text-sm text-[#2B1730] placeholder:text-gray-400
                           transition duration-200 focus:border-[#3b1735] focus:outline-none focus:ring-2 focus:ring-[#3b1735]/20">
                <button type="button" x-show="f.search !== ''" x-cloak @click="f.search = ''; apply()" aria-label="Clear search"
                    class="absolute right-2.5 top-1/2 flex h-6 w-6 -translate-y-1/2 items-center justify-center rounded-full text-gray-400 transition hover:bg-[#F1E9F1] hover:text-[#3b1735]">
                    <x-admin.icon name="x" class="h-3.5 w-3.5" stroke="2.4" />
                </button>
            </div>

            <div class="flex flex-wrap items-center gap-2.5">

                <!-- Case kind -->
                <div class="inline-flex rounded-full border border-[#ddd0e0] bg-white p-1" role="group" aria-label="Case kind">
                    @foreach ($kinds as $value => $label)
                        <button type="button" @click="setKind('{{ $value }}')" :aria-pressed="f.kind === '{{ $value }}'"
                            class="rounded-full px-3.5 py-2 text-sm font-medium text-gray-600 transition-colors duration-200 hover:text-[#3b1735]
                                   aria-pressed:bg-[#3b1735] aria-pressed:text-white
                                   focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>

                <!-- Type -->
                <div class="relative" @click.outside="typeOpen = false">
                    <button type="button" @click="typeOpen = !typeOpen; dateOpen = false" aria-haspopup="listbox" :aria-expanded="typeOpen" class="{{ $pill }}">
                        <x-admin.icon name="tag" class="h-4 w-4 text-gray-500" />
                        <span x-text="typeLabel" class="max-w-[160px] truncate">All types</span>
                        <x-admin.icon name="chevron-down" class="h-3.5 w-3.5 text-gray-500 transition-transform duration-200 ease-vendo" x-bind:class="typeOpen ? 'rotate-180' : ''" />
                    </button>

                    <div x-show="typeOpen" x-cloak role="listbox" aria-label="Case type"
                        x-transition:enter="{{ $menuIn }}" x-transition:enter-start="opacity-0 -translate-y-1 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                        x-transition:leave="transition duration-100 ease-in" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                        class="{{ $menu }} thin-scroll max-h-72 w-64 overflow-y-auto">
                        <button type="button" role="option" :aria-selected="f.type === ''" @click="pickType('')"
                            :class="f.type === '' ? 'bg-[#F7F1F7] font-medium text-[#3b1735]' : 'text-gray-700 hover:bg-[#FBF8FB]'" class="{{ $item }}">
                            All types
                            <x-admin.icon name="check" class="h-4 w-4 text-[#3b1735]" stroke="2.4" x-show="f.type === ''" />
                        </button>
                        @foreach ($types as $type)
                            <button type="button" role="option" :aria-selected="f.type === @js($type)" @click="pickType(@js($type))"
                                :class="f.type === @js($type) ? 'bg-[#F7F1F7] font-medium text-[#3b1735]' : 'text-gray-700 hover:bg-[#FBF8FB]'" class="{{ $item }}">
                                <span class="min-w-0 [overflow-wrap:anywhere]">{{ $type }}</span>
                                <x-admin.icon name="check" class="h-4 w-4 flex-shrink-0 text-[#3b1735]" stroke="2.4" x-show="f.type === @js($type)" />
                            </button>
                        @endforeach
                        @if ($types->isEmpty())
                            <p class="px-3 py-3 text-sm text-gray-500">Types appear once cases are filed.</p>
                        @endif
                    </div>
                </div>

                <!-- Date -->
                <div class="relative" @click.outside="dateOpen = false">
                    <button type="button" @click="dateOpen = !dateOpen; typeOpen = false" aria-haspopup="listbox" :aria-expanded="dateOpen" class="{{ $pill }}">
                        <x-admin.icon name="calendar" class="h-4 w-4 text-gray-500" />
                        <span x-text="dateLabel">All dates</span>
                        <x-admin.icon name="chevron-down" class="h-3.5 w-3.5 text-gray-500 transition-transform duration-200 ease-vendo" x-bind:class="dateOpen ? 'rotate-180' : ''" />
                    </button>

                    <div x-show="dateOpen" x-cloak role="listbox" aria-label="Date filed"
                        x-transition:enter="{{ $menuIn }}" x-transition:enter-start="opacity-0 -translate-y-1 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                        x-transition:leave="transition duration-100 ease-in" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                        class="{{ $menu }} w-60">
                        @foreach (['all' => 'All dates', 'today' => 'Today', 'week' => 'This week', 'month' => 'This month'] as $value => $label)
                            <button type="button" role="option" :aria-selected="f.date_filter === '{{ $value }}'" @click="pickDate('{{ $value }}')"
                                :class="f.date_filter === '{{ $value }}' ? 'bg-[#F7F1F7] font-medium text-[#3b1735]' : 'text-gray-700 hover:bg-[#FBF8FB]'" class="{{ $item }}">
                                {{ $label }}
                                <x-admin.icon name="check" class="h-4 w-4 text-[#3b1735]" stroke="2.4" x-show="f.date_filter === '{{ $value }}'" />
                            </button>
                        @endforeach
                        <div class="mt-1.5 border-t border-[#f3edf4] px-3 pb-2 pt-3">
                            <label for="cs-custom-date" class="mb-1.5 block text-xs font-medium text-gray-500">Specific day</label>
                            <input id="cs-custom-date" type="date" x-model="f.custom_date" @change="pickCustomDate()"
                                class="h-10 w-full rounded-xl border border-[#ddd0e0] px-3 text-sm text-[#2B1730] focus:border-[#3b1735] focus:outline-none focus:ring-2 focus:ring-[#3b1735]/20">
                        </div>
                    </div>
                </div>

                <button type="button" x-show="filtered" x-cloak @click="clearAll()"
                    x-transition:enter="transition duration-200" x-transition:enter-start="opacity-0 scale-90" x-transition:enter-end="opacity-100 scale-100"
                    class="inline-flex h-11 items-center gap-1.5 rounded-full px-3.5 text-sm font-medium text-[#3b1735] transition duration-200 hover:bg-[#F1E9F1]
                           focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40">
                    <x-admin.icon name="x" class="h-4 w-4" /> Clear
                </button>
            </div>
        </div>

        <!-- Request failed -->
        <div x-show="failed" x-cloak x-transition.opacity role="alert"
            class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-2.5 text-sm text-red-700">
            <span class="flex items-center gap-2"><x-admin.icon name="alert-circle" class="h-4 w-4" /> Couldn't load the list. Check your connection and try again.</span>
            <button type="button" @click="load()" class="font-semibold underline underline-offset-2 hover:text-red-800">Try again</button>
        </div>

        <!-- Sample case (preview only) -->
        @include('admin.complaints.partials.sample-case')

        <!-- Case list (swapped on every search, filter and page change) -->
        <div class="relative">
            <div x-show="loading" x-cloak x-transition.opacity class="cs-bar" role="progressbar" aria-label="Loading cases"></div>
            <div id="cs-region" x-ref="region" class="cs-table-wrap" :aria-busy="loading" @click="onRegionClick($event)">
                @include('admin.complaints.partials.complaints-table')
            </div>
        </div>
    </div>
@include('shared.live-revision', ['endpoint' => route('admin.live', 'cases'), 'mode' => 'reload'])
</x-admin.layout>