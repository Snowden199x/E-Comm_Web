{{--
    Admin document viewer popup. Rendered once by components/admin/layout.blade.php,
    so every Admin screen can open it:

        @click="$dispatch('open-document', { url, title, subtitle, filename, kind })"

    Logic lives in resources/js/admin/document-viewer.js (Alpine.data('adminDocViewer')).
    Shows images and PDFs inside the page. Nothing here opens a new tab or navigates away.
--}}
<div x-data="adminDocViewer" @open-document.window="show($event.detail)"
    @keydown.escape.window.capture="onEscape($event)" @keydown.window="onKey($event)"
    @resize.window.debounce.150ms="fit()">

    <div x-show="open" x-cloak x-ref="dialog" role="dialog" aria-modal="true" aria-labelledby="doc-viewer-title"
        @click.self="close()"
        x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-[90] flex items-center justify-center bg-[#140a17]/80 p-3 backdrop-blur-sm sm:p-5">

        <div x-show="open"
            x-transition:enter="transition duration-300 ease-vendo" x-transition:enter-start="opacity-0 translate-y-3 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
            class="flex h-full max-h-[min(92dvh,900px)] w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white shadow-[0_30px_70px_-30px_rgba(0,0,0,0.7)]">

            {{-- Header --}}
            <div class="flex items-center gap-3 border-b border-[#ece4ec] px-4 py-3 sm:px-5">
                <span aria-hidden="true" class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-[#F1E7F3] text-[#5b2963]">
                    <x-admin.icon name="image" class="h-5 w-5" x-show="kind !== 'pdf'" />
                    <x-admin.icon name="file-text" class="h-5 w-5" x-show="kind === 'pdf'" x-cloak />
                </span>

                <div class="min-w-0 flex-1">
                    <h2 id="doc-viewer-title" class="truncate font-display text-base font-semibold text-[#2B1730]" x-text="title">Document</h2>
                    <p class="flex items-center gap-1.5 truncate text-xs text-gray-500">
                        <x-admin.icon name="lock" class="h-3 w-3 flex-shrink-0" />
                        <span class="truncate" x-text="[subtitle, filename].filter(Boolean).join(' · ') || 'Private verification document'"></span>
                    </p>
                </div>

                {{-- Zoom (images only) --}}
                <div x-show="kind === 'image' && status === 'ready'" x-cloak
                    class="hidden items-center gap-1 rounded-full border border-[#e2d6e5] bg-[#FBF8FB] p-1 sm:flex" role="group" aria-label="Zoom">
                    <button type="button" @click="zoomOut()" :disabled="zoom <= 0.5" aria-label="Zoom out"
                        class="flex h-8 w-8 items-center justify-center rounded-full text-gray-600 transition duration-150 hover:bg-white hover:text-[#3b1735] disabled:opacity-40 disabled:hover:bg-transparent focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40">
                        <x-admin.icon name="zoom-out" class="h-4 w-4" />
                    </button>
                    <button type="button" @click="zoomReset()" aria-label="Fit to window"
                        class="flex h-8 min-w-[3.25rem] items-center justify-center rounded-full px-2 text-xs font-semibold tabular-nums text-[#3b1735] transition duration-150 hover:bg-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40"
                        x-text="zoomLabel">100%</button>
                    <button type="button" @click="zoomIn()" :disabled="zoom >= 4" aria-label="Zoom in"
                        class="flex h-8 w-8 items-center justify-center rounded-full text-gray-600 transition duration-150 hover:bg-white hover:text-[#3b1735] disabled:opacity-40 disabled:hover:bg-transparent focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40">
                        <x-admin.icon name="zoom-in" class="h-4 w-4" />
                    </button>
                </div>

                <button type="button" x-ref="close" @click="close()" aria-label="Close document"
                    class="-mr-1 flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full text-gray-500 transition duration-200 hover:bg-[#F1E9F1] hover:text-[#3b1735] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40">
                    <x-admin.icon name="x" class="h-5 w-5" stroke="2.2" />
                </button>
            </div>

            {{-- Stage --}}
            <div class="relative min-h-0 flex-1 bg-[#F3EEF4]" aria-live="polite" :aria-busy="status === 'loading'">

                {{-- Loading --}}
                <div x-show="status === 'loading'" x-cloak
                    x-transition:leave="transition duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                    class="absolute inset-0 flex flex-col items-center justify-center gap-3 text-sm text-gray-500" role="status">
                    <span class="h-9 w-9 animate-spin rounded-full border-[3px] border-[#3b1735]/15 border-t-[#3b1735]" aria-hidden="true"></span>
                    Loading document…
                </div>

                {{-- Error --}}
                <div x-show="status === 'error'" x-cloak role="alert"
                    x-transition:enter="transition duration-300 ease-vendo" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
                    class="absolute inset-0 flex flex-col items-center justify-center px-6 text-center">
                    <span class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-[#FBF1EE] text-[#b4452a]">
                        <x-admin.icon name="alert-circle" class="h-7 w-7" stroke="1.7" />
                    </span>
                    <p class="text-base font-semibold text-[#2B1730]" x-text="error.title"></p>
                    <p class="mt-1 max-w-md text-sm text-gray-500" x-text="error.body"></p>
                    <div class="mt-5 flex items-center gap-2.5">
                        <button type="button" x-show="error.retry" x-cloak @click="load()"
                            class="inline-flex h-10 items-center gap-2 rounded-full bg-[#3b1735] px-5 text-sm font-medium text-white transition duration-200 hover:bg-[#4d1f45] active:scale-95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40 focus-visible:ring-offset-2">
                            <x-admin.icon name="rotate-ccw" class="h-4 w-4" /> Try again
                        </button>
                        <button type="button" @click="close()"
                            class="inline-flex h-10 items-center rounded-full border border-[#cdbbd2] px-5 text-sm font-medium text-[#3b1735] transition duration-200 hover:bg-[#F7F1F7] active:scale-95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40">
                            Close
                        </button>
                    </div>
                </div>

                {{-- Image --}}
                <div x-show="status === 'ready' && kind === 'image'" x-cloak x-ref="stage"
                    x-transition:enter="transition duration-300 ease-vendo" x-transition:enter-start="opacity-0 scale-[0.98]" x-transition:enter-end="opacity-100 scale-100"
                    class="absolute inset-0 flex overflow-auto p-4 thin-scroll">
                    <template x-if="kind === 'image' && src">
                        <img :src="src" :alt="title" :style="imageStyle" x-on:load="onImageLoad($event)" x-on:error="onImageError()"
                            class="m-auto h-auto max-w-none flex-shrink-0 rounded-lg bg-white shadow-[0_10px_30px_-14px_rgba(43,23,48,0.5)]">
                    </template>
                </div>

                {{-- PDF --}}
                <template x-if="status === 'ready' && kind === 'pdf' && src">
                    <iframe :src="src + '#toolbar=1&navpanes=0&view=FitH'" :title="title"
                        class="absolute inset-0 h-full w-full border-0 bg-white"></iframe>
                </template>
            </div>
        </div>
    </div>
</div>