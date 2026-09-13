<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Logistics Center Dashboard - Vendo</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>* { font-family: 'Poppins', sans-serif; } [x-cloak] { display: none !important; }</style>
</head>
<body class="antialiased bg-[#faf6f0] min-h-screen">

    <header class="bg-white border-b border-gray-100 sticky top-0 z-20">
        <div class="max-w-6xl mx-auto px-6 h-[68px] flex items-center justify-between">
            <div class="flex items-center gap-3">
                <img src="{{ asset('assets/branding/vendo-logo.svg') }}" alt="Vendo" class="h-7">
                <span class="h-5 w-px bg-gray-200"></span>
                <span class="text-[0.85rem] font-semibold text-gray-600">Logistics Center Portal</span>
            </div>
            <div class="flex items-center gap-4">
                <div class="text-right hidden sm:block">
                    <p class="text-[0.85rem] font-semibold text-gray-900">{{ $center->business_name ?? 'Your Center' }}</p>
                    <p class="text-[0.72rem] text-gray-400">{{ auth()->user()->email }}</p>
                </div>
                <form method="POST" action="{{ route('logistics.logout') }}">
                    @csrf
                    <button type="submit" class="text-[0.8rem] font-semibold text-gray-500 border border-gray-300 rounded-md px-3.5 py-1.5 hover:bg-gray-50 transition">
                        Logout
                    </button>
                </form>
            </div>
        </div>
    </header>

    <main class="max-w-6xl mx-auto px-6 py-8">

        <div class="mb-6">
            <h1 class="text-[1.5rem] font-bold text-gray-900">Rider Applications</h1>
            <p class="text-gray-500 text-[0.9rem]">Review and decide on riders who've applied to ride under your center.</p>
        </div>

        {{-- Confirmation banner --}}
        @if (session('confirmation'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                 x-transition
                 class="mb-6 rounded-xl px-4 py-3 flex items-center justify-between
                    {{ session('confirmation') === 'approved' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-600 border border-red-200' }}">
                <span class="text-[0.85rem] font-medium">
                    @if (session('confirmation') === 'approved')
                        Rider approved. They can now log in to their account.
                    @else
                        Rider application rejected.
                    @endif
                </span>
                <button @click="show = false" class="text-current opacity-60 hover:opacity-100">&times;</button>
            </div>
        @endif

        {{-- Stat cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
            <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm">
                <p class="text-[0.78rem] text-gray-500 mb-1">Pending Applications</p>
                <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['pending']) }}</p>
            </div>
            <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm">
                <p class="text-[0.78rem] text-gray-500 mb-1">Approved Riders</p>
                <p class="text-2xl font-bold text-green-600">{{ number_format($stats['approved']) }}</p>
            </div>
            <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm">
                <p class="text-[0.78rem] text-gray-500 mb-1">Rejected Applications</p>
                <p class="text-2xl font-bold text-red-500">{{ number_format($stats['rejected']) }}</p>
            </div>
        </div>

        {{-- Pending riders --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="font-bold text-gray-900">Pending Riders</h2>
            </div>

            @forelse ($riders as $rider)
                <div x-data="{ open: false, rejectOpen: false }" class="border-b border-gray-100 last:border-0">

                    <div class="px-5 py-4 flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-4">
                        <div class="flex items-center gap-3 flex-1 min-w-0">
                            <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center text-sm font-semibold text-gray-600 shrink-0">
                                {{ strtoupper(substr($rider->first_name, 0, 1)) }}
                            </div>
                            <div class="min-w-0">
                                <p class="font-semibold text-gray-900 truncate">{{ $rider->first_name }} {{ $rider->last_name }}</p>
                                <p class="text-[0.78rem] text-gray-500 truncate">{{ $rider->user->email }} &middot; {{ $rider->user->phone_number ?? '—' }}</p>
                            </div>
                        </div>

                        <div class="text-[0.8rem] text-gray-600 sm:w-40">
                            {{ $rider->vehicle_type }} &middot; {{ $rider->plate_number }}
                        </div>

                        <div class="text-[0.78rem] text-gray-400 sm:w-32">
                            Applied {{ $rider->created_at->format('M d, Y') }}
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            <button type="button" @click="open = !open"
                                class="text-[0.8rem] font-medium text-gray-600 hover:text-[#3b1735] transition px-2">
                                <span x-text="open ? 'Hide details' : 'View details'"></span>
                            </button>

                            <form method="POST" action="{{ route('logistics.riders.approve', $rider) }}">
                                @csrf
                                <button type="submit"
                                    class="px-4 py-1.5 rounded-lg bg-[#3b1735] text-white text-[0.8rem] font-semibold hover:bg-[#4d1f45] transition">
                                    Approve
                                </button>
                            </form>

                            <button type="button" @click="rejectOpen = true"
                                class="px-4 py-1.5 rounded-lg border border-red-300 text-red-600 text-[0.8rem] font-semibold hover:bg-red-50 transition">
                                Reject
                            </button>
                        </div>
                    </div>

                    {{-- Expandable details --}}
                    <div x-show="open" x-cloak
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 -translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         class="px-5 pb-5">
                        <div class="bg-[#faf6f0] rounded-xl p-4 grid grid-cols-1 sm:grid-cols-2 gap-4 text-[0.82rem]">
                            <div>
                                <p class="text-gray-400 mb-0.5">Address</p>
                                <p class="text-gray-800 font-medium">
                                    {{ $rider->street }}, {{ $rider->barangay }}, {{ $rider->municipality }}, {{ $rider->province }} {{ $rider->zip_code }}
                                </p>
                            </div>
                            <div>
                                <p class="text-gray-400 mb-0.5">Documents</p>
                                <div class="flex flex-wrap gap-2 mt-1">
                                    @if ($rider->valid_id_path)
                                        <a href="{{ Storage::url($rider->valid_id_path) }}" target="_blank" class="text-xs px-3 py-1.5 rounded-full border border-gray-300 text-gray-600 hover:bg-white transition">Valid ID</a>
                                    @endif
                                    @if ($rider->drivers_license_path)
                                        <a href="{{ Storage::url($rider->drivers_license_path) }}" target="_blank" class="text-xs px-3 py-1.5 rounded-full border border-gray-300 text-gray-600 hover:bg-white transition">Driver's License</a>
                                    @endif
                                    @if ($rider->or_cr_path)
                                        <a href="{{ Storage::url($rider->or_cr_path) }}" target="_blank" class="text-xs px-3 py-1.5 rounded-full border border-gray-300 text-gray-600 hover:bg-white transition">OR / CR</a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Reject modal --}}
                    <div x-show="rejectOpen" x-cloak
                         class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4"
                         @click.self="rejectOpen = false">
                        <div class="bg-white rounded-2xl w-full max-w-md" @click.stop x-data="{ reason: '', details: '' }">
                            <form method="POST" action="{{ route('logistics.riders.reject', $rider) }}" class="p-6">
                                @csrf
                                <div class="flex justify-end">
                                    <button type="button" @click="rejectOpen = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
                                </div>
                                <div class="text-center mb-5">
                                    <h3 class="font-bold text-lg text-gray-900">Reject Application</h3>
                                    <p class="text-sm text-gray-500">This rider will not be approved to ride under your center.</p>
                                </div>

                                <p class="text-sm font-medium text-gray-900 mb-2">Reason for Rejection<span class="text-red-500">*</span></p>
                                <div class="space-y-2 mb-4">
                                    @foreach ([
                                        'Incomplete Application',
                                        'Invalid Identification',
                                        'Vehicle Documents Invalid',
                                        'Information Mismatch',
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
                                    <button type="button" @click="rejectOpen = false"
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

                </div>
            @empty
                <div class="px-5 py-12 text-center text-gray-400">
                    No pending rider applications right now.
                </div>
            @endforelse

            @if ($riders->hasPages())
                <div class="px-5 py-4 border-t border-gray-100">
                    {{ $riders->links() }}
                </div>
            @endif
        </div>

    </main>

</body>
</html>