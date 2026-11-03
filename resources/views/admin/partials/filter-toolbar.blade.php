{{--
    Search + date + user-type dropdown shared by Registrations and User Management.
    Must sit inside the page's Alpine scope (q, date, type, onSearch(), apply(), clearAll(), filtered).

    $searchLabel     aria-label for the search box
    $rejectedToggle  (User Management only) also shows the "Rejected users" pill, which needs `status` in scope

    The dropdown defaults to the user-type filter. Other pages can reuse it (Messages uses it for status):
    $dropdownModel   Alpine property the dropdown sets (default 'type')
    $dropdownOptions value => [label, icon]
    $dropdownAll     label shown when the value is 'all' (default 'All users')
    $dropdownLabel   aria-label of the button (default 'Filter by user type')
    $searchPlaceholder  placeholder text of the search box
    $hideDate        true to leave out the date pill (pages that have no date to filter by)
--}}
@php
    // One pill style for every control in the row: soft lift on hover, small press on click.
    $pill = 'inline-flex h-10 items-center gap-2 rounded-full border border-vd-orchid bg-vd-paper px-3.5 text-[13px] font-medium text-vd-ink
             transition-all duration-200 ease-out hover:border-vd-plum hover:bg-white active:scale-[0.98]
             focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-plum/30';

    $dropdownModel = $dropdownModel ?? 'type';
    $dropdownAll = $dropdownAll ?? 'All users';
    $dropdownLabel = $dropdownLabel ?? 'Filter by user type';

    $courierLabel = ! empty($rejectedToggle) ? 'Courier' : 'Logistics';
    $typeOptions = $dropdownOptions ?? [
        'all' => ['All users', 'users'],
        'seller' => ['Sellers', 'store'],
        'buyer' => ['Buyers', 'user'],
        'logistics_center' => [$courierLabel, 'truck'],
    ];
@endphp

