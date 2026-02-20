<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category;
use App\Models\Store;
use App\Models\Brand;
use App\Models\UsedDeviceDetail;

class ShopController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query()->where('is_active', true);
        $baseForFilters = Product::query()->where('is_active', true);

        // CATEGORY FILTER
        if ($request->filled('category')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('slug', $request->category);
            });
        }

        // BRAND FILTER
        if ($request->filled('brand')) {
            $query->whereHas('brand', function ($q) use ($request) {
                $q->where('slug', $request->brand);
            });
        }

        // SEARCH FILTER
        if ($request->filled('q')) {
            $query->where('name', 'like', '%' . $request->q . '%');
        }

        // STORE FILTER
        if ($request->filled('store')) {
            $query->where('store_id', $request->store);
        }

        // PRICE FILTERS
        if ($request->filled('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }

        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        if ($request->filled('min')) {
            $query->where('price', '>=', $request->min);
        }

        if ($request->filled('max')) {
            $query->where('price', '<=', $request->max);
        }

        // APPLY SPEC FILTERS
        if ($request->filled('ram')) {
            $ramValues = is_array($request->ram) ? $request->ram : [$request->ram];
            $query->where(function ($q) use ($ramValues) {
                foreach ($ramValues as $ram) {
                    $q->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(specs, '$.RAM')) = ?", [$ram])
                      ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(specs, '$.Ram')) = ?", [$ram]);
                }
            });
        }

        if ($request->filled('storage')) {
            $storageValues = is_array($request->storage) ? $request->storage : [$request->storage];
            $query->where(function ($q) use ($storageValues) {
                foreach ($storageValues as $storage) {
                    $q->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(specs, '$.STORAGE')) = ?", [$storage])
                      ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(specs, '$.Storage')) = ?", [$storage]);
                }
            });
        }

        if ($request->filled('processor')) {
            $processorValues = is_array($request->processor) ? $request->processor : [$request->processor];
            $query->where(function ($q) use ($processorValues) {
                foreach ($processorValues as $processor) {
                    $q->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(specs, '$.Processor')) = ?", [$processor]);
                }
            });
        }

        if ($request->filled('screen_size')) {
            $query->where('specs->Screen Size', $request->screen_size);
        }


        // BUILD FILTER OPTIONS FROM STORED SPECS JSON
        $specsRaw = (clone $baseForFilters)->whereNotNull('specs')->pluck('specs');

        $ramOptions = collect();
        $storageOptions = collect();

        foreach ($specsRaw as $spec) {
            if (!is_array($spec)) {
                continue;
            }

            $ram = $spec['RAM'] ?? $spec['Ram'] ?? $spec['ram'] ?? null;
            $storage = $spec['STORAGE'] ?? $spec['Storage'] ?? $spec['storage'] ?? null;

            if (!empty($ram)) {
                $ramOptions->push($ram);
            }
            if (!empty($storage)) {
                $storageOptions->push($storage);
            }
        }

        $ramOptions = $ramOptions->unique()->sort()->values();
        $storageOptions = $storageOptions->unique()->sort()->values();

        $products = $query->paginate(12)->withQueryString();
        $products->withPath(route('shop.index'));

        $availableBrandIds = (clone $baseForFilters)->pluck('brand_id')->filter()->unique();
        $availableBrands = Brand::whereIn('id', $availableBrandIds)->orderBy('name')->get();
        $priceMin = (clone $baseForFilters)->min('price') ?? 0;
        $priceMax = (clone $baseForFilters)->max('price') ?? 0;
        $virtualCategory = (object) ['id' => null, 'slug' => null];

        $categories = Category::with('brands')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $stores = Store::all();

        return view('front.shop.index', compact(
            'products',
            'categories',
            'stores',
            'ramOptions',
            'storageOptions',
            'availableBrands',
            'priceMin',
            'priceMax',
            'virtualCategory'
        ));
    }

    public function ajaxProducts(Request $request)
    {
        // Copy SAME FILTER LOGIC from index()
        $query = Product::query()->where('is_active', true);
        $baseForFilters = Product::query()->where('is_active', true);

        if ($request->filled('category')) {
            $query->whereHas('category', fn($q) =>
                $q->where('slug', $request->category)
            );
        }

        if ($request->filled('ram')) {
            $ramValues = is_array($request->ram) ? $request->ram : [$request->ram];
            $query->where(function ($q) use ($ramValues) {
                foreach ($ramValues as $ram) {
                    $q->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(specs, '$.Ram')) = ?", [$ram]);
                    $q->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(specs, '$.RAM')) = ?", [$ram]);
                }
            });
        }

        if ($request->filled('storage')) {
            $storageValues = is_array($request->storage) ? $request->storage : [$request->storage];
            $query->where(function ($q) use ($storageValues) {
                foreach ($storageValues as $storage) {
                    $q->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(specs, '$.Storage')) = ?", [$storage]);
                    $q->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(specs, '$.STORAGE')) = ?", [$storage]);
                }
            });
        }

        if ($request->filled('max_price')) {
            $query->where('price', '<=', (int) $request->max_price);
        }

        if ($request->filled('min_price')) {
            $query->where('price', '>=', (int) $request->min_price);
        }

        if ($request->filled('max')) {
            $query->where('price', '<=', (float) $request->max);
        }

        if ($request->filled('min')) {
            $query->where('price', '>=', (float) $request->min);
        }

        if ($request->filled('brand')) {
            $query->whereHas('brand', fn ($q) => $q->where('slug', $request->brand));
        }

        if ($request->filled('store')) {
            $query->where('store_id', $request->store);
        }

        // Fetch products
        $products = $query->paginate(12)->withQueryString();
        $products->withPath(route('shop.index'));

        $availableBrandIds = (clone $baseForFilters)->pluck('brand_id')->filter()->unique();
        $availableBrands = Brand::whereIn('id', $availableBrandIds)->orderBy('name')->get();
        $priceMin = (clone $baseForFilters)->min('price') ?? 0;
        $priceMax = (clone $baseForFilters)->max('price') ?? 0;
        $virtualCategory = (object) ['id' => null, 'slug' => null];

        // Return only the product grid HTML (partial)
        return response()->json([
            'html' => view('front.products.partials.product-grid', compact('products'))->render(),
            'pagination' => view('front.products.partials.pagination', compact('products'))->render(),
            'chips' => view('components.filter-chips', [
                'category' => $virtualCategory,
                'availableBrands' => $availableBrands,
                'priceMin' => $priceMin,
                'priceMax' => $priceMax,
            ])->render(),
        ]);
    }

    public function search(Request $request)
    {
        $q = $request->input('q');

        $products = Product::query()
            ->where('name', 'LIKE', "%{$q}%")
            ->orWhere('sku', 'LIKE', "%{$q}%")
            ->orWhere('description', 'LIKE', "%{$q}%")
            ->orderBy('name')
            ->paginate(24);

        return view('front.shop.search', [
            'products' => $products,
            'query' => $q
        ]);
    }

    public function suggest(Request $request)
    {
        try {
            $q = $request->q;

            if (!$q || strlen($q) < 2) {
                return response()->json([]);
            }

            $results = Product::where('name', 'LIKE', "%{$q}%")
                ->orWhere('sku', 'LIKE', "%{$q}%")
                ->limit(8)
                ->get();

            return response()->json($results);

        } catch (\Exception $e) {
            \Log::error("Search error: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function used(Request $request)
    {
        // ------------------------------------
        // BASE QUERY (USED DEVICES ONLY)
        // ------------------------------------
        $query = Product::query()
            ->where('is_used', 1)
            ->whereHas('usedDeviceDetails', fn ($q) =>
                $q->where('device_condition', 'used')
            );

        // ------------------------------------
        // BASE QUERY FOR FILTER EXTRACTION
        // (IMPORTANT: before user filters)
        // ------------------------------------
        $baseForFilters = clone $query;

        // ------------------------------------
        // AVAILABLE BRANDS (same pattern)
        // ------------------------------------
        $availableBrandIds = $baseForFilters->pluck('brand_id')->filter()->unique();
        $availableBrands = Brand::whereIn('id', $availableBrandIds)
            ->orderBy('name')
            ->get();

        // ------------------------------------
        // PRICE RANGE
        // ------------------------------------
        $priceMin = $baseForFilters->min('price') ?? 0;
        $priceMax = $baseForFilters->max('price') ?? 0;

        // ------------------------------------
        // BATTERY HEALTH RANGE (USED ONLY)
        // ------------------------------------
        $batteryMin = UsedDeviceDetail::query()
            ->whereHas('product', fn ($q) =>
                $q->where('is_used', 1)
            )
            ->where('device_condition', 'used')
            ->whereNotNull('battery_health')
            ->min('battery_health') ?? 0;

        $batteryMax = UsedDeviceDetail::query()
            ->whereHas('product', fn ($q) =>
                $q->where('is_used', 1)
            )
            ->where('device_condition', 'used')
            ->whereNotNull('battery_health')
            ->max('battery_health') ?? 100;

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
        // APPLY USER FILTERS (IDENTICAL BEHAVIOUR)
        // ------------------------------------
        if ($request->filled('brand')) {
            $slugs = is_array($request->brand) ? $request->brand : [$request->brand];
            $brandIds = Brand::whereIn('slug', $slugs)->pluck('id');

            if ($brandIds->count()) {
                $query->whereIn('brand_id', $brandIds);
            }
        }

        if ($request->filled('min')) {
            $query->where('price', '>=', (float) $request->min);
        }

        if ($request->filled('max')) {
            $query->where('price', '<=', (float) $request->max);
        }

        if ($request->filled('battery')) {
            $values = is_array($request->battery)
                ? array_map('intval', $request->battery)
                : [(int) $request->battery];

            $minBattery = min($values);

            $query->whereHas('usedDeviceDetails', function ($q) use ($minBattery) {
                $q->where('battery_health', '>=', $minBattery);
            });
        }

        if ($request->filled('ram')) {
            $query->whereJsonContains('specs->RAM', $request->ram);
        }

        if ($request->filled('storage')) {
            $query->whereJsonContains('specs->STORAGE', $request->storage);
        }

        // ------------------------------------
        // FINAL PRODUCTS
        // ------------------------------------
        $products = $query->latest()->paginate(20)->withQueryString();

        // ------------------------------------
        // VIRTUAL CATEGORY (FOR SIDEBAR + BREADCRUMB)
        // ------------------------------------
        $category = (object) [
            'id' => null,
            'name' => 'Used Devices',
            'slug' => 'used-devices',
            'children' => collect(),
        ];

        // ------------------------------------
        // AJAX RESPONSE (FILTERING)
        // ------------------------------------
        if ($request->ajax()) {
            return response()->json([
                'products' => view(
                    'front.shop.partials.product-grid',
                    compact('products')
                )->render(),

                'pagination' => $products->links()->render(),
            ]);
        }

        // ------------------------------------
        // VIEW
        // ------------------------------------
        return view('front.shop.used', [
            'category' => $category,
            'products' => $products,

            // SIDEBAR DATA (EXACT SAME KEYS)
            'availableBrands' => $availableBrands,
            'priceMin' => $priceMin,
            'priceMax' => $priceMax,
            'batteryMin' => $batteryMin,
            'batteryMax' => $batteryMax,
            'availableRAM' => $availableRAM,
            'availableStorage' => $availableStorage,
            'subcategories' => collect(),
        ]);
    }

}
