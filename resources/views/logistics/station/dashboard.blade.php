{{-- Live Station Hub overview. Every card links to the page where the work is done. The Station Hub receives, sorts and assigns riders;
     it never creates or changes a transport trip (the Province Hub does). --}}
@php
    $riders = collect($riders);
    $riderCount = fn (string $s) => $riders->where('status', $s)->count();
    $available = $riderCount('Available');
    $onRoute = $riderCount('On route');
    $offDuty = $riderCount('Off duty');

    $awaitingSorting = $stage['Awaiting sorting'];
    $readyForDelivery = $stage['Ready for delivery'];
    $waitingForTransfer = $stage['Waiting for transfer'];
    $outForDelivery = $stage['Out for delivery'];
    // On hand = parcels physically at this station. Parcels out for delivery are with riders, so they are not counted.
    $onHand = $awaitingSorting + $readyForDelivery + $waitingForTransfer;
    $failedCount = count($failed);

    $openPickup = $riders->sum('pickup');
    $openDelivery = $riders->sum('delivery');
    $statusTone = ['Available' => 'green', 'On route' => 'blue', 'Off duty' => 'gray'];

    // Needs your attention: each item links to the page where it is fixed. Most urgent first.
    $attention = [];
    foreach ($failed as $f) {
        $attention[] = ['red', 'alert', 'Delivery failed: '.$f['code'], 'In '.$f['area'].'. Retry it or return it to the seller.', route('lgp.station.delivery-queue', ['tab' => 'failed']), 'Review'];
    }
    if ($pendingPickups) {
        $attention[] = ['amber', 'package', $pendingPickups.' pickup '.($pendingPickups === 1 ? 'request needs' : 'requests need').' a rider', 'Sellers are waiting. Assign a pickup task to an available rider.', route('lgp.station.pickups'), 'Assign'];
    }
    if ($incoming) {
        $attention[] = ['blue', 'truck', 'Transfer '.$incoming['id'].' is bringing '.$incoming['parcels'].' parcels', 'Scheduled from '.$incoming['from'].' for '.\Carbon\Carbon::parse($incoming['scheduled'])->format('g:i A').'. Scan each parcel when it arrives.', route('lgp.station.incoming'), 'Open'];
    }
    if ($awaitingSorting) {
        $attention[] = ['violet', 'layers', $awaitingSorting.' parcels are waiting to be sorted', 'Received parcels get their next step: local delivery or a transfer to the Province Hub.', route('lgp.station.sorting'), 'Sort'];
    }
    if ($readyForDelivery) {
        $attention[] = ['amber', 'map-pin', $readyForDelivery.' parcels are ready for delivery', $available.' '.($available === 1 ? 'rider is' : 'riders are').' available. Assign the parcels by barangay.', route('lgp.station.delivery-queue'), 'Assign'];
    }

    $headline = [
        ['Parcels on hand', $onHand, 'package', 'blue', route('lgp.station.incoming')],
        ['Pickup requests', $pendingPickups, 'inbox', $pendingPickups ? 'amber' : 'green', route('lgp.station.pickups')],
        ['Available riders', $available, 'bike', 'green', route('lgp.station.tasks')],
        ['Failed deliveries', $failedCount, 'alert', $failedCount ? 'red' : 'green', route('lgp.station.delivery-queue', ['tab' => 'failed'])],
    ];

    // Parcels in the order they move through the station (spec section 6A status chain).
    $flow = [
        ['Incoming transfers', $incoming['parcels'] ?? 0, 'inbox', 'blue', route('lgp.station.incoming'), $incoming ? 'Trip '.$incoming['id'].' is on its way from '.$incoming['from'].'. Receive and scan on arrival.' : 'No transfers are on their way here.'],
        ['Awaiting sorting', $awaitingSorting, 'layers', 'violet', route('lgp.station.sorting'), 'Received and scanned. Each one is sorted to a delivery zone or a transfer.'],
        ['Ready for delivery', $readyForDelivery, 'package', 'amber', route('lgp.station.delivery-queue'), 'Sorted by barangay and waiting for a delivery task.'],
        ['Out for delivery', $outForDelivery, 'truck', 'green', route('lgp.station.delivery-queue', ['tab' => 'out']), 'With riders right now. Watch for failed deliveries.'],
    ];

    $today = [
        ['Received today', $receivedToday, 'blue', route('lgp.station.incoming')],
        ['Delivered today', $deliveredToday, 'ok', route('lgp.station.delivery-queue')],
    ];
    $todayMax = max(1, collect($today)->max(1));

    $availability = [
        ['Available', $available, 'ok'],
        ['On route', $onRoute, ''],
        ['Off duty', $offDuty, 'warn'],
    ];
    $riderMax = max(1, $riders->count());
