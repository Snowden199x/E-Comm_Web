<?php

use App\Http\Controllers\Admin\AccountManagementController;
use App\Http\Controllers\Admin\CommissionController;
use App\Http\Controllers\Admin\ComplaintController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MessageController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\PlatformSettingsController;
use App\Http\Controllers\Admin\ProductReviewModerationController;
use App\Http\Controllers\Admin\RegistrationController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SellerComplianceController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Auth\EmailOtpController;
use App\Http\Controllers\Auth\UnifiedLoginController;
use App\Http\Controllers\Auth\UserNewPasswordController;
use App\Http\Controllers\Auth\UserPasswordResetLinkController;
use App\Http\Controllers\MessageAttachmentController;
use App\Http\Controllers\MarketplaceMessageController;
use App\Http\Controllers\UserReportController;
use App\Http\Controllers\Buyer\AccountController as BuyerAccountController;
use App\Http\Controllers\Buyer\AuthenticatedSessionController as BuyerAuthenticatedSessionController;
use App\Http\Controllers\Buyer\CartController as BuyerCartController;
use App\Http\Controllers\Buyer\CategoryController as BuyerCategoryController;
use App\Http\Controllers\Buyer\CheckoutController as BuyerCheckoutController;
use App\Http\Controllers\Buyer\DashboardController as BuyerDashboardController;
use App\Http\Controllers\Buyer\MessageController as BuyerMessageController;
use App\Http\Controllers\Buyer\NotificationController as BuyerNotificationController;
use App\Http\Controllers\Buyer\OrderController as BuyerOrderController;
use App\Http\Controllers\Buyer\OtpController;
use App\Http\Controllers\Buyer\ProductController as BuyerProductController;
use App\Http\Controllers\Buyer\SellerProfileController as BuyerSellerProfileController;
use App\Http\Controllers\Buyer\RegisteredBuyerController;
use App\Http\Controllers\Logistics\Auth\AuthenticatedSessionController as LogisticsAuthenticatedSessionController;
use App\Http\Controllers\Logistics\Auth\RegisteredUserController as LogisticsRegisteredUserController;
use App\Http\Controllers\Logistics\DashboardController as LogisticsDashboardController;
use App\Http\Controllers\Logistics\DispatchController as LogisticsDispatchController;
use App\Http\Controllers\Seller\AuthenticatedSessionController as SellerAuthenticatedSessionController;
use App\Http\Controllers\Seller\DashboardController as SellerDashboardController;
use App\Http\Controllers\Seller\CompletedOrdersController;
use App\Http\Controllers\Seller\FeedbackController as SellerFeedbackController;
use App\Http\Controllers\Seller\OrderController;
use App\Http\Controllers\Seller\ProductController;
use App\Http\Controllers\Seller\RegisteredUserController as SellerRegisteredUserController;
use App\Http\Controllers\Seller\ShipmentController;
use App\Http\Controllers\Seller\ReportController as SellerReportController;
use App\Http\Controllers\Seller\MessageController as SellerMessageController;
use App\Http\Controllers\Seller\AccountController as SellerAccountController;
use App\Http\Controllers\Seller\BuyerProfileController as SellerBuyerProfileController;
use App\Http\Controllers\Seller\NotificationController as SellerNotificationController;
use App\Http\Middleware\EnsureActiveSeller;
use App\Http\Middleware\EnsureActiveLogisticsCenter;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public / Landing
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('home');
});

Route::get('/register', function () {
    return view('auth.choose-role');
})->name('register.choose');

Route::post('/email/otp/send', [EmailOtpController::class, 'send'])->name('email-otp.send');
Route::post('/email/otp/verify', [EmailOtpController::class, 'verify'])->name('email-otp.verify');

Route::get('/seller', function () {
    return redirect('/seller/login');
});

Route::get('/buyer', function () {
    return redirect('/');
});

