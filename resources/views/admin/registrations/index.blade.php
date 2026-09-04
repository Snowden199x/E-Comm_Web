<x-admin-layout>
    <div class="p-4 sm:p-5 lg:p-6">

        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Registration</h2>
            <p class="text-gray-500">Manage user registration here.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
            <div class="bg-purple-50 border-2 border-[#3b1735] rounded-2xl p-4 flex items-center gap-3">
                <img src="{{ asset('assets/icons/dashboard/total-orders-icon.svg') }}" alt="" class="w-10 h-10">
                <div>
                    <p class="text-xs text-gray-600">Pending Request</p>
                    <p class="text-xl font-bold text-gray-900">{{ number_format($stats['pending_request']) }}</p>
                </div>
            </div>
            <div class="bg-purple-50 border-2 border-[#3b1735] rounded-2xl p-4 flex items-center gap-3">
                <img src="{{ asset('assets/icons/dashboard/sellers-registrations.svg') }}" alt=""
                    class="w-10 h-10">
                <div>
                    <p class="text-xs text-gray-600">Pending Sellers</p>
                    <p class="text-xl font-bold text-gray-900">{{ number_format($stats['pending_sellers']) }}</p>
                </div>
            </div>
            <div class="bg-purple-50 border-2 border-[#3b1735] rounded-2xl p-4 flex items-center gap-3">
                <img src="{{ asset('assets/icons/dashboard/buyers-registrations.svg') }}" alt=""
                    class="w-10 h-10">
                <div>
                    <p class="text-xs text-gray-600">Pending Buyers</p>
                    <p class="text-xl font-bold text-gray-900">{{ number_format($stats['pending_buyers']) }}</p>
                </div>
            </div>
        </div>

        <div x-data="{
            q: '{{ request('search') }}',
            dateVal: '{{ request('date') }}',
            userType: '{{ request('user_type', 'all') }}',
            timer: null,
            search() {
                clearTimeout(this.timer);
                this.timer = setTimeout(() => {
                    const params = new URLSearchParams({ search: this.q, date: this.dateVal, user_type: this.userType });
                    fetch('{{ route('registrations.table') }}?' + params)
                        .then(r => r.text()).then(html => { document.getElementById('registrations-table-wrap').innerHTML = html; });
                }, 250);
            }
        }" class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
            <div class="relative w-80">
                <img src="{{ asset('assets/icons/registration/search-icon.svg') }}" alt=""
                    class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 opacity-50">
                <input type="text" x-model="q" @input="search" autocomplete="off"
                    placeholder="Search seller name or email..."
                    class="w-full pl-9 pr-4 py-2.5 rounded-full border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#3b1735]">
            </div>

            <div class="flex gap-3">
                <input type="date" x-model="dateVal" @change="search"
                    class="px-3 py-2.5 rounded-lg border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#3b1735]">

                <select x-model="userType" @change="search"
                    class="appearance-none bg-no-repeat bg-[right_0.75rem_center] bg-[length:12px] px-3 pr-9 py-2.5 rounded-lg border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#3b1735]"
                    style="background-image: url('{{ asset('assets/icons/registration/down-arrow-icon.svg') }}');">
                    <option value="all">All Users</option>
                    <option value="seller">Sellers</option>
                    <option value="buyer">Buyers</option>
                </select>
            </div>
        </div>

        @if (session('success'))
            <div class="mb-4 text-sm text-green-700 bg-green-50 px-3 py-2 rounded-lg">{{ session('success') }}</div>
        @endif

        <div id="registrations-table-wrap">
            @include('admin.registrations.partials.registrations-table')
        </div>
    </div>
</x-admin-layout>
