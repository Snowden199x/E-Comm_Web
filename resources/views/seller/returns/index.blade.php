@php
    use App\Models\Ecommerce\OrderReturnRequest as Ret;

    $money = fn ($amount) => '₱'.number_format((float) $amount, 2);
    $pending = (int) ($counts[Ret::STATUS_PENDING] ?? 0);
    $approved = (int) ($counts[Ret::STATUS_APPROVED] ?? 0);
    $declined = (int) ($counts[Ret::STATUS_REJECTED] ?? 0);
    $tabs = [['', 'All', $pending + $approved + $declined], ['pending', 'Waiting for you', $pending], ['approved', 'Approved', $approved], ['declined', 'Declined', $declined]];
    $filtered = $status !== '' || $reason !== '' || $q !== '';
    $clearUrl = route('seller.returns.index');
    // One status color per state, same mapping as every other page: waiting = butter, in progress = sky, done = mint, failed = rose, new = lilac.
    $badge = function ($r) {
        if ($r->status === Ret::STATUS_PENDING) return ['butter', 'Waiting for you'];
        if ($r->status === Ret::STATUS_REJECTED) return ['rose', 'Declined'];
        return match ($r->return_status) {
            Ret::RETURN_STATUS_PENDING_PICKUP => ['butter', $r->return_status_label],
            Ret::RETURN_STATUS_PICKED_UP => ['sky', $r->return_status_label],
            Ret::RETURN_STATUS_DELIVERED_TO_SELLER => ['mint', $r->return_status_label],
            default => ['lilac', 'Approved'],
        };
    };
