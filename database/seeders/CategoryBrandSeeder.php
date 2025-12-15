<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Brand;
use Illuminate\Support\Str;

class CategoryBrandSeeder extends Seeder
{
    public function run()
    {
        $categories = [
            'Phones' => ['Apple', 'Samsung', 'Xiaomi', 'Oppo', 'Vivo'],
            'Laptops' => ['Dell', 'HP', 'Lenovo', 'Asus', 'Apple'],
            'Tablets' => ['Apple', 'Samsung', 'Lenovo'],
            'Accessories' => ['Anker', 'Baseus', 'JBL', 'Hoco'],
            'Wearables' => ['Apple', 'Samsung', 'Mi', 'Huawei'],
        ];

        foreach ($categories as $cat => $brands) {
            $category = Category::firstOrCreate([
                'name' => $cat,
                'slug' => Str::slug($cat),
                'show_in_menu' => true,
            ]);

            foreach ($brands as $brandName) {
                $brand = Brand::firstOrCreate([
                    'name' => $brandName,
                    'slug' => Str::slug($brandName),
                ]);

                $category->brands()->syncWithoutDetaching([$brand->id]);
            }
        }
    }
}