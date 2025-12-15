<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Brand;
use App\Models\Product;

class MenuService
{
    public function getMenu()
    {
        // Fetch only top-level categories visible in the menu
        $categories = Category::whereNull('parent_id')
                              ->where('show_in_menu', 1)
                              ->orderBy('name', 'asc')
                              ->get();

        foreach ($categories as $cat) {

            /*
            |--------------------------------------------------------------------------
            | CASE 1: PHONE / TABLET / LAPTOP / SMART WATCH CATEGORIES
            | These should show BRANDS automatically based on product existence
            |--------------------------------------------------------------------------
            */

            if ($this->isBrandCategory($cat->slug)) {

                $brandIDs = Product::where('parent_category_id', $cat->id)
                                    ->whereNotNull('brand_id')
                                    ->pluck('brand_id')
                                    ->unique();

                // Fetch brands and tag them as menu items
                $cat->menu_items = Brand::whereIn('id', $brandIDs)
                                        ->orderBy('name')
                                        ->get()
                                        ->map(function ($brand) {
                                            $brand->is_brand = true;
                                            return $brand;
                                        });

                continue;
            }


            /*
            |--------------------------------------------------------------------------
            | CASE 2: ACCESSORIES / WEARABLES
            | These should show SUBCATEGORIES in the dropdown
            |--------------------------------------------------------------------------
            */

            $cat->menu_items = Category::where('parent_id', $cat->id)
                                       ->where('is_active', 1)
                                       ->orderBy('name', 'asc')
                                       ->get()
                                       ->map(function ($subcat) {
                                           $subcat->is_brand = false;
                                           return $subcat;
                                       });
        }

        return $categories;
    }



    /*
    |--------------------------------------------------------------------------
    | Helper: Categories That Show Brands Instead of Subcategories
    |--------------------------------------------------------------------------
    */
    private function isBrandCategory($slug)
    {
        return in_array($slug, [
            'phones',
            'tablets',
            'laptops',
            'smart-watches',
        ]);
    }
}
