<div class="bg-white rounded-2xl p-5 shadow-sm overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr class="text-left text-gray-500 border-b">
                <th class="pb-3 font-medium">Applicant</th>
                <th class="pb-3 font-medium">User Type</th>
                <th class="pb-3 font-medium">Email</th>
                <th class="pb-3 font-medium">Number</th>
                <th class="pb-3 font-medium">Date Applied</th>
                <th class="pb-3 font-medium">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($users as $u)
                <tr class="border-b last:border-0 cursor-pointer hover:bg-gray-50" @click="openId = {{ $u->id }}">
                    <td class="py-3">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center text-xs font-semibold text-gray-600">
                                {{ strtoupper(substr($u->name, 0, 1)) }}
                            </div>
                            <span class="text-gray-900">{{ $u->name }}</span>
                        </div>
                    </td>
                    <td class="py-3 text-gray-600 capitalize">{{ $u->role }}</td>
                    <td class="py-3 text-gray-600">{{ $u->email }}</td>
                    <td class="py-3 text-gray-600">{{ $u->phone_number ?? '—' }}</td>
                    <td class="py-3 text-gray-600">{{ $u->created_at->format('M d, Y') }}</td>
                    <td class="py-3">
                        <span @class([
                            'px-2 py-1 rounded-full text-xs font-medium',
                            'bg-green-100 text-green-700' => $u->status === 'approved',
                            'bg-red-100 text-red-700' => $u->status === 'suspended',
                            'bg-red-50 text-red-400' => $u->status === 'deactivated',
                            'bg-orange-100 text-orange-700' => $u->status === 'disapproved',
                        ])>
                            @if ($u->status === 'approved') Active
                            @elseif ($u->status === 'disapproved') Rejected
                            @else {{ ucfirst($u->status) }}
                            @endif
                        </span>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="py-8 text-center text-gray-400">No users found.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if ($users->hasPages())
        <div class="flex items-center justify-between mt-4 pt-4 border-t">
            <p class="text-xs text-gray-500">Showing {{ $users->count() }} out of {{ $users->total() }} entries</p>
            <div>{{ $users->links() }}</div>
        </div>
    @endif
</div>