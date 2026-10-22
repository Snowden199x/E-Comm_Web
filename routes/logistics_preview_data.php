<?php

/*
 * PREVIEW ONLY - one hardcoded company network shared by Province Hubs, Province Hub details
 * and Network Overview, so the three screens always agree. Delete with logistics_preview.php.
 * Backend later: replace with LogisticsCompany::provinceHubs()->with('stations', 'manager').
 */

// $extra is optional: address, email, phone. Used by the Province Hub > Station Hubs screen.
$station = fn (string $name, string $town, string $manager, int $riders, int $parcels, string $status = 'Active', array $extra = []) => [
    'name' => $name, 'municipality' => $town, 'manager' => $manager, 'riders' => $riders, 'parcels' => $parcels, 'status' => $status,
] + $extra + ['address' => '', 'email' => '', 'phone' => ''];

// Station details for Province Hub > Station Hubs > View. Keyed by municipality slug.
// Backend later: Station Hub record with riders (courier_details.station_hub_id), parcel counts by stage, transport_trips touching it.
$rider = fn (string $name, string $vehicle, string $barangays, string $status, int $tasks) => compact('name', 'vehicle', 'barangays', 'status', 'tasks');

$stationDetails = [
    'santa-cruz' => [
        'since' => 'March 2026',
        'stage' => ['Awaiting sorting' => 18, 'Ready for delivery' => 31, 'Waiting for transfer' => 12, 'Out for delivery' => 35],
        'perf' => ['on_time' => 95, 'failed' => 2, 'hours' => 18],
        'riders_list' => [
            $rider('Jun Bautista', 'Motorcycle', 'Poblacion I, Poblacion II', 'On route', 14),
            $rider('Paolo Garcia', 'Motorcycle', 'Bagumbayan, Patimbao', 'On route', 12),
            $rider('Nina Castillo', 'Tricycle', 'Santo Angel, Labuin', 'On route', 9),
            $rider('Rico Dimaano', 'Motorcycle', 'Calios, Oogong', 'On route', 8),
            $rider('Ella Navarro', 'Motorcycle', 'Gatid, Bubukal', 'Available', 0),
            $rider('Tomas Villar', 'Van', 'Poblacion III, Poblacion IV', 'On route', 11),
            $rider('Sheila Ramos', 'Motorcycle', 'Pagsawitan', 'Available', 0),
            $rider('Gino Alonzo', 'Motorcycle', 'Santisima Cruz', 'On route', 6),
            $rider('Marie Pascual', 'Tricycle', 'Duhat', 'Off duty', 0),
        ],
        'trips' => [
            ['HT-2041', 'Station transfer', 'Santa Cruz Station to Laguna Hub', 'Juan Dela Cruz', 'TRK-004', 85, 'In transit'],
            ['HT-2047', 'Station transfer', 'Laguna Hub to Santa Cruz Station', 'Paolo Mendoza', 'TRK-005', 52, 'Scheduled'],
        ],
    ],
    'pagsanjan' => [
        'since' => 'May 2026',
        'stage' => ['Awaiting sorting' => 6, 'Ready for delivery' => 14, 'Waiting for transfer' => 5, 'Out for delivery' => 16],
        'perf' => ['on_time' => 92, 'failed' => 3, 'hours' => 22],
        'riders_list' => [
            $rider('Carlo Mendoza', 'Motorcycle', 'Pinagsanjan, Maulawin', 'On route', 10),
            $rider('Aira Soriano', 'Motorcycle', 'Sampaloc, Magdapio', 'On route', 7),
            $rider('Benji Cruz', 'Tricycle', 'Calusiche', 'Available', 0),
            $rider('Dennis Lopez', 'Motorcycle', 'Biñan, Layugan', 'On route', 5),
            $rider('Kate Morales', 'Motorcycle', 'Anibong', 'Available', 0),
            $rider('Rey Salonga', 'Motorcycle', 'Lambac', 'Off duty', 0),
        ],
        'trips' => [
            ['HT-2045', 'Station transfer', 'Laguna Hub to Pagsanjan Station', 'Eddie Lim', 'TRK-002', 29, 'Scheduled'],
            ['HT-2044', 'Station transfer', 'Pagsanjan Station to Laguna Hub', 'Ricky Santos', 'TRK-006', 38, 'Completed'],
        ],
    ],
    'los-banos' => [
        'since' => 'Not yet active',
        'stage' => ['Awaiting sorting' => 0, 'Ready for delivery' => 0, 'Waiting for transfer' => 0, 'Out for delivery' => 0],
        'perf' => ['on_time' => 0, 'failed' => 0, 'hours' => 0],
        'riders_list' => [],
        'trips' => [],
    ],
];

