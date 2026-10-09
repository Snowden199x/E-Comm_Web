@php
    $riders = [['Jun Bautista', 14, 85], ['Paolo Garcia', 12, 72], ['Nina Castillo', 9, 55]];
    $parcels = [['VND-10482', 'Brgy. Poblacion', 'Zone A', '2.1 kg'], ['VND-10479', 'Brgy. Bagumbayan', 'Zone B', '0.8 kg'], ['VND-10477', 'Brgy. Patimbao', 'Zone C', '5.4 kg']];
@endphp
<x-logistics.shell title="Delivery Assignments" role="station">
    <div class="lg-page">
        <div class="lg-page-head"><div><h1>Delivery Assignments</h1><p>Give each sorted parcel to a rider. The rider's current load is shown beside the name.</p></div></div>
        <div class="lg-grid" style="grid-template-columns:repeat(auto-fit,minmax(300px,1fr))">
            @foreach ($riders as [$n, $p, $pct])
                <div class="lg-card"><div class="lg-card__body">
                    <div class="lg-person"><span class="lg-avatar" aria-hidden="true">{{ $n[0] }}</span><span><strong>{{ $n }}</strong><small>{{ $p }} parcels today</small></span></div>
                    <div style="height:8px;border-radius:99px;background:var(--lg-line-2);margin-top:12px"><div style="height:100%;width:{{ $pct }}%;border-radius:99px;background:var(--lg-brand)"></div></div>
                </div></div>
            @endforeach
        </div>
        <section class="lg-card" aria-label="Parcels to assign">
            <div class="lg-table-wrap"><table class="lg-table">
                <thead><tr><th>Parcel</th><th>Delivery area</th><th>Zone</th><th>Weight</th><th>Rider</th><th></th></tr></thead>
                <tbody>
                @foreach ($parcels as $i => [$c, $a, $z, $w])
                    <tr>
                        <td><strong>{{ $c }}</strong></td><td>{{ $a }}</td><td><span class="lg-pill lg-pill--teal">{{ $z }}</span></td><td>{{ $w }}</td>
                        <td><label class="sr-only" for="as{{ $i }}">Rider</label><select id="as{{ $i }}" class="lg-select">@foreach ($riders as [$n, $p])<option>{{ $n }} ({{ $p }})</option>@endforeach</select></td>
                        <td><button type="button" class="lg-btn lg-btn--sm">Assign</button></td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
        </section>
    </div>
</x-logistics.shell>