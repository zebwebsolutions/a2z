<?php 

use App\Http\Controllers\Api\BarcodeScanController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\RepairController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\StoreController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\BrandController;

Route::match(['get', 'post'], '/products/scan/{identifier?}', [BarcodeScanController::class, 'scan']);
Route::match(['get', 'post'], '/products/barcode/{identifier?}', [BarcodeScanController::class, 'scan']);
Route::middleware(['auth:sanctum', 'active', 'role:admin,salesman'])->post('/orders', [OrderController::class, 'store']);
Route::middleware(['auth:sanctum', 'active', 'role:admin,salesman'])->get('/orders', [OrderController::class, 'index']);
Route::middleware('auth:sanctum', 'active', 'role:admin,salesman')->get('/orders/{order}', [OrderController::class, 'show']);
Route::middleware('auth:sanctum', 'active', 'role:admin,salesman')->post('/orders/{order}/refund', [OrderController::class, 'refund']);
Route::middleware(['auth:sanctum', 'active', 'role:admin,salesman'])
    ->group(function () {
        Route::get('/products', [ProductController::class, 'index']);
        Route::get('/products/{product}', [ProductController::class, 'show']);
        Route::post('/products', [ProductController::class, 'store']);
        Route::patch('/products/{product}', [ProductController::class, 'update']);
    });

Route::middleware('auth:sanctum', 'active', 'role:admin,salesman')->group(function () {
    Route::get('/repairs', [RepairController::class, 'index']);
    Route::post('/repairs', [RepairController::class, 'store']);
    Route::get('/repairs/{repair}', [RepairController::class, 'show']);
    Route::patch('/repairs/{repair}', [RepairController::class, 'update']);
});

Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum', 'active')->get('/me', [ProfileController::class, 'me']);
Route::middleware('auth:sanctum', 'active')->post('/logout', function (Request $request) {
    $request->user()->tokens()->delete();
    return response()->json(['success' => true]);
});

Route::middleware('auth:sanctum', 'active', 'role:salesman,admin')->group(function () {
    Route::get('/stores', [StoreController::class, 'index']);
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/brands', [BrandController::class, 'index']);
});