// Laguna Hub transport drivers for Province Hub > Transport Drivers (spec 6B and 8).
// status: available | on_trip | off_duty | inactive | pending (application waiting for review).
// 4 on trip = the 4 active trips of Laguna Hub; 12 approved drivers = 5 available + 4 on trip + 3 off duty.
$driver = fn (string $name, string $phone, string $license, string $category, string $expiry, string $status, ?string $trip, int $done, array $extra = []) => [
    'name' => $name, 'phone' => $phone, 'license' => $license, 'category' => $category, 'expiry' => $expiry,
    'status' => $status, 'trip' => $trip, 'done' => $done,
] + $extra;
$addr = fn (string $street, string $town) => $street . ', ' . $town . ', Laguna';

$lagunaDrivers = [
    $driver('Juan Dela Cruz', '0917 310 4411', 'N01-14-228541', 'Professional · C', '2028-04-12', 'on_trip', 'HT-2041', 64, ['email' => 'juan.delacruz@mail.ph', 'birthday' => '1988-02-17', 'sex' => 'Male', 'address' => $addr('Blk 5 Lot 12 Mabini St., Brgy. Pansol', 'Calamba')]),
    $driver('Ramon Aquino', '0918 320 5522', 'N02-11-774120', 'Professional · CE', '2027-09-03', 'on_trip', 'HT-2043', 112, ['email' => 'ramon.aquino@mail.ph', 'birthday' => '1983-07-29', 'sex' => 'Male', 'address' => $addr('45 Rizal Ave., Brgy. Tagapo', 'Santa Rosa')]),
    $driver('Paolo Mendoza', '0919 330 6633', 'N03-17-390218', 'Professional · C', '2029-01-22', 'on_trip', 'HT-2047', 38, ['email' => 'paolo.mendoza@mail.ph', 'birthday' => '1991-11-05', 'sex' => 'Male', 'address' => $addr('Purok 3, Brgy. Bagong Pook', 'Santa Cruz')]),
    $driver('Eddie Lim', '0920 340 7744', 'N01-12-651902', 'Professional · C', '2026-11-30', 'on_trip', 'HT-2045', 91, ['email' => 'eddie.lim@mail.ph', 'birthday' => '1986-05-14', 'sex' => 'Male', 'address' => $addr('88 San Jose St., Brgy. Real', 'Calamba')]),
    $driver('Ricky Santos', '0917 350 8855', 'N02-15-118774', 'Professional · C', '2028-08-19', 'available', null, 57, ['email' => 'ricky.santos@mail.ph', 'birthday' => '1990-03-02', 'sex' => 'Male', 'address' => $addr('Brgy. Pinagsanjan', 'Pagsanjan')]),
    $driver('Leo Manalo', '0918 360 9966', 'N04-13-902261', 'Professional · CE', '2027-12-08', 'available', null, 83, ['email' => 'leo.manalo@mail.ph', 'birthday' => '1985-09-21', 'sex' => 'Male', 'address' => $addr('12 Burgos St., Brgy. Poblacion', 'Cabuyao')]),
    $driver('Arnel Cabrera', '0919 370 1177', 'N01-16-445036', 'Professional · C', '2028-06-27', 'available', null, 46, ['email' => 'arnel.cabrera@mail.ph', 'birthday' => '1993-01-30', 'sex' => 'Male', 'address' => $addr('Zone 2, Brgy. Malinta', 'Los Banos')]),
    $driver('Danilo Reyes', '0920 380 2288', 'N03-10-337719', 'Professional · C', '2027-03-15', 'available', null, 129, ['email' => 'danilo.reyes@mail.ph', 'birthday' => '1981-12-11', 'sex' => 'Male', 'address' => $addr('77 Quezon Ave., Brgy. Sta. Cruz', 'San Pablo')]),
    $driver('Rodel Ocampo', '0917 390 3399', 'N02-18-560843', 'Professional · C', '2029-05-04', 'available', null, 21, ['email' => 'rodel.ocampo@mail.ph', 'birthday' => '1994-08-08', 'sex' => 'Male', 'address' => $addr('Blk 2 Lot 7, Brgy. Pulong', 'Santa Rosa')]),
    $driver('Vic Salazar', '0918 400 4400', 'N01-09-882105', 'Professional · CE', '2027-07-19', 'off_duty', null, 150, ['email' => 'vic.salazar@mail.ph', 'birthday' => '1979-04-25', 'sex' => 'Male', 'address' => $addr('9 Mabuhay St., Brgy. Banay-banay', 'Cabuyao')]),
    $driver('Mon Lacson', '0919 410 5511', 'N03-14-206397', 'Professional · C', '2028-02-11', 'off_duty', null, 72, ['email' => 'mon.lacson@mail.ph', 'birthday' => '1989-10-16', 'sex' => 'Male', 'address' => $addr('Brgy. Kanluran', 'Calauan')]),
    $driver('Nilo Pardo', '0920 420 6622', 'N02-12-731450', 'Professional · C', '2027-10-05', 'off_duty', null, 98, ['email' => 'nilo.pardo@mail.ph', 'birthday' => '1987-06-03', 'sex' => 'Male', 'address' => $addr('34 P. Burgos St., Brgy. Poblacion', 'Bay')]),

    $driver('Francis Tan', '0917 430 7733', 'N01-19-114862', 'Professional · C', '2029-09-14', 'pending', null, 0, ['applied' => '2026-10-19', 'email' => 'francis.tan@mail.ph', 'birthday' => '1996-02-09', 'sex' => 'Male', 'address' => $addr('Blk 8 Lot 3, Brgy. Don Jose', 'Santa Rosa')]),
    $driver('Oscar Delos Reyes', '0918 440 8844', 'N02-08-320771', 'Professional · CE', '2026-08-30', 'pending', null, 0, ['applied' => '2026-10-20', 'email' => 'oscar.dr@mail.ph', 'birthday' => '1978-12-01', 'sex' => 'Male', 'address' => $addr('21 Del Pilar St., Brgy. Sampaloc', 'Siniloan')]),
    $driver('Bong Alvarez', '0919 450 9955', 'N03-18-659204', 'Professional · C', '2028-11-20', 'pending', null, 0, ['applied' => '2026-10-21', 'email' => 'bong.alvarez@mail.ph', 'birthday' => '1992-04-18', 'sex' => 'Male', 'address' => $addr('Zone 1, Brgy. Pagsawitan', 'Santa Cruz')]),

    $driver('Gerry Villanueva', '0920 460 1066', 'N01-07-905512', 'Professional · C', '2028-03-09', 'inactive', null, 33, ['email' => 'gerry.v@mail.ph', 'birthday' => '1982-01-13', 'sex' => 'Male', 'address' => $addr('Brgy. Mayondon', 'Los Banos')]),
    $driver('Tony Abad', '0917 470 2177', 'N02-06-178340', 'Professional · C', '2026-09-15', 'inactive', null, 140, ['email' => 'tony.abad@mail.ph', 'birthday' => '1975-11-27', 'sex' => 'Male', 'address' => $addr('17 Sampaguita St., Brgy. San Antonio', 'San Pedro')]),
];

