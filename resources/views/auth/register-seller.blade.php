<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Seller Registration - Vendo</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        * {
            font-family: 'Poppins', sans-serif;
        }

        [x-cloak] {
            display: none !important;
        }

        /* ---------------------------------------------------------
           Ambient animated background (left panel)
        --------------------------------------------------------- */
        .blob {
            position: absolute;
            border-radius: 9999px;
            filter: blur(70px);
            will-change: transform;
            pointer-events: none;
        }

        .blob-1 {
            width: 380px;
            height: 380px;
            top: -100px;
            left: -90px;
            background: radial-gradient(circle at 30% 30%, rgba(201, 147, 58, 0.5), rgba(201, 147, 58, 0) 70%);
            animation: blobMove1 17s ease-in-out infinite;
        }

        .blob-2 {
            width: 340px;
            height: 340px;
            bottom: -120px;
            right: -100px;
            background: radial-gradient(circle at 60% 60%, rgba(168, 101, 201, 0.4), rgba(168, 101, 201, 0) 70%);
            animation: blobMove2 21s ease-in-out infinite;
        }

        .blob-3 {
            width: 260px;
            height: 260px;
            top: 42%;
            left: -70px;
            background: radial-gradient(circle at 50% 50%, rgba(255, 255, 255, 0.10), rgba(255, 255, 255, 0) 70%);
            animation: blobMove3 14s ease-in-out infinite;
        }

        @keyframes blobMove1 {

            0%,
            100% {
                transform: translate(0, 0) scale(1);
            }

            33% {
                transform: translate(45px, 35px) scale(1.15);
            }

            66% {
                transform: translate(-25px, 15px) scale(0.9);
            }
        }

        @keyframes blobMove2 {

            0%,
            100% {
                transform: translate(0, 0) scale(1);
            }

            40% {
                transform: translate(-35px, -30px) scale(1.1);
            }

            70% {
                transform: translate(20px, -10px) scale(0.95);
            }
        }

        @keyframes blobMove3 {

            0%,
            100% {
                transform: translate(0, 0) scale(1);
                opacity: 0.5;
            }

            50% {
                transform: translate(30px, -20px) scale(1.2);
                opacity: 0.9;
            }
        }

        .aurora-sweep {
            position: absolute;
            inset: -50%;
            background: conic-gradient(from 0deg, transparent 0%, rgba(201, 147, 58, 0.10) 15%, transparent 30%, transparent 60%, rgba(255, 255, 255, 0.05) 75%, transparent 90%);
            animation: spinSlow 30s linear infinite;
        }

        @keyframes spinSlow {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        /* ---------------------------------------------------------
           Entrance animation
        --------------------------------------------------------- */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(14px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in-up {
            opacity: 0;
            animation: fadeInUp 0.6s cubic-bezier(0.22, 1, 0.36, 1) forwards;
        }

        @keyframes floatIllustration {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-10px);
            }
        }

        .float-illustration {
            animation: floatIllustration 5s ease-in-out infinite;
        }
    </style>
</head>

