<x-logistics.shell title="Parcel Exceptions" :role="$role">
    <div class="lg-page">
        <div class="lg-page-head"><div><h1>Parcel Exceptions</h1><p>Record a problem while a parcel is at your facility. An open exception pauses its next scan or trip departure.</p></div></div>
        @if (session('success'))<div class="lg-note" role="status">{{ session('success') }}</div>@endif
        @if ($errors->any())<div class="lg-note lg-note--warn" role="alert">{{ $errors->first() }}</div>@endif

        @if ($role !== 'company')
            <section class="lg-card" aria-labelledby="reportExceptionTitle">
                <div class="lg-card__head"><h2 class="lg-section-title" id="reportExceptionTitle">Report a parcel problem</h2></div>
                <form method="POST" action="{{ route('lgp.'.$role.'.exceptions.store') }}" class="lg-card__body lg-form-grid">
                    @csrf
                    <div class="lg-field"><label for="exceptionParcel">Parcel at {{ $facility->name }}</label><select id="exceptionParcel" name="order_id" class="lg-select" required><option value="">Choose parcel</option>@foreach ($eligible as $order)<option value="{{ $order->id }}" @selected((string) old('order_id') === (string) $order->id)>{{ $order->tracking_number }}</option>@endforeach</select></div>
                    <div class="lg-field"><label for="exceptionReason">Reason</label><select id="exceptionReason" name="reason" class="lg-select" required><option value="">Choose reason</option>@foreach (['damaged' => 'Damaged parcel', 'label_unreadable' => 'Unreadable label', 'missing' => 'Parcel missing', 'wrong_route' => 'Wrong route', 'other' => 'Other'] as $key => $label)<option value="{{ $key }}" @selected(old('reason') === $key)>{{ $label }}</option>@endforeach</select></div>
                    <div class="lg-field is-wide"><label for="exceptionDetails">What happened?</label><textarea id="exceptionDetails" name="details" class="lg-input" rows="3" maxlength="1000" required>{{ old('details') }}</textarea></div>
                    <div class="is-wide"><button type="submit" class="lg-btn" @disabled($eligible->isEmpty())>Record exception</button></div>
                </form>
            </section>
        @endif

        <section class="lg-card" aria-labelledby="exceptionQueueTitle">
            <div class="lg-card__head"><h2 class="lg-section-title" id="exceptionQueueTitle">Exception history</h2></div>
            <div class="lg-table-wrap"><table class="lg-table lg-table--stack"><thead><tr><th>Parcel</th><th>Facility</th><th>Reason and details</th><th>Status</th><th>Recorded</th><th>Resolution</th></tr></thead><tbody>
                @forelse ($exceptions as $exception)
                    <tr>
                        <td data-label="Parcel"><strong>{{ $exception->order?->tracking_number }}</strong></td>
                        <td data-label="Facility">{{ $exception->facility?->name }}</td>
                        <td data-label="Reason and details">{{ ucfirst(str_replace('_', ' ', $exception->reason)) }}<div class="lg-cell-sub">{{ $exception->details }}</div></td>
                        <td data-label="Status"><span class="lg-pill lg-pill--{{ $exception->status === 'open' ? 'amber' : 'green' }}">{{ ucfirst($exception->status) }}</span></td>
                        <td data-label="Recorded">{{ $exception->created_at?->format('M j, g:i A') }}</td>
                        <td data-label="Resolution">
                            @if ($exception->status === 'open' && $role !== 'company' && (int) $exception->facility_id === (int) $facility->id)
                                <form method="POST" action="{{ route('lgp.'.$role.'.exceptions.resolve', $exception) }}" class="lg-form-grid" x-data="{ resolution: 'resume' }">
                                    @csrf @method('PATCH')
                                    <div class="lg-field"><label for="resolution{{ $exception->id }}">Action</label><select id="resolution{{ $exception->id }}" name="resolution" class="lg-select" x-model="resolution"><option value="resume">Resume route</option><option value="detour">Add a route stop</option></select></div>
                                    <div class="lg-field" x-show="resolution === 'detour'" x-cloak><label for="detour{{ $exception->id }}">Next stop before planned destination</label><select id="detour{{ $exception->id }}" name="detour_facility_id" class="lg-select" :required="resolution === 'detour'"><option value="">Choose facility</option>@foreach ($detours as $detour)<option value="{{ $detour->id }}">{{ $detour->name }} · {{ $detour->municipality }}, {{ $detour->province }}</option>@endforeach</select></div>
                                    <div class="lg-field"><label for="resolutionNote{{ $exception->id }}">Resolution note</label><input id="resolutionNote{{ $exception->id }}" name="resolution_note" class="lg-input" required minlength="5" maxlength="1000"></div>
                                    <div><button class="lg-btn lg-btn--sm" type="submit">Resolve</button></div>
                                </form>
                            @elseif ($exception->status === 'resolved')
                                {{ $exception->resolution_note }}
                            @else
                                Managed by {{ $exception->facility?->name }}
                            @endif
                        </td>
                    </tr>
                @empty<tr><td colspan="6">No parcel exceptions in this scope.</td></tr>@endforelse
            </tbody></table></div>
            {{ $exceptions->links() }}
        </section>
    </div>
</x-logistics.shell>