<div class="mb-5 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between">
    {{-- Search --}}
    <div class="group relative w-full sm:max-w-[400px]">
        <x-admin.icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-vd-plum transition-transform duration-200 group-focus-within:scale-110" />
        <input type="text" x-model="q" @input="onSearch()" @keydown.escape="q = ''; apply()"
            autocomplete="off" aria-label="{{ $searchLabel ?? 'Search' }}" placeholder="{{ $searchPlaceholder ?? 'Search seller name or email…' }}"
            class="h-10 w-full rounded-full border border-vd-orchid bg-vd-paper pl-10 pr-9 text-[13px] text-vd-ink placeholder:text-vd-muted
                   transition-all duration-200 ease-out hover:border-vd-plum hover:bg-white
                   focus:border-vd-plum focus:bg-white focus:outline-none focus:ring-2 focus:ring-vd-plum/20">
        <button type="button" x-show="q !== ''" x-cloak @click="q = ''; apply()" aria-label="Clear search"
            x-transition:enter="transition duration-150 ease-out" x-transition:enter-start="scale-75 opacity-0" x-transition:enter-end="scale-100 opacity-100"
            x-transition:leave="transition duration-100 ease-in" x-transition:leave-start="scale-100 opacity-100" x-transition:leave-end="scale-75 opacity-0"
            class="absolute right-2.5 top-1/2 flex h-6 w-6 -translate-y-1/2 items-center justify-center rounded-full text-vd-muted transition-colors duration-200 hover:bg-vd-lavender hover:text-vd-plum">
            <x-admin.icon name="x" class="h-3.5 w-3.5" />
        </button>
    </div>

    <div class="flex flex-wrap items-center gap-3">
        <button type="button" x-show="filtered" x-cloak @click="clearAll()"
            x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="-translate-x-1 opacity-0" x-transition:enter-end="translate-x-0 opacity-100"
            x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="inline-flex h-10 items-center justify-center rounded-full px-3 text-[13px] font-medium text-vd-plum transition-colors duration-200 hover:bg-vd-lavender
                   focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-plum/40">
            Clear filters
        </button>

        @if (empty($hideDate))
        {{-- Date: a pill that shows "All Dates" or the chosen day; the real date input sits invisibly on top --}}
        <label class="{{ $pill }} relative cursor-pointer focus-within:ring-2 focus-within:ring-vd-plum/30" title="Filter by date">
            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-vd-orchid/20 text-vd-plum">
                <x-admin.icon name="calendar" class="h-3.5 w-3.5" />
            </span>
            <span x-text="date ? new Date(date + 'T00:00:00').toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : 'All Dates'">All Dates</span>
            <input type="date" x-model="date" @change="apply()" @click="$el.showPicker && $el.showPicker()" aria-label="Filter by date"
                class="absolute inset-0 h-full w-full cursor-pointer opacity-0">
        </label>
        @endif

        {{-- User type: custom dropdown (same filter as clicking a stat card) --}}
        <div class="relative" x-data="{ open: false, labels: @js(collect($typeOptions)->map(fn ($o) => $o[0])) }"
            @click.outside="open = false" @keydown.escape="open = false">
            <button type="button" @click="open = !open" @keydown.arrow-down.prevent="open = true; $nextTick(() => $refs.list.querySelector('[role=option]')?.focus())"
                aria-haspopup="listbox" :aria-expanded="open" aria-label="{{ $dropdownLabel }}"
                :class="open ? '!border-vd-plum !bg-white' : ''"
                class="{{ $pill }} pr-3">
                <span x-text="labels[{{ $dropdownModel }}] || @js($dropdownAll)">{{ $dropdownAll }}</span>
                <x-admin.icon name="chevron-down" class="h-4 w-4 text-vd-plum transition-transform duration-200" ::class="open ? 'rotate-180' : ''" />
            </button>

            <div x-show="open" x-cloak x-ref="list" role="listbox" aria-label="{{ $dropdownLabel }}"
                x-transition:enter="transition duration-150 ease-out" x-transition:enter-start="-translate-y-1 scale-95 opacity-0" x-transition:enter-end="translate-y-0 scale-100 opacity-100"
                x-transition:leave="transition duration-100 ease-in" x-transition:leave-start="translate-y-0 scale-100 opacity-100" x-transition:leave-end="-translate-y-1 scale-95 opacity-0"
                @keydown.arrow-down.prevent="$event.target.nextElementSibling?.focus()"
                @keydown.arrow-up.prevent="$event.target.previousElementSibling?.focus()"
                class="absolute right-0 z-30 mt-2 w-52 origin-top-right rounded-2xl border border-vd-line-strong bg-white p-1.5">
                @foreach ($typeOptions as $value => [$label, $icon])
                    <button type="button" role="option" :aria-selected="{{ $dropdownModel }} === '{{ $value }}'"
                        @click="{{ $dropdownModel }} = '{{ $value }}'; open = false; apply()"
                        :class="{{ $dropdownModel }} === '{{ $value }}' ? 'bg-vd-lavender font-semibold text-vd-plum' : 'text-vd-ink hover:bg-vd-tint hover:text-vd-plum focus:bg-vd-tint'"
                        class="flex w-full items-center gap-2.5 rounded-xl px-2.5 py-2 text-left text-[13px] font-medium transition-colors duration-150 focus:outline-none">
                        <span class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full bg-vd-orchid/15 text-vd-plum">
                            <x-admin.icon name="{{ $icon }}" class="h-3.5 w-3.5" />
                        </span>
                        <span class="flex-1">{{ $label }}</span>
                        <x-admin.icon name="check" class="h-4 w-4 text-vd-plum" x-show="{{ $dropdownModel }} === '{{ $value }}'" />
                    </button>
                @endforeach
            </div>
        </div>

        @if (! empty($rejectedToggle))
            <button type="button" @click="status = status === 'rejected' ? 'all' : 'rejected'; apply()"
                :aria-pressed="status === 'rejected'"
                :class="status === 'rejected' ? '!border-vd-plum !bg-vd-lavender' : ''"
                class="{{ $pill }}">
                <x-admin.icon name="user-x" class="h-4 w-4 text-vd-plum" />
                Rejected users
            </button>
        @endif
    </div>
</div>
