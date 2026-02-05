<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
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
        return response()->json($product);
    }


    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            // Required
            'store_id' => ['required', 'exists:stores,id'],
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],

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

            // Content
            'description' => ['nullable', 'string'],

            // Flags
            'is_used' => ['nullable', 'boolean'],

            // Used device extras
            'condition_grade' => ['nullable', 'string', 'max:50'],
            'warranty_days' => ['nullable', 'integer', 'min:0'],
            'battery_health' => ['nullable', 'integer', 'min:50', 'max:100'],
            'imei' => ['nullable', 'string', 'max:255'],
            'box_available' => ['nullable', 'boolean'],
            'charger_available' => ['nullable', 'boolean'],
            'headphones_available' => ['nullable', 'boolean'],

            // Images
            'image' => ['nullable', 'image', 'max:4096'],
            'gallery.*' => ['nullable', 'image', 'max:4096'],

            // Specs
            'specs_keys' => ['nullable', 'array'],
            'specs_values' => ['nullable', 'array'],
        ]);

        /* ---------------------------------
         | Handle images
         |---------------------------------*/
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')
                ->store('products', 'public');
        }

        $gallery = [];
        if ($request->hasFile('gallery')) {
            foreach ($request->file('gallery') as $img) {
                $gallery[] = $img->store('products/gallery', 'public');
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

        /* ---------------------------------
         | Create product
         |---------------------------------*/
        $product = Product::create([
            'store_id' => $data['store_id'],
            'parent_category_id' => $data['parent_category_id'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'brand_id' => $data['brand_id'] ?? null,

            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
            'description' => $data['description'] ?? null,

            'price' => $data['price'],
            'stock' => $data['stock'],

            'sku' => $data['sku'] ?? null,
            'barcode' => $data['barcode'] ?? null,

            'is_used' => $data['is_used'] ?? false,


            'image' => $imagePath,
            'gallery' => $gallery,
            'specs' => $specs,
        ]);

        if ($request->boolean('is_used')) {
            $product->usedDeviceDetails()->create([
                'device_condition' => $request->condition_grade,
                'battery_health' => $request->battery_health,
                'imei' => $request->imei,
                'warranty_days' => $request->warranty_days,
                'box_available' => $request->boolean('box_available'),
                'charger_available' => $request->boolean('charger_available'),
                'headphones_available' => $request->boolean('headphones_available'),
            ]);
        }

        return response()->json([
            'success' => true,
            'product' => $product,
        ], 201);
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            // Required
            'store_id' => ['sometimes', 'exists:stores,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'price' => ['sometimes', 'numeric', 'min:0'],
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

            // Content
            'description' => ['nullable', 'string'],

            // Flags
            'is_used' => ['nullable', 'boolean'],

            // Used device extras
            'condition_grade' => ['nullable', 'string', 'max:50'],
            'warranty_days' => ['nullable', 'integer', 'min:0'],
            'battery_health' => ['nullable', 'integer', 'min:50', 'max:100'],
            'imei' => ['nullable', 'string', 'max:255'],
            'box_available' => ['nullable', 'boolean'],
            'charger_available' => ['nullable', 'boolean'],
            'headphones_available' => ['nullable', 'boolean'],

            // Images
            'image' => ['nullable', 'image', 'max:4096'],
            'gallery.*' => ['nullable', 'image', 'max:4096'],

            // Specs
            'specs_keys' => ['nullable', 'array'],
            'specs_values' => ['nullable', 'array'],
        ]);

        /* ---------------------------------
         | Handle images
         |---------------------------------*/
        if ($request->hasFile('image')) {
            $product->image = $request->file('image')
                ->store('products', 'public');
        }

        if ($request->hasFile('gallery')) {
            $gallery = [];
            foreach ($request->file('gallery') as $img) {
                $gallery[] = $img->store('products/gallery', 'public');
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

        /* ---------------------------------
         | Update product
         |---------------------------------*/
        $product->fill([
            'store_id' => $data['store_id'] ?? $product->store_id,
            'parent_category_id' => array_key_exists('parent_category_id', $data)
                ? $data['parent_category_id']
                : $product->parent_category_id,
            'category_id' => array_key_exists('category_id', $data)
                ? $data['category_id']
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
            'stock' => $data['stock'] ?? $product->stock,

            'sku' => array_key_exists('sku', $data) ? $data['sku'] : $product->sku,
            'barcode' => array_key_exists('barcode', $data) ? $data['barcode'] : $product->barcode,

            'is_used' => array_key_exists('is_used', $data) ? $data['is_used'] : $product->is_used,
        ]);

        $product->save();

        if ($request->has('is_used')) {
            if ($request->boolean('is_used')) {
                $product->usedDeviceDetails()->updateOrCreate([
                    'product_id' => $product->id,
                ], [
                    'device_condition' => $request->condition_grade,
                    'battery_health' => $request->battery_health,
                    'imei' => $request->imei,
                    'warranty_days' => $request->warranty_days,
                    'box_available' => $request->boolean('box_available'),
                    'charger_available' => $request->boolean('charger_available'),
                    'headphones_available' => $request->boolean('headphones_available'),
                ]);
            } else {
                $product->usedDeviceDetails()->delete();
            }
        }

        return response()->json([
            'success' => true,
            'product' => $product->fresh(),
        ]);
    }
}