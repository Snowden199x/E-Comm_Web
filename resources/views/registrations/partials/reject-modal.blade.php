<div x-show="{{ $showExpr ?? 'rejectOpen' }}" x-cloak
     class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4"
     @click.self="{{ $closeExpr ?? 'rejectOpen = false' }}">
    <div class="bg-white rounded-2xl w-full max-w-md max-h-[90vh] overflow-y-auto" @click.stop
         x-data="{ reason: '', details: '' }">

        <form method="POST" action="{{ route('registrations.disapprove', $user) }}" class="p-6">
            @csrf

            <div class="flex justify-end">
                <button type="button" @click="{{ $closeExpr ?? 'rejectOpen = false' }}" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
            </div>

            <div class="text-center mb-5">
                <img src="{{ asset('assets/icons/registration/rejection-icon.svg') }}" alt="" class="w-14 h-14 mx-auto mb-3">
                <h3 class="font-bold text-lg text-gray-900">Reject Registration</h3>
                <p class="text-sm text-gray-500">The application will not proceed for {{ $user->name }}.</p>
            </div>

            <p class="text-sm font-medium text-gray-900 mb-2">Reason for Rejection<span class="text-red-500">*</span></p>
            <div class="space-y-2 mb-4">
                @foreach ([
                    'Incomplete Application',
                    'Invalid Identification',
                    'Information Mismatch',
                    'Document Verification Failed',
                    'Fraudulent Information',
                    'Does Not Meet Requirements',
                    'Other (please specify)',
                ] as $option)
                    <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                        <input type="radio" name="reason" value="{{ $option }}" x-model="reason" required
                               class="text-[#3b1735] focus:ring-[#3b1735]">
                        {{ $option }}
                    </label>
                @endforeach
            </div>

            <p class="text-sm font-medium text-gray-900 mb-2">Additional Details</p>
            <textarea name="additional_details" x-model="details" maxlength="500" rows="3"
                      placeholder="Write additional details here..."
                      class="w-full rounded-lg border border-gray-200 text-sm p-3 focus:outline-none focus:ring-2 focus:ring-[#3b1735]"></textarea>
            <p class="text-xs text-gray-400 text-right mt-1" x-text="details.length + '/500'"></p>

            <div class="flex gap-3 mt-5">
                <button type="button" @click="{{ $closeExpr ?? 'rejectOpen = false' }}"
                        class="flex-1 py-2.5 rounded-lg border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit"
                        class="flex-1 py-2.5 rounded-lg bg-red-600 text-white text-sm font-medium hover:bg-red-700">
                    Reject Application
                </button>
            </div>
        </form>

    </div>
</div>