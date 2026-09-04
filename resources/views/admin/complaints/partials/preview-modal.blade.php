<div x-show="previewId === {{ $complaint->id }}" x-cloak
    class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center p-4"
    @click.self="previewId = null">
    <div class="bg-white rounded-2xl p-6 w-full max-w-lg relative" @click.stop>
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-lg text-gray-900">Complaint Preview</h3>
            <button type="button" @click="previewId = null"
                class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>

        <div class="flex items-center gap-2 mb-4">
            @include('admin.complaints.partials.type-badge')
            @include('admin.complaints.partials.status-badge')
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-5 text-sm">
            <div class="space-y-3">
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
                    <p class="font-semibold text-gray-900">₱{{ number_format($complaint->order->total_amount ?? 0, 2) }}
                    </p>
                </div>
            </div>
            <div>
                <p class="text-gray-400 text-xs mb-1">Description</p>
                <p class="text-gray-800 font-medium mb-3">{{ $complaint->description }}</p>
                <p class="text-gray-400 text-xs mb-2">Parties Involved</p>
                <div class="space-y-2">
                    <div class="flex items-center gap-2">
                        <div
                            class="w-7 h-7 rounded-full bg-gray-200 flex items-center justify-center text-xs font-semibold text-gray-600">
                            {{ strtoupper(substr($complaint->complainant->name, 0, 1)) }}
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 leading-none">Buyer</p>
                            <p class="text-xs font-medium text-gray-900">{{ $complaint->complainant->name }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <div
                            class="w-7 h-7 rounded-full bg-gray-200 flex items-center justify-center text-xs font-semibold text-gray-600">
                            {{ strtoupper(substr($complaint->respondent->name, 0, 1)) }}
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 leading-none">Seller</p>
                            <p class="text-xs font-medium text-gray-900">{{ $complaint->respondent->name }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-end">
            <a href="{{ route('complaints.show', $complaint) }}"
                class="px-4 py-2 rounded-lg bg-[#3b1735] text-white text-sm font-medium hover:opacity-90">See Full
                Details</a>
        </div>
    </div>
</div>
