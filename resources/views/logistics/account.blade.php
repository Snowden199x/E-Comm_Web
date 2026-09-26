<x-logistics.layout title="Account Management">
    <div class="lg-page mx-auto max-w-3xl px-4 py-8 sm:px-6">
        <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
            <div><h2 class="text-2xl font-bold">Account Management</h2><p class="mt-1 text-sm text-gray-500">Your registered details and platform policies.</p></div>
            <a href="#accountPolicies" class="rounded-lg border border-[#d8c8dc] bg-white px-3 py-2 text-sm font-medium text-[#5c2864] hover:bg-[#f8f1f9]">Policies</a>
        </div>
        <section class="rounded-2xl border border-gray-100 bg-white p-5 sm:p-6" aria-labelledby="logisticsProfileTitle">
            <div class="flex items-center gap-4 border-b border-gray-100 pb-5">
                @if($user->profile_picture)<img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($user->profile_picture) }}" alt="" class="h-14 w-14 rounded-full object-cover">
                @else<span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-[#f2eaf4] text-xl font-semibold text-[#5c2864]" aria-hidden="true">{{ mb_strtoupper(mb_substr($center?->business_name ?: $user->name, 0, 1)) }}</span>@endif
                <div class="min-w-0"><h2 id="logisticsProfileTitle" class="break-words text-lg font-semibold">{{ $center?->business_name ?: $user->name }}</h2><p class="mt-1 text-sm text-gray-500">Logistics center</p></div>
            </div>
            <dl class="mt-5 grid gap-5 text-sm sm:grid-cols-2">
                <div><dt class="text-gray-500">Registered name</dt><dd class="mt-1 break-words font-medium">{{ $user->name }}</dd></div>
                <div><dt class="text-gray-500">Email</dt><dd class="mt-1 break-words font-medium">{{ $user->email }}</dd></div>
                <div><dt class="text-gray-500">Contact number</dt><dd class="mt-1 font-medium">{{ $user->phone_number ?: 'Not provided' }}</dd></div>
                <div><dt class="text-gray-500">Account status</dt><dd class="mt-1 font-medium">{{ ucfirst($user->status) }}</dd></div>
                <div class="sm:col-span-2"><dt class="text-gray-500">Registered address</dt><dd class="mt-1 break-words font-medium">{{ collect([$center?->house_no, $center?->street, $center?->barangay, $center?->municipality, $center?->province, $center?->zip_code])->filter()->implode(', ') ?: 'Not provided' }}</dd></div>
            </dl>
        </section>
        <x-account-policies :policies="$policies" />
    </div>
</x-logistics.layout>
