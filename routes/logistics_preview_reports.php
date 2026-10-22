<?php

/*
 * PREVIEW ONLY - Company Reports (spec section 8A). Figures are for the last 30 days; the page scales every 'count' column
 * to the chosen date range. Delete with logistics_preview.php.
 * The totals agree with the other preview screens: 4 Province Hubs (3 active), 6 Station Hubs, 37 riders, 25 drivers,
 * 19 vehicles (4/4/1, 3/3/1, 2/1/0 available/on trip/maintenance per hub), failed % per hub 2/3/4, and the dashboard's 3 delayed + 2 failed.
 * Backend later: aggregate queries scoped to the signed-in company, using the same server-side delayed rule as the dashboard.
 * Columns: [label, type, options]. Types: title | text | num | count | pct | hours | pill. Options: meter, target, better (high|low), share.
 */

return [
    'days' => 30,
    'reports' => [
        'volume' => [
            'label' => 'Parcel volume',
            'intro' => 'How many parcels each Province Hub handled, with its Station Hubs.',
            'kpis' => [
                ['Parcels handled', 7420, 'count', 'package', 'blue'],
                ['Delivered', 6953, 'count', 'check-circle', 'green'],
                ['Still in progress', 273, 'count', 'clock', 'amber'],
                ['Failed or returned', 194, 'count', 'x', 'red'],
            ],
            'cols' => [['Province Hub', 'title'], ['Station Hubs', 'num'], ['Parcels handled', 'count', ['meter' => true]], ['Delivered', 'count'], ['Failed', 'count'], ['Share of network', 'pct', ['meter' => true, 'share' => true]]],
            'rows' => [
                ['Laguna Hub', 3, 3860, 3640, 77, 52.0],
                ['Quezon Hub', 2, 2540, 2368, 76, 34.2],
                ['Batangas Hub', 1, 1020, 945, 41, 13.7],
                ['Cavite Hub (inactive)', null, null, null, null, null],
            ],
        ],
        'delivery' => [
            'label' => 'Delivery performance',
            'intro' => 'How deliveries went, by how far the parcel travelled. Target: 90% on time, under 3% failed.',
            'kpis' => [
                ['Delivered on time', 92.1, 'pct', 'check-circle', 'green'],
                ['Delivery success rate', 97.3, 'pct', 'bar-chart', 'blue'],
                ['Failed deliveries', 2.7, 'pct', 'x', 'red'],
                ['Average hub-to-door time', 34, 'hours', 'clock', 'amber'],
            ],
            'cols' => [['Route', 'title'], ['Delivered', 'count', ['meter' => true]], ['On time', 'pct', ['target' => 90, 'better' => 'high', 'meter' => true]], ['Failed', 'pct', ['target' => 3, 'better' => 'low', 'meter' => true]], ['Average time', 'hours']],
            'rows' => [
                ['Same station', 2851, 96.0, 1.6, 17],
                ['Same province', 2503, 92.0, 2.8, 35],
                ['Different provinces', 1599, 85.5, 4.4, 63],
            ],
        ],
        'hubs' => [
            'label' => 'Province Hub performance',
            'intro' => 'How each Province Hub moves parcels on. Target: 90% on time.',
            'kpis' => [
                ['Parcels handled', 7420, 'count', 'package', 'blue'],
                ['Average time at the hub', 10.7, 'hours', 'clock', 'amber'],
                ['Delayed transfers', 106, 'count', 'alert', 'red'],
                ['Active Province Hubs', '3 of 4', 'text', 'map-pin', 'green'],
            ],
            'cols' => [['Province Hub', 'title'], ['Parcels handled', 'count', ['meter' => true]], ['Sent on', 'count'], ['Received', 'count'], ['Time at hub', 'hours'], ['Delayed transfers', 'count'], ['On time', 'pct', ['target' => 90, 'better' => 'high', 'meter' => true]]],
            'rows' => [
                ['Laguna Hub', 3860, 2310, 2290, 9.5, 41, 94.0],
                ['Quezon Hub', 2540, 1480, 1500, 11.2, 38, 91.0],
                ['Batangas Hub', 1020, 560, 590, 13.8, 27, 88.0],
                ['Cavite Hub (inactive)', null, null, null, null, null, null],
            ],
        ],
        'stations' => [
            'label' => 'Station Hub performance',
            'intro' => 'How each municipality station handled its parcels. Target: 90% on time.',
            'kpis' => [
                ['Active Station Hubs', '5 of 6', 'text', 'building', 'green'],
                ['Riders', 37, 'num', 'bike', 'blue'],
                ['Best on time', 'Santa Cruz · 95%', 'text', 'check-circle', 'green'],
                ['Lowest on time', 'Lipa · 88%', 'text', 'alert', 'amber'],
            ],
            'cols' => [['Station Hub', 'title'], ['Province Hub', 'text'], ['Parcels handled', 'count', ['meter' => true]], ['On time', 'pct', ['target' => 90, 'better' => 'high', 'meter' => true]], ['Failed', 'pct', ['target' => 3, 'better' => 'low', 'meter' => true]], ['Riders', 'num']],
            'rows' => [
                ['Santa Cruz Station', 'Laguna Hub', 2210, 95.0, 1.8, 9],
                ['Pagsanjan Station', 'Laguna Hub', 1650, 92.0, 2.3, 6],
                ['Los Banos Station', 'Laguna Hub', null, null, null, 0],
                ['Lucena Station', 'Quezon Hub', 1620, 92.0, 2.8, 11],
                ['Lucban Station', 'Quezon Hub', 920, 89.0, 3.4, 5],
                ['Lipa Station', 'Batangas Hub', 1020, 88.0, 4.0, 6],
            ],
        ],
        'riders' => [
            'label' => 'Rider performance',
            'intro' => 'The ten riders with the most deliveries. Target: 90% on time.',
            'kpis' => [
                ['Riders', 37, 'num', 'bike', 'blue'],
                ['Pickups completed', 7380, 'count', 'package', 'blue'],
                ['Deliveries completed', 6953, 'count', 'check-circle', 'green'],
                ['Average per rider', 188, 'count', 'bar-chart', 'amber'],
            ],
            'cols' => [['Rider', 'title'], ['Station Hub', 'text'], ['Pickups', 'count'], ['Deliveries', 'count', ['meter' => true]], ['On time', 'pct', ['target' => 90, 'better' => 'high', 'meter' => true]], ['Failed', 'pct', ['target' => 3, 'better' => 'low', 'meter' => true]]],
            'rows' => [
                ['Jun Bautista', 'Santa Cruz Station', 310, 402, 97.0, 1.2],
                ['Paolo Garcia', 'Santa Cruz Station', 280, 355, 95.0, 1.8],
                ['Nina Castillo', 'Santa Cruz Station', 265, 345, 94.0, 2.0],
                ['Marlon Reyes', 'Lucena Station', 241, 338, 93.0, 2.3],
                ['Tomas Villar', 'Santa Cruz Station', 220, 322, 92.0, 2.2],
                ['Carlo Mendoza', 'Pagsanjan Station', 198, 301, 93.0, 2.4],
                ['Joel Aquino', 'Lucena Station', 190, 289, 91.0, 3.0],
                ['Rico Dimaano', 'Santa Cruz Station', 175, 276, 90.0, 2.9],
                ['Aira Soriano', 'Pagsanjan Station', 160, 251, 94.0, 2.1],
                ['Gino Alonzo', 'Santa Cruz Station', 140, 233, 88.0, 3.8],
            ],
        ],
        'drivers' => [
            'label' => 'Transport drivers',
            'intro' => 'The eight transport drivers with the most trips. Target: 90% on time.',
            'kpis' => [
                ['Transport drivers', 25, 'num', 'users', 'blue'],
                ['Trips completed', 214, 'count', 'truck', 'green'],
                ['Parcels carried', 10970, 'count', 'package', 'blue'],
                ['On-time arrival', 92.3, 'pct', 'clock', 'amber'],
            ],
            'cols' => [['Driver', 'title'], ['Province Hub', 'text'], ['Trips completed', 'count', ['meter' => true]], ['Parcels carried', 'count'], ['On-time arrival', 'pct', ['target' => 90, 'better' => 'high', 'meter' => true]], ['Cancelled', 'count']],
            'rows' => [
                ['Juan Dela Cruz', 'Laguna Hub', 18, 1020, 96.0, 0],
                ['Ramon Aquino', 'Laguna Hub', 14, 2360, 93.0, 1],
                ['Leo Manalo', 'Laguna Hub', 13, 1980, 92.0, 0],
                ['Bert Magsino', 'Quezon Hub', 13, 860, 94.0, 0],
                ['Danilo Reyes', 'Laguna Hub', 12, 640, 95.0, 0],
                ['Rene Dizon', 'Quezon Hub', 11, 1450, 90.0, 1],
                ['Ricky Santos', 'Laguna Hub', 11, 590, 97.0, 0],
                ['Eddie Lim', 'Laguna Hub', 10, 1720, 88.0, 1],
            ],
        ],
        'vehicles' => [
            'label' => 'Vehicle utilization',
            'intro' => 'How much each company vehicle was on the road. Status shows today.',
            'kpis' => [
                ['Company vehicles', 19, 'num', 'truck', 'blue'],
                ['Average time on the road', 56, 'pct', 'bar-chart', 'green'],
                ['In maintenance now', 2, 'num', 'alert', 'amber'],
                ['Trips completed', 214, 'count', 'check-circle', 'green'],
            ],
            'cols' => [['Vehicle', 'title'], ['Type', 'text'], ['Province Hub', 'text'], ['Trips', 'count'], ['On the road', 'pct', ['meter' => true]], ['Days in maintenance', 'num'], ['Status now', 'pill']],
            'rows' => [
                ['TRK-001', '10-Wheeler', 'Laguna Hub', 14, 71.0, 0, 'On trip'],
                ['TRK-002', '6-Wheeler Truck', 'Laguna Hub', 13, 66.0, 0, 'On trip'],
                ['TRK-003', 'Closed Van', 'Laguna Hub', 10, 52.0, 0, 'Available'],
                ['TRK-004', 'L300 Van', 'Laguna Hub', 18, 79.0, 0, 'On trip'],
                ['TRK-005', 'L300 Van', 'Laguna Hub', 12, 58.0, 0, 'On trip'],
                ['TRK-006', 'Closed Van', 'Laguna Hub', 11, 55.0, 0, 'Available'],
                ['TRK-007', '6-Wheeler Truck', 'Laguna Hub', 15, 74.0, 1, 'Available'],
                ['TRK-008', 'L300 Van', 'Laguna Hub', 12, 49.0, 2, 'Available'],
                ['TRK-009', '10-Wheeler', 'Laguna Hub', 7, 33.0, 9, 'Maintenance'],
                ['QZN-001', '10-Wheeler', 'Quezon Hub', 13, 68.0, 0, 'On trip'],
                ['QZN-002', '6-Wheeler Truck', 'Quezon Hub', 12, 63.0, 0, 'On trip'],
                ['QZN-003', 'Closed Van', 'Quezon Hub', 9, 47.0, 0, 'Available'],
                ['QZN-004', 'L300 Van', 'Quezon Hub', 14, 72.0, 1, 'On trip'],
                ['QZN-005', 'L300 Van', 'Quezon Hub', 10, 50.0, 0, 'Available'],
                ['QZN-006', 'Closed Van', 'Quezon Hub', 9, 44.0, 3, 'Available'],
                ['QZN-007', '6-Wheeler Truck', 'Quezon Hub', 7, 30.0, 12, 'Maintenance'],
                ['BTG-001', '6-Wheeler Truck', 'Batangas Hub', 11, 61.0, 0, 'On trip'],
                ['BTG-002', 'L300 Van', 'Batangas Hub', 9, 46.0, 0, 'Available'],
                ['BTG-003', 'Closed Van', 'Batangas Hub', 8, 41.0, 1, 'Available'],
            ],
        ],
        'trips' => [
            'label' => 'Transport trips',
            'intro' => 'Completed and cancelled trips for each Province Hub. Target: 90% on time.',
            'kpis' => [
                ['Trips completed', 214, 'count', 'check-circle', 'green'],
                ['Station transfers', 183, 'count', 'truck', 'blue'],
                ['Inter-province', 31, 'count', 'map-pin', 'blue'],
                ['Cancelled', 10, 'count', 'x', 'red'],
            ],
            'cols' => [['Province Hub', 'title'], ['Station transfers', 'count'], ['Inter-province', 'count'], ['Cancelled', 'count'], ['Parcels per trip', 'num'], ['On-time arrival', 'pct', ['target' => 90, 'better' => 'high', 'meter' => true]]],
            'rows' => [
                ['Laguna Hub', 96, 16, 5, 52, 94.0],
                ['Quezon Hub', 62, 12, 4, 51, 91.0],
                ['Batangas Hub', 25, 3, 1, 49, 89.0],
                ['Cavite Hub (inactive)', null, null, null, null, null],
            ],
        ],
        'exceptions' => [
            'label' => 'Failed and delayed',
            'intro' => 'Deliveries that failed and parcels that were delayed, by Province Hub. Failed target: under 3%. Delayed target: under 2%.',
            'kpis' => [
                ['Failed deliveries', 194, 'count', 'x', 'red'],
                ['Delayed parcels', 130, 'count', 'clock', 'amber'],
                ['Open right now', 5, 'num', 'alert', 'red'],
                ['Failed rate', 2.6, 'pct', 'bar-chart', 'amber'],
            ],
            'cols' => [['Province Hub', 'title'], ['Failed', 'count'], ['Failed rate', 'pct', ['target' => 3, 'better' => 'low', 'meter' => true]], ['Delayed', 'count'], ['Delayed rate', 'pct', ['target' => 2, 'better' => 'low', 'meter' => true]], ['Main reason for failing', 'text'], ['Main reason for delay', 'text']],
            'rows' => [
                ['Laguna Hub', 77, 2.0, 52, 1.3, 'The buyer was not home', 'Waiting for a transfer trip'],
                ['Quezon Hub', 76, 3.0, 47, 1.9, 'The address could not be found', 'Trip arrived late'],
                ['Batangas Hub', 41, 4.0, 31, 3.0, 'The buyer refused the parcel', 'Waiting in the delivery queue'],
            ],
        ],
    ],
    // ---- Province Hub reports (spec section 8A, Province Hub row) -----------------------------------------------------
    // Laguna Hub only. Every figure is the Laguna slice of the company reports above: 3,860 parcels handled (3,640 delivered, 143 in progress,
    // 77 failed), 2,290 received and 2,310 sent on, 41 delayed transfers, 94% on time, 112 trips (96 + 16, 5 cancelled), 9 vehicles, 12 drivers.
    // Backend later: the same aggregate queries as the company reports, filtered to the signed-in Province Hub and its Station Hubs.
    'province_laguna' => [
        'volume' => [
            'label' => 'Parcel volume',
            'intro' => 'How many parcels each Station Hub in Laguna handled.',
            'kpis' => [
                ['Parcels handled', 3860, 'count', 'package', 'blue'],
                ['Delivered', 3640, 'count', 'check-circle', 'green'],
                ['Still in progress', 143, 'count', 'clock', 'amber'],
                ['Failed or returned', 77, 'count', 'x', 'red'],
            ],
            'cols' => [['Station Hub', 'title'], ['Parcels handled', 'count', ['meter' => true]], ['Delivered', 'count'], ['Failed', 'count'], ['Share of Laguna', 'pct', ['meter' => true, 'share' => true]]],
            'rows' => [
                ['Santa Cruz Station', 2210, 2085, 39, 57.3],
                ['Pagsanjan Station', 1650, 1555, 38, 42.7],
                ['Los Banos Station (in setup)', null, null, null, null],
            ],
        ],
        'transfers' => [
            'label' => 'Incoming and outgoing',
            'intro' => 'Parcels this hub received and sent on, by where they came from or went to. Target: 90% on time.',
            'kpis' => [
                ['Received at this hub', 2290, 'count', 'inbox', 'blue'],
                ['Sent on', 2310, 'count', 'truck', 'green'],
                ['Average time at the hub', 9.5, 'hours', 'clock', 'amber'],
                ['Delayed transfers', 41, 'count', 'alert', 'red'],
            ],
            'cols' => [['Station or hub', 'title'], ['Kind', 'text'], ['Received here', 'count', ['meter' => true]], ['Sent on', 'count'], ['Delayed transfers', 'count'], ['On time', 'pct', ['target' => 90, 'better' => 'high', 'meter' => true]]],
            'rows' => [
                ['Santa Cruz Station', 'Station Hub', 1150, 1060, 12, 95.0],
                ['Pagsanjan Station', 'Station Hub', 840, 810, 9, 94.0],
                ['Quezon Hub', 'Province Hub', 190, 270, 11, 92.0],
                ['Batangas Hub', 'Province Hub', 110, 170, 9, 88.0],
            ],
        ],
        'stations' => [
            'label' => 'Station Hub performance',
            'intro' => 'How each Station Hub in Laguna handled its parcels. Target: 90% on time, under 3% failed.',
            'kpis' => [
                ['Active Station Hubs', '2 of 3', 'text', 'building', 'green'],
                ['Riders', 15, 'num', 'bike', 'blue'],
                ['Best on time', 'Santa Cruz · 95%', 'text', 'check-circle', 'green'],
                ['Lowest on time', 'Pagsanjan · 92%', 'text', 'alert', 'amber'],
            ],
            'cols' => [['Station Hub', 'title'], ['Parcels handled', 'count', ['meter' => true]], ['On time', 'pct', ['target' => 90, 'better' => 'high', 'meter' => true]], ['Failed', 'pct', ['target' => 3, 'better' => 'low', 'meter' => true]], ['Riders', 'num']],
            'rows' => [
                ['Santa Cruz Station', 2210, 95.0, 1.8, 9],
                ['Pagsanjan Station', 1650, 92.0, 2.3, 6],
                ['Los Banos Station (in setup)', null, null, null, 0],
            ],
        ],
        'trips' => [
            'label' => 'Transport trips',
            'intro' => 'Completed and cancelled trips by type. Target: 90% on time.',
            'kpis' => [
                ['Trips completed', 112, 'count', 'check-circle', 'green'],
                ['Station transfers', 96, 'count', 'truck', 'blue'],
                ['Inter-province', 16, 'count', 'map-pin', 'blue'],
                ['Cancelled', 5, 'count', 'x', 'red'],
            ],
            'cols' => [['Trip type', 'title'], ['Completed', 'count', ['meter' => true]], ['Cancelled', 'count'], ['Parcels per trip', 'num'], ['On-time arrival', 'pct', ['target' => 90, 'better' => 'high', 'meter' => true]]],
            'rows' => [
                ['Station transfers', 96, 3, 26, 95.0],
                ['Inter-province trips', 16, 2, 210, 88.0],
            ],
        ],
        'drivers' => [
            'label' => 'Transport drivers',
            'intro' => 'Every approved transport driver at this hub, by trips completed. Target: 90% on time.',
            'kpis' => [
                ['Transport drivers', 12, 'num', 'users', 'blue'],
                ['Trips completed', 112, 'count', 'truck', 'green'],
                ['On-time arrival', 94.0, 'pct', 'clock', 'amber'],
                ['Cancelled trips', 5, 'count', 'x', 'red'],
            ],
            'cols' => [['Driver', 'title'], ['Trips completed', 'count', ['meter' => true]], ['Parcels carried', 'count'], ['On-time arrival', 'pct', ['target' => 90, 'better' => 'high', 'meter' => true]], ['Cancelled', 'count']],
            'rows' => [
                ['Juan Dela Cruz', 18, 1020, 96.0, 0],
                ['Ramon Aquino', 14, 2360, 93.0, 1],
                ['Leo Manalo', 13, 1980, 92.0, 0],
                ['Danilo Reyes', 12, 640, 95.0, 0],
                ['Ricky Santos', 11, 590, 97.0, 0],
                ['Eddie Lim', 10, 1720, 88.0, 1],
                ['Paolo Mendoza', 9, 480, 94.0, 1],
                ['Arnel Cabrera', 7, 380, 91.0, 0],
                ['Vic Salazar', 6, 310, 93.0, 0],
                ['Mon Lacson', 5, 250, 92.0, 1],
                ['Nilo Pardo', 4, 190, 90.0, 1],
                ['Rodel Ocampo', 3, 150, 100.0, 0],
            ],
        ],
        'vehicles' => [
            'label' => 'Vehicle utilization',
            'intro' => 'How much each vehicle at this hub was on the road. Status shows today.',
            'kpis' => [
                ['Vehicles', 9, 'num', 'truck', 'blue'],
                ['Average time on the road', 60, 'pct', 'bar-chart', 'green'],
                ['In maintenance now', 1, 'num', 'alert', 'amber'],
                ['Trips completed', 112, 'count', 'check-circle', 'green'],
            ],
            'cols' => [['Vehicle', 'title'], ['Type', 'text'], ['Trips', 'count'], ['On the road', 'pct', ['meter' => true]], ['Days in maintenance', 'num'], ['Status now', 'pill']],
            'rows' => [
                ['TRK-001', '10-Wheeler', 14, 71.0, 0, 'On trip'],
                ['TRK-002', '6-Wheeler Truck', 13, 66.0, 0, 'On trip'],
                ['TRK-003', 'Closed Van', 10, 52.0, 0, 'Available'],
                ['TRK-004', 'L300 Van', 18, 79.0, 0, 'On trip'],
                ['TRK-005', 'L300 Van', 12, 58.0, 0, 'On trip'],
                ['TRK-006', 'Closed Van', 11, 55.0, 0, 'Available'],
                ['TRK-007', '6-Wheeler Truck', 15, 74.0, 1, 'Available'],
                ['TRK-008', 'L300 Van', 12, 49.0, 2, 'Available'],
                ['TRK-009', '10-Wheeler', 7, 33.0, 9, 'Maintenance'],
            ],
        ],
        'exceptions' => [
            'label' => 'Failed and delayed',
            'intro' => 'Deliveries that failed and parcels that were delayed, by Station Hub. Failed target: under 3%. Delayed target: under 2%.',
            'kpis' => [
                ['Failed deliveries', 77, 'count', 'x', 'red'],
                ['Delayed parcels', 52, 'count', 'clock', 'amber'],
                ['Delayed transfers now', 1, 'num', 'alert', 'red'],
                ['Failed rate', 2.0, 'pct', 'bar-chart', 'amber'],
            ],
            'cols' => [['Station Hub', 'title'], ['Failed', 'count'], ['Failed rate', 'pct', ['target' => 3, 'better' => 'low', 'meter' => true]], ['Delayed', 'count'], ['Delayed rate', 'pct', ['target' => 2, 'better' => 'low', 'meter' => true]], ['Main reason for failing', 'text'], ['Main reason for delay', 'text']],
            'rows' => [
                ['Santa Cruz Station', 39, 1.8, 31, 1.4, 'The buyer was not home', 'Waiting for a transfer trip'],
                ['Pagsanjan Station', 38, 2.3, 21, 1.3, 'The address could not be found', 'Waiting in the delivery queue'],
                ['Los Banos Station (in setup)', null, null, null, null, null, null],
            ],
        ],
    ],
];
