<?php 

use App\Http\Controllers\Api\BarcodeScanController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;

Route::get('/products/barcode/{barcode}', [BarcodeScanController::class, 'scan']);
Route::middleware(['auth:sanctum', 'active', 'role:admin,salesman'])->post('/orders', [OrderController::class, 'store']);
Route::middleware(['auth:sanctum', 'active', 'role:admin,salesman'])->get('/orders', [OrderController::class, 'index']);
Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum', 'active', 'role:admin,salesman')->get('/orders/{order}', [OrderController::class, 'show']);
Route::middleware('auth:sanctum', 'active', 'role:admin,salesman')->post('/orders/{order}/refund', [OrderController::class, 'refund']);
Route::middleware(['auth:sanctum', 'active', 'role:admin,salesman'])->get('/products', [ProductController::class, 'index']);

Route::middleware('auth:sanctum', 'active', 'role:admin,salesman')->group(function () {
    Route::get('/repairs', [Api\RepairController::class, 'index']);
    Route::post('/repairs', [Api\RepairController::class, 'store']);
    Route::get('/repairs/{repair}', [Api\RepairController::class, 'show']);
    Route::patch('/repairs/{repair}', [Api\RepairController::class, 'update']);
});
