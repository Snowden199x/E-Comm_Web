{{--
    Seller Complaints: a read-only list of the cases Admin handles in Complaints & Disputes.

    Data contract supplied by Seller\ComplaintController@index:
      $complaints   LengthAwarePaginator<Complaint> with order, complainant, respondent, evidences, activities loaded
      $counts       ['all' => n, 'open' => n, 'in_review' => n, 'resolved' => n]   (for the active scope)
      $scopeCounts  ['against' => n, 'filed' => n]
      $filters      ['scope' => 'against'|'filed', 'status' => ?, 'type' => ?, 'search' => ?]
      $types        Collection<string> of distinct complaint types for the filter

    Every variable has a safe default for empty states.
--}}
@php
    $complaints = $complaints ?? new \Illuminate\Pagination\LengthAwarePaginator([], 0, 8);
    $counts = ($counts ?? []) + ['all' => 0, 'open' => 0, 'in_review' => 0, 'resolved' => 0];
    $scopeCounts = ($scopeCounts ?? []) + ['against' => 0, 'filed' => 0];
    $filters = ($filters ?? []) + ['scope' => 'against', 'status' => '', 'type' => '', 'search' => ''];
    $types = $types ?? collect();
    $scope = $filters['scope'] ?: 'against';
    $me = auth()->id();
    $filtered = $filters['search'] || $filters['type'] || $filters['status'];
    $clearUrl = route('seller.complaints.index', ['scope' => $scope]);

    // One pastel tone per status, used everywhere on this page.
    $tones = [
        'all' => ['All cases', 'lilac'],
        'open' => ['Open', 'butter'],
        'in_review' => ['In progress', 'sky'],
        'resolved' => ['Resolved', 'mint'],
    ];
    $query = fn (array $extra = []) => array_filter(array_merge(request()->except('page'), $extra), fn ($v) => $v !== null && $v !== '');
    $caseNo = fn ($c) => 'CMP-'.str_pad((string) $c->id, 5, '0', STR_PAD_LEFT);
