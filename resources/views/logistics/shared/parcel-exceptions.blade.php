{{--
    Parcel Exceptions, shared by the Company (read-only), Province Hub and Station Hub portals (spec section 8A).
    Fed by ParcelExceptionController::index. Report posts to ::store and Resolve to ::resolve (province and station only).
    Look: the seller pastel lilac system. Styles: resources/css/logistics/exceptions-theme.css (pushed last, tokens copied
    from seller/delivered-orders.css, dialogs carry their own copy). No page script: the dialogs run in the Alpine scope below.
    Status colors: Open = butter (waiting for action), Resolved = mint.
    Status chips and the tracking-number search are server-side (this list is paged): links and a GET form.
    One click on a row opens its details; whoever manages the exception can resolve it from there.
    Loading: shell skeleton when paging or filtering, spinner on the submit buttons. Errors: top banner, and the dialog
    reopens with what was typed (the hidden _dialog field says which one).
--}}
@php
    $isCompany = $role === 'company';
    $home = route('lgp.'.$role.'.exceptions');
    $reasonLabels = ['damaged' => 'Damaged parcel', 'label_unreadable' => 'Unreadable label', 'missing' => 'Parcel missing', 'wrong_route' => 'Wrong route', 'other' => 'Other'];
    $resolveUrl = $isCompany ? null : str_replace('/0/', '/__id__/', route('lgp.'.$role.'.exceptions.resolve', 0));

    // What the details dialog shows for each row on this page.
    $items = $exceptions->getCollection()->mapWithKeys(fn ($e) => [(string) $e->id => [
        'id' => $e->id,
        'code' => $e->order?->tracking_number ?? 'Unknown parcel',
        'facility' => $e->facility?->name,
        'reason' => $reasonLabels[$e->reason] ?? ucfirst(str_replace('_', ' ', $e->reason)),
        'details' => $e->details,
        'status' => $e->status,
        'recorded' => $e->created_at?->format('M j, g:i A'),
        'note' => $e->resolution_note,
        'can' => ! $isCompany && $e->status === 'open' && (int) $e->facility_id === (int) ($facility?->id ?? 0),
        'url' => $resolveUrl ? str_replace('__id__', (string) $e->id, $resolveUrl) : null,
    ]])->all();

    $chips = [
        'all' => ['All', (int) $statusCounts->sum()],
        'open' => ['Open', (int) ($statusCounts['open'] ?? 0)],
        'resolved' => ['Resolved', (int) ($statusCounts['resolved'] ?? 0)],
    ];
    $filtered = $status || $term !== '';
@endphp

@push('lg-head')
    @vite('resources/css/logistics/exceptions-theme.css')
@endpush

