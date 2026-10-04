{{--
    Applicant profile + tabbed details.
    Used by admin/registrations/show and by the Dashboard "Recent Registrations" modal,
    so it must stay self-contained (own Alpine scope, Tailwind utilities only).
    Required: $user (with sellerDetail / logisticsCenterDetail / buyerDetail / categories).
--}}
@php
    $details = $user->sellerDetail ?? $user->logisticsCenterDetail ?? $user->buyerDetail;
    $role = $user->role;
    $roleLabel = ucwords(str_replace('_', ' ', $role));
    $hasBusiness = in_array($role, ['seller', 'logistics_center'], true);
    $uid = 'rg' . $user->id; // unique ids: the Dashboard renders several of these on one page

    $ext = fn (?string $path, string $fallback) => strtolower(pathinfo((string) $path, PATHINFO_EXTENSION)) ?: $fallback;

    $statusChip = [
        'pending' => ['Pending review', 'bg-[#FDF3E2] text-[#B45309] ring-[#F3D9A6]'],
        'approved' => ['Approved', 'bg-green-50 text-green-700 ring-green-200'],
        'disapproved' => ['Rejected', 'bg-red-50 text-red-600 ring-red-200'],
    ][$user->status] ?? [ucfirst((string) $user->status), 'bg-gray-100 text-gray-600 ring-gray-200'];

    $tabs = [
        ['personal', 'Personal Information', 'personal-information-icon.svg'],
        ['address', 'User Address', 'address-icon.svg'],
    ];
    if ($hasBusiness) {
        $tabs[] = ['business', 'Business Information', 'business-information-icon.svg'];
    }
    $tabKeys = array_column($tabs, 0);

    $dash = '—';
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

    $streetLine = $details ? trim(($details->house_no ?? '') . ' ' . ($details->street ?? '')) : '';
    $addressRows = $details ? [
        ['Province', $details->province ?: $dash],
        ['Municipality', $details->municipality ?: $dash],
        ['Barangay', $details->barangay ?: $dash],
        ['Street / House No.', $streetLine !== '' ? $streetLine : $dash],
        ['Zip Code', $details->zip_code ?: $dash],
    ] : [];

    $panelBase = 'col-start-1 row-start-1 rounded-2xl border border-[#ece4ec] bg-white p-5 transition-[opacity,transform,visibility] duration-300 ease-vendo sm:p-6';
    $dtClass = 'text-[13px] text-gray-500';
    $ddClass = 'min-w-0 break-words text-sm font-medium text-[#2B1730]';
@endphp

