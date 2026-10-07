<x-admin.layout>
    <div class="p-4 sm:p-5 lg:p-6">

        <a href="{{ route('admin.registrations.index') }}"
           class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-800 mb-4 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Registrations
        </a>

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Application Details</h2>
                <p class="text-gray-500">Review the applicant's information before making a decision.</p>
            </div>

            @if ($user->status === 'pending')
                <div class="flex gap-3" x-data="{ rejectOpen: false }">
                    <button type="button" @click="rejectOpen = true"
                        class="px-5 py-2.5 rounded-lg border border-red-300 text-red-600 text-sm font-semibold hover:bg-red-50 transition">
                        Reject
                    </button>
                    <form method="POST" action="{{ route('admin.registrations.approve', $user) }}">
                        @csrf
                        <button type="submit"
                            class="px-5 py-2.5 rounded-lg bg-[#3b1735] text-white text-sm font-semibold hover:bg-[#4d1f45] transition">
                            Approve
                        </button>
                    </form>

                    @include('admin.registrations.partials.reject-modal', ['showExpr' => 'rejectOpen', 'closeExpr' => 'rejectOpen = false'])
                </div>
            @else
                <span class="px-4 py-2 rounded-full text-sm font-semibold capitalize
                    {{ $user->status === 'approved' ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-600' }}">
                    {{ $user->status }}
                </span>
            @endif
        </div>

        @include('admin.registrations.partials.confirmation-modal')

        @include('admin.registrations.partials.applicant-details')

    </div>
</x-admin.layout>