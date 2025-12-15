<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Store;
use App\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Models\Brand;


class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $products = Product::with('store', 'category')->latest()->paginate(10);
        return view('admin.products.index', compact('products'));
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
    public function store(Request $request)
    {
        $data = $request->validate([
            'store_id' => 'required|exists:stores,id',
            'category_id' => 'nullable|exists:categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'sku' => 'nullable|string|max:100|unique:products,sku',
            'image' => 'nullable|image|max:2048',
            'gallery.*' => 'nullable|image|max:2048',
            'is_active' => 'nullable|boolean',
            'brand_id' => 'nullable|exists:brands,id',
            'parent_category_id' => 'nullable|exists:categories,id',
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
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        $galleryPaths = [];
        if($request->hasFile('gallery')) {
            foreach($request->file('gallery') as $img) {
                $galleryPaths[] = $img->store('products/gallery', 'public');
            }
        }
        if(!empty($galleryPaths)) {
            $data['gallery'] = $galleryPaths;
        }

        if (!$data['category_id']) {
            $data['category_id'] = $data['parent_category_id'];
        }

        Product::create($data);

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
    public function update(Request $request, $id)
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
            'stock' => 'required|integer|min:0',
            'image' => 'nullable|image|max:2048',
            'gallery.*' => 'nullable|image|max:2048',
            'is_active' => 'nullable|boolean',
            'brand_id' => 'nullable|exists:brands,id',
            'parent_category_id' => 'nullable|exists:categories,id',
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
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        $galleryPaths = [];
        if ($request->hasFile('gallery')) {
            foreach ($request->file('gallery') as $img) {
                $galleryPaths[] = $img->store('products/gallery', 'public');
            }
        }

        if (!empty($galleryPaths)) {
            $data['gallery'] = $galleryPaths;
        }

        if (!$data['category_id']) {
            $data['category_id'] = $data['parent_category_id'];
        }   
        $product->update($data);

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
