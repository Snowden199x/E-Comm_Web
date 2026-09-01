@php
    $details = $user->sellerDetail ?? $user->buyerDetail;
@endphp

<div x-show="openId === {{ $user->id }}" x-cloak
    class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center p-4 overflow-y-auto"
    @click.self="openId = null">
    <div class="bg-white rounded-2xl p-6 w-full max-w-3xl my-8" @click.stop>

        <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-lg text-gray-900">Profile</h3>
            <button type="button" @click="openId = null"
                class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">

            <!-- Left: Profile Card -->
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 h-fit">
                <div class="flex items-center gap-3 mb-4 pb-4 border-b border-gray-200">
                    <div
                        class="w-14 h-14 rounded-full bg-gray-200 flex items-center justify-center text-lg font-semibold text-gray-600">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                    <div>
                        <p class="font-bold text-gray-900">{{ $user->name }}</p>
                        <p class="text-sm text-gray-500 capitalize">{{ $user->role }}</p>
                        <span @class([
                            'inline-block mt-1 px-2 py-0.5 rounded-full text-xs font-medium',
                            'bg-green-100 text-green-700' => $user->status === 'approved',
                            'bg-red-100 text-red-700' => $user->status === 'suspended',
                            'bg-red-50 text-red-400' => $user->status === 'deactivated',
                            'bg-orange-100 text-orange-700' => $user->status === 'disapproved',
                        ])>
                            @if ($user->status === 'approved')
                                Active
                            @elseif ($user->status === 'disapproved')
                                Rejected
                            @else
                                {{ ucfirst($user->status) }}
                            @endif
                        </span>
                    </div>
                </div>

                <div class="space-y-4 text-sm">
                    <div class="flex items-center gap-2">
                        <img src="{{ asset('assets/icons/user-management/user-email-icon.svg') }}" alt=""
                            class="w-4 h-4">
                        <span class="text-gray-700">{{ $user->email }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <img src="{{ asset('assets/icons/user-management/user-phone-icon.svg') }}" alt=""
                            class="w-4 h-4">
                        <span class="text-gray-700">{{ $user->phone_number ?? '—' }}</span>
                    </div>
                    @if ($user->role === 'seller' && $user->sellerDetail)
                        <div class="flex items-center gap-2">
                            <img src="{{ asset('assets/icons/user-management/user-business-name-icon.svg') }}"
                                alt="" class="w-4 h-4">
                            <span class="text-gray-700">{{ $user->sellerDetail->business_name }}</span>
                        </div>
                        <div class="flex items-start gap-2">
                            <img src="{{ asset('assets/icons/user-management/business-information-icon.svg') }}"
                                alt="" class="w-4 h-4 mt-0.5">
                            <div class="flex flex-wrap gap-1.5">
                                @foreach ($user->categories as $category)
                                    <span
                                        class="px-2 py-0.5 rounded-full bg-purple-50 border border-[#3b1735] text-xs font-medium text-[#3b1735]">
                                        {{ $category->name }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    <div class="flex items-center gap-2">
                        <img src="{{ asset('assets/icons/user-management/user-date-applied-icon.svg') }}"
                            alt="" class="w-4 h-4">
                        <span class="text-gray-700">{{ $user->created_at->format('M d, Y') }}</span>
                    </div>
                </div>

                <!-- Suspension info box (only when currently suspended) -->
                @if ($user->status === 'suspended')
                    <div class="mt-4 pt-4 border-t border-gray-200 space-y-2 text-sm">
                        <div class="flex justify-between"><span class="text-gray-500">Account Status:</span>
                            <span
                                class="px-2 py-0.5 rounded-full bg-red-100 text-red-700 text-xs font-medium">Suspended</span>
                        </div>
                        <div class="flex justify-between"><span class="text-gray-500">Reason:</span><span
                                class="font-medium text-gray-900">{{ $user->suspension_reason }}</span></div>
                        <div class="flex justify-between"><span class="text-gray-500">Start:</span><span
                                class="font-medium text-gray-900">{{ $user->suspended_at->format('F j, Y') }}</span>
                        </div>
                        <div class="flex justify-between"><span class="text-gray-500">End:</span><span
                                class="font-medium text-gray-900">{{ $user->suspended_until->format('F j, Y') }}</span>
                        </div>
                        <div class="flex justify-between"><span class="text-gray-500">Duration:</span><span
                                class="font-medium text-gray-900">{{ is_null($user->suspended_until) ? 'Permanent' : 'Until ' . $user->suspended_until->format('F j, Y') }}</span>
                        </div>
                    </div>
                @endif

                <!-- Action area -->
                <div class="mt-4 pt-4 border-t border-gray-200">
                    @if ($user->status === 'approved')
                        <div class="flex gap-3">
                            <button type="button" @click="suspendId = {{ $user->id }}"
                                class="flex-1 px-4 py-2 rounded-lg border border-red-300 text-red-600 text-sm font-medium hover:bg-red-50">
                                Suspend
                            </button>
                            <button type="button" @click="deactivateId = {{ $user->id }}"
                                class="flex-1 px-4 py-2 rounded-lg border border-red-300 text-red-600 text-sm font-medium hover:bg-red-50">
                                Deactivate
                            </button>
                        </div>
                    @elseif ($user->status === 'suspended')
                        @if (is_null($user->suspended_until))
                            <p class="text-sm text-red-600 text-center mb-3 font-semibold">Permanently Suspended</p>
                        @else
                            <p class="text-sm text-red-600 text-center mb-3" x-data="{
                                endTime: new Date('{{ $user->suspended_until->toIso8601String() }}').getTime(),
                                display: '',
                                tick() {
                                    const diff = this.endTime - Date.now();
                                    if (diff <= 0) { this.display = '0d 0h 0m 0s'; return; }
                                    const d = Math.floor(diff / 86400000);
                                    const h = Math.floor((diff % 86400000) / 3600000);
                                    const m = Math.floor((diff % 3600000) / 60000);
                                    const s = Math.floor((diff % 60000) / 1000);
                                    this.display = `${d}d ${h}h ${m}m ${s}s`;
                                }
                            }"
                                x-init="tick();
                                setInterval(() => tick(), 1000)">
                                Suspended for <span x-text="display"></span>
                            </p>
                        @endif
                        <button type="button" @click="activateId = {{ $user->id }}"
                            class="w-full px-4 py-2 rounded-lg border border-green-300 text-green-700 text-sm font-medium hover:bg-green-50">
                            Activate
                        </button>
                    @elseif ($user->status === 'deactivated')
                        <p class="text-sm text-red-600 text-center mb-3">Account Deactivated</p>
                        <button type="button" @click="activateId = {{ $user->id }}"
                            class="w-full px-4 py-2 rounded-lg border border-green-300 text-green-700 text-sm font-medium hover:bg-green-50">
                            Activate
                        </button>
                    @endif
                </div>
            </div>

            <!-- Right: Info Sections (stacked, not tabbed) -->
            <div class="lg:col-span-2 space-y-4">

                @if ($details)
                    <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                        <h3 class="font-bold text-gray-900 mb-4 flex items-center gap-2">
                            <img src="{{ asset('assets/icons/user-management/personal-information-icon.svg') }}"
                                alt="" class="w-5 h-5">
                            Personal Information
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="md:col-span-2 space-y-3 text-sm">
                                <div class="flex gap-6"><span class="text-gray-500 w-32 flex-shrink-0">Last
                                        Name</span><span
                                        class="font-medium text-gray-900">{{ $details->last_name }}</span></div>
                                <div class="flex gap-6"><span class="text-gray-500 w-32 flex-shrink-0">First
                                        Name</span><span
                                        class="font-medium text-gray-900">{{ $details->first_name }}</span></div>
                                <div class="flex gap-6"><span class="text-gray-500 w-32 flex-shrink-0">Middle
                                        Name</span><span
                                        class="font-medium text-gray-900">{{ $details->middle_name ?? '—' }}</span>
                                </div>
                                <div class="flex gap-6"><span class="text-gray-500 w-32 flex-shrink-0">Sex</span><span
                                        class="font-medium text-gray-900 capitalize">{{ $details->sex }}</span></div>
                                <div class="flex gap-6"><span
                                        class="text-gray-500 w-32 flex-shrink-0">Birthday</span><span
                                        class="font-medium text-gray-900">{{ $details->birthday->format('F j, Y') }}</span>
                                </div>
                                <div class="flex gap-6"><span class="text-gray-500 w-32 flex-shrink-0">Age</span><span
                                        class="font-medium text-gray-900">{{ $details->age }}</span></div>
                                <div class="flex gap-6"><span
                                        class="text-gray-500 w-32 flex-shrink-0">Email</span><span
                                        class="font-medium text-gray-900">{{ $user->email }}</span></div>
                                <div class="flex gap-6"><span class="text-gray-500 w-32 flex-shrink-0">Phone
                                        Number</span><span
                                        class="font-medium text-gray-900">{{ $user->phone_number ?? '—' }}</span>
                                </div>
                            </div>
                            <div class="md:col-span-1 text-center">
                                <p class="text-sm font-semibold text-gray-700 mb-3">Valid Id</p>
                                @if ($details->valid_id_path)
                                    <img src="{{ Storage::url($details->valid_id_path) }}" alt="Valid ID"
                                        class="rounded-lg border w-full mb-3">
                                    <a href="{{ Storage::url($details->valid_id_path) }}" target="_blank"
                                        class="inline-block text-xs px-3 py-1.5 rounded-full border border-gray-300 text-gray-600 hover:bg-gray-50">View
                                        File</a>
                                @else
                                    <p class="text-sm text-gray-400">No ID uploaded.</p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                        <h3 class="font-bold text-gray-900 mb-4 flex items-center gap-2">
                            <img src="{{ asset('assets/icons/user-management/address-icon.svg') }}" alt=""
                                class="w-5 h-5">
                            Address
                        </h3>
                        <div class="space-y-3 text-sm">
                            <div class="flex gap-6"><span
                                    class="text-gray-500 w-32 flex-shrink-0">Province</span><span
                                    class="font-medium text-gray-900">{{ $details->province }}</span></div>
                            <div class="flex gap-6"><span
                                    class="text-gray-500 w-32 flex-shrink-0">Municipality</span><span
                                    class="font-medium text-gray-900">{{ $details->municipality }}</span></div>
                            <div class="flex gap-6"><span
                                    class="text-gray-500 w-32 flex-shrink-0">Barangay</span><span
                                    class="font-medium text-gray-900">{{ $details->barangay }}</span></div>
                            <div class="flex gap-6"><span class="text-gray-500 w-32 flex-shrink-0">Street</span><span
                                    class="font-medium text-gray-900">{{ $details->street ?? '—' }}</span></div>
                            <div class="flex gap-6"><span class="text-gray-500 w-32 flex-shrink-0">House
                                    No.</span><span
                                    class="font-medium text-gray-900">{{ $details->house_no ?? '—' }}</span></div>
                            <div class="flex gap-6"><span class="text-gray-500 w-32 flex-shrink-0">Zip
                                    Code</span><span
                                    class="font-medium text-gray-900">{{ $details->zip_code ?? '—' }}</span></div>
                        </div>
                    </div>

                    @if ($user->role === 'seller')
                        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                            <h3 class="font-bold text-gray-900 mb-4 flex items-center gap-2">
                                <img src="{{ asset('assets/icons/user-management/business-information-icon.svg') }}"
                                    alt="" class="w-5 h-5">
                                Business Information
                            </h3>
                            <div class="space-y-3 text-sm">
                                <div class="flex gap-6"><span class="text-gray-500 w-32 flex-shrink-0">Business
                                        Name</span><span
                                        class="font-medium text-gray-900">{{ $user->sellerDetail->business_name }}</span>
                                </div>
                                <div class="flex gap-6"><span class="text-gray-500 w-32 flex-shrink-0">Category</span>
                                    <span
                                        class="font-medium text-gray-900">{{ $user->categories->pluck('name')->join(', ') }}</span>
                                </div>
                                <div class="flex gap-6 items-center">
                                    <span class="text-gray-500 w-32 flex-shrink-0">Business Permit</span>
                                    <a href="{{ Storage::url($user->sellerDetail->business_permit_path) }}"
                                        target="_blank"
                                        class="text-xs px-3 py-1.5 rounded-full border border-gray-300 text-gray-600 hover:bg-gray-50">View
                                        File</a>
                                </div>
                            </div>
                        </div>
                    @endif
                @else
                    <div class="bg-white rounded-2xl p-8 shadow-sm text-center text-gray-400">
                        No additional details submitted for this user.
                    </div>
                @endif

            </div>

        </div>
    </div>
</div>