@endphp
<x-logistics.shell title="Station Dashboard" role="station">
    <div class="lg-page">
        <div class="lg-page-head">
            <div>
                <h1>Station Dashboard</h1>
                <p>{{ $station['name'] }}: {{ number_format($onHand) }} parcels on hand and {{ number_format($outForDelivery) }} out for delivery. {{ $onRoute }} {{ $onRoute === 1 ? 'rider is' : 'riders are' }} on route and {{ $available }} available.</p>
            </div>
            <div class="lg-page-head__actions">
                <a href="{{ route('lgp.station.pickups') }}" class="lg-btn lg-btn--outline"><x-logistics.icon name="inbox" :size="18" /> Pickup requests</a>
                <a href="{{ route('lgp.station.tasks') }}" class="lg-btn"><x-logistics.icon name="bike" :size="18" /> Assign tasks</a>
            </div>
        </div>

        @if (count($attention))
            <section aria-labelledby="attnTitle">
                <h2 class="lg-section-title" id="attnTitle" style="margin-bottom:12px">Needs your attention</h2>
                <ul class="lg-attn">
                    @foreach ($attention as $i => [$t, $icon, $title, $text, $url, $cta])
                        <li class="lg-attn__item lg-rise" style="--i: {{ $i }}">
                            <span class="lg-attn__icon lg-attn__icon--{{ $t }}"><x-logistics.icon :name="$icon" :size="20" /></span>
                            <span class="lg-attn__text">{{ $title }}<small>{{ $text }}</small></span>
                            <a href="{{ $url }}" class="lg-btn lg-btn--outline lg-btn--sm">{{ $cta }}</a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        <section aria-labelledby="stationStats">
            <h2 class="lg-section-title" id="stationStats" style="margin-bottom:12px">Your station</h2>
            <div class="lg-grid lg-grid--4">
                @foreach ($headline as $i => [$label, $value, $icon, $t, $url])
                    <a href="{{ $url }}" class="lg-stat lg-rise" style="--i: {{ $i }}">
                        <span class="lg-stat__icon lg-stat__icon--{{ $t }}"><x-logistics.icon :name="$icon" :size="24" /></span>
                        <div class="lg-stat__body">
                            <p class="lg-stat__label">{{ $label }}</p>
                            <p class="lg-stat__value">{{ number_format($value) }}</p>
                        </div>
                        <x-logistics.icon name="arrow-right" :size="18" class="lg-stat__go" />
                    </a>
                @endforeach
            </div>
        </section>

        <section aria-labelledby="flowTitle">
            <h2 class="lg-section-title" id="flowTitle" style="margin-bottom:12px">Parcels moving through this station</h2>
            <ol class="lg-flow">
                @foreach ($flow as $i => [$label, $value, $icon, $t, $url, $text])
                    <li>
                        <a href="{{ $url }}" class="lg-flow__step lg-rise" style="--i: {{ $i }}">
                            <span class="lg-flow__top">
                                <span class="lg-flow__chip"><x-logistics.icon :name="$icon" :size="22" /></span>
                                <span class="lg-flow__count">{{ number_format($value) }}</span>
                            </span>
                            <h3>{{ $label }}</h3>
                            <p>{{ $text }}</p>
                        </a>
                    </li>
                @endforeach
            </ol>
        </section>

        <div class="lg-grid" style="grid-template-columns:repeat(auto-fit,minmax(320px,1fr))">
            <section class="lg-card" aria-labelledby="todayTitle">
                <div class="lg-card__head">
                    <div>
                        <h2 class="lg-section-title" id="todayTitle">Today at this station</h2>
                        <p class="lg-section-sub">Counted since midnight.</p>
                    </div>
                </div>
                <div style="display:grid;gap:14px">
                    @foreach ($today as [$label, $count, $tone, $url])
                        <div class="lg-meter-row">
                            <div class="lg-meter-row__top"><a href="{{ $url }}" style="color:inherit;text-decoration:none">{{ $label }}</a><strong>{{ $count }}</strong></div>
                            <div class="lg-meter {{ $tone === 'ok' ? 'lg-meter--ok' : ($tone === 'warn' ? 'lg-meter--warn' : '') }}" role="presentation"><span style="--w: {{ round($count / $todayMax * 100) }}%"></span></div>
                        </div>
                    @endforeach
                </div>
                <div class="lg-note" style="margin-top:16px">
                    <x-logistics.icon name="truck" :size="18" />
                    <span>{{ number_format($waitingForTransfer) }} {{ $waitingForTransfer === 1 ? 'parcel is' : 'parcels are' }} waiting for a transfer to {{ $incoming['from'] ?? 'the Province Hub' }}. The Province Hub schedules that trip. See <a href="{{ route('lgp.station.outgoing') }}">Outgoing Transfers</a>.</span>
                </div>
            </section>

            <section class="lg-card" aria-labelledby="availTitle">
                <div class="lg-card__head">
                    <div>
                        <h2 class="lg-section-title" id="availTitle">Rider availability</h2>
                        <p class="lg-section-sub">{{ $riders->count() }} approved riders. Only this station assigns their tasks.</p>
                    </div>
                    <a href="{{ route('lgp.station.riders') }}" class="lg-btn lg-btn--ghost lg-btn--sm">Manage riders</a>
                </div>
                <div style="display:grid;gap:14px">
                    @foreach ($availability as [$label, $count, $tone])
                        <div class="lg-meter-row">
                            <div class="lg-meter-row__top"><span>{{ $label }}</span><strong>{{ $count }}</strong></div>
                            <div class="lg-meter {{ $tone === 'ok' ? 'lg-meter--ok' : ($tone === 'warn' ? 'lg-meter--warn' : '') }}" role="presentation"><span style="--w: {{ round($count / $riderMax * 100) }}%"></span></div>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>

        <section class="lg-card" aria-labelledby="loadTitle">
            <div class="lg-card__head">
                <div>
                    <h2 class="lg-section-title" id="loadTitle">Rider workload</h2>
                    <p class="lg-section-sub">{{ $openPickup }} open pickup {{ $openPickup === 1 ? 'task' : 'tasks' }} and {{ $openDelivery }} open delivery {{ $openDelivery === 1 ? 'task' : 'tasks' }}. Every rider can do both.</p>
                </div>
                <a href="{{ route('lgp.station.tasks') }}" class="lg-btn lg-btn--ghost lg-btn--sm">Assign tasks</a>
            </div>
            <div class="lg-table-wrap">
                <table class="lg-table lg-table--stack lg-table--mid">
                    <thead><tr><th>Rider</th><th>Status</th><th class="is-num">Pickup</th><th class="is-num">Delivery</th><th class="is-num">Total</th><th>Barangays</th></tr></thead>
                    <tbody>
                    @forelse ($riders as $r)
                        <tr>
                            <td data-label="Rider">
                                <div class="lg-person">
                                    <span class="lg-avatar lg-avatar--sm" aria-hidden="true">{{ mb_substr($r['name'], 0, 1) }}</span>
                                    <div><strong>{{ $r['name'] }}</strong><div class="lg-cell-sub">{{ $r['vehicle'] }}</div></div>
                                </div>
                            </td>
                            <td data-label="Status"><span class="lg-pill lg-pill--{{ $statusTone[$r['status']] ?? 'gray' }}">{{ $r['status'] }}</span></td>
                            <td data-label="Pickup" class="is-num">{{ $r['pickup'] }}</td>
                            <td data-label="Delivery" class="is-num">{{ $r['delivery'] }}</td>
                            <td data-label="Total" class="is-num"><strong>{{ $r['pickup'] + $r['delivery'] }}</strong></td>
                            <td data-label="Barangays">{{ $r['barangays'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6">
                            <x-logistics.empty icon="users" title="No riders yet" text="Approve rider applications from Riders. Approved riders appear here with their workload.">
                                <a href="{{ route('lgp.station.riders') }}" class="lg-btn lg-btn--sm">Open riders</a>
                            </x-logistics.empty>
                        </td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-logistics.shell>
