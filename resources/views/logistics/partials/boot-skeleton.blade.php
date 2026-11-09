{{--
    First-load loading screen: a skeleton of the whole Logistics shell (sidebar rail, top bar, page).
    No logo, no spinner, no text: the page's own layout drawn as placeholders. resources/js/admin/motion.js lifts it
    once the page is ready (same file the Admin uses). $variant is the page shape chosen by the shell
    (dashboard|table|cards|split|form). $railItems is how many sidebar links to draw.
--}}
<div id="vd-boot" class="vd-boot" role="status" aria-live="polite">
    <span class="sr-only">Loading</span>

    <div class="vd-boot__rail" aria-hidden="true">
        <div class="vd-boot__brand">
            <div class="vd-sk-block h-9 w-9 flex-none rounded-lg"></div>
            <div class="vd-sk-block h-4 w-24"></div>
        </div>

        <div class="pt-4">
            @foreach (range(1, $railItems ?? 9) as $i)
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
            <x-logistics.skeleton :variant="$variant" />
        </div>
    </div>
</div>
