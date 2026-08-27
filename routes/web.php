<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\RegistrationController;

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