// Laguna Hub company vehicles for Province Hub > Vehicles (spec 6B and 8). Backend later: company_trucks.
// status: available | on_trip | maintenance | inactive. 4 available + 4 on trip + 1 in maintenance = the 9 vehicles of Laguna Hub.
// The 4 on-trip vehicles and their drivers match the 4 active trips of Laguna Hub and the on-trip drivers above.
$vehicle = fn (string $code, string $plate, string $type, float $capacity, string $status, int $done, array $extra = []) => [
    'code' => $code, 'plate' => $plate, 'type' => $type, 'capacity' => $capacity, 'status' => $status, 'done' => $done,
] + $extra + ['trip' => null, 'driver' => null, 'note' => '', 'since' => null];

$lagunaVehicles = [
    $vehicle('TRK-001', 'ABC 1234', '10-Wheeler', 15, 'on_trip', 88, ['trip' => 'HT-2043', 'driver' => 'Ramon Aquino']),
    $vehicle('TRK-002', 'NBD 4412', '6-Wheeler Truck', 8, 'on_trip', 61, ['trip' => 'HT-2045', 'driver' => 'Eddie Lim']),
    $vehicle('TRK-003', 'CAR 7731', 'Closed Van', 3, 'available', 47),
    $vehicle('TRK-004', 'XYZ 5678', 'L300 Van', 1, 'on_trip', 102, ['trip' => 'HT-2041', 'driver' => 'Juan Dela Cruz']),
    $vehicle('TRK-005', 'DAN 2290', 'L300 Van', 1, 'on_trip', 39, ['trip' => 'HT-2047', 'driver' => 'Paolo Mendoza']),
    $vehicle('TRK-006', 'GHT 5518', 'Closed Van', 3, 'available', 54),
    $vehicle('TRK-007', 'LKM 9046', '6-Wheeler Truck', 8, 'available', 70),
    $vehicle('TRK-008', 'PQR 3382', 'L300 Van', 1, 'available', 28),
    $vehicle('TRK-009', 'UVW 6107', '10-Wheeler', 15, 'maintenance', 95, ['note' => 'Brake system check. Back in service after inspection.', 'since' => '2026-10-18']),
];

