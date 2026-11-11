{{--
    Buyer/seller account deletion request. Needs $user and parent scope { deletionId }.
    Shows the request the user submitted; when there is none the panel is simply empty.
    Approving closes the account (deactivated), rejecting keeps it active.
--}}
@php
    $deletion = $user->latestDeletionRequest;
    $titleId = 'deletion-title-' . $user->id;
    $statusPill = [
        'pending' => ['Pending review', 'pending', 'clock'],
        'approved' => ['Approved', 'success', 'check-circle'],
        'rejected' => ['Rejected', 'danger', 'x-circle'],
    ][$deletion?->status] ?? ['No request', 'neutral', null];
    $btnBase = 'h-10 rounded-xl border text-sm font-semibold transition-colors duration-200 disabled:cursor-not-allowed disabled:opacity-60';
@endphp

<div x-show="deletionId === {{ $user->id }}" x-cloak role="dialog" aria-modal="true" aria-labelledby="{{ $titleId }}"
    @click.self="deletionId = null"
    @keydown.escape.window="if (deletionId === {{ $user->id }}) deletionId = null"
    x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
    x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-[65] flex items-center justify-center bg-vd-ink/50 p-3 sm:p-4">

    <div @click.stop
        x-transition:enter="transition duration-300 ease-vendo" x-transition:enter-start="opacity-0 translate-y-3 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
        class="flex max-h-[calc(100dvh-1.5rem)] w-full max-w-2xl flex-col overflow-hidden rounded-2xl border border-vd-line bg-white sm:max-h-[calc(100dvh-2rem)]">

        {{-- Header --}}
        <div class="flex items-start gap-3 border-b border-vd-line px-5 py-4">
            <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl border border-vd-line-strong bg-vd-tint text-vd-plum">
                <x-admin.icon name="user-x" class="h-5 w-5" />
            </span>
            <div class="min-w-0 flex-1">
                <h3 id="{{ $titleId }}" class="text-base font-semibold text-vd-ink">User request for deletion of account</h3>
                <p class="mt-0.5 text-sm text-vd-muted [overflow-wrap:anywhere]">{{ $user->name }} · {{ ucwords(str_replace('_', ' ', $user->role)) }}</p>
            </div>
            <x-admin.status-pill :tone="$statusPill[1]" :icon="$statusPill[2]">{{ $statusPill[0] }}</x-admin.status-pill>
            <button type="button" @click="deletionId = null" aria-label="Close"
                class="-mr-1.5 flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg text-vd-muted transition-colors duration-200 hover:bg-vd-tint hover:text-vd-plum">
                <x-admin.icon name="x" class="h-4 w-4" />
            </button>
        </div>

        {{-- Body --}}
        <div class="flex-1 overflow-y-auto p-5 thin-scroll">
            @if (! $deletion)
                <x-admin.empty-state icon="inbox" title="No deletion request"
                    text="This user has not asked for their account to be deleted." />
            @else
                <dl class="space-y-4 text-[13px]">
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-vd-muted">Requested</dt>
                        <dd class="text-right font-medium text-vd-ink">{{ $deletion->requested_at?->format('M j, Y · g:i A') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-vd-muted">Reasons</dt>
                        <dd class="mt-1.5 flex flex-wrap gap-1.5">
                            @forelse ($deletion->reasons ?? [] as $reason)
                                <span class="rounded-full border border-vd-line bg-vd-tint px-2.5 py-0.5 text-xs font-medium text-vd-ink">{{ $reason }}</span>
                            @empty
                                <span class="text-vd-muted">No reason recorded</span>
                            @endforelse
                        </dd>
                    </div>
                    <div>
                        <dt class="text-vd-muted">What the user told us</dt>
                        <dd class="mt-1.5 whitespace-pre-line rounded-xl border border-vd-line bg-vd-paper p-3 text-sm text-vd-ink">{{ $deletion->comments ?: 'No comment added.' }}</dd>
                    </div>
                </dl>

                @if ($deletion->status !== 'pending')
                    <div class="mt-4 space-y-1.5 rounded-xl border border-vd-line bg-vd-tint p-3 text-[13px]">
                        <p class="flex items-start justify-between gap-4"><span class="text-vd-muted">Reviewed</span><span class="font-medium text-vd-ink">{{ $deletion->reviewed_at?->format('M j, Y · g:i A') ?? '—' }}</span></p>
                        <p class="flex items-start justify-between gap-4"><span class="text-vd-muted">By</span><span class="font-medium text-vd-ink">{{ $deletion->reviewer?->name ?? 'Admin' }}</span></p>
                        @if ($deletion->admin_notes)
                            <p class="border-t border-vd-line pt-1.5"><span class="text-vd-muted">Admin note</span> <span class="text-vd-ink">{{ $deletion->admin_notes }}</span></p>
                        @endif
                    </div>
                @endif
            @endif
        </div>

        {{-- Decision, only while the request is waiting --}}
        @if ($deletion?->isPending())
            <div class="border-t border-vd-line bg-vd-tint p-4">
                <form method="POST" action="{{ route('admin.user-management.deletion-request.reject', $user) }}"
                    x-data="{ busy: false }" @submit="busy = true"
                    x-effect="if (deletionId !== {{ $user->id }}) busy = false" class="space-y-2">
                    @csrf
                    <label class="block text-[13px] font-medium text-vd-ink" for="deletion-note-{{ $user->id }}">
                        Why are you rejecting? <span class="font-normal text-vd-muted">(optional)</span>
                    </label>
                    <textarea id="deletion-note-{{ $user->id }}" name="admin_notes" rows="2" maxlength="500"
                        class="w-full rounded-xl border border-vd-line-strong bg-white px-3 py-2 text-sm text-vd-ink focus:border-vd-plum focus:outline-none focus:ring-2 focus:ring-vd-plum/20"
                        placeholder="Anything the next admin should know">{{ old('admin_notes') }}</textarea>
                    @error('admin_notes')<p class="text-xs font-medium text-vd-danger-ink">{{ $message }}</p>@enderror
                    <div class="flex flex-wrap gap-3">
                        <button type="button" @click="deletionId = null"
                            class="{{ $btnBase }} min-w-[120px] flex-1 border-vd-line-strong bg-white text-vd-ink hover:bg-vd-paper">
                            Close
                        </button>
                        <button type="submit" :disabled="busy"
                            class="{{ $btnBase }} min-w-[140px] flex-1 border-vd-danger-line bg-white text-vd-danger-ink hover:bg-vd-danger-bg">
                            <span x-text="busy ? 'Rejecting…' : 'Reject request'">Reject request</span>
                        </button>
                    </div>
                </form>

                <form method="POST" action="{{ route('admin.user-management.deletion-request.approve', $user) }}"
                    x-data="{ busy: false }" @submit="busy = true"
                    x-effect="if (deletionId !== {{ $user->id }}) busy = false" class="mt-3">
                    @csrf
                    <button type="submit" :disabled="busy"
                        class="{{ $btnBase }} w-full border-vd-plum bg-vd-plum text-white hover:bg-[#4d1f45]">
                        <span x-text="busy ? 'Approving…' : 'Approve deletion (closes the account)'">Approve deletion (closes the account)</span>
                    </button>
                </form>
            </div>
        @endif
    </div>
</div>
