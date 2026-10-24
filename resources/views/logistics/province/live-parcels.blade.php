<x-logistics.shell title="Parcel Operations" role="province">
    <div class="lg-page">
        <div class="lg-page-head"><div><h1>Parcel Operations</h1><p>Parcels routed through {{ $hub->name }}. Scan a parcel on arrival to receive and sort it, then create a transport trip for its next leg.</p></div><div class="lg-page-head__actions"><a class="lg-btn" href="{{ route('lgp.province.trips.create') }}"><x-logistics.icon name="plus" :size="18" /> Create trip</a></div></div>
        <section class="lg-card" aria-label="Parcels"><div class="lg-table-wrap"><table class="lg-table lg-table--stack"><thead><tr><th>Parcel</th><th>Stage</th><th>Order status</th><th>Next stop</th><th>Received</th><th>Departed</th></tr></thead><tbody>
            @forelse ($rows as $row)<tr><td data-label="Parcel"><strong>{{ $row['tracking'] }}</strong></td><td data-label="Stage"><span class="lg-pill {{ $row['stage'] === 'Outgoing' ? 'lg-pill--amber' : ($row['stage'] === 'Departed' ? 'lg-pill--blue' : 'lg-pill--violet') }}">{{ $row['stage'] }}</span></td><td data-label="Order status">{{ $row['status'] }}</td><td data-label="Next stop">{{ $row['next'] }}</td><td data-label="Received">{{ $row['arrived'] ?? '—' }}</td><td data-label="Departed">{{ $row['departed'] ?? '—' }}</td></tr>
            @empty<tr><td colspan="6">No parcels have been routed through this Province Hub yet.</td></tr>@endforelse
        </tbody></table></div></section>
        <div style="margin-top:16px">{{ $rows->links() }}</div>
    </div>
</x-logistics.shell>
