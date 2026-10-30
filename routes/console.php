<?php

use App\Services\DeliveryCutoff;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Mark parcels still out for delivery as failed when the daily window closes.
// The Rider app and Station Hub also sweep lazily when their lists load.
Schedule::call(fn () => app(DeliveryCutoff::class)->sweep())
    ->dailyAt(config('delivery.cutoff', '17:00'))
    ->name('close-delivery-window');
