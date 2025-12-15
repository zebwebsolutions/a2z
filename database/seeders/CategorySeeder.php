<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        // -----------------------------------------
        // 1. TOP-LEVEL MENU CATEGORIES
        // -----------------------------------------
        $topLevel = [
            ['name' => 'Phones', 'slug' => 'phones'],
            ['name' => 'Tablets', 'slug' => 'tablets'],
            ['name' => 'Laptops', 'slug' => 'laptops'],
            ['name' => 'Smart Watches', 'slug' => 'smart-watches'],
            ['name' => 'Wearables', 'slug' => 'wearables'],
            ['name' => 'Accessories', 'slug' => 'accessories'],
        ];

        $parents = [];

        foreach ($topLevel as $cat) {
            $parents[$cat['slug']] = Category::firstOrCreate(
                ['slug' => $cat['slug']],
                [
                    'name' => $cat['name'],
                    'slug' => $cat['slug'],
                    'is_active' => 1,
                    'show_in_menu' => 1,
                    'parent_id' => null,
                ]
            );
        }

        // -----------------------------------------
        // 2. ACCESSORIES SUBCATEGORIES
        // -----------------------------------------
        $accessoriesSub = [
            'Cases & Covers'        => 'cases',
            'Screen Protectors'     => 'screen-protectors',
            'Chargers'              => 'chargers',
            'Cables'                => 'cables',
            'Power Banks'           => 'power-banks',
            'Car Holders'           => 'car-holders',
            'Earbuds'               => 'earbuds',
            'Headphones'            => 'headphones',
            'Speakers'              => 'speakers',
            'Adapters'              => 'adapters',
            'Memory Cards'          => 'memory-cards',
            'Smart Gadgets'         => 'smart-gadgets',
            'Cleaning Kits'         => 'cleaning-kits',
            'Stands & Holders'      => 'stands',
            'Popsockets & Grips'    => 'grips',
        ];

        foreach ($accessoriesSub as $name => $slug) {
            Category::firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'slug' => $slug,
                    'is_active' => 1,
                    'show_in_menu' => 0,
                    'parent_id' => $parents['accessories']->id,
                ]
            );
        }

        // -----------------------------------------
        // 3. WEARABLES SUBCATEGORIES
        // -----------------------------------------
        $wearablesSub = [
            'Watch Straps'          => 'watch-straps',
            'Watch Chargers'        => 'watch-chargers',
            'Fitness Bands'         => 'fitness-bands',
            'Smart Rings'           => 'smart-rings',
            'Smart Trackers'        => 'smart-trackers',
        ];

        foreach ($wearablesSub as $name => $slug) {
            Category::firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'slug' => $slug,
                    'is_active' => 1,
                    'show_in_menu' => 0,
                    'parent_id' => $parents['wearables']->id,
                ]
            );
        }

        // -----------------------------------------
        // 4. OPTIONAL: Phones subcategories
        //    (e.g., Apple, Samsung pages — NOT brands,
        //     just landing pages if you want them)
        // -----------------------------------------
        $phoneSub = [
            '5G Phones'             => '5g-phones',
            'Budget Phones'         => 'budget-phones',
            'Mid-Range Phones'      => 'mid-range-phones',
            'Flagship Phones'       => 'flagship-phones',
            'Gaming Phones'         => 'gaming-phones',
            'Best Battery Phones'   => 'best-battery-phones',
            'Fast Charging Phones'  => 'fast-charging-phones',
            'High Refresh Rate'     => 'high-refresh-rate-phones',
            'AMOLED Display Phones' => 'amoled-display-phones',
        ];


        foreach ($phoneSub as $name => $slug) {
            Category::firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'slug' => $slug,
                    'is_active' => 1,
                    'show_in_menu' => 0,
                    'parent_id' => $parents['phones']->id,
                ]
            );
        }

        // -----------------------------------------
        // DONE
        // -----------------------------------------
    }
}