<x-logistics.shell title="Parcel Exceptions" :role="$role">
    <div class="lgx"
         x-data="{
            items: @js($items),
            dialog: null,
            itemId: null,
            report: { order: @js((string) old('order_id', '')), reason: @js((string) old('reason', '')), details: @js((string) old('details', '')) },
            resolution: @js((string) old('resolution', 'resume')),
            detour: @js((string) old('detour_facility_id', '')),
            note: @js((string) old('resolution_note', '')),
            init() {
                // After a failed save the page reloads: open the same dialog again with what was typed.
                const was = @js((string) old('_dialog', ''));
                if (was === 'report') this.dialog = 'report';
                else if (was.startsWith('item:') && this.items[was.slice(5)]) { this.itemId = was.slice(5); this.dialog = 'item'; }
                this.$watch('dialog', (open) => {
                    const main = document.querySelector('.vd-main');
                    if (main) main.style.overflow = open ? 'hidden' : '';
                });
            },
            get item() { return this.items[this.itemId] || null; },
            openItem(id) { this.itemId = String(id); this.resolution = 'resume'; this.detour = ''; this.note = ''; this.dialog = 'item'; },
            openReport() { this.dialog = 'report'; },
            close() { this.dialog = null; }
         }"
         @keydown.escape.window="close()">

        <x-logistics.flash />

        <header class="lgx-head">
            <div>
                <h1>Parcel exceptions</h1>
                <p>{{ $isCompany ? 'Problems reported on parcels at your hubs and stations. A facility resolves its own exceptions.' : 'Record a problem while a parcel is at your facility. An open exception pauses the parcel’s next scan or trip departure.' }}</p>
            </div>
            @unless ($isCompany)
                <button type="button" class="lgx-btn lgx-btn--primary" @click="openReport()"><x-logistics.icon name="plus" :size="16" /> Report exception</button>
            @endunless
        </header>

        <div class="lgx-chips" role="group" aria-label="Filter exceptions by status">
            @foreach ($chips as $key => [$text, $count])
                @php $value = $key === 'all' ? null : $key; @endphp
                <a href="{{ route('lgp.'.$role.'.exceptions', array_filter(['status' => $value, 'q' => $term])) }}" class="lgx-chip {{ ($status ?? null) === $value ? 'is-active' : '' }}" @if (($status ?? null) === $value) aria-current="true" @endif>
                    {{ $text }} <span class="lgx-chip__count">{{ $count }}</span>
                </a>
            @endforeach
            @if ($filtered)
                <a href="{{ $home }}" class="lgx-chip lgx-chip--clear"><x-logistics.icon name="x" :size="14" /> Clear filters</a>
            @endif
        </div>

        <form method="GET" action="{{ $home }}" class="lgx-toolbar" role="search">
            @if ($status) <input type="hidden" name="status" value="{{ $status }}"> @endif
            <label class="lgx-search">
                <span class="lgx-sr">Search by tracking number</span>
                <x-logistics.icon name="search" :size="18" />
                <input type="search" name="q" value="{{ $term }}" maxlength="100" autocomplete="off" placeholder="Search by tracking number, then press Enter">
            </label>
            <span class="lgx-meta">{{ number_format($exceptions->total()) }} {{ $exceptions->total() === 1 ? 'exception' : 'exceptions' }}</span>
        </form>

        <section class="lgx-card lgx-table-card" aria-label="Exceptions">
            @if ($exceptions->count())
                <div class="lgx-table-wrap">
                    <table class="lgx-table">
                        <thead><tr><th scope="col">Parcel</th><th scope="col">Reason</th><th scope="col">Status</th><th scope="col">Recorded</th><th scope="col"><span class="lgx-sr">Action</span></th></tr></thead>
                        <tbody>
                            @foreach ($exceptions as $exception)
                                @php $row = $items[(string) $exception->id]; @endphp
                                <tr class="lgx-row" @click="openItem({{ $exception->id }})">
                                    <td>
                                        <strong>{{ $row['code'] }}</strong>
                                        <small>{{ $row['facility'] }}</small>
                                    </td>
                                    <td data-label="Reason">
                                        {{ $row['reason'] }}
                                        <small class="lgx-clamp">{{ $row['details'] }}</small>
                                    </td>
                                    <td data-label="Status"><span class="lgx-pill lgx-pill--{{ $exception->status === 'open' ? 'butter' : 'mint' }}">{{ ucfirst($exception->status) }}</span></td>
                                    <td data-label="Recorded">{{ $row['recorded'] }}</td>
                                    <td class="lgx-actions">
                                        <button type="button" class="lgx-btn {{ $row['can'] ? 'lgx-btn--primary' : 'lgx-btn--soft' }} lgx-btn--small" @click.stop="openItem({{ $exception->id }})" aria-label="{{ $row['can'] ? 'Resolve' : 'View details for' }} {{ $row['code'] }}">
                                            {{ $row['can'] ? 'Resolve' : 'View details' }} <x-logistics.icon name="arrow-right" :size="14" class="lgx-nudge" />
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($exceptions->hasPages())
                    <nav class="lgx-pager" aria-label="Pages">
                        <span>Page {{ $exceptions->currentPage() }} of {{ $exceptions->lastPage() }}</span>
                        <div>
                            @if ($exceptions->onFirstPage()) <span class="lgx-btn lgx-btn--soft lgx-btn--small is-disabled" aria-disabled="true">Previous</span>
                            @else <a href="{{ $exceptions->previousPageUrl() }}" class="lgx-btn lgx-btn--soft lgx-btn--small">Previous</a> @endif
                            @if ($exceptions->hasMorePages()) <a href="{{ $exceptions->nextPageUrl() }}" class="lgx-btn lgx-btn--soft lgx-btn--small">Next</a>
                            @else <span class="lgx-btn lgx-btn--soft lgx-btn--small is-disabled" aria-disabled="true">Next</span> @endif
                        </div>
                    </nav>
                @endif
            @elseif ($filtered)
                <div class="lgx-empty" role="status">
                    <span class="lgx-empty__icon lgx-tone-lilac"><x-logistics.icon name="search" :size="26" /></span>
                    <strong>No exceptions match your filters</strong>
                    <p>Check the tracking number, or pick another status.</p>
                    <a href="{{ $home }}" class="lgx-btn lgx-btn--primary">Clear filters</a>
                </div>
            @else
                <div class="lgx-empty" role="status">
                    <span class="lgx-empty__icon lgx-tone-mint"><x-logistics.icon name="check-circle" :size="26" /></span>
                    <strong>No parcel exceptions</strong>
                    <p>{{ $isCompany ? 'Exceptions reported at your hubs and stations appear here.' : 'Report one if a parcel at your facility is damaged, missing or on the wrong route.' }}</p>
                    @unless ($isCompany)
                        <button type="button" class="lgx-btn lgx-btn--primary" @click="openReport()"><x-logistics.icon name="plus" :size="16" /> Report exception</button>
                    @endunless
                </div>
            @endif
        </section>

        @unless ($isCompany)
            {{-- Report a parcel problem --}}
            <div class="lgx-layer" x-show="dialog === 'report'" x-cloak>
                <div class="lgx-backdrop" @click="close()"></div>
                <div class="lgx-dialog" role="dialog" aria-modal="true" aria-labelledby="reportTitle">
                    <header class="lgx-dialog__head">
                        <div>
                            <h2 id="reportTitle">Report exception</h2>
                            <p>The parcel must be at {{ $facility->name }} right now. Its scans stay paused until you resolve this.</p>
                        </div>
                        <button type="button" class="lgx-close" @click="close()" aria-label="Close"><svg class="lgx-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg></button>
                    </header>
                    @if ($eligible->isEmpty())
                        <div class="lgx-dialog__body">
                            <div class="lgx-empty lgx-empty--compact" role="status">
                                <span class="lgx-empty__icon lgx-tone-lilac"><x-logistics.icon name="package" :size="26" /></span>
                                <strong>No parcels at your facility</strong>
                                <p>You can report a problem once a parcel arrives and is still here.</p>
                                <button type="button" class="lgx-btn lgx-btn--soft" @click="close()">Close</button>
                            </div>
                        </div>
                    @else
                        <form method="POST" action="{{ route('lgp.'.$role.'.exceptions.store') }}" class="lgx-dialog__body lgx-form">
                            @csrf
                            <input type="hidden" name="_dialog" value="report">
                            <div class="lgx-field">
                                <label for="exceptionParcel">Parcel at {{ $facility->name }} <span aria-hidden="true">*</span></label>
                                <select id="exceptionParcel" name="order_id" x-model="report.order" required>
                                    <option value="">Choose a parcel</option>
                                    @foreach ($eligible as $order)
                                        <option value="{{ $order->id }}">{{ $order->tracking_number }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="lgx-field">
                                <label for="exceptionReason">Reason <span aria-hidden="true">*</span></label>
                                <select id="exceptionReason" name="reason" x-model="report.reason" required>
                                    <option value="">Choose a reason</option>
                                    @foreach ($reasonLabels as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="lgx-field">
                                <label for="exceptionDetails">What happened? <span aria-hidden="true">*</span></label>
                                <textarea id="exceptionDetails" name="details" rows="3" maxlength="1000" x-model="report.details" placeholder="Describe the problem so the next person can act on it" required></textarea>
                            </div>
                            <footer class="lgx-dialog__foot">
                                <span class="lgx-spacer"></span>
                                <button type="button" class="lgx-btn lgx-btn--soft" @click="close()">Cancel</button>
                                <button type="submit" class="lgx-btn lgx-btn--primary">Record exception</button>
                            </footer>
                        </form>
                    @endif
                </div>
            </div>
        @endunless

        {{-- Exception details, with the Resolve form for whoever manages it --}}
        <div class="lgx-layer" x-show="dialog === 'item'" x-cloak>
            <div class="lgx-backdrop" @click="close()"></div>
            <div class="lgx-dialog" role="dialog" aria-modal="true" aria-labelledby="itemTitle">
                <template x-if="item">
                    <div class="lgx-dialog__inner">
                        <header class="lgx-dialog__head">
                            <div>
                                <h2 id="itemTitle" x-text="item.code"></h2>
                                <p x-text="item.facility"></p>
                            </div>
                            <span class="lgx-pill" :class="item.status === 'open' ? 'lgx-pill--butter' : 'lgx-pill--mint'" x-text="item.status === 'open' ? 'Open' : 'Resolved'"></span>
                            <button type="button" class="lgx-close" @click="close()" aria-label="Close"><svg class="lgx-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg></button>
                        </header>

                        <div class="lgx-dialog__body">
                            <dl class="lgx-facts">
                                <div><dt>Reason</dt><dd x-text="item.reason"></dd></div>
                                <div><dt>Recorded</dt><dd x-text="item.recorded"></dd></div>
                                <div class="is-wide"><dt>What happened</dt><dd x-text="item.details"></dd></div>
                                <div class="is-wide" x-show="item.status === 'resolved' && item.note"><dt>How it was resolved</dt><dd x-text="item.note"></dd></div>
                            </dl>

                            <p class="lgx-hint" x-show="item.status === 'open' && !item.can" x-text="'Open. ' + item.facility + ' resolves this exception.'"></p>

                            @unless ($isCompany)
                                <form method="POST" :action="item.url" class="lgx-form" x-show="item.can" x-cloak>
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="_dialog" :value="'item:' + item.id">
                                    <div class="lgx-field">
                                        <label for="resolution">How will you resolve it? <span aria-hidden="true">*</span></label>
                                        <select id="resolution" name="resolution" x-model="resolution">
                                            <option value="resume">Resume route</option>
                                            <option value="detour">Add a route stop</option>
                                        </select>
                                    </div>
                                    <div class="lgx-field" x-show="resolution === 'detour'" x-cloak>
                                        <label for="detour">Next stop before the planned destination <span aria-hidden="true">*</span></label>
                                        <select id="detour" name="detour_facility_id" x-model="detour" :required="resolution === 'detour'">
                                            <option value="">Choose a facility</option>
                                            @foreach ($detours as $detour)
                                                <option value="{{ $detour->id }}">{{ $detour->name }} · {{ $detour->municipality }}, {{ $detour->province }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="lgx-field">
                                        <label for="resolutionNote">Resolution note <span aria-hidden="true">*</span></label>
                                        <textarea id="resolutionNote" name="resolution_note" rows="3" minlength="5" maxlength="1000" x-model="note" placeholder="Say what you did, so the next person knows" required></textarea>
                                    </div>
                                    <footer class="lgx-dialog__foot">
                                        <span class="lgx-spacer"></span>
                                        <button type="button" class="lgx-btn lgx-btn--soft" @click="close()">Cancel</button>
                                        <button type="submit" class="lgx-btn lgx-btn--primary">Resolve exception</button>
                                    </footer>
                                </form>
                            @endunless

                            <footer class="lgx-dialog__foot" x-show="!item.can">
                                <span class="lgx-spacer"></span>
                                <button type="button" class="lgx-btn lgx-btn--soft" @click="close()">Close</button>
                            </footer>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</x-logistics.shell>
