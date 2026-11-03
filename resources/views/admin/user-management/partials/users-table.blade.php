{{--
    Expects the Alpine scope from user-management/index.blade.php (openId, clearAll).
    The server filters by status, so every row shown matches the filters.
    Pagination uses data-page buttons handled by that scope.
--}}
@php
    // Status colours: green = can sign in, deep red = suspended, salmon = deactivated, grey = rejected.
    $statusPill = [
        'active' => ['Active', 'border-[#2E8B3E] bg-[#DCEFD9] text-[#1F6B35]'],
        'suspended' => ['Suspended', 'border-[#9B1C2C] bg-[#F9D6DA] text-[#9B1C2C]'],
        'deactivated' => ['Deactivated', 'border-[#E58A76] bg-[#FDE6DF] text-[#C2452D]'],
        'disapproved' => ['Rejected', 'border-[#B8B2BC] bg-[#F0EEF1] text-[#5E5663]'],
    ];

    $stateOf = fn ($u) => $u->status === 'disapproved' ? 'disapproved' : ($u->account_status ?: 'active');

    $isFiltered = request()->filled('search') || request()->filled('date') || request()->boolean('rejected')
        || request()->filled('account_status') || ! in_array(request()->input('user_type', 'all'), ['all', ''], true);
@endphp

<div class="overflow-hidden rounded-xl border border-vd-line bg-white">

    @if ($users->isEmpty())
        @if ($isFiltered)
            <x-admin.empty-state filtered>
                <button type="button" @click="clearAll()"
                    class="inline-flex h-9 items-center rounded-lg border border-vd-line-strong bg-white px-4 text-[13px] font-medium text-vd-plum transition-colors duration-200 hover:bg-vd-tint">
                    Clear filters
                </button>
            </x-admin.empty-state>
        @else
            <x-admin.empty-state icon="users" title="No users" text="Approved accounts appear here." />
        @endif
    @else
        <div class="overflow-x-auto thin-scroll">
            <table class="w-full min-w-[820px] text-center text-sm">
                <caption class="sr-only">Users</caption>
                <thead>
                    <tr class="border-b border-vd-line bg-vd-tint text-[13px] text-vd-muted">
                        <th scope="col" class="px-5 py-3 text-left font-medium">User</th>
                        <th scope="col" class="px-4 py-3 font-medium">Type</th>
                        <th scope="col" class="px-4 py-3 font-medium">Email</th>
                        <th scope="col" class="px-4 py-3 font-medium">Phone</th>
                        <th scope="col" class="px-4 py-3 font-medium">Joined</th>
                        <th scope="col" class="px-4 py-3 font-medium">Status</th>
                        <th scope="col" class="px-5 py-3 text-center font-medium"><span class="sr-only">Action</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-vd-line">
                    @foreach ($users as $u)
                        @php $accountState = $stateOf($u); $pill = $statusPill[$accountState] ?? [ucfirst((string) $accountState), 'border-[#B8B2BC] bg-[#F0EEF1] text-[#5E5663]']; @endphp
                        {{-- Clicking anywhere on the row opens the profile; the View button is the keyboard path. --}}
                        <tr style="--i: {{ $loop->index }}" @click="openId = {{ $u->id }}"
                            class="cursor-pointer transition-colors duration-200 hover:bg-vd-tint">
                            <td class="px-5 py-3 text-left">
                                <div class="flex items-center gap-3">
                                    <x-admin.avatar :user="$u" />
                                    <span class="max-w-[220px] truncate font-medium text-vd-ink">{{ $u->name }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                @include('admin.partials.type-pill', ['role' => $u->role])
                            </td>
                            <td class="max-w-[240px] truncate px-4 py-3 text-center text-vd-muted">{{ $u->email }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-vd-muted">{{ $u->phone_number ?? '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-vd-muted">{{ $u->created_at->format('M j, Y') }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full border-[1.5px] px-3.5 py-0.5 text-xs font-semibold {{ $pill[1] }}"
                                    @if ($accountState === 'suspended') title="{{ $u->suspended_until ? 'Suspended until ' . $u->suspended_until->format('M j, Y') : 'Suspended with no end date' }}" @endif>
                                    {{ $pill[0] }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-center">
                                <button type="button" aria-haspopup="dialog" @click.stop="openId = {{ $u->id }}"
                                    aria-label="View profile of {{ $u->name }}"
                                    class="inline-flex h-8 items-center rounded-lg border border-vd-line-strong bg-white px-3 text-[13px] font-medium text-vd-plum transition-all duration-200 hover:border-vd-plum hover:bg-vd-plum hover:text-white active:scale-95
                                           focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-plum/40">
                                    View
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @include('admin.partials.pagination', ['paginator' => $users])
    @endif
</div>
