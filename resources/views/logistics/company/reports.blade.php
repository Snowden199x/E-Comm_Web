@php
    $stats = [['Parcels this month', '8,420', 'package', 'blue'], ['Delivered', '7,935', 'check-circle', 'green'], ['Failed / returned', '211', 'x', 'red'], ['Success rate', '94.2%', 'bar-chart', 'amber']];
    $hubs = [['Laguna Hub', 'Province hub', 3210, 96.1], ['Santa Cruz Station', 'Station', 1480, 95.8], ['Pagsanjan Station', 'Station', 902, 94.0], ['Quezon Hub', 'Province hub', 2120, 93.5], ['Lucena Station', 'Station', 708, 92.9]];
@endphp
<x-logistics.shell title="Reports" role="company">
    <div class="lg-page">
        <div class="lg-page-head">
            <div><h1>Reports</h1><p>Compare every hub in your network.</p></div>
            <div class="lg-page-head__actions"><select class="lg-select" aria-label="Period"><option>This month</option><option>Last 7 days</option></select><button type="button" class="lg-btn lg-btn--outline">Export PDF</button></div>
        </div>
        <div class="lg-grid lg-grid--4">
            @foreach ($stats as [$l, $v, $ic, $t])
                <div class="lg-stat"><span class="lg-stat__icon lg-stat__icon--{{ $t }}"><x-logistics.icon :name="$ic" :size="24" /></span><div><p class="lg-stat__label">{{ $l }}</p><p class="lg-stat__value">{{ $v }}</p></div></div>
            @endforeach
        </div>
        <section class="lg-card" aria-label="Per hub">
            <div class="lg-card__head"><h2 class="lg-section-title">Performance per hub</h2></div>
            <div class="lg-table-wrap"><table class="lg-table"><thead><tr><th>Hub</th><th>Type</th><th class="is-num">Parcels</th><th class="is-num">Success rate</th></tr></thead><tbody>
            @foreach ($hubs as [$n, $t, $p, $s])
                <tr><td>{{ $n }}</td><td><span class="lg-pill lg-pill--{{ $t === 'Station' ? 'teal' : 'violet' }}">{{ $t }}</span></td><td class="is-num">{{ number_format($p) }}</td><td class="is-num">{{ $s }}%</td></tr>
            @endforeach
            </tbody></table></div>
        </section>
    </div>
</x-logistics.shell>