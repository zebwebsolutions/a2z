<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;

class BrandController extends Controller
{
    public function show(Request $request, $slug)
    {
        $category = Category::where('slug', 'phones')->first();

        // ------------------------------------
        // BRAND
        // ------------------------------------
        $brand = Brand::where('slug', $slug)->firstOrFail();

        // ------------------------------------
        // BASE QUERY (brand scoped)
        // ------------------------------------
        $query = Product::where('brand_id', $brand->id)
            ->where('is_active', 1);

        // ------------------------------------
        // BUILD FILTER EXTRACTION QUERY
        // (IMPORTANT: before filters applied)
        // ------------------------------------
        $baseForFilters = clone $query;

        // ------------------------------------
        // AVAILABLE CATEGORIES (optional sidebar)
        // ------------------------------------
        if ($category) {
            $subcategories = $category->children()->orderBy('name')->get();
        } else {
            $subcategories = collect();
        }

        // ------------------------------------
        // AVAILABLE BRANDS (single brand page)
        // ------------------------------------
        $availableBrands = collect([$brand]);

        // ------------------------------------
        // PRICE RANGE
        // ------------------------------------
        $priceMin = $baseForFilters->min('price') ?? 0;
        $priceMax = $baseForFilters->max('price') ?? 0;

        // ------------------------------------
        // SPECS EXTRACTION (JSON)
        // ------------------------------------
        $specsRaw = $baseForFilters->pluck('specs');

        // RAM
        $availableRAM = Product::specificationOptions($specsRaw, Product::RAM_SPEC_JSON_KEYS)->all();

        // STORAGE
        $availableStorage = Product::specificationOptions($specsRaw, Product::STORAGE_SPEC_KEYS)->all();

        // ------------------------------------
        // APPLY USER FILTERS
        // ------------------------------------
        if ($request->filled('min')) {
            $query->where('price', '>=', (float) $request->min);
        }

        if ($request->filled('max')) {
            $query->where('price', '<=', (float) $request->max);
        }

        if ($request->filled('ram')) {
            $query->whereRamSpecification($request->input('ram'));
        }

        if ($request->filled('storage')) {
            $query->whereStorageSpecification($request->input('storage'));
        }

        // ------------------------------------
        // FINAL PRODUCTS
        // ------------------------------------
        $products = $query->latest()->paginate(12)->withQueryString();

        // ------------------------------------
        // BREADCRUMB
        // ------------------------------------
        $breadcrumbItems = [
            ['label' => 'Home', 'url' => route('home')],
            ['label' => $brand->name, 'url' => route('brand.index', $brand->slug)],
        ];

        // ------------------------------------
        // AJAX RESPONSE (optional, ready)
        // ------------------------------------
        if ($request->ajax()) {
            return response()->json([
                'products' => view('front.products.partials.product-grid', compact('products'))->render(),
                'chips' => view('components.filter-chips', [
                    'brand' => $brand,
                    'category' => $category,
                    'availableBrands' => $availableBrands,
                    'priceMin' => $priceMin,
                    'priceMax' => $priceMax,
                    'availableRAM' => $availableRAM,
                    'availableStorage' => $availableStorage,
                    'subcategories' => $subcategories,
                ])->render(),
                'pagination' => $products->links()->render(),
            ]);
        }

        // ------------------------------------
        // NORMAL PAGE RESPONSE
        // ------------------------------------
        return view('front.brand.index', [
            'category' => $category,
            'brand' => $brand,
            'products' => $products,
            'breadcrumbItems' => $breadcrumbItems,

            // FILTER DATA
            'brands' => collect([$brand]),
            'availableBrands' => collect([$brand]),
            'subcategories' => $subcategories,
            'priceMin' => $priceMin,
            'priceMax' => $priceMax,
            'availableRAM' => $availableRAM,
            'availableStorage' => $availableStorage,
        ]);
    }
}
