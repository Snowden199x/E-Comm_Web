{{--
    One dialog for every announcement: create, edit, and the delete confirmation.
    Lives inside the Alpine scope of platform-settings/index.blade.php (dlg, f, server, errors, fieldError, dirty,
    submitLabel, check, closeAnnouncement). Rows hand it their data, so it also works for rows loaded by search or paging.
--}}
@php
    $focus = 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-plum/40';
    $input = 'mt-2 w-full rounded-xl border bg-white px-3 text-sm text-vd-ink placeholder:text-vd-muted focus:border-vd-plum focus:outline-none focus:ring-2 focus:ring-vd-plum/20';
    $ghostBtn = "h-10 rounded-xl border border-vd-line-strong bg-white px-4 text-sm font-semibold text-vd-ink transition-colors duration-200 hover:bg-vd-paper {$focus}";
    $primaryBtn = "h-10 rounded-xl bg-vd-plum px-4 text-sm font-semibold text-white transition-colors duration-200 hover:bg-vd-plum-hover disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:bg-vd-plum {$focus}";
    $dangerBtn = "inline-flex h-10 items-center gap-2 rounded-xl border border-vd-danger-line bg-vd-danger-bg px-4 text-sm font-semibold text-vd-danger-ink transition-colors duration-200 hover:border-vd-danger-ink {$focus}";
@endphp

