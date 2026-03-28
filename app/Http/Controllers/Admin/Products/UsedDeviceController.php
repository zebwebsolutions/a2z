<?php

namespace App\Http\Controllers\Admin\Products;

use App\Http\Controllers\Controller;
use App\Http\Requests\Products\StoreUsedDeviceRequest;
use App\Models\Product;
use App\Services\Products\UsedDeviceService;

class UsedDeviceController extends Controller
{
    public function store(
        StoreUsedDeviceRequest $request,
        Product $product,
        UsedDeviceService $service
    ) {
        $service->create($product, $request->validated());

        return response()->json([
            'message' => 'Used device details added successfully'
        ]);
    }

    public function update(
        StoreUsedDeviceRequest $request,
        Product $product,
        UsedDeviceService $service
    ) {
        $service->update($product, $request->validated());

        return response()->json([
            'message' => 'Used device details updated successfully'
        ]);
    }

    public function destroy(
        Product $product,
        UsedDeviceService $service
    ) {
        $service->remove($product);

        return response()->json([
            'message' => 'Used device removed successfully'
        ]);
    }
}