@php
    // Groups only describe the parcels loaded on this page.
    $groupTabs = [
        'hub' => 'At hub',
        'transit' => 'In transit',
        'out' => 'Out for delivery',
        'done' => 'Delivered',
        'issue' => 'Needs attention',
    ];

    $groupOf = fn ($order) => match ($order->status) {
        'ready_for_pickup', 'picked_up', 'at_sorting_center', 'sorted', 'to_soc5', 'to_soc6' => 'hub',
        'in_transit_to_hub', 'at_destination_hub', 'assigned_to_rider' => 'transit',
        'out_for_delivery' => 'out',
        'delivered' => 'done',
        'delivery_failed' => 'issue',
        default => 'hub',
    };

    $groupCounts = $orders->getCollection()->groupBy($groupOf)->map->count();
@endphp
<x-logistics.layout title="Delivery Monitoring">
    <div class="lg-page" data-lg-filter>

        <div class="lg-page-head">
            <div>
                <h1>Delivery Monitoring</h1>
                <p>Parcels linked to this logistics center, including current mobile rider updates.</p>
            </div>
            <div class="lg-page-head__actions">
                <a href="{{ route('logistics.delivery-assignments') }}" class="lg-btn lg-btn--outline">
                    <x-logistics.icon name="truck" :size="18" /> Delivery assignments
                </a>
            </div>
        </div>

        @if ($orders->count() > 0)
            <div class="lg-toolbar">
                <div class="lg-search">
                    <x-logistics.icon name="search" :size="18" />
                    <input type="search" class="lg-input" data-lg-search placeholder="Search this page by order or tracking number" aria-label="Search parcels on this page">
                </div>
                <p class="lg-toolbar__meta">Showing <strong data-lg-visible>{{ $orders->count() }}</strong> of {{ $orders->count() }} on this page</p>
            </div>

            <div class="lg-tabs" role="group" aria-label="Filter by delivery stage">
                <button type="button" class="lg-tab" data-lg-tab="all" aria-pressed="true">All <span class="lg-tab__count">{{ $orders->count() }}</span></button>
                @foreach ($groupTabs as $key => $label)
                    <button type="button" class="lg-tab" data-lg-tab="{{ $key }}" aria-pressed="false">{{ $label }} <span class="lg-tab__count">{{ $groupCounts[$key] ?? 0 }}</span></button>
                @endforeach
            </div>
        @endif

        @if ($orders->count() > 0)
            <section class="lg-card" aria-label="Active parcels">
                <div class="lg-table-wrap">
                    <table class="lg-table lg-table--stack">
                        <thead>
                            <tr>
                                <th scope="col">Order</th>
                                <th scope="col">Route</th>
                                <th scope="col">Status</th>
                                <th scope="col">Assigned operator</th>
                                <th scope="col">Last update</th>
                                <th scope="col"><span class="sr-only">Open</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($orders as $order)
                                @php
                                    $stops = $order->routePlan
                                        ? $order->routePlan->stops->map(fn ($stop) => $stop->checkpoint)->filter()
                                        : collect();
                                @endphp
                                <tr data-lg-item data-stage="{{ $groupOf($order) }}" data-search="{{ \Illuminate\Support\Str::lower($order->number.' '.$order->tracking_number) }}">
                                    <td data-label="Order">
                                        <div class="lg-cell-title">{{ $order->number }}</div>
                                        <div class="lg-cell-sub">{{ $order->tracking_number }}</div>
                                    </td>

                                    <td data-label="Route">
                                        <div class="lg-route">
                                            <div class="lg-route__hubs">
                                                <span>{{ $order->logisticsCenter?->business_name ?? 'Origin unavailable' }}</span>
                                                <x-logistics.icon name="arrow-right" :size="14" />
                                                <span>{{ $order->destinationLogisticsCenter?->business_name ?? 'Destination unresolved' }}</span>
                                            </div>
                                            @if ($order->routePlan)
                                                <div class="lg-route__stops" aria-label="Planned route">
                                                    @forelse ($stops as $checkpoint)
                                                        <span @class(['lg-stop', 'is-next' => $order->nextRouteCheckpoint && $order->nextRouteCheckpoint->id === $checkpoint->id]) title="{{ $checkpoint->name }}">{{ $checkpoint->code }} · {{ $checkpoint->name }}</span>
                                                    @empty
                                                        <span class="lg-stop">Direct to destination Main Hub</span>
                                                    @endforelse
                                                </div>
                                                @if ($order->nextRouteCheckpoint)
                                                    <div class="lg-cell-sub">Next virtual waypoint: {{ $order->nextRouteCheckpoint->code }} · {{ $order->nextRouteCheckpoint->name }}</div>
                                                @endif
                                            @endif
                                        </div>
                                    </td>

                                    <td data-label="Status">
                                        <x-logistics.status :status="$order->status" />
                                        <x-logistics.progress :status="$order->status" compact style="margin-top: 12px;" />
                                    </td>

                                    <td data-label="Assigned operator">
                                        @if ($order->status === 'out_for_delivery' || $order->delivery_courier_id)
                                            <div>{{ $order->deliveryCourier?->name ?? 'Delivery operator unavailable' }}</div>
                                            <div class="lg-cell-sub">Delivery</div>
                                        @elseif ($order->courier_id)
                                            <div>{{ $order->courier?->name ?? 'Pickup operator unavailable' }}</div>
                                            <div class="lg-cell-sub">Pickup / origin transfer</div>
                                        @else
                                            <span class="lg-muted">Not assigned</span>
                                        @endif
                                    </td>

                                    <td data-label="Last update" style="white-space: nowrap;">
                                        <div>{{ $order->updated_at->format('M j, Y') }}</div>
                                        <div class="lg-cell-sub">{{ $order->updated_at->format('g:i A') }}</div>
                                    </td>

                                    <td style="text-align: right; white-space: nowrap;">
                                        <a class="lg-btn lg-btn--outline lg-btn--sm" href="{{ route('logistics.delivery-assignments') }}">Open assignments</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <div class="lg-card" data-lg-empty hidden>
                <div class="lg-empty">
                    <span class="lg-empty__icon"><x-logistics.icon name="search" :size="30" /></span>
                    <h3>No parcels match your filters</h3>
                    <p>Try another stage or clear the search. Filters only cover the parcels loaded on this page.</p>
                </div>
            </div>
        @else
            <div class="lg-card">
                <div class="lg-empty">
                    <span class="lg-empty__icon"><x-logistics.icon name="map-pin" :size="30" /></span>
                    <h3>No active parcel updates</h3>
                    <p>Parcels routed through this center show up here with their latest status and assigned operator.</p>
                </div>
            </div>
        @endif

        {{ $orders->links('logistics.partials.pagination') }}
    </div>
</x-logistics.layout>