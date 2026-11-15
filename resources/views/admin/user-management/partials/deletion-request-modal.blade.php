{{--
    Buyer/seller account deletion request. Needs $user and parent scope { deletionId }.
    Shows what the user submitted. While the request is pending, the footer walks through one choice:
    Approve deletion (asks to confirm, because it closes the account) or Reject request (optional note).
    The panel resets to the first step every time it closes.
--}}
@php
    $deletion = $user->latestDeletionRequest;
    $titleId = 'deletion-title-' . $user->id;
    $pending = (bool) $deletion?->isPending();
    $roleLabel = ucwords(str_replace('_', ' ', $user->role));
    $statusPill = [
        'pending' => ['Pending review', 'pending', 'clock'],
        'approved' => ['Approved', 'success', 'check-circle'],
        'rejected' => ['Rejected', 'danger', 'x-circle'],
    ][$deletion?->status] ?? ['No request', 'neutral', null];
    $waitDays = $pending && $deletion->requested_at ? (int) $deletion->requested_at->diffInDays(now()) : 0;
    $focus = 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-plum/40';
    $btn = "inline-flex h-10 flex-1 items-center justify-center gap-2 rounded-lg border px-4 text-sm font-semibold transition-colors duration-200 {$focus}";
    $btnSecondary = $btn . ' min-w-[110px] flex-none border-vd-line-strong bg-white text-vd-ink hover:bg-vd-paper';
    $btnSuccess = $btn . ' border-vd-success-line bg-vd-success-bg text-vd-success-ink hover:border-vd-success-ink';
    $btnDanger = $btn . ' border-vd-danger-line bg-vd-danger-bg text-vd-danger-ink hover:border-vd-danger-ink';
@endphp

