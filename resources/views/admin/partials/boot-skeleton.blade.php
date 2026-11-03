{{--
    First-load loading screen: a skeleton of the whole admin shell (sidebar rail, top bar, page).
    No logo, no spinner, no text: it is the page's own layout drawn as placeholders. motion.js lifts it
    once the page has loaded. $variant is the page shape chosen by the layout (dashboard|table|split|form).
--}}
<div id="vd-boot" class="vd-boot" role="status" aria-live="polite">
    <span class="sr-only">Loading</span>

    <div class="vd-boot__rail" aria-hidden="true">
        <div class="vd-boot__brand">
            <div class="vd-sk-block h-9 w-9 flex-none rounded-lg"></div>
            <div class="vd-sk-block h-4 w-24"></div>
        </div>

        <div class="pt-4">
            @foreach (range(1, 9) as $i)
                <div class="vd-boot__item">
                    <div class="vd-sk-block h-5 w-5 flex-none rounded-md"></div>
                    <div class="vd-sk-block h-3.5 flex-1" style="max-width: {{ 70 + (($i * 37) % 70) }}px"></div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="vd-boot__main" aria-hidden="true">
        <div class="vd-boot__top">
            <div class="flex items-center gap-3">
                <div class="vd-sk-block h-10 w-10 rounded-xl"></div>
                <div class="vd-sk-block hidden h-4 w-32 sm:block"></div>
            </div>
            <div class="flex items-center gap-4">
                <div class="vd-sk-block h-10 w-10 rounded-full"></div>
                <div class="vd-sk-block h-10 w-10 rounded-full"></div>
                <div class="hidden space-y-2 sm:block">
                    <div class="vd-sk-block h-3.5 w-20"></div>
                    <div class="vd-sk-block h-3 w-28"></div>
                </div>
            </div>
        </div>

        <div class="vd-boot__content">
            <x-admin.skeleton :variant="$variant" />
        </div>
    </div>
</div>
