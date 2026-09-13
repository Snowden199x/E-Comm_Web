<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create Your Account - Vendo</title>
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
            width: 420px;
            height: 420px;
            top: -120px;
            left: -100px;
            background: radial-gradient(circle at 30% 30%, rgba(201, 147, 58, 0.55), rgba(201, 147, 58, 0) 70%);
            animation: blobMove1 16s ease-in-out infinite;
        }

        .blob-2 {
            width: 380px;
            height: 380px;
            bottom: -140px;
            right: -120px;
            background: radial-gradient(circle at 60% 60%, rgba(168, 101, 201, 0.45), rgba(168, 101, 201, 0) 70%);
            animation: blobMove2 20s ease-in-out infinite;
        }

        .blob-3 {
            width: 300px;
            height: 300px;
            bottom: 20%;
            left: -80px;
            background: radial-gradient(circle at 50% 50%, rgba(255, 255, 255, 0.12), rgba(255, 255, 255, 0) 70%);
            animation: blobMove3 13s ease-in-out infinite;
        }

        @keyframes blobMove1 {

            0%,
            100% {
                transform: translate(0, 0) scale(1);
            }

            33% {
                transform: translate(50px, 40px) scale(1.15);
            }

            66% {
                transform: translate(-30px, 20px) scale(0.92);
            }
        }

        @keyframes blobMove2 {

            0%,
            100% {
                transform: translate(0, 0) scale(1);
            }

            40% {
                transform: translate(-40px, -35px) scale(1.1);
            }

            70% {
                transform: translate(25px, -15px) scale(0.95);
            }
        }

        @keyframes blobMove3 {

            0%,
            100% {
                transform: translate(0, 0) scale(1);
                opacity: 0.5;
            }

            50% {
                transform: translate(35px, -25px) scale(1.2);
                opacity: 0.9;
            }
        }

        .aurora-sweep {
            position: absolute;
            inset: -50%;
            background: conic-gradient(from 0deg, transparent 0%, rgba(201, 147, 58, 0.10) 15%, transparent 30%, transparent 60%, rgba(255, 255, 255, 0.06) 75%, transparent 90%);
            animation: spinSlow 28s linear infinite;
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
    </style>
</head>

<body class="antialiased bg-[#fdfcf8]">

    <div class="min-h-screen flex flex-col lg:flex-row transition-opacity duration-300" x-data="{ choice: 'seller', navigating: false }"
        :class="navigating ? 'opacity-0' : 'opacity-100'">

        {{-- LEFT PANEL --}}
        <div
            class="hidden lg:flex lg:w-[45%] relative overflow-hidden bg-[#3b1735] flex-col justify-center items-start px-16 py-20">

            {{-- animated ambient background --}}
            <div class="absolute inset-0 overflow-hidden">
                <div class="aurora-sweep"></div>
                <div class="blob blob-1"></div>
                <div class="blob blob-2"></div>
                <div class="blob blob-3"></div>
            </div>

            <div class="relative z-10 max-w-[420px] fade-in-up" style="animation-delay: .05s">
                <img src="{{ asset('assets/branding/log-in-logo.svg') }}" alt="Vendo" class="w-[260px] mb-16">

                <h1 class="text-[2.1rem] font-bold text-white leading-[1.15] mb-4 tracking-tight">
                    Everything you need,<br>
                    <span class="text-[#c9933a]">All in one place.</span>
                </h1>
                <p class="text-[0.95rem] font-light text-white/80 leading-relaxed max-w-[380px]">
                    Your trusted marketplace for discovering products, growing your store, and getting orders to your
                    door.
                </p>
            </div>
        </div>

        {{-- RIGHT PANEL --}}
        <div class="w-full lg:w-[55%] flex items-center justify-center bg-[#fdfcf8] px-6 py-12 sm:px-10">
            <div class="w-full max-w-[500px]">

                <button type="button" onclick="history.back()"
                    class="fade-in-up inline-flex text-gray-400 hover:text-[#3b1735] mb-3 transition-colors duration-200"
                    style="animation-delay: .05s">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>

                <h2 class="fade-in-up text-[1.75rem] font-bold text-gray-900 tracking-tight leading-tight mb-2"
                    style="animation-delay: .1s">
                    Create Your Account
                </h2>
                <p class="fade-in-up text-[0.9rem] text-gray-500 mb-10" style="animation-delay: .15s">
                    Welcome to Vendo. Your journey starts here.
                </p>

                <h3 class="fade-in-up text-[1.15rem] font-bold text-gray-900 mb-1" style="animation-delay: .2s">
                    Sign up as
                </h3>
                <p class="fade-in-up text-[0.85rem] text-gray-500 mb-6" style="animation-delay: .25s">
                    Choose how you want to get started
                </p>

                <div class="grid grid-cols-2 gap-6 mb-10">

                    {{-- Seller card --}}
                    <button type="button" @click="choice = 'seller'"
                        class="fade-in-up group relative text-center flex flex-col items-center rounded-2xl border-2 p-6 transition-all duration-300 ease-out hover:-translate-y-1"
                        style="animation-delay: .3s"
                        :class="choice === 'seller'
                            ?
                            'border-[#3b1735] bg-[#f6f1f8] shadow-lg' :
                            'border-gray-200 bg-white hover:border-gray-300 hover:shadow-md'">

                        {{-- selected check badge --}}
                        <div x-show="choice === 'seller'" x-cloak x-transition:enter="transition ease-out duration-300"
                            x-transition:enter-start="opacity-0 scale-50" x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-50"
                            class="absolute top-3 right-3 w-5 h-5 rounded-full bg-[#3b1735] flex items-center justify-center">
                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                stroke-width="3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>

                        <div
                            class="w-16 h-16 rounded-full flex items-center justify-center mb-4 transition-transform duration-300 group-hover:scale-110 bg-[#ece3f1]">
                            <img src="{{ asset('assets/icons/registration/sellers-registrations-icon.svg') }}"
                                alt="" class="w-8 h-8">
                        </div>
                        <p class="font-bold text-[#3b1735] text-[1rem] mb-2">Seller</p>
                        <p class="text-[0.8rem] text-gray-500 leading-relaxed">
                            Create a seller account to list products, manage orders, and grow your business.
                        </p>
                    </button>

                    {{-- Buyer card --}}
                    <button type="button" @click="choice = 'buyer'"
                        class="fade-in-up group relative text-center flex flex-col items-center rounded-2xl border-2 p-6 transition-all duration-300 ease-out hover:-translate-y-1"
                        style="animation-delay: .35s"
                        :class="choice === 'buyer'
                            ?
                            'border-[#3b1735] bg-[#f6f1f8] shadow-lg' :
                            'border-gray-200 bg-white hover:border-gray-300 hover:shadow-md'">

                        <div x-show="choice === 'buyer'" x-cloak x-transition:enter="transition ease-out duration-300"
                            x-transition:enter-start="opacity-0 scale-50" x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-50"
                            class="absolute top-3 right-3 w-5 h-5 rounded-full bg-[#3b1735] flex items-center justify-center">
                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                stroke-width="3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>

                        <div
                            class="w-16 h-16 rounded-full flex items-center justify-center mb-4 transition-transform duration-300 group-hover:scale-110 bg-[#f3e7cf]">
                            <img src="{{ asset('assets/icons/registration/buyers-registrations-icon.svg') }}"
                                alt="" class="w-8 h-8">
                        </div>
                        <p class="font-bold text-gray-800 text-[1rem] mb-2">Buyer</p>
                        <p class="text-[0.8rem] text-gray-500 leading-relaxed">
                            Create a buyer account to discover products, place orders, and enjoy great deals.
                        </p>
                    </button>

                </div>

                <button type="button"
                    class="fade-in-up w-full text-white text-[0.9rem] font-semibold rounded-full py-3.5 transition-all duration-300 ease-out hover:shadow-lg hover:scale-[1.02] active:scale-[0.97]"
                    style="animation-delay: .4s"
                    :class="choice ? 'bg-[#3b1735] hover:bg-[#4d1f45]' : 'bg-gray-300 cursor-not-allowed'"
                    :disabled="!choice"
                    @click="
                        navigating = true;
                        const target = choice === 'seller' ? '{{ route('seller.register') }}' : '{{ route('buyer.register') }}';
                        setTimeout(() => window.location.href = target, 280);
                    ">
                    Continue
                </button>

                <p class="fade-in-up text-center text-[0.78rem] text-gray-500 mt-6 font-normal"
                    style="animation-delay: .45s">
                    Already have an account?
                    <a href="{{ route('login') }}"
                        class="text-[#3b1735] font-semibold hover:underline transition-colors">Log in</a>
                </p>

            </div>
        </div>

    </div>

</body>

</html>
