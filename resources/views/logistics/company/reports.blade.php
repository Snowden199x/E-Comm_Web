@php
    // HARDCODED PREVIEW: replace with network figures for the selected period.
    $stats = [['Parcels this month', '8,420', 'package', 'blue'], ['Delivered', '7,935', 'check-circle', 'green'], ['Failed / returned', '211', 'x', 'red'], ['Success rate', '94.2%', 'bar-chart', 'amber']];
    $hubs = [['Laguna Hub', 'Province hub', 3210, 96.1], ['Santa Cruz Station', 'Station', 1480, 95.8], ['Pagsanjan Station', 'Station', 902, 94.0], ['Quezon Hub', 'Province hub', 2120, 93.5], ['Lucena Station', 'Station', 708, 92.9]];
    $avg = 94.2;
    $maxParcels = max(array_column($hubs, 2));
@endphp
<x-logistics.shell title="Reports" role="company">
    <div class="lg-page" x-data>
        <div class="lg-page-head">
            <div>
                <h1>Reports</h1>
                <p>Compare every hub in your network.</p>
            </div>
            <div class="lg-page-head__actions">
                <select class="lg-select" aria-label="Period" style="width:auto"><option>This month</option><option>Last 7 days</option></select>
                <button type="button" class="lg-btn lg-btn--outline"
                    @click="$dispatch('lg-toast', { type: 'info', message: 'PDF export is not connected in this preview.' })"><x-logistics.icon name="download" :size="18" /> Export PDF</button>
            </div>
        </div>

        <div class="lg-grid lg-grid--4">
            @foreach ($stats as $i => [$l, $v, $ic, $t])
                <div class="lg-stat lg-rise" style="--i: {{ $i }}">
                    <span class="lg-stat__icon lg-stat__icon--{{ $t }}"><x-logistics.icon :name="$ic" :size="24" /></span>
                    <div class="lg-stat__body"><p class="lg-stat__label">{{ $l }}</p><p class="lg-stat__value">{{ $v }}</p></div>
                </div>
            @endforeach
        </div>

        <section class="lg-card" aria-labelledby="perHub">
            <div class="lg-card__head">
                <div>
                    <h2 class="lg-section-title" id="perHub">Performance per hub</h2>
                    <p class="lg-section-sub">Compared with the network average of {{ $avg }}%.</p>
                </div>
            </div>
            <div class="lg-table-wrap">
                <table class="lg-table lg-table--stack lg-table--mid">
                    <thead><tr><th>Hub</th><th>Type</th><th>Parcels</th><th>Success rate</th><th>Against average</th></tr></thead>
                    <tbody>
                    @foreach ($hubs as [$n, $t, $p, $s])
                        @php $above = $s >= $avg; @endphp
                        <tr>
                            <td data-label="Hub"><span class="lg-cell-title">{{ $n }}</span></td>
                            <td data-label="Type"><span class="lg-pill lg-pill--{{ $t === 'Station' ? 'teal' : 'violet' }}">{{ $t }}</span></td>
                            <td data-label="Parcels" style="min-width:170px">
                                <div class="lg-meter-row">
                                    <div class="lg-meter-row__top"><span class="sr-only">Parcels</span><strong>{{ number_format($p) }}</strong></div>
                                    <div class="lg-meter" role="presentation"><span style="--w: {{ round($p / $maxParcels * 100) }}%"></span></div>
                                </div>
                            </td>
                            <td data-label="Success rate" style="min-width:170px">
                                <div class="lg-meter-row">
                                    <div class="lg-meter-row__top"><span class="sr-only">Success rate</span><strong>{{ number_format($s, 1) }}%</strong></div>
                                    <div class="lg-meter {{ $above ? 'lg-meter--ok' : 'lg-meter--warn' }}" role="presentation"><span style="--w: {{ $s }}%"></span></div>
                                </div>
                            </td>
                            <td data-label="Against average"><span class="lg-pill lg-pill--{{ $above ? 'green' : 'amber' }}">{{ $above ? 'Above average' : 'Below average' }}</span></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-logistics.shell>
