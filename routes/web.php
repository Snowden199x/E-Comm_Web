<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\RegistrationController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\SellerComplianceController;
use App\Http\Controllers\Admin\ComplaintController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PlatformSettingsController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\CommissionController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\MessageController;
use App\Http\Controllers\Admin\AccountManagementController;

use App\Http\Controllers\Auth\UnifiedLoginController;
use App\Http\Controllers\Auth\EmailOtpController;
use App\Http\Controllers\Auth\UserPasswordResetLinkController;
use App\Http\Controllers\Auth\UserNewPasswordController;

use App\Http\Controllers\Buyer\OtpController;
use App\Http\Controllers\Buyer\RegisteredBuyerController;
use App\Http\Controllers\Buyer\AuthenticatedSessionController as BuyerAuthenticatedSessionController;
use App\Http\Controllers\Buyer\DashboardController as BuyerDashboardController;
use App\Http\Controllers\Buyer\CategoryController as BuyerCategoryController;
use App\Http\Controllers\Buyer\ProductController as BuyerProductController;
use App\Http\Controllers\Buyer\CartController as BuyerCartController;
use App\Http\Controllers\Buyer\CheckoutController as BuyerCheckoutController;
use App\Http\Controllers\Buyer\MessageController as BuyerMessageController;
use App\Http\Controllers\Buyer\AccountController as BuyerAccountController;
use App\Http\Controllers\Buyer\OrderController as BuyerOrderController;

use App\Http\Controllers\Seller\RegisteredUserController as SellerRegisteredUserController;
use App\Http\Controllers\Seller\AuthenticatedSessionController as SellerAuthenticatedSessionController;
use App\Http\Controllers\Seller\DashboardController as SellerDashboardController;

use App\Http\Controllers\Logistics\Auth\RegisteredUserController as LogisticsRegisteredUserController;
use App\Http\Controllers\Logistics\Auth\AuthenticatedSessionController as LogisticsAuthenticatedSessionController;
use App\Http\Controllers\Logistics\DashboardController as LogisticsDashboardController;

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
            Route::post('/{complaint}/status', [ComplaintController::class, 'updateStatus'])->name('update-status');
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
    Route::get('/register', function () {
        return view('auth.register-buyer');
    })->name('register');
    Route::post('/register', [RegisteredBuyerController::class, 'store'])->name('register.store');

    Route::get('/login', function () {
        return view('auth.login-buyer');
    })->name('login');
    Route::post('/login', [BuyerAuthenticatedSessionController::class, 'store'])->name('login.store');
    Route::post('/logout', [BuyerAuthenticatedSessionController::class, 'destroy'])->middleware('auth')->name('logout');

    Route::get('/dashboard', [BuyerDashboardController::class, 'index'])->middleware('auth')->name('dashboard');
    Route::get('/categories', [BuyerCategoryController::class, 'index'])->middleware('auth')->name('categories');
    Route::get('/products', [BuyerProductController::class, 'index'])->middleware('auth')->name('products.index');
    Route::get('/products/{product}', [BuyerProductController::class, 'show'])->middleware('auth')->name('products.show');
    Route::get('/cart', [BuyerCartController::class, 'index'])->middleware('auth')->name('cart.index');
    Route::post('/cart', [BuyerCartController::class, 'store'])->middleware('auth')->name('cart.store');
    Route::patch('/cart/{cartItem}', [BuyerCartController::class, 'update'])->middleware('auth')->name('cart.update');
    Route::delete('/cart/{cartItem}', [BuyerCartController::class, 'destroy'])->middleware('auth')->name('cart.destroy');
    Route::get('/checkout', [BuyerCheckoutController::class, 'index'])->middleware('auth')->name('checkout.index');
    Route::post('/checkout', [BuyerCheckoutController::class, 'store'])->middleware('auth')->name('checkout.store');
    Route::get('/orders', [BuyerOrderController::class, 'index'])->middleware('auth')->name('orders.index');
    Route::get('/orders/{order}', [BuyerOrderController::class, 'show'])->middleware('auth')->name('orders.show');
    Route::get('/messages', [BuyerMessageController::class, 'index'])->middleware('auth')->name('messages.index');
    Route::post('/messages/start', [BuyerMessageController::class, 'start'])->middleware('auth')->name('messages.start');
    Route::post('/messages/{conversation}/close', [BuyerMessageController::class, 'close'])->middleware('auth')->name('messages.close');
    Route::get('/messages/{conversation}/fetch', [BuyerMessageController::class, 'fetch'])->middleware('auth')->name('messages.fetch');
    Route::post('/messages/{conversation}', [BuyerMessageController::class, 'store'])->middleware('auth')->name('messages.store');
    Route::get('/account', [BuyerAccountController::class, 'index'])->middleware('auth')->name('account.index');
    Route::put('/account', [BuyerAccountController::class, 'update'])->middleware('auth')->name('account.update');
    Route::put('/account/password', [BuyerAccountController::class, 'updatePassword'])->middleware('auth')->name('account.password');
    Route::get('/messages/fetch', [BuyerMessageController::class, 'fetch'])->middleware('auth')->name('messages.fetch');
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

    Route::get('/dashboard', function () {
        return view('coming-soon', ['title' => 'Seller Dashboard — Coming Soon']);
    })->middleware('auth')->name('dashboard');
});

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

    Route::get('/dashboard', [LogisticsDashboardController::class, 'index'])->name('dashboard');

    Route::get('/forgot-password', function () {
        return view('auth.forgot-password-logistics');
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
require __DIR__ . '/auth.php';
