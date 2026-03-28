<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use Illuminate\Support\Str;

class GenerateProductBarcodes extends Command
{
    protected $signature = 'products:generate-barcodes {--dry-run}';
    protected $description = 'Generate barcodes for products without barcode';

    public function handle()
    {
        $products = Product::whereNull('barcode')->get();

        if ($products->isEmpty()) {
            $this->info('All products already have barcodes.');
            return;
        }

        foreach ($products as $product) {
            do {
                $barcode = 'PRD-' . strtoupper(Str::random(8));
            } while (Product::where('barcode', $barcode)->exists());

            if (!$this->option('dry-run')) {
                $product->update([
                    'barcode' => $barcode,
                    'barcode_type' => 'code128',
                ]);
            }

            $this->line("✔ {$product->name} → {$barcode}");
        }

        $this->info('Barcode generation completed.');
    }
}