@php
    $me = auth()->id();
    $caseNo = 'CMP-'.str_pad((string) $complaint->id, 5, '0', STR_PAD_LEFT);
    $statusLabel = ['open' => 'Open', 'in_review' => 'In progress', 'resolved' => 'Resolved'][$complaint->status] ?? ucfirst($complaint->status);
    $tone = ['open' => 'butter', 'in_review' => 'sky', 'resolved' => 'mint'][$complaint->status] ?? 'lilac';
    $isOpen = $complaint->status !== 'resolved' && $conversation->status === 'open';
    $scope = (int) $complaint->respondent_id === (int) $me ? 'against' : 'filed';
    $lastDay = null;
@endphp
<x-seller.layout title="Case messages">
    @vite(['resources/css/seller/workspace.css', 'resources/css/seller/messages.css', 'resources/js/seller/messages.js'])
    <section class="sw-page sm-page">
        <header class="sw-heading"><div><h1>Messages</h1><p>Reply to buyers, answer complaint cases, or contact Vendo Support.</p></div></header>
        <div class="sw-inbox">
            <aside class="sw-inbox__list">
                <label class="sm-search">
                    <span class="sw-sr-only">Search chats</span>
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="m16 16 4 4"/></svg>
                    <input type="search" id="smSearch" placeholder="Search chats" autocomplete="off">
                </label>
                <div id="sellerCustomerChats">@include('seller.messages.partials.customer-list')</div>
                <p class="sm-list-empty" id="smNoMatch" hidden>No chats match your search.</p>
            </aside>

            <section class="sw-inbox__thread" aria-label="Messages for case {{ $caseNo }}">
                <header class="sw-inbox__thread-head sm-case-head">
                    <div>
                        <strong>{{ $caseNo }} · {{ $complaint->type ?? 'Complaint' }}</strong>
                        <small>{{ $complaint->order ? 'Order #'.$complaint->order->number.' · ' : '' }}Only you and Vendo Support can see this chat</small>
                    </div>
                    <div class="sw-inbox__head-actions">
                        <span class="sm-badge sm-badge--{{ $tone }}">{{ $statusLabel }}</span>
                        <a class="sm-link" href="{{ route('seller.complaints.index', ['scope' => $scope, 'search' => $caseNo]) }}">View case</a>
                    </div>
                </header>

                <div class="sw-inbox__history" id="smMessages" aria-live="polite">
                    @forelse($conversation->messages as $message)
                        @php
                            $mine = (int) $message->sender_id === (int) $me;
                            $day = $message->created_at->toDateString();
                        @endphp
                        @if($day !== $lastDay)
                            <p class="sm-day">{{ $message->created_at->isToday() ? 'Today' : $message->created_at->format('M j, Y') }}</p>
                            @php $lastDay = $day; @endphp
                        @endif
                        <div class="sw-message-line {{ $mine ? 'is-mine' : '' }}">
                            @unless($mine)<span class="sw-message-avatar">V</span>@endunless
                            <div class="sw-message {{ $mine ? 'is-mine' : '' }}">
                                <small>{{ $mine ? 'You' : 'Vendo Support' }} · {{ $message->created_at->format('g:i A') }}</small>
                                <p>{{ $message->body }}</p>
                            </div>
                        </div>
                    @empty
                        <div class="sm-empty">
                            <strong>No messages yet</strong>
                            <p>Vendo Support's messages about this case show here.</p>
                        </div>
                    @endforelse
                </div>

                @if($isOpen)
                    <form class="sw-inbox__composer" method="POST" action="{{ route('seller.case-messages.store', $complaint) }}" id="smForm" data-draft-key="seller-{{ $me }}-case-{{ $complaint->id }}">
                        @csrf
                        <label class="sw-sr-only" for="smBody">Reply to Vendo Support</label>
                        <textarea id="smBody" name="body" rows="3" required maxlength="2000" placeholder="Write your reply. Share facts and any proof Vendo needs.">{{ old('body') }}</textarea>
                        @error('body')<p class="sw-error" role="alert">{{ $message }}</p>@enderror
                        <div class="sw-inbox__composer-actions">
                            <span class="sm-counter" id="smCount">0 / 2000</span>
                            <button class="sw-button" type="submit">Send reply</button>
                        </div>
                    </form>
                @else
                    <p class="sw-inbox__closed">This case is {{ $complaint->status === 'resolved' ? 'resolved' : 'closed' }}, so replies are off. Message Vendo Support from the Direct tab if you still need help.</p>
                @endif
            </section>
        </div>
    </section>
    <script>
    (() => {
        const box = document.getElementById('smMessages');
        if (box) box.scrollTop = box.scrollHeight;

        const form = document.getElementById('smForm');
        const body = document.getElementById('smBody');
        const count = document.getElementById('smCount');
        if (form && body) {
            const update = () => { count.textContent = body.value.length + ' / 2000'; count.classList.toggle('is-near', body.value.length > 1800); };
            body.addEventListener('input', update); update();
            body.addEventListener('keydown', event => { if (event.key === 'Enter' && (event.ctrlKey || event.metaKey)) form.requestSubmit(); });
            form.addEventListener('submit', () => { const button = form.querySelector('button[type=submit]'); button.disabled = true; button.classList.add('is-loading'); });
        }

        @if(session('case_message_sent'))
        document.addEventListener('DOMContentLoaded', () => window.sellerBanner && window.sellerBanner({ type: 'success', message: 'Reply sent to Vendo Support.' }));
        @endif

        async function updateList() {
            if (document.hidden) return;
            try {
                const response = await fetch(@json(route('seller.messages.customer-list', ['tab' => 'complaints', 'case' => $complaint->id])), {headers: {'Accept': 'text/html'}});
                if (response.ok) document.getElementById('sellerCustomerChats').innerHTML = await response.text();
            } catch (_) {}
        }
        setInterval(updateList, 3000);
    })();
    </script>
</x-seller.layout>
