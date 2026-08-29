<x-admin-layout>
    <div class="p-4 sm:p-5 lg:p-6" x-data="{ openId: null, suspendId: null, deactivateId: null, activateId: null }">

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-2">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">User Management</h2>
                <p class="text-gray-500">Manage and control user accounts here.</p>
            </div>
        </div>

        <!-- Stat Cards -->
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

        <!-- Search + Filters -->
        <form method="GET" action="{{ route('user-management.index') }}" class="flex flex-col sm:flex-row gap-3 mb-4">
            @if ($showRejected)
                <input type="hidden" name="rejected" value="1">
            @endif

            <div class="flex-1 relative">
                <img src="{{ asset('assets/icons/user-management/search-icon.svg') }}" alt="" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 opacity-50">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search seller name or email..."
                       class="w-full pl-9 pr-3 py-2.5 rounded-lg border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#3b1735]">
            </div>

            <input type="date" name="date" value="{{ request('date') }}"
                   class="px-3 py-2.5 rounded-lg border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#3b1735]">

            <select name="user_type" onchange="this.form.submit()"
                    class="appearance-none bg-no-repeat bg-[right_0.75rem_center] bg-[length:12px] px-3 pr-9 py-2.5 rounded-lg border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#3b1735]"
                    style="background-image: url('{{ asset('assets/icons/user-management/down-arrow-icon.svg') }}');">
                <option value="all" {{ request('user_type', 'all') === 'all' ? 'selected' : '' }}>All Users</option>
                <option value="seller" {{ request('user_type') === 'seller' ? 'selected' : '' }}>Sellers</option>
                <option value="buyer" {{ request('user_type') === 'buyer' ? 'selected' : '' }}>Buyers</option>
            </select>

            <a href="{{ route('user-management.index', array_merge(request()->except('rejected'), ['rejected' => $showRejected ? 0 : 1])) }}"
               class="flex items-center gap-2 px-4 py-2.5 rounded-lg border {{ $showRejected ? 'bg-[#3b1735] text-white border-[#3b1735]' : 'border-gray-200 text-gray-700' }} text-sm font-medium whitespace-nowrap">
                <img src="{{ asset('assets/icons/user-management/document-icon.svg') }}" alt="" class="w-4 h-4 {{ $showRejected ? 'brightness-0 invert' : '' }}">
                Rejected users
            </a>
        </form>

        <!-- Table -->
        <div class="bg-white rounded-2xl p-5 shadow-sm overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-500 border-b">
                        <th class="pb-3 font-medium">Applicant</th>
                        <th class="pb-3 font-medium">User Type</th>
                        <th class="pb-3 font-medium">Email</th>
                        <th class="pb-3 font-medium">Number</th>
                        <th class="pb-3 font-medium">Date Applied</th>
                        <th class="pb-3 font-medium">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $u)
                        <tr class="border-b last:border-0 cursor-pointer hover:bg-gray-50" @click="openId = {{ $u->id }}">
                            <td class="py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center text-xs font-semibold text-gray-600">
                                        {{ strtoupper(substr($u->name, 0, 1)) }}
                                    </div>
                                    <span class="text-gray-900">{{ $u->name }}</span>
                                </div>
                            </td>
                            <td class="py-3 text-gray-600 capitalize">{{ $u->role }}</td>
                            <td class="py-3 text-gray-600">{{ $u->email }}</td>
                            <td class="py-3 text-gray-600">{{ $u->phone_number ?? '—' }}</td>
                            <td class="py-3 text-gray-600">{{ $u->created_at->format('M d, Y') }}</td>
                            <td class="py-3">
                                <span @class([
                                    'px-2 py-1 rounded-full text-xs font-medium',
                                    'bg-green-100 text-green-700' => $u->status === 'approved',
                                    'bg-red-100 text-red-700' => $u->status === 'suspended',
                                    'bg-red-50 text-red-400' => $u->status === 'deactivated',
                                    'bg-orange-100 text-orange-700' => $u->status === 'disapproved',
                                ])>
                                    @if ($u->status === 'approved') Active
                                    @elseif ($u->status === 'disapproved') Rejected
                                    @else {{ ucfirst($u->status) }}
                                    @endif
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-gray-400">No users found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @if ($users->hasPages())
                <div class="flex items-center justify-between mt-4 pt-4 border-t">
                    <p class="text-xs text-gray-500">Showing {{ $users->count() }} out of {{ $users->total() }} entries</p>
                    <div>{{ $users->links() }}</div>
                </div>
            @endif
        </div>

        <!-- Profile Modals + Action Modals (per user) -->
        @foreach ($users as $u)
            @include('admin.user-management.partials.profile-modal', ['user' => $u])
            @include('admin.user-management.partials.suspend-modal', ['user' => $u])
            @include('admin.user-management.partials.deactivate-modal', ['user' => $u])
            @include('admin.user-management.partials.activate-modal', ['user' => $u])
        @endforeach

        @include('admin.user-management.partials.confirmation-modal')

    </div>
</x-admin-layout>