<div x-show="dlg.open" x-cloak role="dialog" aria-modal="true" aria-labelledby="ann-dialog-title"
    @click.self="closeAnnouncement()" @keydown.escape.window="dlg.open && closeAnnouncement()"
    x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
    x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-50 flex items-center justify-center bg-vd-ink/50 p-4">

    <div @click.stop class="flex max-h-[90vh] w-full max-w-lg flex-col overflow-hidden rounded-2xl border border-vd-line bg-white">

        {{-- Header --}}
        <div class="flex items-start gap-3 p-5 pb-4">
            <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl border"
                :class="dlg.confirming ? 'border-vd-danger-line bg-vd-danger-bg text-vd-danger-ink' : 'border-vd-line bg-vd-tint text-vd-plum'">
                <x-admin.icon name="megaphone" class="h-5 w-5" x-show="!dlg.confirming" />
                <x-admin.icon name="trash" class="h-5 w-5" x-show="dlg.confirming" x-cloak />
            </span>
            <div class="min-w-0 flex-1">
                <h3 id="ann-dialog-title" class="text-base font-semibold text-vd-ink"
                    x-text="dlg.confirming ? 'Delete announcement?' : (dlg.mode === 'edit' ? 'Edit announcement' : 'Create announcement')">Create announcement</h3>
                <p class="mt-0.5 text-sm text-vd-muted" x-show="!dlg.confirming">Shown to the audience you pick, after you publish.</p>
            </div>
            <button type="button" @click="closeAnnouncement()" aria-label="Close"
                class="-mr-1.5 -mt-1.5 flex h-8 w-8 items-center justify-center rounded-lg text-vd-muted transition-colors duration-200 hover:bg-vd-tint hover:text-vd-plum {{ $focus }}">
                <x-admin.icon name="x" class="h-4 w-4" />
            </button>
        </div>

        {{-- Create / edit --}}
        <form x-show="!dlg.confirming" method="POST" :action="dlg.action" novalidate @submit="check($event)" class="flex min-h-0 flex-1 flex-col">
            @csrf
            <input type="hidden" name="_method" value="PUT" :disabled="dlg.mode !== 'edit'">
            <input type="hidden" name="_form" :value="dlg.mode === 'edit' ? 'announcement:' + dlg.id : 'announcement:new'">

            <div class="thin-scroll flex-1 space-y-4 overflow-y-auto px-5 pb-5">
                <div>
                    <label for="ann-title" class="text-sm font-medium text-vd-ink">Title <span class="text-vd-danger-ink" aria-hidden="true">*</span></label>
                    <input id="ann-title" name="title" type="text" maxlength="255" autocomplete="off" x-model="f.title" x-ref="title"
                        @input="server.title = ''" :aria-invalid="!!fieldError('title')"
                        :class="fieldError('title') ? 'border-vd-danger-ink' : 'border-vd-line-strong'"
                        class="h-10 {{ $input }}">
                    <p x-show="fieldError('title')" x-text="fieldError('title')" class="mt-1 text-xs font-medium text-vd-danger-ink"></p>
                </div>

                <div>
                    <div class="flex items-baseline justify-between gap-3">
                        <label for="ann-message" class="text-sm font-medium text-vd-ink">Message <span class="text-vd-danger-ink" aria-hidden="true">*</span></label>
                        <span class="text-xs tabular-nums text-vd-muted" x-text="f.message.length + ' / 2000'">0 / 2000</span>
                    </div>
                    <textarea id="ann-message" name="message" rows="4" maxlength="2000" x-model="f.message" x-ref="message"
                        @input="server.message = ''" :aria-invalid="!!fieldError('message')"
                        :class="fieldError('message') ? 'border-vd-danger-ink' : 'border-vd-line-strong'"
                        class="resize-none p-3 {{ $input }}"></textarea>
                    <p x-show="fieldError('message')" x-text="fieldError('message')" class="mt-1 text-xs font-medium text-vd-danger-ink"></p>
                </div>

                <div>
                    <label for="ann-audience" class="text-sm font-medium text-vd-ink">Audience</label>
                    <select id="ann-audience" name="audience" x-model="f.audience" @change="server.audience = ''"
                        class="h-10 {{ $input }} border-vd-line-strong">
                        @foreach ($audiences as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>
                    <p x-show="fieldError('audience')" x-text="fieldError('audience')" class="mt-1 text-xs font-medium text-vd-danger-ink"></p>
                </div>

                <fieldset>
                    <legend class="text-sm font-medium text-vd-ink">Status</legend>
                    <div class="mt-2 grid grid-cols-3 gap-2">
                        @foreach (['draft' => 'Draft', 'scheduled' => 'Scheduled', 'published' => 'Published'] as $value => $label)
                            <label :class="f.status === '{{ $value }}' ? 'border-vd-plum bg-vd-lavender font-semibold text-vd-plum' : 'border-vd-line-strong bg-white text-vd-muted hover:bg-vd-tint'"
                                class="flex h-10 cursor-pointer items-center justify-center rounded-xl border text-[13px] font-medium transition-colors duration-200 focus-within:ring-2 focus-within:ring-vd-plum/40">
                                <input type="radio" name="status" value="{{ $value }}" x-model="f.status" @change="server.status = ''" class="sr-only">
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                    <p class="mt-1.5 text-xs text-vd-muted"
                        x-text="{ draft: 'Saved only. No one sees it.', scheduled: 'Shows to the audience at the time you pick.', published: 'Shows to the audience now.' }[f.status]"></p>
                    <p x-show="fieldError('status')" x-text="fieldError('status')" class="mt-1 text-xs font-medium text-vd-danger-ink"></p>
                </fieldset>

                <div x-show="f.status === 'scheduled'" x-cloak>
                    <label for="ann-schedule" class="text-sm font-medium text-vd-ink">Go-live date and time <span class="text-vd-danger-ink" aria-hidden="true">*</span></label>
                    <input id="ann-schedule" name="scheduled_at" type="datetime-local" x-model="f.scheduled_at" x-ref="scheduled_at"
                        :disabled="f.status !== 'scheduled'" :min="nowLocal()" @input="server.scheduled_at = ''"
                        :aria-invalid="!!fieldError('scheduled_at')"
                        :class="fieldError('scheduled_at') ? 'border-vd-danger-ink' : 'border-vd-line-strong'"
                        class="h-10 {{ $input }}">
                    <p x-show="fieldError('scheduled_at')" x-text="fieldError('scheduled_at')" class="mt-1 text-xs font-medium text-vd-danger-ink"></p>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-end gap-3 border-t border-vd-line bg-vd-tint p-4">
                <button type="button" x-show="dlg.mode === 'edit'" x-cloak @click="dlg.confirming = true" class="mr-auto {{ $dangerBtn }}">
                    <x-admin.icon name="trash" class="h-4 w-4" />
                    Delete
                </button>
                <button type="button" @click="closeAnnouncement()" class="{{ $ghostBtn }}">Cancel</button>
                <button type="submit" :disabled="!dirty" x-text="submitLabel" class="{{ $primaryBtn }}">Create announcement</button>
            </div>
        </form>

        {{-- Delete confirmation --}}
        <div x-show="dlg.confirming" x-cloak class="flex min-h-0 flex-1 flex-col">
            <p class="px-5 pb-5 text-sm text-vd-ink [overflow-wrap:anywhere]">
                “<span class="font-medium" x-text="f.title"></span>” is removed for everyone. You can't undo this.
            </p>
            <form method="POST" :action="dlg.deleteUrl" class="flex flex-wrap justify-end gap-3 border-t border-vd-line bg-vd-tint p-4">
                @csrf
                @method('DELETE')
                <button type="button" @click="dlg.confirming = false" class="{{ $ghostBtn }}">Keep announcement</button>
                <button type="submit" class="{{ $dangerBtn }}">Delete announcement</button>
            </form>
        </div>
    </div>
</div>
