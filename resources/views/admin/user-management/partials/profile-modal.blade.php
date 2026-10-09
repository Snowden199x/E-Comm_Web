{{--
    Full profile dialog for one user (modal only, never a separate page).
    Needs $user and the parent scope { openId, suspendId, deactivateId, activateId }.
    Reuses the ID preview from the Registrations partials; files open in the shared
    document viewer popup (admin/partials/document-viewer.blade.php).
--}}
@php
    $details = $user->sellerDetail ?? $user->buyerDetail ?? $user->logisticsCenterDetail;
    $role = $user->role;
    $roleLabel = ucwords(str_replace('_', ' ', $role));
    $hasBusiness = in_array($role, ['seller', 'logistics_center'], true);
    // Two IDs (Buyer, secondary ID option): the ID block goes full width under the rows so both are readable.
    $hasSecondId = $role === 'buyer' && $details && ! empty($details->valid_id_path_2);
    $titleId = 'profile-title-' . $user->id;
    $dash = '—';

    $ext = fn (?string $path, string $fallback) => strtolower(pathinfo((string) $path, PATHINFO_EXTENSION)) ?: $fallback;

    $statusPill = [
        'active' => ['Active', 'border-green-600/60 bg-green-50 text-green-700'],
        'suspended' => ['Suspended', 'border-red-600/60 bg-red-50 text-red-700'],
        'deactivated' => ['Deactivated', 'border-[#d9826b]/70 bg-[#FBF1EE] text-[#b4452a]'],
        'disapproved' => ['Rejected', 'border-orange-500/60 bg-orange-50 text-orange-700'],
    ][$user->status === 'disapproved' ? 'disapproved' : ($user->account_status ?: 'active')]
        ?? [ucfirst((string) ($user->account_status ?: $user->status)), 'border-gray-300 bg-gray-50 text-gray-600'];

    $roleIcon = [
        'seller' => 'seller-icon.svg',
        'buyer' => 'buyer-icon.svg',
        'logistics_center' => 'courier-icon.svg',
    ][$role] ?? null;

    // House number and street read as one line ("833 Sisa St.").
    $streetLine = $details ? trim(($details->house_no ?? '') . ' ' . ($details->street ?? '')) : '';

    $personalRows = $details ? [
        ['Last Name', $details->last_name],
        ['First Name', $details->first_name],
        ['Middle Name', $details->middle_name ?: $dash],
        ['Sex', $details->sex ? ucfirst($details->sex) : $dash],
        ['Birthday', $details->birthday ? $details->birthday->format('F j, Y') : $dash],
        ['Age', $details->birthday ? $details->age : $dash],
        ['Email', $user->email],
        ['Phone Number', $user->phone_number ?: $dash],
    ] : [];

    $addressRows = $details ? [
        ['Province', $details->province ?: $dash],
        ['Municipality', $details->municipality ?: $dash],
        ['Barangay', $details->barangay ?: $dash],
        ['Street / House No.', $streetLine !== '' ? $streetLine : $dash],
        ['Zip Code', $details->zip_code ?: $dash],
    ] : [];

    $dtClass = 'text-[13px] text-gray-500';
    $ddClass = 'min-w-0 break-words text-sm font-medium text-[#2B1730]';
    $cardClass = 'rounded-2xl border border-[#ece4ec] bg-white p-5 sm:p-6';
    $h3Class = 'mb-5 flex items-center gap-2.5 text-lg font-semibold text-[#3b1735]';
@endphp

