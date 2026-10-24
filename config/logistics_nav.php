<?php

/*
 * Sidebar navigation for the three logistics portals, in one place.
 * Read by resources/views/components/logistics/shell.blade.php.
 *
 * Item keys:
 *   label   text in the sidebar
 *   icon    a name from resources/views/components/logistics/icon.blade.php
 *   route   a NAMED route that must exist (the shell calls route() on it)
 *   active  optional routeIs() pattern(s); defaults to the route itself.
 *           Use 'lgp.company.hubs*' style patterns when a page has sub-pages.
 *   badge   optional count dot (only include when the count comes from live data)
 *
 * To add a page: register its route, then add one line here.
 */

return [

    'company' => [
        ['label' => null, 'items' => [
            ['label' => 'Dashboard', 'icon' => 'home', 'route' => 'lgp.company'],
        ]],
        ['label' => 'Network', 'items' => [
            ['label' => 'Province Hubs', 'icon' => 'map-pin', 'route' => 'lgp.company.hubs', 'active' => 'lgp.company.hubs*'],
            ['label' => 'Network Overview', 'icon' => 'layers', 'route' => 'lgp.company.network'],
        ]],
        ['label' => 'Operations', 'items' => [
            ['label' => 'Parcel Tracking', 'icon' => 'search', 'route' => 'lgp.company.tracking'],
            ['label' => 'Manual Handoffs', 'icon' => 'truck', 'route' => 'lgp.company.handoffs'],
            ['label' => 'Parcel Exceptions', 'icon' => 'alert', 'route' => 'lgp.company.exceptions'],
            ['label' => 'Messages', 'icon' => 'message', 'route' => 'lgp.company.messages'],
            ['label' => 'Reports', 'icon' => 'bar-chart', 'route' => 'lgp.company.reports'],
        ]],
        ['label' => 'System', 'items' => [
            ['label' => 'Account Management', 'icon' => 'shield', 'route' => 'lgp.company.account'],
        ]],
    ],

    'province' => [
        ['label' => null, 'items' => [
            ['label' => 'Dashboard', 'icon' => 'home', 'route' => 'lgp.hub.province'],
        ]],
        ['label' => 'Network', 'items' => [
            ['label' => 'Station Hubs', 'icon' => 'building', 'route' => 'lgp.province.stations', 'active' => 'lgp.province.stations*'],
        ]],
        ['label' => 'Fleet', 'items' => [
            ['label' => 'Transport Drivers', 'icon' => 'users', 'route' => 'lgp.province.drivers'],
            ['label' => 'Vehicles', 'icon' => 'truck', 'route' => 'lgp.province.vehicles'],
        ]],
        ['label' => 'Parcels', 'items' => [
            ['label' => 'Parcel Operations', 'icon' => 'inbox', 'route' => 'lgp.province.parcels'],
            ['label' => 'Parcel Exceptions', 'icon' => 'alert', 'route' => 'lgp.province.exceptions'],
            ['label' => 'Transport Trips', 'icon' => 'calendar', 'route' => 'lgp.province.trips', 'active' => 'lgp.province.trips*'],
            ['label' => 'Parcel Tracking', 'icon' => 'search', 'route' => 'lgp.province.tracking'],
        ]],
        ['label' => 'Business', 'items' => [
            ['label' => 'Messages', 'icon' => 'message', 'route' => 'lgp.province.messages'],
            ['label' => 'Reports', 'icon' => 'bar-chart', 'route' => 'lgp.province.reports'],
        ]],
        ['label' => 'System', 'items' => [
            ['label' => 'Account Management', 'icon' => 'shield', 'route' => 'lgp.province.account'],
        ]],
    ],

    'station' => [
        ['label' => null, 'items' => [
            ['label' => 'Dashboard', 'icon' => 'home', 'route' => 'lgp.hub.station'],
        ]],
        ['label' => 'Pickups and deliveries', 'items' => [
            ['label' => 'Pickup Requests', 'icon' => 'package', 'route' => 'lgp.station.pickups'],
            ['label' => 'Task Assignment', 'icon' => 'bike', 'route' => 'lgp.station.tasks'],
            ['label' => 'Delivery Queue', 'icon' => 'map-pin', 'route' => 'lgp.station.delivery-queue'],
        ]],
        ['label' => 'Parcels', 'items' => [
            ['label' => 'Incoming Parcels', 'icon' => 'inbox', 'route' => 'lgp.station.incoming'],
            ['label' => 'Sorting', 'icon' => 'layers', 'route' => 'lgp.station.sorting'],
            ['label' => 'Outgoing Transfers', 'icon' => 'truck', 'route' => 'lgp.station.outgoing'],
            ['label' => 'Parcel Exceptions', 'icon' => 'alert', 'route' => 'lgp.station.exceptions'],
            ['label' => 'Parcel Tracking', 'icon' => 'search', 'route' => 'lgp.station.tracking'],
        ]],
        ['label' => 'People', 'items' => [
            ['label' => 'Riders', 'icon' => 'users', 'route' => 'lgp.station.riders'],
        ]],
        ['label' => 'Business', 'items' => [
            ['label' => 'Messages', 'icon' => 'message', 'route' => 'lgp.station.messages'],
            ['label' => 'Reports', 'icon' => 'bar-chart', 'route' => 'lgp.station.reports'],
        ]],
        ['label' => 'System', 'items' => [
            ['label' => 'Account Management', 'icon' => 'shield', 'route' => 'lgp.station.account'],
        ]],
    ],

];
