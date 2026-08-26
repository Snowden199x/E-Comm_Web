@php
    $details = $user->sellerDetail ?? $user->courierDetail ?? $user->buyerDetail;
@endphp

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start" x-data="{ tab: 'personal' }">
    <!-- Left: Profile Card -->
    <div class="bg-white rounded-2xl p-5 shadow-sm h-fit">
        <div class="flex items-center gap-3 mb-4 pb-4 border-b border-gray-200">
            <div class="w-14 h-14 rounded-full bg-gray-200 flex items-center justify-center text-lg font-semibold text-gray-600">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </div>
            <div>
                <p class="font-bold text-gray-900">{{ $user->name }}</p>
                <p class="text-sm text-gray-500 capitalize">{{ $user->role }} Applicant</p>
            </div>
        </div>

        <div class="space-y-5 text-sm">
            <div class="flex items-center gap-2">
                <img src="{{ asset('assets/icons/registration/user-email-icon.svg') }}" alt="" class="w-4 h-4">
                <span class="text-gray-700">{{ $user->email }}</span>
            </div>
            <div class="flex items-center gap-2">
                <img src="{{ asset('assets/icons/registration/user-phone-icon.svg') }}" alt="" class="w-4 h-4">
                <span class="text-gray-700">{{ $user->phone_number ?? '—' }}</span>
            </div>
            @if ($user->role === 'seller' && $user->sellerDetail)
                <div class="flex items-center gap-2">
                    <img src="{{ asset('assets/icons/registration/user-business-name-icon.svg') }}" alt="" class="w-4 h-4">
                    <span class="text-gray-700">{{ $user->sellerDetail->business_name }}</span>
                </div>
            @elseif ($user->role === 'courier' && $user->courierDetail)
                <div class="flex items-center gap-2">
                    <img src="{{ asset('assets/icons/registration/vehicle-type-icon.svg') }}" alt="" class="w-4 h-4">
                    <span class="text-gray-700">{{ $user->courierDetail->vehicle_type }}</span>
                </div>
            @endif
            <div class="flex items-center gap-2">
                <img src="{{ asset('assets/icons/registration/user-date-applied-icon.svg') }}" alt="" class="w-4 h-4">
                <span class="text-gray-700">{{ $user->created_at->format('M d, Y') }}</span>
            </div>
        </div>
    </div>

    <!-- Right: Tabbed Content -->
    <div class="lg:col-span-2">

        <!-- Tabs -->
        <div class="flex flex-wrap gap-2 mb-4">
            <button type="button" @click="tab = 'personal'"
                :class="tab === 'personal' ? 'bg-[#3b1735] text-white' : 'bg-white text-gray-600 border border-gray-200'"
                class="px-4 py-2 rounded-full text-sm font-medium">
                Personal Information
            </button>
            <button type="button" @click="tab = 'address'"
                :class="tab === 'address' ? 'bg-[#3b1735] text-white' : 'bg-white text-gray-600 border border-gray-200'"
                class="px-4 py-2 rounded-full text-sm font-medium">
                User Address
            </button>
            @if ($user->role === 'seller')
                <button type="button" @click="tab = 'business'"
                    :class="tab === 'business' ? 'bg-[#3b1735] text-white' : 'bg-white text-gray-600 border border-gray-200'"
                    class="px-4 py-2 rounded-full text-sm font-medium">
                    Business Information
                </button>
            @elseif ($user->role === 'courier')
                <button type="button" @click="tab = 'vehicle'"
                    :class="tab === 'vehicle' ? 'bg-[#3b1735] text-white' : 'bg-white text-gray-600 border border-gray-200'"
                    class="px-4 py-2 rounded-full text-sm font-medium">
                    Vehicle Information
                </button>
            @endif
        </div>

        @if (! $details)
            <div class="bg-white rounded-2xl p-8 shadow-sm text-center text-gray-400">
                No additional details submitted yet for this applicant.
            </div>
        @else
            <div class="bg-white rounded-2xl p-5 shadow-sm">

                <!-- Personal Information Tab -->
                <div x-show="tab === 'personal'">
                    <h3 class="font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <img src="{{ asset('assets/icons/registration/personal-information-icon.svg') }}" alt="" class="w-5 h-5">
                        Personal Information
                    </h3>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                        <div class="sm:col-span-2 space-y-4 text-sm">
                            <div class="flex gap-40"><span class="text-gray-500 w-32 flex-shrink-0">Last Name</span><span class="font-medium text-gray-900">{{ $details->last_name }}</span></div>
                            <div class="flex gap-40"><span class="text-gray-500 w-32 flex-shrink-0">First Name</span><span class="font-medium text-gray-900">{{ $details->first_name }}</span></div>
                            <div class="flex gap-40"><span class="text-gray-500 w-32 flex-shrink-0">Middle Name</span><span class="font-medium text-gray-900">{{ $details->middle_name ?? '—' }}</span></div>
                            <div class="flex gap-40"><span class="text-gray-500 w-32 flex-shrink-0">Sex</span><span class="font-medium text-gray-900 capitalize">{{ $details->sex }}</span></div>
                            <div class="flex gap-40"><span class="text-gray-500 w-32 flex-shrink-0">Birthday</span><span class="font-medium text-gray-900">{{ $details->birthday->format('F j, Y') }}</span></div>
                            <div class="flex gap-40"><span class="text-gray-500 w-32 flex-shrink-0">Age</span><span class="font-medium text-gray-900">{{ $details->age }}</span></div>
                            <div class="flex gap-40"><span class="text-gray-500 w-32 flex-shrink-0">Email</span><span class="font-medium text-gray-900">{{ $user->email }}</span></div>
                            <div class="flex gap-40"><span class="text-gray-500 w-32 flex-shrink-0">Phone Number</span><span class="font-medium text-gray-900">{{ $user->phone_number ?? '—' }}</span></div>
                        </div>

                        <div class="sm:col-span-1 text-center">
                            <p class="text-sm font-semibold text-gray-700 mb-3">Valid Id</p>
                            @if ($details->valid_id_path)
                                <img src="{{ Storage::url($details->valid_id_path) }}" alt="Valid ID" class="rounded-lg border w-full mb-3">
                                <a href="{{ Storage::url($details->valid_id_path) }}" target="_blank"
                                   class="inline-block text-xs px-3 py-1.5 rounded-full border border-gray-300 text-gray-600 hover:bg-gray-50">
                                    View File
                                </a>
                            @else
                                <p class="text-sm text-gray-400">No ID uploaded.</p>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- User Address Tab -->
                <div x-show="tab === 'address'" x-cloak>
                    <h3 class="font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <img src="{{ asset('assets/icons/registration/address-icon.svg') }}" alt="" class="w-5 h-5">
                        Address
                    </h3>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                        <div class="sm:col-span-2 space-y-4 text-sm">
                            <div class="flex gap-40"><span class="text-gray-500 w-32 flex-shrink-0">Province</span><span class="font-medium text-gray-900">{{ $details->province }}</span></div>
                            <div class="flex gap-40"><span class="text-gray-500 w-32 flex-shrink-0">Municipality</span><span class="font-medium text-gray-900">{{ $details->municipality }}</span></div>
                            <div class="flex gap-40"><span class="text-gray-500 w-32 flex-shrink-0">Barangay</span><span class="font-medium text-gray-900">{{ $details->barangay }}</span></div>
                            <div class="flex gap-40"><span class="text-gray-500 w-32 flex-shrink-0">Street</span><span class="font-medium text-gray-900">{{ $details->street ?? '—' }}</span></div>
                            <div class="flex gap-40"><span class="text-gray-500 w-32 flex-shrink-0">House No.</span><span class="font-medium text-gray-900">{{ $details->house_no ?? '—' }}</span></div>
                            <div class="flex gap-40"><span class="text-gray-500 w-32 flex-shrink-0">Zip Code</span><span class="font-medium text-gray-900">{{ $details->zip_code ?? '—' }}</span></div>
                        </div>

                        <div class="sm:col-span-1 text-center">
                            <p class="text-sm font-semibold text-gray-700 mb-3">Valid Id</p>
                            @if ($details->valid_id_path)
                                <img src="{{ Storage::url($details->valid_id_path) }}" alt="Valid ID" class="rounded-lg border w-full mb-3">
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Business Information Tab (Seller only) -->
                @if ($user->role === 'seller')
                    <div x-show="tab === 'business'" x-cloak>
                        <h3 class="font-bold text-gray-900 mb-4 flex items-center gap-2">
                            <img src="{{ asset('assets/icons/registration/business-information-icon.svg') }}" alt="" class="w-5 h-5">
                            Business Information
                        </h3>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-6">
                            <div class="sm:col-span-2 space-y-4 text-sm">
                                <div class="flex gap-40"><span class="text-gray-500 w-32 flex-shrink-0">Business Name</span><span class="font-medium text-gray-900">{{ $details->business_name }}</span></div>
                                <div class="flex gap-40 items-center">
                                    <span class="text-gray-500 w-32 flex-shrink-0">Business Permit</span>
                                    @if ($details->business_permit_path)
                                        <a href="{{ Storage::url($details->business_permit_path) }}" target="_blank"
                                           class="text-xs px-3 py-1.5 rounded-full border border-gray-300 text-gray-600 hover:bg-gray-50">
                                            View File
                                        </a>
                                    @else
                                        <span class="text-gray-400">Not submitted</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <p class="text-sm text-gray-500 mb-2">Categories</p>
                        <div class="flex flex-wrap gap-2">
                            @forelse ($user->categories as $category)
                                <span class="px-3 py-1.5 rounded-full bg-purple-50 border border-[#3b1735] text-xs font-medium text-[#3b1735]">
                                    {{ $category->name }}
                                </span>
                            @empty
                                <span class="text-sm text-gray-400">No categories selected.</span>
                            @endforelse
                        </div>
                    </div>
                @endif

                <!-- Vehicle Information Tab (Courier only) -->
                @if ($user->role === 'courier')
                    <div x-show="tab === 'vehicle'" x-cloak>
                        <h3 class="font-bold text-gray-900 mb-4 flex items-center gap-2">
                            <img src="{{ asset('assets/icons/registration/vehicle-type-icon.svg') }}" alt="" class="w-5 h-5">
                            Vehicle Information
                        </h3>

                        <div class="space-y-4 text-sm">
                            <div class="flex gap-40"><span class="text-gray-500 w-32 flex-shrink-0">Vehicle type</span><span class="font-medium text-gray-900">{{ $details->vehicle_type }}</span></div>
                            <div class="flex gap-40"><span class="text-gray-500 w-32 flex-shrink-0">Plate Number</span><span class="font-medium text-gray-900">{{ $details->plate_number }}</span></div>
                            <div class="flex gap-40 items-center">
                                <span class="text-gray-500 w-32 flex-shrink-0">Driver's License</span>
                                @if ($details->drivers_license_path)
                                    <a href="{{ Storage::url($details->drivers_license_path) }}" target="_blank"
                                       class="text-xs px-3 py-1.5 rounded-full border border-gray-300 text-gray-600 hover:bg-gray-50">
                                        View File
                                    </a>
                                @else
                                    <span class="text-gray-400">Not submitted</span>
                                @endif
                            </div>
                            <div class="flex gap-40 items-center">
                                <span class="text-gray-500 w-32 flex-shrink-0">OR/CR</span>
                                @if ($details->or_cr_path)
                                    <a href="{{ Storage::url($details->or_cr_path) }}" target="_blank"
                                       class="text-xs px-3 py-1.5 rounded-full border border-gray-300 text-gray-600 hover:bg-gray-50">
                                        View File
                                    </a>
                                @else
                                    <span class="text-gray-400">Not submitted</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

            </div>
        @endif

    </div>

</div>