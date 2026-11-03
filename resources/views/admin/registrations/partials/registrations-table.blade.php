{{--
    Rendered on first load and again by GET admin.registrations.table.
    Expects the Alpine scope from registrations/index.blade.php (clearAll).
    Pagination uses data-page buttons handled by that scope, so page links never
    navigate to the bare /registrations/table partial.
--}}
@php
    $isFiltered = request()->filled('search') || request()->filled('date') || ! in_array(request()->input('user_type', 'all'), ['all', ''], true);
@endphp

<div class="overflow-hidden rounded-xl border border-vd-line bg-white">

    @if ($registrations->isEmpty())
        @if ($isFiltered)
            <x-admin.empty-state filtered>
                <button type="button" @click="clearAll()"
                    class="inline-flex h-9 items-center rounded-lg border border-vd-line-strong bg-white px-4 text-[13px] font-medium text-vd-plum transition-colors duration-200 hover:bg-vd-tint">
                    Clear filters
                </button>
            </x-admin.empty-state>
        @else
            <x-admin.empty-state icon="user-plus" title="No pending registrations" text="New sign-ups appear here." />
        @endif
    @else
        <div class="overflow-x-auto thin-scroll">
            <table class="w-full min-w-[760px] text-center text-sm">
                <caption class="sr-only">Pending registrations</caption>
                <thead>
                    <tr class="border-b border-vd-line bg-vd-tint text-[13px] text-vd-muted">
                        <th scope="col" class="px-5 py-3 text-left font-medium">Applicant</th>
                        <th scope="col" class="px-4 py-3 font-medium">Type</th>
                        <th scope="col" class="px-4 py-3 font-medium">Email</th>
                        <th scope="col" class="px-4 py-3 font-medium">Phone</th>
                        <th scope="col" class="px-4 py-3 font-medium">Applied</th>
                        <th scope="col" class="px-5 py-3 text-center font-medium"><span class="sr-only">Action</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-vd-line">
                    @foreach ($registrations as $reg)
                        {{-- Clicking anywhere on the row opens the application; the Review button is the keyboard path. --}}
                        <tr style="--i: {{ $loop->index }}"
                            @click="if (!$event.target.closest('a, button')) $el.querySelector('[data-open-application]').click()"
                            class="cursor-pointer transition-colors duration-200 hover:bg-vd-tint">
                            <td class="px-5 py-3 text-left">
                                <div class="flex items-center gap-3">
                                    <x-admin.avatar :user="$reg" />
                                    <span class="max-w-[220px] truncate font-medium text-vd-ink">{{ $reg->name }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                @include('admin.partials.type-pill', ['role' => $reg->role])
                            </td>
                            <td class="max-w-[240px] truncate px-4 py-3 text-center text-vd-muted">{{ $reg->email }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-vd-muted">{{ $reg->phone_number ?? '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-vd-muted">{{ $reg->created_at->format('M j, Y') }}</td>
                            <td class="px-5 py-3 text-center">
                                <a href="{{ route('admin.registrations.show', $reg) }}" x-target.push="main-content sidebar" data-open-application
                                    aria-label="Review application from {{ $reg->name }}"
                                    class="inline-flex h-8 items-center rounded-lg border border-vd-line-strong bg-white px-3 text-[13px] font-medium text-vd-plum transition-all duration-200 hover:border-vd-plum hover:bg-vd-plum hover:text-white active:scale-95
                                           focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-plum/40">
                                    Review
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @include('admin.partials.pagination', ['paginator' => $registrations])
    @endif
</div>
