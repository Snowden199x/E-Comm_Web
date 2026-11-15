@use('App\Models\Ecommerce\OrderReturnRequest', 'Ret')
@php
    [$tone, $label] = $badge($r);
    $order = $r->order;
    $items = $order?->items ?? collect();
    $first = $items->first();
    $thumb = $first?->product?->images?->first();
    $summary = $first ? (($first->product?->name ?? 'Product removed').($items->count() > 1 ? ' +'.($items->count() - 1).' more' : '')) : null;
    $refund = $r->status === Ret::STATUS_APPROVED ? $money($r->refund_cents / 100) : ($order ? $money($order->payable_total) : '—');
    $canReceive = $r->status === Ret::STATUS_APPROVED && $r->return_status === Ret::RETURN_STATUS_PICKED_UP;
    $buyerName = $r->buyer?->name ?? 'Buyer';

    // Timeline: what has happened and what is next.
    $steps = [['Requested by the buyer', $r->created_at, 'done']];
    if ($r->status === Ret::STATUS_PENDING) {
        $steps[] = ['Waiting for your decision', null, 'current'];
    } elseif ($r->status === Ret::STATUS_REJECTED) {
        $steps[] = ['You declined the return', $r->decided_at, 'bad'];
    } else {
        $steps[] = ['You approved the return', $r->decided_at, 'done'];
        $pickup = $r->picked_up_at;
        $back = $r->delivered_to_seller_at;
        $steps[] = ['Rider collected the parcel', $pickup, $pickup ? 'done' : 'current'];
        $steps[] = ['Parcel handed back to you', $back, $back ? 'done' : ($pickup ? 'current' : 'todo')];
    }
@endphp
<tr class="do-row rt-row" tabindex="0" data-rt-row="{{ $r->id }}"
    data-approve-url="{{ route('seller.returns.approve', $r) }}" data-reject-url="{{ route('seller.returns.reject', $r) }}" data-received-url="{{ route('seller.returns.received', $r) }}"
    data-order="#{{ $order?->number }}" data-buyer="{{ $buyerName }}" data-refund="{{ $refund }}">
    <td data-label="Order">
        <span class="rt-order">
            <span class="rt-thumb">
                @if ($thumb)<img src="{{ asset('storage/'.$thumb->path) }}" alt="" loading="lazy">@else<svg class="do-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="m12 3 9 5v8l-9 5-9-5V8zM3 8l9 5 9-5M12 13v8"/></svg>@endif
            </span>
            <span class="rt-order__text">
                <strong>#{{ $order?->number }}</strong>
                <small>{{ $buyerName }}</small>
                @if ($summary)<small class="rt-item">{{ $summary }}</small>@endif
            </span>
        </span>
    </td>
    <td data-label="Reason">
        {{ $r->reason_label }}
        @if (filled($r->details))<small class="rt-clamp">{{ $r->details }}</small>@endif
    </td>
    <td data-label="Refund"><strong>{{ $refund }}</strong>@if ($r->status === Ret::STATUS_PENDING)<small>If approved</small>@endif</td>
    <td data-label="Status">
        <span class="do-badge do-tone-{{ $tone }}">{{ $label }}</span>
        @if ($r->status === Ret::STATUS_PENDING)
            <small title="{{ $r->created_at?->format('M j, Y g:i A') }}">Requested {{ $r->created_at?->diffForHumans() }}</small>
        @elseif ($r->decided_at)
            <small>{{ $r->status === Ret::STATUS_REJECTED ? 'Declined' : 'Approved' }} {{ $r->decided_at->format('M j, Y') }}</small>
        @endif
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
                    <div><h3>#{{ $order?->number }}</h3><p>{{ $buyerName }} · {{ $order?->status_label }}</p></div>
                    <span class="do-badge do-tone-{{ $tone }}">{{ $label }}</span>
                </div>

                <div class="rt-summary">
                    <div><span>Refund{{ $r->status === Ret::STATUS_APPROVED ? '' : ' if approved' }}</span><strong>{{ $refund }}</strong></div>
                    <div><span>Reason</span><strong>{{ $r->reason_label }}</strong></div>
                </div>

                @if (filled($r->details))
                    <div class="do-block"><h4>Buyer's details</h4><p class="rt-quote">{{ $r->details }}</p></div>
                @endif
                @if (filled($r->decision_note))
                    <div class="do-block"><h4>Your note to the buyer</h4><p class="rt-quote">{{ $r->decision_note }}</p></div>
                @endif

                <div class="do-block">
                    <h4>Progress</h4>
                    <ol class="rt-timeline">
                        @foreach ($steps as [$stepLabel, $stepTime, $stepState])
                            <li class="rt-step rt-step--{{ $stepState }}">
                                <span class="rt-step__dot" aria-hidden="true"></span>
                                <span class="rt-step__text">
                                    <strong>{{ $stepLabel }}</strong>
                                    @if ($stepTime)<small>{{ $stepTime->format('M j, Y g:i A') }}</small>@endif
                                </span>
                            </li>
                        @endforeach
                    </ol>
                    @if ($r->status === Ret::STATUS_APPROVED && $r->pickupRider)<p class="rt-trip">Rider: {{ $r->pickupRider->name }}</p>@endif
                </div>

                @if ($items->isNotEmpty())
                    <div class="do-block">
                        <h4>Items on this order</h4>
                        <ul class="do-lines">
                            @foreach ($items as $item)
                                @php $itemThumb = $item->product?->images?->first(); @endphp
                                <li>
                                    @if ($itemThumb)<img src="{{ asset('storage/'.$itemThumb->path) }}" alt="" loading="lazy">@else<span class="do-item__empty" aria-hidden="true"><svg class="do-ico" viewBox="0 0 24 24"><path d="m12 3 9 5v8l-9 5-9-5V8zM3 8l9 5 9-5M12 13v8"/></svg></span>@endif
                                    <span><strong>{{ $item->product?->name ?? 'Product removed' }}</strong><small>{{ $item->quantity }} × {{ $money($item->price) }}</small></span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="do-detail__actions">
                    @if ($r->status === Ret::STATUS_PENDING)
                        <button type="button" class="do-btn do-btn--primary" data-rt-approve>Approve return</button>
                        <button type="button" class="do-btn do-btn--rose" data-rt-decline>Decline return</button>
                    @elseif ($canReceive)
                        <button type="button" class="do-btn do-btn--primary" data-rt-receive>Mark received</button>
                    @endif
                    @if ($order)<a class="do-btn do-btn--soft" href="{{ route('seller.orders.show', $order) }}">View order</a>@endif
                </div>
            </div>
        </template>
    </td>
</tr>
