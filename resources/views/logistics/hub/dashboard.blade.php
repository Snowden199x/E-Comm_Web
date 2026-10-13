@php
    $isProvince = $role === 'province';
    $hubUrl = fn (string $p) => route('lgp.hub.page', [$role, $p]);

    // Where each stat card leads and what it means (matched by label, in the order the route passes them).
    $steps = $isProvince ? [
        ['url' => $hubUrl('incoming'), 'text' => 'Parcels your stations passed up to this hub.'],
        ['url' => route('lgp.hub.linehaul'), 'text' => 'Sorted and ready for a truck to another hub.'],
        ['url' => route('lgp.hub.linehaul'), 'text' => 'Trucks on their way here. Receive them on arrival.'],
        ['url' => route('lgp.hub.linehaul'), 'text' => 'Sent to other hubs today.'],
    ] : [
        ['url' => $hubUrl('incoming'), 'text' => 'Verify seller pickups and receive parcels from the province hub.'],
        ['url' => $hubUrl('sorting'), 'text' => 'Group parcels into delivery zones so each rider gets one area.'],
        ['url' => $hubUrl('monitoring'), 'text' => 'With riders right now. Watch for failed deliveries.'],
        ['url' => $hubUrl('monitoring'), 'text' => 'Completed by riders today.'],
    ];

    // HARDCODED PREVIEW: replace with counts from the hub's own queue.
    $attention = $isProvince ? [
        ['amber', 'truck', '58 parcels are waiting for linehaul', 'Assign a truck rider and dispatch them.', route('lgp.hub.linehaul'), 'Dispatch'],
        ['blue', 'clock', 'Truck from Quezon Hub arrives in 25 min', '42 parcels to receive and sort.', route('lgp.hub.linehaul'), 'Open'],
        ['amber', 'alert', 'Los Banos Station is still in setup', 'It has no riders yet.', null, null],
    ] : [
        ['red', 'alert', '1 delivery failed', 'VND-10451 in Brgy. Patimbao. Retry it or return it to the seller.', $hubUrl('monitoring'), 'Review'],
        ['amber', 'inbox', '3 pickup requests need verification', 'Sellers are waiting for a rider.', $hubUrl('incoming'), 'Verify'],
        ['violet', 'layers', '23 parcels are waiting to be sorted', 'Group them by zone before assigning riders.', $hubUrl('sorting'), 'Sort'],
    ];

    $maxWaiting = max(1, collect($stations)->max('waiting') ?? 1);
