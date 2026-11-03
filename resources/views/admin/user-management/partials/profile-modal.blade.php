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
        'active' => ['Active', 'border-vd-success-line bg-vd-success-bg text-vd-success-ink'],
        'suspended' => ['Suspended', 'border-vd-warning-line bg-vd-warning-bg text-vd-warning-ink'],
        'deactivated' => ['Deactivated', 'border-vd-line-strong bg-vd-tint text-vd-muted'],
        'disapproved' => ['Rejected', 'border-vd-danger-line bg-vd-danger-bg text-vd-danger-ink'],
    ][$user->status === 'disapproved' ? 'disapproved' : ($user->account_status ?: 'active')]
        ?? [ucfirst((string) ($user->account_status ?: $user->status)), 'border-vd-line-strong bg-vd-tint text-vd-muted'];

    $roleIcon = [
        'seller' => 'seller-icon.svg',
        'buyer' => 'buyer-icon.svg',
        'logistics_center' => 'courier-icon.svg',
    ][$role] ?? null;

    // House number and street read as one line ("833 Sisa St.").
    $streetLine = $details ? trim(($details->house_no ?? '') . ' ' . ($details->street ?? '')) : '';

    $personalRows = $details ? [
        ['Last name', $details->last_name],
        ['First name', $details->first_name],
        ['Middle name', $details->middle_name ?: $dash],
        ['Sex', $details->sex ? ucfirst($details->sex) : $dash],
        ['Birthday', $details->birthday ? $details->birthday->format('F j, Y') : $dash],
        ['Age', $details->birthday ? $details->age : $dash],
        ['Email', $user->email],
        ['Phone', $user->phone_number ?: $dash],
    ] : [];

    $addressRows = $details ? [
        ['Province', $details->province ?: $dash],
        ['Municipality', $details->municipality ?: $dash],
        ['Barangay', $details->barangay ?: $dash],
        ['Street / house no.', $streetLine !== '' ? $streetLine : $dash],
        ['Zip code', $details->zip_code ?: $dash],
    ] : [];

    $dtClass = 'text-[13px] text-vd-muted';
    $ddClass = 'min-w-0 break-words text-sm font-medium text-vd-ink';
    $cardClass = 'rounded-2xl border border-vd-line bg-white p-5';
    $h3Class = 'mb-5 flex items-center gap-2.5 text-base font-semibold text-vd-plum';
@endphp

