{{--
    Banner notifications. Included once, inside the sticky header, by components/buyer/layout.blade.php.

    Show one from any Alpine scope or script:
        $store.banner.show({ type: 'success', title: 'Added to cart', text: '', action: { label: 'View cart', href: '/buyer/cart' } })
        $store.banner.show({ type: 'error', title: "Couldn't save", text: 'Check your network and try again.', action: { label: 'Retry', onClick: () => retry() } })

    type      success | error | warning | info   (default: success)
    title     short, says what happened
    text      optional, says what to do next (always include it for errors)
    action    optional { label, href | onClick }. The label starts with a verb.
    duration  ms before it closes. Errors stay longer. 0 keeps it until dismissed.

    $store.ui.say('message') still works for older callers and maps to this banner.
--}}
<script>
    document.addEventListener('alpine:init', () => {
        const motionOff = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches
            || document.documentElement.classList.contains('vb-calm');

        Alpine.store('banner', {
            items: [],
            seq: 0,
            show({ type = 'success', title = '', text = '', action = null, duration } = {}) {
                if (!title && !text) return null;
                const id = ++this.seq;
                const ms = duration ?? (type === 'error' ? 9000 : 5000);
                // The same message shown twice replaces the first
                this.items = this.items.filter((item) => item.title !== title || item.text !== text);
                const item = { id, type, title, text, action, duration: ms, leaving: false, timed: ms > 0 && !motionOff() };
                this.items = [...this.items, item].slice(-3);
                // Reduced motion: no progress bar, so close on a plain timer (a little longer)
                if (ms > 0 && !item.timed) setTimeout(() => this.dismiss(id), ms + 2000);
                return id;
            },
            dismiss(id) {
                const item = this.items.find((entry) => entry.id === id);
                if (!item || item.leaving) return;
                item.leaving = true;
                this.items = [...this.items];
                setTimeout(() => { this.items = this.items.filter((entry) => entry.id !== id); }, 220);
            },
            run(item) {
                if (item.action?.onClick) item.action.onClick();
                if (item.action?.href) { location.href = item.action.href; return; }
                this.dismiss(item.id);
            },
        });

        // Older callers: $store.ui.say('Saved'). Messages that describe a failure show as errors.
        Alpine.store('ui', {
            say(message, type) {
                const failed = /couldn.?t|could not|can.?t|cannot|failed|error|no connection|expired|try again/i.test(message);
                const kind = type || (failed ? 'error' : 'success');
                Alpine.store('banner').show({ type: kind, title: kind === 'error' ? "That didn't work" : message, text: kind === 'error' ? message : '' });
            },
        });
    });
</script>

<div x-data class="vb-banners pointer-events-none absolute inset-x-0 top-full z-10 mx-auto flex max-w-[1200px] flex-col items-stretch gap-2 px-3 pt-3 sm:items-center sm:px-4" aria-live="polite">
    <template x-for="b in $store.banner.items" :key="b.id">
        <div class="vb-banner pointer-events-auto relative w-full overflow-hidden rounded-xl border sm:w-[460px]"
            :class="['vb-banner--' + b.type, b.leaving ? 'is-leaving' : '']"
            :role="b.type === 'error' ? 'alert' : 'status'">
            <div class="flex items-start gap-3 py-3 pl-3.5 pr-2.5">
                <span class="vb-banner__icon mt-px grid h-7 w-7 flex-shrink-0 place-items-center rounded-full" aria-hidden="true">
                    <svg x-show="b.type === 'success'" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path class="vb-draw" d="m5 12.5 4.5 4.5L19 7.5" /></svg>
                    <svg x-show="b.type === 'error'" x-cloak class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M12 7v6M12 17h.01" /></svg>
                    <svg x-show="b.type === 'warning'" x-cloak class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 4 3 20h18zM12 10v4M12 17.5h.01" /></svg>
                    <svg x-show="b.type === 'info'" x-cloak class="vb-banner__bell h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 16V11a6 6 0 0 1 12 0v5l1.5 2h-15zM10 20a2 2 0 0 0 4 0" /></svg>
                </span>

                <div class="min-w-0 flex-1 pt-0.5">
                    <p class="text-[13px] font-semibold leading-5" x-text="b.title"></p>
                    <p x-show="b.text" class="line-clamp-2 text-[12px] leading-[18px] opacity-90" x-text="b.text"></p>
                    <button type="button" x-show="b.action" @click="$store.banner.run(b)" x-text="b.action?.label"
                        class="vb-banner__action mt-1.5 inline-flex h-7 items-center rounded-md px-2.5 text-[12px] font-semibold transition-colors duration-200"></button>
                </div>

                <button type="button" @click="$store.banner.dismiss(b.id)" aria-label="Dismiss"
                    class="vb-banner__close grid h-7 w-7 flex-shrink-0 place-items-center rounded-full transition-colors duration-200">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M6 6l12 12M18 6 6 18" /></svg>
                </button>
            </div>
            <span x-show="b.timed" class="vb-banner__bar" :style="'--dur:' + b.duration + 'ms'" @animationend="$store.banner.dismiss(b.id)"></span>
        </div>
    </template>
</div>
