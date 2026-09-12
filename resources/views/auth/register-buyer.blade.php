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
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        * {
            font-family: 'Poppins', sans-serif;
        }

        [x-cloak] {
            display: none !important;
        }

        @keyframes shake {

            0%,
            100% {
                transform: translateX(0);
            }

            20%,
            60% {
                transform: translateX(-4px);
            }

            40%,
            80% {
                transform: translateX(4px);
            }
        }

        .shake-error {
            animation: shake 0.4s ease-in-out;
            border-color: #ef4444 !important;
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
        otpSent: false,
        otpError: '',
        idCategory: 'primary',
        email: '',
        emailVerified: false,
        step1Error: '',

        sendOtp() {
            const email = document.querySelector('[name=email]').value;
            if (!email) {
                this.otpError = 'Enter your email first.';
                return;
            }

            fetch('/buyer/otp/send', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ||
                        document.querySelector('input[name=_token]').value
                },
                body: JSON.stringify({ email })
            }).then(() => {
                this.otpSent = true;
            });
        },

        verifyOtp() {
            const email = document.querySelector('[name=email]').value;
            const otp_code = [...document.querySelectorAll('[id^=otp-]')]
                .map(el => el.value)
                .join('');

            fetch('/buyer/otp/verify', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ||
                        document.querySelector('input[name=_token]').value
                },
                body: JSON.stringify({ email, otp_code })
            })
            .then(res => res.json().then(data => ({
                status: res.status,
                data
            })))
            .then(({ status, data }) => {
                if (status !== 200) {
                    this.otpError = data.message;
                    return;
                }

                this.showVerifyModal = false;
                this.emailVerified = true;
            });
        },

        shakeField(el) {
            if (!el) return;

            el.classList.remove('shake-error');
            void el.offsetWidth;
            el.classList.add('shake-error');

            setTimeout(() => el.classList.remove('shake-error'), 500);
        },

        validateStep1() {
            this.step1Error = '';
            let valid = true;

            const required = [
                'last_name',
                'first_name',
                'sex',
                'email',
                'birthday',
                'age',
                'password',
                'password_confirmation'
            ];

            for (const name of required) {
                const el = document.querySelector(`[name=${name}]`);

                if (!el || !el.value) {
                    this.shakeField(el);
                    valid = false;
                }
            }

            const pw = document.querySelector('[name=password]');
            const pwConfirm = document.querySelector('[name=password_confirmation]');

            if (pw.value && pwConfirm.value && pw.value !== pwConfirm.value) {
                this.shakeField(pwConfirm);
                this.step1Error = 'Passwords do not match.';
                valid = false;
            }

            if (this.idCategory === 'primary') {
                const idType = document.querySelector('[name=id_type]');
                const validId = document.querySelector('#primary-valid-id');

                if (!idType || !idType.value) {
                    this.shakeField(idType);
                    valid = false;
                }

                if (!validId || !validId.files.length) {
                    this.shakeField(validId);
                    valid = false;
                }
            }

            if (this.idCategory === 'secondary') {
                const idType1 = document.querySelector('[name=id_type_1]');
                const idType2 = document.querySelector('[name=id_type_2]');
                const validId1 = document.querySelector('#secondary-valid-id-1');
                const validId2 = document.querySelector('#secondary-valid-id-2');

                if (!idType1 || !idType1.value) {
                    this.shakeField(idType1);
                    valid = false;
                }

                if (!idType2 || !idType2.value) {
                    this.shakeField(idType2);
                    valid = false;
                }

                if (!validId1 || !validId1.files.length) {
                    this.shakeField(validId1);
                    valid = false;
                }

                if (!validId2 || !validId2.files.length) {
                    this.shakeField(validId2);
                    valid = false;
                }
            }

            if (!this.emailVerified) {
                this.shakeField(document.querySelector('[name=email]'));
                this.step1Error = 'Please verify your email first.';
                valid = false;
            }

            if (!valid && !this.step1Error) {
                this.step1Error = 'Please fill in all required fields.';
            }

            return valid;
        },

        step2Error: '',

        validateStep2() {
            this.step2Error = '';
            let valid = true;

            const required = [
                'province',
                'municipality',
                'barangay',
                'house_no',
                'street',
                'zip_code',
                'contact_number'
            ];

            for (const name of required) {
                const el = document.querySelector(`[name=${name}]`);

                if (!el || !el.value) {
                    this.shakeField(el);
                    valid = false;
                }
            }

            if (!valid) {
                this.step2Error = 'Please fill in all required fields.';
            }

            return valid;
        },

        agreeTerms: false,
        step3Error: '',

        validateStep3() {
            this.step3Error = '';

            if (!this.agreeTerms) {
                this.step3Error = 'Please agree to the Terms and Conditions and Privacy Policy.';
                return false;
            }

            return true;
        }
    }">

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
                    Explore products from different sellers, find great deals, and enjoy a convenient shopping
                    experience delivered right to your doorstep.
                </p>
            </div>

            <div class="w-full flex justify-center mb-4 relative z-10">
                <img src="{{ asset('assets/icons/registration/left-side-panel/house-left-panel.svg') }}"
                    alt="Shop illustration" class="w-full max-w-[260px]">
            </div>

            <div class="w-full mt-2 relative z-10">
                <p class="text-white text-[0.82rem] font-semibold mb-4">Registration Steps</p>

                <div class="flex flex-col gap-0">

                    <div class="flex items-start gap-3">
                        <div class="flex flex-col items-center shrink-0">
                            <div
                                class="w-6 h-6 rounded-full bg-[#402143] border border-white/40 text-white text-[0.65rem] font-semibold flex items-center justify-center shrink-0">
                                1
                            </div>
                            <div class="w-px bg-white/20 my-1" style="height: 32px;"></div>
                        </div>

                        <div class="pb-4">
                            <p class="text-white text-[0.78rem] font-semibold leading-tight">
                                1. Personal Information
                            </p>
                            <p class="text-white/60 text-[0.7rem] font-light leading-snug mt-0.5">
                                Tell us about yourself
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <div class="flex flex-col items-center shrink-0">
                            <div
                                class="w-6 h-6 rounded-full bg-[#402143] border border-white/20 text-white/40 text-[0.65rem] font-semibold flex items-center justify-center shrink-0">
                                2
                            </div>
                            <div class="w-px bg-white/20 my-1" style="height: 40px;"></div>
                        </div>

                        <div class="pb-4">
                            <p class="text-white/70 text-[0.78rem] font-semibold leading-tight">
                                2. Contact &amp; Address
                            </p>
                            <p class="text-white/50 text-[0.7rem] font-light leading-snug mt-0.5">
                                Tell about your contact and where you live from
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <div
                            class="w-6 h-6 rounded-full bg-[#402143] border border-white/20 text-white/40 text-[0.65rem] font-semibold flex items-center justify-center shrink-0">
                            3
                        </div>

                        <div>
                            <p class="text-white/70 text-[0.78rem] font-semibold leading-tight">
                                3. Review &amp; Submit
                            </p>
                            <p class="text-white/50 text-[0.7rem] font-light leading-snug mt-0.5">
                                Review your information and submit
                            </p>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <div class="w-full lg:w-[72%] flex flex-col bg-white min-h-screen">

            <div class="flex items-start justify-between px-8 pt-7 pb-2">
                <div>
                    <h1 class="text-[1.25rem] font-bold text-gray-900 tracking-tight leading-tight">
                        Buyer Registration
                    </h1>
                    <p class="text-[0.72rem] text-gray-500 mt-0.5 font-normal">
                        Create your buyer account.
                    </p>
                </div>

                <div class="flex items-center gap-2 mt-1">
                    <span class="text-[0.7rem] text-gray-500 font-normal">
                        Already have an account?
                    </span>

                    <a href="{{ route('buyer.login') }}"
                        class="text-[0.7rem] font-medium text-gray-700 border border-gray-400 rounded-md px-3.5 py-1 hover:bg-gray-50 transition">
                        Login
                    </a>
                </div>
            </div>

            <div class="flex-1 px-8 pb-8 overflow-y-auto">

                <div class="flex items-center mt-5 mb-1 w-full max-w-[580px] mx-auto gap-0">

                    <div class="shrink-0 w-8">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-[0.72rem] font-semibold leading-none"
                            :class="step >= 1 ? 'bg-[#3b1735] text-white' : 'border border-gray-300 bg-white text-gray-400'">
                            1
                        </div>
                    </div>

                    <div class="flex-1 flex h-px shrink">
                        <div class="w-1/2 h-px bg-[#3b1735]"></div>
                        <div class="w-1/2 h-px" :class="step >= 2 ? 'bg-[#3b1735]' : 'bg-gray-300'"></div>
                    </div>

                    <div class="shrink-0 w-8">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-[0.72rem] font-semibold leading-none"
                            :class="step >= 2 ? 'bg-[#3b1735] text-white' : 'border border-gray-300 bg-white text-gray-400'">
                            2
                        </div>
                    </div>

                    <div class="flex-1 flex h-px shrink">
                        <div class="w-1/2 h-px" :class="step >= 2 ? 'bg-[#3b1735]' : 'bg-gray-300'"></div>
                        <div class="w-1/2 h-px" :class="step >= 3 ? 'bg-[#3b1735]' : 'bg-gray-300'"></div>
                    </div>

                    <div class="shrink-0 w-8">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-[0.72rem] font-semibold leading-none"
                            :class="step >= 3 ? 'bg-[#3b1735] text-white' : 'border border-gray-300 bg-white text-gray-400'">
                            3
                        </div>
                    </div>

                </div>

                <div class="relative flex w-full max-w-[580px] mx-auto mb-6 mt-1.5">
                    <div class="shrink-0 w-8 relative">
                        <span
                            class="absolute left-1/2 -translate-x-1/2 text-[0.62rem] whitespace-nowrap"
                            :class="step >= 1 ? 'font-semibold text-[#3b1735]' : 'font-normal text-gray-400'">
                            Personal Information
                        </span>
                    </div>

                    <div class="flex-1"></div>

                    <div class="shrink-0 w-8 relative">
                        <span
                            class="absolute left-1/2 -translate-x-1/2 text-[0.62rem] whitespace-nowrap"
                            :class="step >= 2 ? 'font-semibold text-[#3b1735]' : 'font-normal text-gray-400'">
                            Contact &amp; Address
                        </span>
                    </div>

                    <div class="flex-1"></div>

                    <div class="shrink-0 w-8 relative">
                        <span
                            class="absolute left-1/2 -translate-x-1/2 text-[0.62rem] whitespace-nowrap"
                            :class="step >= 3 ? 'font-semibold text-[#3b1735]' : 'font-normal text-gray-400'">
                            Review &amp; Submit
                        </span>
                    </div>
                </div>

                <div class="mb-4"></div>

                @if ($errors->any())
                    <div
                        class="max-w-[580px] mx-auto mb-4 bg-red-50 border border-red-300 text-red-700 text-[0.72rem] rounded-md p-3">
                        <ul class="list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('buyer.register.store') }}"
                    enctype="multipart/form-data">
                    @csrf

                    <div x-show="step === 1">

                        <div class="mb-1">
                            <h2 class="text-[0.88rem] font-bold text-gray-900 mb-0.5">
                                Personal Information
                            </h2>
                            <p class="text-[0.7rem] text-gray-500 font-normal mb-4">
                                Please provide your personal details.
                            </p>
                        </div>

                        <div class="grid grid-cols-3 gap-3 mb-3">

                            <div>
                                <label class="block text-[0.68rem] font-medium text-gray-700 mb-1">
                                    Last Name <span class="text-red-500">*</span>
                                </label>

                                <input type="text" name="last_name" placeholder="Enter last name"
                                    value="{{ old('last_name') }}"
                                    class="w-full rounded-md border border-gray-200 bg-white text-gray-800 text-[0.72rem] placeholder-gray-400 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#3b1735]/25 focus:border-[#3b1735] transition">
                            </div>

                            <div>
                                <label class="block text-[0.68rem] font-medium text-gray-700 mb-1">
                                    First Name <span class="text-red-500">*</span>
                                </label>

                                <input type="text" name="first_name" placeholder="Enter first name"
                                    value="{{ old('first_name') }}"
                                    class="w-full rounded-md border border-gray-200 bg-white text-gray-800 text-[0.72rem] placeholder-gray-400 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#3b1735]/25 focus:border-[#3b1735] transition">
                            </div>

                            <div>
                                <label class="block text-[0.68rem] font-medium text-gray-700 mb-1">
                                    Middle Initial
                                </label>

                                <input type="text" name="middle_initial" placeholder="Enter middle initial"
                                    value="{{ old('middle_initial') }}"
                                    class="w-full rounded-md border border-gray-200 bg-white text-gray-800 text-[0.72rem] placeholder-gray-400 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#3b1735]/25 focus:border-[#3b1735] transition">
                            </div>

                        </div>

                        <div class="grid grid-cols-2 gap-3 mb-3">

                            <div>
                                <label class="block text-[0.68rem] font-medium text-gray-700 mb-1">
                                    Sex <span class="text-red-500">*</span>
                                </label>

                                <div class="relative">
                                    <select name="sex"
                                        class="w-full rounded-md border border-gray-200 bg-white text-gray-500 text-[0.72rem] px-3 py-2 appearance-none focus:outline-none focus:ring-2 focus:ring-[#3b1735]/25 focus:border-[#3b1735] transition">
                                        <option value="" disabled selected>Select Sex</option>
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                        <option value="prefer_not_to_say">Prefer not to say</option>
                                    </select>

                                    <div class="pointer-events-none absolute inset-y-0 right-2.5 flex items-center">
                                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <label class="text-[0.68rem] font-medium text-gray-700">
                                        Email <span class="text-red-500">*</span>
                                    </label>

                                    <button type="button"
                                        @click="sendOtp(); showVerifyModal = true"
                                        x-show="email.length > 0 && !emailVerified"
                                        class="text-[0.65rem] font-semibold text-[#3b1735] hover:underline">
                                        Verify
                                    </button>

                                    <span x-show="emailVerified"
                                        class="text-[0.65rem] font-semibold text-green-600">
                                        ✓ Verified
                                    </span>
                                </div>

                                <input type="email" name="email" placeholder="Enter email address"
                                    x-model="email" :readonly="emailVerified"
                                    value="{{ old('email') }}"
                                    class="w-full rounded-md border border-gray-200 bg-white text-gray-800 text-[0.72rem] placeholder-gray-400 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#3b1735]/25 focus:border-[#3b1735] transition"
                                    :class="emailVerified ? 'bg-gray-50 cursor-not-allowed' : ''">
                            </div>

                        </div>

                        <div class="grid grid-cols-2 gap-3 mb-3">

                            <div>
                                <label class="block text-[0.68rem] font-medium text-gray-700 mb-1">
                                    Birthday <span class="text-red-500">*</span>
                                </label>

                                <input type="date" name="birthday"
                                    value="{{ old('birthday') }}"
                                    onchange="
                                        const b = new Date(this.value);
                                        const t = new Date();
                                        let age = t.getFullYear() - b.getFullYear();
                                        const m = t.getMonth() - b.getMonth();
                                        if (m < 0 || (m === 0 && t.getDate() < b.getDate())) age--;
                                        document.querySelector('[name=age]').value = isNaN(age) ? '' : age;
                                    "
                                    class="w-full rounded-md border border-gray-200 bg-white text-gray-500 text-[0.72rem] px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#3b1735]/25 focus:border-[#3b1735] transition">
                            </div>

                            <div>
                                <label class="block text-[0.68rem] font-medium text-gray-700 mb-1">
                                    Age <span class="text-red-500">*</span>
                                </label>

                                <input type="text" name="age" placeholder="Enter age"
                                    class="w-full rounded-md border border-gray-200 bg-white text-gray-800 text-[0.72rem] placeholder-gray-400 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#3b1735]/25 focus:border-[#3b1735] transition">
                            </div>

                        </div>

                        <div class="mb-3">
                            <label class="block text-[0.68rem] font-medium text-gray-700 mb-1">
                                ID Category <span class="text-red-500">*</span>
                            </label>

                            <select name="id_category" x-model="idCategory"
                                class="w-full rounded-md border border-gray-200 bg-white text-gray-500 text-[0.72rem] px-3 py-2 appearance-none focus:outline-none focus:ring-2 focus:ring-[#3b1735]/25 focus:border-[#3b1735] transition">
                                <option value="primary">Primary ID (1 ID)</option>
                                <option value="secondary">Secondary ID (2 IDs required)</option>
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-3 mb-5"
                            x-show="idCategory === 'primary'">

                            <div>
                                <label class="block text-[0.68rem] font-medium text-gray-700 mb-1">
                                    ID Type <span class="text-red-500">*</span>
                                </label>

                                <select name="id_type"
                                    :disabled="idCategory !== 'primary'"
                                    class="w-full rounded-md border border-gray-200 bg-white text-gray-500 text-[0.72rem] px-3 py-2 appearance-none focus:outline-none focus:ring-2 focus:ring-[#3b1735]/25 focus:border-[#3b1735] transition">
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
                                <label class="block text-[0.68rem] font-medium text-gray-700 mb-1">
                                    Upload ID <span class="text-red-500">*</span>
                                </label>

                                <label
                                    class="flex items-center justify-between w-full rounded-md border border-gray-200 bg-white text-gray-400 text-[0.72rem] px-3 py-2 cursor-pointer hover:bg-gray-50 transition">

                                    <span id="valid-id-label">Upload ID here</span>

                                    <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                    </svg>

                                    <input type="file"
                                        name="valid_id"
                                        id="primary-valid-id"
                                        class="hidden"
                                        accept="image/*,.pdf"
                                        :disabled="idCategory !== 'primary'"
                                        onchange="document.getElementById('valid-id-label').textContent = this.files[0]?.name || 'Upload ID here'">
                                </label>
                            </div>

                        </div>

                        <div class="grid grid-cols-2 gap-3 mb-5"
                            x-show="idCategory === 'secondary'"
                            x-cloak>

                            <div>
                                <label class="block text-[0.68rem] font-medium text-gray-700 mb-1">
                                    First Secondary ID Type <span class="text-red-500">*</span>
                                </label>

                                <select name="id_type_1"
                                    :disabled="idCategory !== 'secondary'"
                                    class="w-full rounded-md border border-gray-200 bg-white text-gray-500 text-[0.72rem] px-3 py-2 appearance-none focus:outline-none focus:ring-2 focus:ring-[#3b1735]/25 focus:border-[#3b1735] transition">
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
                                    class="flex items-center justify-between w-full rounded-md border border-gray-200 bg-white text-gray-400 text-[0.72rem] px-3 py-2 cursor-pointer hover:bg-gray-50 transition mt-2">

                                    <span id="valid-id-1-label">Upload ID here</span>

                                    <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                    </svg>

                                    <input type="file"
                                        name="valid_id"
                                        id="secondary-valid-id-1"
                                        class="hidden"
                                        accept="image/*,.pdf"
                                        :disabled="idCategory !== 'secondary'"
                                        onchange="document.getElementById('valid-id-1-label').textContent = this.files[0]?.name || 'Upload ID here'">
                                </label>
                            </div>

                            <div>
                                <label class="block text-[0.68rem] font-medium text-gray-700 mb-1">
                                    Second Secondary ID Type <span class="text-red-500">*</span>
                                </label>

                                <select name="id_type_2"
                                    :disabled="idCategory !== 'secondary'"
                                    class="w-full rounded-md border border-gray-200 bg-white text-gray-500 text-[0.72rem] px-3 py-2 appearance-none focus:outline-none focus:ring-2 focus:ring-[#3b1735]/25 focus:border-[#3b1735] transition">
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
                                    class="flex items-center justify-between w-full rounded-md border border-gray-200 bg-white text-gray-400 text-[0.72rem] px-3 py-2 cursor-pointer hover:bg-gray-50 transition mt-2">

                                    <span id="valid-id-2-label">Upload ID here</span>

                                    <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                    </svg>

                                    <input type="file"
                                        name="valid_id_2"
                                        id="secondary-valid-id-2"
                                        class="hidden"
                                        accept="image/*,.pdf"
                                        :disabled="idCategory !== 'secondary'"
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

                        <div class="grid grid-cols-2 gap-3 mb-1">

                            <div>
                                <label class="block text-[0.68rem] font-medium text-gray-700 mb-1">
                                    Password <span class="text-red-500">*</span>
                                </label>

                                <div class="relative">
                                    <input name="password"
                                        x-bind:type="showPassword ? 'text' : 'password'"
                                        placeholder="Create a password"
                                        class="w-full rounded-md border border-gray-200 bg-white text-gray-800 text-[0.72rem] placeholder-gray-400 px-3 py-2 pr-9 focus:outline-none focus:ring-2 focus:ring-[#3b1735]/25 focus:border-[#3b1735] transition">

                                    <button type="button"
                                        @click="showPassword = !showPassword"
                                        class="absolute inset-y-0 right-0 flex items-center justify-center w-9 text-gray-400 hover:text-gray-600 transition"
                                        tabindex="-1">

                                        <svg x-show="!showPassword"
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>

                                        <svg x-show="showPassword"
                                            x-cloak
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.956 9.956 0 012.293-3.95M6.47 6.47A9.953 9.953 0 0112 5c4.477 0 8.268 2.943 9.542 7a9.963 9.963 0 01-4.134 5.247M15 12a3 3 0 00-3-3m0 6a3 3 0 01-2.83-2M3 3l18 18" />
                                        </svg>

                                    </button>
                                </div>
                            </div>

                            <div>
                                <label class="block text-[0.68rem] font-medium text-gray-700 mb-1">
                                    Confirm Password <span class="text-red-500">*</span>
                                </label>

                                <div class="relative">
                                    <input name="password_confirmation"
                                        x-bind:type="showConfirmPassword ? 'text' : 'password'"
                                        placeholder="Confirm your password"
                                        class="w-full rounded-md border border-gray-200 bg-white text-gray-800 text-[0.72rem] placeholder-gray-400 px-3 py-2 pr-9 focus:outline-none focus:ring-2 focus:ring-[#3b1735]/25 focus:border-[#3b1735] transition">

                                    <button type="button"
                                        @click="showConfirmPassword = !showConfirmPassword"
                                        class="absolute inset-y-0 right-0 flex items-center justify-center w-9 text-gray-400 hover:text-gray-600 transition"
                                        tabindex="-1">

                                        <svg x-show="!showConfirmPassword"
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>

                                        <svg x-show="showConfirmPassword"
                                            x-cloak
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.956 9.956 0 012.293-3.95M6.47 6.47A9.953 9.953 0 0112 5c4.477 0 8.268 2.943 9.542 7a9.963 9.963 0 01-4.134 5.247M15 12a3 3 0 00-3-3m0 6a3 3 0 01-2.83-2M3 3l18 18" />
                                        </svg>

                                    </button>
                                </div>
                            </div>

                        </div>

                        <p class="text-[0.62rem] text-gray-400 mt-1.5 mb-6 font-normal">
                            Minimum 8 characters with letters and numbers.
                        </p>

                        <div class="flex justify-end">
                            <button type="button"
                                @click="if (validateStep1()) step = 2"
                                class="flex items-center gap-1.5 bg-[#3b1735] hover:bg-[#4d1f45] active:bg-[#2e1229] text-white text-[0.75rem] font-semibold rounded-lg px-5 py-2 transition-colors duration-150">
                                Next: Contact &amp; Address

                                <svg class="w-3.5 h-3.5" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="2.5" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                        </div>

                        <p x-show="step1Error"
                            x-text="step1Error"
                            class="text-red-500 text-[0.7rem] text-right mt-2">
                        </p>

                    </div>

                    <div x-show="step === 2" x-cloak>

                        <div class="mb-1">
                            <h2 class="text-[0.88rem] font-bold text-gray-900 mb-0.5">
                                Address
                            </h2>

                            <p class="text-[0.7rem] text-gray-500 font-normal mb-4">
                                Please provide your complete address.
                            </p>
                        </div>

                        <div class="grid grid-cols-3 gap-3 mb-3">

                            <div>
                                <label class="block text-[0.68rem] font-medium text-gray-700 mb-1">
                                    Province <span class="text-red-500">*</span>
                                </label>

                                <div class="relative">
                                    <select name="province" id="province-select"
                                        class="w-full rounded-md border border-gray-200 bg-white text-gray-500 text-[0.72rem] px-3 py-2 appearance-none focus:outline-none focus:ring-2 focus:ring-[#3b1735]/25 focus:border-[#3b1735] transition">
                                        <option value="" disabled selected>Select Province</option>
                                    </select>

                                    <div class="pointer-events-none absolute inset-y-0 right-2.5 flex items-center">
                                        <svg class="w-3.5 h-3.5 text-gray-400"
                                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-[0.68rem] font-medium text-gray-700 mb-1">
                                    Municipality / City <span class="text-red-500">*</span>
                                </label>

                                <div class="relative">
                                    <select name="municipality" id="municipality-select" disabled
                                        class="w-full rounded-md border border-gray-200 bg-white text-gray-500 text-[0.72rem] px-3 py-2 appearance-none focus:outline-none focus:ring-2 focus:ring-[#3b1735]/25 focus:border-[#3b1735] transition">
                                        <option value="" disabled selected>Select Municipality / City</option>
                                    </select>

                                    <div class="pointer-events-none absolute inset-y-0 right-2.5 flex items-center">
                                        <svg class="w-3.5 h-3.5 text-gray-400"
                                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-[0.68rem] font-medium text-gray-700 mb-1">
                                    Barangay <span class="text-red-500">*</span>
                                </label>

                                <div class="relative">
                                    <select name="barangay" id="barangay-select" disabled
                                        class="w-full rounded-md border border-gray-200 bg-white text-gray-500 text-[0.72rem] px-3 py-2 appearance-none focus:outline-none focus:ring-2 focus:ring-[#3b1735]/25 focus:border-[#3b1735] transition">
                                        <option value="" disabled selected>Select Barangay</option>
                                    </select>

                                    <div class="pointer-events-none absolute inset-y-0 right-2.5 flex items-center">
                                        <svg class="w-3.5 h-3.5 text-gray-400"
                                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <div class="grid grid-cols-4 gap-3 mb-8">

                            <div>
                                <label class="block text-[0.68rem] font-medium text-gray-700 mb-1">
                                    House No. <span class="text-red-500">*</span>
                                </label>

                                <input type="text" name="house_no"
                                    placeholder="House/Unit No."
                                    class="w-full rounded-md border border-gray-200 bg-white text-gray-800 text-[0.72rem] placeholder-gray-400 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#3b1735]/25 focus:border-[#3b1735] transition">
                            </div>

                            <div>
                                <label class="block text-[0.68rem] font-medium text-gray-700 mb-1">
                                    Street <span class="text-red-500">*</span>
                                </label>

                                <input type="text" name="street"
                                    placeholder="Street, building, subdivision, etc."
                                    class="w-full rounded-md border border-gray-200 bg-white text-gray-800 text-[0.72rem] placeholder-gray-400 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#3b1735]/25 focus:border-[#3b1735] transition">
                            </div>

                            <div>
                                <label class="block text-[0.68rem] font-medium text-gray-700 mb-1">
                                    Zip Code <span class="text-red-500">*</span>
                                </label>

                                <input type="text" name="zip_code"
                                    placeholder="Enter zip code"
                                    class="w-full rounded-md border border-gray-200 bg-white text-gray-800 text-[0.72rem] placeholder-gray-400 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#3b1735]/25 focus:border-[#3b1735] transition">
                            </div>

                            <div>
                                <label class="block text-[0.68rem] font-medium text-gray-700 mb-1">
                                    Contact Number <span class="text-red-500">*</span>
                                </label>

                                <input type="text" name="contact_number"
                                    placeholder="09XX XXX XXXX"
                                    class="w-full rounded-md border border-gray-200 bg-white text-gray-800 text-[0.72rem] placeholder-gray-400 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#3b1735]/25 focus:border-[#3b1735] transition">
                            </div>

                        </div>

                        <div class="flex justify-end gap-3">

                            <button type="button"
                                @click="step = 1"
                                class="text-[0.75rem] font-medium text-gray-700 border border-gray-300 rounded-lg px-5 py-2 hover:bg-gray-50 transition">
                                Back
                            </button>

                            <button type="button"
                                @click="if (validateStep2()) step = 3"
                                class="flex items-center gap-1.5 bg-[#3b1735] hover:bg-[#4d1f45] active:bg-[#2e1229] text-white text-[0.75rem] font-semibold rounded-lg px-5 py-2 transition-colors duration-150">
                                Next: Review &amp; Submit

                                <svg class="w-3.5 h-3.5" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="2.5" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>

                        </div>

                        <p x-show="step2Error"
                            x-text="step2Error"
                            class="text-red-500 text-[0.7rem] text-right mt-2">
                        </p>

                    </div>

                    <div x-show="step === 3" x-cloak>

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

                                        <svg xmlns="http://www.w3.org/2000/svg"
                                            class="w-5 h-5 text-[#3b1735]"
                                            fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>

                                    </div>

                                    <span class="text-[0.82rem] font-bold text-gray-900">
                                        User Information
                                    </span>
                                </div>

                                <button type="button"
                                    @click="step = 1"
                                    class="text-[0.7rem] font-medium text-gray-700 border border-gray-300 rounded-md px-4 py-1 hover:bg-gray-50 transition">
                                    Edit
                                </button>

                            </div>

                            <div class="grid grid-cols-4 gap-x-6 gap-y-4">

                                <div>
                                    <p class="text-[0.62rem] text-gray-400 mb-0.5">
                                        Full Name
                                    </p>

                                    <p class="text-[0.72rem] font-semibold text-gray-800"
                                        x-text="
                                            ((document.querySelector('[name=first_name]')?.value || '') + ' ' +
                                            (document.querySelector('[name=middle_initial]')?.value
                                                ? document.querySelector('[name=middle_initial]').value + '. '
                                                : '') +
                                            (document.querySelector('[name=last_name]')?.value || '')) || '—'
                                        ">
                                    </p>
                                </div>

                                <div>
                                    <p class="text-[0.62rem] text-gray-400 mb-0.5">
                                        Phone Number
                                    </p>

                                    <p class="text-[0.72rem] font-semibold text-gray-800"
                                        x-text="document.querySelector('[name=contact_number]')?.value || '—'">
                                    </p>
                                </div>

                                <div>
                                    <p class="text-[0.62rem] text-gray-400 mb-0.5">
                                        Province
                                    </p>

                                    <p class="text-[0.72rem] font-semibold text-gray-800"
                                        x-text="document.querySelector('[name=province]')?.options[document.querySelector('[name=province]')?.selectedIndex]?.text || '—'">
                                    </p>
                                </div>

                                <div>
                                    <p class="text-[0.62rem] text-gray-400 mb-0.5">
                                        Street / House No.
                                    </p>

                                    <p class="text-[0.72rem] font-semibold text-gray-800"
                                        x-text="
                                            ((document.querySelector('[name=house_no]')?.value || '') + ' ' +
                                            (document.querySelector('[name=street]')?.value || '')) || '—'
                                        ">
                                    </p>
                                </div>

                                <div>
                                    <p class="text-[0.62rem] text-gray-400 mb-0.5">
                                        Sex
                                    </p>

                                    <p class="text-[0.72rem] font-semibold text-gray-800 capitalize"
                                        x-text="document.querySelector('[name=sex]')?.value || '—'">
                                    </p>
                                </div>

                                <div>
                                    <p class="text-[0.62rem] text-gray-400 mb-0.5">
                                        Birthday
                                    </p>

                                    <p class="text-[0.72rem] font-semibold text-gray-800"
                                        x-text="document.querySelector('[name=birthday]')?.value || '—'">
                                    </p>
                                </div>

                                <div>
                                    <p class="text-[0.62rem] text-gray-400 mb-0.5">
                                        Municipality
                                    </p>

                                    <p class="text-[0.72rem] font-semibold text-gray-800"
                                        x-text="document.querySelector('[name=municipality]')?.options[document.querySelector('[name=municipality]')?.selectedIndex]?.text || '—'">
                                    </p>
                                </div>

                                <div>
                                    <p class="text-[0.62rem] text-gray-400 mb-0.5">
                                        Zip Code
                                    </p>

                                    <p class="text-[0.72rem] font-semibold text-gray-800"
                                        x-text="document.querySelector('[name=zip_code]')?.value || '—'">
                                    </p>
                                </div>

                                <div>
                                    <p class="text-[0.62rem] text-gray-400 mb-0.5">
                                        Email
                                    </p>

                                    <p class="text-[0.72rem] font-semibold text-gray-800 break-all"
                                        x-text="document.querySelector('[name=email]')?.value || '—'">
                                    </p>
                                </div>

                                <div>
                                    <p class="text-[0.62rem] text-gray-400 mb-0.5">
                                        Age
                                    </p>

                                    <p class="text-[0.72rem] font-semibold text-gray-800"
                                        x-text="document.querySelector('[name=age]')?.value || '—'">
                                    </p>
                                </div>

                                <div>
                                    <p class="text-[0.62rem] text-gray-400 mb-0.5">
                                        Barangay
                                    </p>

                                    <p class="text-[0.72rem] font-semibold text-gray-800"
                                        x-text="document.querySelector('[name=barangay]')?.options[document.querySelector('[name=barangay]')?.selectedIndex]?.text || '—'">
                                    </p>
                                </div>

                                <div>
                                    <p class="text-[0.62rem] text-gray-400 mb-0.5">
                                        Valid ID
                                    </p>

                                    <div class="flex items-center gap-1.5 mt-0.5">
                                        <svg class="w-3.5 h-3.5 text-gray-400 shrink-0"
                                            fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>

                                        <span class="text-[0.68rem] text-gray-600 truncate"
                                            id="review-valid-id">
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

                                    <p class="text-[0.68rem] text-gray-600 leading-relaxed mb-3">
                                        By submitting this registration, you confirm that all information provided
                                        is
                                        true and correct.<br>
                                        Our team will review your application and you will be notified via email
                                        once
                                        your account is approved.
                                    </p>

                                    <label class="flex items-center gap-2 cursor-pointer">

                                        <input type="checkbox"
                                            name="agree_terms"
                                            x-model="agreeTerms"
                                            class="w-3.5 h-3.5 rounded border-gray-400 text-[#3b1735] focus:ring-[#3b1735]">

                                        <span class="text-[0.68rem] text-gray-600">
                                            I agree to the
                                            <a href="#"
                                                class="text-[#3b1735] font-semibold hover:underline">
                                                Terms and Conditions
                                            </a>
                                            and
                                            <a href="#"
                                                class="text-[#3b1735] font-semibold hover:underline">
                                                Privacy Policy
                                            </a>.
                                        </span>

                                    </label>

                                </div>

                            </div>
                        </div>

                        <div class="flex justify-end gap-3">

                            <button type="button"
                                @click="step = 2"
                                class="text-[0.75rem] font-medium text-gray-700 border border-gray-300 rounded-lg px-5 py-2 hover:bg-gray-50 transition">
                                Back
                            </button>

                            <button type="submit"
                                @click="if (!validateStep3()) $event.preventDefault()"
                                class="bg-[#3b1735] hover:bg-[#4d1f45] text-white text-[0.75rem] font-semibold rounded-lg px-6 py-2 transition-colors duration-150">
                                Submit
                            </button>

                        </div>

                        <p x-show="step3Error"
                            x-text="step3Error"
                            class="text-red-500 text-[0.7rem] text-right mt-2">
                        </p>

                    </div>

                </form>
            </div>
        </div>

        <div x-show="showVerifyModal"
            style="display:none;"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
            @keydown.escape.window="showVerifyModal = false">

            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-[420px] mx-4 px-8 py-10 text-center"
                @click.outside="showVerifyModal = false">

                <div class="flex justify-center mb-6">
                    <div class="w-16 h-16 rounded-full bg-[#ede6f0] flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="w-8 h-8 text-[#3b1735]"
                            viewBox="0 0 24 24"
                            fill="currentColor">
                            <path
                                d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 2-8 5-8-5h16zm0 12H4V8l8 5 8-5v10z" />
                            <circle cx="17" cy="17" r="5" fill="#3b1735" />
                            <path d="M15.5 17.5l1 1 2.5-2.5"
                                stroke="white" stroke-width="1.2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                fill="none" />
                        </svg>
                    </div>
                </div>

                <h2 class="text-[1.3rem] font-bold text-gray-900 mb-2">
                    Verify Your Email
                </h2>

                <p class="text-[0.82rem] text-gray-400 font-normal mb-7 leading-relaxed">
                    We've sent a 6-digit code to your email.<br>
                    <span class="text-[0.7rem] text-gray-400">
                        Can't find it? Check your Spam or Junk folder.
                    </span>
                </p>

                <div class="flex justify-center gap-3 mb-6"
                    x-data="otpInput()"
                    @paste.prevent="handlePaste($event)">

                    <template x-for="(digit, index) in digits" :key="index">
                        <input type="text"
                            maxlength="1"
                            inputmode="numeric"
                            pattern="[0-9]"
                            x-model="digits[index]"
                            :id="'otp-' + index"
                            @input="onInput(index, $event)"
                            @keydown.backspace="onBackspace(index, $event)"
                            @keydown.left="focusAt(index - 1)"
                            @keydown.right="focusAt(index + 1)"
                            class="w-12 h-14 rounded-xl border border-gray-300 text-center text-[1.1rem] font-semibold text-gray-900 focus:outline-none focus:border-[#3b1735] focus:ring-2 focus:ring-[#3b1735]/20 transition">
                    </template>

                </div>

                <div class="flex justify-center mb-7">
                    <button type="button"
                        class="flex items-center gap-1.5 text-[0.78rem] font-semibold text-[#c0392b] hover:underline">

                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="w-4 h-4" fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2">
                            <path stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M4 4v5h.582M20 20v-5h-.581M4.582 9A8 8 0 0119.418 15M19.418 15A8 8 0 014.582 9" />
                        </svg>

                        Resend Code
                    </button>
                </div>

                <button type="button"
                    @click="verifyOtp()"
                    class="w-full bg-[#3b1735] hover:bg-[#4d1f45] text-white text-[0.9rem] font-bold rounded-full py-3.5 transition-colors duration-150">
                    Verify Email
                </button>

                <p x-show="otpError"
                    x-text="otpError"
                    class="text-red-500 text-[0.7rem] mt-3">
                </p>

            </div>
        </div>

        <div x-show="showSuccessModal"
            style="display:none;"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">

            <div
                class="bg-white rounded-3xl shadow-2xl w-full max-w-[400px] mx-4 px-8 py-10 text-center border border-[#e8dff0]">

                <div class="flex justify-center mb-6">
                    <img src="{{ asset('assets/icons/seller-compliance/product-approved-element.svg') }}"
                        alt="Registration approved"
                        class="w-36 h-36 object-contain">
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

    <script>
        document.addEventListener('alpine:init', () => {

            Alpine.effect(() => {
                const primaryInput = document.getElementById('primary-valid-id');
                const secondaryInput = document.getElementById('secondary-valid-id-1');
                const reviewEl = document.getElementById('review-valid-id');

                if (!reviewEl) return;

                if (primaryInput && primaryInput.files.length) {
                    reviewEl.textContent = primaryInput.files[0].name;
                } else if (secondaryInput && secondaryInput.files.length) {
                    reviewEl.textContent = secondaryInput.files[0].name;
                } else {
                    reviewEl.textContent = '—';
                }
            });

            Alpine.data('otpInput', () => ({
                digits: ['', '', '', '', '', ''],

                onInput(index, event) {
                    const val = event.target.value.replace(/\D/g, '');

                    this.digits[index] = val ? val[0] : '';

                    if (val && index < 5) {
                        this.focusAt(index + 1);
                    }
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
                    const text = (event.clipboardData || window.clipboardData)
                        .getData('text')
                        .replace(/\D/g, '')
                        .slice(0, 6);

                    text.split('').forEach((char, i) => {
                        this.digits[i] = char;
                    });

                    this.focusAt(Math.min(text.length, 5));
                }
            }));
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {

            const provinceSelect = document.getElementById('province-select');
            const municipalitySelect = document.getElementById('municipality-select');
            const barangaySelect = document.getElementById('barangay-select');

            fetch('https://psgc.gitlab.io/api/provinces/')
                .then(res => res.json())
                .then(provinces => {

                    provinces.sort((a, b) => a.name.localeCompare(b.name));

                    provinces.forEach(p => {
                        const opt = document.createElement('option');

                        opt.value = p.name;
                        opt.textContent = p.name;
                        opt.dataset.code = p.code;

                        provinceSelect.appendChild(opt);
                    });
                });

            provinceSelect.addEventListener('change', () => {

                const code =
                    provinceSelect.options[provinceSelect.selectedIndex].dataset.code;

                municipalitySelect.innerHTML =
                    '<option value="" disabled selected>Select Municipality / City</option>';

                barangaySelect.innerHTML =
                    '<option value="" disabled selected>Select Barangay</option>';

                barangaySelect.disabled = true;
                municipalitySelect.disabled = true;

                fetch(`https://psgc.gitlab.io/api/provinces/${code}/cities-municipalities/`)
                    .then(res => res.json())
                    .then(cities => {

                        cities.sort((a, b) => a.name.localeCompare(b.name));

                        cities.forEach(c => {

                            const opt = document.createElement('option');

                            opt.value = c.name;
                            opt.textContent = c.name;
                            opt.dataset.code = c.code;

                            municipalitySelect.appendChild(opt);
                        });

                        municipalitySelect.disabled = false;
                    });
            });

            municipalitySelect.addEventListener('change', () => {

                const code =
                    municipalitySelect.options[municipalitySelect.selectedIndex].dataset.code;

                barangaySelect.innerHTML =
                    '<option value="" disabled selected>Select Barangay</option>';

                fetch(`https://psgc.gitlab.io/api/cities-municipalities/${code}/barangays/`)
                    .then(res => res.json())
                    .then(barangays => {

                        barangays.sort((a, b) => a.name.localeCompare(b.name));

                        barangays.forEach(b => {

                            const opt = document.createElement('option');

                            opt.value = b.name;
                            opt.textContent = b.name;

                            barangaySelect.appendChild(opt);
                        });

                        barangaySelect.disabled = false;
                    });
            });
        });
    </script>

</body>

</html>