<body class="antialiased bg-white">

    <div class="min-h-screen flex flex-col lg:flex-row" x-data="{
        step: 1,
        showPassword: false,
        showConfirmPassword: false,
        showVerifyModal: false,
        showSuccessModal: false,
        submitting: false,
        formError: '',
        async submitForm(form) {
            if (!Alpine.store('registration').otpVerified) {
                this.formError = 'Please verify your email address before submitting.';
                return;
            }
            this.submitting = true;
            this.formError = '';
            try {
                const res = await fetch(form.action, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': vendoCsrfToken() },
                    body: new FormData(form),
                });
                const data = await res.json().catch(() => ({}));
                if (res.ok) {
                    this.showSuccessModal = true;
                } else if (res.status === 422 && data.errors) {
                    this.formError = Object.values(data.errors)[0][0];
                    vendoToast(this.formError);
                } else {
                    this.formError = data.message || 'Something went wrong. Please try again.';
                    vendoToast(this.formError);
                }
            } catch (e) {
                this.formError = 'Network error. Please try again.';
                vendoToast(this.formError);
            }
            this.submitting = false;
        }
    }">

        {{-- LEFT PANEL --}}
        <div
            class="hidden lg:flex lg:w-[28%] relative overflow-y-auto bg-[#402143] flex-col items-start justify-start px-8 py-10">

            {{-- animated ambient background --}}
            <div class="absolute inset-0 overflow-hidden pointer-events-none">
                <div class="aurora-sweep"></div>
                <div class="blob blob-1"></div>
                <div class="blob blob-2"></div>
                <div class="blob blob-3"></div>
            </div>

            <div class="relative z-10 w-full flex flex-col flex-1">

                <div class="flex justify-center w-full mb-8 fade-in-up" style="animation-delay: .05s">
                    <a href="{{ url('/') }}" title="Back to landing page"><img
                            src="{{ asset('assets/branding/log-in-logo.svg') }}" alt="Vendo"
                            class="w-[180px] cursor-pointer transition-transform duration-300 hover:scale-105"></a>
                </div>

                <div class="w-full mb-8 fade-in-up" style="animation-delay: .1s">
                    <h2 class="text-white text-[1.5rem] font-bold leading-snug">
                        Grow your business<br>with <span class="text-[#c9933a]">Vendo</span>
                    </h2>
                    <div class="w-8 h-[2px] bg-white/40 mt-3 mb-4"></div>
                    <p class="text-white/70 text-[0.9rem] font-light leading-relaxed">
                        Join thousands of sellers who are successfully growing their business and reaching more
                        customers every day.
                    </p>
                </div>

                <div class="h-[180px] flex items-center justify-center fade-in-up" style="animation-delay: .15s">
                    <img src="{{ asset('assets/icons/registration/left-side-panel/house-left-panel.svg') }}"
                        alt="" class="w-full max-w-[180px] opacity-90 float-illustration">
                </div>

                <div class="w-full mt-2 fade-in-up" style="animation-delay: .15s">
                    <p class="text-white text-[0.92rem] font-semibold mb-4">Registration Steps</p>
                    <div class="flex flex-col gap-0">

                        <div class="flex items-start gap-3 group" :class="step > 1 ? 'cursor-pointer' : ''"
                            @click="if (step > 1) step = 1">
                            <div class="flex flex-col items-center shrink-0">
                                <div class="w-6 h-6 rounded-full text-[0.72rem] font-bold flex items-center justify-center shrink-0 transition-all duration-300"
                                    :class="[step >= 1 ? 'bg-white text-[#3b1735] border border-white' :
                                        'bg-[#402143] border border-white/40 text-white', step > 1 ?
                                        'group-hover:scale-110' : ''
                                    ]">
                                    <span x-show="step > 1" x-cloak
                                        x-transition:enter="transition ease-out duration-200"
                                        x-transition:enter-start="opacity-0 scale-50"
                                        x-transition:enter-end="opacity-100 scale-100">&check;</span>
                                    <span x-show="step <= 1">1</span>
                                </div>
                                <div class="w-px my-1 transition-colors duration-500"
                                    :class="step > 1 ? 'bg-white/60' : 'bg-white/20'" style="height: 32px;"></div>
                            </div>
                            <div class="pb-4">
                                <p class="text-[0.9rem] leading-tight transition-colors duration-300"
                                    :class="step === 1 ? 'text-white font-bold' : 'text-white/80 font-semibold'">1.
                                    Personal Information</p>
                                <p class="text-white/60 text-[0.8rem] font-light leading-snug mt-0.5">Tell us about
                                    yourself</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3 group" :class="step > 2 ? 'cursor-pointer' : ''"
                            @click="if (step > 2) step = 2">
                            <div class="flex flex-col items-center shrink-0">
                                <div class="w-6 h-6 rounded-full text-[0.72rem] font-bold flex items-center justify-center shrink-0 transition-all duration-300"
                                    :class="[step >= 2 ? 'bg-white text-[#3b1735] border border-white' :
                                        'bg-[#402143] border border-white/20 text-white/40', step > 2 ?
                                        'group-hover:scale-110' : ''
                                    ]">
                                    <span x-show="step > 2" x-cloak
                                        x-transition:enter="transition ease-out duration-200"
                                        x-transition:enter-start="opacity-0 scale-50"
                                        x-transition:enter-end="opacity-100 scale-100">&check;</span>
                                    <span x-show="step <= 2">2</span>
                                </div>
                                <div class="w-px my-1 transition-colors duration-500"
                                    :class="step > 2 ? 'bg-white/60' : 'bg-white/20'" style="height: 40px;"></div>
                            </div>
                            <div class="pb-4">
                                <p class="text-[0.9rem] leading-tight transition-colors duration-300"
                                    :class="step === 2 ? 'text-white font-bold' : (step > 2 ? 'text-white/80 font-semibold' :
                                        'text-white/50 font-semibold')">
                                    2. Business Information</p>
                                <p class="text-white/50 text-[0.8rem] font-light leading-snug mt-0.5">Provide your
                                    business details</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <div class="w-6 h-6 rounded-full text-[0.72rem] font-bold flex items-center justify-center shrink-0 transition-all duration-300"
                                :class="step >= 3 ? 'bg-white text-[#3b1735] border border-white' :
                                    'bg-[#402143] border border-white/20 text-white/40'">
                                3</div>
                            <div>
                                <p class="text-[0.9rem] leading-tight transition-colors duration-300"
                                    :class="step === 3 ? 'text-white font-bold' : 'text-white/50 font-semibold'">3.
                                    Review &amp; Submit</p>
                                <p class="text-white/50 text-[0.8rem] font-light leading-snug mt-0.5">Review your
                                    information and submit</p>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

        </div>

        {{-- RIGHT PANEL --}}
        <div class="w-full lg:w-[72%] flex flex-col bg-white min-h-screen">

            {{-- Top bar --}}
            <div class="flex items-start justify-between px-8 pt-7 pb-2 fade-in-up" style="animation-delay: .05s">
                <div>
                    <a href="{{ url('/') }}"
                        class="inline-flex items-center gap-1 text-[0.75rem] font-medium text-gray-400 hover:text-[#3b1735] mb-1.5 transition-all duration-200 hover:-translate-x-0.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            stroke-width="2.2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                        </svg>
                        Back to Landing Page
                    </a>
                    <h1 class="text-[1.4rem] font-bold text-gray-900 tracking-tight leading-tight">Seller Registration
                    </h1>
                    <p class="text-[0.85rem] text-gray-500 mt-0.5 font-normal">Create your seller account.</p>
                </div>
                <div class="flex items-center gap-2 mt-1">
                    <span class="text-[0.8rem] text-gray-500 font-normal">Already have an account?</span>
                    <a href="{{ route('seller.login') }}"
                        class="text-[0.85rem] font-semibold text-gray-700 border border-gray-400 rounded-md px-3.5 py-1 hover:bg-gray-50 hover:border-gray-500 transition-all duration-200">
                        Login
                    </a>
                </div>
            </div>

            {{-- Scrollable content --}}
            <div class="flex-1 px-8 pb-8 overflow-y-auto">

                {{-- Step Indicator (click a completed step's circle to jump back to it) --}}
                <div class="flex items-center mt-5 mb-1 w-full max-w-[580px] mx-auto gap-0 fade-in-up"
                    style="animation-delay: .1s">

                    <div class="shrink-0 w-8">
                        <button type="button" @click="if (step > 1) step = 1"
                            :class="step >= 1 ? 'bg-[#3b1735] text-white' : 'border border-gray-300 bg-white text-gray-400'"
                            :style="step > 1 ? 'cursor:pointer' : 'cursor:default'"
                            class="w-8 h-8 rounded-full flex items-center justify-center text-[0.85rem] font-semibold leading-none transition-all duration-300 hover:enabled:scale-110"
                            :disabled="step <= 1">1</button>
                    </div>

                    <div class="flex-1 flex h-px shrink">
                        <div class="w-1/2 h-px bg-[#3b1735] transition-colors duration-500"></div>
                        <div class="w-1/2 h-px transition-colors duration-500"
                            :class="step >= 2 ? 'bg-[#3b1735]' : 'bg-gray-300'"></div>
                    </div>

                    <div class="shrink-0 w-8">
                        <button type="button" @click="if (step > 2) step = 2"
                            :class="step >= 2 ? 'bg-[#3b1735] text-white' : 'border border-gray-300 bg-white text-gray-400'"
                            :style="step > 2 ? 'cursor:pointer' : 'cursor:default'"
                            class="w-8 h-8 rounded-full flex items-center justify-center text-[0.85rem] font-semibold leading-none transition-all duration-300 hover:enabled:scale-110"
                            :disabled="step <= 2">2</button>
                    </div>

                    <div class="flex-1 flex h-px shrink">
                        <div class="w-1/2 h-px transition-colors duration-500"
                            :class="step >= 2 ? 'bg-[#3b1735]' : 'bg-gray-300'"></div>
                        <div class="w-1/2 h-px transition-colors duration-500"
                            :class="step >= 3 ? 'bg-[#3b1735]' : 'bg-gray-300'"></div>
                    </div>

                    <div class="shrink-0 w-8">
                        <button type="button" disabled
                            :class="step >= 3 ? 'bg-[#3b1735] text-white' : 'border border-gray-300 bg-white text-gray-400'"
                            class="w-8 h-8 rounded-full flex items-center justify-center text-[0.85rem] font-semibold leading-none cursor-default transition-colors duration-300">3</button>
                    </div>

                </div>

                {{-- Step labels --}}
                <div class="relative flex w-full max-w-[580px] mx-auto mb-6 mt-1.5 fade-in-up"
                    style="animation-delay: .12s">
                    <div class="shrink-0 w-8 relative">
                        <span
                            class="absolute left-1/2 -translate-x-1/2 text-[0.72rem] whitespace-nowrap transition-colors duration-300"
                            :class="step >= 1 ? 'font-semibold text-[#3b1735]' : 'font-normal text-gray-400'">Personal
                            Information</span>
                    </div>
                    <div class="flex-1"></div>
                    <div class="shrink-0 w-8 relative">
                        <span
                            class="absolute left-1/2 -translate-x-1/2 text-[0.72rem] whitespace-nowrap transition-colors duration-300"
                            :class="step >= 2 ? 'font-semibold text-[#3b1735]' : 'font-normal text-gray-400'">Business
                            Information</span>
                    </div>
                    <div class="flex-1"></div>
                    <div class="shrink-0 w-8 relative">
                        <span
                            class="absolute left-1/2 -translate-x-1/2 text-[0.72rem] whitespace-nowrap transition-colors duration-300"
                            :class="step >= 3 ? 'font-semibold text-[#3b1735]' : 'font-normal text-gray-400'">Review
                            &amp; Submit</span>
                    </div>
                </div>

                <div class="mb-4"></div>

                <form method="POST" action="{{ route('seller.register.store') }}" enctype="multipart/form-data"
                    @submit.prevent="submitForm($el)" class="fade-in-up" style="animation-delay: .16s">
                    @csrf

                    {{-- STEP 1: Personal Information + Address + Account Security --}}
                    <div x-show="step === 1" x-ref="step1" x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-y-2"
                        x-transition:enter-end="opacity-100 translate-y-0">

                        <div class="mb-1">
                            <h2 class="text-[1rem] font-bold text-gray-900 mb-0.5">Personal Information</h2>
                            <p class="text-[0.8rem] text-gray-500 font-normal mb-4">Please provide your personal
                                details.</p>
                        </div>

                        <div class="grid grid-cols-3 gap-3 mb-3">
                            <div>
                                <label class="block text-[0.85rem] font-semibold text-gray-700 mb-1">Last Name <span
                                        class="text-red-500">*</span></label>
                                <input type="text" name="last_name" required placeholder="Enter last name"
                                    class="w-full rounded-md border border-gray-200 bg-white text-gray-800 text-[0.85rem] placeholder-gray-400 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#3b1735]/25 focus:border-[#3b1735] transition-all duration-200">
                            </div>
                            <div>
                                <label class="block text-[0.85rem] font-semibold text-gray-700 mb-1">First Name <span
                                        class="text-red-500">*</span></label>
                                <input type="text" name="first_name" required placeholder="Enter first name"
                                    class="w-full rounded-md border border-gray-200 bg-white text-gray-800 text-[0.85rem] placeholder-gray-400 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#3b1735]/25 focus:border-[#3b1735] transition-all duration-200">
                            </div>
                            <div>
                                <label class="block text-[0.85rem] font-semibold text-gray-700 mb-1">Middle
                                    Initial</label>
                                <input type="text" name="middle_initial" placeholder="Enter middle initial"
                                    class="w-full rounded-md border border-gray-200 bg-white text-gray-800 text-[0.85rem] placeholder-gray-400 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#3b1735]/25 focus:border-[#3b1735] transition-all duration-200">
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-3 mb-3">
                            <div>
                                <label class="block text-[0.85rem] font-semibold text-gray-700 mb-1">Sex <span
                                        class="text-red-500">*</span></label>
                                <div class="relative">
                                    <select name="sex" required
                                        class="w-full rounded-md border border-gray-200 bg-white text-gray-500 text-[0.85rem] px-3 py-2 appearance-none focus:outline-none focus:ring-2 focus:ring-[#3b1735]/25 focus:border-[#3b1735] transition-all duration-200">
                                        <option value="" disabled selected>Select Sex</option>
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-2.5 flex items-center">
                                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <label class="text-[0.85rem] font-semibold text-gray-700">Email <span
                                            class="text-red-500">*</span></label>
                                    <button type="button"
                                        @click="Alpine.store('registration').email = document.querySelector('[name=email]').value.trim();
                                                if (!Alpine.store('registration').email) { return; }
                                                showVerifyModal = true;
                                                window.dispatchEvent(new CustomEvent('otp-open'))"
                                        class="text-[0.72rem] font-semibold hover:underline transition-colors duration-200"
                                        :class="Alpine.store('registration').otpVerified ? 'text-green-600' : 'text-[#3b1735]'">
                                        <span x-show="!Alpine.store('registration').otpVerified">Verify</span>
                                        <span x-show="Alpine.store('registration').otpVerified" x-cloak
                                            x-transition:enter="transition ease-out duration-300"
                                            x-transition:enter-start="opacity-0 scale-75"
                                            x-transition:enter-end="opacity-100 scale-100">&check; Verified</span>
                                    </button>
                                </div>
                                <input type="email" name="email" required placeholder="Enter email address"
                                    class="w-full rounded-md border border-gray-200 bg-white text-gray-800 text-[0.85rem] placeholder-gray-400 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#3b1735]/25 focus:border-[#3b1735] transition-all duration-200">
                            </div>
                            <div>
                                <label class="block text-[0.85rem] font-semibold text-gray-700 mb-1">Contact Number
                                    <span class="text-red-500">*</span></label>
                                <input type="text" name="contact_number" required placeholder="09XX XXX XXXX"
                                    inputmode="numeric" pattern="[0-9]{10,11}" maxlength="11"
                                    oninput="this.value = this.value.replace(/\D/g, '').slice(0, 11)"
                                    class="w-full rounded-md border border-gray-200 bg-white text-gray-800 text-[0.85rem] placeholder-gray-400 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#3b1735]/25 focus:border-[#3b1735] transition-all duration-200">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3 mb-3">
                            <div>
                                <label class="block text-[0.85rem] font-semibold text-gray-700 mb-1">Birthday <span
                                        class="text-red-500">*</span></label>
                                <input type="date" name="birthday" required
                                    onchange="
                                        const b = new Date(this.value);
                                        const t = new Date();
                                        let age = t.getFullYear() - b.getFullYear();
                                        const m = t.getMonth() - b.getMonth();
                                        if (m < 0 || (m === 0 && t.getDate() < b.getDate())) age--;
                                        document.querySelector('[name=age]').value = isNaN(age) ? '' : age;
                                    "
                                    class="w-full rounded-md border border-gray-200 bg-white text-gray-500 text-[0.85rem] px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#3b1735]/25 focus:border-[#3b1735] transition-all duration-200">
                            </div>
                            <div>
                                <label class="block text-[0.85rem] font-semibold text-gray-700 mb-1">Age <span
                                        class="text-red-500">*</span></label>
                                <input type="text" name="age" required placeholder="Enter age"
                                    class="w-full rounded-md border border-gray-200 bg-white text-gray-800 text-[0.85rem] px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#3b1735]/25 focus:border-[#3b1735] transition-all duration-200">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3 mb-5">
                            <div>
                                <label class="block text-[0.85rem] font-semibold text-gray-700 mb-1">Valid ID <span
                                        class="text-red-500">*</span></label>
                                <label
                                    class="flex items-center justify-between w-full rounded-md border border-gray-200 bg-white text-gray-400 text-[0.85rem] px-3 py-2 cursor-pointer hover:bg-gray-50 hover:border-gray-300 transition-all duration-200">
                                    <span id="valid-id-label">Upload Valid ID here</span>
                                    <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                    </svg>
                                    <input type="file" name="valid_id" required class="hidden"
                                        accept=".jpg,.jpeg,.png,.pdf"
                                        onchange="document.getElementById('valid-id-label').textContent = this.files[0]?.name || 'Upload Valid ID here'">
                                </label>
                                <p class="text-[0.7rem] text-gray-400 mt-1">Accepted formats: JPEG, PNG, or PDF.</p>
                            </div>
                        </div>

                        <hr class="border-gray-200 mb-5">

                        <div class="mb-1">
                            <h2 class="text-[1rem] font-bold text-gray-900 mb-0.5">Address</h2>
                            <p class="text-[0.8rem] text-gray-500 font-normal mb-4">Please provide your complete
                                address.</p>
                        </div>

                        @include('auth.partials.address-comboboxes')

                        <div class="grid grid-cols-2 gap-3 mb-8">
                            <div>
                                <label class="block text-[0.85rem] font-semibold text-gray-700 mb-1">Street / House No.
                                    <span class="text-red-500">*</span></label>
                                <input type="text" name="street" required
                                    placeholder="Enter street, house number, building, subdivision, etc."
                                    class="w-full rounded-md border border-gray-200 bg-white text-gray-800 text-[0.85rem] placeholder-gray-400 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#3b1735]/25 focus:border-[#3b1735] transition-all duration-200">
                            </div>
                            <div>
                                <label class="block text-[0.85rem] font-semibold text-gray-700 mb-1">Zip Code <span
                                        class="text-red-500">*</span></label>
                                <input type="text" name="zip_code" required placeholder="Enter zip code"
                                    class="w-full rounded-md border border-gray-200 bg-white text-gray-800 text-[0.85rem] placeholder-gray-400 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#3b1735]/25 focus:border-[#3b1735] transition-all duration-200">
                            </div>
                        </div>

                        <hr class="border-gray-200 mb-5">

                        <div class="mb-1">
                            <h2 class="text-[1rem] font-bold text-gray-900 mb-0.5">Account Security</h2>
                            <p class="text-[0.8rem] text-gray-500 font-normal mb-4">Set a password to secure your
                                seller account.</p>
                        </div>

                        <div class="grid grid-cols-2 gap-3 mb-1">
                            <div>
                                <label class="block text-[0.85rem] font-semibold text-gray-700 mb-1">Password <span
                                        class="text-red-500">*</span></label>
                                <div class="relative">
                                    <input name="password" required x-bind:type="showPassword ? 'text' : 'password'"
                                        placeholder="Create a password"
                                        class="w-full rounded-md border border-gray-200 bg-white text-gray-800 text-[0.85rem] placeholder-gray-400 px-3 py-2 pr-9 focus:outline-none focus:ring-2 focus:ring-[#3b1735]/25 focus:border-[#3b1735] transition-all duration-200">
                                    <button type="button" @click="showPassword = !showPassword"
                                        class="absolute inset-y-0 right-0 flex items-center justify-center w-9 text-gray-400 hover:text-gray-600 transition-colors duration-200"
                                        tabindex="-1">
                                        <svg x-show="!showPassword" xmlns="http://www.w3.org/2000/svg"
                                            class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                            stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        <svg x-show="showPassword" x-cloak xmlns="http://www.w3.org/2000/svg"
                                            class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                            stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.956 9.956 0 012.293-3.95M6.47 6.47A9.953 9.953 0 0112 5c4.477 0 8.268 2.943 9.542 7a9.963 9.963 0 01-4.134 5.247M15 12a3 3 0 00-3-3m0 6a3 3 0 01-2.83-2M3 3l18 18" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <div>
                                <label class="block text-[0.85rem] font-semibold text-gray-700 mb-1">Confirm Password
                                    <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <input name="password_confirmation" required
                                        x-bind:type="showConfirmPassword ? 'text' : 'password'"
                                        placeholder="Confirm your password"
                                        class="w-full rounded-md border border-gray-200 bg-white text-gray-800 text-[0.85rem] placeholder-gray-400 px-3 py-2 pr-9 focus:outline-none focus:ring-2 focus:ring-[#3b1735]/25 focus:border-[#3b1735] transition-all duration-200">
                                    <button type="button" @click="showConfirmPassword = !showConfirmPassword"
                                        class="absolute inset-y-0 right-0 flex items-center justify-center w-9 text-gray-400 hover:text-gray-600 transition-colors duration-200"
                                        tabindex="-1">
                                        <svg x-show="!showConfirmPassword" xmlns="http://www.w3.org/2000/svg"
                                            class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                            stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        <svg x-show="showConfirmPassword" x-cloak xmlns="http://www.w3.org/2000/svg"
                                            class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                            stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.956 9.956 0 012.293-3.95M6.47 6.47A9.953 9.953 0 0112 5c4.477 0 8.268 2.943 9.542 7a9.963 9.963 0 01-4.134 5.247M15 12a3 3 0 00-3-3m0 6a3 3 0 01-2.83-2M3 3l18 18" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <p class="text-[0.72rem] text-gray-400 mt-1.5 mb-6 font-normal">Minimum 8 characters, with at
                            least one capital letter, one number, and one special character.</p>

                        <div class="flex justify-end">
                            <button type="button" @click="if (vendoValidateStep($refs.step1)) step = 2"
                                class="flex items-center gap-1.5 bg-[#3b1735] hover:bg-[#4d1f45] active:bg-[#2e1229] text-white text-[0.85rem] font-semibold rounded-lg px-5 py-2 transition-all duration-200 hover:shadow-md hover:-translate-y-0.5 active:translate-y-0 active:scale-[0.97]">
                                Next: Business Information
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                        </div>

                    </div>

                    {{-- STEP 2: Business Information --}}
                    <div x-show="step === 2" x-cloak x-ref="step2"
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-y-2"
                        x-transition:enter-end="opacity-100 translate-y-0">

                        <div class="mb-1">
                            <h2 class="text-[1rem] font-bold text-gray-900 mb-0.5">Business Information</h2>
                            <p class="text-[0.8rem] text-gray-500 font-normal mb-4">Please provide your business
                                details.</p>
                        </div>

                        <div class="grid grid-cols-2 gap-6 mb-8">
                            <div>
                                <div class="mb-3">
                                    <label class="block text-[0.85rem] font-semibold text-gray-700 mb-1">Business Name
                                        <span class="text-red-500">*</span></label>
                                    <input type="text" name="business_name" required
                                        placeholder="Enter business name"
                                        class="w-full rounded-md border border-gray-200 bg-white text-gray-800 text-[0.85rem] placeholder-gray-400 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#3b1735]/25 focus:border-[#3b1735] transition-all duration-200">
                                </div>
                                <div>
                                    <label class="block text-[0.85rem] font-semibold text-gray-700 mb-1">Business
                                        Permit <span class="text-red-500">*</span></label>
                                    <label
                                        class="flex items-center justify-between w-full rounded-md border border-gray-200 bg-white text-gray-400 text-[0.85rem] px-3 py-2 cursor-pointer hover:bg-gray-50 hover:border-gray-300 transition-all duration-200">
                                        <span id="business-permit-label">Upload business permit here</span>
                                        <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                        </svg>
                                        <input type="file" name="business_permit" required class="hidden"
                                            accept="image/*,.pdf"
                                            onchange="document.getElementById('business-permit-label').textContent = this.files[0]?.name || 'Upload business permit here'">
                                    </label>
                                </div>
                            </div>

                            <div>
                                <label class="block text-[0.85rem] font-semibold text-gray-700 mb-0.5">Select Category
                                    <span class="text-red-500">*</span></label>
                                <p class="text-[0.72rem] text-gray-400 mb-2">You can select multiple category.</p>
                                <div class="grid grid-cols-1 gap-1.5 max-h-[280px] overflow-y-auto pr-2">
                                    @foreach ($categories as $category)
                                        <label class="flex items-center gap-2 cursor-pointer group">
                                            <input type="checkbox" name="categories[]" value="{{ $category->id }}"
                                                class="w-3.5 h-3.5 rounded border-gray-400 text-[#3b1735] focus:ring-[#3b1735] category-checkbox transition-colors duration-150">
                                            <span
                                                class="text-[0.85rem] text-gray-700 group-hover:text-[#3b1735] transition-colors duration-150">{{ $category->name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end gap-3">
                            <button type="button" @click="step = 1"
                                class="text-[0.85rem] font-medium text-gray-700 border border-gray-300 rounded-lg px-5 py-2 hover:bg-gray-50 hover:border-gray-400 transition-all duration-200 active:scale-[0.97]">
                                Back
                            </button>
                            <button type="button"
                                @click="if (vendoValidateStep($refs.step2)) { vendoRefreshRegistrationReview(); step = 3; }"
                                class="flex items-center gap-1.5 bg-[#3b1735] hover:bg-[#4d1f45] active:bg-[#2e1229] text-white text-[0.85rem] font-semibold rounded-lg px-5 py-2 transition-all duration-200 hover:shadow-md hover:-translate-y-0.5 active:translate-y-0 active:scale-[0.97]">
                                Next: Review &amp; Submit
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                        </div>

                    </div>

                    {{-- STEP 3: Review & Submit --}}
                    <div x-show="step === 3" x-cloak x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-y-2"
                        x-transition:enter-end="opacity-100 translate-y-0">

                        <div class="mb-4">
                            <h2 class="text-[1rem] font-bold text-gray-900 mb-0.5">Review your Information</h2>
                            <p class="text-[0.8rem] text-gray-500 font-normal">Please review all the details below
                                before submitting your registration.</p>
                        </div>

                        {{-- Personal Information Card --}}
                        <div
                            class="border border-gray-200 rounded-xl p-5 mb-4 transition-shadow duration-300 hover:shadow-sm">

                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-9 h-9 rounded-full bg-[#ede6f0] flex items-center justify-center shrink-0">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-[#3b1735]"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                            stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                    </div>
                                    <span class="text-[0.92rem] font-bold text-gray-900">Personal Information</span>
                                </div>
                                <button type="button" @click="step = 1"
                                    class="text-[0.85rem] font-semibold text-gray-700 border border-gray-300 rounded-md px-4 py-1 hover:bg-gray-50 hover:border-gray-400 transition-all duration-200">
                                    Edit
                                </button>
                            </div>

                            <div class="grid grid-cols-4 gap-x-6 gap-y-4">

                                <div>
                                    <p class="text-[0.72rem] text-gray-400 mb-0.5">Full Name</p>
                                    <p class="text-[0.85rem] font-semibold text-gray-800" id="review-fullname">—</p>
                                </div>
                                <div>
                                    <p class="text-[0.72rem] text-gray-400 mb-0.5">Phone Number</p>
                                    <p class="text-[0.85rem] font-semibold text-gray-800" id="review-phone">—</p>
                                </div>
                                <div>
                                    <p class="text-[0.72rem] text-gray-400 mb-0.5">Province</p>
                                    <p class="text-[0.85rem] font-semibold text-gray-800" id="review-province">—</p>
                                </div>
                                <div>
                                    <p class="text-[0.72rem] text-gray-400 mb-0.5">Street / House No.</p>
                                    <p class="text-[0.85rem] font-semibold text-gray-800" id="review-street">—</p>
                                </div>

                                <div>
                                    <p class="text-[0.72rem] text-gray-400 mb-0.5">Sex</p>
                                    <p class="text-[0.85rem] font-semibold text-gray-800 capitalize" id="review-sex">—
                                    </p>
                                </div>
                                <div>
                                    <p class="text-[0.72rem] text-gray-400 mb-0.5">Birthday</p>
                                    <p class="text-[0.85rem] font-semibold text-gray-800" id="review-birthday">—</p>
                                </div>
                                <div>
                                    <p class="text-[0.72rem] text-gray-400 mb-0.5">Municipality</p>
                                    <p class="text-[0.85rem] font-semibold text-gray-800" id="review-municipality">—
                                    </p>
                                </div>
                                <div>
                                    <p class="text-[0.72rem] text-gray-400 mb-0.5">Zip Code</p>
                                    <p class="text-[0.85rem] font-semibold text-gray-800" id="review-zip">—</p>
                                </div>

                                <div>
                                    <p class="text-[0.72rem] text-gray-400 mb-0.5">Email</p>
                                    <p class="text-[0.85rem] font-semibold text-gray-800 break-all" id="review-email">
                                        —</p>
                                </div>
                                <div>
                                    <p class="text-[0.72rem] text-gray-400 mb-0.5">Age</p>
                                    <p class="text-[0.85rem] font-semibold text-gray-800" id="review-age">—</p>
                                </div>
                                <div>
                                    <p class="text-[0.72rem] text-gray-400 mb-0.5">Barangay</p>
                                    <p class="text-[0.85rem] font-semibold text-gray-800" id="review-barangay">—</p>
                                </div>
                                <div>
                                    <p class="text-[0.72rem] text-gray-400 mb-0.5">Valid ID</p>
                                    <div class="flex items-center gap-1.5 mt-0.5">
                                        <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <span class="text-[0.8rem] text-gray-600 truncate"
                                            id="review-valid-id">—</span>
                                    </div>
                                </div>

                            </div>
                        </div>

                        {{-- Business Information Card --}}
                        <div
                            class="border border-gray-200 rounded-xl p-5 mb-4 transition-shadow duration-300 hover:shadow-sm">

                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-9 h-9 rounded-full bg-[#ede6f0] flex items-center justify-center shrink-0">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-[#3b1735]"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                            stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M3 21h18M4 21V8l8-5 8 5v13M9 21v-6h6v6" />
                                        </svg>
                                    </div>
                                    <span class="text-[0.92rem] font-bold text-gray-900">Business Information</span>
                                </div>
                                <button type="button" @click="step = 2"
                                    class="text-[0.85rem] font-semibold text-gray-700 border border-gray-300 rounded-md px-4 py-1 hover:bg-gray-50 hover:border-gray-400 transition-all duration-200">
                                    Edit
                                </button>
                            </div>

                            <div class="grid grid-cols-3 gap-x-6 gap-y-4">
                                <div>
                                    <p class="text-[0.72rem] text-gray-400 mb-0.5">Business Name</p>
                                    <p class="text-[0.85rem] font-semibold text-gray-800" id="review-business-name">—
                                    </p>
                                </div>
                                <div>
                                    <p class="text-[0.72rem] text-gray-400 mb-0.5">Category</p>
                                    <p class="text-[0.85rem] font-semibold text-gray-800" id="review-categories">—</p>
                                </div>
                                <div>
                                    <p class="text-[0.72rem] text-gray-400 mb-0.5">Business Permit</p>
                                    <div class="flex items-center gap-1.5 mt-0.5">
                                        <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <span class="text-[0.8rem] text-gray-600 truncate"
                                            id="review-business-permit">—</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Please Review Carefully notice --}}
                        <div class="bg-[#f3edf7] border border-[#d4b8e0] rounded-xl p-4 mb-6">
                            <div class="flex items-start gap-3">
                                <div
                                    class="w-7 h-7 rounded-full bg-[#3b1735] flex items-center justify-center shrink-0 mt-0.5">
                                    <span class="text-white text-[0.8rem] font-bold">!</span>
                                </div>
                                <div>
                                    <p class="text-[0.9rem] font-bold text-gray-900 mb-1">Please Review Carefully</p>
                                    <p class="text-[0.8rem] text-gray-600 leading-relaxed mb-3">
                                        By submitting this registration, you confirm that all information provided is
                                        true and correct.<br>
                                        Our team will review your application and you will be notified via email once
                                        your account is approved.
                                    </p>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" name="agree_terms" required
                                            class="w-3.5 h-3.5 rounded border-gray-400 text-[#3b1735] focus:ring-[#3b1735]">
                                        <span class="text-[0.8rem] text-gray-600">
                                            I agree to the <a href="#"
                                                class="text-[#3b1735] font-semibold hover:underline">Terms and
                                                Conditions</a> and <a href="#"
                                                class="text-[#3b1735] font-semibold hover:underline">Privacy
                                                Policy</a>.
                                        </span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        {{-- Error banner --}}
                        <div x-show="formError" x-cloak x-transition:enter="transition ease-out duration-250"
                            x-transition:enter-start="opacity-0 -translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            class="mb-4 flex items-start gap-2 rounded-lg bg-red-50 border border-red-200 px-4 py-2.5 text-[0.85rem] text-red-600">
                            <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24" stroke-width="2">
                                <circle cx="12" cy="12" r="9" />
                                <path stroke-linecap="round" d="M12 8v5M12 16h.01" />
                            </svg>
                            <span x-text="formError"></span>
                        </div>

                        {{-- Back | Submit --}}
                        <div class="flex justify-end gap-3">
                            <button type="button" @click="step = 2"
                                class="text-[0.85rem] font-medium text-gray-700 border border-gray-300 rounded-lg px-5 py-2 hover:bg-gray-50 hover:border-gray-400 transition-all duration-200 active:scale-[0.97]">
                                Back
                            </button>
                            <button type="submit" :disabled="!Alpine.store('registration').otpVerified || submitting"
                                :class="(!Alpine.store('registration').otpVerified || submitting) ?
                                'bg-[#3b1735]/50 cursor-not-allowed' :
                                'bg-[#3b1735] hover:bg-[#4d1f45] hover:shadow-md hover:-translate-y-0.5 active:translate-y-0 active:scale-[0.97]'"
                                class="text-white text-[0.85rem] font-semibold rounded-lg px-6 py-2 transition-all duration-200 min-w-[110px]">
                                <span x-show="!submitting">Submit</span>
                                <span x-show="submitting" x-cloak class="inline-flex items-center gap-2">
                                    <svg class="animate-spin h-3.5 w-3.5 text-white" fill="none"
                                        viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                            stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                    Submitting&hellip;
                                </span>
                            </button>
                        </div>

                    </div>

                </form>
            </div>
        </div>{{-- end right panel --}}

        {{-- ── Email Verification Modal ─────────────────────────────────────── --}}
        <div x-show="showVerifyModal" style="display:none;" x-transition:enter="transition ease-out duration-250"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
            @keydown.escape.window="showVerifyModal = false" @otp-verified.window="showVerifyModal = false">

            <div x-show="showVerifyModal" x-transition:enter="transition ease-out duration-250 delay-75"
                x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="bg-white rounded-2xl shadow-2xl w-full max-w-[420px] mx-4 px-8 py-10 text-center"
                @click.outside="showVerifyModal = false">

                <div class="flex justify-center mb-6">
                    <div class="w-16 h-16 rounded-full bg-[#ede6f0] flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-[#3b1735]" viewBox="0 0 24 24"
                            fill="currentColor">
                            <path
                                d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 2-8 5-8-5h16zm0 12H4V8l8 5 8-5v10z" />
                            <circle cx="17" cy="17" r="5" fill="#3b1735" />
                            <path d="M15.5 17.5l1 1 2.5-2.5" stroke="white" stroke-width="1.2" stroke-linecap="round"
                                stroke-linejoin="round" fill="none" />
                        </svg>
                    </div>
                </div>

                <h2 class="text-[1.45rem] font-bold text-gray-900 mb-2">Verify Your Email</h2>
                <p class="text-[0.92rem] text-gray-400 font-normal mb-7 leading-relaxed">
                    We've sent a 6-digit code to your email.
                </p>

                <div x-data="otpInput()">
                    <div class="flex justify-center gap-3 mb-4" @paste.prevent="handlePaste($event)">
                        <template x-for="(digit, index) in digits" :key="index">
                            <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]"
                                x-model="digits[index]" :id="'otp-' + index" @input="onInput(index, $event)"
                                @keydown.backspace="onBackspace(index, $event)" @keydown.left="focusAt(index - 1)"
                                @keydown.right="focusAt(index + 1)"
                                class="w-12 h-14 rounded-xl border border-gray-300 text-center text-[1.2rem] font-semibold text-gray-900 focus:outline-none focus:border-[#3b1735] focus:ring-2 focus:ring-[#3b1735]/20 transition-all duration-200">
                        </template>
                    </div>

                    <p x-show="error" x-cloak class="text-[0.85rem] text-red-500 mb-4 text-center" x-text="error">
                    </p>

                    <div class="flex justify-center mb-7">
                        <button type="button" @click="sendCode()" :disabled="sending"
                            class="flex items-center gap-1.5 text-[0.9rem] font-semibold text-[#c0392b] hover:underline disabled:opacity-50 transition-opacity duration-200">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M4 4v5h.582M20 20v-5h-.581M4.582 9A8 8 0 0119.418 15M19.418 15A8 8 0 014.582 9" />
                            </svg>
                            <span x-show="!sending">Resend Code</span>
                            <span x-show="sending" x-cloak>Sending&hellip;</span>
                        </button>
                    </div>

                    <button type="button" @click="verifyCode()" :disabled="verifying"
                        class="w-full bg-[#3b1735] hover:bg-[#4d1f45] text-white text-[1rem] font-bold rounded-full py-3.5 transition-all duration-200 hover:shadow-md active:scale-[0.98] disabled:opacity-60">
                        <span x-show="!verifying">Verify Email</span>
                        <span x-show="verifying" x-cloak>Verifying&hellip;</span>
                    </button>
                </div>

            </div>
        </div>{{-- end verify modal --}}

        {{-- ── Success Modal ────────────────────────────────────────────────── --}}
        <div x-show="showSuccessModal" style="display:none;" x-transition:enter="transition ease-out duration-250"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">

            <div x-show="showSuccessModal" x-transition:enter="transition ease-out duration-300 delay-100"
                x-transition:enter-start="opacity-0 scale-90" x-transition:enter-end="opacity-100 scale-100"
                class="bg-white rounded-3xl shadow-2xl w-full max-w-[400px] mx-4 px-8 py-10 text-center border border-[#e8dff0]">

                <div class="flex justify-center mb-6">
                    <img src="{{ asset('assets/icons/seller-compliance/product-approved-element.svg') }}"
                        alt="Registration approved" class="w-36 h-36 object-contain">
                </div>

                <h2 class="text-[1.3rem] font-bold text-gray-900 mb-2">Thank you for Registering</h2>
                <p class="text-[0.92rem] font-semibold text-gray-700 mb-2">Your registration is pending review</p>
                <p class="text-[0.9rem] text-gray-400 font-normal leading-relaxed mb-8">
                    You will receive an email once your account has been approved by the administrator.
                </p>

                <a href="{{ route('seller.login') }}"
                    class="block w-full bg-[#3b1735] hover:bg-[#4d1f45] text-white text-[1rem] font-semibold rounded-xl py-3 transition-all duration-200 hover:shadow-md active:scale-[0.98]">
                    Go to Login
                </a>

            </div>
        </div>{{-- end success modal --}}

    </div>

    {{-- ── Error Toast ───────────────────────────────────────────────────── --}}
    <div x-data x-show="$store.toast.visible" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 -translate-y-3" x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-3" style="display:none;"
        class="fixed top-5 right-5 z-[100] max-w-sm rounded-xl bg-red-600 text-white shadow-xl px-4 py-3 flex items-start gap-3">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 flex-shrink-0 mt-0.5" viewBox="0 0 24 24"
            fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="9" />
            <path stroke-linecap="round" d="M12 8v5M12 16h.01" />
        </svg>
        <p class="text-[0.9rem] font-medium leading-snug" x-text="$store.toast.message"></p>
        <button type="button" @click="$store.toast.visible = false"
            class="ml-auto text-white/80 hover:text-white transition-colors duration-200">&times;</button>
    </div>

    @include('auth.partials.registration-scripts')
</body>

</html>
