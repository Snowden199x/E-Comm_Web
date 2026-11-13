{{--
    Station Hub Dashboard. Read-only overview, fed by PortalController::stationDashboard (nothing about the data changed).
    The Station Hub receives, sorts and assigns riders; it never creates or changes a transport trip (the Province Hub does).
    Look: the seller pastel lilac system, the same .lgd layer as the Company and Province Hub dashboards. Styles:
    resources/css/logistics/dashboard-theme.css, province-dashboard-theme.css (tiles, flow and meters) and station-dashboard-theme.css (the note row), loaded last.
    Behavior: resources/js/logistics/dashboard.js (one click on a row opens its page, spinner on buttons that open another page).
    Status colors: new = lilac, waiting = butter, in progress = sky, done = mint, failed = rose.
    Loading: shell skeleton (boot and page-to-page) plus a button spinner. Errors: top banner (<x-logistics.flash />) for messages sent
    back from other pages. Empty: nothing needs attention, no riders. No search or filters.
--}}
@php
    use Carbon\Carbon;
    use Illuminate\Support\Str;

    $riders = collect($riders);
    $available = $riders->where('status', 'Available')->count();
    $offDuty = $riders->where('status', 'Off duty')->count();
    // The status only says Available or Off duty, so "busy" comes from the open tasks each rider holds.
    $withTasks = $riders->filter(fn ($r) => ($r['pickup'] + $r['delivery']) > 0)->count();
    $openPickup = $riders->sum('pickup');
    $openDelivery = $riders->sum('delivery');
    $statusTone = ['Available' => 'mint', 'Off duty' => 'lilac'];

    $awaitingSorting = $stage['Awaiting sorting'];
    $readyForDelivery = $stage['Ready for delivery'];
    $waitingForTransfer = $stage['Waiting for transfer'];
    $outForDelivery = $stage['Out for delivery'];
    // On hand = parcels physically at this station. Parcels out for delivery are with riders, so they are not counted.
    $onHand = $awaitingSorting + $readyForDelivery + $waitingForTransfer;
    // The real total (the list in $failed holds only the latest 10, to name a few of them).
    $failedCount = $failedTotal ?? count($failed);
    $incomingParcels = $incoming['parcels'] ?? 0;

    // Needs attention: each item names the page where it is fixed. Worst first.
    $attention = [];
    if ($failedCount === 1 && count($failed) >= 1) {
        $attention[] = ['rank' => 0, 'tone' => 'rose', 'icon' => 'alert', 'title' => 'Delivery failed: '.$failed[0]['code'],
            'text' => 'In '.$failed[0]['area'].'. Retry it or return it to the seller.',
            'url' => route('lgp.station.delivery-queue', ['tab' => 'failed']), 'cta' => 'Review delivery'];
    } elseif ($failedCount >= 1) {
        $codes = collect($failed)->pluck('code')->take(3)->join(', ');
        $attention[] = ['rank' => 0, 'tone' => 'rose', 'icon' => 'alert', 'title' => $failedCount.' deliveries failed',
            'text' => $codes.($failedCount > 3 ? ' and '.($failedCount - 3).' more' : '').'. Retry them or return them to the seller.',
            'url' => route('lgp.station.delivery-queue', ['tab' => 'failed']), 'cta' => 'Review deliveries'];
    }
    if ($pendingPickups) {
        $attention[] = ['rank' => 1, 'tone' => 'butter', 'icon' => 'package',
            'title' => $pendingPickups.' pickup '.Str::plural('request', $pendingPickups).' '.($pendingPickups === 1 ? 'needs' : 'need').' a rider',
            'text' => 'Sellers are waiting. Assign a pickup task to an available rider.',
            'url' => route('lgp.station.pickups'), 'cta' => 'Assign rider'];
    }
    if ($incoming) {
        $attention[] = ['rank' => 1, 'tone' => 'sky', 'icon' => 'truck',
            'title' => 'Transfer '.$incoming['id'].' is bringing '.$incoming['parcels'].' '.Str::plural('parcel', $incoming['parcels']),
            'text' => 'Scheduled from '.$incoming['from'].' for '.Carbon::parse($incoming['scheduled'])->format('g:i A').'. Scan each parcel when it arrives.',
            'url' => route('lgp.station.incoming'), 'cta' => 'View transfer'];
    }
    if ($awaitingSorting) {
        $attention[] = ['rank' => 2, 'tone' => 'lilac', 'icon' => 'layers',
            'title' => $awaitingSorting.' '.Str::plural('parcel', $awaitingSorting).' '.($awaitingSorting === 1 ? 'is' : 'are').' waiting to be sorted',
            'text' => 'Give each received parcel its next step: local delivery or a transfer to the Province Hub.',
            'url' => route('lgp.station.sorting'), 'cta' => 'Sort parcels'];
    }
    if ($readyForDelivery) {
        $attention[] = ['rank' => 2, 'tone' => 'butter', 'icon' => 'map-pin',
            'title' => $readyForDelivery.' '.Str::plural('parcel', $readyForDelivery).' '.($readyForDelivery === 1 ? 'is' : 'are').' ready for delivery',
            'text' => $available.' '.($available === 1 ? 'rider is' : 'riders are').' available. Assign the parcels by barangay.',
            'url' => route('lgp.station.delivery-queue'), 'cta' => 'Assign parcels'];
    }
    $attention = collect($attention)->sortBy('rank')->values();
    $attentionShown = 3;

    // [label, value, icon, url, tone]
    $tiles = [
        ['Parcels on hand', number_format($onHand), 'package', route('lgp.station.tracking'), 'lilac'],
        ['Pickup requests', number_format($pendingPickups), 'inbox', route('lgp.station.pickups'), $pendingPickups ? 'butter' : 'lilac'],
        ['Available riders', number_format($available), 'bike', route('lgp.station.tasks'), 'mint'],
        ['Failed deliveries', number_format($failedCount), 'alert', route('lgp.station.delivery-queue', ['tab' => 'failed']), $failedCount ? 'rose' : 'lilac'],
    ];

    // Parcels, in the order they move through this station: [label, count, icon, tone, url, hint]
    $flow = [
        ['Incoming transfers', $incomingParcels, 'inbox', 'sky', route('lgp.station.incoming'),
            $incoming ? 'Trip '.$incoming['id'].' is on its way from '.$incoming['from'].'. Scan each parcel on arrival.' : 'No transfers are on their way here.'],
        ['Awaiting sorting', $awaitingSorting, 'layers', 'lilac', route('lgp.station.sorting'), 'Received and scanned. Sort each one to a delivery zone or a transfer.'],
        ['Ready for delivery', $readyForDelivery, 'package', 'butter', route('lgp.station.delivery-queue'), 'Sorted by barangay and waiting for a delivery task.'],
        ['Out for delivery', $outForDelivery, 'truck', 'sky', route('lgp.station.delivery-queue', ['tab' => 'out']), 'With riders right now. Watch for failed deliveries.'],
    ];

    // Counted since midnight: [label, count, tone, url]
    $today = [
        ['Received today', $receivedToday, 'sky', route('lgp.station.incoming')],
        ['Delivered today', $deliveredToday, 'mint', route('lgp.station.delivery-queue')],
    ];
    $todayMax = max(1, collect($today)->max(1));
    $todayAny = ($receivedToday + $deliveredToday) > 0;

    // Rider availability: [label, count, tone]
    $availability = [
        ['Available', $available, 'mint'],
        ['With open tasks', $withTasks, 'sky'],
        ['Off duty', $offDuty, 'lilac'],
    ];
    $riderMax = max(1, $riders->count());