/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
*/
Route::get('/admin', function () {
    return redirect('/admin/login');
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth:admin', 'verified'])->name('dashboard');
    Route::get('/live/{scope}', [\App\Http\Controllers\LiveRevisionController::class, 'admin'])->middleware(['auth:admin', 'check.admin.active', 'force.password.change'])->name('live');
    Route::get('/check-status', [AccountManagementController::class, 'checkStatus'])->middleware('auth:admin')->name('check-status');
    Route::middleware(['auth:admin', 'check.admin.active', 'force.password.change'])->group(function () {

        Route::get('/registrations', [RegistrationController::class, 'index'])->name('registrations.index');
        Route::get('/registrations/table', [RegistrationController::class, 'table'])->name('registrations.table');
        Route::get('/registrations/{user}', [RegistrationController::class, 'show'])->name('registrations.show');
        Route::post('/registrations/{user}/approve', [RegistrationController::class, 'approve'])->name('registrations.approve');
        Route::post('/registrations/{user}/disapprove', [RegistrationController::class, 'disapprove'])->name('registrations.disapprove');

        Route::get('/user-management', [UserManagementController::class, 'index'])->name('user-management.index');
        Route::post('/user-management/{user}/suspend', [UserManagementController::class, 'suspend'])->name('user-management.suspend');
        Route::post('/user-management/{user}/deactivate', [UserManagementController::class, 'deactivate'])->name('user-management.deactivate');
        Route::post('/user-management/{user}/activate', [UserManagementController::class, 'activate'])->name('user-management.activate');
        Route::get('/user-management/table', [UserManagementController::class, 'table'])->name('user-management.table');

        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::get('/notifications/recent', [NotificationController::class, 'recent'])->name('notifications.recent');
        Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
        Route::post('/notifications/{notification}/open', [NotificationController::class, 'open'])->whereNumber('notification')->name('notifications.open');
        Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->whereNumber('notification')->name('notifications.read');
        Route::get('/review-reports', [ProductReviewModerationController::class, 'index'])->name('review-reports.index');
        Route::get('/review-reports/{report}', [ProductReviewModerationController::class, 'show'])->whereNumber('report')->name('review-reports.show');
        Route::post('/review-reports/{report}/resolve', [ProductReviewModerationController::class, 'resolve'])->whereNumber('report')->name('review-reports.resolve');
        Route::post('/reviews/{review}/visibility', [ProductReviewModerationController::class, 'visibility'])->whereNumber('review')->name('reviews.visibility');

        Route::prefix('commission')->name('commission.')->group(function () {
            Route::get('/', [CommissionController::class, 'index'])->name('index');
            Route::get('/table', [CommissionController::class, 'table'])->name('table');
            Route::post('/rate', [CommissionController::class, 'updateRate'])->name('update-rate');
            Route::get('/seller/{seller}', [CommissionController::class, 'sellerDetail'])->name('seller-detail');
        });

        Route::prefix('seller-compliance')->name('seller-compliance.')->group(function () {
            Route::get('/', [SellerComplianceController::class, 'overview'])->name('overview');
            Route::get('/products-for-review', [SellerComplianceController::class, 'productsForReview'])->name('products-for-review');
            Route::get('/search-sellers', [SellerComplianceController::class, 'searchSellers'])->name('search-sellers');
            Route::get('/warnings', [SellerComplianceController::class, 'warnings'])->name('warnings');
            Route::get('/violations', [SellerComplianceController::class, 'violations'])->name('violations');
            Route::get('/suspended-sellers', [SellerComplianceController::class, 'suspendedSellers'])->name('suspended-sellers');
            Route::get('/sellers-table', [SellerComplianceController::class, 'sellersTable'])->name('sellers-table');
            Route::get('/products-table', [SellerComplianceController::class, 'productsTable'])->name('products-table');
            Route::get('/warnings-table', [SellerComplianceController::class, 'warningsTable'])->name('warnings-table');
            Route::get('/violations-table', [SellerComplianceController::class, 'violationsTable'])->name('violations-table');
            Route::get('/suspended-sellers-table', [SellerComplianceController::class, 'suspendedSellersTable'])->name('suspended-sellers-table');
            Route::post('/products/{product}/approve', [SellerComplianceController::class, 'approve'])->name('products.approve');
            Route::post('/products/{product}/reject', [SellerComplianceController::class, 'reject'])->name('products.reject');
            Route::post('/products/{product}/warn', [SellerComplianceController::class, 'warn'])->name('products.warn');
        });

        Route::prefix('complaints-disputes')->name('complaints.')->group(function () {
            Route::get('/', [ComplaintController::class, 'index'])->name('index');
            Route::get('/table', [ComplaintController::class, 'table'])->name('table');
            Route::get('/{complaint}', [ComplaintController::class, 'show'])->name('show');
            Route::get('/{complaint}/evidence/{evidence}', [ComplaintController::class, 'evidence'])->name('evidence');
            Route::post('/{complaint}/status', [ComplaintController::class, 'updateStatus'])->name('update-status');
            Route::post('/{complaint}/decision', [ComplaintController::class, 'decide'])->name('decision');
        });

        Route::prefix('platform-settings')->name('platform-settings.')->group(function () {
            Route::get('/', [PlatformSettingsController::class, 'index'])->name('index');
            Route::post('/announcements', [PlatformSettingsController::class, 'storeAnnouncement'])->name('announcements.store');
            Route::post('/policies', [PlatformSettingsController::class, 'storePolicy'])->name('policies.store');
            Route::put('/policies/{policy}', [PlatformSettingsController::class, 'updatePolicy'])->name('policies.update');
            Route::put('/announcements/{announcement}', [PlatformSettingsController::class, 'updateAnnouncement'])->name('announcements.update');
            Route::delete('/announcements/{announcement}', [PlatformSettingsController::class, 'destroyAnnouncement'])->name('announcements.destroy');
            Route::delete('/policies/{policy}', [PlatformSettingsController::class, 'destroyPolicy'])->name('policies.destroy');
            Route::get('/announcements-table', [PlatformSettingsController::class, 'announcementsTable'])->name('announcements-table');
            Route::post('/chat-welcome', [PlatformSettingsController::class, 'updateChatWelcome'])->name('chat-welcome.update');
        });

        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/', [ReportController::class, 'index'])->name('index');
            Route::get('/preview', [ReportController::class, 'preview'])->name('preview');
            Route::get('/download', [ReportController::class, 'download'])->name('download');
        });

        Route::prefix('messages')->name('messages.')->group(function () {
            Route::get('/', [MessageController::class, 'index'])->name('index');
            Route::get('/list', [MessageController::class, 'conversationsList'])->name('list');
            Route::get('/{conversation}/thread', [MessageController::class, 'thread'])->name('thread');
            Route::get('/{conversation}/fetch', [MessageController::class, 'fetchMessages'])->name('fetch');
            Route::post('/{conversation}/send', [MessageController::class, 'send'])->name('send');
            Route::delete('/{conversation}', [\App\Http\Controllers\MessageDeletionController::class, 'supportConversation'])->whereNumber('conversation')->name('conversation.delete');
        });

        Route::prefix('account-management')->name('account-management.')->group(function () {
            Route::get('/', [AccountManagementController::class, 'index'])->name('index');
            Route::get('/table', [AccountManagementController::class, 'table'])->name('table');
            Route::get('/force-password', [AccountManagementController::class, 'showForcePassword'])->name('force-password');
            Route::post('/force-password', [AccountManagementController::class, 'storeForcePassword'])->name('force-password.store');
            Route::get('/create', [AccountManagementController::class, 'create'])->name('create');
            Route::post('/', [AccountManagementController::class, 'store'])->name('store');
            Route::delete('/{admin}', [AccountManagementController::class, 'destroy'])->name('destroy');
            Route::get('/{admin}', [AccountManagementController::class, 'show'])->name('show');
            Route::put('/{admin}', [AccountManagementController::class, 'update'])->name('update');
            Route::post('/{admin}/view-temp-password', [AccountManagementController::class, 'viewTempPassword'])->name('view-temp-password');
            Route::post('/{admin}/suspend', [AccountManagementController::class, 'suspend'])->name('suspend');
            Route::post('/{admin}/reactivate', [AccountManagementController::class, 'reactivate'])->name('reactivate');
            Route::post('/{admin}/send-reset-link', [AccountManagementController::class, 'sendResetLink'])->name('send-reset-link');
            Route::post('/{admin}/restore', [AccountManagementController::class, 'restore'])->name('restore');
            Route::delete('/{admin}/force-delete', [AccountManagementController::class, 'forceDelete'])->name('force-delete');
            Route::put('/profile/update', [AccountManagementController::class, 'updateProfile'])->name('profile.update');
            Route::put('/profile/password', [AccountManagementController::class, 'updatePassword'])->name('profile.password');
        });
    });
});

