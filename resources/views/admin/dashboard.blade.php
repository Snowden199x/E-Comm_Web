<x-admin.layout title="Dashboard">
    @php
        $admin = Auth::guard('admin')->user();
        $hour = (int) now()->format('G');
        $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');

        $totalOrders = max(1, (int) $salesSummary['total_orders']);
        $completionRate = (int) round(($salesSummary['completed_orders'] / $totalOrders) * 100);
        $returnRate = (int) round(($salesSummary['return_refund'] / $totalOrders) * 100);

        $statCards = [
            ['label' => 'Total orders', 'value' => number_format($stats['total_orders']), 'icon' => 'package', 'tint' => 'bg-vd-lavender border-vd-plum-soft', 'chip' => 'bg-vd-plum'],
            ['label' => 'Total sales', 'value' => '₱' . number_format($stats['total_sales'], 2), 'icon' => 'banknote', 'tint' => 'bg-vd-peach border-vd-peach-line', 'chip' => 'bg-vd-clay'],
            ['label' => 'Total users', 'value' => number_format($stats['total_users']), 'icon' => 'users', 'tint' => 'bg-vd-sand border-vd-sand-line', 'chip' => 'bg-vd-gold'],
            ['label' => 'Total sellers', 'value' => number_format($stats['total_sellers']), 'icon' => 'store', 'tint' => 'bg-vd-mauve border-vd-mauve-line', 'chip' => 'bg-vd-orchid'],
        ];

        // Shared look. Cards: white, 1px border, no shadow. Secondary buttons: bordered. Green and red are only for approve and reject.
        $card = 'rounded-xl border border-vd-line bg-white';
        $focus = 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-plum/40';
        $linkBtn = "inline-flex h-7 items-center rounded-lg border border-vd-line-strong bg-white px-2.5 text-xs font-medium text-vd-plum transition-colors duration-200 hover:bg-vd-tint {$focus}";
        $sectionTitle = 'font-display text-sm font-semibold text-vd-ink';
    @endphp

    <div class="mx-auto w-full max-w-[1440px] p-4 sm:p-4 lg:p-6" x-data="{ openId: null, rejectId: null }">

        <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="text-sm text-vd-muted">{{ now()->format('l, F j') }}</p>
                <h1 class="font-display text-xl font-semibold leading-tight text-vd-ink sm:text-2xl">{{ $greeting }}, {{ $admin->first_name ?? $admin->name }}</h1>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.registrations.index') }}" x-target.push="main-content sidebar" data-skeleton="table"
                    class="inline-flex h-9 items-center gap-2 rounded-lg bg-vd-plum px-3.5 text-sm font-medium text-white transition-colors duration-200 hover:bg-vd-plum-hover {{ $focus }} focus-visible:ring-offset-2">
                    <x-admin.icon name="user-plus" class="h-4 w-4" /> Review registrations
                </a>
                <a href="{{ route('admin.seller-compliance.products-for-review') }}"
                    class="inline-flex h-9 items-center gap-2 rounded-lg border border-vd-line-strong bg-white px-3.5 text-sm font-medium text-vd-plum transition-colors duration-200 hover:bg-vd-tint {{ $focus }}">
                    <x-admin.icon name="clipboard-check" class="h-4 w-4" /> Review products
                </a>
                <a href="{{ route('admin.platform-settings.index') }}" x-target.push="main-content sidebar" data-skeleton="form"
                    class="inline-flex h-9 items-center gap-2 rounded-lg border border-vd-line-strong bg-white px-3.5 text-sm font-medium text-vd-plum transition-colors duration-200 hover:bg-vd-tint {{ $focus }}">
                    <x-admin.icon name="megaphone" class="h-4 w-4" /> Create announcement
                </a>
            </div>
        </div>

        <x-admin.flash class="mb-5" />

        <div class="grid grid-cols-1 items-start gap-4 xl:grid-cols-4">

            <div class="space-y-4 xl:col-span-3">

                {{-- Stat cards --}}
                <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                    @foreach ($statCards as $stat)
                        <div class="flex items-center gap-3 rounded-xl border p-4 {{ $stat['tint'] }}">
                            <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full text-white {{ $stat['chip'] }}">
                                <x-admin.icon :name="$stat['icon']" class="h-5 w-5" />
                            </span>
                            <div class="min-w-0">
                                <p class="text-xs text-vd-ink">{{ $stat['label'] }}</p>
                                <p class="break-words text-lg font-semibold leading-tight tabular-nums text-vd-ink">{{ $stat['value'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Sales overview + summary --}}
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">

                    <section class="{{ $card }} p-4 lg:col-span-2" aria-labelledby="sales-title">
                        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h2 id="sales-title" class="{{ $sectionTitle }}">Sales overview</h2>
                                <p class="text-xs text-vd-muted">Last 6 weeks. The dark bar is this week so far.</p>
                            </div>
                            <div class="inline-flex rounded-xl border border-vd-line bg-vd-tint p-1" role="group" aria-label="Chart metric">
                                @foreach (['sales' => 'Sales', 'orders' => 'Orders'] as $metric => $label)
                                    <button type="button" data-chart-metric="{{ $metric }}" aria-pressed="{{ $loop->first ? 'true' : 'false' }}"
                                        class="rounded-lg px-3.5 py-1.5 text-sm font-medium text-vd-muted transition-colors duration-200 aria-pressed:bg-vd-plum aria-pressed:text-white {{ $focus }}">
                                        {{ $label }}
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        @if (array_sum($chartData['orders']) === 0)
                            <x-admin.empty-state size="sm" icon="bar-chart" title="No orders in 6 weeks" text="The chart fills in when buyers place orders."
                                class="h-[240px] justify-center rounded-xl border border-dashed border-vd-line-strong bg-vd-tint sm:h-[260px]" />
                        @else
                            <div class="relative h-[200px] sm:h-[220px]">
                                {{-- Loading: shown until Chart.js draws. Error: shown if Chart.js never loads. --}}
                                <div data-chart-loading class="vd-skeleton absolute inset-0 rounded-xl" aria-hidden="true"></div>
                                <canvas id="salesOverviewChart" role="img" aria-label="Weekly sales and orders for the last six weeks"
                                    data-chart-config="{{ json_encode(['labels' => $chartData['labels'], 'sales' => $chartData['sales'], 'orders' => $chartData['orders']], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}"></canvas>
                            </div>
                            <x-admin.empty-state data-chart-error hidden size="sm" tone="red" icon="alert-circle" title="Chart didn't load" text="Check your connection, then reload the page."
                                class="h-[240px] justify-center rounded-xl border border-vd-danger-line sm:h-[260px]">
                                <button type="button" onclick="window.location.reload()" class="{{ $linkBtn }}">Reload page</button>
                            </x-admin.empty-state>
                        @endif
                    </section>

                    <section class="{{ $card }} flex flex-col p-4" aria-labelledby="summary-title">
                        <h2 id="summary-title" class="mb-3 {{ $sectionTitle }}">Sales summary</h2>

                        @php
                            $summaryRows = [
                                ['Gross sales', '₱' . number_format($salesSummary['gross_sales'], 2)],
                                ['Total orders', number_format($salesSummary['total_orders'])],
                                ['Average order', '₱' . number_format($salesSummary['average_order_value'], 2)],
                            ];
                        @endphp

                        <div class="divide-y divide-vd-line">
                            @foreach ($summaryRows as [$label, $value])
                                <div class="flex items-center justify-between gap-3 py-2.5 text-sm first:pt-0">
                                    <span class="text-vd-muted">{{ $label }}</span>
                                    <span class="text-right font-semibold tabular-nums text-vd-ink">{{ $value }}</span>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-3 space-y-3.5 border-t border-vd-line pt-4">
                            @foreach ([
                                ['Completed', $salesSummary['completed_orders'], $completionRate, 'bg-vd-success-ink'],
                                ['Returned or refunded', $salesSummary['return_refund'], $returnRate, 'bg-vd-danger-ink'],
                            ] as [$label, $count, $percent, $bar])
                                <div>
                                    <div class="mb-1.5 flex items-baseline justify-between text-sm">
                                        <span class="text-vd-muted">{{ $label }}</span>
                                        <span class="font-semibold tabular-nums text-vd-ink">{{ number_format($count) }} <span class="text-xs font-normal text-vd-muted">({{ $percent }}%)</span></span>
                                    </div>
                                    <div class="h-1.5 overflow-hidden rounded-full bg-vd-lavender" role="img" aria-label="{{ $label }}: {{ $percent }} percent of orders">
                                        <div class="h-full rounded-full {{ $bar }}" style="width: {{ $percent }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-auto pt-5">
                            <a href="{{ route('admin.reports.index') }}" x-target.push="main-content sidebar" data-skeleton="dashboard"
                                class="flex h-9 items-center justify-center gap-2 rounded-lg bg-vd-plum text-sm font-medium text-white transition-colors duration-200 hover:bg-vd-plum-hover {{ $focus }} focus-visible:ring-offset-2">
                                Open full report <x-admin.icon name="arrow-right" class="h-4 w-4" />
                            </a>
                        </div>
                    </section>
                </div>

                {{-- Recent registrations + complaints --}}
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">

                    <section class="{{ $card }} p-4" aria-labelledby="reg-title">
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <h2 id="reg-title" class="{{ $sectionTitle }}">Recent registrations</h2>
                            <a href="{{ route('admin.registrations.index') }}" x-target.push="main-content sidebar" data-skeleton="table" class="{{ $linkBtn }}">View all</a>
                        </div>

                        @if ($recentRegistrations->isEmpty())
                            <x-admin.empty-state size="sm" icon="inbox" title="No registrations" text="New buyer, seller and logistics sign-ups appear here." />
                        @else
                            <div class="thin-scroll -mx-1 overflow-x-auto px-1">
                                <table class="w-full min-w-[440px] text-[13px]">
                                    <thead>
                                        <tr class="border-b border-vd-line text-left text-[13px] text-vd-muted">
                                            <th scope="col" class="py-2.5 font-medium">Name</th>
                                            <th scope="col" class="py-2.5 font-medium">Type</th>
                                            <th scope="col" class="py-2.5 font-medium">Date</th>
                                            <th scope="col" class="py-2.5 font-medium">Status</th>
                                            <th scope="col" class="py-2.5 text-center font-medium">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-vd-line">
                                        @foreach ($recentRegistrations as $reg)
                                            <tr>
                                                <td class="max-w-[140px] py-2 pr-2 text-vd-ink [overflow-wrap:anywhere]">{{ $reg->name }}</td>
                                                <td class="whitespace-nowrap py-2 text-vd-muted">{{ $reg->role === 'logistics_center' ? 'Logistics' : ucfirst($reg->role) }}</td>
                                                <td class="whitespace-nowrap py-2 text-vd-muted">{{ $reg->created_at->format('M j, Y') }}</td>
                                                <td class="py-2">
                                                    <span @class([
                                                        'inline-flex whitespace-nowrap rounded-full border px-2.5 py-0.5 text-xs font-medium',
                                                        'border-vd-warning-line bg-vd-warning-bg text-vd-warning-ink' => $reg->status === 'pending',
                                                        'border-vd-success-line bg-vd-success-bg text-vd-success-ink' => $reg->status === 'approved',
                                                        'border-vd-danger-line bg-vd-danger-bg text-vd-danger-ink' => $reg->status === 'disapproved',
                                                    ])>{{ $reg->status === 'disapproved' ? 'Rejected' : ucfirst($reg->status) }}</span>
                                                </td>
                                                <td class="py-2">
                                                    <div class="flex items-center justify-center gap-1.5">
                                                        <button type="button" @click="openId = {{ $reg->id }}" title="View details" aria-label="View details for {{ $reg->name }}"
                                                            class="flex h-7 w-7 items-center justify-center rounded-md border border-vd-line-strong bg-white text-vd-plum transition-colors duration-200 hover:bg-vd-tint {{ $focus }}">
                                                            <x-admin.icon name="eye" class="h-3.5 w-3.5" />
                                                        </button>
                                                        @if ($reg->status === 'pending')
                                                            <form method="POST" action="{{ route('admin.registrations.approve', $reg) }}">
                                                                @csrf
                                                                <button type="submit" title="Approve" aria-label="Approve {{ $reg->name }}"
                                                                    class="flex h-7 w-7 items-center justify-center rounded-md border border-vd-success-line bg-vd-success-bg text-vd-success-ink transition-colors duration-200 hover:border-vd-success-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-success-ink/40">
                                                                    <x-admin.icon name="check" class="h-4 w-4" stroke="2.4" />
                                                                </button>
                                                            </form>
                                                            <button type="button" @click="rejectId = {{ $reg->id }}" title="Reject" aria-label="Reject {{ $reg->name }}"
                                                                class="flex h-7 w-7 items-center justify-center rounded-md border border-vd-danger-line bg-vd-danger-bg text-vd-danger-ink transition-colors duration-200 hover:border-vd-danger-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-danger-ink/40">
                                                                <x-admin.icon name="x" class="h-4 w-4" stroke="2.4" />
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
                                x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                                x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                                class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-vd-ink/50 p-4"
                                role="dialog" aria-modal="true" aria-labelledby="applicant-title-{{ $reg->id }}"
                                @click.self="openId = null" @keydown.escape.window="openId = null">
                                <div class="my-8 w-full max-w-5xl rounded-2xl border border-vd-line bg-vd-paper p-6" @click.stop>
                                    <div class="mb-4 flex items-center justify-between">
                                        <h3 id="applicant-title-{{ $reg->id }}" class="text-lg font-semibold text-vd-ink">Applicant details</h3>
                                        <button type="button" @click="openId = null" aria-label="Close"
                                            class="flex h-8 w-8 items-center justify-center rounded-lg text-vd-muted transition-colors duration-200 hover:bg-white hover:text-vd-plum {{ $focus }}">
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

                    <section class="{{ $card }} p-4" aria-labelledby="cmp-title">
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <h2 id="cmp-title" class="{{ $sectionTitle }}">Recent complaints</h2>
                            <a href="{{ route('admin.complaints.index') }}" x-target.push="main-content sidebar" data-skeleton="table" class="{{ $linkBtn }}">View all</a>
                        </div>

                        @if ($recentComplaints->isEmpty())
                            <x-admin.empty-state size="sm" icon="scale" title="No complaints" text="New complaints and disputes appear here." />
                        @else
                            <div class="thin-scroll -mx-1 overflow-x-auto px-1">
                                <table class="w-full min-w-[400px] text-[13px]">
                                    <thead>
                                        <tr class="border-b border-vd-line text-left text-[13px] text-vd-muted">
                                            <th scope="col" class="py-2.5 font-medium">ID</th>
                                            <th scope="col" class="py-2.5 font-medium">From</th>
                                            <th scope="col" class="py-2.5 font-medium">Against</th>
                                            <th scope="col" class="py-2.5 font-medium">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-vd-line">
                                        @foreach ($recentComplaints as $complaint)
                                            <tr class="transition-colors duration-200 hover:bg-vd-tint">
                                                <td class="py-2 pr-2">
                                                    <a href="{{ route('admin.complaints.show', $complaint) }}" aria-label="Open complaint #{{ $complaint->id }}"
                                                        class="font-medium text-vd-plum hover:underline {{ $focus }}">#{{ $complaint->id }}</a>
                                                </td>
                                                <td class="max-w-[110px] py-2 pr-2 text-vd-ink [overflow-wrap:anywhere]">
                                                    {{ $complaint->complainant->name ?? 'Unknown' }}
                                                    <span class="block text-xs text-vd-muted">{{ ucwords(str_replace('_', ' ', $complaint->complainant->role ?? '')) }}</span>
                                                </td>
                                                <td class="max-w-[110px] py-2 pr-2 text-vd-ink [overflow-wrap:anywhere]">
                                                    {{ $complaint->respondent->name ?? 'Unknown' }}
                                                    <span class="block text-xs text-vd-muted">{{ ucwords(str_replace('_', ' ', $complaint->respondent->role ?? '')) }}</span>
                                                </td>
                                                <td class="py-2">@include('admin.complaints.partials.status-badge')</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </section>
                </div>
            </div>

            {{-- Rail --}}
            <div class="space-y-4">

                <section class="{{ $card }} p-4" aria-labelledby="notif-title">
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <h2 id="notif-title" class="{{ $sectionTitle }}">Notifications</h2>
                        <a href="{{ route('admin.notifications.index') }}" x-target.push="main-content sidebar" data-skeleton="table" class="{{ $linkBtn }}">View all</a>
                    </div>

                    <div class="thin-scroll max-h-[330px] space-y-1 overflow-y-auto">
                        @forelse ($notifications as $notification)
                            <div class="flex items-start gap-3 rounded-xl p-2.5">
                                <img src="{{ asset('assets/icons/dashboard/' . $notification->icon()) }}" alt="" class="mt-0.5 h-6 w-6 flex-shrink-0">
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium leading-snug text-vd-ink">{{ $notification->title }}</p>
                                    <p class="mt-0.5 line-clamp-2 text-xs text-vd-muted">{{ str_replace(['courier', 'Courier'], ['logistics', 'Logistics'], $notification->message) }}</p>
                                </div>
                                <span class="mt-0.5 whitespace-nowrap text-[11px] text-vd-muted">{{ $notification->timeAgo() }}</span>
                            </div>
                        @empty
                            <x-admin.empty-state size="sm" icon="bell" title="No notifications" text="Registrations, complaints and messages appear here." />
                        @endforelse
                    </div>
                </section>

                <section class="{{ $card }} p-4" aria-labelledby="pending-title">
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <h2 id="pending-title" class="{{ $sectionTitle }}">Pending registrations</h2>
                        <a href="{{ route('admin.registrations.index') }}" x-target.push="main-content sidebar" data-skeleton="table" class="{{ $linkBtn }}">View all</a>
                    </div>

                    @php
                        $pendingRows = [
                            ['Sellers', 'sellers-registrations.svg', $pendingRegistrations['sellers'] ?? 0],
                            ['Logistics', 'couriers-registrations.svg', $pendingRegistrations['logistics_centers'] ?? 0],
                            ['Buyers', 'buyers-registrations.svg', $pendingRegistrations['buyers'] ?? 0],
                        ];
                    @endphp

                    @if (collect($pendingRows)->sum(fn ($r) => $r[2]) === 0)
                        <x-admin.empty-state size="sm" tone="green" icon="check-circle" title="All clear" text="No registrations are waiting." />
                    @else
                        <ul class="divide-y divide-vd-line">
                            @foreach ($pendingRows as [$label, $icon, $count])
                                <li class="flex items-center gap-3 py-3 first:pt-0 last:pb-0">
                                    <img src="{{ asset('assets/icons/dashboard/' . $icon) }}" alt="" class="h-6 w-6 flex-shrink-0">
                                    <span class="flex-1 text-sm text-vd-ink">{{ $label }}</span>
                                    <span class="text-sm font-semibold tabular-nums text-vd-ink">{{ $count }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>

                <section class="relative overflow-hidden rounded-2xl border border-vd-plum bg-vd-plum p-4 text-white" aria-labelledby="ann-title">
                    <img src="{{ asset('assets/icons/dashboard/announcement-icons.svg') }}" alt="" class="pointer-events-none absolute bottom-3 right-3 h-14 w-auto opacity-80">
                    <div class="relative pr-12">
                        <h2 id="ann-title" class="mb-2 text-sm font-semibold text-[#E8C874]">Announcement</h2>
                        @if ($announcement)
                            <p class="mb-2 text-base font-semibold leading-snug">{{ $announcement->title }}</p>
                            <p class="mb-5 line-clamp-4 text-sm leading-relaxed text-white/80">{{ $announcement->message }}</p>
                        @else
                            <p class="mb-2 text-base font-semibold leading-snug">No announcement</p>
                            <p class="mb-5 text-sm leading-relaxed text-white/80">Publish one to show it here and across Vendo.</p>
                        @endif
                        <a href="{{ route('admin.platform-settings.index') }}" x-target.push="main-content sidebar" data-skeleton="form"
                            class="inline-flex h-9 items-center rounded-lg bg-white px-3.5 text-sm font-medium text-vd-plum transition-colors duration-200 hover:bg-vd-lavender focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#E8C874]">{{ $announcement ? 'Manage announcement' : 'Create announcement' }}</a>
                    </div>
                </section>

            </div>
        </div>
    </div>

    @vite('resources/js/admin/dashboard.js')
@include('shared.live-revision', ['endpoint' => route('admin.live', 'dashboard'), 'mode' => 'reload'])
</x-admin.layout>
