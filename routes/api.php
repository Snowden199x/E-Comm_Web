<?php

use App\Http\Controllers\Api\RiderAuthController;
use App\Http\Controllers\Api\RiderEmailOtpController;
use App\Http\Controllers\Api\RiderGoogleAuthController;
use App\Http\Controllers\Api\RiderPasswordResetController;
use App\Http\Controllers\Api\RiderRegistrationController;
use App\Http\Controllers\Api\RiderScanController;
use App\Http\Middleware\EnsureApprovedRiderToken;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/rider')->group(function () {
    Route::get('/locations', [RiderRegistrationController::class, 'locations'])->middleware('throttle:30,1');
    Route::get('/locations/{cityCode}/barangays', [RiderRegistrationController::class, 'barangays'])->middleware('throttle:30,1');
    Route::post('/register', [RiderRegistrationController::class, 'store'])->middleware('throttle:5,1');
    Route::post('/login', [RiderAuthController::class, 'login'])->middleware('throttle:5,1');
    Route::post('/email/otp/send', [RiderEmailOtpController::class, 'sendRegistrationCode'])->middleware('throttle:5,1');
    Route::post('/email/otp/verify', [RiderEmailOtpController::class, 'verifyRegistrationCode'])->middleware('throttle:10,1');
    Route::post('/google', [RiderGoogleAuthController::class, 'exchange'])->middleware('throttle:10,1');
    Route::post('/password/otp/send', [RiderPasswordResetController::class, 'sendCode'])->middleware('throttle:5,1');
    Route::post('/password/otp/verify', [RiderPasswordResetController::class, 'verifyCode'])->middleware('throttle:10,1');
    Route::post('/password/reset', [RiderPasswordResetController::class, 'reset'])->middleware('throttle:5,1');

    Route::middleware(['auth:sanctum', EnsureApprovedRiderToken::class, 'throttle:30,1'])->group(function () {
        Route::get('/assignments', [RiderScanController::class, 'assignments']);
        Route::post('/scans', [RiderScanController::class, 'store']);
        Route::post('/logout', [RiderAuthController::class, 'logout']);
    });
});
