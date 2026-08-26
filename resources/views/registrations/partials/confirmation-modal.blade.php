@if (session('confirmation'))
    <div x-data="{ show: true }" x-show="show" x-cloak x-init="setTimeout(() => show = false, 4000)"
         class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4"
         @click.self="show = false">
        <div class="bg-white rounded-2xl p-8 w-full max-w-sm text-center">
            @if (session('confirmation') === 'approved')
                <img src="{{ asset('assets/icons/registration/registration-approved-icon.svg') }}" alt="" class="w-16 h-16 mx-auto mb-4">
                <h3 class="font-bold text-lg text-gray-900 mb-2">Registration Approved</h3>
                <p class="text-sm text-gray-500">The application has been approved. An email notification will be sent to the applicant confirming their approval.</p>
            @else
                <img src="{{ asset('assets/icons/registration/registration-reject-icon.svg') }}" alt="" class="w-16 h-16 mx-auto mb-4">
                <h3 class="font-bold text-lg text-gray-900 mb-2">Registration Rejected</h3>
                <p class="text-sm text-gray-500">The application has been rejected. An email notification will be sent to the applicant with the decision and reason.</p>
            @endif

            <button type="button" @click="show = false" class="mt-5 px-6 py-2 rounded-lg bg-[#3b1735] text-white text-sm font-medium hover:bg-[#4d1f45]">
                Close
            </button>
        </div>
    </div>
@endif