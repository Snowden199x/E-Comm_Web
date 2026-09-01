<x-admin-layout>
    <div class="p-4 sm:p-5 lg:p-6">
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Warnings</h2>
            <p class="text-gray-500">Track warnings issued to sellers for policy violations.</p>
        </div>

        @include('admin.seller-compliance.partials.tabs')

        @include('admin.seller-compliance.partials.stat-cards', [
            'cards' => [
                [
                    'label' => 'Compliant Sellers',
                    'value' => $stats['compliant'],
                    'icon' => 'compliant-seller-icon.svg',
                    'color' => 'green',
                ],
                [
                    'label' => 'Sellers with Warnings',
                    'value' => $stats['with_warnings'],
                    'icon' => 'seller-with-warning-icon.svg',
                    'color' => 'orange',
                ],
                [
                    'label' => 'Sellers with Violence',
                    'value' => $stats['with_violations'],
                    'icon' => 'seller-with-violence-icon.svg',
                    'color' => 'red',
                ],
                [
                    'label' => 'Suspended Sellers',
                    'value' => $stats['suspended'],
                    'icon' => 'suspended-sellers-icon.svg',
                    'color' => 'purple',
                ],
            ],
        ])

        <div x-data="{
            q: '{{ request('search') }}',
            dateFilter: '{{ request('date_filter', 'all') }}',
            customDate: '{{ request('custom_date') }}',
            dateOpen: false,
            timer: null,
            dateLabel() {
                return { all: 'All Dates', today: 'Today', week: 'This Week', month: 'This Month', custom: this.customDate || 'Custom Date' } [this.dateFilter];
            },
            search() {
                clearTimeout(this.timer);
                this.timer = setTimeout(() => {
                    const params = new URLSearchParams({ search: this.q, date_filter: this.dateFilter, custom_date: this.customDate });
                    fetch('{{ route('seller-compliance.warnings-table') }}?' + params)
                        .then(r => r.text()).then(html => { document.getElementById('warnings-table-wrap').innerHTML = html; });
                }, 250);
            }
        }">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                <div class="flex-1 relative max-w-xs">
                    <img src="{{ asset('assets/icons/user-management/search-icon.svg') }}" alt=""
                        class="w-4 h-4 absolute left-4 top-1/2 -translate-y-1/2 opacity-50">
                    <input type="text" x-model="q" @input="search" autocomplete="off"
                        placeholder="Search seller name or email..."
                        class="w-full pl-10 pr-4 py-2.5 rounded-full border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#3b1735]">
                </div>

                <div class="relative" @click.outside="dateOpen = false">
                    <button type="button" @click="dateOpen = !dateOpen"
                        class="px-3 py-2.5 rounded-lg border border-gray-200 text-sm flex items-center gap-2 min-w-[160px] justify-between">
                        <span x-text="dateLabel()"></span>
                        <span class="text-gray-400">&#9662;</span>
                    </button>
                    <div x-show="dateOpen" x-cloak
                        class="absolute right-0 z-10 mt-1 w-56 bg-white rounded-lg border border-gray-100 shadow-lg p-2">
                        <button type="button" @click="dateFilter = 'all'; dateOpen = false; search()"
                            class="w-full text-left px-3 py-2 rounded hover:bg-gray-50 text-sm">All Dates</button>
                        <button type="button" @click="dateFilter = 'today'; dateOpen = false; search()"
                            class="w-full text-left px-3 py-2 rounded hover:bg-gray-50 text-sm">Today</button>
                        <button type="button" @click="dateFilter = 'week'; dateOpen = false; search()"
                            class="w-full text-left px-3 py-2 rounded hover:bg-gray-50 text-sm">This Week</button>
                        <button type="button" @click="dateFilter = 'month'; dateOpen = false; search()"
                            class="w-full text-left px-3 py-2 rounded hover:bg-gray-50 text-sm">This Month</button>
                        <div class="border-t border-gray-100 my-1"></div>
                        <label class="block px-3 py-1 text-xs text-gray-400">Custom Date</label>
                        <input type="date" x-model="customDate"
                            @change="dateFilter = 'custom'; dateOpen = false; search()"
                            class="w-full px-3 py-2 rounded border border-gray-200 text-sm">
                    </div>
                </div>
            </div>

            <div id="warnings-table-wrap">
                @include('admin.seller-compliance.partials.warnings-table')
            </div>
        </div>
    </div>
</x-admin-layout>
