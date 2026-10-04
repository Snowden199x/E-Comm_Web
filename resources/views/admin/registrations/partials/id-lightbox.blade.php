{{--
    Full-size ID viewer. Needs $user and a parent Alpine scope with { lightbox, lbSrc }
    (set by id-preview.blade.php when the thumbnail is clicked).
--}}
<div x-show="lightbox" x-cloak role="dialog" aria-modal="true" aria-label="Valid ID preview"
    @keydown.escape.window="if (lightbox) { lightbox = false; $event.preventDefault(); }" @click.self="lightbox = false"
    x-effect="if (lightbox) $nextTick(() => $refs.lbClose && $refs.lbClose.focus())"
    x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
    x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-[80] flex items-center justify-center bg-[#140a17]/80 p-4 backdrop-blur-sm">
    <div x-show="lightbox" class="relative max-h-full max-w-4xl"
        x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="opacity-0 scale-95 translate-y-2" x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
        <img :src="lbSrc" alt="Valid ID submitted by {{ $user->name }}"
            class="max-h-[85vh] w-auto rounded-xl bg-white object-contain shadow-2xl">
        <button type="button" x-ref="lbClose" @click="lightbox = false" aria-label="Close preview"
            class="absolute -right-2 -top-2 flex h-9 w-9 items-center justify-center rounded-full bg-white text-[#2B1730] shadow-lg
                   transition hover:scale-105 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/50">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18" /></svg>
        </button>
    </div>
</div>