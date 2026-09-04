<div class="bg-white rounded-2xl p-5 shadow-sm overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr class="text-left text-gray-500 border-b">
                <th class="pb-3 font-medium">Applicant</th>
                <th class="pb-3 font-medium">User Type</th>
                <th class="pb-3 font-medium">Email</th>
                <th class="pb-3 font-medium">Number</th>
                <th class="pb-3 font-medium">Date Applied</th>
                <th class="pb-3 font-medium text-right">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($registrations as $reg)
                <tr class="border-b last:border-0">
                    <td class="py-3">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center text-xs font-semibold text-gray-600">
                                {{ strtoupper(substr($reg->name, 0, 1)) }}
                            </div>
                            <span class="text-gray-900">{{ $reg->name }}</span>
                        </div>
                    </td>
                    <td class="py-3 text-gray-600 capitalize">{{ $reg->role }}</td>
                    <td class="py-3 text-gray-600">{{ $reg->email }}</td>
                    <td class="py-3 text-gray-600">{{ $reg->phone_number ?? '—' }}</td>
                    <td class="py-3 text-gray-600">{{ $reg->created_at->format('M d, Y') }}</td>
                    <td class="py-3 text-right">
                        <a href="{{ route('registrations.show', $reg) }}"
                            class="inline-block px-4 py-1.5 rounded-full border border-gray-300 text-xs font-medium text-gray-700 hover:bg-gray-50">
                            View
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="py-8 text-center text-gray-400">No pending registrations found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    @if ($registrations->hasPages())
        <div class="flex items-center justify-between mt-4 pt-4 border-t">
            <p class="text-xs text-gray-500">Showing {{ $registrations->count() }} out of {{ $registrations->total() }}
                entries</p>
            <div>{{ $registrations->links() }}</div>
        </div>
    @endif
</div>