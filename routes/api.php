<?php

use App\Http\Controllers\Api\RiderAuthController;
use App\Http\Controllers\Api\RiderEmailOtpController;
use App\Http\Controllers\Api\RiderGoogleAuthController;
use App\Http\Controllers\Api\RiderPasswordResetController;
use App\Http\Controllers\Api\RiderProfileController;
use App\Http\Controllers\Api\RiderRegistrationController;
use App\Http\Controllers\Api\RiderNotificationController;
use App\Http\Controllers\Api\RiderPushTokenController;
use App\Http\Controllers\Api\RiderScanController;
use App\Http\Controllers\Api\RiderFacilityScanController;
use App\Http\Controllers\Api\RiderDeliveryProofController;
use App\Http\Controllers\Api\RiderDeliveryRescheduleController;
use App\Http\Controllers\Api\ShScannerAuthController;
use App\Http\Controllers\Api\ShScannerScanController;
use App\Http\Controllers\Api\Buyer\BuyerAccountController;
use App\Http\Controllers\Api\Buyer\BuyerAuthController;
use App\Http\Controllers\Api\Buyer\BuyerCartController;
use App\Http\Controllers\Api\Buyer\BuyerCatalogController;
use App\Http\Controllers\Api\Buyer\BuyerComplaintController;
use App\Http\Controllers\Api\Buyer\BuyerCheckoutController;
use App\Http\Controllers\Api\Buyer\BuyerEmailOtpController;
use App\Http\Controllers\Api\Buyer\BuyerLocationController;
use App\Http\Controllers\Api\Buyer\BuyerMessageController;
use App\Http\Controllers\Api\Buyer\BuyerNotificationController;
use App\Http\Controllers\Api\Buyer\BuyerOrderController;
use App\Http\Controllers\Api\Buyer\BuyerPasswordResetController;
use App\Http\Controllers\Api\Buyer\BuyerRegistrationController;
use App\Http\Controllers\Api\Buyer\BuyerSavedItemController;
use App\Http\Controllers\Api\Buyer\BuyerStandingController;
use App\Http\Controllers\Api\Buyer\BuyerSupportController;
use App\Http\Middleware\EnsureApprovedBuyerToken;
use App\Http\Middleware\EnsureApprovedRiderToken;
use App\Http\Middleware\EnsureApprovedTransportDriverToken;
use App\Http\Middleware\EnsureShScannerStation;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/transport-drivers')->group(function () {
    Route::post('/login', [\App\Http\Controllers\Api\TransportDriverApiController::class, 'login'])->middleware('throttle:5,1');
    Route::post('/google', [\App\Http\Controllers\Api\TransportDriverGoogleAuthController::class, 'exchange'])->middleware('throttle:10,1');
    Route::get('/province-hubs', [\App\Http\Controllers\Api\TransportDriverRegistrationController::class, 'hubs'])->middleware('throttle:30,1');
    Route::get('/locations', [\App\Http\Controllers\Api\TransportDriverRegistrationController::class, 'locations'])->middleware('throttle:30,1');
    Route::get('/locations/{cityCode}/barangays', [\App\Http\Controllers\Api\TransportDriverRegistrationController::class, 'barangays'])->middleware('throttle:30,1');
    Route::post('/application-status', [\App\Http\Controllers\Api\TransportDriverRegistrationController::class, 'status'])->middleware('throttle:10,1');
    Route::post('/password/otp/send', [\App\Http\Controllers\Api\TransportDriverPasswordResetController::class, 'sendCode'])->middleware('throttle:5,1');
    Route::post('/password/otp/verify', [\App\Http\Controllers\Api\TransportDriverPasswordResetController::class, 'verifyCode'])->middleware('throttle:10,1');
    Route::post('/password/reset', [\App\Http\Controllers\Api\TransportDriverPasswordResetController::class, 'reset'])->middleware('throttle:5,1');
    Route::post('/email/otp/send', [RiderEmailOtpController::class, 'sendRegistrationCode'])->middleware('throttle:5,1');
    Route::post('/email/otp/verify', [RiderEmailOtpController::class, 'verifyRegistrationCode'])->middleware('throttle:10,1');
    Route::post('/register', [\App\Http\Controllers\Api\TransportDriverRegistrationController::class, 'store'])->middleware('throttle:5,1');
    Route::middleware(['auth:sanctum', EnsureApprovedTransportDriverToken::class, 'throttle:30,1'])->group(function () {
        Route::get('/me', [\App\Http\Controllers\Api\TransportDriverApiController::class, 'me']);
        Route::get('/trips', [\App\Http\Controllers\Api\TransportDriverApiController::class, 'trips']);
        Route::get('/trips/history', [\App\Http\Controllers\Api\TransportDriverApiController::class, 'history']);
        // One trip, for the destination handover screen: it polls this so the
        // receiving count follows the station's scans without a full board load.
        Route::get('/trips/{trip}', [\App\Http\Controllers\Api\TransportDriverApiController::class, 'trip'])->whereNumber('trip');
        Route::get('/earnings', [\App\Http\Controllers\Api\RiderEarningsController::class, 'transportDriver']);
        Route::post('/trips/{trip}/scan', [\App\Http\Controllers\Api\TransportDriverApiController::class, 'scan']);
        Route::post('/trips/{trip}/depart', [\App\Http\Controllers\Api\TransportDriverApiController::class, 'depart']);
        Route::post('/trips/{trip}/arrive', [\App\Http\Controllers\Api\TransportDriverApiController::class, 'arrive']);
        Route::post('/logout', [\App\Http\Controllers\Api\TransportDriverApiController::class, 'logout']);
    });
});

