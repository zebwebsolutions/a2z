<?php

namespace App\Http\Controllers\Front;

use App\Models\Category;
use App\Models\Product;
use App\Models\Brand;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CategoryController extends Controller
{
    public function show($slug)
    {
        $category = Category::where('slug', $slug)->firstOrFail();
        $breadcrumbItems = [
            ['label' => 'Home', 'url' => route('home')],
            ['label' => $category->name, 'url' => route('category.show', $category->slug)],
        ];

        $subcategories = $category->children()->orderBy('name')->get();
        $categoryBrands = $category->brands ?? collect();

        // ------------------------------------
        // BUILD BASE QUERY FOR CATEGORY
        // ------------------------------------
        $query = Product::query()
            ->where('is_active', true)
            ->where('is_used', false);

        if (in_array($category->slug, ['phones', 'tablets', 'laptops', 'smart-watches'])) {

            $childIds = $category->children()->pluck('id');

            $query->where(function($q) use ($category, $childIds) {
                $q->where('parent_category_id', $category->id)
                  ->orWhereIn('category_id', $childIds);
            });
        }
        elseif (in_array($category->slug, ['accessories', 'wearables'])) {

            if (!request()->filled('brand') && !request()->filled('min') && !request()->filled('max')
                && !request()->filled('ram') && !request()->filled('storage')) {

                return view('front.category.subcategory-grid', [
                    'category' => $category,
                    'subcategories' => $subcategories,
                    'breadcrumbItems' => $breadcrumbItems,
                ]);
            }

            $query->whereIn('category_id', $subcategories->pluck('id'));

        } else {
            $query->where('category_id', $category->id);
        }

        // ------------------------------------
        // BUILD FILTER EXTRACTION QUERY (BEFORE filters applied)
        // ------------------------------------
        $baseForFilters = clone $query;

        // AVAILABLE BRANDS
        $availableBrandIds = $baseForFilters->pluck('brand_id')->filter()->unique();
        $availableBrands = Brand::whereIn('id', $availableBrandIds)->orderBy('name')->get();

        // PRICE RANGE
        $priceMin = $baseForFilters->min('price') ?? 0;
        $priceMax = $baseForFilters->max('price') ?? 0;

        // SPECS EXTRACTION
        $specsRaw = $baseForFilters->pluck('specs');

        // RAM
        $availableRAM = Product::specificationOptions($specsRaw, Product::RAM_SPEC_JSON_KEYS)->all();

        // STORAGE
        $availableStorage = Product::specificationOptions($specsRaw, Product::STORAGE_SPEC_KEYS)->all();

        // ------------------------------------
        // APPLY USER FILTERS
        // ------------------------------------
        if (request()->filled('brand')) {
            // Force into array (handles ?brand=slug and ?brand[]=slug)
            $slugs = is_array(request('brand')) ? request('brand') : [request('brand')];
            
            $brandIds = Brand::whereIn('slug', $slugs)->pluck('id');
            
            if ($brandIds->count()) {
                $query->whereIn('brand_id', $brandIds);
            }
        }

        if (request()->filled('min') && (float) request('min') > 0) {
            $query->where('price', '>=', (float) request('min'));
        }

        if (request()->filled('max') && (float) request('max') > 0) {
            $query->where('price', '<=', (float) request('max'));
        }

        if (request()->filled('ram')) {
            $query->whereRamSpecification(request('ram'));
        }

        if (request()->filled('storage')) {
            $query->whereStorageSpecification(request('storage'));
        }

        // FINAL PRODUCT RESULTS
        $products = $query->latest()->paginate(18)->withQueryString();

        // AJAX RESPONSE
        if (request()->ajax()) {
            return response()->json([
                'products' => view('front.products.partials.product-grid', compact('products'))->render(),
                'chips' => view('components.filter-chips', [
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


        // NORMAL PAGE RESPONSE
        return view('front.category.products', [
            'category' => $category,
            'products' => $products,
            'brands' => $categoryBrands,
            'subcategories' => $subcategories,
            'breadcrumbItems' => $breadcrumbItems,

            // SMART FILTERS
            'availableBrands' => $availableBrands,
            'priceMin' => $priceMin,
            'priceMax' => $priceMax,
            'availableRAM' => $availableRAM,
            'availableStorage' => $availableStorage,
        ]);
    }

    // -----------------------------------------------------
    // BRAND FILTER PAGE
    // -----------------------------------------------------
    public function brandFilter(Request $request, $categorySlug, $brandSlug)
    {
        // ------------------------------------
        // RESOLVE CATEGORY & BRAND BY SLUG
        // ------------------------------------
        $category = Category::where('slug', $categorySlug)->firstOrFail();
        $brand    = Brand::where('slug', $brandSlug)->firstOrFail();

        // ------------------------------------
        // BASE QUERY (brand scoped)
        // ------------------------------------
        $base = Product::query()
            ->where('brand_id', $brand->id)
            ->where('is_active', 1);

        /**
         * CATEGORY LOGIC
         * - If CHILD category → only itself
         * - If PARENT category → include children
         */
        if ($category->parent_id) {
            // CHILD CATEGORY (e.g. headphones)
            $base->where('category_id', $category->id);
        } else {
            // PARENT CATEGORY (e.g. accessories)
            $childIds = $category->children()->pluck('id')->toArray();

            $base->where(function ($q) use ($category, $childIds) {
                $q->where('category_id', $category->id)
                ->orWhereIn('category_id', $childIds);
            });
        }

        // ------------------------------------
        // PRICE RANGE (BEFORE FILTERS)
        // ------------------------------------
        $priceMin = $base->min('price') ?? 0;
        $priceMax = $base->max('price') ?? 0;

        // ------------------------------------
        // SPECS EXTRACTION (JSON)
        // ------------------------------------
        $specsRaw = $base->pluck('specs')->filter();

        $availableRAM = Product::specificationOptions($specsRaw, Product::RAM_SPEC_JSON_KEYS)->all();

        $availableStorage = Product::specificationOptions($specsRaw, Product::STORAGE_SPEC_KEYS)->all();

        // ------------------------------------
        // APPLY USER FILTERS
        // ------------------------------------
        $query = clone $base;

        if ($request->filled('min') && (float) $request->input('min') > 0) {
            $query->where('price', '>=', (float) $request->input('min'));
        }

        if ($request->filled('max') && (float) $request->input('max') > 0) {
            $query->where('price', '<=', (float) $request->input('max'));
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
        $products = $query->latest()->paginate(18)->withQueryString();

        // ------------------------------------
        // BREADCRUMB
        // ------------------------------------
        $breadcrumbItems = [
            ['label' => 'Home', 'url' => route('home')],
            ['label' => $category->name, 'url' => route('category.show', $category->slug)],
            ['label' => $brand->name, 'url' => route('brand.category', [
                'category' => $category->slug,
                'brand' => $brand->slug,
            ])],
        ];

        // ------------------------------------
        // AJAX RESPONSE
        // ------------------------------------
        if ($request->ajax()) {
            return response()->json([
                'products' => view('front.products.partials.product-grid', compact('products'))->render(),
                'chips' => view('components.filter-chips', [
                    'category' => $category,
                    'availableBrands' => collect([$brand]),
                    'priceMin' => $priceMin,
                    'priceMax' => $priceMax,
                    'availableRAM' => $availableRAM,
                    'availableStorage' => $availableStorage,
                    'subcategories' => [],
                ])->render(),
                'pagination' => $products->links()->render(),
            ]);
        }

        // ------------------------------------
        // NORMAL PAGE RESPONSE
        // ------------------------------------
        return view('front.category.brand-filter', [
            'category' => $category,
            'brand' => $brand,
            'products' => $products,
            'breadcrumbItems' => $breadcrumbItems,

            // FILTER DATA
            'availableBrands' => collect([$brand]),
            'priceMin' => $priceMin,
            'priceMax' => $priceMax,
            'availableRAM' => $availableRAM,
            'availableStorage' => $availableStorage,
            'subcategories' => [],
        ]);
    }

}
