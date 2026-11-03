{{--
    Admin accounts table. Rendered inside #admins-table-wrap (Alpine scope from index.blade.php) and returned
    on its own by admin.account-management.table. Needs $admins.
--}}
@php
    $search = trim((string) request('search', ''));
    $statusFilter = request('status', 'all');
    $isFiltered = $search !== '' || ($statusFilter !== 'all' && $statusFilter !== 'archived');
    $onlyArchived = $statusFilter === 'archived' && $search === '';

    $rowBtn = 'inline-flex h-8 items-center justify-center gap-1.5 rounded-lg border px-3 text-[13px] font-medium transition-all duration-200 active:scale-95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-plum/40';
@endphp

<div class="overflow-hidden rounded-xl border border-vd-line bg-white">
    @if ($admins->isEmpty())
        @if ($onlyArchived)
            <x-admin.empty-state icon="archive" title="No archived admins" text="Admins you delete appear here." />
        @elseif ($isFiltered || $statusFilter === 'archived')
            <x-admin.empty-state filtered>
                <button type="button" @click="clearAll()"
                    class="inline-flex h-9 items-center rounded-lg border border-vd-line-strong bg-white px-4 text-[13px] font-medium text-vd-plum transition-colors duration-200 hover:bg-vd-tint">
                    Clear filters
                </button>
            </x-admin.empty-state>
        @else
            <x-admin.empty-state icon="shield-check" title="No admin accounts" text="Create an admin account to share access." />
        @endif
    @else
        <div class="thin-scroll overflow-x-auto">
            <table class="w-full min-w-[860px] text-center text-sm">
                <caption class="sr-only">Administrator accounts</caption>
                <thead>
                    <tr class="border-b border-vd-line bg-vd-tint text-[13px] text-vd-muted">
                        <th scope="col" class="px-5 py-3 text-left font-medium">Admin</th>
                        <th scope="col" class="px-4 py-3 font-medium">Role</th>
                        <th scope="col" class="px-4 py-3 font-medium">Email</th>
                        <th scope="col" class="px-4 py-3 font-medium">Contact</th>
                        <th scope="col" class="px-4 py-3 font-medium">Status</th>
                        <th scope="col" class="px-5 py-3 font-medium">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-vd-line">
                    @foreach ($admins as $admin)
                        <tr style="--i: {{ $loop->index }}" class="transition-colors duration-200 hover:bg-vd-tint">
                            <td class="px-5 py-3 text-left">
                                <div class="flex items-center gap-3">
                                    <x-admin.avatar :user="$admin" />
                                    <p class="max-w-[200px] truncate font-medium text-vd-ink" title="{{ $admin->name }}">{{ $admin->name }}</p>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <x-admin.status-pill :tone="$admin->is_super_admin ? 'plum' : 'neutral'">{{ $admin->is_super_admin ? 'Super Admin' : 'Admin' }}</x-admin.status-pill>
                            </td>
                            <td class="max-w-[240px] truncate px-4 py-3 text-vd-muted" title="{{ $admin->email }}">{{ $admin->email }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-vd-muted">{{ $admin->phone_number ?: '—' }}</td>
                            <td class="px-4 py-3">
                                @if ($admin->archived_at)
                                    <x-admin.status-pill tone="neutral" icon="archive">Archived</x-admin.status-pill>
                                @elseif ($admin->account_status === 'suspended')
                                    <x-admin.status-pill tone="danger" icon="ban">Suspended</x-admin.status-pill>
                                @elseif ($admin->isOnline())
                                    <x-admin.status-pill tone="success"><span class="h-1.5 w-1.5 rounded-full bg-[#2E8B3E]"></span> Online</x-admin.status-pill>
                                @else
                                    <x-admin.status-pill tone="neutral"><span class="h-1.5 w-1.5 rounded-full bg-[#9A929E]"></span> Offline</x-admin.status-pill>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                @if ($admin->archived_at)
                                    <div class="flex items-center justify-center gap-2" x-data="{ confirmAction: null }">
                                        <button type="button" @click="confirmAction = 'restore'" aria-haspopup="dialog" aria-label="Restore {{ $admin->name }}"
                                            class="{{ $rowBtn }} border-vd-success-line bg-vd-success-bg text-vd-success-ink hover:border-vd-success-ink">
                                            Restore
                                        </button>
                                        <button type="button" @click="confirmAction = 'delete-forever'" aria-haspopup="dialog" aria-label="Delete {{ $admin->name }} for good"
                                            class="{{ $rowBtn }} border-vd-danger-line bg-vd-danger-bg text-vd-danger-ink hover:border-vd-danger-ink">
                                            Delete for good
                                        </button>

                                        <x-admin.confirm-dialog model="confirmAction === 'restore'" close="confirmAction = null" title="Restore this admin?"
                                            :text="$admin->name . ' can sign in to the Admin Panel again.'"
                                            :action="route('admin.account-management.restore', $admin)" confirm="Restore admin" tone="success" />

                                        <x-admin.confirm-dialog model="confirmAction === 'delete-forever'" close="confirmAction = null" title="Delete this admin for good?"
                                            :text="'This removes ' . $admin->name . ' for good. You can\'t undo it.'"
                                            :action="route('admin.account-management.force-delete', $admin)" method="DELETE" confirm="Delete admin" tone="danger" />
                                    </div>
                                @else
                                    <a href="{{ route('admin.account-management.show', $admin) }}" x-target.push="main-content" data-skeleton="cards" aria-label="View {{ $admin->name }}"
                                        class="{{ $rowBtn }} border-vd-line-strong bg-white text-vd-plum hover:border-vd-plum hover:bg-vd-plum hover:text-white">
                                        View
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
