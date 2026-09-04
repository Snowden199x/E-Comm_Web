<div class="bg-white rounded-2xl p-5 shadow-sm overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr class="text-left text-gray-500 border-b">
                <th class="pb-3 font-medium">Complaint ID</th>
                <th class="pb-3 font-medium">Parties</th>
                <th class="pb-3 font-medium">Type</th>
                <th class="pb-3 font-medium">Status</th>
                <th class="pb-3 font-medium">Date Filed</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($complaints as $complaint)
                <tr class="border-b last:border-0 cursor-pointer hover:bg-gray-50"
                    @click="previewId = {{ $complaint->id }}">
                    <td class="py-3 font-medium text-gray-900">
                        CMP-{{ $complaint->created_at->format('Y') }}-{{ str_pad($complaint->id, 5, '0', STR_PAD_LEFT) }}
                    </td>
                    <td class="py-3 text-gray-600">
                        <p>{{ $complaint->complainant->name }}<span class="text-xs text-gray-400"> (Buyer)</span></p>
                        <p class="font-semibold text-gray-900">{{ $complaint->respondent->name }}<span
                                class="text-xs text-gray-400 font-normal"> (Seller)</span></p>
                    </td>
                    <td class="py-3">@include('admin.complaints.partials.type-badge')</td>
                    <td class="py-3">@include('admin.complaints.partials.status-badge')</td>
                    <td class="py-3 text-gray-600">{{ $complaint->created_at->format('M d, Y g:i A') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="py-8 text-center text-gray-400">No complaints found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    @if ($complaints->hasPages())
        <div class="flex items-center justify-between mt-4 pt-4 border-t">
            <p class="text-xs text-gray-500">Showing {{ $complaints->count() }} out of {{ $complaints->total() }}
                entries</p>
            <div>{{ $complaints->links() }}</div>
        </div>
    @endif
</div>
