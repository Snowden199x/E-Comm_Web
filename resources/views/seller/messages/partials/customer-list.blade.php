{{-- Inbox list: tabs plus Complaints and Customers groups. Re-rendered every few seconds so unread counts stay live. --}}
@php
    $tab = $tab ?? 'all';
    $activeCase = $activeCase ?? 0;
    $unread = $unread ?? ['direct' => 0, 'complaints' => 0];
    $tabs = ['all' => ['All', $unread['direct'] + $unread['complaints']], 'direct' => ['Direct', $unread['direct']], 'complaints' => ['Complaints', $unread['complaints']]];
    $showCases = in_array($tab, ['all', 'complaints'], true);
    $showCustomers = in_array($tab, ['all', 'direct'], true);
@endphp
<nav class="sm-tabs" role="tablist" aria-label="Message type">
    @foreach($tabs as $key => [$label, $count])
        <a role="tab" href="{{ route('seller.messages.index', $key === 'all' ? [] : ['tab' => $key]) }}" class="sm-tab @if($tab === $key) is-active @endif" aria-selected="{{ $tab === $key ? 'true' : 'false' }}">
            {{ $label }}@if($count > 0)<em class="sw-inbox__unread">{{ $count }}</em>@endif
        </a>
    @endforeach
</nav>

@if($showCases)
    @if($caseThreads->isNotEmpty())
        <p class="sw-inbox__group">Complaints</p>
        @foreach($caseThreads as $thread)
            @php
                $case = $thread->complaint;
                $caseNo = 'CMP-'.str_pad((string) $case->id, 5, '0', STR_PAD_LEFT);
                $snippet = $thread->latestMessage?->body ?: 'No messages yet';
            @endphp
            <a class="sw-inbox__entry sm-entry @if($activeCase === (int) $case->id) is-active @endif" href="{{ route('seller.case-messages.show', $case->id) }}">
                <span class="sw-inbox__initial sm-case-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 21V4M5 4h11l-2 4 2 4H5"/></svg></span>
                <span>
                    <strong>{{ $caseNo }} · {{ $case->type ?? 'Complaint' }} @if($thread->unread_count)<em class="sw-inbox__unread">{{ $thread->unread_count }}</em>@endif</strong>
                    <small>{{ $case->status === 'resolved' ? 'Resolved · ' : '' }}{{ $snippet }}</small>
                </span>
            </a>
        @endforeach
    @elseif($tab === 'complaints')
        <div class="sm-list-empty">
            <strong>No complaint messages</strong>
            <p>Vendo Support writes here when it needs details about a case.</p>
        </div>
    @endif
@endif

@if($showCustomers)
    @if($customerConversations->isNotEmpty())<p class="sw-inbox__group">Customers</p>
    @elseif($tab === 'direct')
        <div class="sm-list-empty">
            <strong>No customer chats yet</strong>
            <p>Buyers can message you from an order or your shop page.</p>
        </div>
    @endif
    @foreach($customerConversations as $customerChat)
        <a class="sw-inbox__entry sm-entry" href="{{ route('seller.marketplace-messages.show', $customerChat) }}">@if($customerChat->buyer?->profile_picture)<img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($customerChat->buyer->profile_picture) }}" alt="" class="sw-inbox__initial" style="object-fit:cover">@else<span class="sw-inbox__initial">{{ strtoupper(mb_substr($customerChat->buyer?->name ?? '?', 0, 1)) }}</span>@endif<span><strong>{{ $customerChat->buyer?->name ?? 'Buyer unavailable' }} @if($customerChat->unread_count)<em class="sw-inbox__unread">{{ $customerChat->unread_count }}</em>@endif</strong><small>{{ $customerChat->order ? 'Order #'.$customerChat->order->number : 'Shop inquiry' }} · {{ $customerChat->latestMessage?->body ?: ($customerChat->latestMessage?->shared_order_id ? 'Order shared' : 'Photo') }}</small></span></a>
    @endforeach
@endif
