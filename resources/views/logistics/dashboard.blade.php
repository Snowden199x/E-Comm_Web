@php
    $centerName = $center?->business_name ?: auth()->user()->name;

    // $overview is optional. Until the controller supplies it, the workflow cards
    // act as shortcuts without numbers. Expected keys are listed in
    // docs/features/logistics/redesign-backend-needs.md.
    $hasOverview = isset($overview) && is_array($overview);

    $workflow = [
        ['Incoming Parcels', 'inbox', 'logistics.incoming-parcels', 'pickup_requests', 'Pickup requests from sellers and parcels heading to your hub.'],
        ['Parcel Sorting', 'layers', 'logistics.parcel-sorting', 'to_sort', 'Confirm hub arrival and sort along the planned route.'],
        ['Delivery Assignments', 'truck', 'logistics.delivery-assignments', 'to_assign', 'Receive parcels and assign a rider per area.'],
        ['Delivery Monitoring', 'map-pin', 'logistics.delivery-monitoring', 'active', 'Follow active parcels and the latest rider update.'],
    ];
@endphp
<x-logistics.layout title="Dashboard">
    <div class="lg-page">

        <div class="lg-page-head">
            <div>
                <h1>Dashboard</h1>
                <p>Here is what is moving through {{ $centerName }}, and the riders waiting on your decision.</p>
            </div>
            <div class="lg-page-head__actions">
                <a href="{{ route('logistics.reports') }}" class="lg-btn lg-btn--outline">
                    <x-logistics.icon name="bar-chart" :size="18" /> View reports
                </a>
                <a href="{{ route('logistics.incoming-parcels') }}" class="lg-btn">
                    <x-logistics.icon name="inbox" :size="18" /> Incoming parcels
                </a>
            </div>
        </div>

        {{-- Parcel workflow: the order a parcel moves through this center --}}
        <section aria-labelledby="lgWorkflowTitle">
            <div style="margin-bottom: 16px;">
                <h2 class="lg-section-title" id="lgWorkflowTitle">Parcel workflow</h2>
                <p class="lg-section-sub">Open a stage to act on its parcels.</p>
            </div>
            <ol class="lg-flow">
                @foreach ($workflow as $index => [$label, $icon, $routeName, $key, $text])
                    <li>
                        <a href="{{ route($routeName) }}" class="lg-flow__step lg-rise" style="--i: {{ $index }}">
                            <span class="lg-flow__top">
                                <span class="lg-flow__chip"><x-logistics.icon :name="$icon" :size="22" /></span>
                                @if ($hasOverview)
                                    <span class="lg-flow__count" data-count="{{ (int) ($overview[$key] ?? 0) }}">{{ number_format((int) ($overview[$key] ?? 0)) }}</span>
                                @else
                                    <x-logistics.icon name="arrow-right" :size="18" class="lg-muted" />
                                @endif
                            </span>
                            <h3>{{ $label }}</h3>
                            <p>{{ $text }}</p>
                        </a>
                    </li>
                @endforeach
            </ol>
        </section>

        {{-- Rider application totals (real data from the controller) --}}
        <section aria-label="Rider applications summary">
            <div class="lg-grid lg-grid--3">
                <div class="lg-stat lg-rise" style="--i: 0">
                    <span class="lg-stat__icon lg-stat__icon--amber"><x-logistics.icon name="clock" :size="24" /></span>
                    <div>
                        <p class="lg-stat__label">Pending applications</p>
                        <p class="lg-stat__value" data-count="{{ $stats['pending'] }}">{{ number_format($stats['pending']) }}</p>
                    </div>
                </div>
                <div class="lg-stat lg-rise" style="--i: 1">
                    <span class="lg-stat__icon lg-stat__icon--green"><x-logistics.icon name="check-circle" :size="24" /></span>
                    <div>
                        <p class="lg-stat__label">Approved riders</p>
                        <p class="lg-stat__value" data-count="{{ $stats['approved'] }}">{{ number_format($stats['approved']) }}</p>
                    </div>
                </div>
                <div class="lg-stat lg-rise" style="--i: 2">
                    <span class="lg-stat__icon lg-stat__icon--red"><x-logistics.icon name="x" :size="24" /></span>
                    <div>
                        <p class="lg-stat__label">Rejected applications</p>
                        <p class="lg-stat__value" data-count="{{ $stats['rejected'] }}">{{ number_format($stats['rejected']) }}</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- Pending rider applications --}}
        <section class="lg-card" id="rider-applications" style="scroll-margin-top: 88px;" aria-labelledby="lgRidersTitle">
            <div class="lg-card__head">
                <div>
                    <h2 class="lg-section-title" id="lgRidersTitle">Rider applications</h2>
                    <p class="lg-section-sub">Riders who applied to ride under {{ $centerName }}. Review their documents before you decide.</p>
                </div>
                @if ($stats['pending'] > 0)
                    <span class="lg-pill lg-pill--amber">{{ number_format($stats['pending']) }} waiting</span>
                @endif
            </div>

            @forelse ($riders as $rider)
                <div class="lg-row" x-data="{ open: false, rejectOpen: false }">
                    <div class="lg-row__main">
                        <div class="lg-person">
                            <span class="lg-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($rider->first_name, 0, 1)) }}</span>
                            <span style="min-width: 0;">
                                <strong>{{ $rider->first_name }} {{ $rider->last_name }}</strong>
                                <small>{{ $rider->user->email }} &middot; {{ $rider->user->phone_number ?? '—' }}</small>
                            </span>
                        </div>

                        <div class="lg-row__meta">
                            {{ $rider->vehicle_type }}
                            <small>Plate {{ $rider->plate_number }}</small>
                        </div>

                        <div class="lg-row__meta lg-row__applied">
                            Applied
                            <small>{{ $rider->created_at->format('M d, Y') }}</small>
                        </div>

                        <div class="lg-row__actions">
                            <button type="button" class="lg-btn lg-btn--ghost lg-btn--sm" @click="open = !open" :aria-expanded="open.toString()">
                                <span x-text="open ? 'Hide details' : 'View details'">View details</span>
                            </button>

                            <form method="POST" action="{{ route('logistics.riders.approve', $rider) }}" data-lg-loading>
                                @csrf
                                <button type="submit" class="lg-btn lg-btn--sm">
                                    <x-logistics.icon name="check" :size="16" /> Approve
                                </button>
                            </form>

                            <button type="button" class="lg-btn lg-btn--danger lg-btn--sm" @click="rejectOpen = true">Reject</button>
                        </div>
                    </div>

                    <div class="lg-expand" :class="{ 'is-open': open }" :inert="!open">
                        <div>
                            <dl class="lg-details">
                                <div>
                                    <dt>Address</dt>
                                    <dd>{{ $rider->street }}, {{ $rider->barangay }}, {{ $rider->municipality }}, {{ $rider->province }} {{ $rider->zip_code }}</dd>
                                </div>
                                <div>
                                    <dt>Documents</dt>
                                    <dd>
                                        <span class="lg-doc-links">
                                            @if ($rider->valid_id_path)
                                                <a href="{{ route('logistics.riders.verification-documents.show', [$rider, 'valid-id']) }}" target="_blank" rel="noopener noreferrer" class="lg-doc-link"><x-logistics.icon name="file" :size="16" /> Valid ID</a>
                                            @endif
                                            @if ($rider->drivers_license_path)
                                                <a href="{{ route('logistics.riders.verification-documents.show', [$rider, 'drivers-license']) }}" target="_blank" rel="noopener noreferrer" class="lg-doc-link"><x-logistics.icon name="file" :size="16" /> Driver's License</a>
                                            @endif
                                            @if ($rider->or_cr_path)
                                                <a href="{{ route('logistics.riders.verification-documents.show', [$rider, 'or-cr']) }}" target="_blank" rel="noopener noreferrer" class="lg-doc-link"><x-logistics.icon name="file" :size="16" /> OR / CR</a>
                                            @endif
                                            @unless ($rider->valid_id_path || $rider->drivers_license_path || $rider->or_cr_path)
                                                <span class="lg-muted">No documents uploaded.</span>
                                            @endunless
                                        </span>
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    {{-- Reject dialog --}}
                    <template x-teleport="body">
                        <div class="lg-body lg-overlay" x-show="rejectOpen" x-cloak
                             x-transition:enter="lg-f-enter" x-transition:enter-start="lg-f-enter-start" x-transition:enter-end="lg-f-enter-end"
                             x-transition:leave="lg-f-leave" x-transition:leave-start="lg-f-leave-start" x-transition:leave-end="lg-f-leave-end"
                             @click.self="rejectOpen = false" @keydown.escape.window="rejectOpen = false">
                            <div class="lg-modal" role="dialog" aria-modal="true" aria-labelledby="reject-title-{{ $rider->id }}" x-data="{ reason: '', details: '' }"
                                 x-show="rejectOpen"
                                 x-transition:enter="lg-t-enter" x-transition:enter-start="lg-t-enter-start" x-transition:enter-end="lg-t-enter-end"
                                 x-transition:leave="lg-t-leave" x-transition:leave-start="lg-t-leave-start" x-transition:leave-end="lg-t-leave-end">
                                <form method="POST" action="{{ route('logistics.riders.reject', $rider) }}" data-lg-loading>
                                    @csrf
                                    <div class="lg-modal__head">
                                        <div>
                                            <h3 id="reject-title-{{ $rider->id }}">Reject application</h3>
                                            <p>{{ $rider->first_name }} {{ $rider->last_name }} will not be approved to ride under your center.</p>
                                        </div>
                                        <button type="button" class="lg-toast__close" @click="rejectOpen = false" aria-label="Close"><x-logistics.icon name="x" :size="18" /></button>
                                    </div>

                                    <div class="lg-modal__body">
                                        <fieldset style="border: 0; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 8px;">
                                            <legend class="lg-label" style="margin-bottom: 8px;">Reason for rejection <span style="color: var(--lg-red);">*</span></legend>
                                            @foreach ([
                                                'Incomplete Application',
                                                'Invalid Identification',
                                                'Vehicle Documents Invalid',
                                                'Information Mismatch',
                                                'Does Not Meet Requirements',
                                                'Other (please specify)',
                                            ] as $option)
                                                <label class="lg-radio">
                                                    <input type="radio" name="reason" value="{{ $option }}" x-model="reason" required>
                                                    {{ $option }}
                                                </label>
                                            @endforeach
                                        </fieldset>

                                        <div class="lg-field">
                                            <label for="reject-details-{{ $rider->id }}">Additional details</label>
                                            <textarea id="reject-details-{{ $rider->id }}" name="additional_details" x-model="details" maxlength="500" rows="3" class="lg-textarea" placeholder="Write additional details here..."></textarea>
                                            <p class="lg-counter" x-text="details.length + '/500'">0/500</p>
                                        </div>
                                    </div>

                                    <div class="lg-modal__foot">
                                        <button type="button" class="lg-btn lg-btn--outline" @click="rejectOpen = false">Cancel</button>
                                        <button type="submit" class="lg-btn lg-btn--danger-solid">Reject application</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </template>
                </div>
            @empty
                <div class="lg-empty">
                    <span class="lg-empty__icon"><x-logistics.icon name="check-circle" :size="30" /></span>
                    <h3>You're all caught up</h3>
                    <p>No pending rider applications right now. New applications appear here when riders register under your center.</p>
                </div>
            @endforelse

            @if ($riders->hasPages())
                <div style="padding: 0 24px 20px;">
                    {{ $riders->links('logistics.partials.pagination') }}
                </div>
            @endif
        </section>

    </div>
</x-logistics.layout>