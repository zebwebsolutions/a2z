<?php

namespace App\Http\Controllers\Admin\Products;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Store;
use App\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Models\Brand;
use App\Services\Products\UsedDeviceService;
use App\Services\Images\ImageOptimizer;
use App\Http\Requests\Products\StoreUsedDeviceRequest;



class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Product::with(['store', 'category']);

        // Search by name
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Filter by store
        if ($request->filled('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        // Filter by category
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Stock filter
        if ($request->filled('stock')) {
            if ($request->stock === 'in') {
                $query->where('stock', '>', 0);
            } elseif ($request->stock === 'out') {
                $query->where('stock', '<=', 0);
            }
        }

        // Price range
        if ($request->filled('price_min')) {
            $query->where('price', '>=', $request->price_min);
        }

        if ($request->filled('price_max')) {
            $query->where('price', '<=', $request->price_max);
        }

        $products = $query->latest()->paginate(20)->withQueryString();

        return view('admin.products.index', [
            'products' => $products,
            'stores' => Store::orderBy('name')->get(),
            'categories' => Category::orderBy('name')->get(),
        ]);
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $stores = Store::all();
        $categories = Category::whereNull('parent_id')->orderBy('name')->get();
        $subcategories = Category::whereNotNull('parent_id')->orderBy('name')->get();
        $brands = Brand::all();
        return view('admin.products.create', compact('stores', 'categories', 'subcategories', 'brands'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, UsedDeviceService $usedDeviceService, ImageOptimizer $imageOptimizer)
    {
        $data = $request->validate([
            'store_id' => 'required|exists:stores,id',
            'category_id' => 'nullable|exists:categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'sku' => 'nullable|string|max:100|unique:products,sku',
            'image' => 'nullable|image|max:2048',
            'gallery.*' => 'nullable|image|max:2048',
            'barcode' => 'nullable|unique:products,barcode',
            'is_active' => 'nullable|boolean',
            'brand_id' => 'nullable|exists:brands,id',
            'parent_category_id' => 'nullable|exists:categories,id',
            'is_used' => 'nullable|boolean',
            'condition_grade' => 'nullable|in:A+,A,B,C',
            'battery_health' => 'nullable|integer|min:50|max:100',
            'box_available' => 'nullable|boolean',
            'cable_available' => 'nullable|boolean',
            'charger_available' => 'nullable|boolean',
            'headphones_available' => 'nullable|boolean',
            'warranty_days' => 'nullable|integer|min:0',
            'imei' => 'nullable|string|max:255',
        ]);

        $keys = $request->specs_keys ?? [];
        $values = $request->specs_values ?? [];
        $specs = [];

        foreach($keys as $i => $key) {
            if(!empty($key) && isset($values[$i]) && !empty($values[$i])) {
                $formattedKey = strtoupper($key);
                $specs[$formattedKey] = $values[$i];
            }
        }

        $data['specs'] = $specs;
        $data['slug'] = Str::slug($data['name']);
        $data['is_active'] = $request->input('is_active', true);

        if($request->hasFile('image')) {
            $data['image'] = $imageOptimizer->storeOptimized(
                $request->file('image'),
                'products',
                1600,
                82,
                $data['name'] ?? null
            );
        }

        $galleryPaths = [];
        if($request->hasFile('gallery')) {
            foreach($request->file('gallery') as $img) {
                $galleryPaths[] = $imageOptimizer->storeOptimized(
                    $img,
                    'products/gallery',
                    1600,
                    82,
                    $data['name'] ?? null
                );
            }
        }
        if(!empty($galleryPaths)) {
            $data['gallery'] = $galleryPaths;
        }

        if (!$data['category_id']) {
            $data['category_id'] = $data['parent_category_id'];
        }

        $product =Product::create($data);

        // USED DEVICE HANDLING
        if ($request->boolean('is_used')) {
            $usedDeviceService->create($product, $request->only([
                'condition_grade',
                'battery_health',
                'box_available',
                'cable_available',
                'charger_available',
                'headphones_available',
                'warranty_days',
                'imei',
            ]));
        }

        return redirect()->route('admin.products.index')->with('success', 'Product created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $product = Product::findOrFail($id);
        $stores = Store::all();
        $categories = Category::whereNull('parent_id')->orderBy('name')->get();
        $subcategories = Category::whereNotNull('parent_id')->orderBy('name')->get();
        $brands = Brand::all();
        return view('admin.products.edit', compact('product', 'stores', 'categories', 'subcategories', 'brands'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id, UsedDeviceService $usedDeviceService, ImageOptimizer $imageOptimizer)
    {
        $product = Product::findOrFail($id);

        $data = $request->validate([
            'store_id' => 'required|exists:stores,id',
            'category_id' => 'nullable|exists:categories,id',
            'name' => 'required|string|max:255',
            //'slug' => 'required|string|max:255|unique:products,slug,'.$id,
            'sku' => 'nullable|string|max:100|unique:products,sku,'.$id,
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'image' => 'nullable|image|max:2048',
            'gallery.*' => 'nullable|image|max:2048',
            'is_active' => 'nullable|boolean',
            'brand_id' => 'nullable|exists:brands,id',
            'parent_category_id' => 'nullable|exists:categories,id',
            'is_used' => 'nullable|boolean',
            'condition_grade' => 'nullable|in:A+,A,B,C',
            'battery_health' => 'nullable|integer|min:50|max:100',
            'box_available' => 'nullable|boolean',
            'cable_available' => 'nullable|boolean',
            'charger_available' => 'nullable|boolean',
            'headphones_available' => 'nullable|boolean',
            'warranty_days' => 'nullable|integer|min:0',
            'imei' => 'nullable|string|max:255',
        ]);

        $keys = $request->specs_keys ?? [];
        $values = $request->specs_values ?? [];  
        $specs = [];

        foreach($keys as $i => $key) {
            if(!empty($key) && isset($values[$i]) && !empty($values[$i])) {
                $formattedKey = strtoupper($key);
                $specs[$formattedKey] = $values[$i];
            }
        }

        $data['specs'] = $specs;
        $data['slug'] = Str::slug($data['name']);
        $data['is_active'] = $request->input('is_active', true);

        if ($request->hasFile('image')) {
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $data['image'] = $imageOptimizer->storeOptimized(
                $request->file('image'),
                'products',
                1600,
                82,
                $data['name'] ?? $product->name
            );
        }

        $galleryPaths = [];
        if ($request->hasFile('gallery')) {
            foreach ($request->file('gallery') as $img) {
                $galleryPaths[] = $imageOptimizer->storeOptimized(
                    $img,
                    'products/gallery',
                    1600,
                    82,
                    $data['name'] ?? $product->name
                );
            }
        }

        if (!empty($galleryPaths)) {
            $data['gallery'] = $galleryPaths;
        }

        if (!$data['category_id']) {
            $data['category_id'] = $data['parent_category_id'];
        }   
        $product->update($data);

        // USED DEVICE HANDLING
        if ($request->boolean('is_used')) {
            $usedDeviceService->update($product, $request->only([
                'condition_grade',
                'battery_health',
                'box_available',
                'cable_available',
                'charger_available',
                'headphones_available',
                'warranty_days',
                'imei',
            ]));
        } else {
            // If not used, remove any existing used device record
            $usedDeviceService->remove($product);
        }

        return redirect()->back()->with('success', 'Product updated successfully.');
    }

    // delete single gallery image
    public function deleteGalleryImage(Request $request, Product $product, $index)
    {
        // authorize if needed
        // $this->authorize('update', $product);

        $gallery = $product->gallery ?? [];

        // cast index to int and validate
        $idx = (int) $index;
        if (!isset($gallery[$idx])) {
            return response()->json(['message' => 'Image not found'], 404);
        }

        $path = $gallery[$idx];

        // remove from array
        array_splice($gallery, $idx, 1);

        // update DB
        $product->gallery = $gallery;
        $product->save();

        // delete file from storage (if exists)
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }

        return response()->json([
            'message' => 'Image deleted',
            'gallery' => $gallery,
        ]);
    }



    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $product = Product::findOrFail($id);

        if($product->image) {
            Storage::disk('public')->delete($product->image);
        }
        $product->delete();
        return redirect()->route('admin.products.index')->with('success', 'Product deleted successfully.');
    }
}
