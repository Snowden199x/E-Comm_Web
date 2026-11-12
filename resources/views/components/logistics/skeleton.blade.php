@props(['variant' => 'table'])

{{--
    Page-shaped loading skeleton for the Logistics portals (flat blocks, no gradients, same blocks as the Admin).
    Five layouts live in the markup; resources/css/admin/motion.css shows only one of them.
      variant="dashboard|table|cards|split|form"  draws that layout
      variant="auto"                              follows body[data-skeleton] (set by the sidebar link that was clicked)
    Used by logistics/partials/boot-skeleton.blade.php (first load) and the shell (page-to-page navigation).
    Keep each layout close to the real page so nothing jumps when the page appears.
--}}
@php
    $block = 'vd-sk-block';
    $card = 'vd-sk-card';
@endphp

<div class="vd-sk mx-auto w-full max-w-[1320px] px-5 py-6 sm:px-8 sm:py-8" data-active="{{ $variant }}" aria-hidden="true">

    {{-- Dashboard: heading and two buttons, a list card, five tiles, a table card and a side rail --}}
    <div class="vd-sk-v" data-v="dashboard">
        <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
            <div class="space-y-2.5">
                <div class="{{ $block }} h-3.5 w-36"></div>
                <div class="{{ $block }} h-7 w-44 max-w-full"></div>
            </div>
            <div class="flex gap-2">
                <div class="{{ $block }} h-9 w-32 rounded-lg"></div>
                <div class="{{ $block }} hidden h-9 w-44 rounded-lg sm:block"></div>
            </div>
        </div>

        <div class="mb-6">
            <div class="{{ $block }} mb-3 h-4 w-36"></div>
            <div class="{{ $card }} divide-y divide-vd-line">
                @foreach (range(1, 2) as $i)
                    <div class="flex items-center gap-3 p-4">
                        <div class="{{ $block }} h-9 w-9 flex-none rounded-lg"></div>
                        <div class="flex-1 space-y-2">
                            <div class="{{ $block }} h-3.5 w-1/3 max-w-[220px]"></div>
                            <div class="{{ $block }} h-3 w-2/3 max-w-[420px]"></div>
                        </div>
                        <div class="{{ $block }} h-8 w-24 flex-none rounded-lg"></div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="mb-6 grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-5">
            @foreach (range(1, 5) as $i)
                <div class="{{ $card }} space-y-3 p-4">
                    <div class="{{ $block }} h-9 w-9 rounded-lg"></div>
                    <div class="space-y-2">
                        <div class="{{ $block }} h-3 w-24 max-w-full"></div>
                        <div class="{{ $block }} h-7 w-16"></div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="grid grid-cols-1 items-start gap-4 xl:grid-cols-3">
            <div class="{{ $card }} overflow-hidden xl:col-span-2">
                <div class="flex items-center justify-between p-4 pb-3">
                    <div class="{{ $block }} h-4 w-32"></div>
                    <div class="{{ $block }} h-7 w-28 rounded-lg"></div>
                </div>
                <div class="border-y border-vd-line bg-vd-tint px-4 py-3"><div class="{{ $block }} h-3 w-full"></div></div>
                <div class="divide-y divide-vd-line">
                    @foreach (range(1, 5) as $i)
                        <div class="flex items-center gap-4 px-4 py-3">
                            <div class="flex-1 space-y-2">
                                <div class="{{ $block }} h-3.5 w-1/3 max-w-[180px]"></div>
                                <div class="{{ $block }} h-3 w-1/2 max-w-[260px]"></div>
                            </div>
                            <div class="{{ $block }} hidden h-3.5 w-24 md:block"></div>
                            <div class="{{ $block }} hidden h-5 w-16 rounded-full sm:block"></div>
                            <div class="{{ $block }} h-8 w-16 flex-none rounded-lg"></div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="space-y-4">
                <div class="{{ $card }} p-4">
                    <div class="mb-4 flex items-center justify-between">
                        <div class="{{ $block }} h-4 w-24"></div>
                        <div class="{{ $block }} h-7 w-28 rounded-lg"></div>
                    </div>
                    <div class="space-y-4">
                        @foreach (range(1, 5) as $i)
                            <div class="flex items-center justify-between gap-3">
                                <div class="{{ $block }} h-3.5 w-28"></div>
                                <div class="{{ $block }} h-3.5 w-8"></div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="{{ $card }} p-4">
                    <div class="{{ $block }} mb-4 h-4 w-32"></div>
                    <div class="space-y-4">
                        @foreach (range(1, 4) as $i)
                            <div class="flex items-start gap-3">
                                <div class="{{ $block }} h-8 w-8 flex-none rounded-lg"></div>
                                <div class="flex-1 space-y-2">
                                    <div class="{{ $block }} h-3.5 w-full"></div>
                                    <div class="{{ $block }} h-3 w-1/2"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Table: heading and action, search and filters, rows --}}
    <div class="vd-sk-v" data-v="table">
        <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
            <div class="space-y-2.5">
                <div class="{{ $block }} h-3.5 w-36"></div>
                <div class="{{ $block }} h-7 w-52 max-w-full"></div>
            </div>
            <div class="{{ $block }} h-9 w-36 rounded-lg"></div>
        </div>

        <div class="{{ $card }} overflow-hidden">
            <div class="flex flex-wrap items-center gap-3 border-b border-vd-line p-4">
                <div class="{{ $block }} h-10 w-64 max-w-full rounded-xl"></div>
                <div class="{{ $block }} hidden h-10 w-28 rounded-xl sm:block"></div>
                <div class="{{ $block }} hidden h-10 w-28 rounded-xl sm:block"></div>
            </div>
            <div class="border-b border-vd-line bg-vd-tint px-4 py-3"><div class="{{ $block }} h-3 w-full"></div></div>
            <div class="divide-y divide-vd-line">
                @foreach (range(1, 8) as $i)
                    <div class="flex items-center gap-4 px-4 py-3.5">
                        <div class="flex-1 space-y-2">
                            <div class="{{ $block }} h-3.5 w-1/3 max-w-[220px]"></div>
                            <div class="{{ $block }} h-3 w-1/2 max-w-[320px]"></div>
                        </div>
                        <div class="{{ $block }} hidden h-3.5 w-24 md:block"></div>
                        <div class="{{ $block }} hidden h-6 w-20 rounded-full sm:block"></div>
                        <div class="{{ $block }} h-8 w-16 flex-none rounded-lg"></div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Cards: heading, three summary cards, a list card (hub details, network overview) --}}
    <div class="vd-sk-v" data-v="cards">
        <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
            <div class="space-y-2.5">
                <div class="{{ $block }} h-3.5 w-36"></div>
                <div class="{{ $block }} h-7 w-52 max-w-full"></div>
            </div>
            <div class="{{ $block }} h-9 w-32 rounded-lg"></div>
        </div>

        <div class="mb-4 grid grid-cols-1 gap-4 md:grid-cols-3">
            @foreach (range(1, 3) as $i)
                <div class="{{ $card }} space-y-3 p-4">
                    <div class="{{ $block }} h-3 w-24"></div>
                    <div class="{{ $block }} h-7 w-20"></div>
                    <div class="{{ $block }} h-3 w-32 max-w-full"></div>
                </div>
            @endforeach
        </div>

        <div class="{{ $card }} overflow-hidden">
            <div class="border-b border-vd-line bg-vd-tint px-4 py-3"><div class="{{ $block }} h-3 w-40"></div></div>
            <div class="divide-y divide-vd-line">
                @foreach (range(1, 6) as $i)
                    <div class="flex items-center gap-4 px-4 py-3">
                        <div class="{{ $block }} h-8 w-8 flex-none rounded-lg"></div>
                        <div class="flex-1 space-y-2">
                            <div class="{{ $block }} h-3.5 w-1/4 max-w-[180px]"></div>
                            <div class="{{ $block }} h-3 w-1/3 max-w-[240px]"></div>
                        </div>
                        <div class="{{ $block }} hidden h-5 w-16 rounded-full sm:block"></div>
                        <div class="{{ $block }} h-8 w-16 flex-none rounded-lg"></div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Split: a conversation list on the left, a conversation on the right (Messages) --}}
    <div class="vd-sk-v" data-v="split">
        <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
            <div class="space-y-2.5">
                <div class="{{ $block }} h-3.5 w-36"></div>
                <div class="{{ $block }} h-7 w-40"></div>
            </div>
            <div class="{{ $block }} h-9 w-36 rounded-lg"></div>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-[minmax(320px,400px)_minmax(0,1fr)]">
            <div class="{{ $card }} h-[calc(100vh-280px)] min-h-[360px] overflow-hidden">
                <div class="border-b border-vd-line p-4"><div class="{{ $block }} h-10 w-full rounded-xl"></div></div>
                @foreach (range(1, 6) as $i)
                    <div class="flex items-start gap-3 border-b border-vd-line px-4 py-3">
                        <div class="{{ $block }} h-9 w-9 flex-none rounded-full"></div>
                        <div class="flex-1 space-y-2">
                            <div class="{{ $block }} h-3.5 w-2/3"></div>
                            <div class="{{ $block }} h-3 w-full"></div>
                        </div>
                        <div class="{{ $block }} h-3 w-10 flex-none"></div>
                    </div>
                @endforeach
            </div>

            <div class="{{ $card }} hidden items-center justify-center lg:flex">
                <div class="{{ $block }} h-14 w-14 rounded-full"></div>
            </div>
        </div>
    </div>

    {{-- Form: settings-style cards with labelled fields (Account Management) --}}
    <div class="vd-sk-v" data-v="form">
        <div class="mb-6 space-y-2.5">
            <div class="{{ $block }} h-3.5 w-36"></div>
            <div class="{{ $block }} h-7 w-56 max-w-full"></div>
        </div>

        <div class="grid gap-4 lg:grid-cols-3">
            <div class="space-y-4 lg:col-span-2">
                @foreach (range(1, 2) as $i)
                    <div class="{{ $card }} p-4">
                        <div class="{{ $block }} mb-4 h-4 w-40"></div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            @foreach (range(1, 4) as $j)
                                <div class="space-y-2">
                                    <div class="{{ $block }} h-3 w-24"></div>
                                    <div class="{{ $block }} h-10 w-full rounded-xl"></div>
                                </div>
                            @endforeach
                        </div>
                        <div class="{{ $block }} mt-4 h-9 w-28 rounded-lg"></div>
                    </div>
                @endforeach
            </div>
            <div class="{{ $card }} h-fit p-4">
                <div class="{{ $block }} mb-4 h-4 w-32"></div>
                <div class="space-y-4">
                    @foreach (range(1, 4) as $i)
                        <div class="flex items-center gap-3">
                            <div class="{{ $block }} h-9 w-9 flex-none rounded-lg"></div>
                            <div class="flex-1 space-y-2">
                                <div class="{{ $block }} h-3 w-3/4"></div>
                                <div class="{{ $block }} h-2.5 w-1/2"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
