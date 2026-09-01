<div class="bg-white rounded-2xl p-5 shadow-sm overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr class="text-left text-gray-500 border-b">
                <th class="pb-3 font-medium">Product</th>
                <th class="pb-3 font-medium">Seller</th>
                <th class="pb-3 font-medium">Reason</th>
                <th class="pb-3 font-medium">Details</th>
                <th class="pb-3 font-medium">Date Issued</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($warnings as $warning)
                <tr class="border-b last:border-0">
                    <td class="py-3 text-gray-900">{{ $warning->product->name ?? '—' }}</td>
                    <td class="py-3 text-gray-600">{{ $warning->seller->name }}</td>
                    <td class="py-3">
                        <span
                            class="px-2 py-0.5 rounded-full bg-orange-50 text-orange-700 text-xs font-medium">{{ $warning->reason }}</span>
                    </td>
                    <td class="py-3 text-gray-500 max-w-xs truncate">{{ $warning->details }}</td>
                    <td class="py-3 text-gray-600">{{ $warning->created_at->format('M d, Y g:i A') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="py-8 text-center text-gray-400">No warnings issued.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    @if ($warnings->hasPages())
        <div class="flex items-center justify-between mt-4 pt-4 border-t">
            <p class="text-xs text-gray-500">Showing {{ $warnings->count() }} out of {{ $warnings->total() }} entries
            </p>
            <div>{{ $warnings->links() }}</div>
        </div>
    @endif
</div>