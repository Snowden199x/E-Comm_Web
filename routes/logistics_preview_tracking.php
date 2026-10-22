<?php

/*
 * PREVIEW ONLY - sample parcels for Parcel Tracking (spec section 8A). Delete with logistics_preview.php.
 * The story matches the other preview screens: trips HT-2041 and HT-2044 are the Laguna Hub trips, HT-2042 is the
 * late Quezon Hub trip, and the dashboard counts 3 delayed + 2 failed parcels, which are exactly the ones flagged here.
 * Backend later: build each journey from order status history, facility scans, rider tasks and transport_trips,
 * and return nothing for a parcel outside the signed-in actor's scope (spec 3.1).
 */

// [status, where, at, trip, trip type, note]. Only a transport leg has a trip id and type.
$ev = fn (string $status, string $where, string $at, ?string $trip = null, ?string $type = null, string $note = '') => compact('status', 'where', 'at', 'trip', 'type', 'note');
$ST = 'Station transfer';
$IP = 'Inter-province';

$parcel = fn (string $code, string $order, string $route, string $from, string $to, array $stops, array $events, string $flag = '', string $reason = '')
    => compact('code', 'order', 'route', 'from', 'to', 'stops', 'events', 'flag', 'reason');

return [
    'delayed_rule' => 'A parcel is delayed when it has not moved on for 24 hours at a facility, or when its trip is more than 2 hours past the expected arrival.',
    'route_labels' => ['local' => 'Same station', 'intra_province' => 'Same province', 'inter_province' => 'Different provinces'],
    'parcels' => [
        $parcel('VND-10473', 'VN-001473', 'local', 'Lucban Station', 'Lucban Station', ['Lucban Station'], [
            $ev('Ready for pickup', 'Seller in Lucban', '2026-10-20T13:20'),
            $ev('Rider assigned', 'Lucban Station · Mario Pineda', '2026-10-20T13:45'),
            $ev('Picked up', 'Mario Pineda (rider)', '2026-10-20T15:05'),
            $ev('Received at Station Hub', 'Lucban Station', '2026-10-20T16:10'),
            $ev('Sorted', 'Lucban Station', '2026-10-20T16:30', null, null, 'Same station: goes to the delivery queue.'),
            $ev('Out for delivery', 'Ben Torres (rider)', '2026-10-21T07:30'),
            $ev('Delivery failed', 'Ben Torres (rider)', '2026-10-21T10:15', null, null, 'The address could not be found.'),
        ], 'failed', 'The address could not be found. Lucban Station must contact the buyer or return the parcel to the seller.'),

        $parcel('VND-10490', 'VN-001490', 'inter_province', 'Lucban Station', 'Santa Cruz Station',
            ['Lucban Station', 'Quezon Hub', 'Laguna Hub', 'Santa Cruz Station'], [
            $ev('Ready for pickup', 'Seller in Lucban', '2026-10-20T08:40'),
            $ev('Rider assigned', 'Lucban Station · Mario Pineda', '2026-10-20T09:00'),
            $ev('Picked up', 'Mario Pineda (rider)', '2026-10-20T10:30'),
            $ev('Received at Station Hub', 'Lucban Station', '2026-10-20T11:50'),
            $ev('Sorted', 'Lucban Station', '2026-10-20T12:10', null, null, 'Different province: waits for a transfer to Quezon Hub.'),
            $ev('In transit to Quezon Hub', 'Lucban Station', '2026-10-20T15:00', 'HT-2032', $ST, 'Bert Magsino · QZN-002'),
            $ev('Received at Province Hub', 'Quezon Hub', '2026-10-20T16:20', 'HT-2032', $ST),
            $ev('In transit to Laguna Hub', 'Quezon Hub', '2026-10-21T04:30', 'HT-2042', $IP, 'Eddie Lim · TRK-007'),
        ], 'delayed', 'Trip HT-2042 is more than 2 hours past its expected arrival at Laguna Hub (due 8:30 AM).'),

        $parcel('VND-10477', 'VN-001477', 'intra_province', 'Pagsanjan Station', 'Santa Cruz Station',
            ['Pagsanjan Station', 'Laguna Hub', 'Santa Cruz Station'], [
            $ev('Ready for pickup', 'Seller in Pagsanjan', '2026-10-19T16:30'),
            $ev('Rider assigned', 'Pagsanjan Station · Aira Soriano', '2026-10-19T17:05'),
            $ev('Picked up', 'Aira Soriano (rider)', '2026-10-20T08:10'),
            $ev('Received at Station Hub', 'Pagsanjan Station', '2026-10-20T08:50'),
            $ev('Sorted', 'Pagsanjan Station', '2026-10-20T08:55', null, null, 'Different station: waits for a transfer to Laguna Hub.'),
            $ev('In transit to Laguna Hub', 'Pagsanjan Station', '2026-10-20T09:10', 'HT-2044', $ST, 'Ricky Santos · TRK-006'),
            $ev('Received at Province Hub', 'Laguna Hub', '2026-10-20T10:35', 'HT-2044', $ST),
        ], 'delayed', 'At Laguna Hub for more than 24 hours. It is not loaded on a trip to Santa Cruz Station yet.'),

        $parcel('VND-10462', 'VN-001462', 'local', 'Lucena Station', 'Lucena Station', ['Lucena Station'], [
            $ev('Ready for pickup', 'Seller in Lucena', '2026-10-19T09:30'),
            $ev('Rider assigned', 'Lucena Station · Marlon Reyes', '2026-10-19T09:55'),
            $ev('Picked up', 'Marlon Reyes (rider)', '2026-10-19T10:40'),
            $ev('Received at Station Hub', 'Lucena Station', '2026-10-19T11:05'),
            $ev('Sorted', 'Lucena Station', '2026-10-19T11:30', null, null, 'Same station: goes to the delivery queue.'),
        ], 'delayed', 'In the delivery queue for more than 24 hours. No rider has been assigned for delivery yet.'),

        $parcel('VND-10451', 'VN-001451', 'intra_province', 'Pagsanjan Station', 'Santa Cruz Station',
            ['Pagsanjan Station', 'Laguna Hub', 'Santa Cruz Station'], [
            $ev('Ready for pickup', 'Seller in Pagsanjan', '2026-10-17T10:05'),
            $ev('Rider assigned', 'Pagsanjan Station · Carlo Mendoza', '2026-10-17T10:30'),
            $ev('Picked up', 'Carlo Mendoza (rider)', '2026-10-17T13:00'),
            $ev('Received at Station Hub', 'Pagsanjan Station', '2026-10-17T14:10'),
            $ev('Sorted', 'Pagsanjan Station', '2026-10-17T14:30', null, null, 'Different station: waits for a transfer to Laguna Hub.'),
            $ev('In transit to Laguna Hub', 'Pagsanjan Station', '2026-10-18T09:10', 'HT-2034', $ST, 'Ricky Santos · TRK-006'),
            $ev('Received at Province Hub', 'Laguna Hub', '2026-10-18T10:40', 'HT-2034', $ST),
            $ev('In transit to Santa Cruz Station', 'Laguna Hub', '2026-10-19T14:05', 'HT-2037', $ST, 'Danilo Reyes · TRK-007'),
            $ev('Received at Station Hub', 'Santa Cruz Station', '2026-10-19T15:30', 'HT-2037', $ST),
            $ev('Out for delivery', 'Ella Navarro (rider)', '2026-10-20T08:30'),
            $ev('Delivery failed', 'Ella Navarro (rider)', '2026-10-20T15:40', null, null, 'The buyer was not home (Brgy. Patimbao).'),
        ], 'failed', 'The buyer was not home on the first attempt. Santa Cruz Station must reassign the parcel or return it to the seller.'),

        $parcel('VND-10438', 'VN-001438', 'intra_province', 'Santa Cruz Station', 'Pagsanjan Station',
            ['Santa Cruz Station', 'Laguna Hub', 'Pagsanjan Station'], [
            $ev('Ready for pickup', 'Seller in Santa Cruz', '2026-10-20T14:00'),
            $ev('Rider assigned', 'Santa Cruz Station · Nina Castillo', '2026-10-20T14:20'),
            $ev('Picked up', 'Nina Castillo (rider)', '2026-10-20T16:20'),
            $ev('Received at Station Hub', 'Santa Cruz Station', '2026-10-20T17:05'),
            $ev('Sorted', 'Santa Cruz Station', '2026-10-20T17:30', null, null, 'Different station: waits for a transfer to Laguna Hub.'),
            $ev('In transit to Laguna Hub', 'Santa Cruz Station', '2026-10-21T08:20', 'HT-2041', $ST, 'Juan Dela Cruz · TRK-004'),
        ]),

        $parcel('VND-10416', 'VN-001416', 'local', 'Santa Cruz Station', 'Santa Cruz Station', ['Santa Cruz Station'], [
            $ev('Ready for pickup', 'Seller in Santa Cruz', '2026-10-16T09:12'),
            $ev('Rider assigned', 'Santa Cruz Station · Jun Bautista', '2026-10-16T09:40'),
            $ev('Picked up', 'Jun Bautista (rider)', '2026-10-16T11:05'),
            $ev('Received at Station Hub', 'Santa Cruz Station', '2026-10-16T13:20'),
            $ev('Sorted', 'Santa Cruz Station', '2026-10-16T14:02', null, null, 'Same station: goes to the delivery queue.'),
            $ev('Out for delivery', 'Paolo Garcia (rider)', '2026-10-17T08:15'),
            $ev('Delivered', 'Paolo Garcia (rider)', '2026-10-17T10:48'),
        ]),

        $parcel('VND-10409', 'VN-001409', 'inter_province', 'Lucban Station', 'Pagsanjan Station',
            ['Lucban Station', 'Quezon Hub', 'Laguna Hub', 'Pagsanjan Station'], [
            $ev('Ready for pickup', 'Seller in Lucban', '2026-10-16T08:30'),
            $ev('Rider assigned', 'Lucban Station · Mario Pineda', '2026-10-16T08:55'),
            $ev('Picked up', 'Mario Pineda (rider)', '2026-10-16T10:15'),
            $ev('Received at Station Hub', 'Lucban Station', '2026-10-16T11:40'),
            $ev('Sorted', 'Lucban Station', '2026-10-16T12:00', null, null, 'Different province: waits for a transfer to Quezon Hub.'),
            $ev('In transit to Quezon Hub', 'Lucban Station', '2026-10-17T07:30', 'HT-2031', $ST, 'Bert Magsino · QZN-001'),
            $ev('Received at Province Hub', 'Quezon Hub', '2026-10-17T09:00', 'HT-2031', $ST),
            $ev('In transit to Laguna Hub', 'Quezon Hub', '2026-10-17T20:00', 'HT-2033', $IP, 'Rene Dizon · QZN-003'),
            $ev('Received at Province Hub', 'Laguna Hub', '2026-10-18T01:40', 'HT-2033', $IP),
            $ev('In transit to Pagsanjan Station', 'Laguna Hub', '2026-10-19T07:30', 'HT-2035', $ST, 'Leo Manalo · TRK-003'),
            $ev('Received at Station Hub', 'Pagsanjan Station', '2026-10-19T09:00', 'HT-2035', $ST),
            $ev('Out for delivery', 'Benji Cruz (rider)', '2026-10-19T13:10'),
            $ev('Delivered', 'Benji Cruz (rider)', '2026-10-19T16:25'),
        ]),
    ],
];
