<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Buyer Registration - Vendo</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    @vite(['resources/css/shared/app.css', 'resources/js/shared/app.js'])
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
        @media (prefers-reduced-motion: reduce) {
            .blob, .aurora-sweep, .fade-in-up, .float-illustration { animation: none !important; }
        }
    </style>
</head>

<body class="antialiased bg-white">

    <div class="min-h-screen flex flex-col lg:flex-row" x-data="buyerRegistration">

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
                        Shop and discover<br>with <span class="text-[#c9933a]">Vendo</span>
                    </h2>
                    <div class="w-8 h-[2px] bg-white/40 mt-3 mb-4"></div>
                    <p class="text-white/70 text-[0.9rem] font-light leading-relaxed">
                        Explore products from different sellers, find great deals, and enjoy convenient shopping.
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
                                    2. Address</p>
                                <p class="text-white/50 text-[0.8rem] font-light leading-snug mt-0.5">Provide your
                                    delivery address</p>
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

        <div class="w-full lg:w-[72%] flex flex-col bg-white min-h-screen">

            {{-- Top bar --}}
            <div class="flex items-start justify-between gap-4 flex-wrap px-4 sm:px-8 pt-7 pb-2 fade-in-up" style="animation-delay: .05s">
                <div>
                    <a href="{{ url('/') }}"
                        class="inline-flex items-center gap-1 text-[0.75rem] font-medium text-gray-400 hover:text-[#3b1735] mb-1.5 transition-all duration-200 hover:-translate-x-0.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            stroke-width="2.2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                        </svg>
                        Back to Landing Page
                    </a>
                    <h1 class="text-[1.4rem] font-bold text-gray-900 tracking-tight leading-tight">Buyer Registration
                    </h1>
                    <p class="text-[0.85rem] text-gray-500 mt-0.5 font-normal">Create your buyer account.</p>
                </div>
                <div class="flex items-center gap-2 mt-1">
                    <span class="text-[0.8rem] text-gray-500 font-normal">Already have an account?</span>
                    <a href="{{ route('buyer.login') }}"
                        class="text-[0.85rem] font-semibold text-gray-700 border border-gray-400 rounded-md px-3.5 py-1 hover:bg-gray-50 hover:border-gray-500 transition-all duration-200">
                        Login
                    </a>
                </div>
            </div>

            {{-- Scrollable content --}}
            <div class="flex-1 px-4 sm:px-8 pb-8 overflow-y-auto">

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
                            :class="step >= 2 ? 'font-semibold text-[#3b1735]' : 'font-normal text-gray-400'">Address</span>
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

                <form method="POST" action="{{ route('buyer.register.store') }}" enctype="multipart/form-data"
                    novalidate x-ref="form" @submit.prevent="submitForm($el)">
                    @csrf
                    <p x-show="formError" x-cloak x-text="formError" role="alert"
                        class="mb-4 border border-red-300 rounded-lg p-3 text-sm text-red-700"></p>
                    <p x-show="draftRestored" x-cloak class="mb-4 text-sm text-gray-600">
                        Your progress was restored. Re-enter your password and select your ID files again before submitting.
                    </p>

                    <div x-ref="step1" x-show="step === 1">

                        <div class="mb-1">
                            <h2 class="text-[0.88rem] font-bold text-gray-900 mb-0.5">
                                Personal Information
                            </h2>
                            <p class="text-[0.7rem] text-gray-500 font-normal mb-4">
                                Please provide your personal details.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-3">

                            <div>
                                <label class="block text-[0.85rem] font-semibold text-gray-700 mb-1">
                                    Last Name <span class="text-red-500">*</span>
                                </label>

                                <input type="text" name="last_name" required placeholder="Enter last name"
                                    value="{{ old('last_name') }}"
                                    class="w-full rounded-md border border-gray-200 bg-white text-gray-800 text-[0.85rem] placeholder-gray-400 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#3b1735]/25 focus:border-[#3b1735] transition">
                            </div>

                            <div>
                                <label class="block text-[0.85rem] font-semibold text-gray-700 mb-1">
                                    First Name <span class="text-red-500">*</span>
                                </label>

                                <input type="text" name="first_name" required placeholder="Enter first name"
                                    value="{{ old('first_name') }}"
                                    class="w-full rounded-md border border-gray-200 bg-white text-gray-800 text-[0.85rem] placeholder-gray-400 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#3b1735]/25 focus:border-[#3b1735] transition">
                            </div>

                            <div>
                                <label class="block text-[0.85rem] font-semibold text-gray-700 mb-1">
                                    Middle Initial
                                </label>

                                <input type="text" name="middle_initial" placeholder="Enter middle initial"
                                    value="{{ old('middle_initial') }}"
                                    class="w-full rounded-md border border-gray-200 bg-white text-gray-800 text-[0.85rem] placeholder-gray-400 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#3b1735]/25 focus:border-[#3b1735] transition">
                            </div>

                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-3">

                            <div>
                                <label class="block text-[0.85rem] font-semibold text-gray-700 mb-1">
                                    Sex <span class="text-red-500">*</span>
                                </label>

                                <div class="relative">
                                    <select name="sex" required
                                        class="w-full rounded-md border border-gray-200 bg-white text-gray-500 text-[0.85rem] px-3 py-2 appearance-none focus:outline-none focus:ring-2 focus:ring-[#3b1735]/25 focus:border-[#3b1735] transition">
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
                                    <label class="text-[0.85rem] font-medium text-gray-700">
                                        Email <span class="text-red-500">*</span>
                                    </label>

                                    <button type="button" @click="openOtp()"
                                        x-show="email.length > 0 && !emailVerified"
                                        class="text-[0.65rem] font-semibold text-[#3b1735] hover:underline">
                                        Verify
                                    </button>

                                    <span x-show="emailVerified" class="text-[0.65rem] font-semibold text-green-600">
                                        ✓ Verified
                                    </span>
                                </div>

                                <input type="email" name="email" required placeholder="Enter email address"
                                    x-model="email" @input="emailVerified = false" value="{{ old('email') }}"
                                    class="w-full rounded-md border border-gray-200 bg-white text-gray-800 text-[0.85rem] placeholder-gray-400 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#3b1735]/25 focus:border-[#3b1735] transition"
                                    >
                            </div>

                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-3">

                            <div>
                                <label class="block text-[0.85rem] font-semibold text-gray-700 mb-1">
                                    Birthday <span class="text-red-500">*</span>
                                </label>

                                <input type="date" name="birthday" required max="{{ now()->subDay()->toDateString() }}" value="{{ old('birthday') }}"
                                    onchange="
                                        const b = new Date(this.value);
                                        const t = new Date();
                                        let age = t.getFullYear() - b.getFullYear();
                                        const m = t.getMonth() - b.getMonth();
                                        if (m < 0 || (m === 0 && t.getDate() < b.getDate())) age--;
                                        document.querySelector('[name=age]').value = isNaN(age) ? '' : age;
                                    "
                                    class="w-full rounded-md border border-gray-200 bg-white text-gray-500 text-[0.85rem] px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#3b1735]/25 focus:border-[#3b1735] transition">
                            </div>

                            <div>
                                <label class="block text-[0.85rem] font-semibold text-gray-700 mb-1">
                                    Age <span class="text-red-500">*</span>
                                </label>

                                <input type="text" name="age" readonly placeholder="Enter age"
                                    class="w-full rounded-md border border-gray-200 bg-white text-gray-800 text-[0.85rem] placeholder-gray-400 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#3b1735]/25 focus:border-[#3b1735] transition">
                            </div>

                        </div>

                        <div class="mb-3">
                            <label class="block text-[0.85rem] font-semibold text-gray-700 mb-1">
                                ID Category <span class="text-red-500">*</span>
                            </label>

                            <select name="id_category" required x-model="idCategory"
                                class="w-full rounded-md border border-gray-200 bg-white text-gray-500 text-[0.85rem] px-3 py-2 appearance-none focus:outline-none focus:ring-2 focus:ring-[#3b1735]/25 focus:border-[#3b1735] transition">
                                <option value="primary">Primary ID (1 ID)</option>
                                <option value="secondary">Secondary ID (2 IDs required)</option>
                            </select>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-5" x-show="idCategory === 'primary'">

                            <div>
                                <label class="block text-[0.85rem] font-semibold text-gray-700 mb-1">
                                    ID Type <span class="text-red-500">*</span>
                                </label>

                                <select name="id_type" :required="idCategory === 'primary'" :disabled="idCategory !== 'primary'"
                                    class="w-full rounded-md border border-gray-200 bg-white text-gray-500 text-[0.85rem] px-3 py-2 appearance-none focus:outline-none focus:ring-2 focus:ring-[#3b1735]/25 focus:border-[#3b1735] transition">
                                    <option value="" disabled selected>Select ID Type</option>
                                    <option value="Philippine Passport">Philippine Passport</option>
                                    <option value="PhilSys National ID">PhilSys National ID</option>
                                    <option value="Driver's License">Driver's License</option>
                                    <option value="UMID">UMID</option>
                                    <option value="SSS ID">SSS ID</option>
                                    <option value="GSIS ID">GSIS ID</option>
                                    <option value="PRC ID">PRC ID</option>
                                    <option value="Postal ID">Postal ID</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-[0.85rem] font-semibold text-gray-700 mb-1">
                                    Upload ID <span class="text-red-500">*</span>
                                </label>

                                <label
                                    class="flex items-center justify-between w-full rounded-md border border-gray-200 bg-white text-gray-400 text-[0.85rem] px-3 py-2 cursor-pointer hover:bg-gray-50 transition">

                                    <span id="valid-id-label">Upload ID here</span>

                                    <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                    </svg>

                                    <input type="file" name="valid_id" id="primary-valid-id" :required="idCategory === 'primary'" class="hidden"
                                        accept="image/*,.pdf" :disabled="idCategory !== 'primary'"
                                        onchange="document.getElementById('valid-id-label').textContent = this.files[0]?.name || 'Upload ID here'">
                                </label>
                            </div>

                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-5" x-show="idCategory === 'secondary'" x-cloak>

                            <div>
                                <label class="block text-[0.85rem] font-semibold text-gray-700 mb-1">
                                    First Secondary ID Type <span class="text-red-500">*</span>
                                </label>

                                <select name="id_type_1" :required="idCategory === 'secondary'" :disabled="idCategory !== 'secondary'"
                                    class="w-full rounded-md border border-gray-200 bg-white text-gray-500 text-[0.85rem] px-3 py-2 appearance-none focus:outline-none focus:ring-2 focus:ring-[#3b1735]/25 focus:border-[#3b1735] transition">
                                    <option value="" disabled selected>Select ID Type</option>
                                    <option value="PhilHealth ID">PhilHealth ID</option>
                                    <option value="TIN ID">TIN ID</option>
                                    <option value="Voter's ID/Certification">Voter's ID/Certification</option>
                                    <option value="NBI Clearance">NBI Clearance</option>
                                    <option value="Barangay Certification">Barangay Certification</option>
                                    <option value="Company ID">Company ID</option>
                                    <option value="School ID">School ID</option>
                                    <option value="Senior Citizen/PWD ID">Senior Citizen/PWD ID</option>
                                </select>

                                <label
                                    class="flex items-center justify-between w-full rounded-md border border-gray-200 bg-white text-gray-400 text-[0.85rem] px-3 py-2 cursor-pointer hover:bg-gray-50 transition mt-2">

                                    <span id="valid-id-1-label">Upload ID here</span>

                                    <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                    </svg>

                                    <input type="file" name="valid_id" id="secondary-valid-id-1" :required="idCategory === 'secondary'" class="hidden"
                                        accept="image/*,.pdf" :disabled="idCategory !== 'secondary'"
                                        onchange="document.getElementById('valid-id-1-label').textContent = this.files[0]?.name || 'Upload ID here'">
                                </label>
                                <p class="text-[0.7rem] text-gray-400 mt-1">Accepted formats: JPEG, PNG, or PDF.</p>
                            </div>

                            <div>
                                <label class="block text-[0.85rem] font-semibold text-gray-700 mb-1">
                                    Second Secondary ID Type <span class="text-red-500">*</span>
                                </label>

                                <select name="id_type_2" :required="idCategory === 'secondary'" :disabled="idCategory !== 'secondary'"
                                    class="w-full rounded-md border border-gray-200 bg-white text-gray-500 text-[0.85rem] px-3 py-2 appearance-none focus:outline-none focus:ring-2 focus:ring-[#3b1735]/25 focus:border-[#3b1735] transition">
                                    <option value="" disabled selected>Select ID Type</option>
                                    <option value="PhilHealth ID">PhilHealth ID</option>
                                    <option value="TIN ID">TIN ID</option>
                                    <option value="Voter's ID/Certification">Voter's ID/Certification</option>
                                    <option value="NBI Clearance">NBI Clearance</option>
                                    <option value="Barangay Certification">Barangay Certification</option>
                                    <option value="Company ID">Company ID</option>
                                    <option value="School ID">School ID</option>
                                    <option value="Senior Citizen/PWD ID">Senior Citizen/PWD ID</option>
                                </select>

                                <label
                                    class="flex items-center justify-between w-full rounded-md border border-gray-200 bg-white text-gray-400 text-[0.85rem] px-3 py-2 cursor-pointer hover:bg-gray-50 transition mt-2">

                                    <span id="valid-id-2-label">Upload ID here</span>

                                    <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                    </svg>

                                    <input type="file" name="valid_id_2" id="secondary-valid-id-2" :required="idCategory === 'secondary'" class="hidden"
                                        accept="image/*,.pdf" :disabled="idCategory !== 'secondary'"
                                        onchange="document.getElementById('valid-id-2-label').textContent = this.files[0]?.name || 'Upload ID here'">
                                </label>
                            </div>

                        </div>

                        <hr class="border-gray-200 mb-5">

                        <div class="mb-1">
                            <h2 class="text-[0.88rem] font-bold text-gray-900 mb-0.5">
                                Account Security
                            </h2>
                            <p class="text-[0.7rem] text-gray-500 font-normal mb-4">
                                Set a password to secure your buyer account.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-1">

                            <div>
                                <label class="block text-[0.85rem] font-semibold text-gray-700 mb-1">
                                    Password <span class="text-red-500">*</span>
                                </label>

                                <div class="relative">
                                    <input name="password" required minlength="8" x-bind:type="showPassword ? 'text' : 'password'"
                                        placeholder="Create a password"
                                        class="w-full rounded-md border border-gray-200 bg-white text-gray-800 text-[0.85rem] placeholder-gray-400 px-3 py-2 pr-9 focus:outline-none focus:ring-2 focus:ring-[#3b1735]/25 focus:border-[#3b1735] transition">

                                    <button type="button" @click="showPassword = !showPassword"
                                        class="absolute inset-y-0 right-0 flex items-center justify-center w-9 text-gray-400 hover:text-gray-600 transition"
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
                                <label class="block text-[0.85rem] font-semibold text-gray-700 mb-1">
                                    Confirm Password <span class="text-red-500">*</span>
                                </label>

                                <div class="relative">
                                    <input name="password_confirmation" required
                                        x-bind:type="showConfirmPassword ? 'text' : 'password'"
                                        placeholder="Confirm your password"
                                        class="w-full rounded-md border border-gray-200 bg-white text-gray-800 text-[0.85rem] placeholder-gray-400 px-3 py-2 pr-9 focus:outline-none focus:ring-2 focus:ring-[#3b1735]/25 focus:border-[#3b1735] transition">

                                    <button type="button" @click="showConfirmPassword = !showConfirmPassword"
                                        class="absolute inset-y-0 right-0 flex items-center justify-center w-9 text-gray-400 hover:text-gray-600 transition"
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

                        <p class="text-[0.62rem] text-gray-400 mt-1.5 mb-6 font-normal">
                            Minimum 8 characters, including uppercase and lowercase letters, a number, and a symbol.
                        </p>

                        <div class="flex justify-end">
                            <button type="button" @click="goNext(1)"
                                class="flex items-center gap-1.5 bg-[#3b1735] hover:bg-[#4d1f45] active:bg-[#2e1229] text-white text-[0.75rem] font-semibold rounded-lg px-5 py-2 transition-colors duration-150">
                                Next: Contact &amp; Address

                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                        </div>

                        <p x-show="step1Error" x-text="step1Error"
                            class="text-red-500 text-[0.7rem] text-right mt-2">
                        </p>

                    </div>

                    <div x-ref="step2" x-show="step === 2" x-cloak>

                        <div class="mb-1">
                            <h2 class="text-[0.88rem] font-bold text-gray-900 mb-0.5">
                                Address
                            </h2>

                            <p class="text-[0.7rem] text-gray-500 font-normal mb-4">
                                Please provide your complete address.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-3">

                            <div>
                                <label class="block text-[0.85rem] font-semibold text-gray-700 mb-1">
                                    Province <span class="text-red-500">*</span>
                                </label>

                                <div class="relative">
                                    <select name="province" required id="province-select"
                                        class="w-full rounded-md border border-gray-200 bg-white text-gray-500 text-[0.85rem] px-3 py-2 appearance-none focus:outline-none focus:ring-2 focus:ring-[#3b1735]/25 focus:border-[#3b1735] transition">
                                        <option value="" disabled selected>Select Province</option>
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
                                <label class="block text-[0.85rem] font-semibold text-gray-700 mb-1">
                                    Municipality / City <span class="text-red-500">*</span>
                                </label>

                                <div class="relative">
                                    <select name="municipality" required id="municipality-select" disabled
                                        class="w-full rounded-md border border-gray-200 bg-white text-gray-500 text-[0.85rem] px-3 py-2 appearance-none focus:outline-none focus:ring-2 focus:ring-[#3b1735]/25 focus:border-[#3b1735] transition">
                                        <option value="" disabled selected>Select Municipality / City</option>
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
                                <label class="block text-[0.85rem] font-semibold text-gray-700 mb-1">
                                    Barangay <span class="text-red-500">*</span>
                                </label>

                                <div class="relative">
                                    <select name="barangay" required id="barangay-select" disabled
                                        class="w-full rounded-md border border-gray-200 bg-white text-gray-500 text-[0.85rem] px-3 py-2 appearance-none focus:outline-none focus:ring-2 focus:ring-[#3b1735]/25 focus:border-[#3b1735] transition">
                                        <option value="" disabled selected>Select Barangay</option>
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

                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-8">

