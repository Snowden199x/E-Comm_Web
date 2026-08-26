<x-admin-layout>
    <div class="p-4 sm:p-5 lg:p-6" x-data="{ rejectOpen: false }">

        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Registration Details</h2>
            <a href="{{ route('registrations.index') }}" class="text-sm text-[#3b1735] hover:underline">&lt; Back to Registrations</a>
        </div>

        @include('registrations.partials.applicant-details', ['user' => $user])

        @if ($user->status === 'pending')
            <div class="flex justify-end gap-3 mt-6">
                <button type="button" @click="rejectOpen = true"
                        class="px-6 py-2.5 rounded-lg border border-red-300 text-red-600 text-sm font-medium hover:bg-red-50">
                    Reject
                </button>
                <form method="POST" action="{{ route('registrations.approve', $user) }}">
                    @csrf
                    <button type="submit" class="px-6 py-2.5 rounded-lg bg-[#3b1735] text-white text-sm font-medium hover:bg-[#4d1f45]">
                        Approve
                    </button>
                </form>
            </div>
        @endif

        @include('registrations.partials.reject-modal', ['user' => $user])
        @include('registrations.partials.confirmation-modal')

    </div>
</x-admin-layout>