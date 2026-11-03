@props(['variant' => 'table'])

{{--
    Page-shaped loading skeleton (flat blocks, no gradients). Five layouts live in the markup; the CSS in
    resources/css/admin/motion.css shows only one of them.
      variant="dashboard|table|cards|split|form"  draws that layout
      variant="auto"                        follows body[data-skeleton] (set by the sidebar link that was clicked)
    Used by admin/partials/boot-skeleton.blade.php (first load) and the layout (page-to-page navigation).
--}}
@php
    $block = 'vd-sk-block';
    $card = 'vd-sk-card';
@endphp

<div class="vd-sk mx-auto w-full max-w-[1440px] p-4 sm:p-6 lg:p-7" data-active="{{ $variant }}" aria-hidden="true">

    {{-- Dashboard: heading, four stat cards, chart + summary, two lists, and the side rail --}}
    <div class="vd-sk-v" data-v="dashboard">
        <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
            <div class="space-y-2.5">
                <div class="{{ $block }} h-3.5 w-36"></div>
                <div class="{{ $block }} h-7 w-64 max-w-full"></div>
            </div>
            <div class="flex gap-2">
                <div class="{{ $block }} h-9 w-40 rounded-lg"></div>
                <div class="{{ $block }} hidden h-9 w-32 rounded-lg sm:block"></div>
                <div class="{{ $block }} hidden h-9 w-40 rounded-lg md:block"></div>
            </div>
        </div>

        <div class="grid grid-cols-1 items-start gap-5 xl:grid-cols-4">
            <div class="space-y-5 xl:col-span-3">
                <div class="grid grid-cols-2 gap-5 lg:grid-cols-4">
                    @foreach (range(1, 4) as $i)
                        <div class="{{ $card }} flex items-center gap-4 p-5">
                            <div class="{{ $block }} h-12 w-12 flex-none rounded-full"></div>
                            <div class="flex-1 space-y-2.5">
                                <div class="{{ $block }} h-3 w-20"></div>
                                <div class="{{ $block }} h-6 w-24 max-w-full"></div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
                    <div class="{{ $card }} p-5 lg:col-span-2">
                        <div class="{{ $block }} mb-1.5 h-4 w-36"></div>
                        <div class="{{ $block }} mb-4 h-3 w-56 max-w-full"></div>
                        <div class="{{ $block }} h-[240px] w-full rounded-xl sm:h-[260px]"></div>
                    </div>
                    <div class="{{ $card }} p-5">
                        <div class="{{ $block }} mb-5 h-4 w-32"></div>
                        <div class="space-y-5">
                            @foreach (range(1, 3) as $i)
                                <div class="space-y-2">
                                    <div class="flex justify-between"><div class="{{ $block }} h-3 w-24"></div><div class="{{ $block }} h-3 w-12"></div></div>
                                    <div class="{{ $block }} h-1.5 w-full rounded-full"></div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                    @foreach (range(1, 2) as $i)
                        <div class="{{ $card }} p-5">
                            <div class="mb-4 flex items-center justify-between">
                                <div class="{{ $block }} h-4 w-40"></div>
                                <div class="{{ $block }} h-8 w-20 rounded-lg"></div>
                            </div>
                            <div class="space-y-4">
                                @foreach (range(1, 4) as $j)
                                    <div class="flex items-center gap-3">
                                        <div class="flex-1 space-y-2">
                                            <div class="{{ $block }} h-3.5 w-2/3"></div>
                                            <div class="{{ $block }} h-3 w-1/3"></div>
                                        </div>
                                        <div class="{{ $block }} h-8 w-24 flex-none rounded-lg"></div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="space-y-5">
                <div class="{{ $card }} p-5">
                    <div class="{{ $block }} mb-4 h-4 w-28"></div>
                    <div class="space-y-4">
                        @foreach (range(1, 4) as $i)
                            <div class="flex items-start gap-3">
                                <div class="{{ $block }} h-8 w-8 flex-none rounded-lg"></div>
                                <div class="flex-1 space-y-2">
                                    <div class="{{ $block }} h-3.5 w-3/4"></div>
                                    <div class="{{ $block }} h-3 w-1/2"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="{{ $card }} p-5">
                    <div class="{{ $block }} mb-4 h-4 w-40"></div>
                    <div class="space-y-4">
                        @foreach (range(1, 3) as $i)
                            <div class="flex items-center gap-3"><div class="{{ $block }} h-6 w-6 flex-none rounded-md"></div><div class="{{ $block }} h-3.5 flex-1"></div><div class="{{ $block }} h-4 w-6"></div></div>
                        @endforeach
                    </div>
                </div>
                <div class="{{ $card }} p-5">
                    <div class="{{ $block }} mb-4 h-4 w-36"></div>
                    <div class="{{ $block }} mb-2 h-4 w-3/4"></div>
                    <div class="{{ $block }} mb-4 h-3 w-full"></div>
                    <div class="{{ $block }} h-9 w-36 rounded-xl"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Table: title and action, stat cards, filter bar, rows --}}
    <div class="vd-sk-v" data-v="table">
        <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
            <div class="space-y-2.5">
                <div class="{{ $block }} h-8 w-56 max-w-full"></div>
                <div class="{{ $block }} h-3.5 w-72 max-w-full"></div>
            </div>
            <div class="{{ $block }} h-10 w-36 rounded-xl"></div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach (range(1, 4) as $i)
                <div class="{{ $card }} p-4">
                    <div class="{{ $block }} mb-3 h-3 w-24"></div>
                    <div class="{{ $block }} h-7 w-16"></div>
                </div>
            @endforeach
        </div>

        <div class="{{ $card }} mt-4 overflow-hidden">
            <div class="flex flex-wrap items-center gap-3 border-b border-vd-line p-4">
                <div class="{{ $block }} h-10 w-64 max-w-full rounded-xl"></div>
                <div class="{{ $block }} hidden h-10 w-32 rounded-xl sm:block"></div>
                <div class="{{ $block }} hidden h-10 w-32 rounded-xl sm:block"></div>
            </div>
            <div class="divide-y divide-vd-line">
                @foreach (range(1, 8) as $i)
                    <div class="flex items-center gap-4 px-4 py-3.5">
                        <div class="{{ $block }} h-10 w-10 flex-none rounded-full"></div>
                        <div class="flex-1 space-y-2">
                            <div class="{{ $block }} h-3.5 w-1/3 max-w-[220px]"></div>
                            <div class="{{ $block }} h-3 w-1/2 max-w-[320px]"></div>
                        </div>
                        <div class="{{ $block }} hidden h-3.5 w-24 md:block"></div>
                        <div class="{{ $block }} hidden h-6 w-20 rounded-full sm:block"></div>
                        <div class="{{ $block }} h-8 w-8 flex-none rounded-lg"></div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Cards: heading, three status cards, then a list of rows (Category requests) --}}
    <div class="vd-sk-v" data-v="cards">
        <div class="mb-4 space-y-2.5">
            <div class="{{ $block }} h-7 w-52 max-w-full"></div>
            <div class="{{ $block }} h-3.5 w-72 max-w-full"></div>
        </div>

        <div class="mb-5 grid grid-cols-1 gap-3 sm:grid-cols-3 sm:gap-4">
            @foreach (range(1, 3) as $i)
                <div class="{{ $block }} h-[62px] rounded-[18px]"></div>
            @endforeach
        </div>

        <div class="{{ $card }} overflow-hidden">
            <div class="border-b border-vd-line bg-vd-tint px-4 py-3"><div class="{{ $block }} h-3 w-40"></div></div>
            <div class="divide-y divide-vd-line">
                @foreach (range(1, 6) as $i)
                    <div class="flex items-center gap-4 px-4 py-3">
                        <div class="{{ $block }} h-8 w-8 flex-none rounded-full"></div>
                        <div class="flex-1 space-y-2">
                            <div class="{{ $block }} h-3.5 w-1/4 max-w-[180px]"></div>
                            <div class="{{ $block }} h-3 w-1/3 max-w-[220px]"></div>
                        </div>
                        <div class="{{ $block }} hidden h-5 w-28 rounded-full md:block"></div>
                        <div class="{{ $block }} hidden h-3.5 w-20 sm:block"></div>
                        <div class="{{ $block }} h-5 w-16 rounded-full"></div>
                        <div class="{{ $block }} h-8 w-16 flex-none rounded-lg"></div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Split: toolbar, tabs, a list on the left and a conversation on the right (Messages) --}}
    <div class="vd-sk-v" data-v="split">
        <div class="mb-5 space-y-2.5">
            <div class="{{ $block }} h-8 w-40"></div>
            <div class="{{ $block }} h-3.5 w-96 max-w-full"></div>
        </div>

        <div class="mb-5 flex items-center justify-between gap-3">
            <div class="{{ $block }} h-10 w-full max-w-[400px] rounded-full"></div>
            <div class="hidden gap-3 sm:flex">
                <div class="{{ $block }} h-10 w-32 rounded-full"></div>
                <div class="{{ $block }} h-10 w-32 rounded-full"></div>
            </div>
        </div>

        <div class="{{ $card }} mb-5 flex gap-2 p-1.5">
            <div class="{{ $block }} h-9 w-20 rounded-lg"></div>
            <div class="{{ $block }} h-9 w-28 rounded-lg"></div>
            <div class="{{ $block }} h-9 w-36 rounded-lg"></div>
        </div>

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-[minmax(340px,400px)_minmax(0,1fr)]">
            <div class="{{ $card }} h-[calc(100vh-380px)] min-h-[360px] overflow-hidden">
                <div class="border-b border-vd-line bg-vd-tint px-4 py-3"><div class="{{ $block }} h-3.5 w-full"></div></div>
                @foreach (range(1, 7) as $i)
                    <div class="grid grid-cols-[minmax(0,1fr)_88px_minmax(0,0.9fr)] items-center gap-3 border-b border-vd-line px-4 py-3">
                        <div class="space-y-2"><div class="{{ $block }} h-3.5 w-3/4"></div><div class="{{ $block }} h-3 w-full"></div></div>
                        <div class="{{ $block }} h-5 w-16 rounded-full"></div>
                        <div class="space-y-2"><div class="{{ $block }} h-3.5 w-4/5"></div><div class="{{ $block }} h-3 w-1/3"></div></div>
                    </div>
                @endforeach
            </div>

            <div class="{{ $card }} hidden items-center justify-center lg:flex">
                <div class="{{ $block }} h-16 w-16 rounded-full"></div>
            </div>
        </div>
    </div>

    {{-- Form: settings-style cards with labelled fields --}}
    <div class="vd-sk-v" data-v="form">
        <div class="mb-6 space-y-2.5">
            <div class="{{ $block }} h-8 w-60 max-w-full"></div>
            <div class="{{ $block }} h-3.5 w-80 max-w-full"></div>
        </div>

        <div class="grid gap-4 lg:grid-cols-3">
            <div class="space-y-4 lg:col-span-2">
                @foreach (range(1, 2) as $i)
                    <div class="{{ $card }} p-5">
                        <div class="{{ $block }} mb-5 h-4 w-40"></div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            @foreach (range(1, 4) as $j)
                                <div class="space-y-2">
                                    <div class="{{ $block }} h-3 w-24"></div>
                                    <div class="{{ $block }} h-10 w-full rounded-xl"></div>
                                </div>
                            @endforeach
                        </div>
                        <div class="{{ $block }} mt-5 h-10 w-28 rounded-xl"></div>
                    </div>
                @endforeach
            </div>
            <div class="{{ $card }} h-fit p-5">
                <div class="{{ $block }} mb-5 h-4 w-32"></div>
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