@endphp
<x-logistics.shell title="Dashboard" :role="$role">
    <div class="lg-page">
        <div class="lg-page-head">
            <div>
                <h1>{{ $isProvince ? 'Province Hub Dashboard' : 'Station Dashboard' }}</h1>
                <p>
                    @if ($isProvince)
                        Parcels from your stations, and parcels moving to and from other provinces.
                    @else
                        Parcels for Santa Cruz: sort them to riders and track deliveries.
                    @endif
                </p>
            </div>
            <div class="lg-page-head__actions">
                @if ($isProvince)
                    <a href="{{ route('lgp.hub.linehaul') }}" class="lg-btn"><x-logistics.icon name="truck" :size="18" /> Open linehaul</a>
                @else
                    <a href="{{ $hubUrl('incoming') }}" class="lg-btn"><x-logistics.icon name="inbox" :size="18" /> Verify pickups</a>
                @endif
            </div>
        </div>

        {{-- 1. What needs me now --}}
        <section aria-labelledby="attnTitle">
            <h2 class="lg-section-title" id="attnTitle" style="margin-bottom:12px">Needs your attention</h2>
            <ul class="lg-attn">
                @foreach ($attention as $i => [$tone, $icon, $title, $text, $url, $cta])
                    <li class="lg-attn__item lg-rise" style="--i: {{ $i }}">
                        <span class="lg-attn__icon lg-attn__icon--{{ $tone }}"><x-logistics.icon :name="$icon" :size="20" /></span>
                        <span class="lg-attn__text">{{ $title }}<small>{{ $text }}</small></span>
                        @if ($url)<a href="{{ $url }}" class="lg-btn lg-btn--outline lg-btn--sm">{{ $cta }}</a>@endif
                    </li>
                @endforeach
            </ul>
        </section>

        {{-- 2. Where parcels are right now (each card opens the page where you work on them) --}}
        <section aria-labelledby="flowTitle">
            <h2 class="lg-section-title" id="flowTitle" style="margin-bottom:12px">Parcels at this {{ $isProvince ? 'hub' : 'station' }}</h2>
            <ol class="lg-flow">
                @foreach ($stats as $i => [$label, $value, $icon, $tone])
                    <li>
                        <a href="{{ $steps[$i]['url'] }}" class="lg-flow__step lg-rise" style="--i: {{ $i }}">
                            <span class="lg-flow__top">
                                <span class="lg-flow__chip"><x-logistics.icon :name="$icon" :size="22" /></span>
                                <span class="lg-flow__count">{{ $value }}</span>
                            </span>
                            <h3>{{ $label }}</h3>
                            <p>{{ $steps[$i]['text'] }}</p>
                        </a>
                    </li>
                @endforeach
            </ol>
        </section>

        @if ($isProvince)
            <section class="lg-card" aria-labelledby="stTitle">
                <div class="lg-card__head">
                    <div>
                        <h2 class="lg-section-title" id="stTitle">Stations under this hub</h2>
                        <p class="lg-section-sub">Parcels each station has waiting for you.</p>
                    </div>
                </div>
                <div class="lg-table-wrap">
                    <table class="lg-table lg-table--stack lg-table--mid">
                        <thead><tr><th>Station</th><th>Waiting</th><th class="is-num">Riders</th><th>Status</th></tr></thead>
                        <tbody>
                        @forelse ($stations as $s)
                            <tr>
                                <td data-label="Station"><span class="lg-cell-title">{{ $s['name'] }}</span></td>
                                <td data-label="Waiting" style="min-width:180px">
                                    <div class="lg-meter-row">
                                        <div class="lg-meter-row__top"><span class="sr-only">Parcels waiting</span><strong>{{ $s['waiting'] }} parcels</strong></div>
                                        <div class="lg-meter" role="presentation"><span style="--w: {{ round($s['waiting'] / $maxWaiting * 100) }}%"></span></div>
                                    </div>
                                </td>
                                <td data-label="Riders" class="is-num">{{ $s['riders'] }}</td>
                                <td data-label="Status"><span class="lg-pill lg-pill--{{ $s['status'] === 'Active' ? 'green' : 'amber' }}">{{ $s['status'] }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="4"><x-logistics.empty icon="map-pin" title="No stations yet" text="Your company adds stations from Hubs. They appear here once they are set up." /></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        <div class="lg-grid" style="grid-template-columns:repeat(auto-fit,minmax(320px,1fr))">
            <section class="lg-card" aria-labelledby="pTitle">
                <div class="lg-card__head">
                    <h2 class="lg-section-title" id="pTitle">Parcels needing action</h2>
                </div>
                <div class="lg-table-wrap">
                    <table class="lg-table lg-table--stack lg-table--mid">
                        <thead><tr><th>Parcel</th><th>Area</th><th>Status</th></tr></thead>
                        <tbody>
                        @forelse ($parcels as $p)
                            @php
                                $go = match ($p['status']) {
                                    'Needs sorting', 'Pass to station' => $hubUrl('sorting'),
                                    'Assign rider' => $isProvince ? $hubUrl('sorting') : $hubUrl('assignments'),
                                    'Verify pickup' => $hubUrl('incoming'),
                                    'Ready for linehaul' => route('lgp.hub.linehaul'),
                                    default => $hubUrl('monitoring'),
                                };
                            @endphp
                            <tr>
                                <td data-label="Parcel"><a href="{{ $go }}" class="lg-cell-title" style="text-decoration:none">{{ $p['code'] }}</a><div class="lg-cell-sub">{{ $p['route'] }}</div></td>
                                <td data-label="Area">{{ $p['area'] }}</td>
                                <td data-label="Status"><span class="lg-pill lg-pill--{{ $p['tone'] }}">{{ $p['status'] }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="3"><x-logistics.empty icon="check-circle" title="Nothing needs action" text="New parcels that need a decision will show up here." /></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="lg-card" aria-labelledby="rTitle">
                <div class="lg-card__head">
                    <h2 class="lg-section-title" id="rTitle">{{ $isProvince ? 'Truck riders on duty' : 'Riders on duty' }}</h2>
                    <a href="{{ $hubUrl('riders') }}" class="lg-btn lg-btn--ghost lg-btn--sm">Manage riders</a>
                </div>
                <div class="lg-table-wrap">
                    <table class="lg-table lg-table--stack lg-table--mid">
                        <thead><tr><th>Rider</th><th>Vehicle</th><th class="is-num">Parcels</th></tr></thead>
                        <tbody>
                        @forelse ($riders as $r)
                            <tr>
                                <td data-label="Rider"><div class="lg-person"><span class="lg-avatar lg-avatar--sm" aria-hidden="true">{{ mb_substr($r['name'], 0, 1) }}</span><strong>{{ $r['name'] }}</strong></div></td>
                                <td data-label="Vehicle">{{ $r['vehicle'] }}</td>
                                <td data-label="Parcels" class="is-num">{{ $r['parcels'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3"><x-logistics.empty icon="users" title="No riders on duty" text="Approved riders appear here when they start their shift." /></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</x-logistics.shell>
