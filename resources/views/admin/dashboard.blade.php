<x-admin.layout title="Dashboard">
    @php
        $admin = Auth::guard('admin')->user();
        $hour = (int) now()->format('G');
        $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');

        /*
         * "Needs your attention" counts. The controller does not pass these yet, so they are read here.
         * Move them into DashboardController and pass $attention when convenient (see backend notes).
         */
        $attention = $attention ?? [
            ['label' => 'Registrations to review', 'count' => array_sum($pendingRegistrations ?? []), 'icon' => 'user-plus',
                'href' => route('admin.registrations.index'), 'clear' => 'No one is waiting'],
            ['label' => 'Products awaiting review', 'count' => \App\Models\Ecommerce\Product::where('status', 'for_review')->count(), 'icon' => 'clipboard-check',
                'href' => route('admin.seller-compliance.products-for-review'), 'clear' => 'Review queue is empty'],
            ['label' => 'Open complaints', 'count' => \App\Models\Complaints\Complaint::where('status', 'open')->count(), 'icon' => 'scale',
                'href' => route('admin.complaints.index'), 'clear' => 'No unanswered cases'],
            ['label' => 'Suspended sellers', 'count' => \App\Models\User::where('role', 'seller')->where('status', 'approved')->where('account_status', 'suspended')->count(), 'icon' => 'ban',
                'href' => route('admin.seller-compliance.suspended-sellers'), 'clear' => 'No active suspensions'],
        ];
        $attentionTotal = collect($attention)->sum('count');

        // Week-over-week movement, taken from the six weekly buckets already supplied for the chart.
        $lastIndex = max(0, count($chartData['sales']) - 1);
        $weekSales = (float) ($chartData['sales'][$lastIndex] ?? 0);
        $prevSales = (float) ($chartData['sales'][$lastIndex - 1] ?? 0);
        $weekOrders = (int) ($chartData['orders'][$lastIndex] ?? 0);
        $prevOrders = (int) ($chartData['orders'][$lastIndex - 1] ?? 0);
        $change = fn ($now, $before) => $before > 0 ? (int) round((($now - $before) / $before) * 100) : null;

        $totalOrders = max(1, (int) $salesSummary['total_orders']);
        $completionRate = (int) round(($salesSummary['completed_orders'] / $totalOrders) * 100);
        $returnRate = (int) round(($salesSummary['return_refund'] / $totalOrders) * 100);

        $statCards = [
            ['label' => 'Total orders', 'value' => number_format($stats['total_orders']), 'icon' => 'total-orders-icon.svg', 'bg' => '#E4DAEA',
                'delta' => $change($weekOrders, $prevOrders), 'note' => number_format($weekOrders) . ' this week'],
            ['label' => 'Total sales', 'value' => '₱' . number_format($stats['total_sales'], 2), 'icon' => 'total-sales-icon.svg', 'bg' => '#F6DCCF',
                'delta' => $change($weekSales, $prevSales), 'note' => '₱' . number_format($weekSales, 2) . ' this week'],
            ['label' => 'Total users', 'value' => number_format($stats['total_users']), 'icon' => 'total-users-icon.svg', 'bg' => '#F3E9C8',
                'delta' => null, 'note' => 'Buyers, sellers and logistics'],
            ['label' => 'Total sellers', 'value' => number_format($stats['total_sellers']), 'icon' => 'total-sellers-icon.svg', 'bg' => '#E9D8EC',
                'delta' => null, 'note' => ($pendingRegistrations['sellers'] ?? 0) . ' waiting for approval'],
        ];

        $card = 'rounded-2xl border border-[#ece4ec] bg-white';
    @endphp

    <div class="mx-auto w-full max-w-[1440px] p-4 sm:p-6 lg:p-7" x-data="{ openId: null, rejectId: null }">

        <!-- Greeting + quick actions -->
        <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-sm text-gray-500">{{ now()->format('l, F j') }}</p>
                <h1 class="font-display text-[26px] font-semibold leading-tight text-[#2B1730] sm:text-[30px]">
                    {{ $greeting }}, {{ $admin->first_name ?? $admin->name }}
                </h1>
            </div>

            <div class="flex flex-wrap gap-2.5">
                <a href="{{ route('admin.registrations.index') }}" x-target.push="main-content sidebar"
                    class="inline-flex h-10 items-center gap-2 rounded-xl bg-[#3b1735] px-4 text-sm font-medium text-white transition-colors duration-200 hover:bg-[#4d1f45]
                           focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40 focus-visible:ring-offset-2">
                    <x-admin.icon name="user-plus" class="h-4 w-4" /> Review registrations
                </a>
                <a href="{{ route('admin.seller-compliance.products-for-review') }}"
                    class="inline-flex h-10 items-center gap-2 rounded-xl border border-[#ddd0e0] bg-white px-4 text-sm font-medium text-[#3b1735] transition-colors duration-200 hover:bg-[#F7F1F7]
                           focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40">
                    <x-admin.icon name="clipboard-check" class="h-4 w-4" /> Review products
                </a>
                <a href="{{ route('admin.platform-settings.index') }}" x-target.push="main-content sidebar"
                    class="inline-flex h-10 items-center gap-2 rounded-xl border border-[#ddd0e0] bg-white px-4 text-sm font-medium text-[#3b1735] transition-colors duration-200 hover:bg-[#F7F1F7]
                           focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40">
                    <x-admin.icon name="megaphone" class="h-4 w-4" /> New announcement
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 items-start gap-5 xl:grid-cols-4">

            <!-- ============ LEFT (main) ============ -->
            <div class="space-y-5 xl:col-span-3">

                <!-- Needs your attention: the one thing an admin opens this page to find out -->
                <section aria-labelledby="attention-title" class="{{ $card }} vd-rise" style="--i: 0">
                    <div class="flex flex-wrap items-center justify-between gap-2 px-5 pt-4 sm:px-6">
                        <h2 id="attention-title" class="font-display text-base font-semibold text-[#2B1730]">Needs your attention</h2>
                        @if ($attentionTotal > 0)
                            <p class="text-sm text-gray-500"><span class="font-semibold text-[#2B1730]">{{ number_format($attentionTotal) }}</span> {{ \Illuminate\Support\Str::plural('item', $attentionTotal) }} waiting</p>
                        @else
                            <p class="inline-flex items-center gap-1.5 text-sm font-medium text-green-700"><x-admin.icon name="check-circle" class="h-4 w-4" /> You're all caught up</p>
                        @endif
                    </div>

                    <div class="mt-3 grid grid-cols-2 divide-[#f3edf4] border-t border-[#f3edf4] lg:grid-cols-4 lg:divide-x">
                        @foreach ($attention as $item)
                            <a href="{{ $item['href'] }}"
                                class="group flex items-start gap-3 p-4 transition-colors duration-200 hover:bg-[#FBF8FB] sm:p-5
                                       focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-[#3b1735]/40
                                       {{ $loop->index >= 2 ? 'border-t border-[#f3edf4] lg:border-t-0' : '' }}">
                                <span class="mt-0.5 flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl
                                             {{ $item['count'] > 0 ? 'bg-[#F6EBC9] text-[#7A5A00]' : 'bg-[#EEF6EF] text-green-700' }}">
                                    <x-admin.icon :name="$item['count'] > 0 ? $item['icon'] : 'check'" class="h-[18px] w-[18px]" />
                                </span>
                                <span class="min-w-0">
                                    <span class="block text-2xl font-semibold leading-none tabular-nums text-[#2B1730]">{{ number_format($item['count']) }}</span>
                                    <span class="mt-1.5 block text-[13px] font-medium leading-snug text-gray-700">{{ $item['label'] }}</span>
                                    @if ($item['count'] === 0)
                                        <span class="mt-0.5 block text-xs text-gray-400">{{ $item['clear'] }}</span>
                                    @endif
                                </span>
                            </a>
                        @endforeach
                    </div>
                </section>

                <!-- KPI cards -->
                <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                    @foreach ($statCards as $stat)
                        <div class="vd-rise rounded-2xl p-4 text-[#2B1730] sm:p-5" style="--i: {{ $loop->iteration }}; background: {{ $stat['bg'] }};">
                            <div class="mb-3 flex items-center gap-2.5">
                                <span class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-white/60">
                                    <img src="{{ asset('assets/icons/dashboard/' . $stat['icon']) }}" alt="" class="h-5 w-5">
                                </span>
                                <span class="text-sm font-medium leading-tight">{{ $stat['label'] }}</span>
                            </div>
                            <p class="break-words text-[22px] font-semibold leading-tight tracking-tight tabular-nums sm:text-2xl">{{ $stat['value'] }}</p>
                            <div class="mt-2 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-[#2B1730]/70">
                                @if ($stat['delta'] !== null)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-white/70 px-2 py-0.5 font-semibold {{ $stat['delta'] >= 0 ? 'text-green-700' : 'text-red-700' }}">
                                        <x-admin.icon :name="$stat['delta'] >= 0 ? 'trending-up' : 'trending-down'" class="h-3.5 w-3.5" />
                                        {{ $stat['delta'] >= 0 ? '+' : '' }}{{ $stat['delta'] }}%
                                    </span>
                                @endif
                                <span>{{ $stat['note'] }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Sales overview + summary -->
                <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">

                    <section class="{{ $card }} vd-rise p-5 sm:p-6 lg:col-span-2" style="--i: 5" aria-labelledby="sales-title">
                        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h2 id="sales-title" class="font-display text-base font-semibold text-[#2B1730]">Sales overview</h2>
                                <p class="text-xs text-gray-500">Last 6 weeks. The darkest bar is this week so far.</p>
                            </div>
                            <div class="inline-flex rounded-xl border border-[#ece4ec] bg-[#FBF8FB] p-1" role="group" aria-label="Chart metric">
                                @foreach (['sales' => 'Sales', 'orders' => 'Orders'] as $metric => $label)
                                    <button type="button" data-chart-metric="{{ $metric }}" aria-pressed="{{ $loop->first ? 'true' : 'false' }}"
                                        class="rounded-lg px-3.5 py-1.5 text-sm font-medium text-gray-600 transition-colors duration-200
                                               aria-pressed:bg-[#3b1735] aria-pressed:text-white
                                               focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40">
                                        {{ $label }}
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        @if (array_sum($chartData['orders']) === 0)
                            <div class="flex h-[240px] flex-col items-center justify-center rounded-xl border border-dashed border-[#e2d6e5] bg-[#FBF8FB] px-6 text-center sm:h-[260px]">
                                <x-admin.icon name="trending-up" class="mb-2 h-7 w-7 text-[#b9a4bd]" />
                                <p class="text-sm font-medium text-[#2B1730]">No orders in the last 6 weeks</p>
                                <p class="mt-1 max-w-xs text-xs text-gray-500">The chart fills in as buyers place orders.</p>
                            </div>
                        @else
                            <div class="h-[240px] sm:h-[260px]">
                                <canvas id="salesOverviewChart" role="img" aria-label="Weekly sales and orders for the last six weeks"
                                    data-chart-config="{{ json_encode(['labels' => $chartData['labels'], 'sales' => $chartData['sales'], 'orders' => $chartData['orders']], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}"></canvas>
                            </div>
                        @endif
                    </section>

                    <section class="{{ $card }} vd-rise flex flex-col p-5 sm:p-6" style="--i: 6" aria-labelledby="summary-title">
                        <h2 id="summary-title" class="mb-3 font-display text-base font-semibold text-[#2B1730]">Sales summary</h2>

                        @php
                            $summaryRows = [
                                ['Gross sales', '₱' . number_format($salesSummary['gross_sales'], 2)],
                                ['Total orders', number_format($salesSummary['total_orders'])],
                                ['Average order value', '₱' . number_format($salesSummary['average_order_value'], 2)],
                            ];
                        @endphp

                        <div class="divide-y divide-[#f3edf4]">
                            @foreach ($summaryRows as [$label, $value])
                                <div class="flex items-center justify-between gap-3 py-2.5 text-sm first:pt-0">
                                    <span class="text-gray-500">{{ $label }}</span>
                                    <span class="text-right font-semibold tabular-nums text-[#2B1730]">{{ $value }}</span>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-3 space-y-3.5 border-t border-[#f3edf4] pt-4">
                            @foreach ([
                                ['Completed', $salesSummary['completed_orders'], $completionRate, 'bg-green-600'],
                                ['Returned or refunded', $salesSummary['return_refund'], $returnRate, 'bg-red-500'],
                            ] as [$label, $count, $percent, $bar])
                                <div>
                                    <div class="mb-1.5 flex items-baseline justify-between text-sm">
                                        <span class="text-gray-600">{{ $label }}</span>
                                        <span class="font-semibold tabular-nums text-[#2B1730]">{{ number_format($count) }} <span class="text-xs font-normal text-gray-400">({{ $percent }}%)</span></span>
                                    </div>
                                    <div class="h-1.5 overflow-hidden rounded-full bg-[#F1E9F1]" role="img" aria-label="{{ $label }}: {{ $percent }} percent of orders">
                                        <div class="h-full rounded-full {{ $bar }}" style="width: {{ $percent }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-auto pt-5">
                            <a href="{{ route('admin.reports.index') }}" x-target.push="main-content sidebar"
                                class="flex items-center justify-center gap-2 rounded-xl bg-[#3b1735] py-2.5 text-sm font-medium text-white
                                       transition-colors duration-300 ease-vendo hover:bg-[#4d1f45]
                                       focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40 focus-visible:ring-offset-2">
                                View full report <x-admin.icon name="arrow-right" class="h-4 w-4" />
                            </a>
                        </div>
                    </section>
                </div>

                <!-- Recent registrations + complaints -->
                <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">

                    <!-- Recent Registrations -->
                    <section class="{{ $card }} vd-rise p-5 sm:p-6" style="--i: 7" aria-labelledby="reg-title">
                        <div class="mb-3 flex items-center justify-between">
                            <h2 id="reg-title" class="font-display text-base font-semibold text-[#2B1730]">Recent registrations</h2>
                            <a href="{{ route('admin.registrations.index') }}" x-target.push="main-content sidebar"
                                class="text-sm font-medium text-[#3b1735] hover:underline">View all</a>
                        </div>

                        @if ($recentRegistrations->isEmpty())
                            <div class="flex flex-col items-center rounded-xl border border-dashed border-[#e2d6e5] bg-[#FBF8FB] px-6 py-9 text-center">
                                <x-admin.icon name="inbox" class="mb-2 h-7 w-7 text-[#b9a4bd]" />
                                <p class="text-sm font-medium text-[#2B1730]">No registrations yet</p>
                                <p class="mt-1 text-xs text-gray-500">New buyer, seller and logistics sign-ups will appear here.</p>
                            </div>
                        @else
                            <div class="thin-scroll -mx-1 overflow-x-auto px-1">
                                <table class="w-full min-w-[440px] text-sm">
                                    <thead>
                                        <tr class="border-b border-[#f3edf4] text-left text-[13px] text-gray-500">
                                            <th scope="col" class="py-2.5 font-medium">Name</th>
                                            <th scope="col" class="py-2.5 font-medium">Type</th>
                                            <th scope="col" class="py-2.5 font-medium">Date</th>
                                            <th scope="col" class="py-2.5 font-medium">Status</th>
                                            <th scope="col" class="py-2.5 text-center font-medium">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-[#f8f4f8]">
                                        @foreach ($recentRegistrations as $reg)
                                            <tr>
                                                <td class="max-w-[150px] py-3 pr-2 text-[#2B1730] [overflow-wrap:anywhere]">{{ $reg->name }}</td>
                                                <td class="whitespace-nowrap py-3 text-gray-600">{{ $reg->role === 'logistics_center' ? 'Logistics' : ucfirst($reg->role) }}</td>
                                                <td class="whitespace-nowrap py-3 text-gray-600">{{ $reg->created_at->format('M d, Y') }}</td>
                                                <td class="py-3">
                                                    <span @class([
                                                        'inline-flex whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-medium',
                                                        'bg-[#FDF3E2] text-[#92400E]' => $reg->status === 'pending',
                                                        'bg-green-50 text-green-700' => $reg->status === 'approved',
                                                        'bg-red-50 text-red-700' => $reg->status === 'disapproved',
                                                    ])>{{ ucfirst($reg->status) }}</span>
                                                </td>
                                                <td class="py-3">
                                                    <div class="flex items-center justify-center gap-1">
                                                        <button type="button" @click="openId = {{ $reg->id }}" title="View details" aria-label="View details for {{ $reg->name }}"
                                                            class="flex h-8 w-8 items-center justify-center rounded-full text-gray-400 transition-colors duration-200 hover:bg-[#F1E9F1] hover:text-[#3b1735]
                                                                   focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40">
                                                            <x-admin.icon name="eye" class="h-[18px] w-[18px]" />
                                                        </button>

                                                        @if ($reg->status === 'pending')
                                                            <form method="POST" action="{{ route('admin.registrations.approve', $reg) }}">
                                                                @csrf
                                                                <button type="submit" title="Approve" aria-label="Approve {{ $reg->name }}"
                                                                    class="flex h-8 w-8 items-center justify-center rounded-full text-green-600 transition-colors duration-200 hover:bg-green-50
                                                                           focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-green-600/40">
                                                                    <x-admin.icon name="check" class="h-[18px] w-[18px]" stroke="2.4" />
                                                                </button>
                                                            </form>
                                                            <button type="button" @click="rejectId = {{ $reg->id }}" title="Reject" aria-label="Reject {{ $reg->name }}"
                                                                class="flex h-8 w-8 items-center justify-center rounded-full text-red-500 transition-colors duration-200 hover:bg-red-50
                                                                       focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500/40">
                                                                <x-admin.icon name="x" class="h-[18px] w-[18px]" stroke="2.4" />
                                                            </button>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif

                        @foreach ($recentRegistrations as $reg)
                            <div x-show="openId === {{ $reg->id }}" x-cloak
                                x-transition:enter="transition duration-300 ease-vendo"
                                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                                x-transition:leave="transition duration-200 ease-vendo"
                                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                                class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black/40 p-4"
                                @click.self="openId = null" @keydown.escape.window="openId = null">
                                <div class="my-8 w-full max-w-5xl rounded-3xl bg-[#FBF7F2] p-6" @click.stop>
                                    <div class="mb-4 flex items-center justify-between">
                                        <h3 class="text-lg font-bold text-[#2B1730]">Applicant Details</h3>
                                        <button type="button" @click="openId = null" aria-label="Close"
                                            class="flex h-8 w-8 items-center justify-center rounded-full text-gray-400 hover:bg-white hover:text-gray-600">
                                            <x-admin.icon name="x" class="h-5 w-5" />
                                        </button>
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
                    </section>

                    <!-- Recent complaints -->
                    <section class="{{ $card }} vd-rise p-5 sm:p-6" style="--i: 8" aria-labelledby="cmp-title">
                        <div class="mb-3 flex items-center justify-between">
                            <h2 id="cmp-title" class="font-display text-base font-semibold text-[#2B1730]">Recent complaints and disputes</h2>
                            <a href="{{ route('admin.complaints.index') }}" x-target.push="main-content sidebar"
                                class="text-sm font-medium text-[#3b1735] hover:underline">View all</a>
                        </div>

                        @if ($recentComplaints->isEmpty())
                            <div class="flex flex-col items-center rounded-xl border border-dashed border-[#e2d6e5] bg-[#FBF8FB] px-6 py-9 text-center">
                                <x-admin.icon name="scale" class="mb-2 h-7 w-7 text-[#b9a4bd]" />
                                <p class="text-sm font-medium text-[#2B1730]">No complaints yet</p>
                                <p class="mt-1 text-xs text-gray-500">New complaints and disputes will show up here.</p>
                            </div>
                        @else
                            <ul class="divide-y divide-[#f8f4f8]">
                                @foreach ($recentComplaints as $complaint)
                                    <li>
                                        <a href="{{ route('admin.complaints.show', $complaint) }}"
                                            class="-mx-2 flex items-start gap-3 rounded-xl px-2 py-3 transition-colors duration-200 hover:bg-[#FBF8FB]
                                                   focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40">
                                            <div class="min-w-0 flex-1">
                                                <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                                    @include('admin.complaints.partials.type-badge')
                                                    <span class="text-xs text-gray-400">{{ $complaint->created_at->diffForHumans() }}</span>
                                                </div>
                                                <p class="mt-1.5 text-sm text-[#2B1730] [overflow-wrap:anywhere]">
                                                    <span class="font-medium">{{ $complaint->complainant->name ?? 'Unknown' }}</span>
                                                    <span class="text-gray-400">reported</span>
                                                    <span class="font-medium">{{ $complaint->respondent->name ?? 'Unknown' }}</span>
                                                </p>
                                            </div>
                                            <div class="flex-shrink-0 pt-0.5">@include('admin.complaints.partials.status-badge')</div>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </section>
                </div>
            </div>

            <!-- ============ RIGHT (rail) ============ -->
            <div class="space-y-5">

                <!-- Notifications -->
                <section class="{{ $card }} vd-rise p-5" style="--i: 3" aria-labelledby="notif-title">
                    <div class="mb-3 flex items-center justify-between">
                        <h2 id="notif-title" class="font-display text-base font-semibold text-[#2B1730]">Notifications</h2>
                        <a href="{{ route('admin.notifications.index') }}" x-target.push="main-content sidebar"
                            class="text-sm font-medium text-[#3b1735] hover:underline">View all</a>
                    </div>

                    <div class="thin-scroll max-h-[330px] space-y-1 overflow-y-auto pr-1">
                        @forelse ($notifications as $notification)
                            <div class="flex items-start gap-3 rounded-xl p-2.5 transition-colors duration-300 ease-vendo hover:bg-[#F7F1F7]">
                                <img src="{{ asset('assets/icons/dashboard/' . $notification->icon()) }}" alt="" class="mt-0.5 h-6 w-6 flex-shrink-0">
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold leading-snug text-[#2B1730]">{{ $notification->title }}</p>
                                    <p class="mt-0.5 line-clamp-2 text-xs text-gray-500">{{ str_replace(['courier', 'Courier'], ['logistics', 'Logistics'], $notification->message) }}</p>
                                </div>
                                <span class="mt-0.5 whitespace-nowrap text-[11px] text-gray-400">{{ $notification->timeAgo() }}</span>
                            </div>
                        @empty
                            <div class="flex flex-col items-center px-4 py-7 text-center">
                                <x-admin.icon name="inbox" class="mb-2 h-6 w-6 text-[#b9a4bd]" />
                                <p class="text-sm font-medium text-[#2B1730]">Nothing new</p>
                                <p class="mt-0.5 text-xs text-gray-500">Registrations, reports and support messages land here.</p>
                            </div>
                        @endforelse
                    </div>
                </section>

                <!-- Pending registrations by type -->
                <section class="{{ $card }} vd-rise p-5" style="--i: 4" aria-labelledby="pending-title">
                    <div class="mb-4 flex items-center justify-between">
                        <h2 id="pending-title" class="font-display text-base font-semibold text-[#2B1730]">Pending registrations</h2>
                        <a href="{{ route('admin.registrations.index') }}" x-target.push="main-content sidebar"
                            class="text-sm font-medium text-[#3b1735] hover:underline">View all</a>
                    </div>

                    @php
                        $pendingRows = [
                            ['Sellers', 'sellers-registrations.svg', $pendingRegistrations['sellers'] ?? 0, '#3b1735'],
                            ['Logistics', 'couriers-registrations.svg', $pendingRegistrations['logistics_centers'] ?? 0, '#C05E41'],
                            ['Buyers', 'buyers-registrations.svg', $pendingRegistrations['buyers'] ?? 0, '#C9A227'],
                        ];
                        $pendingMax = max(1, collect($pendingRows)->max(fn ($r) => $r[2]));
                    @endphp

                    <div class="space-y-4">
                        @foreach ($pendingRows as [$label, $icon, $count, $color])
                            <div>
                                <div class="mb-1.5 flex items-center gap-2.5">
                                    <img src="{{ asset('assets/icons/dashboard/' . $icon) }}" alt="" class="h-5 w-5 flex-shrink-0">
                                    <span class="flex-1 text-sm text-gray-600">{{ $label }}</span>
                                    <span class="text-base font-semibold tabular-nums" style="color: {{ $color }};">{{ $count }}</span>
                                </div>
                                <div class="h-1.5 overflow-hidden rounded-full bg-[#F1E9F1]">
                                    <div class="h-full rounded-full" style="width: {{ $count > 0 ? max(6, round($count / $pendingMax * 100)) : 0 }}%; background: {{ $color }};"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>

                <!-- Announcement (flat plum, no gradient) -->
                <section class="vd-rise relative overflow-hidden rounded-2xl bg-[#3b1735] p-5 text-white" style="--i: 5" aria-labelledby="ann-title">
                    <img src="{{ asset('assets/icons/dashboard/announcement-icons.svg') }}" alt=""
                        class="pointer-events-none absolute bottom-3 right-3 h-14 w-auto opacity-80">

                    <div class="relative pr-12">
                        <h2 id="ann-title" class="mb-2 text-sm font-semibold text-[#e8c874]">Announcement</h2>

                        @if ($announcement)
                            <h3 class="mb-2 text-lg font-semibold leading-snug">{{ $announcement->title }}</h3>
                            <p class="mb-5 line-clamp-4 text-sm leading-relaxed text-white/75">{{ $announcement->message }}</p>
                        @else
                            <h3 class="mb-2 text-lg font-semibold leading-snug">Nothing announced yet</h3>
                            <p class="mb-5 text-sm leading-relaxed text-white/75">Publish an announcement to show it here and across Vendo.</p>
                        @endif

                        <a href="{{ route('admin.platform-settings.index') }}" x-target.push="main-content sidebar"
                            class="inline-block rounded-xl bg-white px-4 py-2.5 text-sm font-medium text-[#3b1735] transition-colors duration-300 ease-vendo hover:bg-[#f6ecd9]
                                   focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#e8c874]">
                            Manage announcement
                        </a>
                    </div>
                </section>

            </div>
        </div>
    </div>

    @vite('resources/js/admin/dashboard.js')
@include('shared.live-revision', ['endpoint' => route('admin.live', 'dashboard'), 'mode' => 'reload'])
</x-admin.layout>