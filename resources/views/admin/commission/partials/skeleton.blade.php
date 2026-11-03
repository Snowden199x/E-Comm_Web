{{--
    In-page loading skeleton for the commission cards and table.
    Pass part => 'cards' or 'table'. Shown by index.blade.php while a month, search or page change is slow.
--}}
@if ($part === 'cards')
    <div class="mb-5 grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4" role="status" aria-label="Loading commission figures">
        @foreach (range(1, 4) as $i)
            <div class="rounded-xl border border-vd-line bg-white p-5">
                <div class="flex items-start justify-between gap-3">
                    <div class="vd-skeleton h-3.5 w-28"></div>
                    <div class="vd-skeleton h-9 w-9 flex-shrink-0 rounded-full"></div>
                </div>
                <div class="vd-skeleton mt-4 h-7 w-36 max-w-full"></div>
                <div class="vd-skeleton mt-3 h-3 w-24"></div>
            </div>
        @endforeach
    </div>
@else
    <div class="overflow-hidden rounded-xl border border-vd-line bg-white" role="status" aria-label="Loading sellers">
        <div class="border-b border-vd-line bg-vd-tint px-5 py-3.5"><div class="vd-skeleton h-3.5 w-full max-w-md"></div></div>
        <div class="divide-y divide-vd-line">
            @foreach (range(1, 6) as $i)
                <div class="flex items-center gap-4 px-5 py-3">
                    <div class="vd-skeleton h-9 w-9 flex-shrink-0 rounded-full"></div>
                    <div class="flex-1 space-y-2">
                        <div class="vd-skeleton h-3.5 w-1/3 max-w-[200px]"></div>
                        <div class="vd-skeleton h-3 w-1/2 max-w-[260px]"></div>
                    </div>
                    <div class="vd-skeleton hidden h-3.5 w-24 md:block"></div>
                    <div class="vd-skeleton h-3.5 w-20"></div>
                    <div class="vd-skeleton h-8 w-28 flex-shrink-0 rounded-lg"></div>
                </div>
            @endforeach
        </div>
    </div>
@endif