// Laguna Hub transport trips for Province Hub > Transport Trips (spec 6B and 8). Backend later: transport_trips.
// type: station_transfer | inter_province. status: scheduled | in_transit | completed | cancelled.
// The 3 scheduled trips and 1 in-transit trip are the 4 active trips used by the drivers and vehicles above.
$trip = fn (string $id, string $type, string $origin, string $destination, string $driver, string $vehicle, int $parcels, string $status, string $scheduled, array $extra = []) => [
    'id' => $id, 'type' => $type, 'origin' => $origin, 'destination' => $destination, 'driver' => $driver, 'vehicle' => $vehicle,
    'parcels' => $parcels, 'status' => $status, 'scheduled' => $scheduled,
] + $extra + ['departed' => null, 'arrived' => null, 'note' => ''];

$lagunaTrips = [
    $trip('HT-2047', 'station_transfer', 'Laguna Hub', 'Santa Cruz Station', 'Paolo Mendoza', 'TRK-005', 52, 'scheduled', '2026-10-21T14:00'),
    $trip('HT-2045', 'station_transfer', 'Laguna Hub', 'Pagsanjan Station', 'Eddie Lim', 'TRK-002', 29, 'scheduled', '2026-10-21T15:00'),
    $trip('HT-2043', 'inter_province', 'Laguna Hub', 'Quezon Hub', 'Ramon Aquino', 'TRK-001', 340, 'scheduled', '2026-10-22T06:00'),
    $trip('HT-2041', 'station_transfer', 'Santa Cruz Station', 'Laguna Hub', 'Juan Dela Cruz', 'TRK-004', 85, 'in_transit', '2026-10-21T08:00', ['departed' => '2026-10-21T08:20']),
    $trip('HT-2044', 'station_transfer', 'Pagsanjan Station', 'Laguna Hub', 'Ricky Santos', 'TRK-006', 38, 'completed', '2026-10-20T09:00', ['departed' => '2026-10-20T09:10', 'arrived' => '2026-10-20T10:35']),
    $trip('HT-2042', 'inter_province', 'Laguna Hub', 'Batangas Hub', 'Leo Manalo', 'TRK-003', 210, 'completed', '2026-10-19T05:30', ['departed' => '2026-10-19T05:45', 'arrived' => '2026-10-19T09:10']),
    $trip('HT-2040', 'station_transfer', 'Laguna Hub', 'Santa Cruz Station', 'Danilo Reyes', 'TRK-007', 44, 'cancelled', '2026-10-18T10:00', ['note' => 'The parcels were not ready for loading.']),
];

