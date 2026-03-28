<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Services\Images\ImageOptimizer;
use App\Services\Products\ProductInventoryService;
use App\Models\Category;

class ProductController extends Controller
{
    private function isPhoneCategory(?int $parentCategoryId): bool
    {
        if (!$parentCategoryId) {
            return false;
        }

        $category = Category::find($parentCategoryId);
        if (!$category) {
            return false;
        }

        $needle = strtolower(($category->slug ?: $category->name) ?? '');

        return str_contains($needle, 'phone')
            || str_contains($needle, 'mobile')
            || str_contains($needle, 'smartphone')
            || str_contains($needle, 'iphone');
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $search = $request->query('search');
        $storeId = $request->query('store_id'); // optional (admin use)

        $query = Product::query()
            ->select(['id', 'name', 'barcode', 'price', 'stock']);

        // 🔐 Store scoping
        if ($user->role !== 'admin') {
            // Staff → force store
            $query->where('store_id', $user->store_id);
        } elseif ($storeId) {
            // Admin filtering by store
            $query->where('store_id', $storeId);
        }

        // 🔍 Search
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        return response()->json(
            $query->latest()->paginate(20)
        );
    }

    public function show(Product $product)
    {
        return response()->json(
            $product->load([
                'usedDeviceDetails',
                'units' => fn ($query) => $query->where('status', 'available')->orderBy('id'),
            ])
        );
    }


    public function store(
        Request $request,
        ImageOptimizer $imageOptimizer,
        ProductInventoryService $productInventoryService
    )
    {
        $user = $request->user();

        $data = $request->validate([
            // Required
            'store_id' => ['required', 'exists:stores,id'],
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'stock' => ['nullable', 'integer', 'min:0'],

            // Optional relations
            'parent_category_id' => ['nullable', 'exists:categories,id'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],

            // Identifiers
            'sku' => ['nullable', 'string', 'max:255'],
            'barcode' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('products', 'barcode'),
            ],
            'inventory_units' => ['nullable', 'array'],
            'inventory_units.*.id' => ['nullable', 'integer'],
            'inventory_units.*.imei_1' => ['nullable', 'string', 'max:50'],
            'inventory_units.*.imei_2' => ['nullable', 'string', 'max:50'],
            'inventory_units.*.serial_number' => ['nullable', 'string', 'max:100'],
            'inventory_units.*.barcode' => ['nullable', 'string', 'max:255'],

            // Content
            'description' => ['nullable', 'string'],

            // Flags
            'is_used' => ['nullable', 'boolean'],

            // Used device extras
            'condition_grade' => ['nullable', Rule::in(['A+', 'A', 'B', 'C'])],
            'used_device_details' => ['nullable', 'array'],
            'used_device_details.device_condition' => ['nullable', Rule::in(['A+', 'A', 'B', 'C'])],
            'warranty_days' => ['nullable', 'integer', 'min:0'],
            'used_device_details.warranty_days' => ['nullable', 'integer', 'min:0'],
            'battery_health' => ['nullable', 'integer', 'min:50', 'max:100'],
            'used_device_details.battery_health' => ['nullable', 'integer', 'min:50', 'max:100'],
            'imei' => ['nullable', 'string', 'max:20', Rule::unique('used_device_details', 'imei')],
            'used_device_details.imei' => ['nullable', 'string', 'max:20', Rule::unique('used_device_details', 'imei')],
            'box_available' => ['nullable', 'boolean'],
            'used_device_details.box_available' => ['nullable', 'boolean'],
            'cable_available' => ['nullable', 'boolean'],
            'used_device_details.cable_available' => ['nullable', 'boolean'],
            'charger_available' => ['nullable', 'boolean'],
            'used_device_details.charger_available' => ['nullable', 'boolean'],
            'headphones_available' => ['nullable', 'boolean'],
            'used_device_details.headphones_available' => ['nullable', 'boolean'],

            // Images
            'image' => ['nullable', 'image', 'max:4096'],
            'gallery.*' => ['nullable', 'image', 'max:4096'],

            // Specs
            'specs_keys' => ['nullable', 'array'],
            'specs_values' => ['nullable', 'array'],
        ]);

