<x-logistics.layout title="Rider Management">
    <div class="lg-page">
        <div class="lg-page-head">
            <div>
                <h1>Rider Management</h1>
                <p>Review applicants and manage riders linked to your Main Hub.</p>
            </div>
        </div>

        <nav class="lg-tabs" aria-label="Rider status">
            @foreach(['approved' => 'Active', 'pending' => 'Applications', 'disapproved' => 'Rejected', 'deactivated' => 'Inactive'] as $value => $label)
                <a href="{{ route('logistics.riders.index', ['status' => $value]) }}" class="lg-tab" @if($status === $value) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
        </nav>

        <section class="lg-card" aria-label="Riders">
            @forelse($riders as $rider)
                <div class="lg-row">
                    <div class="lg-row__main">
                        <div class="lg-person">
                            <span class="lg-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($rider->first_name, 0, 1)) }}</span>
                            <span><strong>{{ $rider->first_name }} {{ $rider->last_name }}</strong><small>{{ $rider->user->email }}</small></span>
                        </div>
                        <div class="lg-row__meta">{{ $rider->vehicle_type }}<small>{{ $rider->municipality }}, {{ $rider->province }}</small></div>
                        <div class="lg-row__meta">{{ $status === 'approved' ? 'Active parcels' : 'Status' }}<small>{{ $status === 'approved' ? ($workload[$rider->user_id] ?? 0) : ucfirst($status) }}</small></div>
                        <div class="lg-row__actions">
                            @if($status === 'pending')
                                <span class="lg-doc-links">
                                    @foreach(['valid-id' => 'Valid ID', 'drivers-license' => 'License', 'or-cr' => 'OR / CR'] as $document => $label)
                                        <a class="lg-doc-link" href="{{ route('logistics.riders.verification-documents.show', [$rider, $document]) }}" target="_blank" rel="noopener noreferrer">{{ $label }}</a>
                                    @endforeach
                                </span>
                                <form method="POST" action="{{ route('logistics.riders.approve', $rider) }}">@csrf<button class="lg-btn lg-btn--sm" type="submit">Approve</button></form>
                                <form method="POST" action="{{ route('logistics.riders.reject', $rider) }}" data-draft-key="logistics-{{ auth()->id() }}-rider-reject-{{ $rider->id }}">@csrf
                                    <label class="sr-only" for="rider-reason-{{ $rider->id }}">Rejection reason</label>
                                    <input id="rider-reason-{{ $rider->id }}" class="lg-input" name="reason" required maxlength="255" placeholder="Rejection reason">
                                    <button class="lg-btn lg-btn--danger lg-btn--sm" type="submit">Reject</button>
                                </form>
                            @elseif(in_array($status, ['approved', 'deactivated'], true))
                                <form method="POST" action="{{ route('logistics.riders.status', $rider) }}">@csrf @method('PATCH')
                                    <input type="hidden" name="active" value="{{ $status === 'approved' ? '0' : '1' }}">
                                    <button class="lg-btn lg-btn--outline lg-btn--sm" type="submit">{{ $status === 'approved' ? 'Deactivate' : 'Activate' }}</button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="lg-empty"><h2>No riders in this tab</h2><p>Riders linked to your Main Hub will appear here.</p></div>
            @endforelse
        </section>
        {{ $riders->links('logistics.partials.pagination') }}
    </div>
</x-logistics.layout>
