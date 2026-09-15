<table class="w-full text-sm">
    <thead>
        <tr class="text-left text-gray-500 border-b">
            <th class="py-2 font-medium">Admin</th>
            <th class="py-2 font-medium">Role</th>
            <th class="py-2 font-medium">Email</th>
            <th class="py-2 font-medium">Contact</th>
            <th class="py-2 font-medium">Status</th>
            <th class="py-2 font-medium">Last Login</th>
            <th class="py-2 font-medium">Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($admins as $admin)
            <tr class="border-b last:border-0">
                <td class="py-3 text-gray-900">{{ $admin->name }}</td>
                <td class="py-3">
                    <span class="px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-700">
                        {{ $admin->is_super_admin ? 'Super Admin' : 'Admin' }}
                    </span>
                </td>
                <td class="py-3 text-gray-600">{{ $admin->email }}</td>
                <td class="py-3 text-gray-600">{{ $admin->phone_number ?? '—' }}</td>
                <td class="py-3">
                    @if ($admin->account_status === 'active' && $admin->must_change_password)
                        <span class="px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700">
                            Pending Setup
                        </span>
                    @else
                        <span @class([
                            'px-2 py-1 rounded-full text-xs font-medium',
                            'bg-green-100 text-green-700' => $admin->account_status === 'active',
                            'bg-red-100 text-red-700' => $admin->account_status === 'suspended',
                            'bg-gray-200 text-gray-600' => $admin->account_status === 'deactivated',
                        ])>
                            {{ ucfirst($admin->account_status) }}
                        </span>
                    @endif
                </td>
                <td class="py-3 text-gray-600">
                    {{ $admin->last_login_at ? $admin->last_login_at->diffForHumans() : '—' }}
                </td>
                <td class="py-3">
                    <a href="{{ route('admin.account-management.show', $admin) }}"
                        class="text-[#3b1735] font-medium hover:underline">View</a>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="py-6 text-center text-gray-400">No admin accounts yet.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
