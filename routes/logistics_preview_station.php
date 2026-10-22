<?php

/*
 * PREVIEW ONLY - hardcoded Station Hub pickup data (JNT Santa Cruz Station), shared by the Station pages so they agree.
 * Used by Pickup Requests and Incoming Parcels in routes/logistics_preview.php. Delete together with the preview routes.
 * Returns a closure: ($net, $stationTasks) => ['requests', 'riders', 'sellers', 'now'].
 */

return function (array $net, array $stationTasks): array {
    $now = \Carbon\Carbon::parse('2026-10-21T10:30', 'Asia/Manila');
    $detail = $net['station_details']['santa-cruz'];

    $riders = collect($detail['riders_list'])->values()->map(function ($r, $i) use ($stationTasks) {
        [$pickup, $delivery] = $stationTasks[$r['name']] ?? [0, 0];

        return ['id' => $i + 1, 'name' => $r['name'], 'vehicle' => $r['vehicle'], 'barangays' => $r['barangays'],
            'status' => $r['status'], 'pickup' => $pickup, 'delivery' => $delivery];
    })->all();

    $sellers = ['Lola Betty Kakanin', 'Mang Berto Hardware', 'Kuya Jong Gadgets', 'Ate Lorna Dry Goods', 'Rosal Home Bakes', 'Laguna Leather Works',
        'Pacita Fashion Hub', 'Bulaklak Plant Shop', 'Dimaano Pet Supply', 'Santa Cruz Cellphone Zone', 'Tita Mely Pasalubong', 'Kape Laguna Roasters',
        'Villar Auto Parts', 'Bayani Sports Gear', 'Nene Baby Needs', 'Aling Cora Herbals', 'Jollo Sneaker Corner', 'Mica Beauty Bar',
        'Tiny Toys Laguna', 'Ramos Farm Fresh', 'Bahay Kubo Crafts', 'Olivia Skin Care', 'Zeny Sari-Sari Online', 'Lakeside Books',
        'Cruz Electronics', 'Alonzo Bike Shop', 'Marasigan Textiles', 'Pagsawitan Honey'];
    $streets = ['12 Rizal St.', '3 Mabini St.', '88 Bonifacio Ave.', '21 Del Pilar St.', '5 Luna St.', '47 Quezon Ave.', '9 Burgos St.', '64 Gen. Luna St.',
        '15 Sampaguita St.', '30 Narra St.', '7 Mahogany St.', '102 National Rd.'];

    $rows = [];
    $n = 0;
    $add = function (string $barangay, string $status, ?int $riderId, int $minutesAgo) use (&$rows, &$n, $sellers, $streets, $now) {
        $at = $now->copy()->subMinutes($minutesAgo);
        $rows[] = [
            'code' => 'VND-'.(10501 + $n),
            'seller' => $sellers[$n % count($sellers)],
            'phone' => '09'.(17 + $n % 3).' '.(300 + $n * 7 % 600).' '.str_pad((string) (1000 + $n * 137 % 9000), 4, '0', STR_PAD_LEFT),
            'street' => $streets[$n % count($streets)],
            'barangay' => $barangay,
            'at' => $at->format('Y-m-d\TH:i'),
            'time' => $at->format('g:i A'),
            'ago' => $minutesAgo < 60 ? $minutesAgo.' min ago' : floor($minutesAgo / 60).' h '.($minutesAgo % 60).' min ago',
            'status' => $status,
            'rider_id' => $riderId,
        ];
        $n++;
    };

    // Requests nobody has taken yet (the dashboard shows 3).
    $add('Patimbao', 'unassigned', null, 131);
    $add('Pagsawitan', 'unassigned', null, 55);
    $add('Poblacion II', 'unassigned', null, 12);

    // Open pickup tasks for each rider: the first third are already picked up, the rest are waiting for the rider to arrive.
    foreach ($riders as $r) {
        $areas = array_map('trim', explode(',', $r['barangays']));
        for ($i = 0; $i < $r['pickup']; $i++) {
            $pickedUp = $i < intdiv($r['pickup'], 3);
            $add($areas[$i % count($areas)], $pickedUp ? 'picked_up' : 'assigned', $r['id'], $pickedUp ? 150 + ($n * 13) % 60 : 25 + ($n * 29) % 110);
        }
    }

    // Received today (48 seller parcels). The newest 18 still wait in Sorting, the rest are sorted. The next step comes from the order's
    // route, so staff never choose it: a parcel for a barangay of this station goes to the delivery queue, any other goes to the Province Hub.
    $workers = collect($riders)->filter(fn ($r) => $r['pickup'] + $r['delivery'] > 0)->values();
    $barangays = collect($riders)->flatMap(fn ($r) => array_map('trim', explode(',', $r['barangays'])))->values();
    $farTowns = ['Calamba', 'Santa Rosa', 'Cabuyao'];
    $received = collect(range(0, 47))->map(function ($i) use ($sellers, $workers, $barangays, $farTowns, $now) {
        $at = $now->copy()->subMinutes(8 + $i * 4 + $i % 3);
        $transfer = $i % 4 === 1;

        return [
            'code' => 'VND-'.(10500 - $i), 'kind' => 'rider', 'stage' => $i < 18 ? 'awaiting_sorting' : 'sorted',
            'seller' => $sellers[($i * 7 + 3) % count($sellers)], 'rider' => $workers[($i * 5 + 2) % count($workers)]['name'],
            'time' => $at->format('g:i A'), 'rcv' => 100 - $i,
            'next' => $transfer ? 'transfer' : 'delivery',
            'to' => $transfer ? $farTowns[$i % 3] : $barangays[($i * 3 + 1) % $barangays->count()],
        ];
    })->all();

    // Delivery Queue and Task Assignment (spec section 8). Ready for delivery (31) agrees with the dashboard: 23 were sorted today (from
    // $received above), the rest were carried over from yesterday. Parcels with riders (35) are the open delivery tasks of the riders above.
    $stage = $detail['stage'];
    $areas = $barangays;
    $readyToday = collect($received)->where('stage', 'sorted')->where('next', 'delivery')->map(fn ($p) => [
        'code' => $p['code'], 'seller' => $p['seller'], 'barangay' => $p['to'], 'since' => 'Received '.$p['time'], 'ago' => 'Today', 'retry' => false,
    ]);
    $carryCount = max(0, $stage['Ready for delivery'] - $readyToday->count());
    $readyCarried = collect(range(0, max(0, $carryCount - 1)))->take($carryCount)->map(fn ($i) => [
        'code' => 'VND-'.(10400 - $i), 'seller' => $sellers[($i * 5 + 1) % count($sellers)], 'barangay' => $areas[($i * 2 + 1) % $areas->count()],
        'since' => 'Received yesterday', 'ago' => 'Yesterday', 'retry' => false,
    ]);

    $withRiders = [];
    $k = 0;
    foreach ($riders as $r) {
        $mine = array_map('trim', explode(',', $r['barangays']));
        for ($i = 0; $i < $r['delivery']; $i++) {
            $withRiders[] = [
                'code' => 'VND-'.(10360 - $k), 'barangay' => $mine[$i % count($mine)], 'rider_id' => $r['id'], 'rider' => $r['name'],
                'since' => 'Assigned '.$now->copy()->setTime(7, 30)->addMinutes(($k * 7) % 150)->format('g:i A'),
            ];
            $k++;
        }
    }

    // The one failed delivery the dashboard shows (VND-10451, Patimbao). Keep it in step with the 'failed' list in routes/logistics_preview.php and with its journey in routes/logistics_preview_tracking.php.
    $failed = [[
        'code' => 'VND-10451', 'seller' => 'Pacita Fashion Hub', 'barangay' => 'Patimbao', 'rider' => 'Ella Navarro',
        'reason' => 'The buyer was not home', 'at' => 'yesterday, 3:40 PM', 'attempts' => 1,
    ]];

    // Outgoing Transfers (spec section 8). Waiting for transfer (12) agrees with the dashboard and with the Province Hub's 'from:santa-cruz' ready-to-load
    // list: 7 were sorted today for another town (from $received above), the rest were carried over from yesterday. 'town' is where the parcel is
    // finally going; every one of them goes to the Province Hub first. Trips come from the Province Hub's list: only trips that start here.
    $transferToday = collect($received)->where('stage', 'sorted')->where('next', 'transfer')->map(fn ($p) => [
        'code' => $p['code'], 'seller' => $p['seller'], 'town' => $p['to'], 'since' => 'Received '.$p['time'], 'ago' => 'Today',
    ]);
    $transferCarry = max(0, $stage['Waiting for transfer'] - $transferToday->count());
    $transferCarried = collect(range(0, max(0, $transferCarry - 1)))->take($transferCarry)->map(fn ($i) => [
        'code' => 'VND-'.(10389 - $i), 'seller' => $sellers[($i * 3 + 2) % count($sellers)], 'town' => $farTowns[($i + 1) % count($farTowns)],
        'since' => 'Received yesterday', 'ago' => 'Yesterday',
    ]);

    $vehiclesByCode = collect($net['laguna_vehicles'])->keyBy('code');
    $outTrips = collect($net['laguna_trips'])->where('origin', 'Santa Cruz Station')->map(function ($t) use ($vehiclesByCode) {
        $at = fn (?string $v, string $f) => $v ? \Carbon\Carbon::parse($v, 'Asia/Manila')->format($f) : '';
        $when = match ($t['status']) {
            'in_transit' => 'Left at '.$at($t['departed'], 'g:i A'),
            'scheduled' => 'Leaves at '.$at($t['scheduled'], 'g:i A'),
            'completed' => 'Arrived '.$at($t['arrived'], 'M j, g:i A'),
            default => $t['note'],
        };
        $v = $vehiclesByCode[$t['vehicle']] ?? null;

        return ['id' => $t['id'], 'status' => $t['status'], 'destination' => $t['destination'], 'driver' => $t['driver'], 'parcels' => $t['parcels'],
            'vehicle' => $t['vehicle'], 'plate' => $v['plate'] ?? '', 'vehicleType' => $v['type'] ?? '', 'when' => $when];
    })->values()->all();

    return [
        'requests' => collect($rows)->sortBy('at')->values()->all(),
        'riders' => $riders,
        'sellers' => $sellers,
        'now' => $now,
        'received' => $received,
        'ready' => $readyCarried->merge($readyToday)->values()->all(),
        'withRiders' => $withRiders,
        'failed' => $failed,
        'transferWaiting' => $transferCarried->merge($transferToday)->values()->all(),
        'outTrips' => $outTrips,
    ];
};
