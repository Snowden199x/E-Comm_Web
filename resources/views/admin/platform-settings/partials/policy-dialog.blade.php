{{--
    Editor for one policy: version, rich-text content, and the delete confirmation.
    Opened by $dispatch('open-policy', id) from the policy list, or on load when a policy was just added
    or its last save failed. The rich-text editor starts the first time the dialog opens.
    Needs: $policy, $audience (who is notified on save).
--}}
@php
    $focus = 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-plum/40';
    $ghostBtn = "h-10 rounded-xl border border-vd-line-strong bg-white px-4 text-sm font-semibold text-vd-ink transition-colors duration-200 hover:bg-vd-paper {$focus}";
    $primaryBtn = "h-10 rounded-xl bg-vd-plum px-4 text-sm font-semibold text-white transition-colors duration-200 hover:bg-vd-plum-hover disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:bg-vd-plum {$focus}";
    $dangerBtn = "inline-flex h-10 items-center gap-2 rounded-xl border border-vd-danger-line bg-vd-danger-bg px-4 text-sm font-semibold text-vd-danger-ink transition-colors duration-200 hover:border-vd-danger-ink {$focus}";

    $failedHere = old('_form') === 'policy:'.$policy->id;
    $openOnLoad = $failedHere || (int) session('edit_policy') === $policy->id;
    $version = $failedHere ? old('version', $policy->version) : $policy->version;
    $content = $failedHere ? old('content', $policy->content ?? '') : ($policy->content ?? '');
@endphp

