{{--
    Seller Complaints — a read-only compilation of the cases Admin handles in Complaints & Disputes.

    Data contract (supplied by a future Seller\ComplaintController@index, see docs/features/seller/backend-needs-2026-10-07.md):
      $complaints   LengthAwarePaginator<Complaint> with order, complainant, respondent, evidences, activities loaded
      $counts       ['all' => n, 'open' => n, 'in_review' => n, 'resolved' => n]   (for the active scope)
      $scopeCounts  ['against' => n, 'filed' => n]
      $filters      ['scope' => 'against'|'filed', 'status' => ?, 'type' => ?, 'search' => ?]
      $types        Collection<string> of distinct complaint types for the filter

    Every variable has a safe default so the page still renders (empty) if the controller is incomplete.
--}}
@php
    $complaints = $complaints ?? new \Illuminate\Pagination\LengthAwarePaginator([], 0, 8);
    $counts = ($counts ?? []) + ['all' => 0, 'open' => 0, 'in_review' => 0, 'resolved' => 0];
    $scopeCounts = ($scopeCounts ?? []) + ['against' => 0, 'filed' => 0];
    $filters = ($filters ?? []) + ['scope' => 'against', 'status' => '', 'type' => '', 'search' => ''];
    $types = $types ?? collect();
    $scope = $filters['scope'] ?: 'against';
    $me = auth()->id();

    $statCards = [
        ['all', 'All cases', '#512258'],
        ['open', 'Open', '#b45309'],
        ['in_review', 'In progress', '#2563eb'],
        ['resolved', 'Resolved', '#15803d'],
    ];
    $statusLabels = ['open' => 'Open', 'in_review' => 'In progress', 'resolved' => 'Resolved'];
    $query = fn (array $extra = []) => array_filter(array_merge(request()->except('page'), $extra), fn ($v) => $v !== null && $v !== '');