@endphp
<x-seller.layout title="Returns">
    @vite(['resources/css/seller/operations.css', 'resources/css/seller/delivered-orders.css', 'resources/css/seller/returns-theme.css', 'resources/js/seller/returns.js'])
    <section class="ops-page do-page rt-page" data-rt-page>
        <header class="do-head">
            <div>
                <h1>Returns</h1>
                <p>Review buyer return requests. Approving one takes the order back, restores the stock and records the refund.</p>
            </div>
            <a class="do-btn do-btn--soft" href="{{ route('seller.completed-orders.index') }}">
                <svg class="do-ico do-ico--slide" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 7h11v9H3zM14 10h4l3 3v3h-7zM7 19a1.8 1.8 0 100-3.6A1.8 1.8 0 007 19zm10 0a1.8 1.8 0 100-3.6 1.8 1.8 0 000 3.6z"/></svg>
                View delivered orders
            </a>
        </header>

        <div class="rt-stats" role="list">
            <div class="do-stat" role="listitem">
                <span class="do-stat__icon do-tone-butter"><svg class="do-ico do-ico--pop" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></span>
                <span class="do-stat__text">Waiting for you<strong>{{ number_format($pending) }}</strong></span>
            </div>
            <div class="do-stat" role="listitem">
                <span class="do-stat__icon do-tone-sky"><svg class="do-ico do-ico--spin" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 7h11v9H3zM14 10h4l3 3v3h-7zM7 19a1.8 1.8 0 100-3.6A1.8 1.8 0 007 19zm10 0a1.8 1.8 0 100-3.6 1.8 1.8 0 000 3.6z"/></svg></span>
                <span class="do-stat__text">On the way back<strong>{{ number_format($onTheWay) }}</strong></span>
            </div>
            <div class="do-stat" role="listitem">
                <span class="do-stat__icon do-tone-mint"><svg class="do-ico do-ico--pop" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="6" width="18" height="12" rx="2"/><circle cx="12" cy="12" r="2.6"/></svg></span>
                <span class="do-stat__text">Refunded<strong>{{ $money($refundedCents / 100) }}</strong></span>
            </div>
        </div>

        {{-- Messages become top banners (see returns.js). The inline copy only shows if scripts fail. --}}
        @if (session('success'))<p hidden data-rt-flash="{{ session('success') }}"></p>@endif
        @foreach ($errors->all() as $message)<p hidden data-rt-error="{{ $message }}"></p>@endforeach
        <noscript>
            @if (session('success'))<div class="do-alert do-alert--success"><div><p>{{ session('success') }}</p></div></div>@endif
            @if ($errors->any())<div class="do-alert do-alert--error"><div>@foreach ($errors->all() as $message)<p>{{ $message }}</p>@endforeach</div></div>@endif
        </noscript>

        <form id="rtFilters" method="GET" action="{{ $clearUrl }}" class="do-filters" role="search">
            @if ($status !== '')<input type="hidden" name="status" value="{{ $status }}">@endif
            <label class="do-search">
                <span class="ops-sr-only">Search returns</span>
                <svg class="do-ico" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="m16 16 4 4"/></svg>
                <input type="search" name="q" value="{{ $q }}" placeholder="Search order or buyer, then press Enter" maxlength="100" autocomplete="off">
            </label>
            <label>
                <span class="ops-sr-only">Reason</span>
                <select name="reason" class="rt-select" data-rt-auto>
                    <option value="">All reasons</option>
                    @foreach (Ret::REASONS as $key => $label)<option value="{{ $key }}" @selected($reason === $key)>{{ $label }}</option>@endforeach
                </select>
            </label>
        </form>

        <div class="do-chips" id="rtChips" role="group" aria-label="Filter by status">
            @foreach ($tabs as [$key, $label, $total])
                <a href="{{ route('seller.returns.index', array_filter(['status' => $key, 'q' => $q, 'reason' => $reason])) }}" data-rt-nav
                    class="do-chip @if ($status === $key) is-active @endif" @if ($status === $key) aria-current="true" @endif>
                    {{ $label }} <span class="er-count">{{ $total }}</span>
                </a>
            @endforeach
            @if ($filtered)
                <a href="{{ $clearUrl }}" data-rt-nav class="do-chip do-chip--clear">
                    <svg class="do-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
                    Clear filters
                </a>
            @endif
        </div>

        <section class="do-card do-table-card" id="rtCard" aria-label="Return requests" aria-live="polite">
            <div class="ops-table-scroll">
                <table class="do-table rt-table">
                    <thead><tr><th>Order</th><th>Buyer</th><th>Reason</th><th>Refund</th><th>Status</th><th><span class="ops-sr-only">Actions</span></th></tr></thead>
                    <tbody class="rt-skel" aria-hidden="true">
                        @for ($i = 0; $i < 5; $i++)<tr class="do-skel"><td><i></i></td><td><i></i></td><td><i></i></td><td><i></i></td><td><i></i></td><td><i></i></td></tr>@endfor
                    </tbody>
                    <tbody class="rt-real">
                    @forelse ($returns as $r)
                        @php
                            [$tone, $label] = $badge($r);
                            $order = $r->order;
                            $first = $order?->items->first();
                            $refund = $r->status === Ret::STATUS_APPROVED ? $money($r->refund_cents / 100) : ($order ? $money($order->payable_total) : '—');
                            $canReceive = $r->status === Ret::STATUS_APPROVED && $r->return_status === Ret::RETURN_STATUS_PICKED_UP;
                        @endphp
                        <tr class="do-row rt-row" tabindex="0" data-rt-row="{{ $r->id }}"
                            data-approve-url="{{ route('seller.returns.approve', $r) }}" data-reject-url="{{ route('seller.returns.reject', $r) }}" data-received-url="{{ route('seller.returns.received', $r) }}"
                            data-order="#{{ $order?->number }}" data-buyer="{{ $r->buyer?->name ?? 'Buyer' }}" data-refund="{{ $refund }}">
                            <td>
                                <strong>#{{ $order?->number }}</strong>
                                <small>Requested {{ $r->created_at?->format('M j, Y') }}</small>
                            </td>
                            <td>{{ $r->buyer?->name ?? 'Buyer' }}</td>
                            <td>
                                {{ $r->reason_label }}
                                @if (filled($r->details))<small class="rt-clamp">{{ $r->details }}</small>@endif
                            </td>
                            <td><strong>{{ $refund }}</strong>@if ($r->status === Ret::STATUS_PENDING)<small>If approved</small>@endif</td>
                            <td>
                                <span class="do-badge do-tone-{{ $tone }}">{{ $label }}</span>
                                @if ($r->decided_at)<small>{{ ucfirst($r->status === Ret::STATUS_REJECTED ? 'declined' : 'approved') }} {{ $r->decided_at->format('M j, Y') }}</small>@endif
                            </td>
                            <td class="do-actions">
                                @if ($r->status === Ret::STATUS_PENDING)
                                    <button type="button" class="do-btn do-btn--primary do-btn--small" data-rt-approve>
                                        <svg class="do-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg>Approve return
                                    </button>
                                    <button type="button" class="do-btn do-btn--rose do-btn--small" data-rt-decline>Decline</button>
                                @elseif ($canReceive)
                                    <button type="button" class="do-btn do-btn--primary do-btn--small" data-rt-receive>Mark received</button>
                                @else
                                    <button type="button" class="do-btn do-btn--soft do-btn--small" data-rt-open>View details
                                        <svg class="do-ico do-ico--nudge" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>
                                    </button>
                                @endif

                                <template data-rt-detail>
                                    <div class="rt-detail">
                                        <div class="rt-detail__head">
                                            <div><h3>#{{ $order?->number }}</h3><p>{{ $order?->status_label }} · {{ $r->buyer?->name ?? 'Buyer' }}</p></div>
                                            <span class="do-badge do-tone-{{ $tone }}">{{ $label }}</span>
                                        </div>
                                        <dl class="do-facts">
                                            <div><dt>Reason</dt><dd>{{ $r->reason_label }}</dd></div>
                                            <div><dt>{{ $r->status === Ret::STATUS_APPROVED ? 'Refund' : 'Refund if approved' }}</dt><dd><strong>{{ $refund }}</strong></dd></div>
                                            <div><dt>Requested</dt><dd>{{ $r->created_at?->format('M j, Y g:i A') }}</dd></div>
                                            <div><dt>{{ $r->status === Ret::STATUS_PENDING ? 'Decision' : 'Decided' }}</dt><dd>{{ $r->decided_at?->format('M j, Y g:i A') ?? 'Not decided yet' }}</dd></div>
                                            @if (filled($r->details))<div class="do-full"><dt>Buyer's details</dt><dd>{{ $r->details }}</dd></div>@endif
                                            @if (filled($r->decision_note))<div class="do-full"><dt>Your note to the buyer</dt><dd>{{ $r->decision_note }}</dd></div>@endif
                                        </dl>
                                        @if ($order && $order->items->isNotEmpty())
                                            <div class="do-block">
                                                <h4>Items on this order</h4>
                                                <ul class="do-lines">
                                                    @foreach ($order->items as $item)
                                                        @php $thumb = $item->product?->images?->first(); @endphp
                                                        <li>
                                                            @if ($thumb)<img src="{{ asset('storage/'.$thumb->path) }}" alt="" loading="lazy">@else<span class="do-item__empty" aria-hidden="true"><svg class="do-ico" viewBox="0 0 24 24"><path d="m12 3 9 5v8l-9 5-9-5V8zM3 8l9 5 9-5M12 13v8"/></svg></span>@endif
                                                            <span><strong>{{ $item->product?->name ?? 'Product removed' }}</strong><small>{{ $item->quantity }} × {{ $money($item->price) }}</small></span>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endif
                                        @if ($r->status === Ret::STATUS_APPROVED)
                                            <div class="do-block">
                                                <h4>Return trip</h4>
                                                <p class="rt-trip">{{ $r->return_status_label }}@if ($r->pickupRider) · Rider {{ $r->pickupRider->name }}@endif</p>
                                            </div>
                                        @endif
                                        @if ($r->status === Ret::STATUS_PENDING)
                                            <div class="do-detail__actions">
                                                <button type="button" class="do-btn do-btn--primary" data-rt-approve>Approve return</button>
                                                <button type="button" class="do-btn do-btn--rose" data-rt-decline>Decline return</button>
                                            </div>
                                        @elseif ($canReceive)
                                            <div class="do-detail__actions"><button type="button" class="do-btn do-btn--primary" data-rt-receive>Mark received</button></div>
                                        @endif
                                    </div>
                                </template>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="do-empty-cell">
                            @if ($filtered)
                                <div class="do-empty">
                                    <span class="do-empty__icon do-tone-lilac"><svg class="do-ico do-ico--float" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="m16 16 4 4M9 11h4"/></svg></span>
                                    <strong>No returns match your filters</strong>
                                    <p>Remove a filter or search for a different order or buyer.</p>
                                    <a class="do-btn do-btn--primary" href="{{ $clearUrl }}" data-rt-nav>Clear filters</a>
                                </div>
                            @else
                                <div class="do-empty">
                                    <span class="do-empty__icon do-tone-mint"><svg class="do-ico do-ico--float" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 14 4 9l5-5"/><path d="M4 9h10a6 6 0 010 12h-3"/></svg></span>
                                    <strong>No return requests yet</strong>
                                    <p>Requests from buyers appear here and in your notifications.</p>
                                    <a class="do-btn do-btn--primary" href="{{ route('seller.completed-orders.index') }}">View delivered orders</a>
                                </div>
                            @endif
                        </td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if ($returns->hasPages() || $returns->total() > 0)
                @include('seller.operations.pagination', ['records' => $returns])
            @endif
        </section>
    </section>

    {{-- Dialogs. Each carries its own copy of the tokens, so they do not depend on the page wrapper. --}}
    <dialog class="rt-dialog" id="rtConfirm" aria-labelledby="rtConfirmTitle">
        <form method="POST" action="" class="rt-dialog__body" data-rt-form>
            @csrf @method('PATCH')
            <div class="rt-dialog__top">
                <span class="rt-dialog__icon do-tone-mint"><svg class="do-ico" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8.5 12.5 2.5 2.5 4.5-5"/></svg></span>
                <button type="button" class="rt-dialog__close" data-rt-close aria-label="Close"><svg class="do-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg></button>
            </div>
            <div><h2 id="rtConfirmTitle" data-rt-title>Approve this return?</h2><p data-rt-text></p></div>
            <div class="rt-dialog__actions">
                <button type="button" class="do-btn do-btn--soft" data-rt-close>Keep waiting</button>
                <button type="submit" class="do-btn do-btn--primary" data-rt-submit>Approve return</button>
            </div>
        </form>
    </dialog>

    <dialog class="rt-dialog" id="rtDecline" aria-labelledby="rtDeclineTitle">
        <form method="POST" action="" class="rt-dialog__body" data-rt-form novalidate>
            @csrf @method('PATCH')
            <input type="hidden" name="return_id" value="">
            <div class="rt-dialog__top">
                <span class="rt-dialog__icon do-tone-rose"><svg class="do-ico" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M9 9l6 6M15 9l-6 6"/></svg></span>
                <button type="button" class="rt-dialog__close" data-rt-close aria-label="Close"><svg class="do-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg></button>
            </div>
            <div><h2 id="rtDeclineTitle">Decline this return?</h2><p data-rt-text>The buyer sees your reason.</p></div>
            <div class="rt-field">
                <label for="rtNote">Reason for the buyer</label>
                <div class="rt-quick" role="group" aria-label="Quick reasons">
                    <button type="button" data-rt-quick="The item shows signs of use, so it cannot be returned.">Signs of use</button>
                    <button type="button" data-rt-quick="The item matches the listing, so the return does not apply.">Matches listing</button>
                    <button type="button" data-rt-quick="The return window for this order has closed.">Window closed</button>
                </div>
                <textarea id="rtNote" name="decision_note" rows="3" maxlength="450" required placeholder="Say what you found and why you are declining."></textarea>
                <div class="rt-hint"><span class="rt-error" data-rt-note-error hidden></span><span data-rt-count>0 / 450</span></div>
            </div>
            <div class="rt-dialog__actions">
                <button type="button" class="do-btn do-btn--soft" data-rt-close>Keep waiting</button>
                <button type="submit" class="do-btn do-btn--rose-solid" data-rt-submit>Decline return</button>
            </div>
        </form>
    </dialog>

    <dialog class="rt-dialog rt-panel" id="rtPanel" aria-labelledby="rtPanelTitle">
        <header class="rt-panel__head">
            <h2 id="rtPanelTitle">Return details</h2>
            <button type="button" class="rt-dialog__close" data-rt-close aria-label="Close details"><svg class="do-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg></button>
        </header>
        <div class="rt-panel__body" id="rtPanelBody"></div>
    </dialog>

    @if (old('return_id') && $errors->has('decision_note'))<p hidden data-rt-reopen="{{ old('return_id') }}" data-rt-note="{{ old('decision_note') }}"></p>@endif
</x-seller.layout>
