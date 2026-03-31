<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;

class GenerateProductSkus extends Command
{
    protected $signature = 'products:generate-skus';
    protected $description = 'Generate SKUs for products without SKU';

    public function handle()
    {
        $products = Product::whereNull('sku')->orderBy('id')->get();

        foreach ($products as $product) {
            $product->sku = 'PRD-' . str_pad(
                (string) $product->id,
                6,
                '0',
                STR_PAD_LEFT
            );
            $product->save();

            $this->info("SKU set for {$product->name}");
        }

        $this->info('SKU generation completed.');
    }
}