<div x-show="openId === {{ $user->id }}" x-cloak role="dialog" aria-modal="true" aria-labelledby="{{ $titleId }}"
    @click.self="openId = null"
    x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
    x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-50 flex items-center justify-center bg-vd-ink/50 p-3 sm:p-4">

    <div x-show="openId === {{ $user->id }}" @click.stop
        @keydown.escape.window="if (openId === {{ $user->id }} && !$event.defaultPrevented && !suspendId && !deactivateId && !activateId) openId = null"
        x-transition:enter="transition duration-300 ease-vendo" x-transition:enter-start="opacity-0 translate-y-3 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
        class="flex max-h-[calc(100dvh-1.5rem)] w-full max-w-5xl flex-col overflow-hidden rounded-2xl border border-vd-line bg-vd-paper sm:max-h-[calc(100dvh-2rem)]">

        {{-- Header --}}
        <div class="flex items-center justify-between border-b border-vd-line bg-white px-5 py-4 sm:px-6">
            <h3 id="{{ $titleId }}" class="font-display text-xl font-semibold text-vd-ink">Profile</h3>
            <button type="button" @click="openId = null" aria-label="Close profile"
                class="-mr-1.5 flex h-9 w-9 items-center justify-center rounded-full text-vd-muted transition duration-200 hover:bg-vd-tint hover:text-vd-plum
                       focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-plum/40">
                <x-admin.icon name="x" class="h-4 w-4" />
            </button>
        </div>

        {{-- Body --}}
        <div class="grid flex-1 grid-cols-1 items-start gap-5 overflow-y-auto p-5 thin-scroll lg:grid-cols-[300px_minmax(0,1fr)]">

            {{-- ===== Left: profile card + actions ===== --}}
            <div class="space-y-4">
                <aside class="overflow-hidden rounded-2xl border border-vd-line bg-white">
                    <div class="flex items-center gap-4 border-b border-vd-line p-5">
                        <x-admin.avatar :user="$user" size="h-16 w-16" text="text-xl" />
                        <div class="min-w-0">
                            <p class="truncate text-base font-semibold text-vd-ink">{{ $user->name }}</p>
                            <p class="mt-0.5 flex items-center gap-1.5 text-[13px] text-vd-ink">
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

                    <dl class="divide-y divide-vd-line px-5 text-[13px]">
                        <div class="flex items-start justify-between gap-4 py-3.5">
                            <dt class="flex flex-shrink-0 items-center gap-2 text-vd-muted">
                                <img src="{{ asset('assets/icons/user-management/user-email-icon.svg') }}" alt="" class="h-4 w-4"> Email
                            </dt>
                            <dd class="min-w-0 break-all text-right font-medium text-vd-ink">{{ $user->email }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-4 py-3.5">
                            <dt class="flex flex-shrink-0 items-center gap-2 text-vd-muted">
                                <img src="{{ asset('assets/icons/user-management/user-phone-icon.svg') }}" alt="" class="h-4 w-4"> Phone
                            </dt>
                            <dd class="min-w-0 text-right font-medium text-vd-ink">{{ $user->phone_number ?: $dash }}</dd>
                        </div>
                        @if ($hasBusiness && $details)
                            <div class="flex items-start justify-between gap-4 py-3.5">
                                <dt class="flex flex-shrink-0 items-center gap-2 text-vd-muted">
                                    <img src="{{ asset('assets/icons/user-management/user-business-name-icon.svg') }}" alt="" class="h-4 w-4"> Business name
                                </dt>
                                <dd class="min-w-0 break-words text-right font-medium text-vd-ink">{{ $details->business_name ?: $dash }}</dd>
                            </div>
                        @endif
                        @if ($role === 'seller')
                            <div class="py-3.5">
                                <dt class="flex items-center gap-2 text-vd-muted">
                                    <img src="{{ asset('assets/icons/user-management/business-information-icon.svg') }}" alt="" class="h-4 w-4"> Category
                                </dt>
                                <dd class="mt-2 flex flex-wrap gap-1.5">
                                    @forelse ($user->categories as $category)
                                        @include('admin.partials.category-chip', ['category' => $category])
                                    @empty
                                        <span class="text-vd-muted">None selected</span>
                                    @endforelse
                                </dd>
                            </div>
                        @endif
                        <div class="flex items-start justify-between gap-4 py-3.5">
                            <dt class="flex flex-shrink-0 items-center gap-2 text-vd-muted">
                                <img src="{{ asset('assets/icons/user-management/user-date-applied-icon.svg') }}" alt="" class="h-4 w-4"> Joined
                            </dt>
                            <dd class="min-w-0 text-right font-medium text-vd-ink">{{ $user->created_at->format('M j, Y') }}</dd>
                        </div>
                    </dl>

                    {{-- Suspension details (only while suspended) --}}
                    @if ($user->account_status === 'suspended')
                        <div class="mx-5 mb-5 mt-1 space-y-1.5 rounded-xl border border-vd-warning-line bg-vd-warning-bg p-3.5 text-[13px]">
                            <p class="flex justify-between gap-3"><span class="text-vd-muted">Reason</span><span class="text-right font-medium text-vd-ink">{{ $user->suspension_reason ?: $dash }}</span></p>
                            <p class="flex justify-between gap-3"><span class="text-vd-muted">Started</span><span class="font-medium text-vd-ink">{{ $user->suspended_at ? $user->suspended_at->format('M j, Y') : $dash }}</span></p>
                            <p class="flex justify-between gap-3"><span class="text-vd-muted">Ends</span><span class="font-medium text-vd-ink">{{ $user->suspended_until ? $user->suspended_until->format('M j, Y') : 'No end date' }}</span></p>

                            @if ($user->suspended_until)
                                {{-- Countdown. destroy() clears the timer when the region is swapped. --}}
                                <p class="flex justify-between gap-3 border-t border-vd-warning-line pt-2"
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
                                    <span class="text-vd-muted">Time left</span>
                                    <span class="font-medium tabular-nums text-vd-warning-ink" x-text="text"></span>
                                </p>
                            @endif
                        </div>
                    @endif
                </aside>

                {{-- Actions, kept outside the card as in the mockup --}}
                @if ($user->status === 'approved' && in_array($user->account_status, ['active', null], true))
                    <div class="grid grid-cols-2 gap-3">
                        <button type="button" @click="suspendId = {{ $user->id }}"
                            class="h-10 rounded-xl border border-vd-danger-line bg-vd-danger-bg text-sm font-semibold text-vd-danger-ink transition-colors duration-200
                                   hover:border-vd-danger-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-danger-ink/40">
                            Suspend
                        </button>
                        <button type="button" @click="deactivateId = {{ $user->id }}"
                            class="h-10 rounded-xl border border-vd-danger-line bg-white text-sm font-semibold text-vd-danger-ink transition-colors duration-200
                                   hover:bg-vd-danger-bg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-danger-ink/40">
                            Deactivate
                        </button>
                    </div>
                @elseif ($user->status === 'approved' && in_array($user->account_status, ['suspended', 'deactivated'], true))
                    <button type="button" @click="activateId = {{ $user->id }}"
                        class="h-10 w-full rounded-xl border border-vd-success-line bg-vd-success-bg text-sm font-semibold text-vd-success-ink transition-colors duration-200
                               hover:border-vd-success-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-success-ink/40">
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
                            Personal information
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
                                Business information
                            </h4>
                            <dl class="grid grid-cols-[130px_minmax(0,1fr)] content-start gap-x-4 gap-y-3.5">
                                <dt class="{{ $dtClass }}">Business name</dt>
                                <dd class="{{ $ddClass }}">{{ $details->business_name ?: $dash }}</dd>

                                <dt class="{{ $dtClass }}">Business permit</dt>
                                <dd class="min-w-0">
                                    @if ($details->business_permit_path)
                                        @php
                                            $permitExt = $ext($details->business_permit_path, 'pdf');
                                            $permitPayload = [
                                                'url' => route('admin.verification-documents.show', [$user, 'business-permit']),
                                                'title' => 'Business permit',
                                                'subtitle' => $user->name,
                                                'filename' => 'business_permit.' . $permitExt,
                                                'kind' => $permitExt === 'pdf' ? 'pdf' : 'image',
                                            ];
                                        @endphp
                                        <button type="button" aria-haspopup="dialog" @click="$dispatch('open-document', @js($permitPayload))"
                                            class="inline-flex max-w-full items-center gap-2 rounded-lg border border-vd-line-strong px-3 py-1.5 text-[13px] font-normal text-vd-plum
                                                   transition duration-200 hover:bg-vd-tint active:scale-[0.98] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-plum/40">
                                            <x-admin.icon name="file" class="h-4 w-4 flex-shrink-0" />
                                            <span class="truncate">business_permit.{{ $permitExt }}</span>
                                        </button>
                                    @else
                                        <span class="text-sm text-vd-muted">Not submitted</span>
                                    @endif
                                </dd>
                            </dl>
                        </section>
                    @endif

                @else
                    <x-admin.empty-state class="rounded-2xl border border-vd-line bg-white" icon="file-text" title="No details on file" text="This user hasn't filled in their profile." />
                @endif
            </div>
        </div>
    </div>
</div>
