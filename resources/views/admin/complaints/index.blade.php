<x-admin-layout>
    <div class="p-4 sm:p-5 lg:p-6" x-data="{ previewId: null }">
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Manage Complaints and Disputes</h2>
            <p class="text-gray-500">Review complaint details and coordinate with buyer, seller, and courier</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-6">
            <div class="bg-yellow-50 border-2 border-yellow-400 rounded-2xl p-4 flex items-center gap-3">
                <img src="{{ asset('assets/icons/complaints_disputes/total-complaints-icon.svg') }}" alt=""
                    class="w-10 h-10">
                <div>
                    <p class="text-xs text-gray-600">Total Complaints</p>
                    <p class="text-xl font-bold text-black">{{ number_format($stats['total']) }}</p>
                </div>
            </div>
            <div class="bg-red-50 border-2 border-red-500 rounded-2xl p-4 flex items-center gap-3">
                <img src="{{ asset('assets/icons/complaints_disputes/open-cases-icon.svg') }}" alt=""
                    class="w-10 h-10">
                <div>
                    <p class="text-xs text-gray-600">Open Cases</p>
                    <p class="text-xl font-bold text-black">{{ number_format($stats['open']) }}</p>
                </div>
            </div>
            <div class="bg-purple-50 border-2 border-purple-400 rounded-2xl p-4 flex items-center gap-3">
                <img src="{{ asset('assets/icons/complaints_disputes/in-progress-icon.svg') }}" alt=""
                    class="w-10 h-10">
                <div>
                    <p class="text-xs text-gray-600">In progress</p>
                    <p class="text-xl font-bold text-black">{{ number_format($stats['in_progress']) }}</p>
                </div>
            </div>
            <div class="bg-green-50 border-2 border-green-500 rounded-2xl p-4 flex items-center gap-3">
                <img src="{{ asset('assets/icons/complaints_disputes/resolved-icon.svg') }}" alt=""
                    class="w-10 h-10">
                <div>
                    <p class="text-xs text-gray-600">Resolved</p>
                    <p class="text-xl font-bold text-black">{{ number_format($stats['resolved']) }}</p>
                </div>
            </div>
        </div>

        <div x-data="{
            q: '{{ request('search') }}',
            typeFilter: '{{ request('type') }}',
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
                    const params = new URLSearchParams({ search: this.q, type: this.typeFilter, date_filter: this.dateFilter, custom_date: this.customDate });
                    fetch('{{ route('complaints.table') }}?' + params)
                        .then(r => r.text()).then(html => { document.getElementById('complaints-table-wrap').innerHTML = html; });
                }, 250);
            }
        }">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                <div class="flex items-center gap-3">
                    <div class="relative w-80">
                        <img src="{{ asset('assets/icons/user-management/search-icon.svg') }}" alt=""
                            class="w-4 h-4 absolute left-4 top-1/2 -translate-y-1/2 opacity-50">
                        <input type="text" x-model="q" @input="search" autocomplete="off"
                            placeholder="Search seller name or email..."
                            class="w-full pl-10 pr-4 py-2.5 rounded-full border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#3b1735]">
                    </div>

                    <div class="relative" x-data="{ legendOpen: false }" @click.outside="legendOpen = false">
                        <button type="button" @click="legendOpen = !legendOpen"
                            class="px-3 py-2.5 rounded-lg border border-gray-200 text-sm flex items-center gap-2">
                            Legend
                            <span class="text-gray-400">&#9662;</span>
                        </button>
                        <div x-show="legendOpen" x-cloak
                            class="absolute left-0 z-10 mt-1 w-64 bg-white rounded-lg border border-gray-100 shadow-lg p-3">
                            <p class="text-xs font-semibold text-gray-500 mb-2">Complaint Types</p>
                            <div class="max-h-[170px] overflow-y-auto space-y-1.5 mb-3 pr-1">
                                @foreach ([
        'Item Not Received' => ['#9A4A00', '#FCE8D2'],
        'Wrong Item Received' => ['#7A4E00', '#F7E9C6'],
        'Damaged Item' => ['#A33A3A', '#F8DADA'],
        'Missing Item' => ['#805A00', '#F5E7B8'],
        'Item Not as Described' => ['#7A3F72', '#EEDDF0'],
        'Payment Issue' => ['#315A8A', '#DCE8F5'],
        'Refund Issue' => ['#4C5C8A', '#E1E6F5'],
        'Seller Issue' => ['#8A3F3F', '#F2DADA'],
        'Delivery Delay' => ['#A35C00', '#F9E2C7'],
        'Delivery Issue' => ['#365F70', '#DCECEF'],
        'Courier Issue' => ['#596B3D', '#E4ECD7'],
        'Other' => ['#666666', '#E9E9E9'],
    ] as $label => $c)
                                    <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium border"
                                        style="border-color: {{ $c[0] }}; background-color: {{ $c[1] }}; color: {{ $c[0] }};">{{ $label }}</span>
                                @endforeach
                            </div>
                            <p class="text-xs font-semibold text-gray-500 mb-2">Status</p>
                            <div class="space-y-1.5">
                                @foreach ([
        'Open' => ['#B45309', '#FEF3C7'],
        'In Progress' => ['#2563EB', '#DBEAFE'],
        'Resolved' => ['#15803D', '#DCFCE7'],
    ] as $label => $c)
                                    <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium border"
                                        style="border-color: {{ $c[0] }}; background-color: {{ $c[1] }}; color: {{ $c[0] }};">{{ $label }}</span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex gap-3">
                    <div class="relative" @click.outside="dateOpen = false">
                        <button type="button" @click="dateOpen = !dateOpen"
                            class="px-3 py-2.5 rounded-lg border border-gray-200 text-sm flex items-center gap-2 min-w-[140px] justify-between">
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

                    <select x-model="typeFilter" @change="search"
                        class="px-3 py-2.5 rounded-lg border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#3b1735]">
                        <option value="">All types</option>
                        <option value="Item Not Received">Item Not Received</option>
                        <option value="Wrong Item Received">Wrong Item Received</option>
                        <option value="Damaged Item">Damaged Item</option>
                        <option value="Missing Item">Missing Item</option>
                        <option value="Item Not as Described">Item Not as Described</option>
                        <option value="Payment Issue">Payment Issue</option>
                        <option value="Refund Issue">Refund Issue</option>
                        <option value="Seller Issue">Seller Issue</option>
                        <option value="Delivery Delay">Delivery Delay</option>
                        <option value="Delivery Issue">Delivery Issue</option>
                        <option value="Courier Issue">Courier Issue</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
            </div>

            <div id="complaints-table-wrap">
                @include('admin.complaints.partials.complaints-table')
            </div>
        </div>

        @if (isset($complaints))
            @foreach ($complaints as $complaint)
                @include('admin.complaints.partials.preview-modal', ['complaint' => $complaint])
            @endforeach
        @endif
    </div>
</x-admin-layout>
