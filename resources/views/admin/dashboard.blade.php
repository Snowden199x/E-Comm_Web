<x-admin-layout>
    <div class="p-4 sm:p-5 lg:p-6">

        <!-- Greeting -->
        <div class="mb-6">
            <p class="text-gray-500">Welcome Back,</p>
            <h2 class="text-2xl font-bold text-gray-900">{{ Auth::user()->name }}</h2>
            <p class="text-gray-500">Here's what's happening in Vendo today.</p>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">

            <div class="bg-[#7a6a9e] rounded-2xl p-5 text-gray-900">
                <div class="flex items-center gap-3 mb-3">
                    <div class="bg-black/15 rounded-full w-11 h-11 flex items-center justify-center">
                        <img src="{{ asset('assets/icons/dashboard/total-orders-icon.svg') }}" class="w-11 h-11">
                    </div>
                    <span class="text-sm font-medium">Total Orders</span>
                </div>
                <p class="text-2xl font-bold">{{ number_format($stats['total_orders']) }}</p>
            </div>

            <div class="bg-[#c97b5f] rounded-2xl p-5 text-gray-900">
                <div class="flex items-center gap-3 mb-3">
                    <div class="bg-black/15 rounded-full w-11 h-11 flex items-center justify-center">
                        <img src="{{ asset('assets/icons/dashboard/total-sales-icon.svg') }}" alt=""
                            class="w-11 h-11">
                    </div>
                    <span class="text-sm font-medium">Total Sales</span>
                </div>
                <p class="text-2xl font-bold">₱{{ number_format($stats['total_sales'], 2) }}</p>
            </div>

            <div class="bg-[#d4b96a] rounded-2xl p-5 text-gray-900">
                <div class="flex items-center gap-3 mb-3">
                    <div class="bg-black/15 rounded-full w-11 h-11 flex items-center justify-center">
                        <img src="{{ asset('assets/icons/dashboard/total-users-icon.svg') }}" alt=""
                            class="w-11 h-11">
                    </div>
                    <span class="text-sm font-medium">Total Users</span>
                </div>
                <p class="text-2xl font-bold">{{ number_format($stats['total_users']) }}</p>
            </div>

            <div class="bg-[#9b7ba8] rounded-2xl p-5 text-gray-900">
                <div class="flex items-center gap-3 mb-3">
                    <div class="bg-black/15 rounded-full w-11 h-11 flex items-center justify-center">
                        <img src="{{ asset('assets/icons/dashboard/total-sellers-icon.svg') }}" alt=""
                            class="w-11 h-11">
                    </div>
                    <span class="text-sm font-medium">Total Sellers</span>
                </div>
                <p class="text-2xl font-bold">{{ number_format($stats['total_sellers']) }}</p>
            </div>

        </div>
        <!-- Main Content (left) + Sidebar (right) -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mt-4">

            <!-- Main Column -->
            <div class="lg:col-span-2 space-y-4">

                <!-- Sales Overview + Summary -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-2 bg-white rounded-2xl p-5 shadow-sm">
                        <div class="flex items-center gap-4 mb-4">
                            <h3 class="font-bold text-gray-900">Sales Overview</h3>
                            <div class="flex items-center gap-1 text-xs text-gray-500">
                                <span class="w-2 h-2 rounded-full bg-[#7a6a9e] inline-block"></span> Sales
                            </div>
                            <div class="flex items-center gap-1 text-xs text-gray-500">
                                <span class="w-2 h-2 rounded-full bg-[#c97b5f] inline-block"></span> Orders
                            </div>
                        </div>
                        <div style="height: 260px;">
                            <canvas id="salesOverviewChart"></canvas>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl p-5 shadow-sm flex flex-col">
                        <h3 class="font-bold text-gray-900 mb-4">Sales Summary</h3>
                        <div class="space-y-3 flex-1">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Gross Sales</span>
                                <span
                                    class="font-semibold text-gray-900">₱{{ number_format($salesSummary['gross_sales'], 2) }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Total Orders</span>
                                <span
                                    class="font-semibold text-gray-900">{{ number_format($salesSummary['total_orders']) }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Average Order Value</span>
                                <span
                                    class="font-semibold text-gray-900">₱{{ number_format($salesSummary['average_order_value'], 2) }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Completed Orders</span>
                                <span
                                    class="font-semibold text-gray-900">{{ number_format($salesSummary['completed_orders']) }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Return/Refund</span>
                                <span
                                    class="font-semibold text-gray-900">{{ number_format($salesSummary['return_refund']) }}</span>
                            </div>
                        </div>
                        <button
                            class="mt-4 bg-[#3b1735] text-white text-sm font-medium py-2.5 rounded-lg hover:bg-[#4d1f45]">
                            View Full Report
                        </button>
                    </div>
                </div>

                <!-- Recent Registrations + Recent Complaints (placeholder for now) -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-white rounded-2xl p-5 shadow-sm overflow-x-auto" x-data="{ openId: null, rejectId: null }">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="font-bold text-gray-900">Recent Registrations</h3>
                            <a href="{{ route('registrations.index') }}"
                                class="text-sm text-[#3b1735] font-medium hover:underline">View All</a>
                        </div>

                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-gray-500 border-b">
                                    <th class="py-2 font-medium">Name</th>
                                    <th class="py-2 font-medium">Type</th>
                                    <th class="py-2 font-medium">Date</th>
                                    <th class="py-2 font-medium">Status</th>
                                    <th class="py-2 font-medium">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentRegistrations as $reg)
                                    <tr class="border-b last:border-0">
                                        <td class="py-3 text-gray-900">{{ $reg->name }}</td>
                                        <td class="py-3 text-gray-600 capitalize">{{ $reg->role }}</td>
                                        <td class="py-3 text-gray-600">{{ $reg->created_at->format('M d, Y') }}</td>
                                        <td class="py-3">
                                            <span @class([
                                                'px-2 py-1 rounded-full text-xs font-medium',
                                                'bg-yellow-100 text-yellow-700' => $reg->status === 'pending',
                                                'bg-green-100 text-green-700' => $reg->status === 'approved',
                                                'bg-red-100 text-red-700' => $reg->status === 'disapproved',
                                            ])>
                                                {{ ucfirst($reg->status) }}
                                            </span>
                                        </td>
                                        <td class="py-3">
                                            <div class="flex items-center gap-3">
                                                <button type="button" @click="openId = {{ $reg->id }}"
                                                    title="View Details">
                                                    <img src="{{ asset('assets/icons/dashboard/see-password.svg') }}"
                                                        alt="View" class="w-4 h-4 opacity-60 hover:opacity-100">
                                                </button>

                                                @if ($reg->status === 'pending')
                                                    <form method="POST"
                                                        action="{{ route('registrations.approve', $reg) }}">
                                                        @csrf
                                                        <button type="submit"
                                                            class="text-green-600 hover:text-green-800 font-bold"
                                                            title="Approve">✓</button>
                                                    </form>
                                                    <form method="POST"
                                                        action="{{ route('registrations.disapprove', $reg) }}">
                                                        @csrf
                                                        <button type="button"
                                                            @click="rejectId = {{ $reg->id }}"
                                                            class="text-red-600 hover:text-red-800 font-bold"
                                                            title="Disapprove">✕</button>
                                                    </form>
                                                @else
                                                    <span class="text-gray-400">—</span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="py-6 text-center text-gray-400">No registrations
                                            yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>

                        <!-- View Modals -->
                        @foreach ($recentRegistrations as $reg)
                            <div x-show="openId === {{ $reg->id }}" x-cloak
                                class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4 overflow-y-auto"
                                @click.self="openId = null">
                                <div class="bg-white rounded-2xl p-6 w-full max-w-5xl my-8" @click.stop>
                                    <div class="flex items-center justify-between mb-4">
                                        <h3 class="font-bold text-lg text-gray-900">Applicant Details</h3>
                                        <button type="button" @click="openId = null"
                                            class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
                                    </div>

                                    @include('admin.registrations.partials.applicant-details', [
                                        'user' => $reg,
                                    ])
                                </div>
                            </div>
                        @endforeach
                        @foreach ($recentRegistrations as $reg)
                            @include('admin.registrations.partials.reject-modal', [
                                'user' => $reg,
                                'showExpr' => "rejectId === {$reg->id}",
                                'closeExpr' => 'rejectId = null',
                            ])
                        @endforeach
                    </div>
                    <div class="bg-white rounded-2xl p-5 shadow-sm overflow-x-auto">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="font-bold text-gray-900">Pending Registrations</h3>
                            <a href="{{ route('registrations.index') }}"
                                class="text-sm text-[#3b1735] font-medium hover:underline">View All</a>
                        </div>

                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-gray-500 border-b">
                                    <th class="pb-2 font-medium">Complaint ID</th>
                                    <th class="pb-2 font-medium">From</th>
                                    <th class="pb-2 font-medium">Against</th>
                                    <th class="pb-2 font-medium">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentComplaints as $complaint)
                                    <tr class="border-b last:border-0">
                                        <td class="py-3 text-gray-900">
                                            {{ $complaint->order ? '#' . $complaint->order->id : '—' }}
                                        </td>
                                        <td class="py-3 text-gray-600 capitalize">
                                            {{ $complaint->complainant->role ?? '—' }}</td>
                                        <td class="py-3 text-gray-600 capitalize">
                                            {{ $complaint->respondent->role ?? '—' }}</td>
                                        <td class="py-3">
                                            <span @class([
                                                'px-2 py-1 rounded-full text-xs font-medium',
                                                'bg-orange-100 text-orange-700' => $complaint->status === 'open',
                                                'bg-yellow-100 text-yellow-700' => $complaint->status === 'in_review',
                                                'bg-green-100 text-green-700' => $complaint->status === 'resolved',
                                            ])>
                                                {{ str_replace('_', ' ', ucfirst($complaint->status)) }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="py-6 text-center text-gray-400">No complaints yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            <!-- Sidebar Column -->
            <div class="space-y-4">

                <!-- Notifications -->
                <div class="bg-white rounded-2xl p-5 shadow-sm">
                    <h3 class="font-bold text-gray-900 mb-4">Notifications</h3>
                    <div class="space-y-4">
                        @forelse ($notifications as $notification)
                            <div class="flex items-start gap-3">
                                <img src="{{ asset('assets/icons/dashboard/' . $notification->icon()) }}"
                                    alt="" class="w-5 h-5 mt-0.5">
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-900">{{ $notification->title }}</p>
                                    <p class="text-sm text-gray-500">{{ $notification->message }}</p>
                                </div>
                                <span
                                    class="text-xs text-gray-400 whitespace-nowrap">{{ $notification->timeAgo() }}</span>
                            </div>
                        @empty
                            <p class="text-sm text-gray-400">No notifications yet.</p>
                        @endforelse
                    </div>
                </div>

                <!-- Pending Registrations -->
                <div class="bg-white rounded-2xl p-5 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-bold text-gray-900">Pending Registrations</h3>
                        <a href="{{ route('registrations.index') }}"
                            class="text-sm text-[#3b1735] font-medium hover:underline">View All</a>
                    </div>
                    <div class="space-y-3">
                        <div class="flex items-center gap-3">
                            <img src="{{ asset('assets/icons/dashboard/sellers-registrations.svg') }}" alt=""
                                class="w-5 h-5">
                            <span class="text-sm text-gray-600 flex-1">Sellers</span>
                            <span
                                class="text-sm font-semibold text-gray-900">{{ $pendingRegistrations['sellers'] }}</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <img src="{{ asset('assets/icons/dashboard/buyers-registrations.svg') }}" alt=""
                                class="w-5 h-5">
                            <span class="text-sm text-gray-600 flex-1">Buyers</span>
                            <span
                                class="text-sm font-semibold text-gray-900">{{ $pendingRegistrations['buyers'] }}</span>
                        </div>
                    </div>
                </div>

                <!-- Announcement -->
                <div class="relative bg-[#3b1735] rounded-2xl p-5 text-white overflow-hidden">

                    <img src="{{ asset('assets/icons/dashboard/announcement-icons.svg') }}" alt=""
                        class="absolute bottom-3 right-3 h-10 w-auto opacity-90">

                    <div class="relative pr-10">
                        <p class="text-sm font-bold text-[#e8c874] tracking-wide mb-2">Announcement</p>

                        @if ($announcement)
                            <h4 class="text-lg font-bold mb-1">{{ $announcement->title }}</h4>
                            <p class="text-sm text-gray-300 mb-4">{{ $announcement->message }}</p>
                        @else
                            <p class="text-sm text-gray-300 mb-4">No active announcement yet.</p>
                        @endif

                        <button
                            class="bg-white text-[#3b1735] text-sm font-medium px-4 py-2 rounded-lg hover:bg-gray-100">
                            Manage Announcement
                        </button>
                    </div>

                </div>

            </div>

        </div>
    </div>

    <!-- Chart.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
    <script>
        const ctx = document.getElementById('salesOverviewChart');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: @json($chartData['labels']),
                datasets: [{
                        label: 'Sales',
                        data: @json($chartData['sales']),
                        borderColor: '#7a6a9e',
                        backgroundColor: 'rgba(122, 106, 158, 0.15)',
                        fill: true,
                        tension: 0.4,
                        yAxisID: 'y',
                    },
                    {
                        label: 'Orders',
                        data: @json($chartData['orders']),
                        borderColor: '#c97b5f',
                        backgroundColor: 'rgba(201, 123, 95, 0.15)',
                        fill: true,
                        tension: 0.4,
                        yAxisID: 'y1',
                    }
                ]
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
                        type: 'linear',
                        position: 'left',
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                if (value >= 1000) {
                                    return (value / 1000) + 'k';
                                }
                                return value;
                            }
                        }
                    },
                    y1: {
                        type: 'linear',
                        position: 'right',
                        beginAtZero: true,
                        grid: {
                            drawOnChartArea: false
                        },
                    }
                }
            }
        });
    </script>
</x-admin-layout>
