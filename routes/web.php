<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\RegistrationController;
use App\Http\Controllers\Admin\UserManagementController;

Route::get('/', function () {
    return redirect('/login');
});

use App\Http\Controllers\Admin\DashboardController;

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/registrations', [RegistrationController::class, 'index'])->name('registrations.index');
    Route::get('/registrations/{user}', [RegistrationController::class, 'show'])->name('registrations.show');
    Route::post('/registrations/{user}/approve', [RegistrationController::class, 'approve'])->name('registrations.approve');
    Route::post('/registrations/{user}/disapprove', [RegistrationController::class, 'disapprove'])->name('registrations.disapprove');

    Route::get('/user-management', [UserManagementController::class, 'index'])->name('user-management.index');
    Route::post('/user-management/{user}/suspend', [UserManagementController::class, 'suspend'])->name('user-management.suspend');
    Route::post('/user-management/{user}/deactivate', [UserManagementController::class, 'deactivate'])->name('user-management.deactivate');
    Route::post('/user-management/{user}/activate', [UserManagementController::class, 'activate'])->name('user-management.activate');
});

use App\Http\Controllers\Seller\DashboardController as SellerDashboardController;
use App\Http\Controllers\Buyer\DashboardController as BuyerDashboardController;
use App\Http\Controllers\Logistics\Courier\DashboardController as CourierDashboardController;
use App\Http\Controllers\Logistics\DashboardController as LogisticsDashboardController;

Route::get('/logistics/dashboard', [LogisticsDashboardController::class, 'index']);
Route::get('/logistics/courier/dashboard', [CourierDashboardController::class, 'index']);
Route::get('/seller/dashboard', [SellerDashboardController::class, 'index']);
Route::get('/buyer/dashboard', [BuyerDashboardController::class, 'index']);
require __DIR__.'/auth.php';
