<x-admin-layout>
    <div class="p-4 sm:p-5 lg:p-6">

        <!-- Header -->
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Registration</h2>
            <p class="text-gray-500">Manage user registration here.</p>
        </div>

        <!-- Stat Cards -->
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

        <!-- Search + Filters -->
        <form method="GET" action="{{ route('registrations.index') }}" class="flex flex-col sm:flex-row gap-3 mb-4">
            <div class="flex-1 relative">
                <img src="{{ asset('assets/icons/registration/search-icon.svg') }}" alt=""
                    class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 opacity-50">
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="Search seller name or email..."
                    class="w-full pl-9 pr-3 py-2.5 rounded-lg border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#3b1735]">
            </div>

            <input type="date" name="date" value="{{ request('date') }}"
                class="px-3 py-2.5 rounded-lg border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#3b1735]">

            <select name="user_type" onchange="this.form.submit()"
                class="appearance-none bg-no-repeat bg-[right_0.75rem_center] bg-[length:12px] px-3 pr-9 py-2.5 rounded-lg border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#3b1735]"
                style="background-image: url('{{ asset('assets/icons/registration/down-arrow-icon.svg') }}');">
                <option value="all" {{ request('user_type', 'all') === 'all' ? 'selected' : '' }}>All Users</option>
                <option value="seller" {{ request('user_type') === 'seller' ? 'selected' : '' }}>Sellers</option>
                <option value="buyer" {{ request('user_type') === 'buyer' ? 'selected' : '' }}>Buyers</option>
            </select>

            <button type="submit"
                class="px-4 py-2.5 rounded-lg bg-[#3b1735] text-white text-sm font-medium hover:bg-[#4d1f45]">
                Search
            </button>
        </form>

        @if (session('success'))
            <div class="mb-4 text-sm text-green-700 bg-green-50 px-3 py-2 rounded-lg">{{ session('success') }}</div>
        @endif

        <!-- Table -->
        <div class="bg-white rounded-2xl p-5 shadow-sm overflow-x-auto" x-data="{ openId: null }">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-500 border-b">
                        <th class="pb-3 font-medium">Applicant</th>
                        <th class="pb-3 font-medium">User Type</th>
                        <th class="pb-3 font-medium">Email</th>
                        <th class="pb-3 font-medium">Number</th>
                        <th class="pb-3 font-medium">Date Applied</th>
                        <th class="pb-3 font-medium text-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($registrations as $reg)
                        <tr class="border-b last:border-0">
                            <td class="py-3">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center text-xs font-semibold text-gray-600">
                                        {{ strtoupper(substr($reg->name, 0, 1)) }}
                                    </div>
                                    <span class="text-gray-900">{{ $reg->name }}</span>
                                </div>
                            </td>
                            <td class="py-3 text-gray-600 capitalize">{{ $reg->role }}</td>
                            <td class="py-3 text-gray-600">{{ $reg->email }}</td>
                            <td class="py-3 text-gray-600">{{ $reg->phone_number ?? '—' }}</td>
                            <td class="py-3 text-gray-600">{{ $reg->created_at->format('M d, Y') }}</td>
                            <td class="py-3 text-right">
                                <a href="{{ route('registrations.show', $reg) }}"
                                    class="inline-block px-4 py-1.5 rounded-full border border-gray-300 text-xs font-medium text-gray-700 hover:bg-gray-50">
                                    View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-gray-400">No pending registrations found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <!-- Pagination -->
            @if ($registrations->hasPages())
                <div class="flex items-center justify-between mt-4 pt-4 border-t">
                    <p class="text-xs text-gray-500">
                        Showing {{ $registrations->count() }} out of {{ $registrations->total() }} entries
                    </p>
                    <div>
                        {{ $registrations->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-admin-layout>