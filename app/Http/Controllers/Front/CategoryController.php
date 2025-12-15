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

        $subcategories = $category->children()->orderBy('name')->get();
        $categoryBrands = $category->brands ?? collect();

        // ------------------------------------
        // BUILD BASE QUERY FOR CATEGORY
        // ------------------------------------
        $query = Product::query();

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
        $availableRAM = [];
        foreach ($specsRaw as $spec) {
            if (!is_array($spec)) continue;
            if (isset($spec['RAM'])) {
                $availableRAM[] = $spec['RAM'];
            }
        }
        $availableRAM = collect($availableRAM)->unique()->values()->sort()->all();

        // STORAGE
        $availableStorage = [];
        foreach ($specsRaw as $spec) {
            if (!is_array($spec)) continue;
            if (isset($spec['STORAGE'])) {
                $availableStorage[] = $spec['STORAGE'];
            }
        }
        $availableStorage = collect($availableStorage)->unique()->values()->sort()->all();

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

        if (request()->filled('min')) {
            $query->where('price', '>=', request('min'));
        }

        if (request()->filled('max')) {
            $query->where('price', '<=', request('max'));
        }

        if (request()->filled('ram')) {
            $query->whereJsonContains('specs->RAM', request('ram'));
        }

        if (request()->filled('storage')) {
            $query->whereJsonContains('specs->STORAGE', request('storage'));
        }

        // FINAL PRODUCT RESULTS
        $products = $query->latest()->paginate(20)->withQueryString();

        // BREADCRUMB
        $breadcrumbItems = [
            ['label' => 'Home', 'url' => route('home')],
            ['label' => $category->name, 'url' => '#'],
        ];

        // AJAX RESPONSE
        // if (request()->ajax()) {
        //     return view('front.category.partials.ajax-response', [
        //         'category' => $category,
        //         'products' => $products,
        //         'availableBrands' => $availableBrands,
        //         'availableRAM' => $availableRAM,
        //         'availableStorage' => $availableStorage,
        //         'priceMin' => $priceMin,
        //         'priceMax' => $priceMax,
        //         'subcategories' => $subcategories,
        //         'menuCategories' => Category::whereNull('parent_id')->with('menu_items')->get()
        //     ]);
        // }


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
        $category = Category::where('slug', $categorySlug)->firstOrFail();
        $brand    = Brand::where('slug', $brandSlug)->firstOrFail();

        // Base query: products in this category (or its children) and this brand
        $childIds = $category->children()->pluck('id')->toArray();

        $base = Product::where(function ($q) use ($category, $childIds) {
                $q->where('parent_category_id', $category->id)
                ->orWhereIn('category_id', $childIds);
            })
            ->where('brand_id', $brand->id)
            ->where('is_active', 1);

        // Price range based on base set
        $priceMin = $base->min('price') ?? 0;
        $priceMax = $base->max('price') ?? 0;

        // Build available specs lists (assuming specs keys are uppercase like 'RAM' and 'STORAGE')
        $specsRaw = $base->pluck('specs')->filter();

        $availableRAM = collect($specsRaw)
            ->map(function ($s) { return is_array($s) ? ($s['RAM'] ?? null) : (data_get($s, 'RAM')); })
            ->filter()
            ->unique()
            ->values()
            ->sort()
            ->all();

        $availableStorage = collect($specsRaw)
            ->map(function ($s) { return is_array($s) ? ($s['STORAGE'] ?? null) : (data_get($s, 'STORAGE')); })
            ->filter()
            ->unique()
            ->values()
            ->sort()
            ->all();

        // APPLY FILTERS (use request values)
        $query = clone $base;

        if ($request->filled('min')) {
            $query->where('price', '>=', (float) $request->input('min'));
        }
        if ($request->filled('max')) {
            $query->where('price', '<=', (float) $request->input('max'));
        }
        if ($request->filled('ram')) {
            // JSON key is uppercase 'RAM'
            $query->whereJsonContains('specs->RAM', $request->input('ram'));
        }
        if ($request->filled('storage')) {
            $query->whereJsonContains('specs->STORAGE', $request->input('storage'));
        }

        $products = $query->latest()->paginate(20)->withQueryString();

        $breadcrumbItems = [
            ['label' => 'Home', 'url' => route('home')],
            ['label' => $category->name, 'url' => route('category.show', $category->slug)],
            ['label' => $brand->name, 'url' => '#'],
        ];

        // If AJAX: return JSON with two rendered fragments (products + chips)
        if ($request->ajax()) {
            $productsHtml = view('front.products.partials.product-grid', compact('products'))->render();

            $chipsHtml = view('components.filter-chips', [
                'category' => $category,
                'availableBrands' => collect([$brand]),
                'priceMin' => $priceMin,
                'priceMax' => $priceMax,
                'availableRAM' => $availableRAM,
                'availableStorage' => $availableStorage,
                'subcategories' => [],
            ])->render();

            return response()->json([
                'products' => $productsHtml,
                'chips'    => $chipsHtml,
                'pagination' => $products->links()->render(),
            ]);
        }

        // Regular full-page response
        return view('front.category.brand-filter', [
            'category' => $category,
            'brand' => $brand,
            'products' => $products,
            'breadcrumbItems' => $breadcrumbItems,
            'availableBrands' => collect([$brand]),
            'priceMin' => $priceMin,
            'priceMax' => $priceMax,
            'availableRAM' => $availableRAM,
            'availableStorage' => $availableStorage,
            'subcategories' => [],
        ]);
    }
}