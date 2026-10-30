<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Daily delivery cutoff
    |--------------------------------------------------------------------------
    |
    | Local time (config/app.php timezone) when a parcel that is still out for
    | delivery is closed for the day and marked Delivery Failed. The Rider can
    | then reschedule it for the next day's run from the Rider app.
    |
    */
    'cutoff' => env('DELIVERY_CUTOFF', '17:00'),
];
