@php
    // HARDCODED PREVIEW: replace with figures for the selected period and this hub only.
    $stats = [['Parcels handled', '1,284', 'package', 'blue'], ['Delivered', '1,102', 'check-circle', 'green'], ['Failed', '48', 'x', 'red'], ['Success rate', '95.8%', 'bar-chart', 'amber']];
    $areas = [['Poblacion', 412], ['Bagumbayan', 301], ['Patimbao', 244], ['Santo Angel', 145]];
    $riders = [['Jun Bautista', 402, 97], ['Paolo Garcia', 355, 95], ['Nina Castillo', 345, 94]];
    $maxArea = max(array_column($areas, 1));
    $target = 95; // demo target line used only to colour the success meters
@endphp
<x-logistics.shell title="Reports" :role="$role">
    <div class="lg-page" x-data>
        <div class="lg-page-head">
            <div>
                <h1>Reports</h1>
                <p>How this hub performed in the chosen period.</p>
            </div>
            <div class="lg-page-head__actions">
                <select class="lg-select" aria-label="Period" style="width:auto"><option>This month</option><option>Last 7 days</option><option>Last month</option></select>
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

        <div class="lg-grid" style="grid-template-columns:repeat(auto-fit,minmax(320px,1fr))">
            <section class="lg-card" aria-labelledby="areaTitle">
                <div class="lg-card__head"><h2 class="lg-section-title" id="areaTitle">By delivery area</h2></div>
                <div class="lg-card__body" style="display:grid;gap:16px">
                    @foreach ($areas as [$n, $c])
                        <div class="lg-meter-row">
                            <div class="lg-meter-row__top"><span>{{ $n }}</span><strong>{{ number_format($c) }} parcels</strong></div>
                            <div class="lg-meter" role="presentation"><span style="--w: {{ round($c / $maxArea * 100) }}%"></span></div>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="lg-card" aria-labelledby="riderTitle">
                <div class="lg-card__head">
                    <div><h2 class="lg-section-title" id="riderTitle">By rider</h2><p class="lg-section-sub">Success rate against a {{ $target }}% target.</p></div>
                </div>
                <div class="lg-table-wrap">
                    <table class="lg-table lg-table--stack lg-table--mid">
                        <thead><tr><th>Rider</th><th class="is-num">Parcels</th><th>Success</th></tr></thead>
                        <tbody>
                        @foreach ($riders as [$n, $c, $s])
                            <tr>
                                <td data-label="Rider"><span class="lg-cell-title">{{ $n }}</span></td>
                                <td data-label="Parcels" class="is-num">{{ number_format($c) }}</td>
                                <td data-label="Success" style="min-width:160px">
                                    <div class="lg-meter-row">
                                        <div class="lg-meter-row__top"><span class="sr-only">Success rate</span><strong>{{ $s }}%</strong></div>
                                        <div class="lg-meter {{ $s >= $target ? 'lg-meter--ok' : 'lg-meter--warn' }}" role="presentation"><span style="--w: {{ $s }}%"></span></div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</x-logistics.shell>