@endphp
<x-seller.layout title="Complaints">
    @vite(['resources/css/seller/operations.css', 'resources/css/seller/delivered-orders.css', 'resources/css/seller/complaints.css', 'resources/js/seller/delivered-orders.js', 'resources/js/seller/complaints.js'])

    <section class="ops-page do-page cmp-page">
        <header class="do-head">
            <div>
                <h1>Complaints</h1>
                <p>Every case Vendo Admin is handling for your shop.</p>
            </div>
        </header>

        <nav class="do-chips cmp-scope" aria-label="Complaint scope">
            <a href="{{ route('seller.complaints.index', ['scope' => 'against']) }}" class="do-chip @if($scope === 'against') is-active @endif" @if($scope === 'against') aria-current="page" @endif>
                Against my shop <span class="er-count">{{ number_format($scopeCounts['against']) }}</span>
            </a>
            <a href="{{ route('seller.complaints.index', ['scope' => 'filed']) }}" class="do-chip @if($scope === 'filed') is-active @endif" @if($scope === 'filed') aria-current="page" @endif>
                My reports <span class="er-count">{{ number_format($scopeCounts['filed']) }}</span>
            </a>
        </nav>

        <div class="do-stats" role="group" aria-label="Case summary">
            @foreach($tones as $key => [$label, $tone])
                @php $active = ($filters['status'] ?: 'all') === $key; @endphp
                <a class="do-stat cmp-stat @if($active) is-active @endif" href="{{ route('seller.complaints.index', $query(['status' => $key === 'all' ? null : $key])) }}" data-load @if($active) aria-current="true" @endif>
                    <span class="do-stat__icon do-tone-{{ $tone }}">
                        @if($key === 'all')<svg class="do-ico do-ico--pop" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 4 6v6c0 4.500 3.200 7.800 8 9 4.800-1.200 8-4.500 8-9V6z"/></svg>
                        @elseif($key === 'open')<svg class="do-ico do-ico--pop" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16.500v.01"/></svg>
                        @elseif($key === 'in_review')<svg class="do-ico do-ico--spin" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.500"/><path d="m16 16 4 4"/></svg>
                        @else<svg class="do-ico do-ico--draw" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8 12.500 3 3 5-6"/></svg>@endif
                    </span>
                    <span class="do-stat__text">{{ $label }}<strong>{{ number_format($counts[$key]) }}</strong></span>
                </a>
            @endforeach
        </div>

        @if($errors->any())
            <div class="do-alert do-alert--error" role="alert">
                <svg class="do-ico" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16.500v.01"/></svg>
                <div>@foreach($errors->all() as $message)<p>{{ $message }}</p>@endforeach</div>
                <a class="do-btn do-btn--small" href="{{ route('seller.complaints.index') }}">Clear filters</a>
            </div>
        @endif

        <form method="GET" action="{{ route('seller.complaints.index') }}" class="do-filters" data-do-filters>
            <input type="hidden" name="scope" value="{{ $scope }}">
            @if($filters['status'])<input type="hidden" name="status" value="{{ $filters['status'] }}">@endif
            <label class="do-search">
                <span class="ops-sr-only">Search cases</span>
                <svg class="do-ico" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.500"/><path d="m16 16 4 4"/></svg>
                <input type="search" name="search" value="{{ $filters['search'] }}" maxlength="100" placeholder="Search case no., order no., or type" autocomplete="off">
            </label>
            <label>
                <span class="ops-sr-only">Complaint type</span>
                <select name="type" data-auto-submit>
                    <option value="">All types</option>
                    @foreach($types as $type)<option value="{{ $type }}" @selected($filters['type'] === $type)>{{ $type }}</option>@endforeach
                </select>
            </label>
            @if($filtered)
                <a href="{{ $clearUrl }}" class="do-chip do-chip--clear" data-load><svg class="do-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>Clear filters</a>
            @endif
        </form>

        <section class="do-card do-table-card" aria-label="Complaint cases" data-do-table>
            <div class="ops-table-scroll">
                <table class="do-table">
                    <thead>
                        <tr>
                            <th scope="col">Case</th>
                            <th scope="col">Order</th>
                            <th scope="col">{{ $scope === 'against' ? 'Raised by' : 'Reported' }}</th>
                            <th scope="col">Date</th>
                            <th scope="col">Status</th>
                            <th scope="col"><span class="ops-sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($complaints as $complaint)
                            @php
                                $isReport = $complaint->kind === 'user_report';
                                $tone = $tones[$complaint->status][1] ?? 'lilac';
                                $statusLabel = $tones[$complaint->status][0] ?? ucfirst(str_replace('_', ' ', (string) $complaint->status));
                                // A buyer who reported the shop stays anonymous; order complaints show the buyer as on any order.
                                $party = $scope === 'against'
                                    ? ($isReport ? 'A buyer' : ($complaint->complainant?->name ?? 'Buyer removed'))
                                    : ($complaint->respondent?->name ?? 'Buyer removed');
                            @endphp
                            <tr class="do-row">
                                <td><strong>{{ $caseNo($complaint) }}</strong><small>{{ $complaint->type ?? 'Complaint' }}</small></td>
                                <td class="cmp-nowrap">{{ $complaint->order?->number ? '#'.$complaint->order->number : '—' }}</td>
                                <td>{{ $party }}</td>
                                <td class="cmp-nowrap">{{ $complaint->created_at->format('M j, Y') }}<small>{{ $complaint->created_at->diffForHumans() }}</small></td>
                                @php $unread = (int) ($complaint->conversations->first()?->unread_count ?? 0); @endphp
                                <td>
                                    <span class="do-badge do-badge--{{ $tone }}">{{ $statusLabel }}</span>
                                    @if($unread > 0)<small class="cmp-unread"><i></i>{{ $unread }} new {{ \Illuminate\Support\Str::plural('message', $unread) }}</small>@endif
                                </td>
                                <td class="do-actions">
                                    <button type="button" class="do-btn do-btn--soft do-btn--small" data-cmp-open="cmp-{{ $complaint->id }}">
                                        View case
                                        <svg class="do-ico do-ico--nudge" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="do-empty-cell">
                                @if($filtered)
                                    <div class="do-empty">
                                        <span class="do-empty__icon do-tone-lilac"><svg class="do-ico do-ico--float" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.500"/><path d="m16 16 4 4M9 11h4"/></svg></span>
                                        <strong>No cases match your filters</strong>
                                        <p>Remove a filter to see more cases.</p>
                                        <a class="do-btn do-btn--primary" href="{{ $clearUrl }}" data-load>Clear filters</a>
                                    </div>
                                @elseif($scope === 'against')
                                    <div class="do-empty">
                                        <span class="do-empty__icon do-tone-mint"><svg class="do-ico do-ico--float" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 4 6v6c0 4.500 3.200 7.800 8 9 4.800-1.200 8-4.500 8-9V6z"/><path d="m9 12 2 2 4-4"/></svg></span>
                                        <strong>No complaints against your shop</strong>
                                        <p>Good news. A case shows here if a buyer files one.</p>
                                    </div>
                                @else
                                    <div class="do-empty">
                                        <span class="do-empty__icon do-tone-lilac"><svg class="do-ico do-ico--float" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 21V4M5 4h11l-2 4 2 4H5"/></svg></span>
                                        <strong>You haven't filed any reports</strong>
                                        <p>Reports you file from Messages or an order show here with Admin's decision.</p>
                                        <a class="do-btn do-btn--primary" href="{{ route('seller.orders.index') }}">View orders</a>
                                    </div>
                                @endif
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($complaints->total() > 0)
                @include('seller.operations.pagination', ['records' => $complaints])
            @endif
        </section>
    </section>

    {{-- One read-only dialog per case on this page (max 8), so opening a case needs no request. --}}
    @foreach($complaints as $complaint)
        @php
            $isReport = $complaint->kind === 'user_report';
            $anonymous = $isReport && $scope === 'against';
            $tone = $tones[$complaint->status][1] ?? 'lilac';
            $statusLabel = $tones[$complaint->status][0] ?? ucfirst(str_replace('_', ' ', (string) $complaint->status));
            $outcome = match (true) {
                $complaint->decision === 'approved' && $scope === 'against' => 'Admin upheld this report and issued a warning to your shop.',
                $complaint->decision === 'approved' => 'Admin approved your report.',
                $complaint->decision === 'rejected' && $scope === 'against' => 'Admin reviewed this report and took no action.',
                $complaint->decision === 'rejected' => 'Admin reviewed your report and did not approve it.',
                $complaint->status === 'resolved' => 'Admin marked this case as resolved.',
                $complaint->status === 'in_review' => 'Admin is reviewing this case.',
                default => 'Waiting for Admin to start the review.',
            };
            $party = $scope === 'against'
                ? ($isReport ? 'A buyer (hidden)' : ($complaint->complainant?->name ?? 'Buyer removed'))
                : ($complaint->respondent?->name ?? 'Buyer removed');
            $order = $complaint->order;
            $thread = $complaint->conversations->first();
            $unread = (int) ($thread?->unread_count ?? 0);
            // Admin's internal decision notes are only shown to the person who filed the report, never to the respondent.
            $events = $complaint->activities->filter(fn ($a) => $scope === 'filed'
                || str_starts_with($a->action, 'Status changed') || str_starts_with($a->action, 'Report submitted'))->sortBy('id')->values();
            $who = fn ($actor) => str_starts_with($actor, 'admin') ? 'Vendo Admin' : (str_ends_with($actor, '#'.$me) ? 'You' : 'Buyer');
            $track = $order ? ($order->carrier_tracking_number ?: $order->tracking_number) : null;
            $payLabel = fn ($p) => strlen($p) <= 5 ? strtoupper($p) : \Illuminate\Support\Str::headline($p);
        @endphp
        <dialog class="cmp-dialog" id="cmp-{{ $complaint->id }}" aria-labelledby="cmp-title-{{ $complaint->id }}">
            <header class="cmp-dialog__head">
                <div>
                    <span class="cmp-dialog__eyebrow">{{ $isReport ? 'Account report' : 'Order complaint' }}</span>
                    <h2 id="cmp-title-{{ $complaint->id }}">{{ $caseNo($complaint) }} · {{ $complaint->type ?? 'Complaint' }}</h2>
                </div>
                <button type="button" class="cmp-x" data-cmp-close aria-label="Close case">×</button>
            </header>
            <div class="cmp-dialog__body do-detail">
                <div class="cmp-outcome do-tone-{{ $tone }}">
                    <span class="do-badge">{{ $statusLabel }}</span>
                    <p>{{ $outcome }}</p>
                </div>

                <dl class="do-facts">
                    <div><dt>{{ $scope === 'against' ? 'Raised by' : 'Reported' }}</dt><dd>{{ $party }}</dd></div>
                    <div><dt>Type</dt><dd>{{ $complaint->type ?? 'Complaint' }}</dd></div>
                    <div><dt>Submitted</dt><dd>{{ $complaint->created_at->format('M j, Y · g:i A') }}</dd></div>
                    <div><dt>Reviewed</dt><dd>{{ $complaint->reviewed_at ? $complaint->reviewed_at->format('M j, Y · g:i A') : 'Not yet' }}</dd></div>
                </dl>

                <section class="do-block">
                    <h4>What was reported</h4>
                    @if(filled($complaint->description))
                        <p class="cmp-text">{{ $complaint->description }}</p>
                    @else
                        <p class="do-note">No description was provided.</p>
                    @endif
                </section>

                @if($order)
                    <section class="do-block">
                        <h4>Order <small class="cmp-count">#{{ $order->number }}</small></h4>
                        <ul class="do-lines">
                            @foreach($order->items as $item)
                                @php $img = $item->product?->images->first(); $opts = collect([$item->color, $item->size])->filter()->join(' · '); @endphp
                                <li>
                                    @if($img)<img src="{{ asset('storage/'.$img->path) }}" alt="" loading="lazy">@else<span class="do-item__empty" aria-hidden="true"><svg class="do-ico" viewBox="0 0 24 24"><path d="m12 3 9 5v8l-9 5-9-5V8zM3 8l9 5 9-5M12 13v8"/></svg></span>@endif
                                    <span><strong>{{ $item->product?->name ?? 'Product removed' }}</strong><small>{{ $opts ? $opts.' · ' : '' }}{{ $item->quantity }} × ₱{{ number_format($item->price, 2) }}</small></span>
                                    <strong>₱{{ number_format($item->quantity * $item->price, 2) }}</strong>
                                </li>
                            @endforeach
                        </ul>
                        <dl class="do-facts cmp-order">
                            <div><dt>Order total</dt><dd>₱{{ number_format($order->payable_total, 2) }}</dd></div>
                            <div><dt>Payment</dt><dd>{{ $order->payment_mode ? $payLabel($order->payment_mode) : '—' }}</dd></div>
                            <div><dt>Order status</dt><dd>{{ $order->status_label }}</dd></div>
                            <div><dt>Delivered</dt><dd>{{ $order->delivered_at ? $order->delivered_at->format('M j, Y · g:i A') : 'Not delivered' }}</dd></div>
                            <div><dt>Courier</dt><dd>{{ $order->carrier_name ?? '—' }}</dd></div>
                            <div><dt>Tracking no.</dt><dd class="cmp-track">{{ $track ?: '—' }}@if($track)<button type="button" class="do-copy" data-copy="{{ $track }}" data-copy-label="Tracking number" aria-label="Copy tracking number"><svg class="do-ico" viewBox="0 0 24 24" aria-hidden="true"><rect x="9" y="9" width="11" height="11" rx="2"/><path d="M5 15V6a2 2 0 012-2h9"/></svg></button>@endif</dd></div>
                        </dl>
                        <div class="cmp-links">
                            <a class="do-btn do-btn--soft do-btn--small" href="{{ route('seller.orders.show', $order) }}">View order</a>
                            @if($order->status !== 'placed')
                                <a class="do-btn do-btn--soft do-btn--small" href="{{ route('seller.shipments.show', $order) }}">Track shipment</a>
                            @else
                                <span class="cmp-muted">Not shipped yet. Accept the order to start shipping.</span>
                            @endif
                        </div>
                    </section>
                @endif

                <section class="do-block">
                    <h4>Evidence <small class="cmp-count">{{ $complaint->evidences->count() }} {{ \Illuminate\Support\Str::plural('file', $complaint->evidences->count()) }}</small></h4>
                    @if($complaint->evidences->isEmpty())
                        <p class="do-note">No evidence was attached.</p>
                    @else
                        <div class="cmp-evidence">
                            @foreach($complaint->evidences as $file)
                                @php
                                    $url = route('seller.complaints.evidence', [$complaint, $file]);
                                    $ext = strtolower(pathinfo($file->path, PATHINFO_EXTENSION));
                                    $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true);
                                    $label = $anonymous ? 'Evidence '.$loop->iteration : ($file->original_filename ?: 'Evidence '.$loop->iteration);
                                @endphp
                                <a href="{{ $url }}" target="_blank" rel="noopener" class="cmp-evidence__item" aria-label="Open {{ $label }} in a new tab">
                                    <span class="cmp-evidence__thumb">
                                        @if($isImage)<img src="{{ $url }}" alt="" loading="lazy">
                                        @else<svg class="do-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M14 3H7a2 2 0 00-2 2v14a2 2 0 002 2h10a2 2 0 002-2V8z"/><path d="M14 3v5h5"/></svg><b>{{ strtoupper($ext ?: 'file') }}</b>@endif
                                    </span>
                                    <span class="cmp-evidence__name">{{ $label }}</span>
                                    <span class="cmp-evidence__open">Open file</span>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </section>

                <section class="do-block">
                    <h4>Messages from Vendo</h4>
                    @if($thread && $thread->messages_count > 0)
                        <div class="cmp-thread">
                            <p>{{ $thread->messages_count }} {{ \Illuminate\Support\Str::plural('message', $thread->messages_count) }}@if($unread > 0) · <strong>{{ $unread }} new</strong>@endif</p>
                            <a class="do-btn do-btn--primary do-btn--small" href="{{ route('seller.case-messages.show', $complaint) }}">
                                <svg class="do-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M21 12a8 8 0 01-11.600 7.100L4 20l1-4.600A8 8 0 1121 12z"/></svg>
                                {{ $complaint->status === 'resolved' ? 'View messages' : 'Open messages' }}
                            </a>
                        </div>
                    @else
                        <p class="do-note">Vendo hasn't messaged you about this case. You'll get a notification if they do.</p>
                    @endif
                </section>

                <section class="do-block">
                    <h4>Case history</h4>
                    @forelse($events as $event)
                        <div class="do-step @if($loop->last) is-last @endif">
                            <i></i>
                            <div><strong>{{ $who($event->actor) }}</strong><p>{{ $event->action }}</p><small>{{ $event->created_at->format('M j, Y · g:i A') }}</small></div>
                        </div>
                    @empty
                        <p class="do-note">No activity yet.</p>
                    @endforelse
                </section>

                <button type="button" class="do-btn do-btn--soft cmp-done" data-cmp-close>Close case</button>
            </div>
        </dialog>
    @endforeach
</x-seller.layout>
