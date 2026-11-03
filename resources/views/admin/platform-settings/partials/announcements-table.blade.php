{{--
    Rendered on first load and again by GET admin.platform-settings.announcements-table.
    Expects the Alpine scope from platform-settings/index.blade.php (openCreate, openEdit, clearAll).
    Pagination uses data-page buttons handled by that scope.
--}}
@php
    $focus = 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-plum/40';

    // Same colours as the stat cards: success = live, warning = waiting, neutral = not sent.
    $statusChips = [
        'published' => 'border-vd-success-line bg-vd-success-bg text-vd-success-ink',
        'scheduled' => 'border-vd-warning-line bg-vd-warning-bg text-vd-warning-ink',
        'draft' => 'border-vd-line-strong bg-vd-tint text-vd-muted',
    ];

    $isFiltered = request()->filled('search')
        || request()->filled('date')
        || in_array(request()->input('status'), ['draft', 'scheduled', 'published'], true);
@endphp

<div class="overflow-hidden rounded-xl border border-vd-line bg-white">
    @if ($announcements->isEmpty())
        @if ($isFiltered)
            <x-admin.empty-state filtered filtered-title="No matching announcements" filtered-text="Check the spelling, or pick another date or status.">
                <button type="button" @click="clearAll()"
                    class="inline-flex h-9 items-center rounded-lg border border-vd-line-strong bg-white px-4 text-[13px] font-medium text-vd-plum transition-colors duration-200 hover:bg-vd-tint {{ $focus }}">
                    Clear filters
                </button>
            </x-admin.empty-state>
        @else
            <x-admin.empty-state icon="megaphone" title="No announcements" text="Announcements you create appear here.">
                <button type="button" @click="openCreate()"
                    class="inline-flex h-9 items-center gap-2 rounded-lg bg-vd-plum px-4 text-[13px] font-medium text-white transition-colors duration-200 hover:bg-vd-plum-hover {{ $focus }}">
                    <x-admin.icon name="plus" class="h-4 w-4" />
                    Create announcement
                </button>
            </x-admin.empty-state>
        @endif
    @else
        <div class="thin-scroll overflow-x-auto">
            <table class="w-full min-w-[720px] text-center text-sm">
                <caption class="sr-only">Announcements</caption>
                <thead>
                    <tr class="border-b border-vd-line bg-vd-tint text-[13px] text-vd-muted">
                        <th scope="col" class="px-5 py-3 text-left font-medium">Announcement</th>
                        <th scope="col" class="px-4 py-3 font-medium">Audience</th>
                        <th scope="col" class="px-4 py-3 font-medium">Date</th>
                        <th scope="col" class="px-4 py-3 font-medium">Status</th>
                        <th scope="col" class="px-5 py-3 text-center font-medium"><span class="sr-only">Action</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-vd-line">
                    @foreach ($announcements as $a)
                        @php
                            $goesLive = $a->status === 'scheduled' && $a->scheduled_at;
                            $shownAt = $goesLive ? $a->scheduled_at : $a->created_at;

                            $row = [
                                'id' => $a->id,
                                'title' => $a->title,
                                'message' => $a->message,
                                'audience' => $a->audience,
                                'status' => $a->status,
                                'scheduled_at' => $a->scheduled_at?->format('Y-m-d\TH:i') ?? '',
                                'updateUrl' => route('admin.platform-settings.announcements.update', $a),
                                'deleteUrl' => route('admin.platform-settings.announcements.destroy', $a),
                            ];
                        @endphp
                        {{-- Clicking the row opens the announcement; the Edit button is the keyboard path. --}}
                        <tr style="--i: {{ $loop->index }}"
                            @click="if (!$event.target.closest('a, button')) $el.querySelector('[data-open-announcement]').click()"
                            class="cursor-pointer transition-colors duration-200 hover:bg-vd-tint">
                            <td class="max-w-[320px] px-5 py-3 text-left">
                                <p class="truncate font-medium text-vd-ink">{{ $a->title }}</p>
                                <p class="truncate text-xs text-vd-muted">{{ Str::limit($a->message, 70) }}</p>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <span class="inline-flex items-center rounded-full border border-vd-line-strong bg-vd-tint px-2.5 py-0.5 text-xs font-medium text-vd-muted">{{ $a->audience }}</span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <p class="text-vd-ink">{{ $shownAt->format('M j, Y') }}</p>
                                <p class="text-xs text-vd-muted">{{ $goesLive ? 'Goes live' : 'Created' }} {{ $shownAt->format('g:i A') }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium {{ $statusChips[$a->status] ?? $statusChips['draft'] }}">
                                    {{ ucfirst($a->status) }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-center">
                                <button type="button" data-open-announcement @click="openEdit(@js($row))"
                                    aria-label="Edit announcement {{ $a->title }}"
                                    class="inline-flex h-8 items-center rounded-lg border border-vd-line-strong bg-white px-3 text-[13px] font-medium text-vd-plum transition-all duration-200 hover:border-vd-plum hover:bg-vd-plum hover:text-white active:scale-95 {{ $focus }}">
                                    Edit
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @include('admin.partials.pagination', ['paginator' => $announcements])
    @endif
</div>
