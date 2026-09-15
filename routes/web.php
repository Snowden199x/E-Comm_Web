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
            Route::delete('/{admin}', [AccountManagementController::class, 'destroy'])->name('destroy');
            Route::post('/{admin}/view-temp-password', [AccountManagementController::class, 'viewTempPassword'])->name('view-temp-password');
            Route::post('/{admin}/suspend', [AccountManagementController::class, 'suspend'])->name('suspend');
            Route::post('/{admin}/reactivate', [AccountManagementController::class, 'reactivate'])->name('reactivate');
            Route::post('/{admin}/deactivate', [AccountManagementController::class, 'deactivate'])->name('deactivate');
            Route::post('/{admin}/send-reset-link', [AccountManagementController::class, 'sendResetLink'])->name('send-reset-link');
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

    Route::get('/dashboard', function () {
        return view('coming-soon', ['title' => 'Buyer Dashboard — Coming Soon']);
    })->middleware('auth')->name('dashboard');});

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
    })->middleware('auth')->name('dashboard');});

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
});

Route::get('/login', [UnifiedLoginController::class, 'create'])->name('login');
Route::post('/login', [UnifiedLoginController::class, 'store'])->name('login.store');
Route::post('/logout', [UnifiedLoginController::class, 'destroy'])->middleware('auth')->name('logout');

Route::get('/forgot-password', [UserPasswordResetLinkController::class, 'create'])->name('password.request');
Route::post('/forgot-password', [UserPasswordResetLinkController::class, 'store'])->name('password.email');
Route::get('/reset-password/{token}', [UserNewPasswordController::class, 'create'])->name('password.reset');
Route::post('/reset-password', [UserNewPasswordController::class, 'store'])->name('password.store');
require __DIR__.'/auth.php';