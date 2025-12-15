<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Brand;
use Illuminate\Support\Str;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        $brands = [
            // Mobile Brands
            'Apple',
            'Samsung',
            'Xiaomi',
            'Oppo',
            'Vivo',
            'Huawei',
            'Realme',
            'Infinix',
            'Tecno',

            // Laptop Brands
            'Dell',
            'HP',
            'Lenovo',
            'Asus',
            'Acer',
            'Microsoft Surface',

            // Accessories / Audio Brands
            'Anker',
            'Baseus',
            'Ugreen',
            'JBL',
            'Hoco',
            'Joyroom',
            'Remax',
            'Belkin',
            'Aukey',

            // Wearable Brands
            'Amazfit',
            'Fitbit',
            'Garmin',
            'Huawei Watch',
        ];

        foreach ($brands as $brandName) {
            Brand::firstOrCreate(
                ['slug' => Str::slug($brandName)],
                [
                    'name' => $brandName,
                    'slug' => Str::slug($brandName),
                    'logo' => null, // You can update logos later
                ]
            );
        }
    }
}