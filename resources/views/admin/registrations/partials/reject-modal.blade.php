{{--
    Reject-registration dialog.
    Contract (unchanged, also used by the Dashboard): $user, $showExpr, $closeExpr.
    Keeps its own form state so it works in any parent Alpine scope.
--}}
@php
    $show = $showExpr ?? 'rejectOpen';
    $close = $closeExpr ?? 'rejectOpen = false';
    $titleId = 'reject-title-' . $user->id;

    $reasons = [
        'Incomplete Application',
        'Invalid Identification',
        'Information Mismatch',
        'Document Verification Failed',
        'Fraudulent Information',
        'Does Not Meet Requirements',
        'Other (please specify)',
    ];
@endphp

<div x-show="{{ $show }}" x-cloak role="dialog" aria-modal="true" aria-labelledby="{{ $titleId }}"
    @click.self="{{ $close }}" @keydown.escape.window="{{ $show }} && ({{ $close }})"
    x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
    x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-50 flex items-center justify-center bg-vd-ink/50 p-4">

    <div @click.stop class="w-full max-w-md overflow-hidden rounded-2xl border border-vd-line bg-white">
        <form method="POST" action="{{ route('admin.registrations.disapprove', $user) }}" data-draft-key="admin-{{ auth('admin')->id() }}-registration-reject-{{ $user->id }}"
            x-data="{ reason: '', details: '', busy: false }" @submit="busy = true"
            x-effect="if (!({{ $show }})) { reason = ''; details = ''; busy = false }"
            class="flex max-h-[90vh] flex-col">
            @csrf

            <div class="flex items-start gap-3 p-5 pb-4">
                <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl border border-vd-danger-line bg-vd-danger-bg text-vd-danger-ink">
                    <x-admin.icon name="x-circle" class="h-5 w-5" />
                </span>
                <div class="min-w-0 flex-1">
                    <h3 id="{{ $titleId }}" class="text-base font-semibold text-vd-ink">Reject registration</h3>
                    <p class="mt-0.5 text-sm text-vd-muted [overflow-wrap:anywhere]">{{ $user->name }} sees the reason you pick at sign-in.</p>
                </div>
                <button type="button" @click="{{ $close }}" aria-label="Close"
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
                                <input type="radio" name="reason" value="{{ $option }}" x-model="reason" required class="peer absolute inset-0 opacity-0">
                                <span :class="reason === @js($option) ? 'border-vd-plum bg-vd-tint' : 'border-vd-line-strong hover:bg-vd-tint'"
                                    class="flex items-center gap-3 rounded-xl border px-3.5 py-2.5 text-sm text-vd-ink transition-colors duration-150 peer-focus-visible:ring-2 peer-focus-visible:ring-vd-plum/40">
                                    <i :class="reason === @js($option) ? 'border-vd-plum' : 'border-vd-plum-soft'"
                                        class="flex h-[18px] w-[18px] flex-shrink-0 items-center justify-center rounded-full border-2">
                                        <b :class="reason === @js($option) ? 'scale-100' : 'scale-0'" class="h-2 w-2 rounded-full bg-vd-plum transition-transform duration-200"></b>
                                    </i>
                                    {{ $option }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <label class="mt-5 block text-sm font-medium text-vd-ink" for="reject-details-{{ $user->id }}">
                    Details
                    <span class="font-normal text-vd-muted" x-show="!reason.startsWith('Other')">(optional)</span>
                    <span class="text-vd-danger-ink" x-show="reason.startsWith('Other')" x-cloak aria-hidden="true">*</span>
                </label>
                <textarea id="reject-details-{{ $user->id }}" name="additional_details" x-model="details" maxlength="500" rows="3"
                    :required="reason.startsWith('Other')" placeholder="Tell the applicant what to fix"
                    class="mt-2 w-full resize-none rounded-xl border border-vd-line-strong p-3 text-sm text-vd-ink placeholder:text-vd-muted focus:border-vd-plum focus:outline-none focus:ring-2 focus:ring-vd-plum/20"></textarea>
                <p class="mb-3 mt-1 text-right text-xs text-vd-muted" x-text="details.length + '/500'">0/500</p>
            </div>

            <div class="flex gap-3 border-t border-vd-line bg-vd-tint p-4">
                <button type="button" @click="{{ $close }}"
                    class="h-10 flex-1 rounded-xl border border-vd-line-strong bg-white text-sm font-semibold text-vd-ink transition-colors duration-200 hover:bg-vd-paper">
                    Keep application
                </button>
                <button type="submit" :disabled="busy"
                    class="h-10 flex-1 rounded-xl border border-vd-danger-line bg-vd-danger-bg text-sm font-semibold text-vd-danger-ink transition-colors duration-200 hover:border-vd-danger-ink disabled:cursor-wait disabled:opacity-70">
                    <span x-text="busy ? 'Rejecting…' : 'Reject registration'">Reject registration</span>
                </button>
            </div>
        </form>
    </div>
</div>
