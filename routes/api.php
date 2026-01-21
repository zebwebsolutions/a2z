<?php 

use App\Http\Controllers\Api\BarcodeScanController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\AuthController;

Route::get('/products/barcode/{barcode}', [BarcodeScanController::class, 'scan']);
Route::middleware(['auth:sanctum', 'active', 'role:admin,salesman'])->post('/orders', [OrderController::class, 'store']);
Route::post('/login', [AuthController::class, 'login']);