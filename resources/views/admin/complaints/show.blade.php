<x-admin-layout>
    <div class="p-4 sm:p-5 lg:p-6">
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Complaint Details</h2>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">

            <div class="lg:col-span-2 space-y-4">
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                    <div class="flex items-center gap-2">
                        @include('admin.complaints.partials.type-badge')
                        @include('admin.complaints.partials.status-badge')
                    </div>

                    @if ($complaint->status !== 'resolved')
                        <div class="flex gap-3 mt-4 pt-4 border-t border-gray-100">
                            @if ($complaint->status === 'open')
                                <form method="POST" action="{{ route('complaints.update-status', $complaint) }}">
                                    @csrf
                                    <input type="hidden" name="status" value="in_review">
                                    <button type="submit"
                                        class="px-4 py-2 rounded-lg border border-blue-300 text-blue-700 text-sm font-medium hover:bg-blue-50">Mark
                                        In Progress</button>
                                </form>
                            @endif
                            <form method="POST" action="{{ route('complaints.update-status', $complaint) }}">
                                @csrf
                                <input type="hidden" name="status" value="resolved">
                                <button type="submit"
                                    class="px-4 py-2 rounded-lg bg-green-600 text-white text-sm font-medium hover:bg-green-700">Mark
                                    Resolved</button>
                            </form>
                        </div>
                    @endif
                </div>

                <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                    <h3 class="font-bold text-gray-900 mb-4">Complaint Summary</h3>
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
                    <p class="text-sm text-gray-800">{{ $complaint->description }}</p>
                </div>

                @if ($complaint->order)
                    <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                        <h3 class="font-bold text-gray-900 mb-4">Order Information</h3>
                        @foreach ($complaint->order->items as $item)
                            <div class="flex items-center gap-3 mb-3 last:mb-0">
                                <div class="w-14 h-14 rounded-lg bg-gray-100 overflow-hidden flex-shrink-0">
                                    @if ($item->product?->images->first())
                                        <img src="{{ Storage::url($item->product->images->first()->path) }}"
                                            class="w-full h-full object-cover">
                                    @endif
                                </div>
                                <div class="flex-1">
                                    <p class="font-medium text-gray-900 text-sm">
                                        {{ $item->product->name ?? 'Product removed' }}</p>
                                    <p class="text-xs text-gray-400">Qty: {{ $item->quantity }}</p>
                                </div>
                                <p class="font-semibold text-gray-900 text-sm">₱{{ number_format($item->price, 2) }}
                                </p>
                            </div>
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
                        <p class="text-xs text-gray-400 mb-2">Buyer</p>
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
                        <p class="text-xs text-gray-400 mb-2">Seller</p>
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
                        <div class="grid grid-cols-4 gap-2">
                            @foreach ($complaint->evidences->take(3) as $evidence)
                                <div class="aspect-square rounded-lg bg-gray-100 overflow-hidden">
                                    <img src="{{ Storage::url($evidence->path) }}" class="w-full h-full object-cover">
                                </div>
                            @endforeach
                            @if ($complaint->evidences->count() > 3)
                                <div
                                    class="aspect-square rounded-lg bg-gray-100 flex items-center justify-center text-gray-500 text-sm font-medium">
                                    +{{ $complaint->evidences->count() - 3 }}
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</x-admin-layout>