<div>
                                <label class="block text-[0.85rem] font-semibold text-gray-700 mb-1">
                                    Street / House No. <span class="text-red-500">*</span>
                                </label>

                                <input type="text" name="street" required
                                    placeholder="House/Unit No., street, building, subdivision, etc."
                                    class="w-full rounded-md border border-gray-200 bg-white text-gray-800 text-[0.85rem] placeholder-gray-400 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#3b1735]/25 focus:border-[#3b1735] transition">
                            </div>

                            <div>
                                <label class="block text-[0.85rem] font-semibold text-gray-700 mb-1">
                                    Zip Code <span class="text-red-500">*</span>
                                </label>

                                <input type="text" name="zip_code" required placeholder="Enter zip code"
                                    class="w-full rounded-md border border-gray-200 bg-white text-gray-800 text-[0.85rem] placeholder-gray-400 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#3b1735]/25 focus:border-[#3b1735] transition">
                            </div>

                            <div>
                                <label class="block text-[0.85rem] font-semibold text-gray-700 mb-1">
                                    Contact Number <span class="text-red-500">*</span>
                                </label>

                                <input type="text" name="contact_number" required inputmode="numeric" pattern="[0-9]{10,15}" maxlength="15" placeholder="09XX XXX XXXX"
                                    class="w-full rounded-md border border-gray-200 bg-white text-gray-800 text-[0.85rem] placeholder-gray-400 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#3b1735]/25 focus:border-[#3b1735] transition">
                            </div>

                        </div>

                        <div class="flex justify-end gap-3">

                            <button type="button" @click="step = 1"
                                class="text-[0.75rem] font-medium text-gray-700 border border-gray-300 rounded-lg px-5 py-2 hover:bg-gray-50 transition">
                                Back
                            </button>

                            <button type="button" @click="goNext(2)"
                                class="flex items-center gap-1.5 bg-[#3b1735] hover:bg-[#4d1f45] active:bg-[#2e1229] text-white text-[0.75rem] font-semibold rounded-lg px-5 py-2 transition-colors duration-150">
                                Next: Review &amp; Submit

                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M9 5l7 7-7 7" />
                                </svg>
                            </button>

                        </div>

                        <p x-show="step2Error" x-text="step2Error"
                            class="text-red-500 text-[0.7rem] text-right mt-2">
                        </p>

                    </div>

                    <div x-ref="step3" x-show="step === 3" x-cloak>

                        <div class="mb-4">
                            <h2 class="text-[0.88rem] font-bold text-gray-900 mb-0.5">
                                Review your Information
                            </h2>

                            <p class="text-[0.7rem] text-gray-500 font-normal">
                                Please review all the details below before submitting your registration.
                            </p>
                        </div>

                        <div class="border border-gray-200 rounded-xl p-5 mb-4">

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

                                    <span class="text-[0.82rem] font-bold text-gray-900">
                                        User Information
                                    </span>
                                </div>

                                <button type="button" @click="step = 1"
                                    class="text-[0.7rem] font-medium text-gray-700 border border-gray-300 rounded-md px-4 py-1 hover:bg-gray-50 transition">
                                    Edit
                                </button>

                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-x-6 gap-y-4">

                                <div>
                                    <p class="text-[0.62rem] text-gray-400 mb-0.5">
                                        Full Name
                                    </p>

                                    <p class="text-[0.85rem] font-semibold text-gray-800"
                                        x-text="
                                            reviewVersion && (((document.querySelector('[name=first_name]')?.value || '') + ' ' +
                                            (document.querySelector('[name=middle_initial]')?.value
                                                ? document.querySelector('[name=middle_initial]').value + '. '
                                                : '') +
                                            (document.querySelector('[name=last_name]')?.value || '')) || '—')
                                        ">
                                    </p>
                                </div>

                                <div>
                                    <p class="text-[0.62rem] text-gray-400 mb-0.5">
                                        Phone Number
                                    </p>

                                    <p class="text-[0.85rem] font-semibold text-gray-800"
                                        x-text="reviewVersion && document.querySelector('[name=contact_number]')?.value || '—'">
                                    </p>
                                </div>

                                <div>
                                    <p class="text-[0.62rem] text-gray-400 mb-0.5">
                                        Province
                                    </p>

                                    <p class="text-[0.85rem] font-semibold text-gray-800"
                                        x-text="reviewVersion && document.querySelector('[name=province]')?.options[document.querySelector('[name=province]')?.selectedIndex]?.text || '—'">
                                    </p>
                                </div>

                                <div>
                                    <p class="text-[0.62rem] text-gray-400 mb-0.5">
                                        Street / House No.
                                    </p>

                                    <p class="text-[0.85rem] font-semibold text-gray-800"
                                        x-text="
                                            reviewVersion && (document.querySelector('[name=street]')?.value || '—')
                                        ">
                                    </p>
                                </div>

                                <div>
                                    <p class="text-[0.62rem] text-gray-400 mb-0.5">
                                        Sex
                                    </p>

                                    <p class="text-[0.85rem] font-semibold text-gray-800 capitalize"
                                        x-text="reviewVersion && document.querySelector('[name=sex]')?.value || '—'">
                                    </p>
                                </div>

                                <div>
                                    <p class="text-[0.62rem] text-gray-400 mb-0.5">
                                        Birthday
                                    </p>

                                    <p class="text-[0.85rem] font-semibold text-gray-800"
                                        x-text="reviewVersion && document.querySelector('[name=birthday]')?.value || '—'">
                                    </p>
                                </div>

                                <div>
                                    <p class="text-[0.62rem] text-gray-400 mb-0.5">
                                        Municipality
                                    </p>

                                    <p class="text-[0.85rem] font-semibold text-gray-800"
                                        x-text="reviewVersion && document.querySelector('[name=municipality]')?.options[document.querySelector('[name=municipality]')?.selectedIndex]?.text || '—'">
                                    </p>
                                </div>

                                <div>
                                    <p class="text-[0.62rem] text-gray-400 mb-0.5">
                                        Zip Code
                                    </p>

                                    <p class="text-[0.85rem] font-semibold text-gray-800"
                                        x-text="reviewVersion && document.querySelector('[name=zip_code]')?.value || '—'">
                                    </p>
                                </div>

                                <div>
                                    <p class="text-[0.62rem] text-gray-400 mb-0.5">
                                        Email
                                    </p>

                                    <p class="text-[0.85rem] font-semibold text-gray-800 break-all"
                                        x-text="reviewVersion && document.querySelector('[name=email]')?.value || '—'">
                                    </p>
                                </div>

                                <div>
                                    <p class="text-[0.62rem] text-gray-400 mb-0.5">
                                        Age
                                    </p>

                                    <p class="text-[0.85rem] font-semibold text-gray-800"
                                        x-text="reviewVersion && document.querySelector('[name=age]')?.value || '—'">
                                    </p>
                                </div>

                                <div>
                                    <p class="text-[0.62rem] text-gray-400 mb-0.5">
                                        Barangay
                                    </p>

                                    <p class="text-[0.85rem] font-semibold text-gray-800"
                                        x-text="reviewVersion && document.querySelector('[name=barangay]')?.options[document.querySelector('[name=barangay]')?.selectedIndex]?.text || '—'">
                                    </p>
                                </div>

                                <div>
                                    <p class="text-[0.62rem] text-gray-400 mb-0.5">
                                        Valid ID
                                    </p>

                                    <div class="flex items-center gap-1.5 mt-0.5">
                                        <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>

                                        <span class="text-[0.85rem] text-gray-600 truncate" id="review-valid-id" x-text="reviewFiles">
                                            —
                                        </span>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <div class="bg-[#f3edf7] border border-[#d4b8e0] rounded-xl p-4 mb-6">

                            <div class="flex items-start gap-3">

                                <div
                                    class="w-7 h-7 rounded-full bg-[#3b1735] flex items-center justify-center shrink-0 mt-0.5">
                                    <span class="text-white text-[0.7rem] font-bold">!</span>
                                </div>

                                <div>

                                    <p class="text-[0.78rem] font-bold text-gray-900 mb-1">
                                        Please Review Carefully
                                    </p>

                                    <p class="text-[0.85rem] text-gray-600 leading-relaxed mb-3">
                                        By submitting this registration, you confirm that all information provided
                                        is
                                        true and correct.<br>
                                        Our team will review your application and you will be notified via email
                                        once
                                        your account is approved.
                                    </p>

                                    <label class="flex items-center gap-2 cursor-pointer">

                                        <input type="checkbox" name="agree_terms" required x-model="agreeTerms"
                                            class="w-3.5 h-3.5 rounded border-gray-400 text-[#3b1735] focus:ring-[#3b1735]">

                                        <span class="text-[0.85rem] text-gray-600">
                                            I agree to the
                                            <a href="#" class="text-[#3b1735] font-semibold hover:underline">
                                                Terms and Conditions
                                            </a>
                                            and
                                            <a href="#" class="text-[#3b1735] font-semibold hover:underline">
                                                Privacy Policy
                                            </a>.
                                        </span>

                                    </label>

                                </div>

                            </div>
                        </div>

                        <div class="flex justify-end gap-3">

                            <button type="button" @click="step = 2"
                                class="text-[0.75rem] font-medium text-gray-700 border border-gray-300 rounded-lg px-5 py-2 hover:bg-gray-50 transition">
                                Back
                            </button>

                            <button type="submit" :disabled="submitting"
                                class="bg-[#3b1735] hover:bg-[#4d1f45] text-white text-[0.75rem] font-semibold rounded-lg px-6 py-2 transition-colors duration-150 disabled:opacity-50">
                                <span x-show="!submitting">Submit</span>
                                <span x-show="submitting" x-cloak>Submitting…</span>
                            </button>

                        </div>

                        <p x-show="step3Error" x-text="step3Error"
                            class="text-red-500 text-[0.7rem] text-right mt-2">
                        </p>

                    </div>

                </form>
            </div>
        </div>

        <div x-show="showVerifyModal" style="display:none;"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
            @keydown.escape.window="showVerifyModal = false">

            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-[420px] mx-4 px-8 py-10 text-center"
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

                <h2 class="text-[1.3rem] font-bold text-gray-900 mb-2">
                    Verify Your Email
                </h2>

                <p class="text-[0.82rem] text-gray-400 font-normal mb-7 leading-relaxed">
                    <span x-text="sending ? 'Sending your code…' : (otpSent ? 'We sent a 6-digit code to your email.' : 'Request a code to verify your email.')"></span><br>
                    <span class="text-[0.7rem] text-gray-400">
                        Can't find it? Check your Spam or Junk folder.
                    </span>
                </p>

                <div class="flex justify-center gap-1.5 sm:gap-3 mb-6"
                    @paste.prevent="handlePaste($event)">

                    <template x-for="(digit, index) in digits" :key="index">
                        <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]"
                            x-model="digits[index]" :id="'otp-' + index" @input="onInput(index, $event)"
                            @keydown.backspace="onBackspace(index)" @keydown.left="focusAt(index - 1)"
                            @keydown.right="focusAt(index + 1)"
                            class="w-9 sm:w-12 h-12 sm:h-14 rounded-xl border border-gray-300 text-center text-[1.1rem] font-semibold text-gray-900 focus:outline-none focus:border-[#3b1735] focus:ring-2 focus:ring-[#3b1735]/20 transition">
                    </template>

                </div>

                <div class="flex justify-center mb-7">
                    <button type="button" @click="sendOtp()" :disabled="sending || verifying"
                        class="flex items-center gap-1.5 text-[0.78rem] font-semibold text-[#c0392b] hover:underline">

                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M4 4v5h.582M20 20v-5h-.581M4.582 9A8 8 0 0119.418 15M19.418 15A8 8 0 014.582 9" />
                        </svg>

                        Resend Code
                    </button>
                </div>

                <button type="button" @click="verifyOtp()" :disabled="sending || verifying"
                    class="w-full bg-[#3b1735] hover:bg-[#4d1f45] text-white text-[0.9rem] font-bold rounded-full py-3.5 transition-colors duration-150">
                    Verify Email
                </button>

                <p x-show="otpError" x-text="otpError" class="text-red-500 text-[0.7rem] mt-3">
                </p>

            </div>
        </div>

        <div x-show="showSuccessModal" style="display:none;"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">

            <div
                class="bg-white rounded-3xl shadow-2xl w-full max-w-[400px] mx-4 px-8 py-10 text-center border border-[#e8dff0]">

                <div class="flex justify-center mb-6">
                    <img src="{{ asset('assets/icons/seller-compliance/product-approved-element.svg') }}"
                        alt="Registration approved" class="w-36 h-36 object-contain">
                </div>

                <h2 class="text-[1.15rem] font-bold text-gray-900 mb-2">
                    Thank you for Registering
                </h2>

                <p class="text-[0.82rem] font-semibold text-gray-700 mb-2">
                    Your registration is pending review
                </p>

                <p class="text-[0.78rem] text-gray-400 font-normal leading-relaxed mb-8">
                    You will receive an email once your account has<br>
                    been approved by the administrator
                </p>

                <a href="{{ route('buyer.login') }}"
                    class="block w-full bg-[#3b1735] hover:bg-[#4d1f45] text-white text-[0.88rem] font-semibold rounded-xl py-3 transition-colors duration-150">
                    Go to Login
                </a>

            </div>
        </div>

    </div>

    <script type="application/json" id="buyer-registration-config">@json($registrationVerification)</script>
    @include('buyer.auth.registration-scripts')
</body>
</html>
