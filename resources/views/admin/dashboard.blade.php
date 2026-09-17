<x-admin-layout>
    <div class="p-4 sm:p-6 lg:p-7" x-data="{ openId: null, rejectId: null }">

        <!-- Greeting -->
        <div class="mb-6">
            <p class="text-gray-500">Welcome Back,</p>
            <h2 class="text-[28px] sm:text-[32px] font-bold text-[#2B1730] leading-tight">
                {{ Auth::guard('admin')->user()->first_name ?? Auth::guard('admin')->user()->name }}
            </h2>
            <p class="text-gray-500">Here's what's happening in Vendo today.</p>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-4 gap-5 items-start">

            <!-- ============ LEFT (main) ============ -->
            <div class="xl:col-span-3 space-y-5">

                <!-- Stat cards -->
                @php
                    $statCards = [
                        ['label' => 'Total Orders',  'value' => number_format($stats['total_orders']),          'icon' => 'total-orders-icon.svg',  'bg' => '#B6A6C0'],
                        ['label' => 'Total Sales',   'value' => '₱' . number_format($stats['total_sales'], 2),  'icon' => 'total-sales-icon.svg',   'bg' => '#E7A68D'],
                        ['label' => 'Total Users',   'value' => number_format($stats['total_users']),           'icon' => 'total-users-icon.svg',   'bg' => '#ECDCAB'],
                        ['label' => 'Total Sellers', 'value' => number_format($stats['total_sellers']),         'icon' => 'total-sellers-icon.svg', 'bg' => '#C9ABD0'],
                    ];
                @endphp

                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    @foreach ($statCards as $card)
                        <div class="rounded-3xl p-5 text-[#2B1730] transition-transform duration-500 ease-vendo hover:-translate-y-1"
                            style="background: {{ $card['bg'] }};">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="bg-black/15 rounded-full w-11 h-11 flex items-center justify-center flex-shrink-0">
                                    <img src="{{ asset('assets/icons/dashboard/' . $card['icon']) }}" alt=""
                                        class="w-6 h-6">
                                </div>
                                <span class="text-sm font-medium leading-tight">{{ $card['label'] }}</span>
                            </div>
                            <p class="text-2xl font-bold tracking-tight">{{ $card['value'] }}</p>
                        </div>
                    @endforeach
                </div>

                <!-- Sales overview + summary -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

                    <div class="lg:col-span-2 bg-white rounded-3xl p-5 sm:p-6 shadow-[0_18px_40px_-32px_rgba(43,23,48,0.55)]">
                        <div class="flex flex-wrap items-center gap-x-5 gap-y-2 mb-5">
                            <h3 class="font-bold text-[#2B1730] text-[17px]">Sales Overview</h3>
                            <div class="flex items-center gap-1.5 text-xs text-gray-500">
                                <span class="w-2.5 h-2.5 rounded-full bg-[#4A2A52] inline-block"></span> Sales
                            </div>
                            <div class="flex items-center gap-1.5 text-xs text-gray-500">
                                <span class="w-2.5 h-2.5 rounded-full bg-[#C97B5F] inline-block"></span> Orders
                            </div>
                        </div>
                        <div class="h-[240px] sm:h-[260px]">
                            <canvas id="salesOverviewChart"></canvas>
                        </div>
                    </div>

                    <div class="bg-white rounded-3xl p-5 sm:p-6 shadow-[0_18px_40px_-32px_rgba(43,23,48,0.55)] flex flex-col">
                        <h3 class="font-bold text-[#2B1730] text-[17px] mb-4">Sales Summary</h3>

                        @php
                            $summaryRows = [
                                ['Gross Sales', '₱' . number_format($salesSummary['gross_sales'], 2)],
                                ['Total Orders', number_format($salesSummary['total_orders'])],
                                ['Average Order Value', '₱' . number_format($salesSummary['average_order_value'], 2)],
                                ['Completed Orders', number_format($salesSummary['completed_orders'])],
                                ['Return/Refund', number_format($salesSummary['return_refund'])],
                            ];
                        @endphp

                        <div class="flex-1 divide-y divide-gray-100">
                            @foreach ($summaryRows as [$label, $value])
                                <div class="flex items-center justify-between gap-3 py-3 text-sm first:pt-0">
                                    <span class="text-gray-500">{{ $label }}</span>
                                    <span class="font-semibold text-[#2B1730] text-right">{{ $value }}</span>
                                </div>
                            @endforeach
                        </div>

                        <a href="{{ route('admin.reports.index') }}" x-target.push="main-content sidebar"
                            class="mt-5 bg-[#3b1735] text-white text-sm font-medium py-3 rounded-xl text-center
                                   hover:bg-[#4d1f45] transition-colors duration-300 ease-vendo">
                            View Full Report
                        </a>
                    </div>
                </div>

                <!-- Recent registrations + complaints -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

                    <!-- Recent Registrations -->
                    <div class="bg-white rounded-3xl p-5 sm:p-6 shadow-[0_18px_40px_-32px_rgba(43,23,48,0.55)]">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="font-bold text-[#2B1730] text-[17px]">Recent Registrations</h3>
                            <a href="{{ route('admin.registrations.index') }}" x-target.push="main-content sidebar"
                                class="text-sm text-[#3b1735] font-medium hover:underline">View All</a>
                        </div>

                        <div class="overflow-x-auto thin-scroll -mx-1 px-1">
                            <table class="w-full text-sm min-w-[440px]">
                                <thead>
                                    <tr class="text-left text-gray-500 border-b border-gray-100">
                                        <th class="py-2.5 font-medium">Name</th>
                                        <th class="py-2.5 font-medium">Type</th>
                                        <th class="py-2.5 font-medium">Date</th>
                                        <th class="py-2.5 font-medium">Status</th>
                                        <th class="py-2.5 font-medium text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($recentRegistrations as $reg)
                                        <tr class="border-b border-gray-50 last:border-0">
                                            <td class="py-3 text-[#2B1730] whitespace-nowrap">{{ $reg->name }}</td>
                                            <td class="py-3 text-gray-600 whitespace-nowrap">
                                                {{ $reg->role === 'logistics_center' ? 'Logistics' : ucfirst($reg->role) }}
                                            </td>
                                            <td class="py-3 text-gray-600 whitespace-nowrap">
                                                {{ $reg->created_at->format('M d, Y') }}</td>
                                            <td class="py-3">
                                                <span @class([
                                                    'px-2.5 py-1 rounded-full text-xs font-medium whitespace-nowrap border',
                                                    'bg-[#FDF3E2] text-[#B45309] border-[#F3D9A6]' => $reg->status === 'pending',
                                                    'bg-green-50 text-green-700 border-green-200' => $reg->status === 'approved',
                                                    'bg-red-50 text-red-600 border-red-200' => $reg->status === 'disapproved',
                                                ])>
                                                    {{ ucfirst($reg->status) }}
                                                </span>
                                            </td>
                                            <td class="py-3">
                                                <div class="flex items-center justify-center gap-3">
                                                    <button type="button" @click="openId = {{ $reg->id }}"
                                                        title="View details"
                                                        class="text-gray-400 hover:text-[#3b1735] transition-colors duration-200">
                                                        <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor"
                                                            stroke-width="1.8" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12z" />
                                                            <circle cx="12" cy="12" r="3" />
                                                        </svg>
                                                    </button>

                                                    @if ($reg->status === 'pending')
                                                        <form method="POST"
                                                            action="{{ route('admin.registrations.approve', $reg) }}">
                                                            @csrf
                                                            <button type="submit" title="Approve"
                                                                class="text-green-600 hover:text-green-700 transition-colors duration-200">
                                                                <svg class="w-[18px] h-[18px]" fill="none"
                                                                    stroke="currentColor" stroke-width="2.4"
                                                                    viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        d="M5 13l4 4L19 7" />
                                                                </svg>
                                                            </button>
                                                        </form>
                                                        <button type="button" @click="rejectId = {{ $reg->id }}"
                                                            title="Reject"
                                                            class="text-red-500 hover:text-red-600 transition-colors duration-200">
                                                            <svg class="w-[18px] h-[18px]" fill="none"
                                                                stroke="currentColor" stroke-width="2.4"
                                                                viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" d="M6 6l12 12M18 6L6 18" />
                                                            </svg>
                                                        </button>
                                                    @else
                                                        <span class="text-gray-300">—</span>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="py-8 text-center text-gray-400">
                                                No registrations yet.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @foreach ($recentRegistrations as $reg)
                            <div x-show="openId === {{ $reg->id }}" x-cloak
                                x-transition:enter="transition duration-300 ease-vendo"
                                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                                x-transition:leave="transition duration-200 ease-vendo"
                                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                                class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center p-4 overflow-y-auto"
                                @click.self="openId = null">
                                <div class="bg-[#FBF7F2] rounded-3xl p-6 w-full max-w-5xl my-8" @click.stop>
                                    <div class="flex items-center justify-between mb-4">
                                        <h3 class="font-bold text-lg text-[#2B1730]">Applicant Details</h3>
                                        <button type="button" @click="openId = null"
                                            class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
                                    </div>
                                    @include('admin.registrations.partials.applicant-details', ['user' => $reg])
                                </div>
                            </div>

                            @include('admin.registrations.partials.reject-modal', [
                                'user' => $reg,
                                'showExpr' => "rejectId === {$reg->id}",
                                'closeExpr' => 'rejectId = null',
                            ])
                        @endforeach
                    </div>

                    <!-- Recent Complaint -->
                    <div class="bg-white rounded-3xl p-5 sm:p-6 shadow-[0_18px_40px_-32px_rgba(43,23,48,0.55)]">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="font-bold text-[#2B1730] text-[17px]">Recent Complaint</h3>
                            <a href="{{ route('admin.complaints.index') }}" x-target.push="main-content sidebar"
                                class="text-sm text-[#3b1735] font-medium hover:underline">View All</a>
                        </div>

                        <div class="overflow-x-auto thin-scroll -mx-1 px-1">
                            <table class="w-full text-sm min-w-[400px]">
                                <thead>
                                    <tr class="text-left text-gray-500 border-b border-gray-100">
                                        <th class="py-2.5 font-medium">Complaint ID</th>
                                        <th class="py-2.5 font-medium">From</th>
                                        <th class="py-2.5 font-medium">Against</th>
                                        <th class="py-2.5 font-medium">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($recentComplaints as $complaint)
                                        <tr class="border-b border-gray-50 last:border-0">
                                            <td class="py-3 text-[#2B1730] whitespace-nowrap">
                                                #{{ str_pad($complaint->id, 5, '0', STR_PAD_LEFT) }}
                                            </td>
                                            <td class="py-3 text-gray-600 capitalize">
                                                {{ str_replace('logistics_center', 'logistics', $complaint->complainant->role ?? '—') }}
                                            </td>
                                            <td class="py-3 text-gray-600 capitalize">
                                                {{ str_replace('logistics_center', 'logistics', $complaint->respondent->role ?? '—') }}
                                            </td>
                                            <td class="py-3">
                                                <span @class([
                                                    'px-2.5 py-1 rounded-full text-xs font-medium whitespace-nowrap border',
                                                    'bg-[#FDECEC] text-[#C0392B] border-[#F3C9C4]' => $complaint->status === 'open',
                                                    'bg-[#FDF3E2] text-[#B45309] border-[#F3D9A6]' => $complaint->status === 'in_review',
                                                    'bg-[#E7F6EC] text-[#15803D] border-[#BFE6CC]' => $complaint->status === 'resolved',
                                                ])>
                                                    {{ $complaint->status === 'in_review' ? 'In review' : ucfirst($complaint->status) }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="py-8 text-center text-gray-400">
                                                No complaints yet.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============ RIGHT (rail) ============ -->
            <div class="space-y-5">

                <!-- Notifications -->
                <div class="bg-white rounded-3xl p-5 shadow-[0_18px_40px_-32px_rgba(43,23,48,0.55)]">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="font-bold text-[#2B1730] text-[17px]">Notifications</h3>
                        <a href="{{ route('admin.notifications.index') }}" x-target.push="main-content sidebar"
                            class="text-sm text-[#3b1735] font-medium hover:underline">View All</a>
                    </div>

                    <div class="space-y-1 max-h-[330px] overflow-y-auto thin-scroll pr-1">
                        @forelse ($notifications as $notification)
                            <div class="flex items-start gap-3 p-2.5 rounded-2xl hover:bg-[#F7F1F7] transition-colors duration-300 ease-vendo">
                                <img src="{{ asset('assets/icons/dashboard/' . $notification->icon()) }}" alt=""
                                    class="w-6 h-6 mt-0.5 flex-shrink-0">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold text-[#2B1730] leading-snug">
                                        {{ $notification->title }}</p>
                                    <p class="text-xs text-gray-500 mt-0.5 line-clamp-2">
                                        {{ str_replace(['courier', 'Courier'], ['logistics', 'Logistics'], $notification->message) }}
                                    </p>
                                </div>
                                <span class="text-[11px] text-gray-400 whitespace-nowrap mt-0.5">
                                    {{ $notification->timeAgo() }}
                                </span>
                            </div>
                        @empty
                            <p class="text-sm text-gray-400 py-4 text-center">No notifications yet.</p>
                        @endforelse
                    </div>
                </div>

                <!-- Pending Registrations -->
                <div class="bg-white rounded-3xl p-5 shadow-[0_18px_40px_-32px_rgba(43,23,48,0.55)]">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-bold text-[#2B1730] text-[17px]">Pending Registrations</h3>
                        <a href="{{ route('admin.registrations.index') }}" x-target.push="main-content sidebar"
                            class="text-sm text-[#3b1735] font-medium hover:underline">View All</a>
                    </div>

                    @php
                        $pendingRows = [
                            ['Sellers',   'sellers-registrations.svg',   $pendingRegistrations['sellers'] ?? 0,           '#2B1730'],
                            ['Logistics', 'couriers-registrations.svg',  $pendingRegistrations['logistics_centers'] ?? 0, '#C05E41'],
                            ['Buyers',    'buyers-registrations.svg',    $pendingRegistrations['buyers'] ?? 0,            '#C9A227'],
                        ];
                    @endphp

                    <div class="divide-y divide-gray-100">
                        @foreach ($pendingRows as [$label, $icon, $count, $color])
                            <div class="flex items-center gap-3 py-3 first:pt-0 last:pb-0">
                                <img src="{{ asset('assets/icons/dashboard/' . $icon) }}" alt=""
                                    class="w-6 h-6 flex-shrink-0">
                                <span class="text-sm text-gray-600 flex-1">{{ $label }}</span>
                                <span class="text-base font-bold" style="color: {{ $color }};">{{ $count }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Announcement -->
                <div class="relative bg-gradient-to-br from-[#4a1f42] to-[#2c0f28] rounded-3xl p-5 text-white overflow-hidden">
                    <img src="{{ asset('assets/icons/dashboard/announcement-icons.svg') }}" alt=""
                        class="absolute bottom-3 right-3 h-16 w-auto opacity-90 pointer-events-none">

                    <div class="relative pr-12">
                        <p class="text-sm font-bold text-[#e8c874] mb-2">Announcement</p>

                        @if ($announcement)
                            <h4 class="text-xl font-bold mb-2 leading-snug">{{ $announcement->title }}</h4>
                            <p class="text-sm text-white/70 mb-5 leading-relaxed">{{ $announcement->message }}</p>
                        @else
                            <h4 class="text-xl font-bold mb-2 leading-snug">Nothing announced yet</h4>
                            <p class="text-sm text-white/70 mb-5 leading-relaxed">
                                Publish an announcement to show it here and across Vendo.
                            </p>
                        @endif

                        <a href="{{ route('admin.platform-settings.index') }}" x-target.push="main-content sidebar"
                            class="inline-block bg-white text-[#3b1735] text-sm font-medium px-4 py-2.5 rounded-xl
                                   hover:bg-[#f6ecd9] transition-colors duration-300 ease-vendo">
                            Manage Announcement
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script>
        (function() {
            const ctx = document.getElementById('salesOverviewChart');
            if (!ctx || typeof Chart === 'undefined') return;

            const fill = (color, alpha) => {
                const g = ctx.getContext('2d').createLinearGradient(0, 0, 0, 260);
                g.addColorStop(0, color + alpha);
                g.addColorStop(1, color + '05');
                return g;
            };

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: @json($chartData['labels']),
                    datasets: [{
                            label: 'Sales',
                            data: @json($chartData['sales']),
                            borderColor: '#4A2A52',
                            backgroundColor: fill('#8B6E95', '66'),
                            borderWidth: 2,
                            fill: true,
                            tension: 0.35,
                            pointRadius: 3,
                            pointBackgroundColor: '#4A2A52',
                            pointBorderWidth: 0,
                            yAxisID: 'y',
                        },
                        {
                            label: 'Orders',
                            data: @json($chartData['orders']),
                            borderColor: '#C97B5F',
                            backgroundColor: fill('#E0916F', '55'),
                            borderWidth: 2,
                            fill: true,
                            tension: 0.35,
                            pointRadius: 3,
                            pointBackgroundColor: '#C97B5F',
                            pointBorderWidth: 0,
                            yAxisID: 'y1',
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    animation: { duration: 900, easing: 'easeOutQuart' },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#2B1730',
                            padding: 10,
                            cornerRadius: 10,
                            displayColors: true,
                            boxPadding: 4,
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            border: { display: false },
                            ticks: { color: '#9CA3AF', font: { size: 11 }, maxRotation: 0, autoSkip: true, maxTicksLimit: 7 }
                        },
                        y: {
                            type: 'linear',
                            position: 'left',
                            beginAtZero: true,
                            border: { display: false },
                            grid: { color: '#F1ECF1' },
                            ticks: {
                                color: '#9CA3AF',
                                font: { size: 11 },
                                callback: v => v >= 1000 ? (v / 1000) + 'k' : v
                            }
                        },
                        y1: {
                            type: 'linear',
                            position: 'right',
                            beginAtZero: true,
                            border: { display: false },
                            grid: { drawOnChartArea: false },
                            ticks: { color: '#9CA3AF', font: { size: 11 } }
                        }
                    }
                }
            });
        })();
    </script>
</x-admin-layout>