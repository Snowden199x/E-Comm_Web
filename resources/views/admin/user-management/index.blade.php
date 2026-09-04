<x-admin-layout>
    <div class="p-4 sm:p-5 lg:p-6" x-data="{ openId: null, suspendId: null, deactivateId: null, activateId: null }">

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-2">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">User Management</h2>
                <p class="text-gray-500">Manage and control user accounts here.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
            <div class="bg-purple-50 border-2 border-[#3b1735] rounded-2xl p-4 flex items-center gap-3">
                <img src="{{ asset('assets/icons/dashboard/total-users-icon.svg') }}" alt="" class="w-10 h-10">
                <div>
                    <p class="text-xs text-gray-600">Total Users</p>
                    <p class="text-xl font-bold text-gray-900">{{ number_format($stats['total_users']) }}</p>
                </div>
            </div>
            <div class="bg-purple-50 border-2 border-[#3b1735] rounded-2xl p-4 flex items-center gap-3">
                <img src="{{ asset('assets/icons/user-management/seller-icon.svg') }}" alt="" class="w-10 h-10">
                <div>
                    <p class="text-xs text-gray-600">Sellers</p>
                    <p class="text-xl font-bold text-gray-900">{{ number_format($stats['sellers']) }}</p>
                </div>
            </div>
            <div class="bg-purple-50 border-2 border-[#3b1735] rounded-2xl p-4 flex items-center gap-3">
                <img src="{{ asset('assets/icons/user-management/buyer-icon.svg') }}" alt="" class="w-10 h-10">
                <div>
                    <p class="text-xs text-gray-600">Buyers</p>
                    <p class="text-xl font-bold text-gray-900">{{ number_format($stats['buyers']) }}</p>
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
                    const params = new URLSearchParams({ search: this.q, date: this.dateVal, user_type: this.userType, rejected: '{{ $showRejected ? 1 : 0 }}' });
                    fetch('{{ route('user-management.table') }}?' + params)
                        .then(r => r.text()).then(html => { document.getElementById('users-table-wrap').innerHTML = html; });
                }, 250);
            }
        }" class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
            <div class="relative w-80">
                <img src="{{ asset('assets/icons/user-management/search-icon.svg') }}" alt=""
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
                    style="background-image: url('{{ asset('assets/icons/user-management/down-arrow-icon.svg') }}');">
                    <option value="all">All Users</option>
                    <option value="seller">Sellers</option>
                    <option value="buyer">Buyers</option>
                </select>

                <a href="{{ route('user-management.index', array_merge(request()->except('rejected'), ['rejected' => $showRejected ? 0 : 1])) }}"
                    class="flex items-center gap-2 px-4 py-2.5 rounded-lg border {{ $showRejected ? 'bg-[#3b1735] text-white border-[#3b1735]' : 'border-gray-200 text-gray-700' }} text-sm font-medium whitespace-nowrap">
                    <img src="{{ asset('assets/icons/user-management/document-icon.svg') }}" alt=""
                        class="w-4 h-4 {{ $showRejected ? 'brightness-0 invert' : '' }}">
                    Rejected users
                </a>
            </div>
        </div>

        <div id="users-table-wrap">
            @include('admin.user-management.partials.users-table')
        </div>

        @foreach ($users as $u)
            @include('admin.user-management.partials.profile-modal', ['user' => $u])
            @include('admin.user-management.partials.suspend-modal', ['user' => $u])
            @include('admin.user-management.partials.deactivate-modal', ['user' => $u])
            @include('admin.user-management.partials.activate-modal', ['user' => $u])
        @endforeach

        @include('admin.user-management.partials.confirmation-modal')

    </div>
</x-admin-layout>