// Parcels ready to load, by trip leg. Backend later: orders at the origin facility whose next destination matches.
// 'to:<slug>' = from Laguna Hub to that station or hub; 'from:<slug>' = from that station to Laguna Hub.
$ready = function (int $count, array $areas, int $seed) {
    $weights = [0.6, 1.2, 2.4, 3.1, 0.8, 5.0, 1.9, 4.2, 0.5, 2.8];
    $rows = [];
    for ($i = 0; $i < $count; $i++) {
        $rows[] = [
            'order' => sprintf('VND-2610%02d-%04d', 14 + ($i % 7), 1200 + $seed * 37 + $i * 13),
            'area' => $areas[$i % count($areas)],
            'weight' => $weights[($i + $seed) % count($weights)],
        ];
    }

    return $rows;
};
$readyParcels = [
    'to:santa-cruz' => $ready(14, ['Poblacion II', 'Bagumbayan', 'Santo Angel', 'Patimbao'], 1),
    'to:pagsanjan' => $ready(9, ['Pinagsanjan', 'Sampaloc', 'Calusiche'], 2),
    'to:quezon' => $ready(22, ['Lucena City', 'Tayabas', 'Sariaya', 'Candelaria'], 3),
    'to:batangas' => $ready(17, ['Batangas City', 'Lipa City', 'Tanauan'], 4),
    'from:santa-cruz' => $ready(12, ['Calamba', 'Santa Rosa', 'Cabuyao'], 5),
    'from:pagsanjan' => $ready(6, ['Calamba', 'Santa Rosa', 'Cabuyao'], 6),
];

// Laguna Hub parcels for Province Hub > Parcel Operations (spec section 8). Backend later: orders at, or heading to, this facility,
// grouped by status, with their next destination taken from the order's generated route stops.
// Counts agree with the dashboard: 85 incoming (HT-2041), 64 awaiting sorting, 62 waiting for a trip (14 + 9 + 22 + 17 as in Create Transport Trip),
// 421 loaded on scheduled trips (HT-2047 52, HT-2045 29, HT-2043 340), and 248 handed over in history (HT-2044 38, HT-2042 210).
// stage: incoming | sorting | waiting | loaded | history. 'to' is always another station of this province or another Province Hub.
$parcelAreas = [
    'Santa Cruz Station' => ['Poblacion II', 'Bagumbayan', 'Santo Angel', 'Patimbao'],
    'Pagsanjan Station' => ['Pinagsanjan', 'Sampaloc', 'Calusiche'],
    'Quezon Hub' => ['Lucena City', 'Tayabas', 'Sariaya', 'Candelaria'],
    'Batangas Hub' => ['Batangas City', 'Lipa City', 'Tanauan'],
];
$parcelClock = fn (int $minutesBefore) => gmdate('Y-m-d\TH:i', strtotime('2026-10-21 10:30 UTC') - $minutesBefore * 60);
$parcelSeq = 11000;
$mkParcel = function (string $stage, string $from, string $to, ?string $trip, string $at, string $result = '') use (&$parcelSeq, $parcelAreas) {
    $parcelSeq++;
    $areas = $parcelAreas[$to];

    return ['code' => 'VND-'.$parcelSeq, 'area' => $areas[$parcelSeq % count($areas)], 'from' => $from, 'to' => $to, 'trip' => $trip, 'stage' => $stage, 'at' => $at, 'result' => $result];
};
// Pick the i-th destination from a cycle, never the facility the parcel came from.
$pickTo = function (array $cycle, string $from, int $i) {
    for ($k = 0; $k < count($cycle); $k++) {
        $to = $cycle[($i + $k) % count($cycle)];
        if ($to !== $from) {
            return $to;
        }
    }
};
$allDest = ['Quezon Hub', 'Santa Cruz Station', 'Batangas Hub', 'Pagsanjan Station', 'Quezon Hub', 'Batangas Hub', 'Santa Cruz Station'];
$allFrom = ['Santa Cruz Station', 'Pagsanjan Station', 'Quezon Hub', 'Santa Cruz Station', 'Batangas Hub'];

