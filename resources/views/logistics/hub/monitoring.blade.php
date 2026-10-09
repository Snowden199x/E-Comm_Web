@php
    $rows = [
        ['VND-10440', 'Ana Lim', 'Brgy. Poblacion', 'Jun Bautista', 'Out for delivery', 'brand', '10:42 AM, left hub'],
        ['VND-10441', 'Ben Cruz', 'Brgy. Bagumbayan', 'Paolo Garcia', 'Out for delivery', 'brand', '10:55 AM, near drop-off'],
        ['VND-10433', 'Cara Diaz', 'Brgy. Patimbao', 'Nina Castillo', 'Delivered', 'green', '9:30 AM, signed'],
        ['VND-10429', 'Dan Ortiz', 'Brgy. Santo Angel', 'Jun Bautista', 'Delivery failed', 'red', '9:05 AM, no one home'],
        ['VND-10421', 'Eve Ramos', 'Brgy. Poblacion', 'Paolo Garcia', 'Delivered', 'green', '8:48 AM, signed'],
    ];
@endphp
<x-logistics.shell title="Delivery Monitoring" :role="$role">
    <div class="lg-page">
        <div class="lg-page-head"><div><h1>Delivery Monitoring</h1><p>Watch parcels go out and come back. Failed deliveries need a decision.</p></div></div>
        <div class="lg-toolbar">
            <select class="lg-select" aria-label="Status"><option>All statuses</option><option>Out for delivery</option><option>Delivered</option><option>Delivery failed</option></select>
            <select class="lg-select" aria-label="Rider"><option>All riders</option><option>Jun Bautista</option><option>Paolo Garcia</option><option>Nina Castillo</option></select>
            <input class="lg-input" style="max-width:260px" placeholder="Search parcel code" aria-label="Search parcel code">
        </div>
        <section class="lg-card" aria-label="Deliveries">
            <div class="lg-table-wrap"><table class="lg-table">
                <thead><tr><th>Parcel</th><th>Buyer</th><th>Area</th><th>Rider</th><th>Status</th><th>Last scan</th><th></th></tr></thead>
                <tbody>
                @foreach ($rows as [$c, $b, $a, $r, $s, $t, $l])
                    <tr>
                        <td><strong>{{ $c }}</strong></td><td>{{ $b }}</td><td>{{ $a }}</td><td>{{ $r }}</td>
                        <td><span class="lg-pill lg-pill--{{ $t }}">{{ $s }}</span></td><td>{{ $l }}</td>
                        <td>@if ($t === 'red')<button type="button" class="lg-btn lg-btn--outline lg-btn--sm">Retry</button>@endif</td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
        </section>
    </div>
</x-logistics.shell>