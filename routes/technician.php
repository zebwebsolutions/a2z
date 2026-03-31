<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\RepairController;
use App\Http\Controllers\Admin\AdminDashboardController;

Route::middleware(['auth', 'active', 'role:admin,technician'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::resource('repairs', RepairController::class);

        Route::post('/spare-parts/scan', [SparePartScanController::class, 'scan']);
        Route::post('/spare-parts/sell', [SparePartScanController::class, 'sell']);
    });
