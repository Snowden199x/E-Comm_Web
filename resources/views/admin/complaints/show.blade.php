<x-admin.layout>
    <div class="p-4 sm:p-5 lg:p-6">
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900">{{ $complaint->kind === 'user_report' ? 'Account Report Details' : 'Complaint Details' }}</h2>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">

            <div class="lg:col-span-2 space-y-4">
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                    <div class="flex items-center gap-2">
                        @include('admin.complaints.partials.type-badge')
                        @include('admin.complaints.partials.status-badge')
                    </div>

                    @if ($complaint->kind !== 'user_report' && $complaint->status !== 'resolved')
                        <div class="flex gap-3 mt-4 pt-4 border-t border-gray-100">
                            @if ($complaint->status === 'open')
                                <form method="POST" action="{{ route('admin.complaints.update-status', $complaint) }}">
                                    @csrf
                                    <input type="hidden" name="status" value="in_review">
                                    <button type="submit"
                                        class="px-4 py-2 rounded-lg border border-blue-300 text-blue-700 text-sm font-medium hover:bg-blue-50">Mark
                                        In Progress</button>
                                </form>
                            @endif
                            <form method="POST" action="{{ route('admin.complaints.update-status', $complaint) }}">
                                @csrf
                                <input type="hidden" name="status" value="resolved">
                                <button type="submit"
                                    class="px-4 py-2 rounded-lg bg-green-600 text-white text-sm font-medium hover:bg-green-700">Mark
                                    Resolved</button>
                            </form>
                        </div>
                    @endif
                </div>

                @if($complaint->kind === 'user_report')
                    <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                        <h3 class="mb-2 font-bold text-gray-900">Report review</h3>
                        @if($complaint->decision)
                            <p class="text-sm text-gray-700">Decision: <strong>{{ ucfirst($complaint->decision) }}</strong> on {{ $complaint->reviewed_at?->format('M j, Y g:i A') }}.</p>
                        @else
                            <p class="mb-4 text-sm text-gray-600">Review the description and order context before deciding. Approval sends a warning to the reported account.</p>
                            @if($complaint->status === 'open')
                                <form method="POST" action="{{ route('admin.complaints.update-status', $complaint) }}" class="mb-3">
                                    @csrf<input type="hidden" name="status" value="in_review">
                                    <button type="submit" class="rounded-lg border border-blue-300 px-3 py-2 text-sm text-blue-700">Mark in progress</button>
                                </form>
                            @endif
                            <form method="POST" action="{{ route('admin.complaints.decision', $complaint) }}">
                                @csrf
                                <label for="reviewNote" class="mb-1 block text-sm font-medium text-gray-700">Review note</label>
                                <textarea id="reviewNote" name="note" minlength="10" maxlength="500" rows="3" required class="mb-3 w-full rounded-lg border border-gray-200 p-3 text-sm" placeholder="Explain the decision for the case record.">{{ old('note') }}</textarea>
                                <div class="flex flex-wrap gap-2">
                                    <button type="submit" name="decision" value="approved" class="rounded-lg bg-[#3b1735] px-4 py-2 text-sm font-semibold text-white">Approve and warn</button>
                                    <button type="submit" name="decision" value="rejected" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700">Reject report</button>
                                </div>
                            </form>
                        @endif
                    </div>
                @endif

                <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                    <h3 class="font-bold text-gray-900 mb-4">{{ $complaint->kind === 'user_report' ? 'Report Summary' : 'Complaint Summary' }}</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4 text-sm">
                        <div>
                            <p class="text-gray-400 text-xs mb-1">Complaint Type</p>
                            @include('admin.complaints.partials.type-badge')
                        </div>
                        <div>
                            <p class="text-gray-400 text-xs mb-1">Order ID</p>
                            <p class="text-red-600 font-medium">
                                {{ $complaint->order ? 'ORD-' . str_pad($complaint->order->id, 4, '0', STR_PAD_LEFT) : '—' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-gray-400 text-xs mb-1">Amount</p>
                            <p class="font-semibold text-gray-900">
                                ₱{{ number_format($complaint->order->total_amount ?? 0, 2) }}</p>
                        </div>
                    </div>
                    <p class="text-gray-400 text-xs mb-1">Description</p>
                    <p class="whitespace-pre-wrap break-words text-sm text-gray-800 [overflow-wrap:anywhere]">{{ $complaint->description }}</p>
                </div>

                @if ($complaint->order)
                    <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                        <h3 class="font-bold text-gray-900 mb-4">Order Information</h3>
                        @foreach ($complaint->order->items as $item)
                            @if($item->product)
                                <button type="button" onclick="document.getElementById('complaintProduct{{ $item->id }}').showModal()" class="mb-3 flex w-full items-center gap-3 rounded-xl p-2 text-left transition hover:bg-purple-50 focus-visible:outline-2 focus-visible:outline-[#603168]">
                                    <span class="h-14 w-14 flex-shrink-0 overflow-hidden rounded-lg bg-gray-100">
                                        @if($item->product->images->first())<img src="{{ Storage::url($item->product->images->first()->path) }}" alt="{{ $item->product->name }}" class="h-full w-full object-cover">@endif
                                    </span>
                                    <span class="min-w-0 flex-1"><strong class="block truncate text-sm text-gray-900">{{ $item->product->name }}</strong><small class="block text-xs text-gray-500">Qty: {{ $item->quantity }} · View product details</small></span>
                                    <span class="text-sm font-semibold text-gray-900">₱{{ number_format($item->price, 2) }}</span>
                                </button>
                                <dialog id="complaintProduct{{ $item->id }}" class="w-[min(680px,calc(100vw-2rem))] max-h-[calc(100dvh-2rem)] overflow-y-auto rounded-2xl border border-gray-100 p-0 text-gray-900 shadow-2xl backdrop:bg-black/55" aria-labelledby="complaintProductTitle{{ $item->id }}">
                                    <div class="sticky top-0 z-10 flex items-center justify-between border-b bg-white px-5 py-4">
                                        <h4 id="complaintProductTitle{{ $item->id }}" class="min-w-0 break-words text-lg font-bold">{{ $item->product->name }}</h4>
                                        <button type="button" onclick="this.closest('dialog').close()" aria-label="Close product details" class="ml-4 text-2xl leading-none text-gray-500">&times;</button>
                                    </div>
                                    <div class="space-y-5 p-5">
                                        @if($item->product->images->isNotEmpty())
                                            <div class="flex gap-2 overflow-x-auto">
                                                @foreach($item->product->images as $image)<img src="{{ Storage::url($image->path) }}" alt="{{ $item->product->name }} photo {{ $loop->iteration }}" class="h-32 w-32 flex-none rounded-lg bg-gray-100 object-cover">@endforeach
                                            </div>
                                        @endif
                                        <div class="grid grid-cols-2 gap-4 text-sm sm:grid-cols-3">
                                            <div><span class="block text-xs text-gray-500">Unit price</span><strong>₱{{ number_format($item->price, 2) }}</strong></div>
                                            <div><span class="block text-xs text-gray-500">Quantity</span><strong>{{ $item->quantity }}</strong></div>
                                            @if($item->color)<div><span class="block text-xs text-gray-500">Color</span><strong>{{ $item->color }}</strong></div>@endif
                                            @if($item->size)<div><span class="block text-xs text-gray-500">Size</span><strong>{{ $item->size }}</strong></div>@endif
                                            @if($item->product->brand)<div><span class="block text-xs text-gray-500">Brand</span><strong>{{ $item->product->brand }}</strong></div>@endif
                                            @if($item->product->material)<div><span class="block text-xs text-gray-500">Material</span><strong>{{ $item->product->material }}</strong></div>@endif
                                            @if($item->product->weight)<div><span class="block text-xs text-gray-500">Weight</span><strong>{{ $item->product->weight }}</strong></div>@endif
                                            @if($item->product->country_of_origin)<div><span class="block text-xs text-gray-500">Country of origin</span><strong>{{ $item->product->country_of_origin }}</strong></div>@endif
                                        </div>
                                        <div><h5 class="mb-2 text-sm font-semibold">Description</h5><p class="whitespace-pre-wrap break-words text-sm leading-6 text-gray-700">{{ $item->product->description ?: 'No description provided.' }}</p></div>
                                    </div>
                                </dialog>
                            @else
                                <div class="mb-3 flex items-center justify-between rounded-xl bg-gray-50 p-3 text-sm"><span>Product removed · Qty: {{ $item->quantity }}</span><strong>₱{{ number_format($item->price, 2) }}</strong></div>
                            @endif
                        @endforeach
                        <div class="border-t border-gray-100 mt-4 pt-4 grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <p class="text-gray-400 text-xs mb-1">Order Date</p>
                                <p class="font-medium text-gray-900">
                                    {{ $complaint->order->created_at->format('M d, Y g:i A') }}</p>
                            </div>
                            <div>
                                <p class="text-gray-400 text-xs mb-1">Payment Method</p>
                                <p class="font-medium text-gray-900">{{ $complaint->order->payment_mode ?? '—' }}</p>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                    <h3 class="font-bold text-gray-900 mb-4">Timeline</h3>
                    <div class="space-y-4">
                        @forelse ($complaint->activities as $activity)
                            <div class="flex gap-3">
                                <div
                                    class="w-8 h-8 rounded-full bg-purple-50 flex items-center justify-center flex-shrink-0 text-[#3b1735] text-xs">
                                    ●</div>
                                <div>
                                    <p class="text-xs text-gray-400">
                                        {{ $activity->created_at->format('M d, Y g:i A') }}</p>
                                    <p class="text-sm text-gray-800">{{ $activity->action }}</p>
                                </div>
                            </div>
                        @empty
                            <div class="flex gap-3">
                                <div
                                    class="w-8 h-8 rounded-full bg-purple-50 flex items-center justify-center flex-shrink-0 text-[#3b1735] text-xs">
                                    ●</div>
                                <div>
                                    <p class="text-xs text-gray-400">
                                        {{ $complaint->created_at->format('M d, Y g:i A') }}</p>
                                    <p class="text-sm text-gray-800">Complaint submitted by
                                        {{ $complaint->complainant->name }}</p>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                    <h3 class="font-bold text-gray-900 mb-4">Parties Involved</h3>

                    <div class="mb-4 pb-4 border-b border-gray-100">
                        <p class="text-xs text-gray-400 mb-2">{{ ucfirst($complaint->complainant->role) }} · Reporter</p>
                        <div class="flex items-center gap-2 mb-2">
                            <div
                                class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center text-xs font-semibold text-gray-600">
                                {{ strtoupper(substr($complaint->complainant->name, 0, 1)) }}
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $complaint->complainant->name }}</p>
                                <p class="text-xs text-gray-400">{{ $complaint->complainant->email }}</p>
                            </div>
                        </div>
                        @if ($complaint->complainant->phone_number)
                            <p class="text-xs text-gray-500 mb-2">{{ $complaint->complainant->phone_number }}</p>
                        @endif
                        <button type="button" disabled
                            class="w-full px-3 py-1.5 rounded-lg border border-gray-200 text-gray-600 text-xs font-medium">Message</button>
                    </div>

                    <div>
                        <p class="text-xs text-gray-400 mb-2">{{ ucfirst($complaint->respondent->role) }} · Reported account</p>
                        <div class="flex items-center gap-2 mb-2">
                            <div
                                class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center text-xs font-semibold text-gray-600">
                                {{ strtoupper(substr($complaint->respondent->name, 0, 1)) }}
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $complaint->respondent->name }}</p>
                                <p class="text-xs text-gray-400">{{ $complaint->respondent->email }}</p>
                            </div>
                        </div>
                        @if ($complaint->respondent->phone_number)
                            <p class="text-xs text-gray-500 mb-2">{{ $complaint->respondent->phone_number }}</p>
                        @endif
                        <button type="button" disabled
                            class="w-full px-3 py-1.5 rounded-lg border border-gray-200 text-gray-600 text-xs font-medium">Message</button>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                    <h3 class="font-bold text-gray-900 mb-4">Supporting Evidence</h3>
                    @if ($complaint->evidences->isEmpty())
                        <p class="text-sm text-gray-400">No evidence uploaded.</p>
                    @else
                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                            @foreach ($complaint->evidences as $evidence)
                                @php $isPdf = str_ends_with(strtolower($evidence->original_filename ?? $evidence->path), '.pdf'); @endphp
                                <a href="{{ route('admin.complaints.evidence', [$complaint, $evidence]) }}" target="_blank" rel="noopener" class="group min-w-0 overflow-hidden rounded-lg border border-gray-200 bg-gray-50 hover:border-[#704278]">
                                    @if($isPdf)
                                        <span class="grid h-24 place-items-center text-lg font-bold text-[#603168]">PDF</span>
                                    @else
                                        <img src="{{ route('admin.complaints.evidence', [$complaint, $evidence]) }}" alt="Evidence {{ $loop->iteration }}" class="h-24 w-full object-cover">
                                    @endif
                                    <span class="block truncate px-2 py-1.5 text-xs text-gray-700" title="{{ $evidence->original_filename }}">{{ $evidence->original_filename ?: 'Evidence '.$loop->iteration }}</span>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</x-admin.layout>
