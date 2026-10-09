<?php

/*
 * PREVIEW ONLY - hardcoded data so the team can click through the new screens.
 * Delete this whole file (and its require line in routes/web.php) once the real
 * controllers exist. Open: /logistics-preview/company
 */

use Illuminate\Support\Facades\Route;

Route::prefix('logistics-preview')->name('lgp.')->group(function () {

    // ---- Company portal ---------------------------------------------------
    Route::get('/company', fn () => view('logistics.company.dashboard', [
        'stats' => [
            ['Hubs', '9', 'map-pin', 'blue'],
            ['Active riders', '64', 'users', 'green'],
            ['Parcels in network', '1,284', 'package', 'amber'],
            ['Delivered today', '312', 'check-circle', 'green'],
        ],
        'hubs' => [
            ['name' => 'Laguna Hub',        'place' => 'Santa Rosa, Laguna',   'type' => 'Province hub', 'manager' => 'Ana Reyes',    'riders' => 18, 'parcels' => 412, 'status' => 'Active'],
            ['name' => 'Santa Cruz Station', 'place' => 'Santa Cruz, Laguna',   'type' => 'Station',      'manager' => 'Mark Dela Cruz', 'riders' => 9,  'parcels' => 96,  'status' => 'Active'],
            ['name' => 'Pagsanjan Station',  'place' => 'Pagsanjan, Laguna',    'type' => 'Station',      'manager' => 'Liza Santos',  'riders' => 6,  'parcels' => 41,  'status' => 'Active'],
            ['name' => 'Los Banos Station',  'place' => 'Los Banos, Laguna',    'type' => 'Station',      'manager' => 'Pending invite', 'riders' => 0,  'parcels' => 0,   'status' => 'Setup'],
            ['name' => 'Quezon Hub',         'place' => 'Lucena, Quezon',       'type' => 'Province hub', 'manager' => 'Ben Villanueva', 'riders' => 14, 'parcels' => 305, 'status' => 'Active'],
            ['name' => 'Lucena Station',     'place' => 'Lucena, Quezon',       'type' => 'Station',      'manager' => 'Joy Mercado',  'riders' => 11, 'parcels' => 128, 'status' => 'Active'],
        ],
    ]))->name('company');

    Route::get('/company/hubs', fn () => view('logistics.company.hubs', [
        'provinces' => [
            ['name' => 'Laguna Hub', 'place' => 'Santa Rosa, Laguna', 'manager' => 'Ana Reyes', 'stations' => [
                ['name' => 'Santa Cruz Station', 'municipality' => 'Santa Cruz', 'manager' => 'Mark Dela Cruz', 'riders' => 9, 'status' => 'Active'],
                ['name' => 'Pagsanjan Station',  'municipality' => 'Pagsanjan',  'manager' => 'Liza Santos',    'riders' => 6, 'status' => 'Active'],
                ['name' => 'Los Banos Station',  'municipality' => 'Los Banos',  'manager' => 'Pending invite', 'riders' => 0, 'status' => 'Setup'],
            ]],
            ['name' => 'Quezon Hub', 'place' => 'Lucena, Quezon', 'manager' => 'Ben Villanueva', 'stations' => [
                ['name' => 'Lucena Station', 'municipality' => 'Lucena', 'manager' => 'Joy Mercado', 'riders' => 11, 'status' => 'Active'],
            ]],
        ],
    ]))->name('company.hubs');

    Route::get('/company/managers', fn () => view('logistics.company.managers', [
        'hubNames' => ['Laguna Hub', 'Santa Cruz Station', 'Pagsanjan Station', 'Los Banos Station', 'Quezon Hub', 'Lucena Station'],
        'managers' => [
            ['name' => 'Ana Reyes',      'email' => 'ana@jnt-demo.ph',    'hub' => 'Laguna Hub',         'role' => 'Hub manager',        'last_login' => 'Today, 8:12 AM',  'status' => 'Active'],
            ['name' => 'Mark Dela Cruz', 'email' => 'mark@jnt-demo.ph',   'hub' => 'Santa Cruz Station', 'role' => 'Hub manager',        'last_login' => 'Today, 7:40 AM',  'status' => 'Active'],
            ['name' => 'Rica Flores',    'email' => 'rica@jnt-demo.ph',   'hub' => 'Santa Cruz Station', 'role' => 'Hub staff (sorter)', 'last_login' => 'Yesterday',       'status' => 'Active'],
            ['name' => 'Liza Santos',    'email' => 'liza@jnt-demo.ph',   'hub' => 'Pagsanjan Station',  'role' => 'Hub manager',        'last_login' => 'Oct 5',           'status' => 'Active'],
            ['name' => 'Carlo Ramos',    'email' => 'carlo@jnt-demo.ph',  'hub' => 'Los Banos Station',  'role' => 'Hub manager',        'last_login' => 'Never',           'status' => 'Invited'],
        ],
    ]))->name('company.managers');

    // ---- Hub dashboard (station or province) ------------------------------
    Route::get('/hub/station', fn () => view('logistics.hub.dashboard', [
        'role' => 'station',
        'stats' => [
            ['Incoming today', '48', 'inbox', 'blue'],
            ['In sorting', '23', 'layers', 'amber'],
            ['Out for delivery', '61', 'truck', 'green'],
            ['Delivered today', '37', 'check-circle', 'green'],
        ],
        'stations' => [],
        'parcels' => [
            ['code' => 'VND-10482', 'route' => 'Laguna Hub to Santa Cruz', 'area' => 'Brgy. Poblacion', 'status' => 'Needs sorting',  'tone' => 'violet'],
            ['code' => 'VND-10479', 'route' => 'Laguna Hub to Santa Cruz', 'area' => 'Brgy. Bagumbayan', 'status' => 'Assign rider', 'tone' => 'teal'],
            ['code' => 'VND-10466', 'route' => 'Pickup in Santa Cruz',      'area' => 'Brgy. Santo Angel', 'status' => 'Verify pickup', 'tone' => 'amber'],
            ['code' => 'VND-10451', 'route' => 'Laguna Hub to Santa Cruz', 'area' => 'Brgy. Patimbao',  'status' => 'Delivery failed', 'tone' => 'red'],
        ],
        'riders' => [
            ['name' => 'Jun Bautista',  'vehicle' => 'Motorcycle', 'parcels' => 14],
            ['name' => 'Paolo Garcia',  'vehicle' => 'Motorcycle', 'parcels' => 12],
            ['name' => 'Nina Castillo', 'vehicle' => 'Tricycle',   'parcels' => 9],
        ],
    ]))->name('hub.station');

    Route::get('/hub/province', fn () => view('logistics.hub.dashboard', [
        'role' => 'province',
        'stats' => [
            ['From stations', '132', 'inbox', 'blue'],
            ['Waiting for linehaul', '58', 'layers', 'amber'],
            ['Arriving from other hubs', '74', 'truck', 'green'],
            ['Dispatched today', '210', 'check-circle', 'green'],
        ],
        'stations' => [
            ['name' => 'Santa Cruz Station', 'waiting' => 96, 'riders' => 9, 'status' => 'Active'],
            ['name' => 'Pagsanjan Station',  'waiting' => 41, 'riders' => 6, 'status' => 'Active'],
            ['name' => 'Los Banos Station',  'waiting' => 0,  'riders' => 0, 'status' => 'Setup'],
        ],
        'parcels' => [
            ['code' => 'VND-10501', 'route' => 'Santa Cruz to Lucena',  'area' => 'Quezon Hub', 'status' => 'Ready for linehaul', 'tone' => 'violet'],
            ['code' => 'VND-10498', 'route' => 'Pagsanjan to Lucena',   'area' => 'Quezon Hub', 'status' => 'Ready for linehaul', 'tone' => 'violet'],
            ['code' => 'VND-10377', 'route' => 'Lucena to Santa Cruz',  'area' => 'Santa Cruz', 'status' => 'Pass to station',    'tone' => 'teal'],
        ],
        'riders' => [
            ['name' => 'Ramon Aquino', 'vehicle' => 'Truck', 'parcels' => 58],
            ['name' => 'Eddie Lim',    'vehicle' => 'Van',   'parcels' => 32],
        ],
    ]))->name('hub.province');

    Route::get('/hub/linehaul', fn () => view('logistics.hub.linehaul', [
        'truckRiders' => ['Ramon Aquino (Truck)', 'Eddie Lim (Van)'],
        'outbound' => [
            ['to' => 'Quezon Hub', 'via' => 'Laguna to Quezon', 'count' => 34],
            ['to' => 'Batangas Hub', 'via' => 'Laguna to Batangas', 'count' => 24],
        ],
        'inbound' => [
            ['from' => 'Quezon Hub',   'count' => 42, 'rider' => 'Ben Torres',  'eta' => 'Arriving in 25 min', 'tone' => 'blue'],
            ['from' => 'Cavite Hub',   'count' => 32, 'rider' => 'Dan Navarro', 'eta' => 'Arrived',            'tone' => 'green'],
        ],
    ]))->name('hub.linehaul');

    // ---- Remaining company pages ------------------------------------------
    Route::get('/company/reports', fn () => view('logistics.company.reports'))->name('company.reports');
    Route::get('/company/account', fn () => view('logistics.shared.account', ['role' => 'company']))->name('company.account');

    // ---- Remaining hub pages (same views for station and province hub) ----
    // /logistics-preview/hub/{station|province}/{riders|incoming|sorting|assignments|monitoring|reports|account}
    Route::get('/hub/{role}/{page}', function (string $role, string $page) {
        $view = $page === 'account' ? 'logistics.shared.account' : 'logistics.hub.'.$page;
        abort_unless(view()->exists($view), 404);
        abort_if($page === 'assignments' && $role !== 'station', 404);
        return view($view, ['role' => $role]);
    })->where(['role' => 'station|province', 'page' => 'riders|incoming|sorting|assignments|monitoring|reports|account'])
      ->name('hub.page');
});