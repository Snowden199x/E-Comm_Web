@php
    $pageTitle = match ($lane) {
        'incoming' => 'Incoming Parcels',
        'sorting' => 'Parcel Sorting',
        'delivery' => 'Delivery Assignments',
        default => 'Dispatch',
    };

    $subtitle = match ($lane) {
        'incoming' => 'Assign an approved pickup courier to parcels routed to this Main Hub. The rider app records physical pickup.',
        'sorting' => 'Confirm Main Hub arrival and sort parcels along their configured route.',
        'delivery' => 'Confirm parcel receipt at this destination Main Hub and assign an approved local delivery rider.',
        default => 'Manage pickup, Main Hub sorting, destination receipt, and local delivery assignments.',
    };

    // Stage tabs per lane. These only group the parcels already on this page.
    $stageTabs = match ($lane) {
        'incoming' => ['pickup' => 'Pickup requests', 'arriving' => 'On the way to hub'],
        'sorting' => ['to-sort' => 'To sort', 'sorted' => 'Sorted', 'transit' => 'In transit', 'legacy' => 'Legacy route'],
        'delivery' => ['inbound' => 'Inbound', 'at-hub' => 'Ready to assign', 'assigned' => 'Rider assigned', 'out' => 'Out for delivery'],
        default => [],
    };

    $stageOf = function ($order) use ($lane, $center) {
        $atOwnDestination = $order->logistics_center_id === $center->id
            && $order->destination_logistics_center_id === $center->id;

        return match ($lane) {
            'incoming' => $order->status === 'ready_for_pickup' ? 'pickup' : 'arriving',
            'sorting' => match (true) {
                $order->status === 'at_sorting_center' => 'to-sort',
                $order->status === 'sorted' => 'sorted',
                in_array($order->status, ['to_soc5', 'to_soc6'], true) => 'legacy',
                default => 'transit',
            },
            'delivery' => match (true) {
                $order->status === 'sorted' && $atOwnDestination => 'at-hub',
                in_array($order->status, ['sorted', 'in_transit_to_hub'], true) => 'inbound',
                $order->status === 'at_destination_hub' => 'at-hub',
                $order->status === 'assigned_to_rider' => 'assigned',
                $order->status === 'out_for_delivery' => 'out',
                default => 'inbound',
            },
            default => (string) $order->status,
        };
    };

    $stageCounts = $orders->getCollection()->groupBy($stageOf)->map->count();
    $areas = $orders->getCollection()->pluck('shipping_city')->filter()->unique()->sort()->values();
    $initial = fn (?string $name) => mb_strtoupper(mb_substr($name ?: '?', 0, 1));