@endphp

@push('lg-head')
    @vite(['resources/css/logistics/dashboard-theme.css', 'resources/css/logistics/province-dashboard-theme.css', 'resources/css/logistics/station-dashboard-theme.css', 'resources/js/logistics/dashboard.js'])
@endpush

<x-logistics.shell title="Station Dashboard" role="station">
    <div class="lgd" data-lgd>

        <x-logistics.flash />

        <header class="lgd-head">
            <div>
                <h1>Dashboard</h1>
                <p>{{ $station['name'] }} · {{ now()->format('l, F j') }}</p>
            </div>
            <div class="lgd-head__actions">
                <a href="{{ route('lgp.station.tasks') }}" class="lgd-btn lgd-btn--primary">
                    <x-logistics.icon name="bike" :size="16" /> Assign tasks
                </a>
                <a href="{{ route('lgp.station.pickups') }}" class="lgd-btn lgd-btn--soft">
                    <x-logistics.icon name="inbox" :size="16" /> View pickup requests
                </a>
                <a href="{{ route('lgp.station.trips') }}" class="lgd-btn lgd-btn--soft">
                    <x-logistics.icon name="truck" :size="16" /> Assign driver
                </a>
            </div>
        </header>

        {{-- Your station --}}
        <section class="lgd-tiles lgd-tiles--4" aria-label="Your station">
            @foreach ($tiles as [$label, $value, $icon, $url, $tone])
                <a href="{{ $url }}" class="lgd-tile @if ($tone === 'rose') is-problem @endif" aria-label="{{ $label }}: {{ $value }}. View details">
                    <span class="lgd-tile__top">
                        <span class="lgd-tile__icon lgd-tone-{{ $tone }}"><x-logistics.icon :name="$icon" :size="22" /></span>
                        <span class="lgd-tile__go"><x-logistics.icon name="arrow-right" :size="16" /></span>
                    </span>
                    <span class="lgd-tile__label">{{ $label }}</span>
                    <strong class="lgd-tile__value">{{ $value }}</strong>
                </a>
            @endforeach
        </section>

        {{-- Needs attention --}}
        <section class="lgd-card" aria-labelledby="attn-title" x-data="{ all: false }">
            <div class="lgd-card__head">
                <h2 id="attn-title">Needs attention</h2>
                @if ($attention->isNotEmpty())
                    <span class="lgd-count">{{ $attention->count() }}</span>
                @endif
            </div>

            @if ($attention->isEmpty())
                <div class="lgd-empty" role="status">
                    <span class="lgd-empty__icon lgd-tone-mint"><x-logistics.icon name="check-circle" :size="26" /></span>
                    <strong>Nothing needs attention</strong>
                    <p>No failed deliveries, waiting parcels or pickup requests.</p>
                </div>
            @else
                <ul class="lgd-list">
                    @foreach ($attention as $i => $item)
                        <li class="lgd-item lgd-row" data-href="{{ $item['url'] }}" @if ($i >= $attentionShown) x-show="all" x-cloak @endif>
                            <span class="lgd-item__icon lgd-tone-{{ $item['tone'] }}"><x-logistics.icon :name="$item['icon']" :size="18" /></span>
                            <div class="lgd-item__body">
                                <strong>{{ $item['title'] }}</strong>
                                <p>{{ $item['text'] }}</p>
                            </div>
                            <a href="{{ $item['url'] }}" class="lgd-btn lgd-btn--soft lgd-btn--small">
                                {{ $item['cta'] }} <x-logistics.icon name="arrow-right" :size="14" class="lgd-nudge" />
                            </a>
                        </li>
                    @endforeach
                </ul>

                @if ($attention->count() > $attentionShown)
                    <div class="lgd-more">
                        <button type="button" @click="all = !all" :aria-expanded="all" class="lgd-btn lgd-btn--soft lgd-btn--small"
                                x-text="all ? 'Show fewer' : 'Show {{ $attention->count() - $attentionShown }} more'">Show {{ $attention->count() - $attentionShown }} more</button>
                    </div>
                @endif
            @endif
        </section>

        {{-- Parcels moving through this station --}}
        <section class="lgd-card" aria-labelledby="flow-title">
            <div class="lgd-card__head">
                <div class="lgd-card__title">
                    <h2 id="flow-title">Parcels moving through this station</h2>
                    <p class="lgd-card__sub">In the order they pass through.</p>
                </div>
            </div>
            <ol class="lgd-tiles lgd-tiles--4" style="list-style:none;margin:0;padding:0">
                @foreach ($flow as [$label, $value, $icon, $tone, $url, $hint])
                    <li style="display:grid">
                        <a href="{{ $url }}" class="lgd-tile" aria-label="{{ $label }}: {{ $value }}. View details">
                            <span class="lgd-tile__top">
                                <span class="lgd-tile__icon lgd-tone-{{ $tone }}"><x-logistics.icon :name="$icon" :size="22" /></span>
                                <span class="lgd-tile__go"><x-logistics.icon name="arrow-right" :size="16" /></span>
                            </span>
                            <span class="lgd-tile__label">{{ $label }}</span>
                            <strong class="lgd-tile__value">{{ number_format($value) }}</strong>
                            <span class="lgd-tile__hint">{{ $hint }}</span>
                        </a>
                    </li>
                @endforeach
            </ol>
        </section>

        {{-- Today, and rider availability --}}
        <div class="lgd-pair">
            <section class="lgd-card" aria-labelledby="today-title">
                <div class="lgd-card__head lgd-card__head--bar">
                    <div class="lgd-card__title">
                        <h2 id="today-title">Today at this station</h2>
                        <p class="lgd-card__sub">Counted since midnight.</p>
                    </div>
                </div>
                @if ($todayAny)
                    <div class="lgd-meters">
                        @foreach ($today as [$label, $count, $tone, $url])
                            <div>
                                <div class="lgd-meter__top"><a href="{{ $url }}" style="color:inherit;text-decoration:none"><i class="lgd-meter__dot lgd-meter__dot--{{ $tone }}"></i> {{ $label }}</a><strong>{{ number_format($count) }}</strong></div>
                                <span class="lgd-bar lgd-bar--{{ $tone }}" role="img" aria-label="{{ $label }}: {{ $count }}"><i style="--w: {{ round($count / $todayMax * 100) }}%"></i></span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="lgd-empty" role="status">
                        <span class="lgd-empty__icon lgd-tone-lilac"><x-logistics.icon name="calendar" :size="26" /></span>
                        <strong>No parcels received or delivered yet today</strong>
                        <p>Scan a parcel on arrival or deliver one, and it is counted here.</p>
                        <a href="{{ route('lgp.station.incoming') }}" class="lgd-btn lgd-btn--soft lgd-btn--small">View incoming parcels</a>
                    </div>
                @endif
                <div class="lgd-note" role="status">
                    <x-logistics.icon name="truck" :size="18" />
                    <span>{{ number_format($waitingForTransfer) }} {{ $waitingForTransfer === 1 ? 'parcel is' : 'parcels are' }} waiting for a transfer. Assign one of your drivers and the hub they must reach, or see outgoing transfers.</span>
                    <a href="{{ route('lgp.station.trips') }}" class="lgd-btn lgd-btn--soft lgd-btn--small">Assign driver</a>
                </div>
            </section>

            <section class="lgd-card" aria-labelledby="avail-title">
                <div class="lgd-card__head lgd-card__head--bar">
                    <div class="lgd-card__title">
                        <h2 id="avail-title">Rider availability</h2>
                        <p class="lgd-card__sub">{{ $riders->count() }} approved {{ Str::plural('rider', $riders->count()) }}. Only this station assigns their tasks.</p>
                    </div>
                    <a href="{{ route('lgp.station.riders') }}" class="lgd-btn lgd-btn--soft lgd-btn--small">Manage riders</a>
                </div>
                @if ($riders->count())
                    <div class="lgd-meters">
                        @foreach ($availability as [$label, $count, $tone])
                            <div>
                                <div class="lgd-meter__top"><span><i class="lgd-meter__dot lgd-meter__dot--{{ $tone }}"></i>{{ $label }}</span><strong>{{ $count }}</strong></div>
                                <span class="lgd-bar lgd-bar--{{ $tone }}" role="img" aria-label="{{ $count }} of {{ $riderMax }} riders: {{ Str::lower($label) }}"><i style="--w: {{ round($count / $riderMax * 100) }}%"></i></span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="lgd-empty" role="status">
                        <span class="lgd-empty__icon lgd-tone-lilac"><x-logistics.icon name="users" :size="26" /></span>
                        <strong>No riders yet</strong>
                        <p>Riders apply in the Rider app. Approve them so they can take tasks.</p>
                        <a href="{{ route('lgp.station.riders') }}" class="lgd-btn lgd-btn--soft lgd-btn--small">Open riders</a>
                    </div>
                @endif
            </section>
        </div>

        {{-- Rider workload --}}
        <section class="lgd-card lgd-table-card" aria-labelledby="load-title">
            <div class="lgd-card__head lgd-card__head--bar">
                <div class="lgd-card__title">
                    <h2 id="load-title">Rider workload</h2>
                    <p class="lgd-card__sub">{{ $openPickup }} open pickup {{ Str::plural('task', $openPickup) }} and {{ $openDelivery }} open delivery {{ Str::plural('task', $openDelivery) }}. Every rider can do both.</p>
                </div>
                <a href="{{ route('lgp.station.tasks') }}" class="lgd-btn lgd-btn--soft lgd-btn--small">Assign tasks</a>
            </div>
            <div class="lgd-table-wrap">
                <table class="lgd-table lgd-table--wide">
                    <thead>
                        <tr>
                            <th scope="col">Rider</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="is-num">Pickup tasks</th>
                            <th scope="col" class="is-num">Delivery tasks</th>
                            <th scope="col">Barangays</th>
                            <th scope="col"><span class="lgd-sr">Action</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($riders as $r)
                            <tr class="lgd-row" data-href="{{ route('lgp.station.tasks') }}">
                                <td><span class="lgd-name">{{ $r['name'] }}</span><small>{{ $r['vehicle'] ?: 'Vehicle not set' }}</small></td>
                                <td data-label="Status"><span class="lgd-pill lgd-pill--{{ $statusTone[$r['status']] ?? 'lilac' }}">{{ $r['status'] }}</span></td>
                                <td class="is-num" data-label="Pickup tasks">{{ $r['pickup'] }}</td>
                                <td class="is-num" data-label="Delivery tasks">{{ $r['delivery'] }}</td>
                                <td data-label="Barangays">{{ $r['barangays'] }}</td>
                                <td class="lgd-actions">
                                    <a href="{{ route('lgp.station.tasks') }}" aria-label="Assign tasks to {{ $r['name'] }}" class="lgd-btn lgd-btn--soft lgd-btn--small">
                                        Assign tasks <x-logistics.icon name="arrow-right" :size="14" class="lgd-nudge" />
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <div class="lgd-empty" role="status">
                                        <span class="lgd-empty__icon lgd-tone-lilac"><x-logistics.icon name="users" :size="26" /></span>
                                        <strong>No riders yet</strong>
                                        <p>Approve rider applications. Approved riders show here with their workload.</p>
                                        <a href="{{ route('lgp.station.riders') }}" class="lgd-btn lgd-btn--primary lgd-btn--small">Open riders</a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-logistics.shell>
