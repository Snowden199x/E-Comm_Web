<table class="w-full text-sm">
    <thead>
        <tr class="text-left text-gray-500 border-b">
            <th class="py-2 font-medium">Admin</th>
            <th class="py-2 font-medium">Role</th>
            <th class="py-2 font-medium">Email</th>
            <th class="py-2 font-medium">Contact</th>
            <th class="py-2 font-medium">Status</th>
            <th class="py-2 font-medium">Actions</th>
        </tr>
    </thead>

    <tbody>
        @forelse ($admins as $admin)
            <tr class="border-b last:border-0">
                <td class="py-3 text-gray-900">
                    {{ $admin->name }}
                </td>

                <td class="py-3">
                    <span class="px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-700">
                        {{ $admin->is_super_admin ? 'Super Admin' : 'Admin' }}
                    </span>
                </td>

                <td class="py-3 text-gray-600">
                    {{ $admin->email }}
                </td>

                <td class="py-3 text-gray-600">
                    {{ $admin->phone_number ?? '—' }}
                </td>

                {{-- Account / Online Status --}}
                <td class="py-3">

                    @if ($admin->archived_at)
                        <span
                            class="inline-flex items-center gap-2 px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">
                            <span class="w-2.5 h-2.5 rounded-full bg-red-500"></span>
                            Deleted
                        </span>
                    @elseif ($admin->account_status === 'suspended')
                        <span
                            class="inline-flex items-center gap-2 px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700">
                            <span class="w-2.5 h-2.5 rounded-full bg-yellow-500"></span>
                            Suspended
                        </span>
                    @elseif ($admin->isOnline())
                        <span
                            class="inline-flex items-center gap-2 px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                            <span class="relative flex w-2.5 h-2.5">
                                <span
                                    class="absolute inline-flex w-full h-full rounded-full bg-green-400 opacity-75 animate-ping"></span>
                                <span
                                    class="relative inline-flex w-2.5 h-2.5 rounded-full bg-green-500 shadow-[0_0_8px_rgba(34,197,94,0.8)]"></span>
                            </span>
                            Online
                        </span>
                    @else
                        <span
                            class="inline-flex items-center gap-2 px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-500">
                            <span class="w-2.5 h-2.5 rounded-full bg-gray-400"></span>
                            Offline
                        </span>
                    @endif

                </td>

                <td class="py-3">
                    @if ($admin->archived_at)
                        <div class="flex items-center gap-2 text-xs" x-data="{ confirmAction: null }" x-init="$watch('confirmAction', v => $dispatch('modal-toggle', v !== null))">

                            <button type="button" @click="confirmAction = 'restore'"
                                class="text-green-600 font-medium hover:underline">Restore</button>
                            <span class="text-gray-300">|</span>
                            <button type="button" @click="confirmAction = 'delete-forever'"
                                class="text-red-600 font-medium hover:underline">Delete Forever</button>

                            <!-- Confirm Restore Modal -->
                            <div x-show="confirmAction === 'restore'" x-cloak
                                class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4"
                                @click.self="confirmAction = null">
                                <div class="bg-white rounded-2xl p-6 w-full max-w-sm text-left" @click.stop>
                                    <h3 class="font-bold text-lg text-gray-900 mb-2">Restore this admin?</h3>
                                    <p class="text-sm text-gray-500 mb-4">
                                        {{ $admin->name }} will regain access to the Admin Panel.
                                    </p>
                                    <div class="flex gap-3">
                                        <button type="button" @click="confirmAction = null"
                                            class="flex-1 border border-gray-300 text-gray-700 text-sm font-medium py-2 rounded-lg hover:bg-gray-50">
                                            Cancel
                                        </button>
                                        <form method="POST"
                                            action="{{ route('admin.account-management.restore', $admin) }}"
                                            class="flex-1">
                                            @csrf
                                            <button type="submit"
                                                class="w-full bg-green-600 text-white text-sm font-medium py-2 rounded-lg hover:bg-green-700">
                                                Restore
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Confirm Delete Forever Modal -->
                            <div x-show="confirmAction === 'delete-forever'" x-cloak
                                class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4"
                                @click.self="confirmAction = null">
                                <div class="bg-white rounded-2xl p-6 w-full max-w-sm text-left" @click.stop>
                                    <h3 class="font-bold text-lg text-gray-900 mb-2">Permanently delete this admin?</h3>
                                    <p class="text-sm text-gray-500 mb-4">
                                        This will permanently delete {{ $admin->name }}. This action cannot be undone.
                                    </p>
                                    <div class="flex gap-3">
                                        <button type="button" @click="confirmAction = null"
                                            class="flex-1 border border-gray-300 text-gray-700 text-sm font-medium py-2 rounded-lg hover:bg-gray-50">
                                            Cancel
                                        </button>
                                        <form method="POST"
                                            action="{{ route('admin.account-management.force-delete', $admin) }}"
                                            class="flex-1">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="w-full bg-red-600 text-white text-sm font-medium py-2 rounded-lg hover:bg-red-700">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                        </div>
                    @else
                        <a href="{{ route('admin.account-management.show', $admin) }}" x-target.push="main-content"
                            class="text-[#3b1735] font-medium hover:underline">
                            View
                        </a>
                    @endif
                </td>
            </tr>

        @empty
            <tr>
                <td colspan="6" class="py-6 text-center text-gray-400">
                    No admin accounts yet.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
