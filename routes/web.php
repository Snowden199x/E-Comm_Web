<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\RegistrationController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\SellerComplianceController;
use App\Http\Controllers\Admin\ComplaintController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Seller\DashboardController as SellerDashboardController;
use App\Http\Controllers\Buyer\DashboardController as BuyerDashboardController;
use App\Http\Controllers\Logistics\Courier\DashboardController as CourierDashboardController;
use App\Http\Controllers\Logistics\DashboardController as LogisticsDashboardController;
use App\Http\Controllers\Admin\PlatformSettingsController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\CommissionController;
use App\Http\Controllers\Admin\ReportController;

// Buyer landing = root domain
Route::get('/', function () {
    return view('home');
});

Route::get('/admin', function () {
    return redirect('/admin/dashboard');
});

Route::get('/seller', function () {
    return view('coming-soon', ['title' => 'Seller Portal — Coming Soon']);
});

Route::get('/logistics', function () {
    return view('coming-soon', ['title' => 'Logistics Portal — Coming Soon']);
});

Route::get('/buyer/login', function () {
    return view('coming-soon', ['title' => 'Coming Soon']);
});

Route::prefix('admin')->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

    Route::middleware('auth')->group(function () {
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
        });

        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/', [ReportController::class, 'index'])->name('index');
            Route::get('/preview', [ReportController::class, 'preview'])->name('preview');
            Route::get('/download', [ReportController::class, 'download'])->name('download');
        });
    });

});

Route::get('/logistics/dashboard', [LogisticsDashboardController::class, 'index']);
Route::get('/logistics/courier/dashboard', [CourierDashboardController::class, 'index']);
Route::get('/seller/dashboard', [SellerDashboardController::class, 'index']);
Route::get('/buyer/dashboard', [BuyerDashboardController::class, 'index']);

require __DIR__.'/auth.php';