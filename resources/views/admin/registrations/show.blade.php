<x-admin.layout title="Application">
    @php
        $statusChip = [
            'pending' => ['Pending', 'border-vd-warning-line bg-vd-warning-bg text-vd-warning-ink'],
            'approved' => ['Approved', 'border-vd-success-line bg-vd-success-bg text-vd-success-ink'],
            'disapproved' => ['Rejected', 'border-vd-danger-line bg-vd-danger-bg text-vd-danger-ink'],
        ][$user->status] ?? [ucfirst((string) $user->status), 'border-vd-line-strong bg-vd-tint text-vd-muted'];

        $focus = 'focus-visible:outline-none focus-visible:ring-2';
    @endphp

    <div class="mx-auto w-full max-w-[1440px] p-4 sm:p-6 lg:p-7">

        <a href="{{ route('admin.registrations.index') }}" x-target.push="main-content sidebar"
            class="mb-4 inline-flex items-center gap-1.5 text-sm text-vd-muted transition-colors duration-200 hover:text-vd-plum {{ $focus }} focus-visible:ring-vd-plum/40">
            <x-admin.icon name="chevron-left" class="h-4 w-4" />
            Back to registrations
        </a>

        <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="font-display text-xl font-semibold leading-tight text-vd-ink sm:text-2xl">Review application</h1>
                <p class="mt-1 text-sm text-vd-muted">Check the details and IDs, then approve or reject.</p>
            </div>

            @if ($user->status === 'pending')
                <div class="flex gap-3" x-data="{ rejectOpen: false }">
                    <button type="button" @click="rejectOpen = true"
                        class="inline-flex h-10 items-center gap-2 rounded-xl border border-vd-danger-line bg-vd-danger-bg px-4 text-sm font-semibold text-vd-danger-ink transition-colors duration-200 hover:border-vd-danger-ink {{ $focus }} focus-visible:ring-vd-danger-ink/40">
                        <x-admin.icon name="x" class="h-4 w-4" stroke="2.4" /> Reject
                    </button>
                    <form method="POST" action="{{ route('admin.registrations.approve', $user) }}">
                        @csrf
                        <button type="submit"
                            class="inline-flex h-10 items-center gap-2 rounded-xl border border-vd-success-line bg-vd-success-bg px-4 text-sm font-semibold text-vd-success-ink transition-colors duration-200 hover:border-vd-success-ink {{ $focus }} focus-visible:ring-vd-success-ink/40">
                            <x-admin.icon name="check" class="h-4 w-4" stroke="2.4" /> Approve
                        </button>
                    </form>

                    @include('admin.registrations.partials.reject-modal', ['showExpr' => 'rejectOpen', 'closeExpr' => 'rejectOpen = false'])
                </div>
            @else
                <span class="inline-flex items-center rounded-full border px-3 py-1 text-sm font-medium {{ $statusChip[1] }}">{{ $statusChip[0] }}</span>
            @endif
        </div>

        <x-admin.flash class="mb-5" />

        @include('admin.registrations.partials.applicant-details')

    </div>
</x-admin.layout>
