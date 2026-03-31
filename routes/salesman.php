<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SparePartScanController;
use App\Http\Controllers\Admin\Products\ProductController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\SparePartController;

Route::middleware(['auth', 'active', 'role:admin,salesman'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        Route::resource('products', ProductController::class);
        Route::resource('categories', CategoryController::class);
        Route::resource('brands', BrandController::class);
        Route::resource('spare-parts', SparePartController::class);
        Route::resource('orders', OrderController::class);

        Route::post('/spare-parts/scan', [SparePartScanController::class, 'scan']);
        Route::post('/spare-parts/sell', [SparePartScanController::class, 'sell']);
    });
