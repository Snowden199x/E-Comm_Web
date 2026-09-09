<x-admin-layout>
    <div class="p-4 sm:p-5 lg:p-6" x-data="{
        view: '{{ $view }}',
        dateFilter: '{{ $dateFilter }}',
        customDate: '{{ $customDate }}',
        year: '{{ $year }}',
        dateOpen: false,
        previewOpen: false,
        dateLabel() {
            return { today: 'Today', week: 'This Week', month: 'This Month', custom: this.customDate || 'Custom Date' } [this.dateFilter];
        },
        params() {
            return this.view === 'monthly' ? { view: this.view, year: this.year } : { view: this.view, date_filter: this.dateFilter, custom_date: this.customDate };
        },
        reload() {
            if (this.view === 'daily') {
                localStorage.setItem('reports_date_filter', this.dateFilter);
                localStorage.setItem('reports_custom_date', this.customDate);
            }
            if (this.view === 'monthly') {
                localStorage.setItem('reports_year', this.year);
            }
            if (this.view === 'daily' && !{{ $dateFilter ? 'true' : 'false' }}) {
                this.dateFilter = localStorage.getItem('reports_date_filter') || 'today';
                this.customDate = localStorage.getItem('reports_custom_date') || '';
            }
            const params = new URLSearchParams(this.params());
            window.location = '{{ route('reports.index') }}?' + params;
        },
        previewMenuOpen: false,
        downloadMenuOpen: false,
        rangeParams(period) {
            return period === 'year' ?
                { view: 'monthly', year: '{{ now()->format('Y') }}' } :
                { view: 'daily', date_filter: period };
        },
        loadPreview(period) {
            const params = new URLSearchParams(this.rangeParams(period));
            fetch('{{ route('reports.preview') }}?' + params)
                .then(r => r.text())
                .then(html => {
                    document.getElementById('report-preview').innerHTML = html;
                    this.previewOpen = true;
                });
        },
        downloadUrlFor(period) {
            const params = new URLSearchParams(this.rangeParams(period));
            return '{{ route('reports.download') }}?' + params;
        }
    }">
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Reports</h2>
            <p class="text-gray-500">Generate sales and commission reports.</p>
        </div>

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
            <div class="flex items-center gap-3">
                <div class="flex bg-white border border-gray-200 rounded-lg p-1">
                    <a href="{{ route('reports.index', ['view' => 'daily', 'date_filter' => session('last_date_filter', 'today'), 'custom_date' => session('last_custom_date', '')]) }}"
                        @class([
                            'px-3 py-1.5 rounded-md text-sm font-medium',
                            'bg-[#3b1735] text-white' => $view === 'daily',
                            'text-gray-600' => $view !== 'daily',
                        ])>Daily</a>
                    <a href="{{ route('reports.index', ['view' => 'monthly', 'year' => $year]) }}"
                        @class([
                            'px-3 py-1.5 rounded-md text-sm font-medium',
                            'bg-[#3b1735] text-white' => $view === 'monthly',
                            'text-gray-600' => $view !== 'monthly',
                        ])>Monthly</a>
                </div>

                <div x-show="view === 'daily'" class="relative" @click.outside="dateOpen = false">
                    <button type="button" @click="dateOpen = !dateOpen"
                        class="px-3 py-2.5 rounded-lg border border-gray-200 text-sm flex items-center gap-2 min-w-[140px] justify-between">
                        <span x-text="dateLabel()"></span>
                        <span class="text-gray-400">&#9662;</span>
                    </button>
                    <div x-show="dateOpen" x-cloak
                        class="absolute z-10 mt-1 w-56 bg-white rounded-lg border border-gray-100 shadow-lg p-2">
                        <button type="button" @click="dateFilter = 'today'; dateOpen = false; reload()"
                            class="w-full text-left px-3 py-2 rounded hover:bg-gray-50 text-sm">Today</button>
                        <button type="button" @click="dateFilter = 'week'; dateOpen = false; reload()"
                            class="w-full text-left px-3 py-2 rounded hover:bg-gray-50 text-sm">This Week</button>
                        <button type="button" @click="dateFilter = 'month'; dateOpen = false; reload()"
                            class="w-full text-left px-3 py-2 rounded hover:bg-gray-50 text-sm">This Month</button>
                        <div class="border-t border-gray-100 my-1"></div>
                        <label class="block px-3 py-1 text-xs text-gray-400">Custom Date</label>
                        <input type="date" x-model="customDate"
                            @change="dateFilter = 'custom'; dateOpen = false; reload()"
                            class="w-full px-3 py-2 rounded border border-gray-200 text-sm">
                    </div>
                </div>

                <input type="number" x-show="view === 'monthly'" x-model="year" @change="reload()" min="2020"
                    max="2100"
                    class="w-28 px-3 py-2.5 rounded-lg border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#3b1735]">
            </div>

            <div class="flex gap-3">
                <div class="relative" @click.outside="previewMenuOpen = false">
                    <button type="button" @click="previewMenuOpen = !previewMenuOpen"
                        class="px-4 py-2.5 rounded-lg border border-[#3b1735] text-[#3b1735] text-sm font-medium hover:bg-purple-50">
                        Preview
                    </button>
                    <div x-show="previewMenuOpen" x-cloak
                        class="absolute right-0 z-10 mt-1 w-40 bg-white rounded-lg border border-gray-100 shadow-lg p-2">
                        <button type="button" @click="previewMenuOpen = false; loadPreview('today')"
                            class="w-full text-left px-3 py-2 rounded hover:bg-gray-50 text-sm">Today</button>
                        <button type="button" @click="previewMenuOpen = false; loadPreview('week')"
                            class="w-full text-left px-3 py-2 rounded hover:bg-gray-50 text-sm">This Week</button>
                        <button type="button" @click="previewMenuOpen = false; loadPreview('month')"
                            class="w-full text-left px-3 py-2 rounded hover:bg-gray-50 text-sm">This Month</button>
                        <button type="button" @click="previewMenuOpen = false; loadPreview('year')"
                            class="w-full text-left px-3 py-2 rounded hover:bg-gray-50 text-sm">This Year</button>
                    </div>
                </div>

                <div class="relative" @click.outside="downloadMenuOpen = false">
                    <button type="button" @click="downloadMenuOpen = !downloadMenuOpen"
                        class="px-4 py-2.5 rounded-lg bg-[#3b1735] text-white text-sm font-medium hover:opacity-90">
                        Download PDF
                    </button>
                    <div x-show="downloadMenuOpen" x-cloak
                        class="absolute right-0 z-10 mt-1 w-40 bg-white rounded-lg border border-gray-100 shadow-lg p-2">
                        <a :href="downloadUrlFor('today')"
                            class="block px-3 py-2 rounded hover:bg-gray-50 text-sm text-gray-700">Today</a>
                        <a :href="downloadUrlFor('week')"
                            class="block px-3 py-2 rounded hover:bg-gray-50 text-sm text-gray-700">This Week</a>
                        <a :href="downloadUrlFor('month')"
                            class="block px-3 py-2 rounded hover:bg-gray-50 text-sm text-gray-700">This Month</a>
                        <a :href="downloadUrlFor('year')"
                            class="block px-3 py-2 rounded hover:bg-gray-50 text-sm text-gray-700">This Year</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
            <div class="bg-purple-50 border-2 border-[#3b1735] rounded-2xl p-4">
                <p class="text-xs text-gray-600">Gross Sales ({{ $periodLabel }})</p>
                <p class="text-xl font-bold text-gray-900">₱{{ number_format($salesSummary['gross_sales'], 2) }}</p>
            </div>
            <div class="bg-green-50 border-2 border-green-500 rounded-2xl p-4">
                <p class="text-xs text-gray-600">Total Commission</p>
                <p class="text-xl font-bold text-gray-900">₱{{ number_format($totalCommission, 2) }}</p>
            </div>
            <div class="bg-orange-50 border-2 border-orange-500 rounded-2xl p-4">
                <p class="text-xs text-gray-600">Top Seller</p>
                <p class="text-xl font-bold text-gray-900">{{ $topSeller['name'] ?? '—' }}</p>
                @if ($topSeller)
                    <p class="text-xs text-gray-500">₱{{ number_format($topSeller['sales'], 2) }} in sales</p>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
            <div class="bg-white rounded-2xl p-5 shadow-sm">
                <h3 class="font-bold text-gray-900 mb-3">{{ $breakdownTitle }} — Sales ({{ $periodLabel }})</h3>
                <div style="height: 240px;">
                    <canvas id="salesChart"></canvas>
                </div>
            </div>
            <div class="bg-white rounded-2xl p-5 shadow-sm">
                <h3 class="font-bold text-gray-900 mb-3">{{ $breakdownTitle }} — Commission ({{ $periodLabel }})</h3>
                <div style="height: 240px;">
                    <canvas id="commissionChart"></canvas>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 shadow-sm overflow-x-auto">
            <h3 class="font-bold text-gray-900 mb-3">Sellers Ranked by Sales</h3>
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-500 border-b">
                        <th class="pb-2 font-medium">Seller</th>
                        <th class="pb-2 font-medium text-right">Sales</th>
                        <th class="pb-2 font-medium text-right">Commission</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sellers as $seller)
                        <tr class="border-b last:border-0">
                            <td class="py-2 text-gray-900">{{ $seller['name'] }}</td>
                            <td class="py-2 text-gray-700 text-right">₱{{ number_format($seller['sales'], 2) }}</td>
                            <td class="py-2 text-green-700 font-semibold text-right">
                                ₱{{ number_format($seller['commission'], 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="py-6 text-center text-gray-400">No sales recorded.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div x-show="previewOpen" x-cloak
            class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center p-4"
            @click.self="previewOpen = false">
            <div class="bg-white rounded-2xl p-6 w-full max-w-2xl max-h-[85vh] overflow-y-auto relative" @click.stop>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-bold text-lg text-gray-900">Report Preview</h3>
                    <button type="button" @click="previewOpen = false"
                        class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
                </div>
                <div id="report-preview"></div>
            </div>
        </div>
    </div>

    <script>
        (function() {
            const salesCtx = document.getElementById('salesChart');
            const commissionCtx = document.getElementById('commissionChart');
            if (!salesCtx || !commissionCtx) return;

            const labels = @json($chartLabels);

            new Chart(salesCtx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Sales',
                        data: @json($chartSales),
                        borderColor: '#7a6a9e',
                        backgroundColor: 'rgba(122, 106, 158, 0.15)',
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

            new Chart(commissionCtx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Commission',
                        data: @json($chartCommission),
                        borderColor: '#15803d',
                        backgroundColor: 'rgba(21, 128, 61, 0.15)',
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        })();
    </script>
</x-admin-layout>
