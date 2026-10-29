<?php

use App\Http\Controllers\Logistics\PortalController;
use App\Http\Controllers\Logistics\StationOperationsController;
use App\Http\Controllers\Logistics\StationCashController;
use App\Http\Controllers\Logistics\ProvinceCashController;
use App\Http\Controllers\Logistics\CompanyCashController;
use App\Http\Controllers\Logistics\PortalNotificationController;
use App\Http\Controllers\Logistics\PortalReportController;
use App\Http\Controllers\Logistics\PortalAccountController;
use App\Http\Controllers\Logistics\PortalMessageController;
use App\Http\Controllers\Logistics\CrossCompanyManifestController;
use App\Http\Controllers\Logistics\ParcelExceptionController;
use App\Http\Middleware\EnsureLogisticsPortalRole;
use Illuminate\Support\Facades\Route;

Route::prefix('logistics/manager-password')->name('lgp.manager-password.')->group(function () {
    Route::get('/{token}', [PortalAccountController::class, 'createManagerPassword'])->name('create');
    Route::post('/{token}', [PortalAccountController::class, 'storeManagerPassword'])->name('store');
});

Route::prefix('logistics/portal')->name('lgp.')->middleware('auth')->group(function () {
    Route::post('/account/email', [PortalAccountController::class, 'requestEmailChange'])
        ->middleware('throttle:3,10')->name('account.email.request');
    Route::get('/account/email/confirm', [PortalAccountController::class, 'confirmEmailChange'])
        ->middleware('signed:relative')->name('account.email.confirm');
    Route::get('/account/manager-email/confirm', [PortalAccountController::class, 'confirmManagedManagerEmailChange'])
        ->withoutMiddleware('auth')->middleware('signed:relative')->name('account.manager-email.confirm');
    Route::get('/notifications', [PortalNotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/feed', [PortalNotificationController::class, 'feed'])->name('notifications.feed');
    Route::post('/notifications/read-all', [PortalNotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::post('/notifications/{notification}/open', [PortalNotificationController::class, 'open'])->whereNumber('notification')->name('notifications.open');
    Route::middleware(EnsureLogisticsPortalRole::class.':company')->prefix('company')->group(function () {
        Route::get('/', [PortalController::class, 'companyDashboard'])->name('company');
        Route::get('/hubs', [PortalController::class, 'companyHubs'])->name('company.hubs');
        Route::get('/hubs/manager-feed', [PortalController::class, 'companyHubManagersFeed'])->name('company.hubs.manager-feed');
        Route::post('/hubs', [PortalController::class, 'createProvinceHub'])->name('company.hubs.store');
        Route::patch('/hubs/{hub}', [PortalController::class, 'updateProvinceHubDetails'])->whereNumber('hub')->name('company.hubs.update');
        Route::delete('/hubs/{hub}', [PortalController::class, 'deleteProvinceHub'])->whereNumber('hub')->name('company.hubs.destroy');
        Route::patch('/hubs/{hub}/status', [PortalController::class, 'updateProvinceHub'])->whereNumber('hub')->name('company.hubs.status');
        Route::get('/hubs/{hub}', [PortalController::class, 'companyHubShow'])->whereNumber('hub')->name('company.hubs.show');
        Route::get('/managers', [PortalController::class, 'managerIndex'])->name('company.managers');
        Route::get('/account', [PortalAccountController::class, 'index'])->defaults('portal_role', 'company')->name('company.account');
        Route::patch('/account', [PortalAccountController::class, 'update'])->defaults('portal_role', 'company')->name('company.account.update');
        Route::patch('/account/password', [PortalAccountController::class, 'password'])->defaults('portal_role', 'company')->name('company.account.password');
        Route::post('/account/documents', [PortalAccountController::class, 'requestDocumentChange'])->name('company.account.documents');
        Route::get('/network', [PortalController::class, 'companyNetwork'])->name('company.network');
        Route::get('/tracking', [PortalController::class, 'companyTracking'])->name('company.tracking');
        Route::get('/handoffs', [PortalController::class, 'companyHandoffs'])->name('company.handoffs');
        Route::get('/exceptions', [ParcelExceptionController::class, 'index'])->defaults('portal_role', 'company')->name('company.exceptions');
        Route::post('/handoff-manifests', [CrossCompanyManifestController::class, 'store'])->name('company.handoff-manifests.store');
        Route::get('/cash', [CompanyCashController::class, 'index'])->name('company.cash');
        Route::post('/cash/remittances/{remittance}/receive', [CompanyCashController::class, 'receive'])->whereNumber('remittance')->name('company.cash.receive');
        Route::patch('/cash/remittances/{remittance}/resolve', [CompanyCashController::class, 'resolve'])->whereNumber('remittance')->name('company.cash.resolve');
        Route::post('/cash/remit', [CompanyCashController::class, 'remit'])->name('company.cash.remit');
        Route::get('/rider-pay', [CompanyCashController::class, 'riderPay'])->name('company.rider-pay');
        Route::patch('/rider-pay', [CompanyCashController::class, 'updateRiderPay'])->name('company.rider-pay.update');
        Route::get('/reports', [PortalReportController::class, 'index'])->defaults('portal_role', 'company')->name('company.reports');
        Route::get('/reports/export', [PortalReportController::class, 'export'])->defaults('portal_role', 'company')->name('company.reports.export');
        Route::get('/messages', [PortalMessageController::class, 'index'])->defaults('portal_role', 'company')->name('company.messages');
        Route::post('/messages', [PortalMessageController::class, 'store'])->defaults('portal_role', 'company')->name('company.messages.store');
        Route::post('/messages/{thread}/reply', [PortalMessageController::class, 'reply'])->defaults('portal_role', 'company')->name('company.messages.reply');
        Route::patch('/messages/{thread}/status', [PortalMessageController::class, 'status'])->defaults('portal_role', 'company')->name('company.messages.status');
    });

    Route::middleware(EnsureLogisticsPortalRole::class.':province')->prefix('province')->group(function () {
        Route::get('/', [PortalController::class, 'provinceDashboard'])->name('hub.province');
        Route::get('/stations', [PortalController::class, 'provinceStations'])->name('province.stations');
        Route::post('/stations', [PortalController::class, 'createStation'])->name('province.stations.store');
        Route::patch('/stations/{station}/status', [PortalController::class, 'updateStation'])->whereNumber('station')->name('province.stations.status');
        Route::get('/stations/{station}', [PortalController::class, 'stationShow'])->whereNumber('station')->name('province.stations.show');
        Route::get('/drivers', [PortalController::class, 'provinceDrivers'])->name('province.drivers');
        Route::post('/drivers/{driver}/review', [PortalController::class, 'reviewDriver'])->whereNumber('driver')->name('province.drivers.review');
        Route::patch('/drivers/{driver}/status', [PortalController::class, 'updateDriverStatus'])->whereNumber('driver')->name('province.drivers.status');
        Route::get('/drivers/{driver}/documents/{document}', [PortalController::class, 'driverDocument'])
            ->whereNumber('driver')->whereIn('document', ['license', 'valid-id'])->name('province.drivers.document');
        Route::get('/vehicles', [PortalController::class, 'provinceVehicles'])->name('province.vehicles');
        Route::post('/vehicles', [PortalController::class, 'storeVehicle'])->name('province.vehicles.store');
        Route::put('/vehicles/{vehicle}', [PortalController::class, 'updateVehicle'])->whereNumber('vehicle')->name('province.vehicles.update');
        Route::patch('/vehicles/{vehicle}/status', [PortalController::class, 'updateVehicleStatus'])->whereNumber('vehicle')->name('province.vehicles.status');
        Route::get('/parcels', [PortalController::class, 'provinceParcels'])->name('province.parcels');
        Route::get('/exceptions', [ParcelExceptionController::class, 'index'])->defaults('portal_role', 'province')->name('province.exceptions');
        Route::post('/exceptions', [ParcelExceptionController::class, 'store'])->name('province.exceptions.store');
        Route::patch('/exceptions/{exception}/resolve', [ParcelExceptionController::class, 'resolve'])->whereNumber('exception')->name('province.exceptions.resolve');
        Route::get('/trips', [PortalController::class, 'provinceTrips'])->name('province.trips');
        Route::get('/trips/create', [PortalController::class, 'createTripPage'])->name('province.trips.create');
        Route::post('/trips', [PortalController::class, 'storeTrip'])->name('province.trips.store');
        Route::patch('/trips/{trip}/cancel', [PortalController::class, 'cancelTrip'])->whereNumber('trip')->name('province.trips.cancel');
        Route::patch('/trips/{trip}/depart', [PortalController::class, 'departTrip'])->whereNumber('trip')->name('province.trips.depart');
        Route::patch('/trips/{trip}/arrive', [PortalController::class, 'arriveTrip'])->whereNumber('trip')->name('province.trips.arrive');
        Route::get('/tracking', [PortalController::class, 'facilityTracking'])->name('province.tracking');
        Route::get('/cash', [ProvinceCashController::class, 'index'])->name('province.cash');
        Route::post('/cash/remittances/{remittance}/receive', [ProvinceCashController::class, 'receive'])->whereNumber('remittance')->name('province.cash.receive');
        Route::patch('/cash/remittances/{remittance}/resolve', [ProvinceCashController::class, 'resolve'])->whereNumber('remittance')->name('province.cash.resolve');
        Route::post('/cash/remit', [ProvinceCashController::class, 'remit'])->name('province.cash.remit');
        Route::get('/reports', [PortalReportController::class, 'index'])->defaults('portal_role', 'province')->name('province.reports');
        Route::get('/reports/export', [PortalReportController::class, 'export'])->defaults('portal_role', 'province')->name('province.reports.export');
        Route::get('/messages', [PortalMessageController::class, 'index'])->defaults('portal_role', 'province')->name('province.messages');
        Route::post('/messages', [PortalMessageController::class, 'store'])->defaults('portal_role', 'province')->name('province.messages.store');
        Route::post('/messages/{thread}/reply', [PortalMessageController::class, 'reply'])->defaults('portal_role', 'province')->name('province.messages.reply');
        Route::patch('/messages/{thread}/status', [PortalMessageController::class, 'status'])->defaults('portal_role', 'province')->name('province.messages.status');
        Route::get('/account', [PortalAccountController::class, 'index'])->defaults('portal_role', 'province')->name('province.account');
        Route::patch('/account', [PortalAccountController::class, 'update'])->defaults('portal_role', 'province')->name('province.account.update');
        Route::patch('/account/password', [PortalAccountController::class, 'password'])->defaults('portal_role', 'province')->name('province.account.password');
    });

    Route::middleware(EnsureLogisticsPortalRole::class.':station')->prefix('station')->group(function () {
        Route::get('/', [PortalController::class, 'stationDashboard'])->name('hub.station');
        Route::get('/pickups', [StationOperationsController::class, 'index'])->defaults('mode', 'pickups')->name('station.pickups');
        Route::get('/tasks', [StationOperationsController::class, 'index'])->defaults('mode', 'tasks')->name('station.tasks');
        Route::get('/delivery-queue', [StationOperationsController::class, 'index'])->defaults('mode', 'delivery-queue')->name('station.delivery-queue');
        Route::get('/incoming', [StationOperationsController::class, 'index'])->defaults('mode', 'incoming')->name('station.incoming');
        Route::get('/sorting', [StationOperationsController::class, 'index'])->defaults('mode', 'sorting')->name('station.sorting');
        Route::get('/outgoing', [StationOperationsController::class, 'index'])->defaults('mode', 'outgoing')->name('station.outgoing');
        Route::get('/exceptions', [ParcelExceptionController::class, 'index'])->defaults('portal_role', 'station')->name('station.exceptions');
        Route::post('/exceptions', [ParcelExceptionController::class, 'store'])->name('station.exceptions.store');
        Route::patch('/exceptions/{exception}/resolve', [ParcelExceptionController::class, 'resolve'])->whereNumber('exception')->name('station.exceptions.resolve');
        Route::post('/orders/{order}/assign', [StationOperationsController::class, 'assign'])->whereNumber('order')->name('station.orders.assign');
        Route::post('/orders/{order}/decline-pickup', [StationOperationsController::class, 'declinePickup'])->whereNumber('order')->name('station.orders.decline-pickup');
        Route::get('/tracking', [PortalController::class, 'facilityTracking'])->name('station.tracking');
        Route::get('/riders', [PortalController::class, 'stationRiders'])->name('station.riders');
        Route::post('/riders/{courierDetail}/review', [PortalController::class, 'reviewRider'])->whereNumber('courierDetail')->name('station.riders.review');
        Route::patch('/riders/{courierDetail}/status', [PortalController::class, 'updateRiderStatus'])->whereNumber('courierDetail')->name('station.riders.status');
        Route::get('/cash', [StationCashController::class, 'index'])->name('station.cash');
        Route::post('/cash/riders/{courierDetail}/receive', [StationCashController::class, 'receive'])->whereNumber('courierDetail')->name('station.cash.receive');
        Route::post('/cash/remit', [StationCashController::class, 'remit'])->name('station.cash.remit');
        Route::get('/cash/discrepancies', [StationCashController::class, 'discrepancies'])->name('station.cash.discrepancies');
        Route::patch('/cash/remittances/{remittance}/resolve', [StationCashController::class, 'resolve'])->whereNumber('remittance')->name('station.cash.resolve');
        Route::get('/reports', [PortalReportController::class, 'index'])->defaults('portal_role', 'station')->name('station.reports');
        Route::get('/reports/export', [PortalReportController::class, 'export'])->defaults('portal_role', 'station')->name('station.reports.export');
        Route::get('/messages', [PortalMessageController::class, 'index'])->defaults('portal_role', 'station')->name('station.messages');
        Route::post('/messages', [PortalMessageController::class, 'store'])->defaults('portal_role', 'station')->name('station.messages.store');
        Route::post('/messages/{thread}/reply', [PortalMessageController::class, 'reply'])->defaults('portal_role', 'station')->name('station.messages.reply');
        Route::patch('/messages/{thread}/status', [PortalMessageController::class, 'status'])->defaults('portal_role', 'station')->name('station.messages.status');
        Route::get('/account', [PortalAccountController::class, 'index'])->defaults('portal_role', 'station')->name('station.account');
        Route::patch('/account', [PortalAccountController::class, 'update'])->defaults('portal_role', 'station')->name('station.account.update');
        Route::patch('/account/password', [PortalAccountController::class, 'password'])->defaults('portal_role', 'station')->name('station.account.password');
    });

    Route::middleware(EnsureLogisticsPortalRole::class.':province')->post('/province/scans', [PortalController::class, 'scanParcel'])
        ->name('province.scans.store');
    Route::middleware(EnsureLogisticsPortalRole::class.':station')->post('/station/scans', [PortalController::class, 'scanParcel'])
        ->name('station.scans.store');

    // These legacy dashboard links remain role-protected while their operational pages are replaced.
    Route::middleware(EnsureLogisticsPortalRole::class.':province')->get('/hub/{role}/{page}', [PortalController::class, 'plannedPage'])
        ->whereIn('role', ['province', 'station'])->name('hub.page');
    Route::middleware(EnsureLogisticsPortalRole::class.':province')->get('/hub/linehaul', [PortalController::class, 'plannedPage'])
        ->defaults('portal', 'province')->defaults('page', 'linehaul')->name('hub.linehaul');
});
