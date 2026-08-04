<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category;
use App\Models\Store;
use App\Models\Brand;
use App\Models\UsedDeviceDetail;
use Illuminate\Database\Eloquent\Builder;

class ShopController extends Controller
{
    public function index(Request $request)
    {
        $query = $this->shopContextQuery($request);
        $this->applyShopListingFilters($query, $request);

        $baseForFilters = $this->shopContextQuery($request);
        $baseForBrands = $this->shopContextQuery($request, includeBrand: false);
        $filterData = $this->shopFilterData($baseForFilters, $baseForBrands);

        $products = $query->paginate(12)->withQueryString();
        $products->withPath(route('shop.index'));

        $availableBrands = $filterData['availableBrands'];
        $priceMin = $filterData['priceMin'];
        $priceMax = $filterData['priceMax'];
        $ramOptions = $filterData['ramOptions'];
        $storageOptions = $filterData['storageOptions'];
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
        $query = $this->shopContextQuery($request);
        $this->applyShopListingFilters($query, $request);

        $baseForFilters = $this->shopContextQuery($request);
        $baseForBrands = $this->shopContextQuery($request, includeBrand: false);
        $filterData = $this->shopFilterData($baseForFilters, $baseForBrands);

        // Fetch products
        $products = $query->paginate(12)->withQueryString();
        $products->withPath(route('shop.index'));

        $availableBrands = $filterData['availableBrands'];
        $priceMin = $filterData['priceMin'];
        $priceMax = $filterData['priceMax'];
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
                'availableRAM' => $filterData['ramOptions'],
                'availableStorage' => $filterData['storageOptions'],
            ])->render(),
        ]);
    }

    public function search(Request $request)
    {
        $q = $request->input('q');

        $products = Product::query()
            ->where('is_active', true)
            ->when($q, fn ($query) => $this->applyProductKeywordSearch($query, $q))
            ->when($q, fn ($query) => $this->orderProductSearchResults($query, $q), fn ($query) => $query->latest())
            ->paginate(24)
            ->withQueryString();

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

            $results = Product::query()
                ->where('is_active', true)
                ->where(fn ($query) => $this->applyProductKeywordSearch($query, $q))
                ->tap(fn ($query) => $this->orderProductSearchResults($query, $q))
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
            ->where('is_active', true)
            ->where('is_used', 1)
            ->whereHas('usedDeviceDetails', fn ($q) =>
                $q->whereNotNull('device_condition')
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
                $q->where('is_active', true)->where('is_used', 1)
            )
            ->whereNotNull('device_condition')
            ->whereNotNull('battery_health')
            ->min('battery_health') ?? 0;

        $batteryMax = UsedDeviceDetail::query()
            ->whereHas('product', fn ($q) =>
                $q->where('is_active', true)->where('is_used', 1)
            )
            ->whereNotNull('device_condition')
            ->whereNotNull('battery_health')
            ->max('battery_health') ?? 100;

        $specsRaw = $baseForFilters->pluck('specs');

        // RAM
        $availableRAM = Product::specificationOptions($specsRaw, Product::RAM_SPEC_JSON_KEYS)->all();

        // STORAGE
        $availableStorage = Product::specificationOptions($specsRaw, Product::STORAGE_SPEC_KEYS)->all();

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
            $query->whereRamSpecification($request->input('ram'));
        }

        if ($request->filled('storage')) {
            $query->whereStorageSpecification($request->input('storage'));
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
                'chips' => view('components.filter-chips', [
                    'category' => $category,
                    'availableBrands' => $availableBrands,
                    'priceMin' => $priceMin,
                    'priceMax' => $priceMax,
                    'availableRAM' => $availableRAM,
                    'availableStorage' => $availableStorage,
                    'subcategories' => collect(),
                ])->render(),
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

    private function shopContextQuery(Request $request, bool $includeBrand = true): Builder
    {
        $query = Product::query()->where('is_active', true);

        if ($request->filled('category')) {
            $query->whereHas('category', fn ($categoryQuery) =>
                $categoryQuery->where('slug', $request->input('category'))
            );
        }

        if ($includeBrand && $request->filled('brand')) {
            $query->whereHas('brand', fn ($brandQuery) =>
                $brandQuery->where('slug', $request->input('brand'))
            );
        }

        if ($request->filled('q')) {
            $this->applyProductKeywordSearch($query, $request->input('q'));
        }

        if ($request->filled('store')) {
            $query->where('store_id', $request->input('store'));
        }

        return $query;
    }

    private function applyShopListingFilters(Builder $query, Request $request): Builder
    {
        if ($request->filled('min_price')) {
            $query->where('price', '>=', (float) $request->input('min_price'));
        }

        if ($request->filled('max_price')) {
            $query->where('price', '<=', (float) $request->input('max_price'));
        }

        if ($request->filled('min')) {
            $query->where('price', '>=', (float) $request->input('min'));
        }

        if ($request->filled('max')) {
            $query->where('price', '<=', (float) $request->input('max'));
        }

        if ($request->filled('ram')) {
            $query->whereRamSpecification($request->input('ram'));
        }

        if ($request->filled('storage')) {
            $query->whereStorageSpecification($request->input('storage'));
        }

        if ($request->filled('processor')) {
            $query->whereProcessorSpecification($request->input('processor'));
        }

        if ($request->filled('screen_size')) {
            $query->whereScreenSizeSpecification($request->input('screen_size'));
        }

        return $query;
    }

    private function shopFilterData(Builder $baseForFilters, Builder $baseForBrands): array
    {
        $specs = (clone $baseForFilters)->whereNotNull('specs')->pluck('specs');
        $availableBrandIds = (clone $baseForBrands)->pluck('brand_id')->filter()->unique();

        return [
            'availableBrands' => Brand::whereIn('id', $availableBrandIds)->orderBy('name')->get(),
            'priceMin' => (clone $baseForFilters)->min('price') ?? 0,
            'priceMax' => (clone $baseForFilters)->max('price') ?? 0,
            'ramOptions' => Product::specificationOptions($specs, Product::RAM_SPEC_JSON_KEYS),
            'storageOptions' => Product::specificationOptions($specs, Product::STORAGE_SPEC_KEYS),
        ];
    }

    private function applyProductKeywordSearch($query, string $search)
    {
        $tokens = $this->searchTokens($search);

        if (empty($tokens)) {
            return $query;
        }

        foreach ($tokens as $token) {
            $like = "%{$token}%";

            $query->where(function ($q) use ($like) {
                $q->where('name', 'LIKE', $like)
                    ->orWhere('sku', 'LIKE', $like)
                    ->orWhere('barcode', 'LIKE', $like)
                    ->orWhere('description', 'LIKE', $like)
                    ->orWhere('specs', 'LIKE', $like)
                    ->orWhereHas('brand', fn ($brand) => $brand->where('name', 'LIKE', $like));
            });
        }

        return $query;
    }

    private function orderProductSearchResults($query, string $search)
    {
        $phrase = trim($search);

        if ($phrase === '') {
            return $query->latest();
        }

        return $query
            ->orderByRaw('CASE WHEN name LIKE ? THEN 0 WHEN name LIKE ? THEN 1 ELSE 2 END', [
                $phrase . '%',
                '%' . $phrase . '%',
            ])
            ->orderBy('name');
    }

    private function searchTokens(string $search): array
    {
        $normalized = preg_replace('/[^\pL\pN]+/u', ' ', strtolower($search));

        $stopWords = [
            'only',
            'with',
            'and',
            'for',
            'the',
            'a',
            'an',
        ];

        return collect(preg_split('/\s+/', trim($normalized)))
            ->filter(fn ($token) => strlen($token) >= 2 && ! in_array($token, $stopWords, true))
            ->unique()
            ->values()
            ->all();
    }
}
