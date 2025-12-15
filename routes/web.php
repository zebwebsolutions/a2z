<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\StoreController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\RepairController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\SliderController;
use App\Http\Controllers\Admin\SliderImageController;
use App\Http\Controllers\Front\ProductController as FrontProductController;
use App\Http\Controllers\Front\CartController;
use App\Http\Controllers\Front\ShopController;
use App\Http\Controllers\Front\HomeController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Front\CategoryController as FrontCategoryController;
use App\Http\Controllers\Admin\HomeSectionController;
use App\Http\Controllers\Front\RepairController as FrontRepairController;
use App\Http\Controllers\Admin\ProductSearchController;

//
// FRONT ROUTES
//
Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/shop', [ShopController::class, 'index'])->name('shop.index');
Route::get('shop/ajax', [ShopController::class, 'ajaxProducts'])->name('shop.ajax');
Route::get('/products', [FrontProductController::class, 'index'])->name('products.index');
Route::get('/product/{product:slug}', [FrontProductController::class, 'show'])->name('product.show');
Route::get('/category/{category:slug}', [FrontCategoryController::class, 'show'])->name('category.show');
Route::get('/category/{category:slug}/{brand:slug}', 
    [FrontCategoryController::class, 'brandFilter'])->name('brand.category');
Route::get('/brand/{brand:slug}', 
    [BrandController::class, 'show'])->name('brand.show');
Route::get('/category/{category:slug}/ajax', [FrontCategoryController::class, 'ajax'])
     ->name('category.ajax');
Route::get('/search', [ShopController::class, 'search'])->name('search');
Route::get('/search/suggest', [ShopController::class, 'suggest'])
     ->name('search.suggest');
Route::get('/admin/products/search', [ProductSearchController::class, 'search'])
    ->name('admin.products.search');

//Frontend Repair Routes

Route::get('/repair', [FrontRepairController::class, 'form'])->name('repair.form');
Route::post('/repair/book', [FrontRepairController::class, 'book'])->name('repair.book');

//Contact Route
Route::get('/contact', function() {
    return view('front.contact');
})->name('contact');

Route::view('/warranty-policy', 'front.warranty', [
    'policy' => file_get_contents(resource_path('text/warranty.txt'))
])->name('warranty.policy');

Route::view('/returns-refunds', 'front.returns', [
    'policy' => file_get_contents(resource_path('text/returns.txt'))
])->name('returns.policy');



// CART ROUTES
//
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::get('/cart/add/{id}', [CartController::class, 'add'])->name('cart.add');
Route::get('/cart/remove/{id}', [CartController::class, 'remove'])->name('cart.remove');
Route::get('/cart/checkout', [CartController::class, 'checkout'])->name('cart.checkout');
Route::post('/cart/place-order', [CartController::class, 'placeOrder'])->name('cart.placeOrder');
Route::get('/cart/success/{id}', [CartController::class, 'success'])->name('cart.success');

//
// AUTH PROFILE ROUTES
//
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

//
// SALESMAN ROUTES
//
Route::middleware(['auth', 'role:salesman'])
        ->prefix('salesman')
        ->name('salesman.')
        ->group(function () {
            Route::get('/dashboard', function () {
                return view('salesman.dashboard');
            })->name('dashboard');
        });

//
// ADMIN ROUTES (all admin logic must go here)
//
Route::middleware(['auth', 'role:admin'])
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {

            Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

            Route::resource('stores', StoreController::class);
            Route::resource('products', ProductController::class);
            Route::resource('repairs', RepairController::class);
            Route::resource('categories', CategoryController::class);
            Route::resource('orders', OrderController::class);
            Route::resource('brands', BrandController::class);
            Route::resource('home-sections', HomeSectionController::class);

            // Delete single gallery image
            Route::delete('products/{product}/gallery/{index}', [ProductController::class, 'deleteGalleryImage'])
                ->name('products.deleteGalleryImage');


            // SLIDERS
            Route::resource('sliders', SliderController::class);

            // SLIDER IMAGES
            Route::prefix('sliders')->group(function () {
                Route::get('{slider}/images', [SliderImageController::class, 'index'])
                    ->name('sliders.images.index');

                Route::post('{slider}/images', [SliderImageController::class, 'store'])
                    ->name('sliders.images.store');

                Route::delete('images/{image}', [SliderImageController::class, 'destroy'])
                    ->name('sliders.images.destroy');

                Route::post('images/sort', [SliderImageController::class, 'sort'])
                    ->name('sliders.images.sort');

                Route::get('images/{image}/edit', [SliderImageController::class, 'edit'])
                    ->name('sliders.images.edit');

                Route::put('images/{image}', [SliderImageController::class, 'update'])
                    ->name('sliders.images.update');
            });
        });

require __DIR__.'/auth.php';
