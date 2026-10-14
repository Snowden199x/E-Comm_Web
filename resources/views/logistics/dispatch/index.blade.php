@php
    $heading = match ($lane) {
        'incoming' => 'Incoming Parcels', 'sorting' => 'Parcel Sorting',
        'delivery' => 'Delivery Assignments', default => 'Dispatch',
    };
@endphp
<x-logistics.layout :title="$heading">
    <div class="lg-page">
        <div class="lg-page-head">
            <div><h1>{{ $heading }}</h1><p>{{ $center->coverage_province ?: $center->province }} network · {{ $center->business_name }}</p></div>
            <a href="{{ route('logistics.facilities.index') }}" class="lg-btn lg-btn--outline">Hubs & Stations</a>
        </div>

        <form method="GET" class="lg-dispatch-filters" aria-label="Filter parcels">
            <div class="lg-field"><label for="dispatchSearch">Search</label><input id="dispatchSearch" name="q" class="lg-input" value="{{ $filters['q'] ?? '' }}" placeholder="Tracking, seller, address"></div>
            <div class="lg-field"><label for="dispatchStatus">Status</label><select id="dispatchStatus" name="status" class="lg-select"><option value="">All statuses</option>@foreach ($availableStatuses as $status)<option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ \App\Models\Ecommerce\Order::STATUSES[$status] ?? $status }}</option>@endforeach</select></div>
            <div class="lg-field"><label for="dispatchArea">Area</label><select id="dispatchArea" name="area" class="lg-select"><option value="">All areas</option>@foreach ($areas as $area)<option value="{{ $area->shipping_city_code }}" @selected(($filters['area'] ?? '') === $area->shipping_city_code)>{{ $area->shipping_city }}</option>@endforeach</select></div>
            <button type="submit" class="lg-btn">Apply filters</button>
        </form>

        @if ($stageCounts->isNotEmpty())
            <nav class="lg-dispatch-stages" aria-label="Parcel stages"><a href="{{ request()->fullUrlWithQuery(['stage' => 'all', 'page' => null]) }}" @if($selectedStage === 'all') aria-current="page" @endif>All</a>@foreach ($stageCounts as $stage => $count)<a href="{{ request()->fullUrlWithQuery(['stage' => $stage, 'page' => null]) }}" @if($selectedStage === $stage) aria-current="page" @endif>{{ ucfirst(str_replace('-', ' ', $stage)) }} ({{ $count }})</a>@endforeach</nav>
        @endif

        <section class="lg-dispatch-list" aria-label="Parcels">
            @forelse ($orders as $order)
                @php
                    $isOrigin = (int) $order->logistics_center_id === (int) $center->id;
                    $isDestination = (int) $order->destination_logistics_center_id === (int) $center->id;
                    $eligibleStations = $routeStations->filter(fn ($station) => in_array($station->logistics_center_id, [$center->id, $order->destination_logistics_center_id], true));
                    $hasNewRoute = $order->facilityStops->isNotEmpty();
                @endphp
                <article class="lg-card lg-dispatch-order">
                    <div class="lg-dispatch-order__head"><div><h2>{{ $order->number }}</h2><p>{{ $order->tracking_number }} · {{ $order->seller?->name ?: 'Seller' }}</p></div><span>{{ $order->status_label }}</span></div>
                    <div class="lg-dispatch-order__meta"><span>Destination: {{ $order->shipping_city ?: 'Unknown city' }}, {{ $order->shipping_province ?: 'Unknown province' }}</span><span>Pickup rider: {{ $order->courier?->name ?: 'Unassigned' }}</span><span>Delivery rider: {{ $order->deliveryCourier?->name ?: 'Unassigned' }}</span></div>
                    @if ($order->facilityStops->isNotEmpty())
                        <p class="lg-dispatch-route"><strong>Route:</strong> {{ $order->facilityStops->map(fn ($stop) => $stop->facility?->name)->implode(' → ') }}</p>
                    @endif
                    <div class="lg-dispatch-actions">
                        @if ($isOrigin && $order->status === 'ready_for_pickup' && $order->pickup_request_status === 'pending')
                            <form method="POST" action="{{ route('logistics.dispatch.pickup-verify', $order) }}">@csrf<button class="lg-btn lg-btn--sm">Verify pickup</button></form>
                            <form method="POST" action="{{ route('logistics.dispatch.pickup-decline', $order) }}" class="lg-dispatch-inline">@csrf<input name="reason" class="lg-input" maxlength="500" required placeholder="Reason parcel is not ready"><button class="lg-btn lg-btn--outline lg-btn--sm">Return to seller</button></form>
                        @elseif ($isOrigin && $order->status === 'ready_for_pickup' && $order->pickup_request_status === 'verified' && ! $order->courier_id)
                            <form method="POST" action="{{ route('logistics.dispatch.courier', $order) }}" class="lg-dispatch-inline">@csrf<select name="courier_id" class="lg-select" required><option value="">Pickup rider</option>@foreach ($localRiders as $rider)<option value="{{ $rider->user_id }}">{{ $rider->user?->name }} · {{ $rider->vehicle_type }}</option>@endforeach</select><button class="lg-btn lg-btn--sm">Assign</button></form>
                        @elseif ($isOrigin && $order->status === 'picked_up')
                            <a href="{{ route('logistics.facilities.show', $center->mainHub) }}" class="lg-btn lg-btn--outline lg-btn--sm">Scan Main Hub arrival</a>
                        @elseif ($isOrigin && $order->status === 'at_sorting_center')
                            <form method="POST" action="{{ route('logistics.dispatch.sort', $order) }}" class="lg-dispatch-sort">@csrf
                                <p>Choose actual station stops in travel order. Leave blank for direct Main Hub delivery.</p>
                                @for ($position = 1; $position <= 3; $position++)
                                    <label>Stop {{ $position }}<select name="station_ids[]" class="lg-select"><option value="">No station</option>@foreach ($eligibleStations as $station)<option value="{{ $station->id }}">{{ $station->name }} · {{ $station->municipality }}, {{ $station->province }}</option>@endforeach</select></label>
                                @endfor
                                <button type="submit" class="lg-btn lg-btn--sm">Complete sorting</button>
                            </form>
                        @elseif ($isOrigin && $order->status === 'sorted' && ! $isDestination && ! $order->linehaul_rider_id)
                            <form method="POST" action="{{ route('logistics.dispatch.linehaul-rider', $order) }}" class="lg-dispatch-inline">@csrf<select name="rider_id" class="lg-select" required><option value="">Truck Rider</option>@foreach ($truckRiders as $rider)<option value="{{ $rider->user_id }}">{{ $rider->user?->name }}</option>@endforeach</select><button class="lg-btn lg-btn--sm">Assign linehaul</button></form>
                        @endif

                        @if ($isDestination && ($order->status === 'at_destination_hub' || ($isOrigin && $order->status === 'sorted' && ! $hasNewRoute)) && ! $order->delivery_courier_id)
                            <form method="POST" action="{{ route('logistics.dispatch.delivery-rider', $order) }}" class="lg-dispatch-inline">@csrf<select name="courier_id" class="lg-select" required><option value="">Delivery rider</option>@foreach ($localRiders as $rider)<option value="{{ $rider->user_id }}">{{ $rider->user?->name }} · {{ $rider->vehicle_type }}</option>@endforeach</select><button class="lg-btn lg-btn--sm">Assign delivery</button></form>
                        @elseif ($isDestination && $order->status === 'in_transit_to_hub' && ! $hasNewRoute)
                            <form method="POST" action="{{ route('logistics.dispatch.receive', $order) }}">@csrf<button class="lg-btn lg-btn--outline lg-btn--sm">Confirm legacy hub receipt</button></form>
                        @endif
                        @if ($hasNewRoute && in_array($order->status, ['sorted', 'in_transit_to_hub', 'at_destination_hub'], true))
                            <a href="{{ route('logistics.facilities.index') }}" class="lg-btn lg-btn--outline lg-btn--sm">Open route facility scanner</a>
                        @endif
                    </div>
                </article>
            @empty
                <div class="lg-card lg-empty"><h2>No parcels found</h2><p>Try another stage or filter. New Seller pickup requests appear here when their province is covered.</p></div>
            @endforelse
        </section>
        {{ $orders->links() }}
    </div>
</x-logistics.layout>
