{{--
    Toolbar + table region shared by every Seller Compliance tab.

    Needs:
      $table            Blade view of the table partial (rendered inside #sc-region and swapped on every change)
      $url              Route of that table partial (existing *-table route)
      $placeholder      Search placeholder
    Optional:
      $showCategory     Category dropdown (needs $categories)
      $showDate         Date dropdown (All / Today / This week / This month / custom day)
      $outside          Blade view rendered inside the scope but outside the swapped region (confirmation dialog)

    Scope and behavior live in resources/js/admin/seller-compliance.js (Alpine.data('scTable')).
--}}
@php
    $showCategory = $showCategory ?? false;
    $showDate = $showDate ?? false;
    $outside = $outside ?? null;

    $config = [
        'url' => $url,
        'pageUrl' => url()->current(),
        'page' => max(1, (int) request('page', 1)),
        'filters' => [
            'search' => request()->string('search')->toString(),
            'category_id' => request()->string('category_id')->toString(),
            'date_filter' => request()->string('date_filter', 'all')->toString(),
            'custom_date' => request()->string('custom_date')->toString(),
        ],
        'categories' => $showCategory && isset($categories)
            ? $categories->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->values()
            : [],
        'confirmation' => session('confirmation'),
    ];

    $pill = 'inline-flex h-11 items-center gap-2 rounded-full border border-[#ddd0e0] bg-white pl-4 pr-3.5 text-sm text-[#2B1730] transition duration-200
             hover:border-[#cdbbd2] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40';
    $menu = 'absolute right-0 z-30 mt-2 origin-top-right rounded-2xl border border-[#ece4ec] bg-white p-1.5 shadow-[0_18px_40px_-20px_rgba(43,23,48,0.4)]';
    $menuIn = 'transition duration-150 ease-out';
    $item = 'flex w-full items-center justify-between gap-3 rounded-xl px-3 py-2 text-left text-sm transition-colors duration-150';
@endphp

<div x-data="scTable(@js($config))"
    @keydown.escape.window="catOpen = false; dateOpen = false; if (anyDialog && !$event.defaultPrevented) closeDialogs()"
    x-effect="document.body.classList.toggle('overflow-hidden', anyDialog)">

    {{-- Search + filters --}}
    <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">

        <div class="relative w-full lg:max-w-md">
            <x-admin.icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
            <input type="text" x-model="f.search" @input="onSearch()" @keydown.escape="f.search = ''; onSearch()"
                autocomplete="off" aria-label="{{ $placeholder }}" placeholder="{{ $placeholder }}"
                class="h-11 w-full rounded-full border border-[#ddd0e0] bg-white pl-10 pr-10 text-sm text-[#2B1730] placeholder:text-gray-400
                       transition duration-200 focus:border-[#3b1735] focus:outline-none focus:ring-2 focus:ring-[#3b1735]/20">
            <button type="button" x-show="f.search !== ''" x-cloak @click="f.search = ''; apply()" aria-label="Clear search"
                x-transition:enter="transition duration-150" x-transition:enter-start="opacity-0 scale-75" x-transition:enter-end="opacity-100 scale-100"
                class="absolute right-2.5 top-1/2 flex h-6 w-6 -translate-y-1/2 items-center justify-center rounded-full text-gray-400 transition hover:bg-[#F1E9F1] hover:text-[#3b1735]">
                <x-admin.icon name="x" class="h-3.5 w-3.5" stroke="2.4" />
            </button>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">

            @if ($showCategory)
                <div class="relative" @click.outside="catOpen = false">
                    <button type="button" @click="catOpen = !catOpen; dateOpen = false" aria-haspopup="listbox" :aria-expanded="catOpen" class="{{ $pill }}">
                        <x-admin.icon name="tag" class="h-4 w-4 text-gray-500" />
                        <span x-text="categoryLabel" class="max-w-[160px] truncate">All categories</span>
                        <x-admin.icon name="chevron-down" class="h-3.5 w-3.5 text-gray-500 transition-transform duration-200 ease-vendo" x-bind:class="catOpen ? 'rotate-180' : ''" />
                    </button>

                    <div x-show="catOpen" x-cloak role="listbox" aria-label="Category"
                        x-transition:enter="{{ $menuIn }}" x-transition:enter-start="opacity-0 -translate-y-1 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                        x-transition:leave="transition duration-100 ease-in" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                        class="{{ $menu }} thin-scroll max-h-72 w-64 overflow-y-auto">
                        <button type="button" role="option" :aria-selected="f.category_id === ''" @click="pickCategory('')"
                            :class="f.category_id === '' ? 'bg-[#F7F1F7] font-medium text-[#3b1735]' : 'text-gray-700 hover:bg-[#FBF8FB]'" class="{{ $item }}">
                            All categories
                            <x-admin.icon name="check" class="h-4 w-4 text-[#3b1735]" stroke="2.4" x-show="f.category_id === ''" />
                        </button>
                        @foreach ($categories ?? [] as $cat)
                            <button type="button" role="option" :aria-selected="f.category_id === '{{ $cat->id }}'" @click="pickCategory('{{ $cat->id }}')"
                                :class="f.category_id === '{{ $cat->id }}' ? 'bg-[#F7F1F7] font-medium text-[#3b1735]' : 'text-gray-700 hover:bg-[#FBF8FB]'" class="{{ $item }}">
                                <span class="flex min-w-0 items-center gap-2">
                                    <i aria-hidden="true" class="h-2.5 w-2.5 flex-shrink-0 rounded-full" style="background-color: {{ $cat->colors['border'] }}"></i>
                                    <span class="truncate">{{ $cat->name }}</span>
                                </span>
                                <x-admin.icon name="check" class="h-4 w-4 flex-shrink-0 text-[#3b1735]" stroke="2.4" x-show="f.category_id === '{{ $cat->id }}'" />
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($showDate)
                <div class="relative" @click.outside="dateOpen = false">
                    <button type="button" @click="dateOpen = !dateOpen; catOpen = false" aria-haspopup="listbox" :aria-expanded="dateOpen" class="{{ $pill }}">
                        <x-admin.icon name="calendar" class="h-4 w-4 text-gray-500" />
                        <span x-text="dateLabel">All dates</span>
                        <x-admin.icon name="chevron-down" class="h-3.5 w-3.5 text-gray-500 transition-transform duration-200 ease-vendo" x-bind:class="dateOpen ? 'rotate-180' : ''" />
                    </button>

                    <div x-show="dateOpen" x-cloak role="listbox" aria-label="Date"
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
                            <label for="sc-custom-date" class="mb-1.5 block text-xs font-medium text-gray-500">Specific day</label>
                            <input id="sc-custom-date" type="date" x-model="f.custom_date" @change="pickCustomDate()"
                                class="h-10 w-full rounded-xl border border-[#ddd0e0] px-3 text-sm text-[#2B1730] focus:border-[#3b1735] focus:outline-none focus:ring-2 focus:ring-[#3b1735]/20">
                        </div>
                    </div>
                </div>
            @endif

            <button type="button" x-show="filtered" x-cloak @click="clearAll()"
                x-transition:enter="transition duration-200" x-transition:enter-start="opacity-0 scale-90" x-transition:enter-end="opacity-100 scale-100"
                class="inline-flex h-11 items-center gap-1.5 rounded-full px-3.5 text-sm font-medium text-[#3b1735] transition duration-200 hover:bg-[#F1E9F1]
                       focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40">
                <x-admin.icon name="x" class="h-4 w-4" /> Clear
            </button>
        </div>
    </div>

    <div x-show="failed" x-cloak x-transition.opacity role="alert"
        class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-2.5 text-sm text-red-700">
        <span class="flex items-center gap-2"><x-admin.icon name="alert-circle" class="h-4 w-4" /> Couldn't load the list. Check your connection and try again.</span>
        <button type="button" @click="load()" class="font-semibold underline underline-offset-2 hover:text-red-800">Try again</button>
    </div>

    {{-- Table region (swapped on every search, filter and page change) --}}
    <div class="relative">
        <div x-show="loading" x-cloak x-transition.opacity class="sc-bar" role="progressbar" aria-label="Loading"></div>
        <div id="sc-region" x-ref="region" class="sc-table-wrap" :aria-busy="loading" @click="onRegionClick($event)">
            @include($table)
        </div>
    </div>

    @if ($outside)
        @include($outside)
    @endif
</div>