$lagunaParcels = [];
for ($i = 0; $i < 85; $i++) {
    $lagunaParcels[] = $mkParcel('incoming', 'Santa Cruz Station', $pickTo(['Quezon Hub', 'Pagsanjan Station', 'Batangas Hub', 'Quezon Hub'], 'Santa Cruz Station', $i), 'HT-2041', $parcelClock(130));
}
for ($i = 0; $i < 64; $i++) {
    $from = $allFrom[$i % count($allFrom)];
    $lagunaParcels[] = $mkParcel('sorting', $from, $pickTo($allDest, $from, $i), null, $parcelClock(20 + ($i * 7) % 400));
}
foreach (['Santa Cruz Station' => 14, 'Pagsanjan Station' => 9, 'Quezon Hub' => 22, 'Batangas Hub' => 17] as $to => $count) {
    for ($i = 0; $i < $count; $i++) {
        $from = $pickTo($allFrom, $to, $i + 1);
        $lagunaParcels[] = $mkParcel('waiting', $from, $to, null, $parcelClock(60 + ($i * 11) % 600));
    }
}
foreach ([['HT-2047', 'Santa Cruz Station', 52, '2026-10-21T14:00'], ['HT-2045', 'Pagsanjan Station', 29, '2026-10-21T15:00'], ['HT-2043', 'Quezon Hub', 340, '2026-10-22T06:00']] as [$tripId, $to, $count, $departs]) {
    for ($i = 0; $i < $count; $i++) {
        $lagunaParcels[] = $mkParcel('loaded', $pickTo($allFrom, $to, $i), $to, $tripId, $departs);
    }
}
for ($i = 0; $i < 38; $i++) {
    $lagunaParcels[] = $mkParcel('history', 'Pagsanjan Station', $pickTo($allDest, 'Pagsanjan Station', $i), 'HT-2044', '2026-10-20T10:35', 'Received at this hub');
}
for ($i = 0; $i < 210; $i++) {
    $from = $allFrom[$i % count($allFrom)];
    $lagunaParcels[] = $mkParcel('history', $from === 'Batangas Hub' ? 'Santa Cruz Station' : $from, 'Batangas Hub', 'HT-2042', '2026-10-19T09:10', 'Arrived at Batangas Hub');
}

