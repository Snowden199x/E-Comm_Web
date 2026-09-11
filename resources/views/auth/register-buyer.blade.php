<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Buyer Registration - Vendo</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        * { font-family: 'Poppins', sans-serif; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="antialiased bg-white">

    <div class="min-h-screen flex flex-col lg:flex-row"
         x-data="{ step: 1, showPassword: false, showConfirmPassword: false, showVerifyModal: false, showSuccessModal: false }">

        {{-- LEFT PANEL --}}
        <div class="hidden lg:flex lg:w-[28%] bg-[#402143] flex-col items-center justify-start px-8 py-10 overflow-y-auto relative"
             style="background-image: url('{{ asset('assets/icons/registration/left-side-panel/leftside-panel.svg') }}'); background-repeat: no-repeat; background-position: center center; background-size: cover;">

            <div class="flex justify-center w-full mb-6 relative z-10">
                <img src="{{ asset('assets/branding/log-in-logo.svg') }}" alt="Vendo" class="w-[180px]">
            </div>

            <div class="w-full mb-4 relative z-10">
                <h2 class="text-white text-[1.35rem] font-bold leading-snug">
                    Shop and discover<br>with <span class="text-[#c9933a]">Vendo</span>
                </h2>
                <div class="w-8 h-[2px] bg-white/40 mt-3 mb-4"></div>
                <p class="text-white/70 text-[0.78rem] font-light leading-relaxed">
                    Explore products from different sellers, find great deals, and enjoy a convenient shopping experience delivered right to your doorstep.
                </p>
            </div>

            <div class="w-full flex justify-center mb-4 relative z-10">
                <img src="{{ asset('assets/icons/registration/left-side-panel/house-left-panel.svg') }}" alt="Shop illustration" class="w-full max-w-[260px]">
            </div>

            <div class="w-full mt-2 relative z-10">
                <p class="text-white text-[0.82rem] font-semibold mb-4">Registration Steps</p>
                <div class="flex flex-col gap-0">

                    <div class="flex items-start gap-3">
                        <div class="flex flex-col items-center shrink-0">
                            <div class="w-6 h-6 rounded-full bg-[#402143] border border-white/40 text-white text-[0.65rem] font-semibold flex items-center justify-center shrink-0">1</div>
                            <div class="w-px bg-white/20 my-1" style="height: 32px;"></div>
                        </div>
                        <div class="pb-4">
                            <p class="text-white text-[0.78rem] font-semibold leading-tight">1. Personal Information</p>
                            <p class="text-white/60 text-[0.7rem] font-light leading-snug mt-0.5">Tell us about yourself</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <div class="flex flex-col items-center shrink-0">
                            <div class="w-6 h-6 rounded-full bg-[#402143] border border-white/20 text-white/40 text-[0.65rem] font-semibold flex items-center justify-center shrink-0">2</div>
                            <div class="w-px bg-white/20 my-1" style="height: 40px;"></div>
                        </div>
                        <div class="pb-4">
                            <p class="text-white/70 text-[0.78rem] font-semibold leading-tight">2. Contact &amp; Address</p>
                            <p class="text-white/50 text-[0.7rem] font-light leading-snug mt-0.5">Tell about your contact and where you live from</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <div class="w-6 h-6 rounded-full bg-[#402143] border border-white/20 text-white/40 text-[0.65rem] font-semibold flex items-center justify-center shrink-0">3</div>
                        <div>
                            <p class="text-white/70 text-[0.78rem] font-semibold leading-tight">3. Review &amp; Submit</p>
                            <p class="text-white/50 text-[0.7rem] font-light leading-snug mt-0.5">Review your information and submit</p>
                        </div>
                    </div>

                </div>
            </div>

        </div>

        {{-- RIGHT PANEL --}}
        <div class="w-full lg:w-[72%] flex flex-col bg-white min-h-screen">

            {{-- Top bar --}}
            <div class="flex items-start justify-between px-8 pt-7 pb-2">
                <div>
                    <h1 class="text-[1.25rem] font-bold text-gray-900 tracking-tight leading-tight">Buyer Registration</h1>
                    <p class="text-[0.72rem] text-gray-500 mt-0.5 font-normal">Create your buyer account.</p>
                </div>
                <div class="flex items-center gap-2 mt-1">
                    <span class="text-[0.7rem] text-gray-500 font-normal">Already have an account?</span>
                    <a href="{{ route('buyer.login') }}"
                       class="text-[0.7rem] font-medium text-gray-700 border border-gray-400 rounded-md px-3.5 py-1 hover:bg-gray-50 transition">
                        Login
                    </a>
                </div>
            </div>

            {{-- Scrollable content --}}
            <div class="flex-1 px-8 pb-8 overflow-y-auto">

                {{-- Step Indicator --}}
                <div class="flex items-center mt-5 mb-1 w-full max-w-[580px] mx-auto gap-0">

                    <div class="shrink-0 w-8">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-[0.72rem] font-semibold leading-none"
                             :class="step >= 1 ? 'bg-[#3b1735] text-white' : 'border border-gray-300 bg-white text-gray-400'">1</div>
                    </div>

                    <div class="flex-1 flex h-px shrink">
                        <div class="w-1/2 h-px bg-[#3b1735]"></div>
                        <div class="w-1/2 h-px" :class="step >= 2 ? 'bg-[#3b1735]' : 'bg-gray-300'"></div>
                    </div>

                    <div class="shrink-0 w-8">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-[0.72rem] font-semibold leading-none"
                             :class="step >= 2 ? 'bg-[#3b1735] text-white' : 'border border-gray-300 bg-white text-gray-400'">2</div>
                    </div>

                    {{-- Segment 2→3 --}}
                    <div class="flex-1 flex h-px shrink">
                        <div class="w-1/2 h-px" :class="step >= 2 ? 'bg-[#3b1735]' : 'bg-gray-300'"></div>
                        <div class="w-1/2 h-px" :class="step >= 3 ? 'bg-[#3b1735]' : 'bg-gray-300'"></div>
                    </div>

                    <div class="shrink-0 w-8">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-[0.72rem] font-semibold leading-none"
                             :class="step >= 3 ? 'bg-[#3b1735] text-white' : 'border border-gray-300 bg-white text-gray-400'">3</div>
                    </div>

                </div>

                {{-- Step labels --}}
                <div class="relative flex w-full max-w-[580px] mx-auto mb-6 mt-1.5">
                    <div class="shrink-0 w-8 relative">
                        <span class="absolute left-1/2 -translate-x-1/2 text-[0.62rem] whitespace-nowrap"
                              :class="step >= 1 ? 'font-semibold text-[#3b1735]' : 'font-normal text-gray-400'">Personal Information</span>
                    </div>
                    <div class="flex-1"></div>
                    <div class="shrink-0 w-8 relative">
                        <span class="absolute left-1/2 -translate-x-1/2 text-[0.62rem] whitespace-nowrap"
                              :class="step >= 2 ? 'font-semibold text-[#3b1735]' : 'font-normal text-gray-400'">Contact &amp; Address</span>
                    </div>
                    <div class="flex-1"></div>
                    <div class="shrink-0 w-8 relative">
                        <span class="absolute left-1/2 -translate-x-1/2 text-[0.62rem] whitespace-nowrap"
                              :class="step >= 3 ? 'font-semibold text-[#3b1735]' : 'font-normal text-gray-400'">Review &amp; Submit</span>
                    </div>
                </div>

                <div class="mb-4"></div>

                <form method="POST" action="#" enctype="multipart/form-data">
                    @csrf

                    {{-- STEP 1 --}}
                    <div x-show="step === 1">

                        <div class="mb-1">
                            <h2 class="text-[0.88rem] font-bold text-gray-900 mb-0.5">Personal Information</h2>
                            <p class="text-[0.7rem] text-gray-500 font-normal mb-4">Please provide your personal details.</p>
                        </div>

                        <div class="grid grid-cols-3 gap-3 mb-3">
                            <div>
                                <label class="block text-[0.68rem] font-medium text-gray-700 mb-1">Last Name <span class="text-red-500">*</span></label>
                                <input type="text" name="last_name" placeholder="Enter last name" class="w-full rounded-md border border-gray-200 bg-white text-gray-800 text-[0.72rem] placeholder-gray-400 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#3b1735]/25 focus:border-[#3b1735] transition">
                            </div>
                            <div>
                                <label class="block text-[0.68rem] font-medium text-gray-700 mb-1">First Name <span class="text-red-500">*</span></label>
                                <input type="text" name="first_name" placeholder="Enter first name" class="w-full rounded-md border border-gray-200 bg-white text-gray-800 text-[0.72rem] placeholder-gray-400 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#3b1735]/25 focus:border-[#3b1735] transition">
                            </div>
                            <div>
                                <label class="block text-[0.68rem] font-medium text-gray-700 mb-1">Middle Initial</label>
                                <input type="text" name="middle_initial" placeholder="Enter middle initial" class="w-full rounded-md border border-gray-200 bg-white text-gray-800 text-[0.72rem] placeholder-gray-400 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#3b1735]/25 focus:border-[#3b1735] transition">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3 mb-3">
                            <div>
                                <label class="block text-[0.68rem] font-medium text-gray-700 mb-1">Sex <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <select name="sex" class="w-full rounded-md border border-gray-200 bg-white text-gray-500 text-[0.72rem] px-3 py-2 appearance-none focus:outline-none focus:ring-2 focus:ring-[#3b1735]/25 focus:border-[#3b1735] transition">
                                        <option value="" disabled selected>Select Sex</option>
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                        <option value="prefer_not_to_say">Prefer not to say</option>
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-2.5 flex items-center">
                                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <label class="text-[0.68rem] font-medium text-gray-700">Email <span class="text-red-500">*</span></label>
                                    <button type="button" class="text-[0.65rem] font-semibold text-[#3b1735] hover:underline">Verify</button>
                                </div>
                                <input type="email" name="email" placeholder="Enter email address" class="w-full rounded-md border border-gray-200 bg-white text-gray-800 text-[0.72rem] placeholder-gray-400 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#3b1735]/25 focus:border-[#3b1735] transition">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3 mb-3">
                            <div>
                                <label class="block text-[0.68rem] font-medium text-gray-700 mb-1">Birthday <span class="text-red-500">*</span></label>
                                <input type="date" name="birthday" class="w-full rounded-md border border-gray-200 bg-white text-gray-500 text-[0.72rem] px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#3b1735]/25 focus:border-[#3b1735] transition">
                            </div>
                            <div>
                                <label class="block text-[0.68rem] font-medium text-gray-700 mb-1">Age <span class="text-red-500">*</span></label>
                                <input type="text" name="age" placeholder="--" readonly class="w-full rounded-md border border-gray-200 bg-gray-50 text-gray-500 text-[0.72rem] px-3 py-2 cursor-not-allowed">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3 mb-5">
                            <div>
                                <label class="block text-[0.68rem] font-medium text-gray-700 mb-1">Valid ID <span class="text-red-500">*</span></label>
                                <label class="flex items-center justify-between w-full rounded-md border border-gray-200 bg-white text-gray-400 text-[0.72rem] px-3 py-2 cursor-pointer hover:bg-gray-50 transition">
                                    <span id="valid-id-label">Upload Valid ID here</span>
                                    <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                    <input type="file" name="valid_id" class="hidden" accept="image/*,.pdf" onchange="document.getElementById('valid-id-label').textContent = this.files[0]?.name || 'Upload Valid ID here'">
                                </label>
                            </div>
                        </div>

                        <hr class="border-gray-200 mb-5">

                        <div class="mb-1">
                            <h2 class="text-[0.88rem] font-bold text-gray-900 mb-0.5">Account Security</h2>
                            <p class="text-[0.7rem] text-gray-500 font-normal mb-4">Set a password to secure your buyer account.</p>
                        </div>

                        <div class="grid grid-cols-2 gap-3 mb-1">
                            <div>
                                <label class="block text-[0.68rem] font-medium text-gray-700 mb-1">Password <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <input name="password" x-bind:type="showPassword ? 'text' : 'password'" placeholder="Create a password"
                                        class="w-full rounded-md border border-gray-200 bg-white text-gray-800 text-[0.72rem] placeholder-gray-400 px-3 py-2 pr-9 focus:outline-none focus:ring-2 focus:ring-[#3b1735]/25 focus:border-[#3b1735] transition">
                                    <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 flex items-center justify-center w-9 text-gray-400 hover:text-gray-600 transition" tabindex="-1">
                                        <svg x-show="!showPassword" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        <svg x-show="showPassword" x-cloak xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.956 9.956 0 012.293-3.95M6.47 6.47A9.953 9.953 0 0112 5c4.477 0 8.268 2.943 9.542 7a9.963 9.963 0 01-4.134 5.247M15 12a3 3 0 00-3-3m0 6a3 3 0 01-2.83-2M3 3l18 18"/></svg>
                                    </button>
                                </div>
                            </div>
                            <div>
                                <label class="block text-[0.68rem] font-medium text-gray-700 mb-1">Confirm Password <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <input name="password_confirmation" x-bind:type="showConfirmPassword ? 'text' : 'password'" placeholder="Confirm your password"
                                        class="w-full rounded-md border border-gray-200 bg-white text-gray-800 text-[0.72rem] placeholder-gray-400 px-3 py-2 pr-9 focus:outline-none focus:ring-2 focus:ring-[#3b1735]/25 focus:border-[#3b1735] transition">
                                    <button type="button" @click="showConfirmPassword = !showConfirmPassword" class="absolute inset-y-0 right-0 flex items-center justify-center w-9 text-gray-400 hover:text-gray-600 transition" tabindex="-1">
                                        <svg x-show="!showConfirmPassword" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        <svg x-show="showConfirmPassword" x-cloak xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.956 9.956 0 012.293-3.95M6.47 6.47A9.953 9.953 0 0112 5c4.477 0 8.268 2.943 9.542 7a9.963 9.963 0 01-4.134 5.247M15 12a3 3 0 00-3-3m0 6a3 3 0 01-2.83-2M3 3l18 18"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <p class="text-[0.62rem] text-gray-400 mt-1.5 mb-6 font-normal">Minimum 8 characters with letters and numbers.</p>

                        <div class="flex justify-end">
                            <button type="button" @click="step = 2"
                                class="flex items-center gap-1.5 bg-[#3b1735] hover:bg-[#4d1f45] active:bg-[#2e1229] text-white text-[0.75rem] font-semibold rounded-lg px-5 py-2 transition-colors duration-150">
                                Next: Contact &amp; Address
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                            </button>
                        </div>

                    </div>

                    {{-- STEP 2 --}}
                    <div x-show="step === 2" x-cloak>

                        <div class="mb-1">
                            <h2 class="text-[0.88rem] font-bold text-gray-900 mb-0.5">Address</h2>
                            <p class="text-[0.7rem] text-gray-500 font-normal mb-4">Please provide your complete address.</p>
                        </div>

                        <div class="grid grid-cols-3 gap-3 mb-3">
                            <div>
                                <label class="block text-[0.68rem] font-medium text-gray-700 mb-1">Province <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <select name="province" class="w-full rounded-md border border-gray-200 bg-white text-gray-500 text-[0.72rem] px-3 py-2 appearance-none focus:outline-none focus:ring-2 focus:ring-[#3b1735]/25 focus:border-[#3b1735] transition">
                                        <option value="" disabled selected>Select Province</option>
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-2.5 flex items-center">
                                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label class="block text-[0.68rem] font-medium text-gray-700 mb-1">Municipality / City <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <select name="municipality" class="w-full rounded-md border border-gray-200 bg-white text-gray-500 text-[0.72rem] px-3 py-2 appearance-none focus:outline-none focus:ring-2 focus:ring-[#3b1735]/25 focus:border-[#3b1735] transition">
                                        <option value="" disabled selected>Select Municipality / City</option>
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-2.5 flex items-center">
                                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label class="block text-[0.68rem] font-medium text-gray-700 mb-1">Barangay <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <select name="barangay" class="w-full rounded-md border border-gray-200 bg-white text-gray-500 text-[0.72rem] px-3 py-2 appearance-none focus:outline-none focus:ring-2 focus:ring-[#3b1735]/25 focus:border-[#3b1735] transition">
                                        <option value="" disabled selected>Select Barangay</option>
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-2.5 flex items-center">
                                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-3 mb-8">
                            <div>
                                <label class="block text-[0.68rem] font-medium text-gray-700 mb-1">Street / House No. <span class="text-red-500">*</span></label>
                                <input type="text" name="street" placeholder="Enter street, house number, building, subdivision, etc."
                                    class="w-full rounded-md border border-gray-200 bg-white text-gray-800 text-[0.72rem] placeholder-gray-400 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#3b1735]/25 focus:border-[#3b1735] transition">
                            </div>
                            <div>
                                <label class="block text-[0.68rem] font-medium text-gray-700 mb-1">Zip Code <span class="text-red-500">*</span></label>
                                <input type="text" name="zip_code" placeholder="Enter zip code"
                                    class="w-full rounded-md border border-gray-200 bg-white text-gray-800 text-[0.72rem] placeholder-gray-400 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#3b1735]/25 focus:border-[#3b1735] transition">
                            </div>
                            <div>
                                <label class="block text-[0.68rem] font-medium text-gray-700 mb-1">Contact Number <span class="text-red-500">*</span></label>
                                <input type="text" name="contact_number" placeholder="09XX XXX XXXX"
                                    class="w-full rounded-md border border-gray-200 bg-white text-gray-800 text-[0.72rem] placeholder-gray-400 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#3b1735]/25 focus:border-[#3b1735] transition">
                            </div>
                        </div>

                        <div class="flex justify-end gap-3">
                            <button type="button" @click="step = 1"
                                class="text-[0.75rem] font-medium text-gray-700 border border-gray-300 rounded-lg px-5 py-2 hover:bg-gray-50 transition">
                                Back
                            </button>
                            <button type="button" @click="step = 3"
                                class="flex items-center gap-1.5 bg-[#3b1735] hover:bg-[#4d1f45] active:bg-[#2e1229] text-white text-[0.75rem] font-semibold rounded-lg px-5 py-2 transition-colors duration-150">
                                Next: Review &amp; Submit
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                            </button>
                        </div>

                    </div>

                    {{-- STEP 3 --}}
                    <div x-show="step === 3" x-cloak>

                        <div class="mb-4">
                            <h2 class="text-[0.88rem] font-bold text-gray-900 mb-0.5">Review your Information</h2>
                            <p class="text-[0.7rem] text-gray-500 font-normal">Please review all the details below before submitting your registration.</p>
                        </div>

                        {{-- User Information Card --}}
                        <div class="border border-gray-200 rounded-xl p-5 mb-4">

                            {{-- Card header --}}
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-[#ede6f0] flex items-center justify-center shrink-0">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-[#3b1735]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                    </div>
                                    <span class="text-[0.82rem] font-bold text-gray-900">User Information</span>
                                </div>
                                <button type="button" @click="step = 1"
                                    class="text-[0.7rem] font-medium text-gray-700 border border-gray-300 rounded-md px-4 py-1 hover:bg-gray-50 transition">
                                    Edit
                                </button>
                            </div>

                            {{-- Info grid --}}
                            <div class="grid grid-cols-4 gap-x-6 gap-y-4">

                                {{-- Row 1 --}}
                                <div>
                                    <p class="text-[0.62rem] text-gray-400 mb-0.5">Full Name</p>
                                    <p class="text-[0.72rem] font-semibold text-gray-800" x-text="
                                        (document.querySelector('[name=first_name]')?.value || '') + ' ' +
                                        (document.querySelector('[name=middle_initial]')?.value ? document.querySelector('[name=middle_initial]').value + '. ' : '') +
                                        (document.querySelector('[name=last_name]')?.value || '') || '—'
                                    "></p>
                                </div>
                                <div>
                                    <p class="text-[0.62rem] text-gray-400 mb-0.5">Phone Number</p>
                                    <p class="text-[0.72rem] font-semibold text-gray-800" x-text="document.querySelector('[name=contact_number]')?.value || '—'"></p>
                                </div>
                                <div>
                                    <p class="text-[0.62rem] text-gray-400 mb-0.5">Province</p>
                                    <p class="text-[0.72rem] font-semibold text-gray-800" x-text="document.querySelector('[name=province]')?.options[document.querySelector('[name=province]')?.selectedIndex]?.text || '—'"></p>
                                </div>
                                <div>
                                    <p class="text-[0.62rem] text-gray-400 mb-0.5">Street / House No.</p>
                                    <p class="text-[0.72rem] font-semibold text-gray-800" x-text="document.querySelector('[name=street]')?.value || '—'"></p>
                                </div>

                                {{-- Row 2 --}}
                                <div>
                                    <p class="text-[0.62rem] text-gray-400 mb-0.5">Sex</p>
                                    <p class="text-[0.72rem] font-semibold text-gray-800 capitalize" x-text="document.querySelector('[name=sex]')?.value || '—'"></p>
                                </div>
                                <div>
                                    <p class="text-[0.62rem] text-gray-400 mb-0.5">Birthday</p>
                                    <p class="text-[0.72rem] font-semibold text-gray-800" x-text="document.querySelector('[name=birthday]')?.value || '—'"></p>
                                </div>
                                <div>
                                    <p class="text-[0.62rem] text-gray-400 mb-0.5">Municipality</p>
                                    <p class="text-[0.72rem] font-semibold text-gray-800" x-text="document.querySelector('[name=municipality]')?.options[document.querySelector('[name=municipality]')?.selectedIndex]?.text || '—'"></p>
                                </div>
                                <div>
                                    <p class="text-[0.62rem] text-gray-400 mb-0.5">Zip Code</p>
                                    <p class="text-[0.72rem] font-semibold text-gray-800" x-text="document.querySelector('[name=zip_code]')?.value || '—'"></p>
                                </div>

                                {{-- Row 3 --}}
                                <div>
                                    <p class="text-[0.62rem] text-gray-400 mb-0.5">Email</p>
                                    <p class="text-[0.72rem] font-semibold text-gray-800 break-all" x-text="document.querySelector('[name=email]')?.value || '—'"></p>
                                </div>
                                <div>
                                    <p class="text-[0.62rem] text-gray-400 mb-0.5">Age</p>
                                    <p class="text-[0.72rem] font-semibold text-gray-800" x-text="document.querySelector('[name=age]')?.value || '—'"></p>
                                </div>
                                <div>
                                    <p class="text-[0.62rem] text-gray-400 mb-0.5">Barangay</p>
                                    <p class="text-[0.72rem] font-semibold text-gray-800" x-text="document.querySelector('[name=barangay]')?.options[document.querySelector('[name=barangay]')?.selectedIndex]?.text || '—'"></p>
                                </div>
                                <div>
                                    <p class="text-[0.62rem] text-gray-400 mb-0.5">Valid ID</p>
                                    <div class="flex items-center gap-1.5 mt-0.5">
                                        <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                        <span class="text-[0.68rem] text-gray-600 truncate" id="review-valid-id">—</span>
                                    </div>
                                </div>

                            </div>
                        </div>

                        {{-- Please Review Carefully notice --}}
                        <div class="bg-[#f3edf7] border border-[#d4b8e0] rounded-xl p-4 mb-6">
                            <div class="flex items-start gap-3">
                                <div class="w-7 h-7 rounded-full bg-[#3b1735] flex items-center justify-center shrink-0 mt-0.5">
                                    <span class="text-white text-[0.7rem] font-bold">!</span>
                                </div>
                                <div>
                                    <p class="text-[0.78rem] font-bold text-gray-900 mb-1">Please Review Carefully</p>
                                    <p class="text-[0.68rem] text-gray-600 leading-relaxed mb-3">
                                        By submitting this registration, you confirm that all information provided is true and correct.<br>
                                        Our team will review your application and you will be notified via email once your account is approved.
                                    </p>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" name="agree_terms" required
                                            class="w-3.5 h-3.5 rounded border-gray-400 text-[#3b1735] focus:ring-[#3b1735]">
                                        <span class="text-[0.68rem] text-gray-600">
                                            I agree to the <a href="#" class="text-[#3b1735] font-semibold hover:underline">Terms and Conditions</a> and <a href="#" class="text-[#3b1735] font-semibold hover:underline">Privacy Policy</a>.
                                        </span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        {{-- Back | Submit --}}
                        <div class="flex justify-end gap-3">
                            <button type="button" @click="step = 2"
                                class="text-[0.75rem] font-medium text-gray-700 border border-gray-300 rounded-lg px-5 py-2 hover:bg-gray-50 transition">
                                Back
                            </button>
                            <button type="button" @click="showVerifyModal = true"
                                class="bg-[#3b1735] hover:bg-[#4d1f45] text-white text-[0.75rem] font-semibold rounded-lg px-6 py-2 transition-colors duration-150">
                                Submit
                            </button>
                        </div>

                    </div>

                </form>
            </div>
        </div>{{-- end right panel --}}

        {{-- ── Email Verification Modal ─────────────────────────────────────── --}}
        <div x-show="showVerifyModal"
             style="display:none;"
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
             @keydown.escape.window="showVerifyModal = false">

            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-[420px] mx-4 px-8 py-10 text-center"
                 @click.outside="showVerifyModal = false">

                {{-- Icon --}}
                <div class="flex justify-center mb-6">
                    <div class="w-16 h-16 rounded-full bg-[#ede6f0] flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-[#3b1735]" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 2-8 5-8-5h16zm0 12H4V8l8 5 8-5v10z"/>
                            <circle cx="17" cy="17" r="5" fill="#3b1735"/>
                            <path d="M15.5 17.5l1 1 2.5-2.5" stroke="white" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                        </svg>
                    </div>
                </div>

                {{-- Heading --}}
                <h2 class="text-[1.3rem] font-bold text-gray-900 mb-2">Verify Your Email</h2>
                <p class="text-[0.82rem] text-gray-400 font-normal mb-7 leading-relaxed">
                    We've sent a 6-digit code to your email.
                </p>

                {{-- 6-digit OTP inputs --}}
                <div class="flex justify-center gap-3 mb-6"
                     x-data="otpInput()"
                     @paste.prevent="handlePaste($event)">
                    <template x-for="(digit, index) in digits" :key="index">
                        <input
                            type="text"
                            maxlength="1"
                            inputmode="numeric"
                            pattern="[0-9]"
                            x-model="digits[index]"
                            :id="'otp-' + index"
                            @input="onInput(index, $event)"
                            @keydown.backspace="onBackspace(index, $event)"
                            @keydown.left="focusAt(index - 1)"
                            @keydown.right="focusAt(index + 1)"
                            class="w-12 h-14 rounded-xl border border-gray-300 text-center text-[1.1rem] font-semibold text-gray-900 focus:outline-none focus:border-[#3b1735] focus:ring-2 focus:ring-[#3b1735]/20 transition"
                        >
                    </template>
                </div>

                {{-- Resend --}}
                <div class="flex justify-center mb-7">
                    <button type="button" class="flex items-center gap-1.5 text-[0.78rem] font-semibold text-[#c0392b] hover:underline">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582M20 20v-5h-.581M4.582 9A8 8 0 0119.418 15M19.418 15A8 8 0 014.582 9"/>
                        </svg>
                        Resend Code
                    </button>
                </div>

                {{-- Verify button --}}
                <button type="button" @click="showVerifyModal = false; showSuccessModal = true"
                    class="w-full bg-[#3b1735] hover:bg-[#4d1f45] text-white text-[0.9rem] font-bold rounded-full py-3.5 transition-colors duration-150">
                    Verify Email
                </button>

            </div>
        </div>{{-- end verify modal --}}

        {{-- ── Success Modal ────────────────────────────────────────────────── --}}
        <div x-show="showSuccessModal"
             style="display:none;"
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">

            <div class="bg-white rounded-3xl shadow-2xl w-full max-w-[400px] mx-4 px-8 py-10 text-center border border-[#e8dff0]">

                {{-- Vendo bag icon with green check --}}
                <div class="flex justify-center mb-6">
                    <img src="{{ asset('assets/icons/seller-compliance/product-approved-element.svg') }}" alt="Registration approved" class="w-36 h-36 object-contain">
                </div>

                {{-- Text --}}
                <h2 class="text-[1.15rem] font-bold text-gray-900 mb-2">Thank you for Registering</h2>
                <p class="text-[0.82rem] font-semibold text-gray-700 mb-2">Your registration is pending review</p>
                <p class="text-[0.78rem] text-gray-400 font-normal leading-relaxed mb-8">
                    You will receive an email once your account has<br>been approved by the administrator
                </p>

                {{-- Go to Login --}}
                <a href="{{ route('buyer.login') }}"
                   class="block w-full bg-[#3b1735] hover:bg-[#4d1f45] text-white text-[0.88rem] font-semibold rounded-xl py-3 transition-colors duration-150">
                    Go to Login
                </a>

            </div>
        </div>{{-- end success modal --}}

    </div>

    <script>
        // Sync valid ID filename to review step
        document.addEventListener('alpine:init', () => {
            Alpine.effect(() => {
                const filename = document.getElementById('valid-id-label')?.textContent;
                const reviewEl = document.getElementById('review-valid-id');
                if (reviewEl && filename) reviewEl.textContent = filename;
            });

            // OTP input component
            Alpine.data('otpInput', () => ({
                digits: ['', '', '', '', '', ''],
                onInput(index, event) {
                    const val = event.target.value.replace(/\D/g, '');
                    this.digits[index] = val ? val[0] : '';
                    if (val && index < 5) this.focusAt(index + 1);
                },
                onBackspace(index, event) {
                    if (!this.digits[index] && index > 0) {
                        this.focusAt(index - 1);
                    }
                },
                focusAt(index) {
                    if (index >= 0 && index <= 5) {
                        document.getElementById('otp-' + index)?.focus();
                    }
                },
                handlePaste(event) {
                    const text = (event.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6);
                    text.split('').forEach((char, i) => { this.digits[i] = char; });
                    this.focusAt(Math.min(text.length, 5));
                }
            }));
        });
    </script>
</body>
</html>