@endphp
<x-logistics.layout :title="$pageTitle">
    <div class="lg-page" data-lg-filter>

        <div class="lg-page-head">
            <div>
                <h1>{{ $pageTitle }}</h1>
                <p>{{ $subtitle }}</p>
            </div>
            @if ($lane === 'incoming')
                <div class="lg-page-head__actions">
                    <a href="{{ route('logistics.parcel-sorting') }}" class="lg-btn lg-btn--outline">Go to sorting <x-logistics.icon name="arrow-right" :size="16" /></a>
                </div>
            @elseif ($lane === 'sorting')
                <div class="lg-page-head__actions">
                    <a href="{{ route('logistics.delivery-assignments') }}" class="lg-btn lg-btn--outline">Go to delivery <x-logistics.icon name="arrow-right" :size="16" /></a>
                </div>
            @endif
        </div>

        {{-- Riders at this hub, with their load on this page --}}
        @if ($lane === 'delivery' && $couriers->isNotEmpty())
            <section class="lg-card" aria-labelledby="lgRidersLoad">
                <div class="lg-card__head">
                    <div>
                        <h2 class="lg-section-title" id="lgRidersLoad">Riders at this hub</h2>
                        <p class="lg-section-sub">Parcels each rider holds among the {{ $orders->count() }} shown on this page.</p>
                    </div>
                </div>
                <div class="lg-card__body">
                    <div class="lg-chiplist">
                        @foreach ($couriers as $courier)
                            @php
                                $load = $orders->getCollection()
                                    ->where('delivery_courier_id', $courier->user_id)
                                    ->whereIn('status', ['assigned_to_rider', 'out_for_delivery'])
                                    ->count();
                            @endphp
                            <div class="lg-chip">
                                <span class="lg-avatar lg-avatar--sm" aria-hidden="true">{{ $initial($courier->user->name) }}</span>
                                <span>
                                    <strong>{{ $courier->user->name }}</strong>
                                    <small>{{ $courier->vehicle_type }} &middot; {{ $load }} {{ \Illuminate\Support\Str::plural('parcel', $load) }}</small>
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        {{-- Filters --}}
        @if ($orders->count() > 0)
            <div class="lg-toolbar">
                <div class="lg-search">
                    <x-logistics.icon name="search" :size="18" />
                    <input type="search" class="lg-input" data-lg-search placeholder="Search this page by order, tracking, seller or address" aria-label="Search parcels on this page">
                </div>

                @if ($areas->count() > 1)
                    <select class="lg-select" data-lg-area aria-label="Filter by delivery area">
                        <option value="">All areas</option>
                        @foreach ($areas as $area)
                            <option value="{{ \Illuminate\Support\Str::lower($area) }}">{{ $area }}</option>
                        @endforeach
                    </select>
                @endif

                <p class="lg-toolbar__meta">Showing <strong data-lg-visible>{{ $orders->count() }}</strong> of {{ $orders->count() }} on this page</p>
            </div>

            @if ($stageTabs)
                <div class="lg-tabs" role="group" aria-label="Filter by stage">
                    <button type="button" class="lg-tab" data-lg-tab="all" aria-pressed="true">All <span class="lg-tab__count">{{ $orders->count() }}</span></button>
                    @foreach ($stageTabs as $key => $label)
                        <button type="button" class="lg-tab" data-lg-tab="{{ $key }}" aria-pressed="false">{{ $label }} <span class="lg-tab__count">{{ $stageCounts[$key] ?? 0 }}</span></button>
                    @endforeach
                </div>
            @endif
        @endif

        <div class="lg-parcel-list">
            @forelse ($orders as $order)
                @php
                    $seller = $order->seller;
                    $sellerDetail = $seller?->sellerDetail;
                    $pickupAddress = implode(', ', array_filter([
                        $sellerDetail?->house_no,
                        $sellerDetail?->street,
                        $sellerDetail?->barangay,
                        $sellerDetail?->municipality,
                        $sellerDetail?->province,
                    ]));
                    $searchText = \Illuminate\Support\Str::lower(implode(' ', array_filter([
                        $order->number,
                        $order->tracking_number,
                        $sellerDetail?->business_name ?? $seller?->name,
                        $order->shipping_address,
                        $order->shipping_city,
                        $order->courier?->name,
                        $order->deliveryCourier?->name,
                    ])));
                    $legacy = in_array($order->status, ['to_soc5', 'to_soc6'], true);
                @endphp
                <article class="lg-parcel lg-rise" style="--i: {{ min($loop->index, 8) }}"
                         data-lg-item data-stage="{{ $stageOf($order) }}" data-area="{{ \Illuminate\Support\Str::lower((string) $order->shipping_city) }}" data-search="{{ $searchText }}">
                    <header class="lg-parcel__head">
                        <div class="lg-parcel__id">
                            <span class="lg-parcel__chip"><x-logistics.icon name="package" :size="20" /></span>
                            <div>
                                <h2>{{ $order->number }}</h2>
                                <small>Tracking {{ $order->tracking_number }}</small>
                            </div>
                        </div>
                        <x-logistics.status :status="$order->status" />
                    </header>

                    <div class="lg-parcel__body">
                        <x-logistics.progress :status="$order->status" />

                        <dl class="lg-parcel__grid">
                            <div>
                                <dt>Pickup from</dt>
                                <dd>{{ $sellerDetail?->business_name ?? $seller?->name }}</dd>
                                @if ($pickupAddress)
                                    <dd>{{ $pickupAddress }}</dd>
                                @endif
                            </div>
                            <div>
                                <dt>Deliver to</dt>
                                <dd>{{ $order->shipping_address }}</dd>
                                @if ($order->shipping_city)
                                    <dd>{{ $order->shipping_city }}@if ($order->shipping_province), {{ $order->shipping_province }}@endif</dd>
                                @endif
                            </div>
                            <div>
                                <dt>Destination hub</dt>
                                <dd>{{ $order->destinationLogisticsCenter?->business_name ?? 'Unresolved: no unique approved center for this destination' }}</dd>
                            </div>
                            <div>
                                <dt>Pickup courier</dt>
                                <dd>{{ $order->courier?->name ?? 'Not assigned' }}</dd>
                            </div>
                            @if ($order->delivery_courier_id)
                                <div>
                                    <dt>Delivery rider</dt>
                                    <dd>{{ $order->deliveryCourier?->name ?? 'Unavailable' }}</dd>
                                </div>
                            @endif
                            @if ($order->linehaul_rider_id)
                                <div>
                                    <dt>Truck Rider</dt>
                                    <dd>{{ $order->linehaulRider?->name ?? 'Unavailable' }}</dd>
                                </div>
                            @endif
                            @if ($order->status === 'sorted' && $order->logistics_center_id !== $order->destination_logistics_center_id)
                                <div>
                                    <dt>Planned next stop</dt>
                                    <dd>{{ $order->nextRouteCheckpoint ? $order->nextRouteCheckpoint->name.' ('.$order->nextRouteCheckpoint->code.')' : ($order->routePlan ? ($order->destinationLogisticsCenter?->business_name ?? 'Destination Main Hub') : 'No active route plan configured') }}</dd>
                                    @if ($order->routePlan)
                                        <dd>Route: {{ $order->routePlan->stops->map(fn ($stop) => $stop->checkpoint?->code)->filter()->implode(' → ') ?: 'Direct to Main Hub' }} → {{ $order->destinationLogisticsCenter?->business_name }}</dd>
                                    @endif
                                </div>
                            @endif
                        </dl>
                    </div>

                    <footer class="lg-parcel__foot">
                        @if ($order->status === 'ready_for_pickup' && ! $order->courier_id)
                            <form method="POST" action="{{ route('logistics.dispatch.courier', $order) }}" class="lg-action" data-lg-loading>
                                @csrf
                                <div class="lg-field">
                                    <label for="courier-{{ $order->id }}">Pickup courier</label>
                                    <select id="courier-{{ $order->id }}" name="courier_id" required class="lg-select">
                                        <option value="">Select approved courier</option>
                                        @foreach ($couriers as $courier)
                                            <option value="{{ $courier->user_id }}">{{ $courier->user->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button type="submit" class="lg-btn" @disabled($couriers->isEmpty())>Assign courier</button>
                            </form>
                            @if ($couriers->isEmpty())
                                <p class="lg-note"><x-logistics.icon name="info" :size="16" /><span>No approved rider is linked to this hub. <a href="{{ route('logistics.dashboard') }}#rider-applications">Open Rider Management</a> to review applications.</span></p>
                            @endif
                        @elseif ($order->status === 'ready_for_pickup')
                            <p class="lg-note"><x-logistics.icon name="info" :size="16" /><span>Assigned rider: {{ $order->courier?->name }}. Ask the seller to print the shipping label, then scan its QR or barcode in the rider app at pickup.</span></p>
                        @elseif ($order->status === 'picked_up')
                            <form method="POST" action="{{ route('logistics.dispatch.arrive', $order) }}" class="lg-action" data-lg-loading>
                                @csrf
                                <button type="submit" class="lg-btn">Confirm arrival at center</button>
                            </form>
                        @elseif ($order->status === 'at_sorting_center')
                            <form method="POST" action="{{ route('logistics.dispatch.sort', $order) }}" class="lg-action" data-lg-loading>
                                @csrf
                                <button type="submit" class="lg-btn">Mark sorted</button>
                            </form>
                        @elseif ($order->status === 'sorted' && $order->destination_logistics_center_id !== $center->id)
                            @if ($order->destination_logistics_center_id)
                                <p class="lg-note"><x-logistics.icon name="info" :size="16" /><span>Sorted at this Main Hub. Planned next checkpoint: {{ $order->nextRouteCheckpoint ? $order->nextRouteCheckpoint->name.' ('.$order->nextRouteCheckpoint->code.')' : ($order->routePlan ? ($order->destinationLogisticsCenter?->business_name ?? 'destination Main Hub') : 'No active route plan configured for this Main Hub pair') }}. SH names are virtual route checkpoints; the configured plan determines which ones this parcel follows.</span></p>
                                @if ($order->routePlan)
                                    @if ($order->linehaul_rider_id)
                                        <p class="lg-note"><x-logistics.icon name="truck" :size="16" /><span>Truck Rider assigned: {{ $order->linehaulRider?->name ?? 'Unavailable' }}. The route shown is planned; SH arrival and sorting still require the separate SH scanner.</span></p>
                                    @endif
                                    <form method="POST" action="{{ route('logistics.dispatch.linehaul-rider', $order) }}" class="lg-action" data-lg-loading>
                                        @csrf
                                        <div class="lg-field">
                                            <label for="linehaul-rider-{{ $order->id }}">{{ $order->linehaul_rider_id ? 'Reassign Truck Rider' : 'Truck Rider' }}</label>
                                            <select id="linehaul-rider-{{ $order->id }}" name="rider_id" required class="lg-select">
                                                <option value="">Select approved Truck Rider</option>
                                                @foreach ($truckRiders as $truckRider)
                                                    <option value="{{ $truckRider->user_id }}" @selected((int) $order->linehaul_rider_id === (int) $truckRider->user_id)>{{ $truckRider->user->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <button type="submit" class="lg-btn" @disabled($truckRiders->isEmpty())>{{ $order->linehaul_rider_id ? 'Update assignment' : 'Assign Truck Rider' }}</button>
                                    </form>
                                    @if ($truckRiders->isEmpty())
                                        <p class="lg-note"><x-logistics.icon name="info" :size="16" /><span>No approved Truck Rider is linked to this Main Hub. Review applications in Rider Management.</span></p>
                                    @endif
                                @else
                                    <p class="lg-note lg-note--warn"><x-logistics.icon name="alert" :size="16" /><span>No configured route plan is available. Configure and activate a route before assigning a Truck Rider.</span></p>
                                @endif
                            @else
                                <p class="lg-note lg-note--warn"><x-logistics.icon name="alert" :size="16" /><span>Destination hub is unresolved. An approved center must cover {{ $order->shipping_city ?: 'the buyer city' }}, {{ $order->shipping_province ?: 'the buyer province' }} before transfer.</span></p>
                            @endif
                        @elseif ($legacy)
                            <p class="lg-note lg-note--warn"><x-logistics.icon name="alert" :size="16" /><span>This parcel is in a legacy SOC route state. It does not confirm arrival at an SH locality. Do not treat it as an SH scan or sorting update.</span></p>
                        @elseif ($order->status === 'in_transit_to_hub' && $order->destination_logistics_center_id === $center->id)
                            <form method="POST" action="{{ route('logistics.dispatch.receive', $order) }}" class="lg-action" data-lg-loading>
                                @csrf
                                <button type="submit" class="lg-btn">Confirm receipt at hub</button>
                            </form>
                        @elseif (($order->status === 'sorted' && $order->logistics_center_id === $center->id && $order->destination_logistics_center_id === $center->id) || $order->status === 'at_destination_hub')
                            <form method="POST" action="{{ route('logistics.dispatch.delivery-rider', $order) }}" class="lg-action" data-lg-loading>
                                @csrf
                                <div class="lg-field">
                                    <label for="delivery-courier-{{ $order->id }}">Delivery rider</label>
                                    <select id="delivery-courier-{{ $order->id }}" name="courier_id" required class="lg-select">
                                        <option value="">Select approved rider</option>
                                        @foreach ($couriers as $courier)
                                            <option value="{{ $courier->user_id }}">{{ $courier->user->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button type="submit" class="lg-btn" @disabled($couriers->isEmpty())>Assign delivery rider</button>
                            </form>
                            @if ($couriers->isEmpty())
                                <p class="lg-note"><x-logistics.icon name="info" :size="16" /><span>Approve a rider for this center to enable assignment.</span></p>
                            @endif
                        @else
                            <p class="lg-note"><x-logistics.icon name="clock" :size="16" /><span>{{ match ($order->status) {
                                'in_transit_to_hub' => 'Waiting for the destination hub to confirm receipt.',
                                'out_for_delivery' => 'The assigned delivery rider scanned this parcel for delivery.',
                                default => 'Delivery rider assigned. Waiting for the rider scan.',
                            } }}</span></p>
                        @endif
                    </footer>
                </article>
            @empty
                <div class="lg-card">
                    <div class="lg-empty">
                        <span class="lg-empty__icon"><x-logistics.icon name="package" :size="30" /></span>
                        <h3>No parcels in this section right now</h3>
                        <p>New parcels appear here as they reach this stage.</p>
                        @if ($lane === 'incoming')
                            <p>Ready orders appear here when the seller's location matches this approved hub. If an older order is missing, ask the web operator to run <code>php artisan orders:route-ready</code>.</p>
                        @endif
                    </div>
                </div>
            @endforelse

            {{-- Shown by the page filters when nothing matches --}}
            <div class="lg-card" data-lg-empty hidden>
                <div class="lg-empty">
                    <span class="lg-empty__icon"><x-logistics.icon name="search" :size="30" /></span>
                    <h3>No parcels match your filters</h3>
                    <p>Try a different stage or area, or clear the search. Filters only cover the parcels loaded on this page.</p>
                </div>
            </div>
        </div>

        {{ $orders->links('logistics.partials.pagination') }}
    </div>
</x-logistics.layout>