<x-buyer-layout>
    <div class="max-w-2xl mx-auto p-4 sm:p-5 lg:p-6">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">Account</h2>

        @if (session('success'))
            <div class="bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg p-3 mb-4">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white rounded-2xl p-5 shadow-sm mb-4">
            <h3 class="font-bold text-gray-900 mb-4">Profile Information</h3>

            <form action="{{ route('buyer.account.update') }}" method="POST" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label class="text-sm font-medium text-gray-700">Full Name</label>
                    <input type="text" name="name" value="{{ old('name', auth()->user()->name) }}"
                        class="w-full border rounded-lg mt-1 p-2 text-sm">
                    @error('name')
                        <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">Email</label>
                    <input type="email" value="{{ auth()->user()->email }}" disabled
                        class="w-full border rounded-lg mt-1 p-2 text-sm bg-gray-50 text-gray-500">
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">Phone Number</label>
                    <input type="text" name="phone_number" value="{{ old('phone_number', auth()->user()->phone_number) }}"
                        class="w-full border rounded-lg mt-1 p-2 text-sm">
                </div>

                @if ($buyerDetail)
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-sm font-medium text-gray-700">House No.</label>
                            <input type="text" name="house_no" value="{{ old('house_no', $buyerDetail->house_no) }}"
                                class="w-full border rounded-lg mt-1 p-2 text-sm">
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700">Street</label>
                            <input type="text" name="street" value="{{ old('street', $buyerDetail->street) }}"
                                class="w-full border rounded-lg mt-1 p-2 text-sm">
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-3 text-sm text-gray-500">
                        <div>
                            <p class="font-medium text-gray-700">Barangay</p>
                            <p>{{ $buyerDetail->barangay }}</p>
                        </div>
                        <div>
                            <p class="font-medium text-gray-700">Municipality</p>
                            <p>{{ $buyerDetail->municipality }}</p>
                        </div>
                        <div>
                            <p class="font-medium text-gray-700">Province</p>
                            <p>{{ $buyerDetail->province }}</p>
                        </div>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700">Zip Code</label>
                        <input type="text" name="zip_code" value="{{ old('zip_code', $buyerDetail->zip_code) }}"
                            class="w-32 border rounded-lg mt-1 p-2 text-sm">
                    </div>
                @endif

                <button type="submit" class="bg-[#3b1735] text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-[#4d1f45]">
                    Save Changes
                </button>
            </form>
        </div>

        <div class="bg-white rounded-2xl p-5 shadow-sm">
            <h3 class="font-bold text-gray-900 mb-4">Change Password</h3>

            <form action="{{ route('buyer.account.password') }}" method="POST" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label class="text-sm font-medium text-gray-700">Current Password</label>
                    <input type="password" name="current_password" class="w-full border rounded-lg mt-1 p-2 text-sm">
                    @error('current_password')
                        <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">New Password</label>
                    <input type="password" name="password" class="w-full border rounded-lg mt-1 p-2 text-sm">
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">Confirm New Password</label>
                    <input type="password" name="password_confirmation" class="w-full border rounded-lg mt-1 p-2 text-sm">
                </div>

                <button type="submit" class="bg-[#3b1735] text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-[#4d1f45]">
                    Update Password
                </button>
            </form>
        </div>
    </div>
</x-buyer-layout>