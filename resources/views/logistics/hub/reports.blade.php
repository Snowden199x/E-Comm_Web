@php
    $stats = [['Parcels handled', '1,284', 'package', 'blue'], ['Delivered', '1,102', 'check-circle', 'green'], ['Failed', '48', 'x', 'red'], ['Success rate', '95.8%', 'bar-chart', 'amber']];
    $areas = [['Poblacion', 412], ['Bagumbayan', 301], ['Patimbao', 244], ['Santo Angel', 145]];
    $riders = [['Jun Bautista', 402, 97], ['Paolo Garcia', 355, 95], ['Nina Castillo', 345, 94]];
@endphp
<x-logistics.shell title="Reports" :role="$role">
    <div class="lg-page">
        <div class="lg-page-head">
            <div><h1>Reports</h1><p>How this hub performed in the chosen period.</p></div>
            <div class="lg-page-head__actions">
                <select class="lg-select" aria-label="Period"><option>This month</option><option>Last 7 days</option><option>Last month</option></select>
                <button type="button" class="lg-btn lg-btn--outline">Export PDF</button>
            </div>
        </div>
        <div class="lg-grid lg-grid--4">
            @foreach ($stats as $i => [$l, $v, $ic, $t])
                <div class="lg-stat"><span class="lg-stat__icon lg-stat__icon--{{ $t }}"><x-logistics.icon :name="$ic" :size="24" /></span><div><p class="lg-stat__label">{{ $l }}</p><p class="lg-stat__value">{{ $v }}</p></div></div>
            @endforeach
        </div>
        <div class="lg-grid" style="grid-template-columns:repeat(auto-fit,minmax(320px,1fr))">
            <section class="lg-card"><div class="lg-card__head"><h2 class="lg-section-title">By delivery area</h2></div>
                <div class="lg-card__body" style="display:grid;gap:12px">
                @foreach ($areas as [$n, $c])
                    <div><div style="display:flex;justify-content:space-between;font-size:13px"><span>{{ $n }}</span><strong>{{ $c }}</strong></div>
                    <div style="height:8px;border-radius:99px;background:var(--lg-line-2);margin-top:4px"><div style="height:100%;width:{{ round($c / 412 * 100) }}%;border-radius:99px;background:var(--lg-brand)"></div></div></div>
                @endforeach
                </div>
            </section>
            <section class="lg-card"><div class="lg-card__head"><h2 class="lg-section-title">By rider</h2></div>
                <div class="lg-table-wrap"><table class="lg-table"><thead><tr><th>Rider</th><th class="is-num">Parcels</th><th class="is-num">Success</th></tr></thead><tbody>
                @foreach ($riders as [$n, $c, $s])<tr><td>{{ $n }}</td><td class="is-num">{{ $c }}</td><td class="is-num">{{ $s }}%</td></tr>@endforeach
                </tbody></table></div>
            </section>
        </div>
    </div>
</x-logistics.shell>