/*
|--------------------------------------------------------------------------
| Buyer
|--------------------------------------------------------------------------
*/
Route::prefix('buyer')->name('buyer.')->group(function () {
    Route::get('/register', [RegisteredBuyerController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredBuyerController::class, 'store'])->name('register.store');

    Route::get('/login', function () {
        return view('buyer.auth.login');
    })->name('login');
    Route::post('/login', [BuyerAuthenticatedSessionController::class, 'store'])->name('login.store');
    Route::post('/logout', [BuyerAuthenticatedSessionController::class, 'destroy'])->middleware('auth')->name('logout');

    Route::get('/dashboard', [BuyerDashboardController::class, 'index'])->middleware('auth')->name('dashboard');
    Route::get('/live/{scope}', [\App\Http\Controllers\LiveRevisionController::class, 'buyer'])->middleware('auth')->name('live');
    Route::get('/categories', [BuyerCategoryController::class, 'index'])->middleware('auth')->name('categories');
    Route::get('/products', [BuyerProductController::class, 'index'])->middleware('auth')->name('products.index');
    Route::get('/products/{product}', [BuyerProductController::class, 'show'])->middleware('auth')->name('products.show');
    Route::get('/sellers/{seller}', [BuyerSellerProfileController::class, 'show'])->middleware('auth')->whereNumber('seller')->name('sellers.show');
    Route::get('/cart', [BuyerCartController::class, 'index'])->middleware('auth')->name('cart.index');
    Route::post('/cart', [BuyerCartController::class, 'store'])->middleware('auth')->name('cart.store');
    Route::patch('/cart/{cartItem}', [BuyerCartController::class, 'update'])->middleware('auth')->name('cart.update');
    Route::delete('/cart/{cartItem}', [BuyerCartController::class, 'destroy'])->middleware('auth')->name('cart.destroy');
    Route::get('/checkout', [BuyerCheckoutController::class, 'index'])->middleware('auth')->name('checkout.index');
    Route::post('/checkout', [BuyerCheckoutController::class, 'store'])->middleware('auth')->name('checkout.store');
    Route::get('/orders', [BuyerOrderController::class, 'index'])->middleware('auth')->name('orders.index');
    Route::get('/orders/{order}', [BuyerOrderController::class, 'show'])->middleware('auth')->name('orders.show');
    Route::get('/messages/orders/{order}', [MarketplaceMessageController::class, 'buyerShow'])->middleware('auth')->whereNumber('order')->name('marketplace-messages.show');
    Route::post('/messages/orders/{order}', [MarketplaceMessageController::class, 'buyerStore'])->middleware(['auth', 'throttle:20,1'])->whereNumber('order')->name('marketplace-messages.store');
    Route::get('/messages/orders/{order}/fetch', [MarketplaceMessageController::class, 'buyerFetch'])->middleware('auth')->whereNumber('order')->name('marketplace-messages.fetch');
    Route::get('/messages/sellers/{seller}', [MarketplaceMessageController::class, 'buyerSellerShow'])->middleware('auth')->whereNumber('seller')->name('marketplace-messages.seller.show');
    Route::post('/messages/sellers/{seller}', [MarketplaceMessageController::class, 'buyerSellerStore'])->middleware(['auth', 'throttle:20,1'])->whereNumber('seller')->name('marketplace-messages.seller.store');
    Route::get('/messages/sellers/{seller}/fetch', [MarketplaceMessageController::class, 'buyerSellerFetch'])->middleware('auth')->whereNumber('seller')->name('marketplace-messages.seller.fetch');
    Route::post('/orders/{order}/complete', [BuyerOrderController::class, 'complete'])->middleware('auth')->whereNumber('order')->name('orders.complete');
    Route::post('/order-items/{orderItem}/review', [BuyerOrderController::class, 'storeReview'])->middleware('auth')->whereNumber('orderItem')->name('reviews.store');
    Route::get('/messages', [BuyerMessageController::class, 'index'])->middleware('auth')->name('messages.index');
    Route::get('/messages/seller-list', [BuyerMessageController::class, 'sellerList'])->middleware('auth')->name('messages.seller-list');
    Route::post('/messages/start', [BuyerMessageController::class, 'start'])->middleware('auth')->name('messages.start');
    Route::post('/messages/{conversation}/close', [BuyerMessageController::class, 'close'])->middleware('auth')->name('messages.close');
    Route::get('/messages/{conversation}/fetch', [BuyerMessageController::class, 'fetch'])->middleware('auth')->name('messages.fetch');
    Route::post('/messages/{conversation}', [BuyerMessageController::class, 'store'])->middleware('auth')->name('messages.store');
    Route::get('/account', [BuyerAccountController::class, 'index'])->middleware('auth')->name('account.index');
    Route::put('/account', [BuyerAccountController::class, 'update'])->middleware('auth')->name('account.update');
    Route::put('/account/password', [BuyerAccountController::class, 'updatePassword'])->middleware('auth')->name('account.password');
    Route::post('/account/profile-picture', [BuyerAccountController::class, 'uploadProfilePicture'])->middleware('auth')->name('account.profile-picture.upload');
    Route::delete('/account/profile-picture', [BuyerAccountController::class, 'removeProfilePicture'])->middleware('auth')->name('account.profile-picture.remove');
    Route::post('/account/banner', [BuyerAccountController::class, 'uploadBanner'])->middleware('auth')->name('account.banner.upload');
    Route::delete('/account/banner', [BuyerAccountController::class, 'removeBanner'])->middleware('auth')->name('account.banner.remove');
    Route::get('/messages/fetch', [BuyerMessageController::class, 'fetch'])->middleware('auth')->name('messages.fetch');
    Route::post('/user-reports', [UserReportController::class, 'store'])->middleware(['auth', 'throttle:3,1'])->name('user-reports.store');
    Route::get('/notifications', [BuyerNotificationController::class, 'index'])->middleware('auth')->name('notifications.index');
    Route::get('/notifications/recent', [BuyerNotificationController::class, 'recent'])->middleware('auth')->name('notifications.recent');
    Route::post('/notifications/read-all', [BuyerNotificationController::class, 'readAll'])->middleware('auth')->name('notifications.read-all');
    Route::post('/notifications/{notification}/open', [BuyerNotificationController::class, 'open'])->middleware('auth')->whereNumber('notification')->name('notifications.open');
    Route::post('/notifications/{notification}/read', [BuyerNotificationController::class, 'markRead'])->middleware('auth')->whereNumber('notification')->name('notifications.read');
});

