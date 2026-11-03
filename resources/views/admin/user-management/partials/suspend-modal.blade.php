{{-- Suspend dialog. Needs $user and parent scope { suspendId }. Posts reasons[] + additional_details. --}}
@php
    $titleId = 'suspend-title-' . $user->id;
    $reasons = ['Policy Violation', 'Suspicious Activity', 'Repeated Violations', 'Security Concern', 'Other'];
@endphp

<div x-show="suspendId === {{ $user->id }}" x-cloak role="dialog" aria-modal="true" aria-labelledby="{{ $titleId }}"
    @click.self="suspendId = null" @keydown.escape.window="if (suspendId === {{ $user->id }}) suspendId = null"
    x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
    x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-[60] flex items-center justify-center bg-vd-ink/50 p-4">

    <div @click.stop class="w-full max-w-md overflow-hidden rounded-2xl border border-vd-line bg-white">
        <form method="POST" action="{{ route('admin.user-management.suspend', $user) }}" data-draft-key="admin-{{ auth('admin')->id() }}-user-suspend-{{ $user->id }}"
            x-data="{ reasons: [], details: '', busy: false }" @submit="busy = true"
            x-effect="if (suspendId !== {{ $user->id }}) { reasons = []; details = ''; busy = false }"
            class="flex max-h-[90vh] flex-col">
            @csrf

            <div class="flex items-start gap-3 p-5 pb-4">
                <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl border border-vd-danger-line bg-vd-danger-bg text-vd-danger-ink">
                    <x-admin.icon name="shield-alert" class="h-5 w-5" />
                </span>
                <div class="min-w-0 flex-1">
                    <h3 id="{{ $titleId }}" class="text-base font-semibold text-vd-ink">Suspend account</h3>
                    <p class="mt-0.5 text-sm text-vd-muted [overflow-wrap:anywhere]">{{ $user->name }} can't sign in until the suspension ends or you lift it.</p>
                </div>
                <button type="button" @click="suspendId = null" aria-label="Close"
                    class="-mr-1.5 -mt-1.5 flex h-8 w-8 items-center justify-center rounded-lg text-vd-muted transition-colors duration-200 hover:bg-vd-tint hover:text-vd-plum">
                    <x-admin.icon name="x" class="h-4 w-4" />
                </button>
            </div>

            <div class="thin-scroll flex-1 overflow-y-auto px-5 pb-2">
                <fieldset>
                    <legend class="mb-2 text-sm font-medium text-vd-ink">Reason <span class="text-vd-danger-ink" aria-hidden="true">*</span></legend>
                    <div class="space-y-2">
                        @foreach ($reasons as $option)
                            <label class="relative block cursor-pointer">
                                <input type="checkbox" name="reasons[]" value="{{ $option }}" x-model="reasons" class="peer absolute inset-0 opacity-0">
                                <span :class="reasons.includes(@js($option)) ? 'border-vd-plum bg-vd-tint' : 'border-vd-line-strong hover:bg-vd-tint'"
                                    class="flex items-center gap-3 rounded-xl border px-3.5 py-2.5 text-sm text-vd-ink transition-colors duration-150 peer-focus-visible:ring-2 peer-focus-visible:ring-vd-plum/40">
                                    <i :class="reasons.includes(@js($option)) ? 'border-vd-plum bg-vd-plum text-white' : 'border-vd-plum-soft text-transparent'"
                                        class="flex h-[18px] w-[18px] flex-shrink-0 items-center justify-center rounded-md border-2 transition-colors duration-150">
                                        <x-admin.icon name="check" class="h-3 w-3" stroke="3" />
                                    </i>
                                    {{ $option }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <label class="mt-5 block text-sm font-medium text-vd-ink" for="suspend-details-{{ $user->id }}">
                    Details
                    <span class="font-normal text-vd-muted" x-show="!reasons.includes('Other')">(optional)</span>
                    <span class="text-vd-danger-ink" x-show="reasons.includes('Other')" x-cloak aria-hidden="true">*</span>
                </label>
                <textarea id="suspend-details-{{ $user->id }}" name="additional_details" x-model="details" maxlength="500" rows="3"
                    :required="reasons.includes('Other')" placeholder="Note what happened for the next admin"
                    class="mt-2 w-full resize-none rounded-xl border border-vd-line-strong p-3 text-sm text-vd-ink placeholder:text-vd-muted focus:border-vd-plum focus:outline-none focus:ring-2 focus:ring-vd-plum/20"></textarea>
                <p class="mb-3 mt-1 text-right text-xs text-vd-muted" x-text="details.length + '/500'">0/500</p>

                <fieldset class="mb-4 border-t border-vd-line pt-4">
                    <legend class="mb-2 text-sm font-medium text-vd-ink">Length</legend>
                    <label for="suspend-duration-{{ $user->id }}" class="block text-xs text-vd-muted">Days (1–365)</label>
                    <input id="suspend-duration-{{ $user->id }}" type="number" name="duration_days" value="7" min="1" max="365"
                        class="mt-1 h-10 w-full rounded-xl border border-vd-line-strong px-3 text-sm text-vd-ink focus:border-vd-plum focus:outline-none focus:ring-2 focus:ring-vd-plum/20 disabled:bg-vd-tint disabled:text-vd-muted"
                        x-bind:disabled="$refs.permanent.checked" x-bind:required="!$refs.permanent.checked">
                    <label class="mt-3 flex items-center gap-2 text-sm text-vd-ink">
                        <input x-ref="permanent" type="checkbox" name="permanent" value="1" class="rounded border-vd-line-strong text-vd-plum focus:ring-vd-plum/40">
                        No end date (until you lift it)
                    </label>
                </fieldset>
            </div>

            <div class="flex gap-3 border-t border-vd-line bg-vd-tint p-4">
                <button type="button" @click="suspendId = null"
                    class="h-10 flex-1 rounded-xl border border-vd-line-strong bg-white text-sm font-semibold text-vd-ink transition-colors duration-200 hover:bg-vd-paper">
                    Keep active
                </button>
                <button type="submit" :disabled="busy || reasons.length === 0"
                    class="h-10 flex-1 rounded-xl border text-sm font-semibold transition-colors duration-200 disabled:cursor-not-allowed disabled:opacity-60 border-vd-danger-line bg-vd-danger-bg text-vd-danger-ink hover:border-vd-danger-ink">
                    <span x-text="busy ? 'Suspending…' : 'Suspend account'">Suspend account</span>
                </button>
            </div>
        </form>
    </div>
</div>
