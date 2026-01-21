<?php 

use App\Http\Controllers\Api\BarcodeScanController;
use App\Http\Controllers\Api\OrderController;

Route::get('/products/barcode/{barcode}', [BarcodeScanController::class, 'scan']);
Route::post('/orders', [OrderController::class, 'store']);