<div class="grid grid-cols-1 items-start gap-5 lg:grid-cols-[320px_minmax(0,1fr)]" x-data="{
    tab: 'personal',
    ready: false,
    ind: { x: 0, w: 0 },
    lightbox: false,
    lbSrc: '',
    order: @js($tabKeys),

    move() {
        const el = this.$refs['tab_' + this.tab];
        if (!el || !el.offsetWidth) return;
        this.ind = { x: el.offsetLeft, w: el.offsetWidth };
        if (!this.ready) requestAnimationFrame(() => this.ready = true);
    },
    select(key) {
        this.tab = key;
        this.move();
    },
    keynav(e) {
        let i = this.order.indexOf(this.tab);
        if (e.key === 'ArrowRight') i = (i + 1) % this.order.length;
        else if (e.key === 'ArrowLeft') i = (i - 1 + this.order.length) % this.order.length;
        else return;
        e.preventDefault();
        this.select(this.order[i]);
        this.$refs['tab_' + this.tab].focus();
    },
}" x-init="if ($refs.tabbar) { new ResizeObserver(() => move()).observe($refs.tabbar); $nextTick(() => move()); }">

    {{-- ============ Profile card ============ --}}
    <aside class="overflow-hidden rounded-2xl border border-[#ece4ec] bg-white lg:sticky lg:top-6">
        <div class="flex items-center gap-4 border-b border-[#ece4ec] p-5">
            <span aria-hidden="true"
                class="flex h-16 w-16 flex-shrink-0 items-center justify-center rounded-full bg-[#EFE4F1] text-xl font-semibold text-[#5b2963]">
                {{ strtoupper(mb_substr($user->name, 0, 1)) }}
            </span>
            <div class="min-w-0">
                <p class="truncate text-base font-semibold text-[#2B1730]">{{ $user->name }}</p>
                <p class="text-[13px] text-gray-500">{{ $roleLabel }} Applicant</p>
                <span class="mt-1.5 inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium ring-1 ring-inset {{ $statusChip[1] }}">
                    {{ $statusChip[0] }}
                </span>
            </div>
        </div>

        <dl class="divide-y divide-[#f3edf4] px-5 text-[13px]">
            <div class="flex items-start justify-between gap-4 py-3.5">
                <dt class="flex flex-shrink-0 items-center gap-2 text-gray-500">
                    <img src="{{ asset('assets/icons/registration/user-email-icon.svg') }}" alt="" class="h-4 w-4"> Email
                </dt>
                <dd class="min-w-0 break-all text-right font-medium text-[#2B1730]">{{ $user->email }}</dd>
            </div>
            <div class="flex items-start justify-between gap-4 py-3.5">
                <dt class="flex flex-shrink-0 items-center gap-2 text-gray-500">
                    <img src="{{ asset('assets/icons/registration/user-phone-icon.svg') }}" alt="" class="h-4 w-4"> Phone
                </dt>
                <dd class="min-w-0 text-right font-medium text-[#2B1730]">{{ $user->phone_number ?: $dash }}</dd>
            </div>
            @if ($hasBusiness && $details)
                <div class="flex items-start justify-between gap-4 py-3.5">
                    <dt class="flex flex-shrink-0 items-center gap-2 text-gray-500">
                        <img src="{{ asset('assets/icons/registration/user-business-name-icon.svg') }}" alt="" class="h-4 w-4"> Business
                    </dt>
                    <dd class="min-w-0 break-words text-right font-medium text-[#2B1730]">{{ $details->business_name ?: $dash }}</dd>
                </div>
            @endif
            <div class="flex items-start justify-between gap-4 py-3.5">
                <dt class="flex flex-shrink-0 items-center gap-2 text-gray-500">
                    <img src="{{ asset('assets/icons/registration/user-date-applied-icon.svg') }}" alt="" class="h-4 w-4"> Date Applied
                </dt>
                <dd class="min-w-0 text-right font-medium text-[#2B1730]">{{ $user->created_at->format('M j, Y') }}</dd>
            </div>
        </dl>
    </aside>

    {{-- ============ Tabs + panels ============ --}}
    <div class="min-w-0">

        @if ($details)
        <div class="mb-3 overflow-x-auto pb-1 thin-scroll">
            <div x-ref="tabbar" role="tablist" aria-label="Application sections" @keydown="keynav($event)"
                class="relative inline-flex gap-1 rounded-full border border-[#ece4ec] bg-white p-1">

                {{-- Sliding highlight --}}
                <span aria-hidden="true"
                    class="absolute inset-y-1 left-0 rounded-full bg-[#3b1735] shadow-sm"
                    :class="ready ? 'transition-[transform,width] duration-300 ease-vendo' : ''"
                    :style="`width:${ind.w}px;transform:translateX(${ind.x}px);opacity:${ind.w ? 1 : 0}`"></span>

                @foreach ($tabs as [$key, $label])
                    <button type="button" role="tab" id="{{ $uid }}-tab-{{ $key }}" x-ref="tab_{{ $key }}"
                        aria-controls="{{ $uid }}-panel-{{ $key }}"
                        :aria-selected="tab === '{{ $key }}'" :tabindex="tab === '{{ $key }}' ? 0 : -1"
                        @click="select('{{ $key }}')"
                        :class="tab === '{{ $key }}' ? 'text-white' : 'text-gray-600 hover:text-[#3b1735]'"
                        class="relative z-10 whitespace-nowrap rounded-full px-4 py-2 text-[13px] font-medium transition-colors duration-200
                               focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>
        @endif

        @if (! $details)
            <div class="rounded-2xl border border-[#ece4ec] bg-white px-6 py-14 text-center">
                <p class="text-base font-semibold text-[#2B1730]">No details submitted yet</p>
                <p class="mt-1 text-sm text-gray-500">This applicant hasn't completed their profile, so there's nothing to review.</p>
            </div>
        @else
            {{-- Panels share one grid cell, so switching tabs cross-fades without the card changing height. --}}
            <div class="grid">

                {{-- Personal --}}
                <section role="tabpanel" id="{{ $uid }}-panel-personal" aria-labelledby="{{ $uid }}-tab-personal"
                    :aria-hidden="tab !== 'personal'" :inert="tab !== 'personal'"
                    :class="tab === 'personal' ? 'visible translate-y-0 opacity-100' : 'pointer-events-none invisible translate-y-1.5 opacity-0'"
                    class="{{ $panelBase }}">
                    <h3 class="mb-5 flex items-center gap-2.5 text-base font-semibold text-[#3b1735]">
                        <img src="{{ asset('assets/icons/registration/personal-information-icon.svg') }}" alt="" class="h-5 w-5">
                        Personal Information
                    </h3>
                    <div class="grid gap-6 md:grid-cols-[minmax(0,1fr)_260px]">
                        <dl class="grid grid-cols-[110px_minmax(0,1fr)] content-start gap-x-4 gap-y-3.5">
                            @foreach ($personalRows as [$label, $value])
                                <dt class="{{ $dtClass }}">{{ $label }}</dt>
                                <dd class="{{ $ddClass }}">{{ $value }}</dd>
                            @endforeach
                        </dl>
                        @include('admin.registrations.partials.id-preview')
                    </div>
                </section>

                {{-- Address --}}
                <section role="tabpanel" id="{{ $uid }}-panel-address" aria-labelledby="{{ $uid }}-tab-address"
                    :aria-hidden="tab !== 'address'" :inert="tab !== 'address'"
                    :class="tab === 'address' ? 'visible translate-y-0 opacity-100' : 'pointer-events-none invisible translate-y-1.5 opacity-0'"
                    class="{{ $panelBase }}">
                    <h3 class="mb-5 flex items-center gap-2.5 text-base font-semibold text-[#3b1735]">
                        <img src="{{ asset('assets/icons/registration/address-icon.svg') }}" alt="" class="h-5 w-5">
                        Address
                    </h3>
                    <div class="grid gap-6 md:grid-cols-[minmax(0,1fr)_260px]">
                        <dl class="grid grid-cols-[130px_minmax(0,1fr)] content-start gap-x-4 gap-y-3.5">
                            @foreach ($addressRows as [$label, $value])
                                <dt class="{{ $dtClass }}">{{ $label }}</dt>
                                <dd class="{{ $ddClass }}">{{ $value }}</dd>
                            @endforeach
                        </dl>
                        @include('admin.registrations.partials.id-preview')
                    </div>
                </section>

                {{-- Business (sellers and logistics centers) --}}
                @if ($hasBusiness)
                    <section role="tabpanel" id="{{ $uid }}-panel-business" aria-labelledby="{{ $uid }}-tab-business"
                        :aria-hidden="tab !== 'business'" :inert="tab !== 'business'"
                        :class="tab === 'business' ? 'visible translate-y-0 opacity-100' : 'pointer-events-none invisible translate-y-1.5 opacity-0'"
                        class="{{ $panelBase }}">
                        <h3 class="mb-5 flex items-center gap-2.5 text-base font-semibold text-[#3b1735]">
                            <img src="{{ asset('assets/icons/registration/business-information-icon.svg') }}" alt="" class="h-5 w-5">
                            Business Information
                        </h3>
                        <dl class="grid grid-cols-[130px_minmax(0,1fr)] content-start gap-x-4 gap-y-3.5">
                            <dt class="{{ $dtClass }}">Business Name</dt>
                            <dd class="{{ $ddClass }}">{{ $details->business_name ?: $dash }}</dd>

                            @if ($role === 'seller')
                                <dt class="{{ $dtClass }}">Categories</dt>
                                <dd class="min-w-0">
                                    <div class="flex flex-wrap gap-2">
                                        @forelse ($user->categories as $category)
                                            <span class="rounded-full border border-[#d9ccdc] bg-[#F7F1F7] px-3 py-1 text-xs font-medium text-[#3b1735]">{{ $category->name }}</span>
                                        @empty
                                            <span class="text-sm text-gray-400">None selected</span>
                                        @endforelse
                                    </div>
                                </dd>
                            @endif

                            <dt class="{{ $dtClass }}">Business Permit</dt>
                            <dd class="min-w-0">
                                @if ($details->business_permit_path)
                                    <a href="{{ route('admin.verification-documents.show', [$user, 'business-permit']) }}" target="_blank" rel="noopener noreferrer"
                                        class="inline-flex max-w-full items-center gap-2 rounded-lg border border-[#d9ccdc] px-3 py-1.5 text-[13px] font-normal text-[#3b1735]
                                               transition duration-200 hover:bg-[#F7F1F7] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/40">
                                        <img src="{{ asset('assets/icons/registration/document-icon.svg') }}" alt="" class="h-4 w-4 flex-shrink-0">
                                        <span class="truncate">business_permit.{{ $ext($details->business_permit_path, 'pdf') }}</span>
                                    </a>
                                @else
                                    <span class="text-sm text-gray-400">Not submitted</span>
                                @endif
                            </dd>
                        </dl>
                    </section>
                @endif
            </div>

            {{-- ID lightbox --}}
            @if ($details->valid_id_path)
                <div x-show="lightbox" x-cloak role="dialog" aria-modal="true" aria-label="Valid ID preview"
                    @keydown.escape.window="lightbox = false" @click.self="lightbox = false"
                    x-effect="if (lightbox) $nextTick(() => $refs.lbClose && $refs.lbClose.focus())"
                    x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                    x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                    class="fixed inset-0 z-[70] flex items-center justify-center bg-[#140a17]/80 p-4 backdrop-blur-sm">
                    <div x-show="lightbox" class="relative max-h-full max-w-4xl"
                        x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="opacity-0 scale-95 translate-y-2" x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                        x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
                        <img :src="lbSrc" alt="Valid ID submitted by {{ $user->name }}"
                            class="max-h-[85vh] w-auto rounded-xl bg-white object-contain shadow-2xl">
                        <button type="button" x-ref="lbClose" @click="lightbox = false" aria-label="Close preview"
                            class="absolute -right-2 -top-2 flex h-9 w-9 items-center justify-center rounded-full bg-white text-[#2B1730] shadow-lg
                                   transition hover:scale-105 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3b1735]/50">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18" /></svg>
                        </button>
                    </div>
                </div>
            @endif
        @endif
    </div>
</div>