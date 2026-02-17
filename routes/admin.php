<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\{
    AdminDashboardController,
    StoreController,
    RepairController,
    CategoryController,
    OrderController,
    BrandController,
    HomeSectionController,
    SliderController,
    SliderImageController,
    SparePartController,
    SparePartSaleController,
    UserController,
    ContactMessageController
};
use App\Http\Controllers\Api\BarcodeScanController;
use App\Http\Controllers\Admin\Products\ProductController;

Route::middleware(['auth', 'active', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        Route::resource('stores', StoreController::class);
        Route::resource('repairs', RepairController::class);
        Route::resource('home-sections', HomeSectionController::class);
        Route::resource('sliders', SliderController::class);
        Route::resource('spare-parts', SparePartController::class);
        Route::resource('users', UserController::class);

        Route::get('contact-messages', [ContactMessageController::class, 'index'])
            ->name('contact-messages.index');

        Route::delete('contact-messages/{contactMessage}', [ContactMessageController::class, 'destroy'])
            ->name('contact-messages.destroy');

        Route::get('spare-parts-sales', [SparePartSaleController::class, 'index'])
            ->name('spare-parts.sales');

        Route::get('spare-parts/{sparePart}/barcode', [SparePartController::class, 'barcode'])
            ->name('spare-parts.barcode');

        Route::patch('users/{user}/toggle', [UserController::class, 'toggle'])
            ->name('users.toggle');

        // Slider images
        Route::prefix('sliders')->group(function () {
            Route::get('{slider}/images', [SliderImageController::class, 'index'])->name('sliders.images.index');
            Route::post('{slider}/images', [SliderImageController::class, 'store'])->name('sliders.images.store');
            Route::delete('images/{image}', [SliderImageController::class, 'destroy'])->name('sliders.images.destroy');
            Route::post('images/sort', [SliderImageController::class, 'sort'])->name('sliders.images.sort');
            Route::get('images/{image}/edit', [SliderImageController::class, 'edit'])->name('sliders.images.edit');
            Route::put('images/{image}', [SliderImageController::class, 'update'])->name('sliders.images.update');
        });

        // Admin barcode scan (optional)
        Route::post('/scan-barcode', [BarcodeScanController::class, 'scan'])
            ->middleware('auth:sanctum');
    });