Route::prefix('v1/rider')->group(function () {
    Route::get('/locations', [RiderRegistrationController::class, 'locations'])->middleware('throttle:30,1');
    Route::get('/locations/{cityCode}/barangays', [RiderRegistrationController::class, 'barangays'])->middleware('throttle:30,1');
    Route::get('/logistics-companies', [RiderRegistrationController::class, 'companies'])->middleware('throttle:30,1');
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
        Route::post('/assignments/{order}/accept', [RiderScanController::class, 'accept'])->whereNumber('order');
        Route::get('/profile', [RiderProfileController::class, 'show']);
        Route::put('/profile', [RiderProfileController::class, 'update']);
        Route::post('/profile/photo', [RiderProfileController::class, 'uploadPhoto']);
        Route::put('/profile/password', [RiderProfileController::class, 'updatePassword']);
        Route::get('/notifications', [RiderNotificationController::class, 'index']);
        Route::post('/push-tokens', [RiderPushTokenController::class, 'store']);
        Route::delete('/push-tokens', [RiderPushTokenController::class, 'destroy']);
        Route::post('/notifications/read-all', [RiderNotificationController::class, 'readAll']);
        Route::post('/notifications/{notification}/read', [RiderNotificationController::class, 'read'])
            ->whereNumber('notification');
        Route::get('/earnings', [\App\Http\Controllers\Api\RiderEarningsController::class, 'rider']);
        Route::get('/cash', [\App\Http\Controllers\Api\RiderCashController::class, 'show']);
        Route::post('/tasks/{task}/exceptions', [\App\Http\Controllers\Api\RiderTaskExceptionController::class, 'store']);
        Route::post('/scans', [RiderScanController::class, 'store']);
        Route::post('/pickups/bulk', [RiderScanController::class, 'bulkPickup']);
        Route::post('/delivery-proofs', [RiderDeliveryProofController::class, 'store']);
        Route::get('/deliveries/history', [\App\Http\Controllers\Api\RiderDeliveryHistoryController::class, 'index']);
        Route::post('/deliveries/{order}/reschedule', [RiderDeliveryRescheduleController::class, 'store'])
            ->whereNumber('order');
        Route::post('/deliveries/{order}/return-scan', [\App\Http\Controllers\Api\RiderReturnScanController::class, 'store'])
            ->whereNumber('order');
        Route::post('/deliveries/{order}/return-pickup', [\App\Http\Controllers\Api\RiderReturnLegController::class, 'pickup'])
            ->whereNumber('order');
        Route::post('/deliveries/{order}/return-handover', [\App\Http\Controllers\Api\RiderReturnLegController::class, 'handover'])
            ->whereNumber('order');
        Route::post('/facility-scans', [RiderFacilityScanController::class, 'store']);
        Route::post('/logout', [RiderAuthController::class, 'logout']);
    });
});

