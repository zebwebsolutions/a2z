<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category;
use App\Models\Store;
use App\Models\Brand;

class ShopController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query()->where('is_active', true);

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

        // APPLY SPEC FILTERS
        if ($request->filled('ram')) {
            $query->whereIn('specs->RAM', $request->ram);
        }

        if ($request->filled('processor')) {
            $query->whereIn('specs->Processor', $request->processor);
        }

        if ($request->filled('screen_size')) {
            $query->where('specs->Screen Size', $request->screen_size);
        }


        // BUILD FILTER OPTIONS
        $ramOptions = Product::selectRaw("JSON_UNQUOTE(JSON_EXTRACT(specs, '$.Ram')) AS ram")
            ->whereNotNull('specs')
            ->groupBy('ram')
            ->pluck('ram')
            ->filter(fn($v) => !empty($v));

        $processors = Product::selectRaw("JSON_UNQUOTE(JSON_EXTRACT(specs, '$.Processor')) AS processor")
            ->whereNotNull('specs')
            ->groupBy('processor')
            ->pluck('processor')
            ->filter(fn($v) => !empty($v));

        $screenSizes = Product::selectRaw("JSON_UNQUOTE(JSON_EXTRACT(specs, '$.ScreenSize')) AS screenSize")
            ->whereNotNull('specs')
            ->groupBy('screenSize')
            ->pluck('screenSize')
            ->filter(fn($v) => !empty($v));

        $products = $query->paginate(12)->withQueryString();

        $categories = Category::with('brands')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $stores = Store::all();

        return view('front.shop.index', compact('products', 'categories', 'stores', 'ramOptions', 'processors', 'screenSizes'));
    }

    public function ajaxProducts(Request $request)
    {
        // Copy SAME FILTER LOGIC from index()
        $query = Product::query()->where('is_active', true);

        if ($request->filled('category')) {
            $query->whereHas('category', fn($q) =>
                $q->where('slug', $request->category)
            );
        }

        if ($request->filled('ram')) {
            $query->where(function ($q) use ($request) {
                foreach ($request->ram as $ram) {
                    $q->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(specs, '$.Ram')) = ?", [$ram]);
                }
            });
        }

        if ($request->filled('processor')) {
            $query->where(function ($q) use ($request) {
                foreach ($request->processor as $processor) {
                    $q->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(specs, '$.Processor')) = ?", [$processor]);
                }
            });
        }

        if ($request->filled('screen_size')) {
            $query->where(function ($q) use ($request) {
                foreach ($request->screen_size as $screen) {
                    $q->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(specs, '$.ScreenSize')) = ?", [$screen]);
                }
            });
        }

        if ($request->filled('max_price')) {
            $query->where('price', '<=', (int) $request->max_price);
        }

        // Fetch products
        $products = $query->paginate(12)->withQueryString();

        // Return only the product grid HTML (partial)
        return response()->json([
            'html' => view('front.products.partials.product-grid', compact('products'))->render(),
            'pagination' => view('front.products.partials.pagination', compact('products'))->render()
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


}
