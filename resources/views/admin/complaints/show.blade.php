<x-admin.layout title="Complaint Details">
    @vite('resources/css/admin/complaints.css')

    @php
        $isReport = $complaint->kind === 'user_report';
        $caseNo = 'CMP-' . $complaint->created_at->format('Y') . '-' . str_pad($complaint->id, 5, '0', STR_PAD_LEFT);
        $roleLabel = fn ($role) => $role === 'logistics_center' ? 'Logistics' : ucfirst((string) $role);
        $card = 'rounded-2xl border border-[#ece4ec] bg-white p-5';
        $flash = [
            'status_updated' => 'Case status updated.',
            'report_reviewed' => 'Report decision saved.',
        ][session('confirmation')] ?? null;
        $timeline = $complaint->activities->sortByDesc('created_at')->values();
        $evidences = $complaint->evidences;
    @endphp

    <div class="mx-auto w-full max-w-[1280px] p-4 sm:p-6"
        x-data="{ toast: @js($flash), init() { if (this.toast) setTimeout(() => this.toast = null, 4000); } }">

        {{-- Confirmation after a status change or decision --}}
        <div x-show="toast" x-cloak x-transition.opacity role="status"
            class="fixed right-4 top-24 z-50 flex items-center gap-2 rounded-xl bg-[#2B1730] px-4 py-3 text-sm font-medium text-white shadow-lg">
            <x-admin.icon name="check-circle" class="h-4 w-4 text-[#e8c874]" />
            <span x-text="toast"></span>
        </div>

        <a href="{{ route('admin.complaints.index') }}" x-target.push="main-content sidebar"
            class="mb-2 inline-flex items-center gap-1.5 rounded-lg text-sm font-medium text-[#3b1735] hover:underline
                   focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40">
            <x-admin.icon name="chevron-left" class="h-4 w-4" /> All complaints and disputes
        </a>
        <h1 class="mb-5 font-display text-2xl font-semibold text-[#2B1730] sm:text-3xl">Complaint Details</h1>

        <div class="grid grid-cols-1 items-start gap-5 lg:grid-cols-2">

            {{-- ================= LEFT ================= --}}
            <div class="space-y-5">

                {{-- Header card --}}
                <section class="{{ $card }} cs-card flex flex-wrap items-center gap-4" style="--i: 0" aria-label="Case header">
                    <span class="flex h-16 w-16 flex-shrink-0 items-center justify-center rounded-2xl bg-[#C98585] text-[#7A1414]">
                        <x-admin.icon :name="$isReport ? 'flag' : 'alert-circle'" class="h-8 w-8" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs text-gray-500">{{ $isReport ? 'Report ID' : 'Complaint ID' }}</p>
                        <p class="font-display text-2xl font-semibold leading-tight text-[#9B111E]">{{ $caseNo }}</p>
                        <p class="mt-1 text-xs text-gray-500">Filed on {{ $complaint->created_at->format('M j, Y · g:i A') }}</p>
                    </div>
                    <div class="flex-shrink-0">@include('admin.complaints.partials.status-badge')</div>
                </section>

                {{-- Case actions (order complaints) --}}
                @if (! $isReport && $complaint->status !== 'resolved')
                    <section class="{{ $card }} cs-card flex flex-wrap items-center justify-between gap-3" style="--i: 1" aria-label="Case actions">
                        <p class="text-sm text-gray-600">Update where this case stands.</p>
                        <div class="flex flex-wrap gap-2.5">
                            @if ($complaint->status === 'open')
                                <form method="POST" action="{{ route('admin.complaints.update-status', $complaint) }}">
                                    @csrf
                                    <input type="hidden" name="status" value="in_review">
                                    <button type="submit"
                                        class="h-10 rounded-xl border border-[#ddd0e0] bg-white px-4 text-sm font-medium text-[#3b1735] transition-colors duration-150 hover:bg-[#F7F1F7]
                                               focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40">Mark in progress</button>
                                </form>
                            @endif
                            <form method="POST" action="{{ route('admin.complaints.update-status', $complaint) }}">
                                @csrf
                                <input type="hidden" name="status" value="resolved">
                                <button type="submit"
                                    class="h-10 rounded-xl bg-green-700 px-4 text-sm font-medium text-white transition-colors duration-150 hover:bg-green-800
                                           focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-green-700/40 focus-visible:ring-offset-2">Mark resolved</button>
                            </form>
                        </div>
                    </section>
                @endif

                {{-- Account report decision --}}
                @if ($isReport)
                    <section class="{{ $card }} cs-card" style="--i: 1" aria-labelledby="review-title">
                        <h2 id="review-title" class="mb-2 font-display text-base font-semibold text-[#2B1730]">Report review</h2>
                        @if ($complaint->decision)
                            <p class="flex items-center gap-2 text-sm text-gray-700">
                                <x-admin.icon :name="$complaint->decision === 'approved' ? 'shield-check' : 'x-circle'" class="h-4 w-4 {{ $complaint->decision === 'approved' ? 'text-green-700' : 'text-gray-500' }}" />
                                Decision: <strong>{{ ucfirst($complaint->decision) }}</strong> on {{ $complaint->reviewed_at?->format('M j, Y g:i A') }}.
                            </p>
                        @else
                            <p class="mb-4 text-sm text-gray-600">Review the description and order context before deciding. Approving sends a warning to the reported account.</p>
                            @if ($complaint->status === 'open')
                                <form method="POST" action="{{ route('admin.complaints.update-status', $complaint) }}" class="mb-4">
                                    @csrf<input type="hidden" name="status" value="in_review">
                                    <button type="submit" class="h-10 rounded-xl border border-[#ddd0e0] px-4 text-sm font-medium text-[#3b1735] hover:bg-[#F7F1F7]">Mark in progress</button>
                                </form>
                            @endif
                            <form method="POST" action="{{ route('admin.complaints.decision', $complaint) }}" data-draft-key="admin-{{ auth('admin')->id() }}-complaint-decision-{{ $complaint->id }}"
                                x-data="{ note: @js(old('note', '')) }">
                                @csrf
                                <label for="reviewNote" class="mb-1.5 block text-sm font-medium text-gray-700">Review note</label>
                                <textarea id="reviewNote" name="note" x-model="note" minlength="10" maxlength="500" rows="3" required
                                    class="mb-1 w-full rounded-xl border border-[#ddd0e0] p-3 text-sm text-[#2B1730] placeholder:text-gray-400 focus:border-[#3b1735] focus:outline-none focus:ring-2 focus:ring-[#3b1735]/20"
                                    placeholder="Explain the decision for the case record."></textarea>
                                <p class="mb-3 text-right text-xs tabular-nums text-gray-400"><span x-text="note.length">0</span>/500 · at least 10 characters</p>
                                @error('note')<p class="mb-3 text-sm text-red-600">{{ $message }}</p>@enderror
                                <div class="flex flex-wrap gap-2.5">
                                    <button type="submit" name="decision" value="approved" class="h-10 rounded-xl bg-[#3b1735] px-4 text-sm font-semibold text-white transition-colors duration-150 hover:bg-[#4d1f45]">Approve and warn</button>
                                    <button type="submit" name="decision" value="rejected" class="h-10 rounded-xl border border-[#ddd0e0] px-4 text-sm font-semibold text-gray-700 transition-colors duration-150 hover:bg-[#F7F1F7]">Reject report</button>
                                </div>
                            </form>
                        @endif
                    </section>
                @endif

                {{-- Summary --}}
                <section class="{{ $card }} cs-card" style="--i: 2" aria-labelledby="summary-title">
                    <h2 id="summary-title" class="mb-4 font-display text-base font-semibold text-[#2B1730]">{{ $isReport ? 'Report Summary' : 'Complaint Summary' }}</h2>
                    <dl class="mb-5 grid grid-cols-1 gap-4 text-sm sm:grid-cols-3">
                        <div>
                            <dt class="mb-1.5 text-xs text-gray-500">{{ $isReport ? 'Report Type' : 'Complaint Type' }}</dt>
                            <dd>@include('admin.complaints.partials.type-badge')</dd>
                        </div>
                        <div>
                            <dt class="mb-1.5 text-xs text-gray-500">Order ID</dt>
                            <dd class="font-medium text-[#9B111E]">{{ $complaint->order ? 'ORD-' . str_pad($complaint->order->id, 4, '0', STR_PAD_LEFT) : 'Not linked to an order' }}</dd>
                        </div>
                        <div>
                            <dt class="mb-1.5 text-xs text-gray-500">Amount</dt>
                            <dd class="font-medium tabular-nums text-[#2B1730]">{{ $complaint->order ? '₱' . number_format($complaint->order->total_amount ?? 0, 2) : '—' }}</dd>
                        </div>
                    </dl>
                    <h3 class="mb-1.5 text-xs text-gray-500">Description</h3>
                    @if (filled($complaint->description))
                        <p class="whitespace-pre-wrap text-sm font-medium leading-relaxed text-[#2B1730] [overflow-wrap:anywhere]">{{ $complaint->description }}</p>
                    @else
                        <p class="rounded-xl border border-dashed border-[#e2d6e5] p-4 text-sm text-gray-500">No description was provided.</p>
                    @endif
                </section>

                {{-- Order information --}}
                @if ($complaint->order)
                    <section class="{{ $card }} cs-card" style="--i: 3" aria-labelledby="order-title">
                        <h2 id="order-title" class="mb-4 font-display text-base font-semibold text-[#2B1730]">Order Information</h2>
                        @foreach ($complaint->order->items as $item)
                            @if ($item->product)
                                <button type="button" onclick="document.getElementById('complaintProduct{{ $item->id }}').showModal()"
                                    class="mb-2 flex w-full items-center gap-3 rounded-xl p-2 text-left transition hover:bg-[#FBF8FB] focus-visible:outline-2 focus-visible:outline-[#603168]">
                                    <span class="h-14 w-14 flex-shrink-0 overflow-hidden rounded-lg bg-gray-100">
                                        @if ($item->product->images->first())<img src="{{ Storage::url($item->product->images->first()->path) }}" alt="" class="h-full w-full object-cover">@endif
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <strong class="block text-sm text-[#2B1730] [overflow-wrap:anywhere]">{{ $item->product->name }}</strong>
                                        <small class="block text-xs text-gray-500">Qty {{ $item->quantity }} · View product details</small>
                                    </span>
                                    <span class="text-sm font-semibold tabular-nums text-[#2B1730]">₱{{ number_format($item->price, 2) }}</span>
                                </button>
                                <dialog id="complaintProduct{{ $item->id }}" class="w-[min(680px,calc(100vw-2rem))] max-h-[calc(100dvh-2rem)] overflow-y-auto rounded-2xl border border-gray-100 p-0 text-gray-900 shadow-2xl backdrop:bg-black/55" aria-labelledby="complaintProductTitle{{ $item->id }}">
                                    <div class="sticky top-0 z-10 flex items-center justify-between border-b bg-white px-5 py-4">
                                        <h4 id="complaintProductTitle{{ $item->id }}" class="min-w-0 break-words text-lg font-bold">{{ $item->product->name }}</h4>
                                        <button type="button" onclick="this.closest('dialog').close()" aria-label="Close product details" class="ml-4 text-2xl leading-none text-gray-500">&times;</button>
                                    </div>
                                    <div class="space-y-5 p-5">
                                        @if ($item->product->images->isNotEmpty())
                                            <div class="flex gap-2 overflow-x-auto">
                                                @foreach ($item->product->images as $image)<img src="{{ Storage::url($image->path) }}" alt="{{ $item->product->name }} photo {{ $loop->iteration }}" class="h-32 w-32 flex-none rounded-lg bg-gray-100 object-cover">@endforeach
                                            </div>
                                        @endif
                                        <div class="grid grid-cols-2 gap-4 text-sm sm:grid-cols-3">
                                            <div><span class="block text-xs text-gray-500">Unit price</span><strong>₱{{ number_format($item->price, 2) }}</strong></div>
                                            <div><span class="block text-xs text-gray-500">Quantity</span><strong>{{ $item->quantity }}</strong></div>
                                            @if ($item->color)<div><span class="block text-xs text-gray-500">Color</span><strong>{{ $item->color }}</strong></div>@endif
                                            @if ($item->size)<div><span class="block text-xs text-gray-500">Size</span><strong>{{ $item->size }}</strong></div>@endif
                                            @if ($item->product->brand)<div><span class="block text-xs text-gray-500">Brand</span><strong>{{ $item->product->brand }}</strong></div>@endif
                                            @if ($item->product->material)<div><span class="block text-xs text-gray-500">Material</span><strong>{{ $item->product->material }}</strong></div>@endif
                                            @if ($item->product->weight)<div><span class="block text-xs text-gray-500">Weight</span><strong>{{ $item->product->weight }}</strong></div>@endif
                                            @if ($item->product->country_of_origin)<div><span class="block text-xs text-gray-500">Country of origin</span><strong>{{ $item->product->country_of_origin }}</strong></div>@endif
                                        </div>
                                        <div><h5 class="mb-2 text-sm font-semibold">Description</h5><p class="whitespace-pre-wrap break-words text-sm leading-6 text-gray-700">{{ $item->product->description ?: 'No description provided.' }}</p></div>
                                    </div>
                                </dialog>
                            @else
                                <div class="mb-2 flex items-center justify-between rounded-xl bg-[#FBF8FB] p-3 text-sm"><span>Product removed · Qty {{ $item->quantity }}</span><strong class="tabular-nums">₱{{ number_format($item->price, 2) }}</strong></div>
                            @endif
                        @endforeach
                        <dl class="mt-4 grid grid-cols-2 gap-4 border-t border-[#f3edf4] pt-4 text-sm">
                            <div><dt class="mb-1 text-xs text-gray-500">Order Date</dt><dd class="font-medium text-[#2B1730]">{{ $complaint->order->created_at->format('M j, Y · g:i A') }}</dd></div>
                            <div><dt class="mb-1 text-xs text-gray-500">Payment Method</dt><dd class="font-medium text-[#2B1730]">{{ $complaint->order->payment_mode ?? '—' }}</dd></div>
                        </dl>
                    </section>
                @endif

                {{-- Timeline (newest first) --}}
                <section class="{{ $card }} cs-card" style="--i: 4" aria-labelledby="timeline-title">
                    <h2 id="timeline-title" class="mb-5 font-display text-base font-semibold text-[#2B1730]">Timeline</h2>
                    <ol class="cs-timeline space-y-5">
                        @forelse ($timeline as $activity)
                            @php
                                $actionText = strtolower($activity->action);
                                $activityIcon = str_contains($actionText, 'status') ? 'refresh' : (str_contains($actionText, 'submitted') ? 'flag' : 'shield-check');
                            @endphp
                            <li class="relative flex gap-3">
                                <span class="z-[1] flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-[#EDE3F0] text-[#5b2963]"><x-admin.icon :name="$activityIcon" class="h-4 w-4" /></span>
                                <div class="min-w-0 pt-0.5">
                                    <p class="text-sm font-medium text-gray-700">{{ $activity->created_at->format('F j, Y g:i A') }}</p>
                                    <p class="mt-0.5 text-sm text-gray-600 [overflow-wrap:anywhere]">{{ $activity->action }}@if ($activity->actor) <span class="text-gray-400">· {{ $activity->actor }}</span>@endif</p>
                                </div>
                            </li>
                        @empty
                            <li class="relative flex gap-3">
                                <span class="z-[1] flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-[#EDE3F0] text-[#5b2963]"><x-admin.icon name="flag" class="h-4 w-4" /></span>
                                <div class="min-w-0 pt-0.5">
                                    <p class="text-sm font-medium text-gray-700">{{ $complaint->created_at->format('F j, Y g:i A') }}</p>
                                    <p class="mt-0.5 text-sm text-gray-600">{{ $isReport ? 'Report' : 'Complaint' }} submitted by {{ $complaint->complainant->name }}</p>
                                </div>
                            </li>
                        @endforelse
                    </ol>
                </section>
            </div>

            {{-- ================= RIGHT ================= --}}
            <div class="space-y-5">

                {{-- Parties involved --}}
                <section class="{{ $card }} cs-card" style="--i: 1" aria-labelledby="people-title">
                    <h2 id="people-title" class="mb-4 font-display text-base font-semibold text-[#2B1730]">Parties Involved</h2>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        @foreach ([$complaint->complainant, $complaint->respondent] as $person)
                            <div class="flex flex-col rounded-xl border border-[#ece4ec] p-4">
                                <div class="mb-3 flex items-start gap-3">
                                    <x-admin.avatar :user="$person" size="h-11 w-11" text="text-sm" />
                                    <div class="min-w-0 leading-tight">
                                        <p class="text-xs text-gray-500">{{ $roleLabel($person->role) }}</p>
                                        <p class="text-[15px] font-semibold text-[#2B1730] [overflow-wrap:anywhere]">{{ $person->name }}</p>
                                        <p class="mt-0.5 text-xs text-gray-500 [overflow-wrap:anywhere]">{{ $person->email }}</p>
                                    </div>
                                </div>
                                @if ($person->phone_number)
                                    <p class="mb-3 text-xs text-gray-500">{{ $person->phone_number }}</p>
                                @endif
                                @if (in_array($person->role, ['buyer', 'seller'], true))
                                <a href="#case-message-{{ $person->id }}"
                                    class="mt-auto inline-flex h-9 w-fit items-center gap-2 rounded-lg border border-[#ddd0e0] px-3 text-xs font-medium text-[#2B1730] transition-colors duration-150 hover:bg-[#F7F1F7]
                                           focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40">
                                    <x-admin.icon name="message" class="h-3.5 w-3.5" /> Message
                                </a>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </section>

                {{-- Supporting evidence --}}
                <section class="{{ $card }} cs-card" style="--i: 2" aria-labelledby="evidence-title" x-data="{ all: false }">
                    <h2 id="evidence-title" class="mb-4 font-display text-base font-semibold text-[#2B1730]">Supporting Evidence</h2>
                    @if ($evidences->isEmpty())
                        <div class="flex flex-col items-center rounded-xl border border-dashed border-[#e2d6e5] bg-[#FBF8FB] px-4 py-8 text-center">
                            <x-admin.icon name="image" class="mb-2 h-6 w-6 text-[#b9a4bd]" />
                            <p class="text-sm font-medium text-[#2B1730]">No evidence uploaded</p>
                            <p class="mt-0.5 text-xs text-gray-500">Photos and files attached to the case appear here.</p>
                        </div>
                    @else
                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                            @foreach ($evidences as $evidence)
                                @php $isPdf = str_ends_with(strtolower($evidence->original_filename ?? $evidence->path), '.pdf'); @endphp
                                <a href="{{ route('admin.complaints.evidence', [$complaint, $evidence]) }}" target="_blank" rel="noopener"
                                    @if ($loop->index >= 3) x-show="all" x-cloak @endif
                                    class="group min-w-0 focus-visible:outline-none">
                                    <span class="block aspect-square overflow-hidden rounded-lg bg-[#E4E0E4] transition duration-150 group-hover:ring-2 group-hover:ring-[#704278] group-focus-visible:ring-2 group-focus-visible:ring-[#3b1735]">
                                        @if ($isPdf)
                                            <span class="grid h-full place-items-center text-lg font-bold text-[#603168]">PDF</span>
                                        @else
                                            <img src="{{ route('admin.complaints.evidence', [$complaint, $evidence]) }}" alt="Evidence {{ $loop->iteration }}" loading="lazy" class="h-full w-full object-cover">
                                        @endif
                                    </span>
                                    <span class="mt-1 block truncate text-center text-[11px] text-gray-500" title="{{ $evidence->original_filename }}">{{ $evidence->original_filename ?: 'Evidence ' . $loop->iteration }}</span>
                                </a>
                            @endforeach

                            @if ($evidences->count() > 3)
                                <button type="button" x-show="!all" @click="all = true"
                                    class="min-w-0 focus-visible:outline-none" aria-label="Show {{ $evidences->count() - 3 }} more files">
                                    <span class="flex aspect-square items-center justify-center rounded-lg bg-[#E4E0E4] text-lg font-semibold text-[#2B1730] transition duration-150 hover:bg-[#d9d3d9]">+{{ $evidences->count() - 3 }}</span>
                                    <span class="mt-1 block text-center text-[11px] text-gray-500">Other</span>
                                </button>
                            @endif
                        </div>
                    @endif
                </section>

                {{-- Case conversations are private to Admin and each participant. --}}
                <section class="{{ $card }} cs-card" style="--i: 3" aria-labelledby="messages-title" x-data="{ who: 'all' }">
                    <h2 id="messages-title" class="mb-3 font-display text-base font-semibold text-[#2B1730]">Messages</h2>
                    <div role="tablist" aria-label="Filter messages by person" class="mb-4 flex gap-1 border-b border-[#ece4ec]">
                        @foreach (['all' => 'All', 'buyer' => 'Buyer', 'seller' => 'Seller'] as $id => $label)
                            <button type="button" role="tab" @click="who = '{{ $id }}'" :aria-selected="who === '{{ $id }}'"
                                class="relative px-3.5 py-2 text-sm font-medium text-gray-500 transition-colors duration-150 hover:text-[#3b1735] aria-selected:text-[#2B1730]
                                       after:absolute after:inset-x-2 after:bottom-0 after:h-[2px] after:origin-left after:scale-x-0 after:bg-[#2B1730] after:transition-transform after:duration-300
                                       aria-selected:after:scale-x-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-[#3b1735]/40">{{ $label }}</button>
                        @endforeach
                    </div>
                    @if (session('case_message_sent'))
                        <p class="mb-4 rounded-md border border-green-200 bg-green-50 p-3 text-sm text-green-800" role="status">Case message sent.</p>
                    @endif
                    <div class="space-y-5">
                        @foreach (collect([$complaint->complainant, $complaint->respondent])->filter()->unique('id') as $person)
                            @continue(! in_array($person->role, ['buyer', 'seller'], true))
                            @php $thread = $complaint->conversations->firstWhere('user_id', $person->id); @endphp
                            <div id="case-message-{{ $person->id }}" x-show="who === 'all' || who === '{{ $person->role }}'" class="rounded-lg border border-[#ece4ec] p-4">
                                <h3 class="text-sm font-semibold text-[#2B1730]">{{ $person->name }} <span class="font-normal text-gray-500">({{ ucfirst($person->role) }})</span></h3>
                                @if ($thread && $thread->messages->isNotEmpty())
                                    <div class="mt-3 max-h-72 divide-y divide-[#ece4ec] overflow-y-auto border-y border-[#ece4ec]">
                                        @foreach ($thread->messages as $caseMessage)
                                            <div class="py-3">
                                                <div class="flex justify-between gap-3 text-xs text-gray-500">
                                                    <strong class="text-[#402143]">{{ $caseMessage->sender_id === $person->id ? $person->name : 'Vendo Admin' }}</strong>
                                                    <time datetime="{{ $caseMessage->created_at->toIso8601String() }}">{{ $caseMessage->created_at->format('M j, Y g:i A') }}</time>
                                                </div>
                                                <p class="mt-1 whitespace-pre-wrap break-words text-sm text-[#2B1730]">{{ $caseMessage->body }}</p>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="mt-3 text-sm text-gray-500">No messages with this participant yet.</p>
                                @endif
                                @if ($complaint->status !== 'resolved')
                                    <form method="POST" action="{{ route('admin.complaints.messages.store', $complaint) }}" class="mt-4 space-y-2">
                                        @csrf
                                        <input type="hidden" name="recipient_id" value="{{ $person->id }}">
                                        <label for="case-message-body-{{ $person->id }}" class="block text-sm font-medium text-[#2B1730]">Send a message to {{ $person->name }}</label>
                                        <textarea id="case-message-body-{{ $person->id }}" name="body" rows="3" required maxlength="2000" class="w-full rounded-md border-[#d9cddd] focus:border-[#805487] focus:ring-[#805487]">{{ old('recipient_id') == $person->id ? old('body') : '' }}</textarea>
                                        @if (old('recipient_id') == $person->id) @error('body')<p class="text-sm text-red-700">{{ $message }}</p>@enderror @endif
                                        <button type="submit" class="rounded-md bg-[#402143] px-4 py-2 text-sm font-medium text-white hover:bg-[#52245b]">Send message</button>
                                    </form>
                                @else
                                    <p class="mt-3 text-xs text-gray-500">This case is resolved. Messaging is closed.</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </section>
            </div>
        </div>
    </div>
</x-admin.layout>
