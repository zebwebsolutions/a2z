<?php

use Illuminate\Support\Facades\Route;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Http\Controllers\Front\HomeController;
use App\Http\Controllers\Front\ShopController;
use App\Http\Controllers\Front\ProductController;
use App\Http\Controllers\Front\CategoryController;
use App\Http\Controllers\Front\BrandController;
use App\Http\Controllers\Front\CartController;
use App\Http\Controllers\Front\RepairController;
use App\Http\Controllers\Front\ContactController;
use App\Http\Controllers\Admin\AdminDashboardController;

// Home
Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/sitemap.xml', function () {
    $staticUrls = [
        route('home'),
        route('shop.index'),
        route('products.index'),
        route('shop.used'),
        route('about'),
        route('contact'),
        route('repair.form'),
        route('warranty.policy'),
        route('returns.policy'),
        route('privacy.policy'),
    ];

    $dynamicUrls = collect()
        ->merge(Product::select('slug', 'updated_at')->where('is_active', 1)->get()->map(function ($product) {
            return [
                'loc' => route('product.show', $product->slug),
                'lastmod' => optional($product->updated_at)->toAtomString(),
            ];
        }))
        ->merge(Category::select('slug', 'updated_at')->where('is_active', 1)->get()->map(function ($category) {
            return [
                'loc' => route('category.show', $category->slug),
                'lastmod' => optional($category->updated_at)->toAtomString(),
            ];
        }))
        ->merge(Brand::select('slug', 'updated_at')->where('is_active', 1)->get()->map(function ($brand) {
            return [
                'loc' => route('brand.index', $brand->slug),
                'lastmod' => optional($brand->updated_at)->toAtomString(),
            ];
        }));

    $urls = collect($staticUrls)->map(fn ($url) => ['loc' => $url, 'lastmod' => now()->toAtomString()])
        ->merge($dynamicUrls);

    $xml = view('sitemap', ['urls' => $urls]);

    return response($xml, 200)->header('Content-Type', 'application/xml');
})->name('sitemap');

// Shop & Products
Route::get('/shop', [ShopController::class, 'index'])->name('shop.index');
Route::get('/shop/ajax', [ShopController::class, 'ajaxProducts'])->name('shop.ajax');
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/product/{product:slug}', [ProductController::class, 'show'])->name('product.show');
Route::get('/used-devices', [ShopController::class, 'used'])->name('shop.used');

// Categories & Brands
Route::get('/category/{category:slug}', [CategoryController::class, 'show'])->name('category.show');
Route::get('/category/{category:slug}/{brand:slug}', [CategoryController::class, 'brandFilter'])->name('brand.category');
Route::get('/brand/{brand:slug}', [BrandController::class, 'show'])->name('brand.index');

// Search
Route::get('/search', [ShopController::class, 'search'])->name('search');
Route::get('/search/suggest', [ShopController::class, 'suggest'])->name('search.suggest');

// Repairs
Route::get('/repair', [RepairController::class, 'form'])->name('repair.form');
Route::post('/repair/book', [RepairController::class, 'book'])->name('repair.book');

// Cart
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::get('/cart/add/{id}', [CartController::class, 'add'])->name('cart.add');
Route::get('/cart/remove/{id}', [CartController::class, 'remove'])->name('cart.remove');
Route::get('/cart/checkout', [CartController::class, 'checkout'])->name('cart.checkout');
Route::post('/cart/place-order', [CartController::class, 'placeOrder'])->name('cart.placeOrder');
Route::get('/cart/success/{id}', [CartController::class, 'success'])->name('cart.success');

// Static pages
Route::view('/about-us', 'front.about')->name('about');
Route::get('/contact', [ContactController::class, 'index'])->name('contact');
Route::post('/contact', [ContactController::class, 'send'])->name('contact.send');
Route::view('/warranty-policy', 'front.warranty', [
    'policy' => file_get_contents(resource_path('text/warranty.txt'))
])->name('warranty.policy');

Route::view('/returns-refunds', 'front.returns', [
    'policy' => file_get_contents(resource_path('text/returns.txt'))
])->name('returns.policy');

Route::view('/privacy-policy', 'front.privacy')->name('privacy.policy');

Route::middleware(['auth', 'active', 'role:admin,salesman,technician'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        Route::get('/dashboard', [AdminDashboardController::class, 'index'])
            ->name('dashboard');
    });

require __DIR__.'/../routes/admin.php';
require __DIR__.'/../routes/salesman.php';
require __DIR__.'/../routes/auth.php';
require __DIR__.'/../routes/technician.php';