@endphp
<x-seller.layout title="Complaints">
    @vite('resources/css/seller/complaints.css')

    <section class="cmp-page">
        <header class="cmp-head">
            <div>
                <h1>Complaints</h1>
                <p>Every case Vendo Admin is handling for your shop, in one place.</p>
            </div>
        </header>

        {{-- Scope tabs: complaints raised about me vs. reports I raised --}}
        <nav class="cmp-scope" aria-label="Complaint scope">
            <a href="{{ route('seller.complaints.index', ['scope' => 'against']) }}" class="@if($scope === 'against') is-active @endif" @if($scope === 'against') aria-current="page" @endif>
                Filed against my shop <span>{{ number_format($scopeCounts['against']) }}</span>
            </a>
            <a href="{{ route('seller.complaints.index', ['scope' => 'filed']) }}" class="@if($scope === 'filed') is-active @endif" @if($scope === 'filed') aria-current="page" @endif>
                Reports I filed <span>{{ number_format($scopeCounts['filed']) }}</span>
            </a>
        </nav>

        <div class="cmp-stats" role="group" aria-label="Case summary">
            @foreach($statCards as [$key, $label, $color])
                <a class="cmp-stat @if(($filters['status'] ?: 'all') === $key) is-active @endif"
                   href="{{ route('seller.complaints.index', $query(['status' => $key === 'all' ? null : $key])) }}" style="--c: {{ $color }}">
                    <span class="cmp-stat__label">{{ $label }}</span>
                    <strong>{{ number_format($counts[$key]) }}</strong>
                </a>
            @endforeach
        </div>

        <form method="GET" action="{{ route('seller.complaints.index') }}" class="cmp-filters">
            <input type="hidden" name="scope" value="{{ $scope }}">
            @if($filters['status'])<input type="hidden" name="status" value="{{ $filters['status'] }}">@endif
            <label class="cmp-search">
                <span class="cmp-sr">Search cases</span>
                <input type="search" name="search" value="{{ $filters['search'] }}" maxlength="100" placeholder="Search case number, order or type…">
            </label>
            <label>
                <span class="cmp-sr">Complaint type</span>
                <select name="type" data-cmp-autosubmit>
                    <option value="">All types</option>
                    @foreach($types as $type)<option value="{{ $type }}" @selected($filters['type'] === $type)>{{ $type }}</option>@endforeach
                </select>
            </label>
            <button class="cmp-btn" type="submit">Apply</button>
            @if($filters['search'] || $filters['type'] || $filters['status'])
                <a class="cmp-link" href="{{ route('seller.complaints.index', ['scope' => $scope]) }}">Clear</a>
            @endif
        </form>

        <section class="cmp-card" aria-label="Complaint cases">
            <div class="cmp-table-wrap">
                <table class="cmp-table">
                    <thead>
                        <tr>
                            <th scope="col">Case</th>
                            <th scope="col">Type</th>
                            <th scope="col">Order</th>
                            <th scope="col">{{ $scope === 'against' ? 'Raised by' : 'Reported' }}</th>
                            <th scope="col">Date</th>
                            <th scope="col">Status</th>
                            <th scope="col"><span class="cmp-sr">Action</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($complaints as $complaint)
                            @php
                                $caseNo = 'CMP-'.str_pad((string) $complaint->id, 5, '0', STR_PAD_LEFT);
                                $isReport = $complaint->kind === 'user_report';
                                $statusColors = $complaint->status_colors;
                                $typeColors = $complaint->type_colors;
                                // A buyer who reported the shop stays anonymous; order complaints show the buyer as on any order.
                                $party = $scope === 'against'
                                    ? ($isReport ? 'A buyer' : ($complaint->complainant?->name ?? 'Buyer unavailable'))
                                    : ($complaint->respondent?->name ?? 'Buyer unavailable');
                            @endphp
                            <tr>
                                <td class="cmp-nowrap"><strong>{{ $caseNo }}</strong></td>
                                <td><span class="cmp-type" style="--b: {{ $typeColors['border'] }}; --bg: {{ $typeColors['bg'] }}">{{ $complaint->type ?? 'Complaint' }}</span></td>
                                <td class="cmp-nowrap">{{ $complaint->order?->number ?? '—' }}</td>
                                <td>{{ $party }}</td>
                                <td class="cmp-nowrap">{{ $complaint->created_at->format('M j, Y') }}<small>{{ $complaint->created_at->format('g:i A') }}</small></td>
                                <td><span class="cmp-status" style="--b: {{ $statusColors['border'] }}; --bg: {{ $statusColors['bg'] }}">{{ $statusColors['label'] }}</span></td>
                                <td class="cmp-actions"><button type="button" class="cmp-btn cmp-btn--small" data-cmp-open="cmp-{{ $complaint->id }}">View</button></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="cmp-empty">
                                    <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3 4 6v6c0 4.5 3.2 7.8 8 9 4.8-1.2 8-4.5 8-9V6z"/><path d="m9 12 2 2 4-4"/></svg>
                                    <strong>{{ ($filters['search'] || $filters['type'] || $filters['status']) ? 'No cases match your filters' : ($scope === 'against' ? 'No complaints against your shop' : 'You have not filed any reports') }}</strong>
                                    <span>{{ $scope === 'against' ? 'Good news. Cases raised by buyers would appear here once Admin receives them.' : 'Reports you file from Messages or an order appear here so you can follow Admin’s decision.' }}</span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($complaints->total() > 0)
                @include('seller.operations.pagination', ['records' => $complaints])
            @endif
        </section>
    </section>

    {{-- One read-only details dialog per case on this page (max 8), so no extra request is needed. --}}
    @foreach($complaints as $complaint)
        @php
            $caseNo = 'CMP-'.str_pad((string) $complaint->id, 5, '0', STR_PAD_LEFT);
            $isReport = $complaint->kind === 'user_report';
            $outcome = match (true) {
                $complaint->decision === 'approved' && $scope === 'against' => 'Admin upheld this report and issued a warning to your shop.',
                $complaint->decision === 'approved' => 'Admin approved your report.',
                $complaint->decision === 'rejected' && $scope === 'against' => 'Admin reviewed this report and took no action.',
                $complaint->decision === 'rejected' => 'Admin reviewed your report and did not approve it.',
                $complaint->status === 'resolved' => 'Admin marked this case as resolved.',
                $complaint->status === 'in_review' => 'Admin is reviewing this case.',
                default => 'Waiting for Admin to start the review.',
            };
            $statusColors = $complaint->status_colors;
            // Admin's internal decision notes are only shown to the person who filed the report, never to the respondent.
            $events = $complaint->activities->filter(fn ($a) => $scope === 'filed'
                || str_starts_with($a->action, 'Status changed') || str_starts_with($a->action, 'Report submitted'))->sortBy('id');
            $who = fn ($actor) => str_starts_with($actor, 'admin') ? 'Vendo Admin' : (str_ends_with($actor, '#'.$me) ? 'You' : 'Buyer');
        @endphp
        <dialog class="cmp-dialog" id="cmp-{{ $complaint->id }}" aria-labelledby="cmp-title-{{ $complaint->id }}">
            <header class="cmp-dialog__head">
                <div>
                    <span class="cmp-dialog__eyebrow">{{ $isReport ? 'Account report' : 'Order complaint' }}</span>
                    <h2 id="cmp-title-{{ $complaint->id }}">{{ $caseNo }} · {{ $complaint->type ?? 'Complaint' }}</h2>
                </div>
                <button type="button" class="cmp-x" data-cmp-close aria-label="Close case details">×</button>
            </header>
            <div class="cmp-dialog__body">
                <p class="cmp-outcome" style="--b: {{ $statusColors['border'] }}; --bg: {{ $statusColors['bg'] }}"><strong>{{ $statusColors['label'] }}</strong> {{ $outcome }}</p>

                <dl class="cmp-info">
                    <div><dt>Submitted</dt><dd>{{ $complaint->created_at->format('M j, Y · g:i A') }}</dd></div>
                    <div><dt>Order</dt><dd>{{ $complaint->order?->number ?? 'Not linked to an order' }}</dd></div>
                    @if($complaint->reviewed_at)<div><dt>Reviewed</dt><dd>{{ $complaint->reviewed_at->format('M j, Y · g:i A') }}</dd></div>@endif
                </dl>

                <h3>What was reported</h3>
                <p class="cmp-text">{{ $complaint->description ?: 'No description was provided.' }}</p>

                <h3>Evidence <small>{{ $complaint->evidences->count() }} file(s)</small></h3>
                @forelse($complaint->evidences as $file)
                    <p class="cmp-file">{{ $file->original_filename }}</p>
                @empty
                    <p class="cmp-muted">No evidence was attached.</p>
                @endforelse

                <h3>Case history</h3>
                <ol class="cmp-timeline">
                    @forelse($events as $event)
                        <li><strong>{{ $who($event->actor) }}</strong><span>{{ $event->action }}</span><small>{{ $event->created_at->format('M j, Y · g:i A') }}</small></li>
                    @empty
                        <li><span>No activity recorded yet.</span></li>
                    @endforelse
                </ol>
            </div>
        </dialog>
    @endforeach

    <script>
        document.addEventListener('click', event => {
            const open = event.target.closest('[data-cmp-open]');
            if (open) document.getElementById(open.dataset.cmpOpen)?.showModal();
            if (event.target.closest('[data-cmp-close]')) event.target.closest('dialog')?.close();
            if (event.target.matches('dialog.cmp-dialog')) event.target.close(); // click on the backdrop
        });
        document.querySelectorAll('[data-cmp-autosubmit]').forEach(select => select.addEventListener('change', () => select.form.submit()));
    </script>
</x-seller.layout>