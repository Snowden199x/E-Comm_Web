<?php

/*
 * PREVIEW ONLY - sample conversations for Company > Messages. Delete with logistics_preview.php.
 * Each conversation is one subject between the Company Admin and one hub, like an email thread. It matches the other
 * preview screens: the same parcels, trips and hubs as Dashboard and Parcel Tracking (HT-2042 late, VND-10473 failed, ...).
 * Backend later: message_threads + messages tables, a thread is always between the company and one facility manager,
 * the hub sees the same thread in its own Messages page (Province Hub and Station Hub read it from routes/logistics_preview.php,
 * filtered to their own facility), and every new message creates a notification for the receiver.
 *
 * 'from' is 'company' or 'hub'. 'ref' links a conversation to a parcel, trip or hub: [kind, label].
 */

$m = fn (string $from, string $name, string $at, string $body) => compact('from', 'name', 'at', 'body');
$ME = 'Maria Lourdes Tan';

return [
    'threads' => [
        [
            'id' => 1, 'subject' => 'Trip HT-2042 is running late', 'hub' => 'Quezon Hub', 'contact' => 'Ben Villanueva', 'topic' => 'Trip problem',
            'ref' => ['trip', 'HT-2042'], 'priority' => 'urgent', 'status' => 'open', 'unread' => true,
            'messages' => [
                $m('company', $ME, '2026-10-21T08:50', "Hi Ben,\n\nHT-2042 should have reached Laguna Hub by 8:30 AM and is now more than 2 hours late. It carries 210 parcels, including VND-10490.\n\nWhere is the truck, and when will it arrive?"),
                $m('hub', 'Ben Villanueva', '2026-10-21T09:35', "Good morning Ma'am Maria,\n\nThe truck (TRK-007) had a flat tire near Tiaong. The driver, Eddie Lim, is waiting for a replacement tire. We expect it to leave again by 10:30 and reach Laguna Hub around 12:30 PM.\n\nI will update you once it is moving."),
            ],
        ],
        [
            'id' => 2, 'subject' => 'VND-10473 delivery failed: address not found', 'hub' => 'Lucban Station', 'contact' => 'Tess Aguilar', 'topic' => 'Failed delivery',
            'ref' => ['parcel', 'VND-10473'], 'priority' => 'normal', 'status' => 'open', 'unread' => false,
            'messages' => [
                $m('company', $ME, '2026-10-21T10:25', "Hi Tess,\n\nThe rider could not find the address for VND-10473 this morning. Please call the buyer to confirm the address, then reassign the parcel or return it to the seller.\n\nPlease tell me what you decide so I can update the seller."),
            ],
        ],
        [
            'id' => 3, 'subject' => 'VND-10477 has waited at Laguna Hub for over a day', 'hub' => 'Laguna Hub', 'contact' => 'Ana Reyes', 'topic' => 'Delayed parcel',
            'ref' => ['parcel', 'VND-10477'], 'priority' => 'normal', 'status' => 'open', 'unread' => true,
            'messages' => [
                $m('company', $ME, '2026-10-21T08:10', "Hi Ana,\n\nVND-10477 came from Pagsanjan Station yesterday morning and is still at Laguna Hub. It is not on any trip to Santa Cruz Station. Can you load it on the next one?"),
                $m('hub', 'Ana Reyes', '2026-10-21T09:05', "Hi Ma'am,\n\nSorry about that. It was missed during sorting. I added it to HT-2047, which leaves at 2:00 PM today for Santa Cruz Station. I also reminded the sorters to check the waiting list every morning."),
            ],
        ],
        [
            'id' => 4, 'subject' => 'VND-10462 has no delivery rider', 'hub' => 'Lucena Station', 'contact' => 'Joy Mercado', 'topic' => 'Delayed parcel',
            'ref' => ['parcel', 'VND-10462'], 'priority' => 'normal', 'status' => 'open', 'unread' => true,
            'messages' => [
                $m('company', $ME, '2026-10-21T07:55', "Hi Joy,\n\nVND-10462 has been in your delivery queue since yesterday and no rider is assigned. Please assign one today."),
                $m('hub', 'Joy Mercado', '2026-10-21T10:02', "Hello Ma'am,\n\nAssigned to Marlon Reyes. He is out delivering it now, and I will confirm once it is delivered."),
            ],
        ],
        [
            'id' => 5, 'subject' => 'Los Banos Station: manager has not signed in', 'hub' => 'Los Banos Station', 'contact' => 'Carlo Ramos', 'topic' => 'Hub setup',
            'ref' => ['hub', 'Laguna Hub'], 'priority' => 'normal', 'status' => 'open', 'unread' => false,
            'messages' => [
                $m('company', $ME, '2026-10-20T09:20', "Hi Carlo,\n\nWelcome to the network. Your account is ready, but you have not signed in yet. Please sign in with the temporary password we sent, change it, and add your riders so the station can start receiving parcels."),
            ],
        ],
        [
            'id' => 6, 'subject' => 'VND-10451: buyer was not home', 'hub' => 'Santa Cruz Station', 'contact' => 'Mark Dela Cruz', 'topic' => 'Failed delivery',
            'ref' => ['parcel', 'VND-10451'], 'priority' => 'normal', 'status' => 'resolved', 'unread' => false,
            'messages' => [
                $m('company', $ME, '2026-10-20T16:10', "Hi Mark,\n\nVND-10451 failed yesterday because the buyer was not home. Can Ella try again?"),
                $m('hub', 'Mark Dela Cruz', '2026-10-20T16:45', "Yes. The buyer replied to our text and will be home tomorrow morning. Ella will deliver it first thing."),
                $m('company', $ME, '2026-10-20T16:50', 'Thank you, Mark. Closing this for now.'),
            ],
        ],
        // 7 and 8 are waiting for the hub's answer, so Province Hub > Messages and Station Hub > Messages each have a conversation to reply to.
        [
            'id' => 7, 'subject' => 'HT-2043 to Quezon Hub: please confirm loading', 'hub' => 'Laguna Hub', 'contact' => 'Ana Reyes', 'topic' => 'Trip problem',
            'ref' => ['trip', 'HT-2043'], 'priority' => 'normal', 'status' => 'open', 'unread' => false,
            'messages' => [
                $m('company', $ME, '2026-10-21T10:05', "Hi Ana,\n\nHT-2043 leaves for Quezon Hub at 6:00 AM tomorrow with 340 parcels on TRK-001 (driver Ramon Aquino). HT-2042 ran late today, so I want to be sure this one leaves on time.\n\nPlease confirm that all 340 parcels will be loaded by 5:30 AM and that the truck is ready."),
            ],
        ],
        [
            'id' => 8, 'subject' => 'VND-10501: pickup request in Patimbao has no rider', 'hub' => 'Santa Cruz Station', 'contact' => 'Mark Dela Cruz', 'topic' => 'Delayed parcel',
            'ref' => ['none', ''], 'priority' => 'normal', 'status' => 'open', 'unread' => false,
            'messages' => [
                $m('company', $ME, '2026-10-21T10:12', "Hi Mark,\n\nThe seller's pickup request VND-10501 in Brgy. Patimbao has waited for a rider for over 2 hours. Please assign a rider today and tell me when the parcel has been picked up."),
            ],
        ],
    ],
    'topics' => ['Delayed parcel', 'Failed delivery', 'Trip problem', 'Driver or vehicle', 'Hub setup', 'Other'],
];