        $inventoryUnits = $productInventoryService->normalizeUnits($request->input('inventory_units'));
        $isPhoneCategory = $this->isPhoneCategory($request->integer('parent_category_id'));
        $productInventoryService->validateUnits($inventoryUnits, null, $isPhoneCategory);

        if (!$isPhoneCategory && empty($inventoryUnits) && !array_key_exists('stock', $data)) {
            $request->validate([
                'stock' => ['required', 'integer', 'min:0'],
            ]);
        }

        /* ---------------------------------
         | Handle images
         |---------------------------------*/
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $imageOptimizer->storeOptimized(
                $request->file('image'),
                'products',
                1600,
                82,
                $data['name'] ?? null
            );
        }

        $gallery = [];
        if ($request->hasFile('gallery')) {
            foreach ($request->file('gallery') as $img) {
                $gallery[] = $imageOptimizer->storeOptimized(
                    $img,
                    'products/gallery',
                    1600,
                    82,
                    $data['name'] ?? null
                );
            }
        }

        /* ---------------------------------
         | Build specs array
         |---------------------------------*/
        $specs = [];
        if (
            isset($data['specs_keys'], $data['specs_values'])
            && count($data['specs_keys']) === count($data['specs_values'])
        ) {
            foreach ($data['specs_keys'] as $i => $key) {
                if ($key && $data['specs_values'][$i]) {
                    $specs[strtoupper(trim($key))] = $data['specs_values'][$i];
                }
            }
        }

        $deviceCondition = null;
        if ($request->boolean('is_used')) {
            $deviceCondition = $request->input('used_device_details.device_condition', $request->condition_grade);
            if (!$deviceCondition) {
                return response()->json([
                    'success' => false,
                    'message' => 'Condition grade is required for used device.',
                ], 422);
            }
        }

        $product = DB::transaction(function () use ($data, $request, $imagePath, $gallery, $specs, $deviceCondition, $inventoryUnits) {
            $product = Product::create([
                'store_id' => $data['store_id'],
                'parent_category_id' => $data['parent_category_id'] ?? null,
                'category_id' => $data['category_id'] ?? ($data['parent_category_id'] ?? null),
                'brand_id' => $data['brand_id'] ?? null,
                'name' => $data['name'],
                'slug' => Str::slug($data['name']),
                'description' => $data['description'] ?? null,
                'price' => $data['price'],
                'cost_price' => $data['cost_price'] ?? null,
                'stock' => !empty($inventoryUnits) ? count($inventoryUnits) : ($data['stock'] ?? 0),
                'sku' => $data['sku'] ?? null,
                'barcode' => $data['barcode'] ?? null,
                'tracks_inventory_by_unit' => !empty($inventoryUnits),
                'is_used' => $data['is_used'] ?? false,
                'image' => $imagePath,
                'gallery' => $gallery,
                'specs' => $specs,
            ]);

            if ($request->boolean('is_used')) {
                $product->usedDeviceDetails()->create([
                    'device_condition' => $deviceCondition,
                    'battery_health' => $request->input('used_device_details.battery_health', $request->battery_health),
                    'imei' => $request->input('used_device_details.imei', $request->imei),
                    'warranty_days' => $request->input('used_device_details.warranty_days', $request->warranty_days),
                    'box_available' => $request->has('used_device_details.box_available')
                        ? $request->boolean('used_device_details.box_available')
                        : $request->boolean('box_available'),
                    'cable_available' => $request->has('used_device_details.cable_available')
                        ? $request->boolean('used_device_details.cable_available')
                        : $request->boolean('cable_available'),
                    'charger_available' => $request->has('used_device_details.charger_available')
                        ? $request->boolean('used_device_details.charger_available')
                        : $request->boolean('charger_available'),
                    'headphones_available' => $request->has('used_device_details.headphones_available')
                        ? $request->boolean('used_device_details.headphones_available')
                        : $request->boolean('headphones_available'),
                ]);
            }

            return $product;
        });

        if (!empty($inventoryUnits)) {
            $productInventoryService->syncUnits($product, $inventoryUnits);
        }

        return response()->json([
            'success' => true,
            'product' => $product->fresh(['usedDeviceDetails', 'units']),
        ], 201);
    }

    public function update(
        Request $request,
        Product $product,
        ImageOptimizer $imageOptimizer,
        ProductInventoryService $productInventoryService
    )
    {
        $usedDetailId = optional($product->usedDeviceDetails)->id;

        $data = $request->validate([
            // Required
            'store_id' => ['sometimes', 'exists:stores,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'stock' => ['sometimes', 'integer', 'min:0'],

            // Optional relations
            'parent_category_id' => ['nullable', 'exists:categories,id'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],

            // Identifiers
            'sku' => ['nullable', 'string', 'max:255'],
            'barcode' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('products', 'barcode')->ignore($product->id),
            ],
            'inventory_units' => ['nullable', 'array'],
            'inventory_units.*.id' => ['nullable', 'integer'],
            'inventory_units.*.imei_1' => ['nullable', 'string', 'max:50'],
            'inventory_units.*.imei_2' => ['nullable', 'string', 'max:50'],
            'inventory_units.*.serial_number' => ['nullable', 'string', 'max:100'],
            'inventory_units.*.barcode' => ['nullable', 'string', 'max:255'],

            // Content
            'description' => ['nullable', 'string'],

            // Flags
            'is_used' => ['nullable', 'boolean'],

            // Used device extras
            'condition_grade' => ['nullable', Rule::in(['A+', 'A', 'B', 'C'])],
            'used_device_details' => ['nullable', 'array'],
            'used_device_details.device_condition' => ['nullable', Rule::in(['A+', 'A', 'B', 'C'])],
            'warranty_days' => ['nullable', 'integer', 'min:0'],
            'used_device_details.warranty_days' => ['nullable', 'integer', 'min:0'],
            'battery_health' => ['nullable', 'integer', 'min:50', 'max:100'],
            'used_device_details.battery_health' => ['nullable', 'integer', 'min:50', 'max:100'],
            'imei' => ['nullable', 'string', 'max:20', Rule::unique('used_device_details', 'imei')->ignore($usedDetailId)],
            'used_device_details.imei' => ['nullable', 'string', 'max:20', Rule::unique('used_device_details', 'imei')->ignore($usedDetailId)],
            'box_available' => ['nullable', 'boolean'],
            'used_device_details.box_available' => ['nullable', 'boolean'],
            'cable_available' => ['nullable', 'boolean'],
            'used_device_details.cable_available' => ['nullable', 'boolean'],
            'charger_available' => ['nullable', 'boolean'],
            'used_device_details.charger_available' => ['nullable', 'boolean'],
            'headphones_available' => ['nullable', 'boolean'],
            'used_device_details.headphones_available' => ['nullable', 'boolean'],

            // Images
            'image' => ['nullable', 'image', 'max:4096'],
            'gallery.*' => ['nullable', 'image', 'max:4096'],

            // Specs
            'specs_keys' => ['nullable', 'array'],
            'specs_values' => ['nullable', 'array'],
        ]);

        $inventoryUnits = $productInventoryService->normalizeUnits($request->input('inventory_units'));
        $isPhoneCategory = $this->isPhoneCategory($request->integer('parent_category_id', $product->parent_category_id));
        $productInventoryService->validateUnits($inventoryUnits, $product, $isPhoneCategory);

        if (!$isPhoneCategory && empty($inventoryUnits) && !array_key_exists('stock', $data)) {
            $request->validate([
                'stock' => ['required', 'integer', 'min:0'],
            ]);
        }

        /* ---------------------------------
         | Handle images
         |---------------------------------*/
        if ($request->hasFile('image')) {
            $product->image = $imageOptimizer->storeOptimized(
                $request->file('image'),
                'products',
                1600,
                82,
                $data['name'] ?? $product->name
            );
        }

        if ($request->hasFile('gallery')) {
            $gallery = [];
            foreach ($request->file('gallery') as $img) {
                $gallery[] = $imageOptimizer->storeOptimized(
                    $img,
                    'products/gallery',
                    1600,
                    82,
                    $data['name'] ?? $product->name
                );
            }
            $product->gallery = $gallery;
        }

        /* ---------------------------------
         | Build specs array
         |---------------------------------*/
        if (
            isset($data['specs_keys'], $data['specs_values'])
            && count($data['specs_keys']) === count($data['specs_values'])
        ) {
            $specs = [];
            foreach ($data['specs_keys'] as $i => $key) {
                if ($key && $data['specs_values'][$i]) {
                    $specs[strtoupper(trim($key))] = $data['specs_values'][$i];
                }
            }
            $product->specs = $specs;
        }

        $deviceCondition = null;
        if ($request->has('is_used') && $request->boolean('is_used')) {
            $deviceCondition = $request->input('used_device_details.device_condition', $request->condition_grade);
            if (!$deviceCondition) {
                return response()->json([
                    'success' => false,
                    'message' => 'Condition grade is required for used device.',
                ], 422);
            }
        }

        $product->fill([
            'store_id' => $data['store_id'] ?? $product->store_id,
            'parent_category_id' => array_key_exists('parent_category_id', $data)
                ? $data['parent_category_id']
                : $product->parent_category_id,
            'category_id' => array_key_exists('category_id', $data)
                ? ($data['category_id'] ?? $data['parent_category_id'] ?? null)
                : $product->category_id,
            'brand_id' => array_key_exists('brand_id', $data)
                ? $data['brand_id']
                : $product->brand_id,

            'name' => $data['name'] ?? $product->name,
            'slug' => isset($data['name'])
                ? Str::slug($data['name'])
                : $product->slug,
            'description' => array_key_exists('description', $data)
                ? $data['description']
                : $product->description,

            'price' => $data['price'] ?? $product->price,
            'cost_price' => array_key_exists('cost_price', $data)
                ? $data['cost_price']
                : $product->cost_price,
            'stock' => !empty($inventoryUnits)
                ? count($inventoryUnits)
                : ($data['stock'] ?? $product->stock),

            'sku' => array_key_exists('sku', $data) ? $data['sku'] : $product->sku,
            'barcode' => array_key_exists('barcode', $data) ? $data['barcode'] : $product->barcode,

            'is_used' => array_key_exists('is_used', $data) ? $data['is_used'] : $product->is_used,
            'tracks_inventory_by_unit' => !empty($inventoryUnits) || $product->tracks_inventory_by_unit,
        ]);

        DB::transaction(function () use ($product, $request, $deviceCondition, $productInventoryService, $inventoryUnits) {
            $product->save();

            if ($request->has('inventory_units')) {
                $productInventoryService->syncUnits($product, $inventoryUnits);
            }

            if ($request->has('is_used')) {
                if ($request->boolean('is_used')) {
                    $product->usedDeviceDetails()->updateOrCreate([
                        'product_id' => $product->id,
                    ], [
                        'device_condition' => $deviceCondition,
                        'battery_health' => $request->input('used_device_details.battery_health', $request->battery_health),
                        'imei' => $request->input('used_device_details.imei', $request->imei),
                        'warranty_days' => $request->input('used_device_details.warranty_days', $request->warranty_days),
                        'box_available' => $request->has('used_device_details.box_available')
                            ? $request->boolean('used_device_details.box_available')
                            : $request->boolean('box_available'),
                        'cable_available' => $request->has('used_device_details.cable_available')
                            ? $request->boolean('used_device_details.cable_available')
                            : $request->boolean('cable_available'),
                        'charger_available' => $request->has('used_device_details.charger_available')
                            ? $request->boolean('used_device_details.charger_available')
                            : $request->boolean('charger_available'),
                        'headphones_available' => $request->has('used_device_details.headphones_available')
                            ? $request->boolean('used_device_details.headphones_available')
                            : $request->boolean('headphones_available'),
                    ]);
                } else {
                    $product->usedDeviceDetails()->delete();
                }
            }
        });

        return response()->json([
            'success' => true,
            'product' => $product->fresh(['usedDeviceDetails', 'units']),
        ]);
    }
}
