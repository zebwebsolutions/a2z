<?php

namespace App\Services\Products;
use App\Models\Product;


class UsedDeviceService
{
    protected function normalize(array $data): array
    {
        if (array_key_exists('condition_grade', $data) && !array_key_exists('device_condition', $data)) {
            $data['device_condition'] = $data['condition_grade'];
            unset($data['condition_grade']);
        }

        return $data;
    }

    public function create(Product $product, array $data)
    {
        $product->update(['is_used' => true]);

        return $product->usedDeviceDetails()->create($this->normalize($data));
    }

    public function update(Product $product, array $data)
    {
        return $product->usedDeviceDetails()->updateOrCreate(
            ['product_id' => $product->id],
            $this->normalize($data)
        );
    }

    public function remove(Product $product)
    {
        $product->update(['is_used' => false]);

        return $product->usedDeviceDetails()->delete();
    }
}