return [
    'laguna_parcels' => $lagunaParcels,
    'station_details' => $stationDetails,
    'laguna_drivers' => $lagunaDrivers,
    'laguna_vehicles' => $lagunaVehicles,
    'laguna_trips' => $lagunaTrips,
    'ready_parcels' => $readyParcels,
    'vehicle_types' => ['L300 Van', 'Closed Van', '6-Wheeler Truck', '10-Wheeler'],

    // Municipalities of Laguna, for the Province Hub > Create Station Hub form (PSGC data later).
    'laguna_municipalities' => ['Alaminos', 'Bay', 'Binan', 'Cabuyao', 'Calamba', 'Calauan', 'Cavinti', 'Famy', 'Kalayaan', 'Liliw', 'Los Banos', 'Luisiana', 'Lumban', 'Mabitac', 'Magdalena', 'Majayjay', 'Nagcarlan', 'Paete', 'Pagsanjan', 'Pakil', 'Pangil', 'Pila', 'Rizal', 'San Pablo', 'San Pedro', 'Santa Cruz', 'Santa Maria', 'Santa Rosa', 'Siniloan', 'Victoria'],

    'provinces' => ['Albay', 'Batangas', 'Bulacan', 'Cavite', 'Cebu', 'Laguna', 'Pampanga', 'Pangasinan', 'Quezon', 'Rizal'],

    'hubs' => [
        'laguna' => [
            'name' => 'Laguna Hub', 'province' => 'Laguna', 'address' => 'Unit 4, Sta. Rosa Logistics Park, Brgy. Tagapo, Santa Rosa',
            'manager' => 'Ana Reyes', 'email' => 'ana@jnt-demo.ph', 'phone' => '0917 123 4567',
            'drivers' => 12, 'vehicles' => 9, 'parcels' => 412, 'open' => 412, 'status' => 'Active',
            'fleet' => ['drivers' => [5, 4, 3], 'vehicles' => [4, 4, 1]],
            'perf' => ['on_time' => 94, 'failed' => 2, 'hours' => 31],
            'trips' => [
                ['HT-2041', 'Station transfer', 'Santa Cruz Station to Laguna Hub', 'Juan Dela Cruz', 'TRK-004', 85, 'In transit'],
                ['HT-2043', 'Inter-province', 'Laguna Hub to Quezon Hub', 'Ramon Aquino', 'TRK-001', 340, 'Scheduled'],
                ['HT-2045', 'Station transfer', 'Laguna Hub to Pagsanjan Station', 'Eddie Lim', 'TRK-002', 29, 'Scheduled'],
                ['HT-2047', 'Station transfer', 'Laguna Hub to Santa Cruz Station', 'Paolo Mendoza', 'TRK-005', 52, 'Scheduled'],
            ],
            'stations' => [
                $station('Santa Cruz Station', 'Santa Cruz', 'Mark Dela Cruz', 9, 96, 'Active', ['address' => 'Rizal St., Brgy. Poblacion II, Santa Cruz', 'email' => 'mark@jnt-demo.ph', 'phone' => '0917 200 1100']),
                $station('Pagsanjan Station', 'Pagsanjan', 'Liza Santos', 6, 41, 'Active', ['address' => 'Brgy. Pinagsanjan, Pagsanjan', 'email' => 'liza@jnt-demo.ph', 'phone' => '0918 200 2200']),
                $station('Los Banos Station', 'Los Banos', 'Carlo Ramos', 0, 0, 'Setup', ['address' => 'Lopez Ave., Brgy. Batong Malake, Los Banos', 'email' => 'carlo@jnt-demo.ph', 'phone' => '0919 200 3300']),
            ],
        ],
        'quezon' => [
            'name' => 'Quezon Hub', 'province' => 'Quezon', 'address' => '12 Diversion Road, Brgy. Ilayang Dupay, Lucena City',
            'manager' => 'Ben Villanueva', 'email' => 'ben@jnt-demo.ph', 'phone' => '0918 555 0142',
            'drivers' => 9, 'vehicles' => 7, 'parcels' => 305, 'open' => 305, 'status' => 'Active',
            'fleet' => ['drivers' => [4, 4, 1], 'vehicles' => [3, 3, 1]],
            'perf' => ['on_time' => 91, 'failed' => 3, 'hours' => 36],
            'trips' => [
                ['HT-2042', 'Inter-province', 'Quezon Hub to Laguna Hub', 'Eddie Lim', 'TRK-007', 210, 'In transit'],
            ],
            'stations' => [
                $station('Lucena Station', 'Lucena', 'Joy Mercado', 11, 128),
                $station('Lucban Station', 'Lucban', 'Tess Aguilar', 5, 37),
            ],
        ],
        'batangas' => [
            'name' => 'Batangas Hub', 'province' => 'Batangas', 'address' => 'Warehouse 2, Pallocan West, Batangas City',
            'manager' => 'Marco Silva', 'email' => 'marco@jnt-demo.ph', 'phone' => '0920 411 7788',
            'drivers' => 4, 'vehicles' => 3, 'parcels' => 88, 'open' => 88, 'status' => 'Active',
            'fleet' => ['drivers' => [3, 1, 0], 'vehicles' => [2, 1, 0]],
            'perf' => ['on_time' => 88, 'failed' => 4, 'hours' => 40],
            'trips' => [],
            'stations' => [
                $station('Lipa Station', 'Lipa City', 'Grace Tolentino', 6, 52),
            ],
        ],
        'cavite' => [
            'name' => 'Cavite Hub', 'province' => 'Cavite', 'address' => 'Lot 9, Governor\'s Drive, Brgy. Palico, Imus',
            'manager' => 'Dan Navarro', 'email' => 'dan@jnt-demo.ph', 'phone' => '0919 300 2211',
            'drivers' => 0, 'vehicles' => 0, 'parcels' => 0, 'open' => 0, 'status' => 'Inactive',
            'fleet' => ['drivers' => [0, 0, 0], 'vehicles' => [0, 0, 0]],
            'perf' => ['on_time' => 0, 'failed' => 0, 'hours' => 0],
            'trips' => [],
            'stations' => [],
        ],
    ],
];