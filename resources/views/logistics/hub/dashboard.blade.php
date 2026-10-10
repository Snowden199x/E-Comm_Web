<x-logistics.shell title="Dashboard" :role="$role">
    <div class="lg-page">
        <div class="lg-page-head">
            <div>
                <h1>{{ $role === 'province' ? 'Province Hub Dashboard' : 'Station Dashboard' }}</h1>
                <p>
                    @if ($role === 'province')
                        Parcels from your stations, and parcels moving to and from other provinces.
                    @else
                        Parcels for Santa Cruz: sort them to riders and track deliveries.
                    @endif
                </p>
            </div>
        </div>

        <div class="lg-grid lg-grid--4">
            @foreach ($stats as $i => [$label, $value, $icon, $tone])
                <div class="lg-stat lg-rise" style="--i: {{ $i }}">
                    <span class="lg-stat__icon lg-stat__icon--{{ $tone }}"><x-logistics.icon :name="$icon" :size="24" /></span>
                    <div>
                        <p class="lg-stat__label">{{ $label }}</p>
                        <p class="lg-stat__value">{{ $value }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        @if ($role === 'province')
            <section class="lg-card" aria-labelledby="stTitle">
                <div class="lg-card__head"><h2 class="lg-section-title" id="stTitle">Stations under this hub</h2></div>
                <div class="lg-table-wrap">
                    <table class="lg-table">
                        <thead><tr><th>Station</th><th class="is-num">Parcels waiting</th><th class="is-num">Riders</th><th>Status</th></tr></thead>
                        <tbody>
                        @foreach ($stations as $s)
                            <tr>
                                <td>{{ $s['name'] }}</td>
                                <td class="is-num">{{ $s['waiting'] }}</td>
                                <td class="is-num">{{ $s['riders'] }}</td>
                                <td><span class="lg-pill lg-pill--{{ $s['status'] === 'Active' ? 'green' : 'amber' }}">{{ $s['status'] }}</span></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        <div class="lg-grid" style="grid-template-columns:repeat(auto-fit,minmax(320px,1fr))">
            <section class="lg-card" aria-labelledby="pTitle">
                <div class="lg-card__head"><h2 class="lg-section-title" id="pTitle">Parcels needing action</h2></div>
                <div class="lg-table-wrap">
                    <table class="lg-table">
                        <thead><tr><th>Parcel</th><th>Area</th><th>Status</th></tr></thead>
                        <tbody>
                        @foreach ($parcels as $p)
                            <tr>
                                <td><div class="lg-cell-title">{{ $p['code'] }}</div><div class="lg-cell-sub">{{ $p['route'] }}</div></td>
                                <td>{{ $p['area'] }}</td>
                                <td><span class="lg-pill lg-pill--{{ $p['tone'] }}">{{ $p['status'] }}</span></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="lg-card" aria-labelledby="rTitle">
                <div class="lg-card__head"><h2 class="lg-section-title" id="rTitle">Riders on duty</h2></div>
                <div class="lg-table-wrap">
                    <table class="lg-table">
                        <thead><tr><th>Rider</th><th>Vehicle</th><th class="is-num">Parcels</th></tr></thead>
                        <tbody>
                        @foreach ($riders as $r)
                            <tr>
                                <td><div class="lg-person"><span class="lg-avatar lg-avatar--sm" aria-hidden="true">{{ mb_substr($r['name'], 0, 1) }}</span><strong>{{ $r['name'] }}</strong></div></td>
                                <td>{{ $r['vehicle'] }}</td>
                                <td class="is-num">{{ $r['parcels'] }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</x-logistics.shell>