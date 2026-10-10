@php
    $next = $role === 'province' ? ['Quezon Hub', 'Batangas Hub', 'Cavite Hub', 'Stay: Laguna stations'] : ['Zone A (Poblacion)', 'Zone B (Bagumbayan)', 'Zone C (Patimbao)'];
    $parcels = [['VND-10482', 'Laguna Hub', 'Brgy. Poblacion', '2.1 kg'], ['VND-10479', 'Laguna Hub', 'Brgy. Bagumbayan', '0.8 kg'], ['VND-10477', 'Laguna Hub', 'Brgy. Patimbao', '5.4 kg'], ['VND-10469', 'Quezon Hub', 'Brgy. Santo Angel', '1.2 kg']];
@endphp
<x-logistics.shell title="Parcel Sorting" :role="$role">
    <div class="lg-page" x-data="{ picked: [] }">
        <div class="lg-page-head">
            <div><h1>Parcel Sorting</h1><p>{{ $role === 'province' ? 'Group parcels by the next hub they should go to.' : 'Group parcels into delivery zones so each rider gets one area.' }}</p></div>
            <div class="lg-page-head__actions">
                <select class="lg-select" aria-label="Sort to">@foreach ($next as $n)<option>{{ $n }}</option>@endforeach</select>
                <button type="button" class="lg-btn" :disabled="picked.length === 0" x-text="'Mark ' + picked.length + ' as sorted'"></button>
            </div>
        </div>
        <section class="lg-card" aria-label="Parcels to sort">
            <div class="lg-table-wrap"><table class="lg-table">
                <thead><tr><th style="width:44px"><span class="sr-only">Select</span></th><th>Parcel</th><th>From</th><th>Destination area</th><th>Weight</th><th>Status</th></tr></thead>
                <tbody>
                @foreach ($parcels as [$c, $f, $a, $w])
                    <tr>
                        <td><input type="checkbox" value="{{ $c }}" x-model="picked" aria-label="Select {{ $c }}"></td>
                        <td><strong>{{ $c }}</strong></td><td>{{ $f }}</td><td>{{ $a }}</td><td>{{ $w }}</td>
                        <td><span class="lg-pill lg-pill--violet">At sorting</span></td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
        </section>
    </div>
</x-logistics.shell>