<div x-show="deletionId === {{ $user->id }}" x-cloak role="dialog" aria-modal="true" aria-labelledby="{{ $titleId }}"
    @click.self="deletionId = null"
    @keydown.escape.window="if (deletionId === {{ $user->id }}) deletionId = null"
    x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
    x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-[65] flex items-center justify-center bg-vd-ink/50 p-3 sm:p-4">

    <div @click.stop x-data="{ mode: 'choose' }"
        x-effect="if (deletionId !== {{ $user->id }}) mode = 'choose'"
        x-transition:enter="transition duration-300 ease-vendo" x-transition:enter-start="opacity-0 translate-y-3 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
        class="flex max-h-[calc(100dvh-1.5rem)] w-full max-w-lg flex-col overflow-hidden rounded-2xl border border-vd-line-strong bg-white sm:max-h-[calc(100dvh-2rem)]">

        {{-- Header: who asked, and where the request stands --}}
        <div class="flex items-start gap-3 border-b border-vd-line px-5 py-4">
            <x-admin.avatar :user="$user" size="h-10 w-10" text="text-sm" />
            <div class="min-w-0 flex-1">
                <h3 id="{{ $titleId }}" class="font-display text-base font-semibold leading-snug text-vd-ink">Deletion request</h3>
                <p class="mt-0.5 text-[13px] text-vd-muted [overflow-wrap:anywhere]">{{ $user->name }} · {{ $roleLabel }}</p>
            </div>
            <x-admin.status-pill :tone="$statusPill[1]" :icon="$statusPill[2]" class="flex-shrink-0">{{ $statusPill[0] }}</x-admin.status-pill>
            <button type="button" @click="deletionId = null" aria-label="Close"
                class="-mr-2 flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full text-vd-muted transition-colors duration-200 hover:bg-vd-lavender hover:text-vd-plum {{ $focus }}">
                <x-admin.icon name="x" class="h-4 w-4" />
            </button>
        </div>

        {{-- Body --}}
        <div class="thin-scroll min-h-0 flex-1 overflow-y-auto p-5">
            @if (! $deletion)
                <x-admin.empty-state size="sm" icon="inbox" title="No deletion request" text="This user has not asked to delete their account.">
                    <button type="button" @click="deletionId = null"
                        class="inline-flex h-8 items-center justify-center rounded-lg border border-vd-line-strong bg-white px-3 text-[13px] font-medium text-vd-plum transition-colors duration-200 hover:bg-vd-tint {{ $focus }}">Close</button>
                </x-admin.empty-state>
            @else
                <div class="space-y-4 text-[13px]">
                    <p class="text-vd-muted">
                        Requested {{ $deletion->requested_at?->format('M j, Y, g:i A') ?? 'on an unknown date' }}
                        @if ($pending && $deletion->requested_at)
                            · <span @class(['font-medium', 'text-vd-warning-ink' => $waitDays >= 3])>waiting {{ $deletion->requested_at->diffForHumans(null, \Carbon\CarbonInterface::DIFF_ABSOLUTE) }}</span>
                        @endif
                    </p>

                    <div>
                        <p class="mb-1.5 text-xs font-medium text-vd-muted">Reasons</p>
                        <div class="flex flex-wrap gap-1.5">
                            @forelse ($deletion->reasons ?? [] as $reason)
                                <span class="inline-flex items-center rounded-full border border-vd-line-strong bg-vd-tint px-2.5 py-0.5 text-xs font-medium text-vd-ink">{{ $reason }}</span>
                            @empty
                                <span class="text-vd-muted">No reason chosen.</span>
                            @endforelse
                        </div>
                    </div>

                    <div>
                        <p class="mb-1.5 text-xs font-medium text-vd-muted">In their words</p>
                        <p class="whitespace-pre-line rounded-xl border border-vd-line bg-vd-paper p-3 text-sm text-vd-ink [overflow-wrap:anywhere]">{{ $deletion->comments ?: 'No comment added.' }}</p>
                    </div>

                    @if ($pending)
                        {{-- What each choice does, before the admin makes it --}}
                        <dl class="divide-y divide-vd-line overflow-hidden rounded-xl border border-vd-line">
                            <div class="flex items-start gap-3 p-3">
                                <span class="mt-0.5 flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-vd-success-bg text-vd-success-ink"><x-admin.icon name="check" class="h-3.5 w-3.5" /></span>
                                <div><dt class="font-medium text-vd-ink">Approve deletion</dt><dd class="text-vd-muted">Closes the account. The user can't sign in. Order records stay.</dd></div>
                            </div>
                            <div class="flex items-start gap-3 p-3">
                                <span class="mt-0.5 flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-vd-danger-bg text-vd-danger-ink"><x-admin.icon name="x" class="h-3.5 w-3.5" /></span>
                                <div><dt class="font-medium text-vd-ink">Reject request</dt><dd class="text-vd-muted">The account stays active. You can leave a note for other admins.</dd></div>
                            </div>
                        </dl>
                    @else
                        <div class="space-y-1.5 rounded-xl border border-vd-line bg-vd-tint p-3">
                            <p class="flex items-start justify-between gap-4"><span class="text-vd-muted">Reviewed</span><span class="text-right font-medium text-vd-ink">{{ $deletion->reviewed_at?->format('M j, Y, g:i A') ?? '—' }}</span></p>
                            <p class="flex items-start justify-between gap-4"><span class="text-vd-muted">By</span><span class="text-right font-medium text-vd-ink">{{ $deletion->reviewer?->name ?? 'Admin' }}</span></p>
                            @if ($deletion->admin_notes)
                                <p class="border-t border-vd-line pt-1.5"><span class="text-vd-muted">Admin note</span> <span class="text-vd-ink [overflow-wrap:anywhere]">{{ $deletion->admin_notes }}</span></p>
                            @endif
                        </div>
                    @endif
                </div>
            @endif
        </div>

        {{-- Decision, only while the request is waiting --}}
        @if ($pending)
            <div class="border-t border-vd-line bg-vd-tint p-4">
                <div x-show="mode === 'choose'" class="flex flex-wrap gap-3">
                    <button type="button" @click="mode = 'reject'; $nextTick(() => $refs.note.focus())" class="{{ $btnDanger }}">
                        <x-admin.icon name="x-circle" class="h-4 w-4" />
                        Reject request
                    </button>
                    <button type="button" @click="mode = 'approve'" class="{{ $btnSuccess }}">
                        <x-admin.icon name="check-circle" class="h-4 w-4" />
                        Approve deletion
                    </button>
                </div>

                <form x-show="mode === 'approve'" x-cloak method="POST" action="{{ route('admin.user-management.deletion-request.approve', $user) }}" class="space-y-3">
                    @csrf
                    <p class="text-sm text-vd-ink"><span class="font-semibold">Close {{ $user->name }}'s account?</span> They can't sign in afterwards. You can activate the account again from their profile.</p>
                    <div class="flex flex-wrap gap-3">
                        <button type="button" @click="mode = 'choose'" class="{{ $btnSecondary }}">Go back</button>
                        <button type="submit" class="{{ $btnSuccess }}">Approve and close account</button>
                    </div>
                </form>

                <form x-show="mode === 'reject'" x-cloak method="POST" action="{{ route('admin.user-management.deletion-request.reject', $user) }}"
                    x-data="{ note: @js((string) old('admin_notes')) }" class="space-y-2">
                    @csrf
                    <label class="block text-xs font-medium text-vd-muted" for="deletion-note-{{ $user->id }}">Note for other admins <span class="font-normal">(optional)</span></label>
                    <textarea id="deletion-note-{{ $user->id }}" name="admin_notes" rows="2" maxlength="500" x-ref="note" x-model="note"
                        class="block w-full rounded-lg border border-vd-line-strong bg-white px-3 py-2 text-[13px] text-vd-ink placeholder:text-vd-muted focus:border-vd-plum focus:outline-none focus:ring-2 focus:ring-vd-plum/20"
                        placeholder="Example: Open orders still need delivery."></textarea>
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-xs text-vd-danger-ink">{{ $errors->first('admin_notes') }}</p>
                        <p class="text-xs tabular-nums text-vd-muted"><span x-text="note.length">0</span> / 500</p>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <button type="button" @click="mode = 'choose'" class="{{ $btnSecondary }}">Go back</button>
                        <button type="submit" class="{{ $btnDanger }}">Reject request</button>
                    </div>
                </form>
            </div>
        @endif
    </div>
</div>
