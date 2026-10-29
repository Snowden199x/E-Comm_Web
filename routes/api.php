<?php

use App\Http\Controllers\Api\RiderAuthController;
use App\Http\Controllers\Api\RiderEmailOtpController;
use App\Http\Controllers\Api\RiderGoogleAuthController;
use App\Http\Controllers\Api\RiderPasswordResetController;
use App\Http\Controllers\Api\RiderRegistrationController;
use App\Http\Controllers\Api\RiderScanController;
use App\Http\Controllers\Api\RiderFacilityScanController;
use App\Http\Controllers\Api\RiderDeliveryProofController;
use App\Http\Controllers\Api\ShScannerAuthController;
use App\Http\Controllers\Api\ShScannerScanController;
use App\Http\Middleware\EnsureApprovedRiderToken;
use App\Http\Middleware\EnsureApprovedTransportDriverToken;
use App\Http\Middleware\EnsureShScannerStation;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/transport-drivers')->group(function () {
    Route::post('/login', [\App\Http\Controllers\Api\TransportDriverApiController::class, 'login'])->middleware('throttle:5,1');
    Route::get('/province-hubs', [\App\Http\Controllers\Api\TransportDriverRegistrationController::class, 'hubs'])->middleware('throttle:30,1');
    Route::post('/email/otp/send', [RiderEmailOtpController::class, 'sendRegistrationCode'])->middleware('throttle:5,1');
    Route::post('/email/otp/verify', [RiderEmailOtpController::class, 'verifyRegistrationCode'])->middleware('throttle:10,1');
    Route::post('/register', [\App\Http\Controllers\Api\TransportDriverRegistrationController::class, 'store'])->middleware('throttle:5,1');
    Route::middleware(['auth:sanctum', EnsureApprovedTransportDriverToken::class, 'throttle:30,1'])->group(function () {
        Route::get('/trips', [\App\Http\Controllers\Api\TransportDriverApiController::class, 'trips']);
        Route::get('/earnings', [\App\Http\Controllers\Api\RiderEarningsController::class, 'transportDriver']);
        Route::post('/trips/{trip}/depart', [\App\Http\Controllers\Api\TransportDriverApiController::class, 'depart']);
        Route::post('/trips/{trip}/arrive', [\App\Http\Controllers\Api\TransportDriverApiController::class, 'arrive']);
        Route::post('/logout', [\App\Http\Controllers\Api\TransportDriverApiController::class, 'logout']);
    });
});

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
        Route::get('/earnings', [\App\Http\Controllers\Api\RiderEarningsController::class, 'rider']);
        Route::get('/cash', [\App\Http\Controllers\Api\RiderCashController::class, 'show']);
        Route::post('/tasks/{task}/exceptions', [\App\Http\Controllers\Api\RiderTaskExceptionController::class, 'store']);
        Route::post('/scans', [RiderScanController::class, 'store']);
        Route::post('/delivery-proofs', [RiderDeliveryProofController::class, 'store']);
        Route::post('/facility-scans', [RiderFacilityScanController::class, 'store']);
        Route::post('/logout', [RiderAuthController::class, 'logout']);
    });
});

Route::prefix('v1/sh-scanner')->group(function () {
    Route::post('/pair', [ShScannerAuthController::class, 'pair'])->middleware('throttle:10,1');
    Route::middleware([EnsureShScannerStation::class, 'throttle:60,1'])->group(function () {
        Route::get('/session', [ShScannerAuthController::class, 'session']);
        Route::post('/lookup', [ShScannerScanController::class, 'lookup']);
        Route::post('/scans', [ShScannerScanController::class, 'store']);
        Route::get('/scans', [ShScannerScanController::class, 'history']);
        Route::post('/logout', [ShScannerAuthController::class, 'logout']);
    });
});
