{{-- Temporary sign-in details for a new admin. Opens by itself when the session holds a generated password. --}}
@if (session('generated_password'))
    <div x-data="{
        open: true,
        copied: null,
        copy(text, key) {
            navigator.clipboard?.writeText(text).then(() => {
                this.copied = key;
                setTimeout(() => this.copied = null, 2000);
            });
        },
    }">
        <x-admin.dialog model="open" title="Admin credentials" text="Copy these and send them to the admin. You can view them again from the admin's page until they set their own password.">
            @foreach ([['email', 'Email', session('generated_email')], ['password', 'Temporary password', session('generated_password')]] as [$key, $label, $value])
                <div class="{{ $loop->first ? '' : 'mt-3' }} flex items-center justify-between gap-3 rounded-xl border border-vd-line bg-vd-tint px-4 py-3">
                    <div class="min-w-0">
                        <p class="text-xs text-vd-muted">{{ $label }}</p>
                        <p class="truncate font-mono text-sm font-semibold text-vd-ink">{{ $value }}</p>
                    </div>
                    <button type="button" @click="copy(@js($value), '{{ $key }}')" data-no-loading
                        class="inline-flex h-8 flex-shrink-0 items-center gap-1.5 rounded-lg border border-vd-line-strong bg-white px-3 text-[13px] font-medium text-vd-plum transition-colors duration-200 hover:bg-vd-lavender
                               focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-plum/40">
                        <x-admin.icon name="copy" class="h-3.5 w-3.5" x-show="copied !== '{{ $key }}'" />
                        <x-admin.icon name="check" class="h-3.5 w-3.5" x-show="copied === '{{ $key }}'" x-cloak />
                        <span x-text="copied === '{{ $key }}' ? 'Copied' : 'Copy'">Copy</span>
                    </button>
                </div>
            @endforeach

            <div class="mt-5 flex justify-end">
                <button type="button" @click="open = false"
                    class="inline-flex h-10 items-center justify-center rounded-lg bg-vd-plum px-5 text-sm font-semibold text-white transition-colors duration-200 hover:bg-vd-plum-hover
                           focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-plum/40 focus-visible:ring-offset-2">
                    Close
                </button>
            </div>
        </x-admin.dialog>
    </div>
@endif
