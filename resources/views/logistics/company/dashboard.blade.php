@php
    $hubCount = count($hubs);
    $setup = collect($hubs)->where('status', 'Setup');
    $maxParcels = max(1, collect($hubs)->max('parcels') ?? 1);
    $statLinks = [route('lgp.company.hubs'), route('lgp.company.hubs'), route('lgp.company.reports'), route('lgp.company.reports')];

    // HARDCODED PREVIEW: replace with real setup/invite checks for this company.
    $attention = [];
    foreach ($setup as $h) {
        $attention[] = ['amber', 'alert', $h['name'].' is still in setup', $h['manager'] === 'Pending invite' ? 'The manager has not accepted the invite yet.' : 'It has no riders yet.', route('lgp.company.managers'), 'Open managers'];
    }
@endphp
<x-logistics.shell title="Company Dashboard" role="company">
    <div class="lg-page">
        <div class="lg-page-head">
            <div>
                <h1>Company Dashboard</h1>
                <p>Your whole delivery network across every province and municipality.</p>
            </div>
            <div class="lg-page-head__actions">
                <a href="{{ route('lgp.company.hubs') }}" class="lg-btn"><x-logistics.icon name="map-pin" :size="18" /> Manage hubs</a>
            </div>
        </div>

        @if (count($attention))
            <section aria-labelledby="attnTitle">
                <h2 class="lg-section-title" id="attnTitle" style="margin-bottom:12px">Needs your attention</h2>
                <ul class="lg-attn">
                    @foreach ($attention as $i => [$tone, $icon, $title, $text, $url, $cta])
                        <li class="lg-attn__item lg-rise" style="--i: {{ $i }}">
                            <span class="lg-attn__icon lg-attn__icon--{{ $tone }}"><x-logistics.icon :name="$icon" :size="20" /></span>
                            <span class="lg-attn__text">{{ $title }}<small>{{ $text }}</small></span>
                            <a href="{{ $url }}" class="lg-btn lg-btn--outline lg-btn--sm">{{ $cta }}</a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        <div class="lg-grid lg-grid--4">
            @foreach ($stats as $i => [$label, $value, $icon, $tone])
                <a href="{{ $statLinks[$i] }}" class="lg-stat lg-rise" style="--i: {{ $i }}">
                    <span class="lg-stat__icon lg-stat__icon--{{ $tone }}"><x-logistics.icon :name="$icon" :size="24" /></span>
                    <div class="lg-stat__body">
                        <p class="lg-stat__label">{{ $label }}</p>
                        <p class="lg-stat__value">{{ $value }}</p>
                    </div>
                    <x-logistics.icon name="arrow-right" :size="18" class="lg-stat__go" />
                </a>
            @endforeach
        </div>

        <section class="lg-card" aria-labelledby="netTitle">
            <div class="lg-card__head">
                <div>
                    <h2 class="lg-section-title" id="netTitle">Hub network</h2>
                    <p class="lg-section-sub">Province hubs, with the stations that report to them indented below.</p>
                </div>
                <a href="{{ route('lgp.company.hubs') }}" class="lg-btn lg-btn--ghost lg-btn--sm">See all hubs</a>
            </div>
            <div class="lg-table-wrap">
                <table class="lg-table lg-table--stack lg-table--mid">
                    <thead><tr><th>Hub</th><th>Type</th><th>Manager</th><th class="is-num">Riders</th><th>Parcels now</th><th>Status</th></tr></thead>
                    <tbody>
                    @forelse ($hubs as $hub)
                        @php $isStation = $hub['type'] === 'Station'; @endphp
                        <tr @class(['is-parent' => ! $isStation])>
                            <td data-label="Hub">
                                <div @class(['lg-tree-child' => $isStation])>
                                    <div class="lg-cell-title">{{ $hub['name'] }}</div>
                                    <div class="lg-cell-sub">{{ $hub['place'] }}</div>
                                </div>
                            </td>
                            <td data-label="Type"><span class="lg-pill lg-pill--{{ $isStation ? 'teal' : 'violet' }}">{{ $hub['type'] }}</span></td>
                            <td data-label="Manager">
                                @if ($hub['manager'] === 'Pending invite')
                                    <span class="lg-pill lg-pill--amber">Pending invite</span>
                                @else
                                    {{ $hub['manager'] }}
                                @endif
                            </td>
                            <td data-label="Riders" class="is-num">{{ $hub['riders'] }}</td>
                            <td data-label="Parcels now" style="min-width:150px">
                                <div class="lg-meter-row">
                                    <div class="lg-meter-row__top"><span class="sr-only">Parcels now</span><strong>{{ number_format($hub['parcels']) }}</strong></div>
                                    <div class="lg-meter" role="presentation"><span style="--w: {{ round($hub['parcels'] / $maxParcels * 100) }}%"></span></div>
                                </div>
                            </td>
                            <td data-label="Status"><span class="lg-pill lg-pill--{{ $hub['status'] === 'Active' ? 'green' : 'amber' }}">{{ $hub['status'] }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6">
                            <x-logistics.empty icon="map-pin" title="No hubs yet" text="Add your first province hub, then the stations that deliver to buyers in each municipality.">
                                <a href="{{ route('lgp.company.hubs') }}" class="lg-btn lg-btn--sm">Add a hub</a>
                            </x-logistics.empty>
                        </td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-logistics.shell>
