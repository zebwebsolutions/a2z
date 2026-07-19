<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Category;
use Illuminate\Support\Facades\View;
use App\Services\MenuService;
use App\Models\Brand;
use Illuminate\Support\Facades\Cache;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(MenuService $menuService): void
    {
        View::composer('*', function ($view) use ($menuService) {
            $view->with('menuCategories', $menuService->getMenu());

            $footerCategories = Cache::remember('footer_categories', now()->addHour(), function () {
                return Category::where('is_active', true)
                    ->withCount('products')
                    ->orderByDesc('products_count')
                    ->take(6)
                    ->get();
            });

            $footerBrands = Cache::remember('footer_brands', now()->addHour(), function () {
                return Brand::where('is_active', true)
                    ->withCount('products')
                    ->orderByDesc('products_count')
                    ->take(6)
                    ->get();
            });

            $view->with('footerCategories', $footerCategories);
            $view->with('footerBrands', $footerBrands);

            // if controller already supplied $breadcrumbItems, this won't override it
            $viewData = $view->getData();

            if (!array_key_exists('breadcrumbItems', $viewData)) {
                // If category exists in view data, build breadcrumbs accordingly
                $breadcrumbItems = [
                    ['label'=>'Home', 'url'=>route('home')],
                ];

                if (isset($viewData['category']) && $viewData['category'] instanceof Category) {
                    $cat = $viewData['category'];

                    if ($cat->parent) {
                        $breadcrumbItems[] = [
                            'label' => $cat->parent->name,
                            'url' => route('category.show', $cat->parent->slug),
                        ];
                    }

                    $breadcrumbItems[] = ['label' => $cat->name, 'url' => null];
                }

                $view->with('breadcrumbItems', $breadcrumbItems);
            }

        });
    }
}
