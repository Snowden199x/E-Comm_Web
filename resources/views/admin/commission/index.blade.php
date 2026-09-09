<x-admin-layout>
    <div class="p-4 sm:p-5 lg:p-6" x-data="{
        editRateOpen: false,
        sellerDetailOpen: false,
        sellerDetail: null,
        sellerChart: null,
        confirmation: @js(session('confirmation')),
        openSellerDetail(sellerId) {
            const month = document.querySelector('input[type=month]')?.value || '{{ $month }}';
            fetch('{{ url('commission/seller') }}/' + sellerId + '?month=' + month)
                .then(r => r.json())
                .then(data => {
                    this.sellerDetail = data;
                    this.sellerDetailOpen = true;
                    this.$nextTick(() => this.renderChart());
                });
        },
        renderChart() {
            if (this.sellerChart) this.sellerChart.destroy();
            const ctx = this.$refs.sellerChartCanvas;
            if (!ctx) return;
            this.sellerChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: this.sellerDetail.items.map(i => i.name),
                    datasets: [{
                        label: 'Sales',
                        data: this.sellerDetail.items.map(i => i.sales),
                        backgroundColor: '#7a6a9e',
                        borderRadius: 6,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true } }
                }
            });
        }
    }">
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Manage Commission</h2>
            <p class="text-gray-500">Track and calculate platform commissions per seller.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
            <div class="bg-purple-50 border-2 border-[#3b1735] rounded-2xl p-4 flex items-center gap-3">
                <div>
                    <p class="text-xs text-gray-600">Total Sales (Completed)</p>
                    <p class="text-xl font-bold text-gray-900">₱{{ number_format($stats['total_sales'], 2) }}</p>
                </div>
            </div>
            <div class="bg-green-50 border-2 border-green-500 rounded-2xl p-4 flex items-center gap-3">
                <div>
                    <p class="text-xs text-gray-600">Total Commission Earned</p>
                    <p class="text-xl font-bold text-gray-900">₱{{ number_format($stats['total_commission'], 2) }}</p>
                </div>
            </div>
            <div class="bg-orange-50 border-2 border-orange-500 rounded-2xl p-4 flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-600">Commission Rate</p>
                    <p class="text-xl font-bold text-gray-900">{{ rtrim(rtrim(number_format($rate, 2), '0'), '.') }}%
                    </p>
                </div>
                <button type="button" @click="editRateOpen = true"
                    class="text-xs px-3 py-1.5 rounded-full border border-[#3b1735] text-[#3b1735] hover:bg-purple-50">Edit</button>
            </div>
        </div>

        <div x-data="{
            q: '{{ request('search') }}',
            month: '{{ $month }}',
            timer: null,
            search() {
                clearTimeout(this.timer);
                this.timer = setTimeout(() => {
                    const params = new URLSearchParams({ search: this.q, month: this.month });
                    fetch('{{ route('commission.table') }}?' + params)
                        .then(r => r.text()).then(html => { document.getElementById('commission-table-wrap').innerHTML = html; });
                }, 250);
            }
        }">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                <div class="relative w-80">
                    <img src="{{ asset('assets/icons/user-management/search-icon.svg') }}" alt=""
                        class="w-4 h-4 absolute left-4 top-1/2 -translate-y-1/2 opacity-50">
                    <input type="text" x-model="q" @input="search" autocomplete="off"
                        placeholder="Search seller name or email..."
                        class="w-full pl-10 pr-4 py-2.5 rounded-full border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#3b1735]">
                </div>

                <input type="month" x-model="month" @change="search"
                    class="px-3 py-2.5 rounded-lg border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#3b1735]">
            </div>

            <div id="commission-table-wrap">
                @include('admin.commission.partials.commission-table')
            </div>
        </div>

        @include('admin.commission.partials.edit-rate-modal')
        @include('admin.commission.partials.seller-detail-modal')
    </div>
</x-admin-layout>
