<?php

use App\Http\Controllers\Api\RiderAuthController;
use App\Http\Controllers\Api\RiderScanController;
use App\Http\Middleware\EnsureApprovedRiderToken;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/rider')->group(function () {
    Route::post('/login', [RiderAuthController::class, 'login'])->middleware('throttle:5,1');

    Route::middleware(['auth:sanctum', EnsureApprovedRiderToken::class, 'throttle:30,1'])->group(function () {
        Route::get('/assignments', [RiderScanController::class, 'assignments']);
        Route::post('/scans', [RiderScanController::class, 'store']);
        Route::post('/logout', [RiderAuthController::class, 'logout']);
    });
});
