<?php 

use App\Http\Controllers\Api\BarcodeScanController;

Route::get('/products/barcode/{barcode}', [BarcodeScanController::class, 'scan']);