<div x-show="openId === {{ $user->id }}" x-cloak role="dialog" aria-modal="true" aria-labelledby="{{ $titleId }}"
    @click.self="openId = null"
    x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
    x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-50 flex items-center justify-center bg-[#2B1730]/50 p-3 backdrop-blur-[2px] sm:p-4">

    <div x-show="openId === {{ $user->id }}" @click.stop
        @keydown.escape.window="if (openId === {{ $user->id }} && !$event.defaultPrevented && !suspendId && !deactivateId && !activateId) openId = null"
        x-transition:enter="transition duration-300 ease-vendo" x-transition:enter-start="opacity-0 translate-y-3 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
        class="flex max-h-[calc(100dvh-1.5rem)] w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-[#FBF7F2] shadow-[0_30px_70px_-30px_rgba(43,23,48,0.6)] sm:max-h-[calc(100dvh-2rem)]">

        {{-- Header --}}
        <div class="flex items-center justify-between border-b border-[#ece4ec] bg-white px-5 py-4 sm:px-6">
            <h3 id="{{ $titleId }}" class="font-display text-xl font-semibold text-[#2B1730]">Profile</h3>
            <button type="button" @click="openId = null" aria-label="Close profile"
                class="-mr-1.5 flex h-9 w-9 items-center justify-center rounded-full text-gray-500 transition duration-200 hover:bg-[#F1E9F1] hover:text-[#3b1735]
                       focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18" /></svg>
            </button>
        </div>

        {{-- Body --}}
        <div class="grid flex-1 grid-cols-1 items-start gap-5 overflow-y-auto p-5 thin-scroll sm:p-6 lg:grid-cols-[300px_minmax(0,1fr)]">

            {{-- ===== Left: profile card + actions ===== --}}
            <div class="space-y-4">
                <aside class="overflow-hidden rounded-2xl border border-[#ece4ec] bg-white">
                    <div class="flex items-center gap-4 border-b border-[#ece4ec] p-5">
                        @if ($user->profile_picture)
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($user->profile_picture) }}" alt="{{ $user->name }} profile photo" loading="lazy"
                                class="h-16 w-16 flex-shrink-0 rounded-full bg-[#EFE4F1] object-cover">
                        @else
                            <span aria-hidden="true"
                                class="flex h-16 w-16 flex-shrink-0 items-center justify-center rounded-full bg-[#EFE4F1] text-xl font-semibold text-[#5b2963]">
                                {{ strtoupper(mb_substr($user->name, 0, 1)) }}
                            </span>
                        @endif
                        <div class="min-w-0">
                            <p class="truncate text-base font-semibold text-[#2B1730]">{{ $user->name }}</p>
                            <p class="mt-0.5 flex items-center gap-1.5 text-[13px] text-gray-600">
                                @if ($roleIcon)
                                    <img src="{{ asset('assets/icons/user-management/' . $roleIcon) }}" alt="" class="h-3.5 w-3.5">
                                @endif
                                {{ $roleLabel }}
                            </p>
                            <span class="mt-1.5 inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium {{ $statusPill[1] }}">
                                {{ $statusPill[0] }}
                            </span>
                        </div>
                    </div>

                    <dl class="divide-y divide-[#f3edf4] px-5 text-[13px]">
                        <div class="flex items-start justify-between gap-4 py-3.5">
                            <dt class="flex flex-shrink-0 items-center gap-2 text-gray-500">
                                <img src="{{ asset('assets/icons/user-management/user-email-icon.svg') }}" alt="" class="h-4 w-4"> Email
                            </dt>
                            <dd class="min-w-0 break-all text-right font-medium text-[#2B1730]">{{ $user->email }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-4 py-3.5">
                            <dt class="flex flex-shrink-0 items-center gap-2 text-gray-500">
                                <img src="{{ asset('assets/icons/user-management/user-phone-icon.svg') }}" alt="" class="h-4 w-4"> Phone
                            </dt>
                            <dd class="min-w-0 text-right font-medium text-[#2B1730]">{{ $user->phone_number ?: $dash }}</dd>
                        </div>
                        @if ($hasBusiness && $details)
                            <div class="flex items-start justify-between gap-4 py-3.5">
                                <dt class="flex flex-shrink-0 items-center gap-2 text-gray-500">
                                    <img src="{{ asset('assets/icons/user-management/user-business-name-icon.svg') }}" alt="" class="h-4 w-4"> Business Name
                                </dt>
                                <dd class="min-w-0 break-words text-right font-medium text-[#2B1730]">{{ $details->business_name ?: $dash }}</dd>
                            </div>
                        @endif
                        @if ($role === 'seller')
                            <div class="py-3.5">
                                <dt class="flex items-center gap-2 text-gray-500">
                                    <img src="{{ asset('assets/icons/user-management/business-information-icon.svg') }}" alt="" class="h-4 w-4"> Category
                                </dt>
                                <dd class="mt-2 flex flex-wrap gap-1.5">
                                    @forelse ($user->categories as $category)
                                        @php $c = $category->colors; @endphp
                                        <span class="rounded-full border px-2.5 py-0.5 text-[11px] font-medium"
                                            style="border-color: {{ $c['border'] }}; background-color: {{ $c['bg'] }}; color: {{ $c['border'] }}">
                                            {{ $category->name }}
                                        </span>
                                    @empty
                                        <span class="text-gray-400">None selected</span>
                                    @endforelse
                                </dd>
                            </div>
                        @endif
                        <div class="flex items-start justify-between gap-4 py-3.5">
                            <dt class="flex flex-shrink-0 items-center gap-2 text-gray-500">
                                <img src="{{ asset('assets/icons/user-management/user-date-applied-icon.svg') }}" alt="" class="h-4 w-4"> Date Joined
                            </dt>
                            <dd class="min-w-0 text-right font-medium text-[#2B1730]">{{ $user->created_at->format('M j, Y') }}</dd>
                        </div>
                    </dl>

                    {{-- Suspension details (only while suspended) --}}
                    @if ($user->account_status === 'suspended')
                        <div class="mx-5 mb-5 mt-1 space-y-1.5 rounded-xl border border-[#f0d4cc] bg-[#FBF1EE] p-3.5 text-[13px]">
                            <p class="flex justify-between gap-3"><span class="text-gray-500">Reason</span><span class="text-right font-medium text-[#2B1730]">{{ $user->suspension_reason ?: $dash }}</span></p>
                            <p class="flex justify-between gap-3"><span class="text-gray-500">Started</span><span class="font-medium text-[#2B1730]">{{ $user->suspended_at ? $user->suspended_at->format('M j, Y') : $dash }}</span></p>
                            <p class="flex justify-between gap-3"><span class="text-gray-500">Ends</span><span class="font-medium text-[#2B1730]">{{ $user->suspended_until ? $user->suspended_until->format('M j, Y') : 'No end date' }}</span></p>

                            @if ($user->suspended_until)
                                {{-- Countdown. destroy() clears the timer when the region is swapped. --}}
                                <p class="flex justify-between gap-3 border-t border-[#f0d4cc] pt-2"
                                    x-data="{
                                        end: new Date(@js($user->suspended_until->toIso8601String())).getTime(),
                                        text: '',
                                        t: null,
                                        tick() {
                                            const diff = this.end - Date.now();
                                            if (diff <= 0) { this.text = 'Ending now'; return; }
                                            const d = Math.floor(diff / 86400000);
                                            const h = Math.floor((diff % 86400000) / 3600000);
                                            const m = Math.floor((diff % 3600000) / 60000);
                                            const s = Math.floor((diff % 60000) / 1000);
                                            this.text = `${d}d ${h}h ${m}m ${s}s left`;
                                        },
                                        init() { this.tick(); this.t = setInterval(() => this.tick(), 1000); },
                                        destroy() { clearInterval(this.t); },
                                    }">
                                    <span class="text-gray-500">Time left</span>
                                    <span class="font-medium tabular-nums text-red-700" x-text="text"></span>
                                </p>
                            @endif
                        </div>
                    @endif
                </aside>

                {{-- Actions, kept outside the card as in the mockup --}}
                @if ($user->status === 'approved' && in_array($user->account_status, ['active', null], true))
                    <div class="grid grid-cols-2 gap-3">
                        <button type="button" @click="suspendId = {{ $user->id }}"
                            class="h-11 rounded-xl border border-[#cf9f93] bg-[#FBF1EE] text-sm font-semibold text-[#8a2f1b] transition duration-200
                                   hover:bg-[#F6E3DD] active:scale-[0.98] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#8a2f1b]/30">
                            Suspend
                        </button>
                        <button type="button" @click="deactivateId = {{ $user->id }}"
                            class="h-11 rounded-xl border border-[#d9826b] bg-white text-sm font-semibold text-[#b4452a] transition duration-200
                                   hover:bg-[#FBF1EE] active:scale-[0.98] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#b4452a]/30">
                            Deactivate
                        </button>
                    </div>
                @elseif ($user->status === 'approved' && in_array($user->account_status, ['suspended', 'deactivated'], true))
                    <button type="button" @click="activateId = {{ $user->id }}"
                        class="h-11 w-full rounded-xl border border-green-600/60 bg-green-50 text-sm font-semibold text-green-700 transition duration-200
                               hover:bg-green-100 active:scale-[0.98] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-green-600/30">
                        {{ $user->account_status === 'suspended' ? 'Lift suspension' : 'Activate account' }}
                    </button>
                @endif
            </div>

            {{-- ===== Right: stacked sections ===== --}}
            <div class="min-w-0 space-y-5">
                @if ($details)

                    <section class="{{ $cardClass }}" aria-labelledby="{{ $titleId }}-personal">
                        <h4 id="{{ $titleId }}-personal" class="{{ $h3Class }}">
                            <img src="{{ asset('assets/icons/user-management/personal-information-icon.svg') }}" alt="" class="h-5 w-5">
                            Personal Information
                        </h4>
                        <div class="grid gap-6 {{ $hasSecondId ? '' : 'md:grid-cols-[minmax(0,1fr)_240px]' }}">
                            <dl class="grid grid-cols-[110px_minmax(0,1fr)] content-start gap-x-4 gap-y-3.5">
                                @foreach ($personalRows as [$label, $value])
                                    <dt class="{{ $dtClass }}">{{ $label }}</dt>
                                    <dd class="{{ $ddClass }}">{{ $value }}</dd>
                                @endforeach
                            </dl>
                            @include('admin.registrations.partials.id-preview')
                        </div>
                    </section>

                    <section class="{{ $cardClass }}" aria-labelledby="{{ $titleId }}-address">
                        <h4 id="{{ $titleId }}-address" class="{{ $h3Class }}">
                            <img src="{{ asset('assets/icons/user-management/address-icon.svg') }}" alt="" class="h-5 w-5">
                            Address
                        </h4>
                        <dl class="grid grid-cols-[130px_minmax(0,1fr)] content-start gap-x-4 gap-y-3.5">
                            @foreach ($addressRows as [$label, $value])
                                <dt class="{{ $dtClass }}">{{ $label }}</dt>
                                <dd class="{{ $ddClass }}">{{ $value }}</dd>
                            @endforeach
                        </dl>
                    </section>

                    @if ($hasBusiness)
                        <section class="{{ $cardClass }}" aria-labelledby="{{ $titleId }}-business">
                            <h4 id="{{ $titleId }}-business" class="{{ $h3Class }}">
                                <img src="{{ asset('assets/icons/user-management/business-information-icon.svg') }}" alt="" class="h-5 w-5">
                                Business Information
                            </h4>
                            <dl class="grid grid-cols-[130px_minmax(0,1fr)] content-start gap-x-4 gap-y-3.5">
                                <dt class="{{ $dtClass }}">Business Name</dt>
                                <dd class="{{ $ddClass }}">{{ $details->business_name ?: $dash }}</dd>

                                @if ($role === 'seller')
                                    <dt class="{{ $dtClass }}">Category</dt>
                                    <dd class="{{ $ddClass }}">{{ $user->categories->pluck('name')->join(', ') ?: $dash }}</dd>
                                @endif

                                <dt class="{{ $dtClass }}">Business Permit</dt>
                                <dd class="min-w-0">
                                    @if ($details->business_permit_path)
                                        @php
                                            $permitExt = $ext($details->business_permit_path, 'pdf');
                                            $permitPayload = [
                                                'url' => route('admin.verification-documents.show', [$user, 'business-permit']),
                                                'title' => 'Business Permit',
                                                'subtitle' => $user->name,
                                                'filename' => 'business_permit.' . $permitExt,
                                                'kind' => $permitExt === 'pdf' ? 'pdf' : 'image',
                                            ];
                                        @endphp
                                        <button type="button" aria-haspopup="dialog" @click="$dispatch('open-document', @js($permitPayload))"
                                            class="inline-flex max-w-full items-center gap-2 rounded-lg border border-[#d9ccdc] px-3 py-1.5 text-[13px] font-normal text-[#3b1735]
                                                   transition duration-200 hover:bg-[#F7F1F7] active:scale-[0.98] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40">
                                            <x-admin.icon name="file" class="h-4 w-4 flex-shrink-0" />
                                            <span class="truncate">business_permit.{{ $permitExt }}</span>
                                        </button>
                                    @else
                                        <span class="text-sm text-gray-400">Not submitted</span>
                                    @endif
                                </dd>
                            </dl>
                        </section>
                    @endif

                @else
                    <div class="rounded-2xl border border-[#ece4ec] bg-white px-6 py-14 text-center">
                        <p class="text-base font-semibold text-[#2B1730]">No details on file</p>
                        <p class="mt-1 text-sm text-gray-500">This user hasn't submitted profile details.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
