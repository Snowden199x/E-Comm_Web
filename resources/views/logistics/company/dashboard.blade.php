<x-logistics.shell title="Company Dashboard" role="company">
    <div class="lg-page">
        <div class="lg-page-head">
            <div>
                <h1>Company Dashboard</h1>
                <p>Your whole delivery network across every province and municipality.</p>
            </div>
            <div class="lg-page-head__actions">
                <a href="{{ route('lgp.company.hubs') }}" class="lg-btn">Manage hubs</a>
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

        <section class="lg-card" aria-labelledby="netTitle">
            <div class="lg-card__head">
                <div>
                    <h2 class="lg-section-title" id="netTitle">Hub network</h2>
                    <p class="lg-section-sub">Province hubs and the stations under them.</p>
                </div>
            </div>
            <div class="lg-table-wrap">
                <table class="lg-table">
                    <thead><tr><th>Hub</th><th>Type</th><th>Manager</th><th class="is-num">Riders</th><th class="is-num">Parcels now</th><th>Status</th></tr></thead>
                    <tbody>
                    @foreach ($hubs as $hub)
                        <tr>
                            <td>
                                <div class="lg-cell-title" @if ($hub['type'] === 'Station') style="padding-left:20px" @endif>{{ $hub['name'] }}</div>
                                <div class="lg-cell-sub" @if ($hub['type'] === 'Station') style="padding-left:20px" @endif>{{ $hub['place'] }}</div>
                            </td>
                            <td><span class="lg-pill lg-pill--{{ $hub['type'] === 'Province hub' ? 'violet' : 'teal' }}">{{ $hub['type'] }}</span></td>
                            <td>{{ $hub['manager'] }}</td>
                            <td class="is-num">{{ $hub['riders'] }}</td>
                            <td class="is-num">{{ $hub['parcels'] }}</td>
                            <td><span class="lg-pill lg-pill--{{ $hub['status'] === 'Active' ? 'green' : 'amber' }}">{{ $hub['status'] }}</span></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-logistics.shell>