Route::post('/buyer/otp/send', [OtpController::class, 'send']);
Route::post('/buyer/otp/verify', [OtpController::class, 'verify']);

/*
|--------------------------------------------------------------------------
| Seller
|--------------------------------------------------------------------------
*/
Route::prefix('seller')->name('seller.')->group(function () {

    Route::get('/register', [SellerRegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [SellerRegisteredUserController::class, 'store'])->name('register.store');

    Route::get('/login', [SellerAuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [SellerAuthenticatedSessionController::class, 'store'])->name('login.store');
    Route::post('/logout', [SellerAuthenticatedSessionController::class, 'destroy'])->middleware('auth')->name('logout');

    Route::middleware(['auth', EnsureActiveSeller::class])->group(function () {
        Route::get('/dashboard', [SellerDashboardController::class, 'index'])->name('dashboard');
        Route::get('/live/{scope}', [\App\Http\Controllers\LiveRevisionController::class, 'seller'])->name('live');
        Route::get('/products', [ProductController::class, 'index'])->name('products.index');
        Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
        Route::post('/products', [ProductController::class, 'store'])->name('products.store');
        Route::get('/products/{product}', [ProductController::class, 'show'])->whereNumber('product')->name('products.show');
        Route::patch('/products/{product}', [ProductController::class, 'update'])->whereNumber('product')->name('products.update');
        Route::post('/products/{product}/restock', [ProductController::class, 'restock'])->whereNumber('product')->name('products.restock');
        Route::get('/shipments', [ShipmentController::class, 'index'])->name('shipments.index');
        Route::get('/shipments/{order}', [ShipmentController::class, 'show'])->whereNumber('order')->name('shipments.show');
        Route::patch('/shipments/{order}', [ShipmentController::class, 'update'])->whereNumber('order')->name('shipments.update');
        Route::patch('/shipments/{order}/tracking', [ShipmentController::class, 'tracking'])->whereNumber('order')->name('shipments.tracking');
        Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}/waybill', [OrderController::class, 'waybill'])->whereNumber('order')->name('orders.waybill');
        Route::get('/orders/{order}', [OrderController::class, 'show'])->whereNumber('order')->name('orders.show');
        Route::patch('/orders/{order}', [OrderController::class, 'update'])->whereNumber('order')->name('orders.update');
        Route::get('/completed-orders', [CompletedOrdersController::class, 'index'])->name('completed-orders.index');
        Route::get('/completed-orders/{order}', [CompletedOrdersController::class, 'show'])->whereNumber('order')->name('completed-orders.show');
        Route::get('/feedback', [SellerFeedbackController::class, 'index'])->name('feedback.index');
        Route::get('/feedback/{review}', [SellerFeedbackController::class, 'show'])->whereNumber('review')->name('feedback.show');
        Route::post('/feedback/{review}/reply', [SellerFeedbackController::class, 'reply'])->whereNumber('review')->name('feedback.reply');
        Route::post('/feedback/{review}/report', [SellerFeedbackController::class, 'report'])->whereNumber('review')->name('feedback.report');
        Route::get('/reports', [SellerReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/preview', [SellerReportController::class, 'preview'])->name('reports.preview');
        Route::get('/reports/download', [SellerReportController::class, 'download'])->name('reports.download');
        Route::get('/messages', [SellerMessageController::class, 'index'])->name('messages.index');
        Route::get('/messages/customer-list', [SellerMessageController::class, 'customerList'])->name('messages.customer-list');
        Route::get('/messages/orders/{conversation}', [MarketplaceMessageController::class, 'sellerShow'])->whereNumber('conversation')->name('marketplace-messages.show');
        Route::post('/messages/orders/{conversation}', [MarketplaceMessageController::class, 'sellerStore'])->middleware('throttle:20,1')->whereNumber('conversation')->name('marketplace-messages.store');
        Route::get('/messages/orders/{conversation}/fetch', [MarketplaceMessageController::class, 'sellerFetch'])->whereNumber('conversation')->name('marketplace-messages.fetch');
        Route::post('/messages/start', [SellerMessageController::class, 'start'])->middleware('throttle:20,1')->name('messages.start');
        Route::get('/messages/{conversation}/fetch', [SellerMessageController::class, 'fetch'])->whereNumber('conversation')->name('messages.fetch');
        Route::post('/messages/{conversation}', [SellerMessageController::class, 'store'])->middleware('throttle:20,1')->whereNumber('conversation')->name('messages.store');
        Route::post('/messages/{conversation}/close', [SellerMessageController::class, 'close'])->whereNumber('conversation')->name('messages.close');
        Route::post('/messages/{conversation}/reopen', [SellerMessageController::class, 'reopen'])->whereNumber('conversation')->name('messages.reopen');
        Route::get('/buyers/{buyer}', [SellerBuyerProfileController::class, 'show'])->whereNumber('buyer')->name('buyers.show');
        Route::get('/account', [SellerAccountController::class, 'index'])->name('account.index');
        Route::patch('/account', [SellerAccountController::class, 'update'])->name('account.update');
        Route::post('/account/avatar', [SellerAccountController::class, 'avatar'])->name('account.avatar');
        Route::delete('/account/avatar', [SellerAccountController::class, 'removeAvatar'])->name('account.avatar.remove');
        Route::post('/account/banner', [SellerAccountController::class, 'banner'])->name('account.banner');
        Route::delete('/account/banner', [SellerAccountController::class, 'removeBanner'])->name('account.banner.remove');
        Route::patch('/account/password', [SellerAccountController::class, 'password'])->middleware('throttle:5,1')->name('account.password');
        Route::post('/user-reports', [UserReportController::class, 'store'])->middleware('throttle:3,1')->name('user-reports.store');
        Route::get('/notifications', [SellerNotificationController::class, 'index'])->name('notifications.index');
        Route::get('/notifications/recent', [SellerNotificationController::class, 'recent'])->name('notifications.recent');
        Route::post('/notifications/read-all', [SellerNotificationController::class, 'readAll'])->name('notifications.read-all');
        Route::post('/notifications/{notification}/open', [SellerNotificationController::class, 'open'])->whereNumber('notification')->name('notifications.open');
        Route::post('/notifications/{notification}/read', [SellerNotificationController::class, 'markRead'])->whereNumber('notification')->name('notifications.read');
    });
});

Route::get('/message-attachments/{attachment}', [MessageAttachmentController::class, 'show'])
    ->middleware('auth:admin,web')->whereNumber('attachment')->name('messages.attachments.show');
Route::get('/marketplace-messages/{message}/attachment', [MarketplaceMessageController::class, 'attachment'])
    ->middleware('auth')->whereNumber('message')->name('marketplace-messages.attachment');

/*
|--------------------------------------------------------------------------
| Logistics
|--------------------------------------------------------------------------
*/
Route::prefix('logistics')->name('logistics.')->group(function () {
    Route::get('/', function () {
        return view('logistics.landing');
    })->name('landing');

    Route::get('/register', [LogisticsRegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [LogisticsRegisteredUserController::class, 'store'])->name('register.store');

    Route::get('/login', [LogisticsAuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [LogisticsAuthenticatedSessionController::class, 'store'])->name('login.store');
    Route::post('/logout', [LogisticsAuthenticatedSessionController::class, 'destroy'])->middleware('auth')->name('logout');

    Route::middleware(['auth', EnsureActiveLogisticsCenter::class])->group(function () {
        Route::get('/dashboard', [LogisticsDashboardController::class, 'index'])->name('dashboard');
        Route::get('/incoming-parcels', [LogisticsDispatchController::class, 'index'])->defaults('lane', 'incoming')->name('incoming-parcels');
        Route::get('/parcel-sorting', [LogisticsDispatchController::class, 'index'])->defaults('lane', 'sorting')->name('parcel-sorting');
        Route::get('/delivery-assignments', [LogisticsDispatchController::class, 'index'])->defaults('lane', 'delivery')->name('delivery-assignments');
        Route::get('/delivery-monitoring', fn () => view('logistics.placeholder', ['title' => 'Delivery Monitoring']))->name('delivery-monitoring');
        Route::get('/reports', fn () => view('logistics.placeholder', ['title' => 'Reports']))->name('reports');
        Route::get('/messages', fn () => view('logistics.placeholder', ['title' => 'Messages']))->name('messages');
        Route::get('/account', [\App\Http\Controllers\Logistics\AccountController::class, 'index'])->name('account.index');
        Route::post('/riders/{courierDetail}/approve', [LogisticsDashboardController::class, 'approveRider'])
            ->whereNumber('courierDetail')->name('riders.approve');
        Route::post('/riders/{courierDetail}/reject', [LogisticsDashboardController::class, 'rejectRider'])
            ->whereNumber('courierDetail')->name('riders.reject');
        Route::get('/dispatch', [LogisticsDispatchController::class, 'index'])->name('dispatch.index');
        Route::post('/dispatch/{order}/courier', [LogisticsDispatchController::class, 'assignCourier'])
            ->whereNumber('order')->name('dispatch.courier');
        Route::post('/dispatch/{order}/arrive', [LogisticsDispatchController::class, 'markArrived'])
            ->whereNumber('order')->name('dispatch.arrive');
        Route::post('/dispatch/{order}/sort', [LogisticsDispatchController::class, 'markSorted'])
            ->whereNumber('order')->name('dispatch.sort');
        Route::post('/dispatch/{order}/send-to-hub', [LogisticsDispatchController::class, 'sendToHub'])
            ->whereNumber('order')->name('dispatch.send-to-hub');
        Route::post('/dispatch/{order}/receive', [LogisticsDispatchController::class, 'receiveAtHub'])
            ->whereNumber('order')->name('dispatch.receive');
        Route::post('/dispatch/{order}/delivery-rider', [LogisticsDispatchController::class, 'assignDeliveryCourier'])
            ->whereNumber('order')->name('dispatch.delivery-rider');
    });

    Route::get('/forgot-password', function () {
        return view('logistics.auth.forgot-password');
    })->name('password.request');

    // Temporary placeholder — individual riders will apply via the Vendo Rider mobile app,
    // which isn't built yet. Swap this for a real app-store/landing redirect once it exists.
    Route::get('/get-the-app', function () {
        return view('coming-soon', ['title' => 'Vendo Rider App — Coming Soon']);
    })->name('rider-app');
});

Route::get('/login', [UnifiedLoginController::class, 'create'])->name('login');
Route::post('/login', [UnifiedLoginController::class, 'store'])->name('login.store');
Route::post('/logout', [UnifiedLoginController::class, 'destroy'])->middleware('auth')->name('logout');

Route::get('/forgot-password', [UserPasswordResetLinkController::class, 'create'])->name('password.request');
Route::post('/forgot-password', [UserPasswordResetLinkController::class, 'store'])->name('password.email');
Route::get('/reset-password/{token}', [UserNewPasswordController::class, 'create'])->name('password.reset');
Route::post('/reset-password', [UserNewPasswordController::class, 'store'])->name('password.store');
require __DIR__.'/auth.php';
