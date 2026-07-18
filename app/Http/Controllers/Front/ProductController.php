<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Store;
use App\Models\Category;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    // Display all active products (with filters)
    public function index(Request $request)
    {
        $query = Product::where('is_active', true)
            ->with(['store', 'category']);

        if ($request->filled('store')) {
            $query->where('store_id', $request->store);
        }

        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }

        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        if ($request->filled('ram')) {
            $query->where(function ($q) use ($request) {
                $q->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(specs, '$.RAM')) = ?", [$request->ram])
                    ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(specs, '$.Ram')) = ?", [$request->ram])
                    ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(specs, '$.ram')) = ?", [$request->ram]);
            });
        }

        if ($request->filled('storage')) {
            $query->where(function ($q) use ($request) {
                $q->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(specs, '$.STORAGE')) = ?", [$request->storage])
                    ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(specs, '$.Storage')) = ?", [$request->storage])
                    ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(specs, '$.storage')) = ?", [$request->storage]);
            });
        }

        if ($request->filled('color')) {
            $query->where(function ($q) use ($request) {
                $q->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(specs, '$.COLOR')) = ?", [$request->color])
                    ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(specs, '$.Color')) = ?", [$request->color])
                    ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(specs, '$.color')) = ?", [$request->color]);
            });
        }

        if ($request->sort === 'price_asc') {
            $query->orderBy('price', 'asc');
        } elseif ($request->sort === 'price_desc') {
            $query->orderBy('price', 'desc');
        } else {
            $query->latest();
        }

        $products = $query->paginate(12)->withQueryString();
        $stores = Store::all();
        $categories = Category::all();

        $ramOptions = collect();
        $storageOptions = collect();
        $colorOptions = collect();

        Product::where('is_active', true)
            ->whereNotNull('specs')
            ->pluck('specs')
            ->each(function ($specs) use (&$ramOptions, &$storageOptions, &$colorOptions) {
                if (!is_array($specs)) {
                    return;
                }

                $ram = $specs['RAM'] ?? $specs['Ram'] ?? $specs['ram'] ?? null;
                $storage = $specs['STORAGE'] ?? $specs['Storage'] ?? $specs['storage'] ?? null;
                $color = $specs['COLOR'] ?? $specs['Color'] ?? $specs['color'] ?? null;

                if (!empty($ram)) {
                    $ramOptions->push($ram);
                }
                if (!empty($storage)) {
                    $storageOptions->push($storage);
                }
                if (!empty($color)) {
                    $colorOptions->push($color);
                }
            });

        $ramOptions = $ramOptions->unique()->sort()->values();
        $storageOptions = $storageOptions->unique()->sort()->values();
        $colorOptions = $colorOptions->unique()->sort()->values();

        return view('front.products.index', compact(
            'products',
            'stores',
            'categories',
            'ramOptions',
            'storageOptions',
            'colorOptions'
        ));
    }

    // Single product details
    public function show($slug)
    {
        $product = Product::where('slug', $slug)->where('is_active', true)->firstOrFail();

        $parent = $product->parentCategory;
        $child  = $product->category;
        $brand  = $product->brand;

        // Build breadcrumb items
        $breadcrumbItems = [
            ['label' => 'Home', 'url' => route('home')],
        ];

        if ($parent) {
            $breadcrumbItems[] = [
                'label' => $parent->name,
                'url' => route('category.show', $parent->slug),
            ];
        }

        if ($child && $child->id !== ($parent->id ?? null)) {
            $breadcrumbItems[] = [
                'label' => $child->name,
                'url' => route('category.show', $child->slug),
            ];
        }

        if ($brand) {
            $breadcrumbItems[] = [
                'label' => $brand->name,
                'url' => $parent
                    ? route('brand.category', [
                        'category' => $parent->slug,
                        'brand' => $brand->slug,
                    ])
                    : route('brand.index', $brand->slug),
            ];
        }

        abort_unless($product->is_active, 404);

        $relatedProducts = Product::where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->where('is_active', true)
            ->take(4)
            ->get();

        $colourVariants = $this->colourVariantsFor($product);

        return view('front.products.show', compact('product', 'breadcrumbItems', 'relatedProducts', 'colourVariants'));
    }

    private function colourVariantsFor(Product $product)
    {
        if (! $product->colour_variant_group_id) {
            return collect();
        }

        $variants = Product::query()
            ->where('colour_variant_group_id', $product->colour_variant_group_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function (Product $variant) use ($product) {
                $colour = $this->productColour($variant);

                return [
                    'id' => $variant->id,
                    'colour' => $colour ?: $variant->name,
                    'url' => route('product.show', $variant->slug),
                    'is_current' => $variant->id === $product->id,
                    'in_stock' => $variant->stock > 0,
                    'swatch' => $this->colourSwatch($colour ?: ''),
                ];
            })
            ->values();

        return $variants->count() > 1 ? $variants : collect();
    }

    private function productColour(Product $product): ?string
    {
        $specs = $product->specs ?? [];

        foreach (['COLOUR', 'COLOR'] as $key) {
            $value = $specs[$key] ?? null;

            if ($value !== null && trim((string) $value) !== '') {
                return trim((string) $value);
            }
        }

        return null;
    }

    private function colourSwatch(string $colour): string
    {
        $colour = mb_strtolower($colour);

        $map = [
            'black' => '#111827',
            'white' => '#ffffff',
            'silver' => '#d1d5db',
            'gray' => '#6b7280',
            'grey' => '#6b7280',
            'blue' => '#2563eb',
            'green' => '#16a34a',
            'red' => '#dc2626',
            'pink' => '#ec4899',
            'purple' => '#7c3aed',
            'yellow' => '#facc15',
            'gold' => '#d4af37',
            'orange' => '#f97316',
            'brown' => '#92400e',
            'natural' => '#c7b8a4',
            'titanium' => '#b7b2aa',
            'graphite' => '#3f3f46',
            'midnight' => '#111827',
            'starlight' => '#f4eadc',
        ];

        foreach ($map as $keyword => $hex) {
            if (str_contains($colour, $keyword)) {
                return $hex;
            }
        }

        return '#e5e7eb';
    }

}
