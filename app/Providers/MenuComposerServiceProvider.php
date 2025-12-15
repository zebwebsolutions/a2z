<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Services\MenuService;

class MenuComposerServiceProvider extends ServiceProvider
{
    public function boot(MenuService $menuService)
    {
        View::composer('*', function ($view) use ($menuService) {
            $view->with('menuCategories', $menuService->getMenu());
        });
    }
}
