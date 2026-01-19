<?php

namespace App\Services\Products;
use App\Models\Product;


class UsedDeviceService
{
    public function create(Product $product, array $data)
    {
        $product->update(['is_used' => true]);

        return $product->usedDeviceDetails()->create($data);
    }

    public function update(Product $product, array $data)
    {
        return $product->usedDeviceDetails()->updateOrCreate(
            ['product_id' => $product->id],
            $data
        );
    }
}