Route::prefix('v1/buyer')->group(function () {
    Route::post('/login', [BuyerAuthController::class, 'login'])->middleware('throttle:10,1');
    Route::post('/google', [\App\Http\Controllers\Api\Buyer\BuyerGoogleAuthController::class, 'exchange'])->middleware('throttle:10,1');
    Route::post('/email/otp/send', [BuyerEmailOtpController::class, 'sendRegistrationCode'])->middleware('throttle:5,1');
    Route::post('/email/otp/verify', [BuyerEmailOtpController::class, 'verifyRegistrationCode'])->middleware('throttle:10,1');
    Route::post('/register', [BuyerRegistrationController::class, 'store'])->middleware('throttle:5,1');
    Route::get('/locations', [BuyerLocationController::class, 'index'])->middleware('throttle:30,1');
    Route::get('/locations/{cityCode}/barangays', [BuyerLocationController::class, 'barangays'])->middleware('throttle:30,1');
    Route::post('/password/otp/send', [BuyerPasswordResetController::class, 'sendCode'])->middleware('throttle:5,1');
    Route::post('/password/otp/verify', [BuyerPasswordResetController::class, 'verifyCode'])->middleware('throttle:10,1');
    Route::post('/password/reset', [BuyerPasswordResetController::class, 'reset'])->middleware('throttle:5,1');

    // Reachable while suspended (unlike the group below) so a Buyer can always
    // read their strikes — the same rule the web account-standing page follows.
    Route::get('/account-standing', [BuyerStandingController::class, 'show'])
        ->middleware(['auth:sanctum', 'throttle:60,1,buyer-standing']);

    // Each throttle in this group carries its own prefix. Laravel keys a
    // throttle by the authenticated user alone, so without a prefix the group
    // counter (every API call) would be the same bucket the stricter per-route
    // limits read, and a busy minute would 429 placing an order.
    Route::middleware(['auth:sanctum', EnsureApprovedBuyerToken::class, 'throttle:120,1,buyer'])->group(function () {
        Route::post('/logout', [BuyerAuthController::class, 'logout']);
        Route::get('/me', [BuyerAuthController::class, 'me']);

        Route::get('/home', [BuyerCatalogController::class, 'home']);
        Route::get('/categories', [BuyerCatalogController::class, 'categories']);
        Route::get('/products', [BuyerCatalogController::class, 'products']);
        Route::get('/products/{product}', [BuyerCatalogController::class, 'product'])->whereNumber('product');
        Route::get('/sellers', [BuyerCatalogController::class, 'sellers']);
        Route::get('/sellers/{seller}', [BuyerCatalogController::class, 'seller'])->whereNumber('seller');

        Route::get('/saved-items', [BuyerSavedItemController::class, 'index']);
        Route::post('/saved-items/{product}', [BuyerSavedItemController::class, 'store'])->whereNumber('product');
        Route::delete('/saved-items/{product}', [BuyerSavedItemController::class, 'destroy'])->whereNumber('product');
        Route::delete('/saved-items', [BuyerSavedItemController::class, 'clear']);

        Route::get('/cart', [BuyerCartController::class, 'index']);
        Route::post('/cart', [BuyerCartController::class, 'store']);
        Route::patch('/cart/{cartItem}', [BuyerCartController::class, 'update'])->whereNumber('cartItem');
        Route::delete('/cart/{cartItem}', [BuyerCartController::class, 'destroy'])->whereNumber('cartItem');

        Route::get('/geo/config', [\App\Http\Controllers\Api\Buyer\BuyerGeoController::class, 'config']);
        Route::get('/geo/search', [\App\Http\Controllers\Api\Buyer\BuyerGeoController::class, 'search'])->middleware('throttle:30,1,buyer-geo');
        Route::get('/geo/reverse', [\App\Http\Controllers\Api\Buyer\BuyerGeoController::class, 'reverse'])->middleware('throttle:30,1,buyer-geo');

        Route::post('/checkout/preview', [BuyerCheckoutController::class, 'preview']);
        Route::post('/checkout/shipping-quote', [BuyerCheckoutController::class, 'shippingQuote'])->middleware('throttle:20,1,buyer-shipping-quote');
        Route::post('/checkout/voucher-quote', [BuyerCheckoutController::class, 'voucherQuote'])->middleware('throttle:20,1,buyer-voucher-quote');
        Route::post('/checkout', [BuyerCheckoutController::class, 'store'])->middleware('throttle:10,1,buyer-checkout');

        Route::get('/orders', [BuyerOrderController::class, 'index']);
        Route::get('/orders/{order}', [BuyerOrderController::class, 'show'])->whereNumber('order');
        Route::patch('/orders/{order}/cancel', [BuyerOrderController::class, 'cancel'])->whereNumber('order');
        Route::post('/orders/{order}/complete', [BuyerOrderController::class, 'complete'])->whereNumber('order');
        Route::post('/orders/{order}/return', [BuyerOrderController::class, 'requestReturn'])->whereNumber('order')->middleware('throttle:10,1,buyer-return');
        Route::get('/orders/{order}/delivery-proof', [BuyerOrderController::class, 'deliveryProof'])->whereNumber('order');
        Route::post('/order-items/{orderItem}/review', [BuyerOrderController::class, 'review'])->whereNumber('orderItem');

        // The inbox list and Vendo Support, the same rows the web Messages page reads.
        Route::get('/messages', [BuyerMessageController::class, 'index']);
        Route::get('/messages/support', [BuyerSupportController::class, 'show']);
        Route::post('/messages/support', [BuyerSupportController::class, 'start'])
            ->middleware('throttle:5,1,buyer-support');
        Route::get('/messages/support/{conversation}', [BuyerSupportController::class, 'thread'])
            ->whereNumber('conversation');
        Route::post('/messages/support/{conversation}', [BuyerSupportController::class, 'store'])
            ->whereNumber('conversation')->middleware('throttle:20,1,buyer-support-chat');
        Route::post('/messages/support/{conversation}/close', [BuyerSupportController::class, 'close'])
            ->whereNumber('conversation');

        // Seller chat (no order) and per-order chats, the same threads the web chat page uses.
        Route::get('/messages/sellers/{seller}', [BuyerMessageController::class, 'seller'])
            ->whereNumber('seller')->name('api.buyer.messages.seller');
        Route::post('/messages/sellers/{seller}', [BuyerMessageController::class, 'storeSeller'])
            ->whereNumber('seller')->middleware('throttle:20,1,buyer-chat')->name('api.buyer.messages.seller.store');
        Route::get('/messages/sellers/{seller}/fetch', [BuyerMessageController::class, 'fetchSeller'])
            ->whereNumber('seller');
        Route::get('/messages/orders/{order}', [BuyerMessageController::class, 'order'])
            ->whereNumber('order')->name('api.buyer.messages.order');
        Route::post('/messages/orders/{order}', [BuyerMessageController::class, 'storeOrder'])
            ->whereNumber('order')->middleware('throttle:20,1,buyer-chat')->name('api.buyer.messages.order.store');
        Route::get('/messages/orders/{order}/fetch', [BuyerMessageController::class, 'fetchOrder'])
            ->whereNumber('order');
        Route::get('/messages/attachments/{message}', [BuyerMessageController::class, 'attachment'])
            ->whereNumber('message')->name('api.buyer.messages.attachment');

        Route::get('/complaints', [BuyerComplaintController::class, 'index']);

        Route::get('/notifications', [BuyerNotificationController::class, 'index']);
        Route::post('/notifications/read-all', [BuyerNotificationController::class, 'readAll']);
        Route::post('/notifications/{notification}/read', [BuyerNotificationController::class, 'markRead'])->whereNumber('notification');

        Route::get('/vouchers', [\App\Http\Controllers\Api\Buyer\BuyerVoucherController::class, 'index']);
        Route::get('/reviews', [\App\Http\Controllers\Api\Buyer\BuyerReviewController::class, 'index']);
        Route::get('/help', [\App\Http\Controllers\Api\Buyer\BuyerContentController::class, 'help']);
        Route::get('/legal/{page}', [\App\Http\Controllers\Api\Buyer\BuyerContentController::class, 'legal']);

        Route::get('/account', [BuyerAccountController::class, 'show']);
        Route::put('/account', [BuyerAccountController::class, 'update']);
        Route::put('/account/password', [BuyerAccountController::class, 'updatePassword']);
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
