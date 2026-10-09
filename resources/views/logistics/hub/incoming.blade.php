@php
    $pickups = [['VND-10466', 'Hana Shop', 'Brgy. Santo Angel', 2], ['VND-10470', 'Tech Corner', 'Brgy. Patimbao', 1], ['VND-10473', 'Bloom Florist', 'Brgy. Poblacion', 3]];
    $arriving = $role === 'province'
        ? [['VND-10501', 'Santa Cruz Station', 'Quezon Hub', 'Arrived'], ['VND-10498', 'Pagsanjan Station', 'Quezon Hub', 'On the way']]
        : [['VND-10482', 'Laguna Hub', 'Brgy. Poblacion', 'Arrived'], ['VND-10479', 'Laguna Hub', 'Brgy. Bagumbayan', 'On the way']];
    $riders = ['Jun Bautista', 'Paolo Garcia', 'Nina Castillo'];
@endphp
<x-logistics.shell title="Incoming Parcels" :role="$role">
    <div class="lg-page">
        <div class="lg-page-head"><div><h1>Incoming Parcels</h1><p>Verify seller pickups, then confirm parcels as they arrive at this hub.</p></div></div>

        <section class="lg-card" aria-label="Pickup requests">
            <div class="lg-card__head"><div><h2 class="lg-section-title">Pickup requests</h2><p class="lg-section-sub">Sellers waiting for a rider.</p></div></div>
            <div class="lg-table-wrap"><table class="lg-table">
                <thead><tr><th>Parcel</th><th>Seller</th><th>Pickup area</th><th class="is-num">Items</th><th>Rider</th><th></th></tr></thead>
                <tbody>
                @foreach ($pickups as $i => [$c, $s, $a, $n])
                    <tr>
                        <td><strong>{{ $c }}</strong></td><td>{{ $s }}</td><td>{{ $a }}</td><td class="is-num">{{ $n }}</td>
                        <td><label class="sr-only" for="pk{{ $i }}">Rider</label><select id="pk{{ $i }}" class="lg-select">@foreach ($riders as $r)<option>{{ $r }}</option>@endforeach</select></td>
                        <td><button type="button" class="lg-btn lg-btn--sm">Verify &amp; assign</button></td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
        </section>

        <section class="lg-card" aria-label="Arriving at this hub">
            <div class="lg-card__head"><div><h2 class="lg-section-title">Arriving at this hub</h2><p class="lg-section-sub">Scan or confirm each parcel when it gets here.</p></div></div>
            <div class="lg-table-wrap"><table class="lg-table">
                <thead><tr><th>Parcel</th><th>From</th><th>Going to</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @foreach ($arriving as [$c, $f, $t, $st])
                    <tr>
                        <td><strong>{{ $c }}</strong></td><td>{{ $f }}</td><td>{{ $t }}</td>
                        <td><span class="lg-pill lg-pill--{{ $st === 'Arrived' ? 'green' : 'blue' }}">{{ $st }}</span></td>
                        <td><button type="button" class="lg-btn lg-btn--sm" @if ($st !== 'Arrived') disabled @endif>Mark arrived</button></td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
        </section>
    </div>
</x-logistics.shell>