<div x-data="{
        isOpen: false, confirming: false, tried: false, edited: false, ready: false, quill: null, editorFailed: false,
        version: @js($version),
        content: @js($content),
        saved: { version: @js($policy->version), content: @js($policy->content ?? '') },
        serverVersion: @js($failedHere ? $errors->first('version') : ''),

        get versionError() {
            const v = this.version.trim();
            if (v === '') return 'Enter a version, like 1.1.';
            if (v.length > 20) return 'Shorten the version to 20 characters or fewer.';
            return this.serverVersion;
        },
        get isEmpty() { return this.content.replace(/<[^>]*>/g, '').replace(/&nbsp;/g, ' ').trim() === ''; },
        get dirty() { return this.version.trim() !== this.saved.version || this.edited; },

        open() {
            this.isOpen = true;
            this.confirming = false;
            this.tried = false;
            this.$nextTick(() => this.setup());
        },
        close() { this.isOpen = false; },

        setup() {
            this.$refs.version.focus();
            if (this.quill || this.editorFailed) return;
            if (typeof Quill === 'undefined') { this.editorFailed = true; return; }

            // A restored draft differs from what was saved: count it as an edit.
            this.edited = this.content !== this.saved.content;
            this.quill = new Quill(this.$refs.editor, {
                theme: 'snow',
                modules: { toolbar: [['bold', 'italic', 'underline'], [{ list: 'ordered' }, { list: 'bullet' }], ['clean']] },
            });
            this.quill.root.innerHTML = this.content;

            // Quill rewrites the HTML it is given. Ignore that first rewrite so Save stays off until a real edit.
            setTimeout(() => { this.ready = true; }, 120);
            this.quill.on('text-change', () => {
                if (!this.ready) return;
                this.edited = true;
                this.content = this.quill.root.innerHTML;
                this.$nextTick(() => this.$refs.contentField.dispatchEvent(new Event('input', { bubbles: true })));
            });
        },

        check(event) {
            this.tried = true;
            if (this.versionError) { event.preventDefault(); this.$refs.version.focus(); }
        },

        init() { if (@js($openOnLoad)) this.open(); },
    }"
    @open-policy.window="if ($event.detail === {{ $policy->id }}) open()"
    x-show="isOpen" x-cloak role="dialog" aria-modal="true" aria-labelledby="policy-title-{{ $policy->id }}"
    @click.self="close()" @keydown.escape.window="isOpen && close()"
    x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
    x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-50 flex items-center justify-center bg-vd-ink/50 p-4">

    <div @click.stop class="flex max-h-[90vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl border border-vd-line bg-white">

        {{-- Header --}}
        <div class="flex items-start gap-3 p-5 pb-4">
            <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl border"
                :class="confirming ? 'border-vd-danger-line bg-vd-danger-bg text-vd-danger-ink' : 'border-vd-line bg-vd-tint text-vd-plum'">
                <x-admin.icon name="file-text" class="h-5 w-5" x-show="!confirming" />
                <x-admin.icon name="trash" class="h-5 w-5" x-show="confirming" x-cloak />
            </span>
            <div class="min-w-0 flex-1">
                <h3 id="policy-title-{{ $policy->id }}" class="text-base font-semibold text-vd-ink"
                    x-text="confirming ? @js('Delete '.$policy->name.'?') : @js('Edit '.$policy->name)">Edit {{ $policy->name }}</h3>
                <p class="mt-0.5 text-sm text-vd-muted" x-show="!confirming">Saving notifies {{ $audience }}.</p>
            </div>
            <button type="button" @click="close()" aria-label="Close"
                class="-mr-1.5 -mt-1.5 flex h-8 w-8 items-center justify-center rounded-lg text-vd-muted transition-colors duration-200 hover:bg-vd-tint hover:text-vd-plum {{ $focus }}">
                <x-admin.icon name="x" class="h-4 w-4" />
            </button>
        </div>

        {{-- Edit --}}
        <form x-show="!confirming" method="POST" action="{{ route('admin.platform-settings.policies.update', $policy) }}" novalidate
            @submit="check($event)" data-draft-key="admin-{{ auth('admin')->id() }}-policy-{{ $policy->id }}" data-draft-hidden="content"
            class="flex min-h-0 flex-1 flex-col">
            @csrf
            @method('PUT')
            <input type="hidden" name="_form" value="policy:{{ $policy->id }}">

            <div class="thin-scroll flex-1 overflow-y-auto px-5 pb-5">
                <label for="policy-version-{{ $policy->id }}" class="text-sm font-medium text-vd-ink">Version <span class="text-vd-danger-ink" aria-hidden="true">*</span></label>
                <input id="policy-version-{{ $policy->id }}" name="version" type="text" maxlength="20" autocomplete="off" x-model="version" x-ref="version"
                    @input="serverVersion = ''" :aria-invalid="(tried || serverVersion !== '') && !!versionError"
                    :class="(tried || serverVersion !== '') && versionError ? 'border-vd-danger-ink' : 'border-vd-line-strong'"
                    class="mt-2 h-10 w-40 rounded-xl border bg-white px-3 text-sm text-vd-ink focus:border-vd-plum focus:outline-none focus:ring-2 focus:ring-vd-plum/20">
                <p x-show="(tried || serverVersion !== '') && versionError" x-text="versionError" class="mt-1 text-xs font-medium text-vd-danger-ink"></p>

                <label class="mt-4 block text-sm font-medium text-vd-ink">Content</label>
                <div x-ref="editor" x-show="!editorFailed" style="min-height: 220px;" class="mt-2 rounded-xl border border-vd-line-strong bg-white"></div>
                <input type="hidden" name="content" x-model="content" x-ref="contentField" :disabled="editorFailed">

                {{-- Fallback when the editor script did not load --}}
                <div x-show="editorFailed" x-cloak>
                    <p class="mt-2 rounded-xl border border-vd-warning-line bg-vd-warning-bg px-3 py-2 text-xs text-vd-warning-ink">
                        The editor didn't load. Edit the text below, or refresh the page to retry.
                    </p>
                    <textarea name="content" rows="10" x-model="content" @input="edited = true" :disabled="!editorFailed"
                        class="mt-2 w-full rounded-xl border border-vd-line-strong p-3 text-sm text-vd-ink focus:border-vd-plum focus:outline-none focus:ring-2 focus:ring-vd-plum/20"></textarea>
                </div>

                <p x-show="isEmpty" x-cloak class="mt-2 text-xs text-vd-warning-ink">Add content to publish this policy. Empty policies stay hidden from users.</p>
            </div>

            <div class="flex flex-wrap items-center justify-end gap-3 border-t border-vd-line bg-vd-tint p-4">
                <button type="button" @click="confirming = true" class="mr-auto {{ $dangerBtn }}">
                    <x-admin.icon name="trash" class="h-4 w-4" />
                    Delete
                </button>
                <button type="button" @click="close()" class="{{ $ghostBtn }}">Cancel</button>
                <button type="submit" :disabled="!dirty" class="{{ $primaryBtn }}">Save changes</button>
            </div>
        </form>

        {{-- Delete confirmation --}}
        <div x-show="confirming" x-cloak class="flex min-h-0 flex-1 flex-col">
            <p class="px-5 pb-5 text-sm text-vd-ink">Users can no longer read {{ $policy->name }}, and its content is removed. You can't undo this.</p>
            <form method="POST" action="{{ route('admin.platform-settings.policies.destroy', $policy) }}" class="flex flex-wrap justify-end gap-3 border-t border-vd-line bg-vd-tint p-4">
                @csrf
                @method('DELETE')
                <button type="button" @click="confirming = false" class="{{ $ghostBtn }}">Keep policy</button>
                <button type="submit" class="{{ $dangerBtn }}">Delete policy</button>
            </form>
        </div>
    </div>
</div>
