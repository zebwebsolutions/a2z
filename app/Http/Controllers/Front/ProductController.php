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
            $query->whereRamSpecification($request->input('ram'));
        }

        if ($request->filled('storage')) {
            $query->whereStorageSpecification($request->input('storage'));
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
                $storage = Product::storageSpecificationValue($specs);
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

        $ramOptions = $ramOptions
            ->map(fn ($value) => Product::normalizeSpecificationValue((string) $value, 'RAM'))
            ->unique(fn ($value) => Product::specificationComparisonValue((string) $value))
            ->sort(fn ($left, $right) => strnatcasecmp((string) $left, (string) $right))
            ->values();
        $storageOptions = $storageOptions
            ->map(fn ($value) => Product::normalizeSpecificationValue((string) $value, 'STORAGE'))
            ->unique(fn ($value) => Product::specificationComparisonValue((string) $value))
            ->sort(fn ($left, $right) => strnatcasecmp((string) $left, (string) $right))
            ->values();
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

        [$colourVariants, $storageVariants] = $this->variantOptionsFor($product);

        return view('front.products.show', compact('product', 'breadcrumbItems', 'relatedProducts', 'colourVariants', 'storageVariants'));
    }

    private function variantOptionsFor(Product $product): array
    {
        $variants = $this->connectedVariantProducts($product);
        $currentColour = $this->productColour($product);
        $currentStorage = $this->productStorage($product);

        $colourLabels = $variants
            ->map(fn (Product $variant) => $this->productColour($variant))
            ->filter()
            ->unique(fn (string $colour) => $this->variantValueKey($colour))
            ->sort(fn ($a, $b) => strnatcasecmp($a, $b))
            ->values();

        $storageLabels = $variants
            ->map(fn (Product $variant) => $this->productStorage($variant))
            ->filter()
            ->unique(fn (string $storage) => $this->variantValueKey($storage))
            ->sort(fn ($a, $b) => strnatcasecmp($a, $b))
            ->values();

        $shouldMatchStorage = $storageLabels->count() > 1 && ! empty($currentStorage);
        $shouldMatchColour = $colourLabels->count() > 1 && ! empty($currentColour);

        $colourOptions = $colourLabels
            ->map(function (string $colour) use ($variants, $product, $currentStorage, $shouldMatchStorage) {
                $exactTarget = $this->firstMatchingVariant($variants, fn (Product $variant) => (
                    $this->variantValuesMatch($this->productColour($variant), $colour)
                    && (! $shouldMatchStorage || $this->variantValuesMatch($this->productStorage($variant), $currentStorage))
                ), $product->id);
                $fallbackTarget = $this->firstMatchingVariant($variants, fn (Product $variant) => (
                    $this->variantValuesMatch($this->productColour($variant), $colour)
                ), $product->id);
                $target = $exactTarget ?: $fallbackTarget;
                $targetStorage = $target ? $this->productStorage($target) : null;

                return [
                    'colour' => $colour,
                    'url' => $target ? route('product.show', $target->slug) : null,
                    'is_current' => $this->variantValuesMatch($this->productColour($product), $colour),
                    'is_available' => (bool) $target,
                    'is_exact_match' => (bool) $exactTarget,
                    'in_stock' => $target ? $target->stock > 0 : false,
                    'fallback_label' => $targetStorage,
                    'swatch' => $this->colourSwatch($colour),
                ];
            })
            ->values();

        $storageOptions = $storageLabels
            ->map(function (string $storage) use ($variants, $product, $currentColour, $shouldMatchColour) {
                $exactTarget = $this->firstMatchingVariant($variants, fn (Product $variant) => (
                    $this->variantValuesMatch($this->productStorage($variant), $storage)
                    && (! $shouldMatchColour || $this->variantValuesMatch($this->productColour($variant), $currentColour))
                ), $product->id);
                $fallbackTarget = $this->firstMatchingVariant($variants, fn (Product $variant) => (
                    $this->variantValuesMatch($this->productStorage($variant), $storage)
                ), $product->id);
                $target = $exactTarget ?: $fallbackTarget;
                $targetColour = $target ? $this->productColour($target) : null;

                return [
                    'storage' => $storage,
                    'url' => $target ? route('product.show', $target->slug) : null,
                    'is_current' => $this->variantValuesMatch($this->productStorage($product), $storage),
                    'is_available' => (bool) $target,
                    'is_exact_match' => (bool) $exactTarget,
                    'in_stock' => $target ? $target->stock > 0 : false,
                    'fallback_label' => $targetColour,
                ];
            })
            ->values();

        return [
            $colourOptions->count() > 1 ? $colourOptions : collect(),
            $storageOptions->count() > 1 ? $storageOptions : collect(),
        ];
    }

    private function connectedVariantProducts(Product $product)
    {
        $products = collect([$product]);
        $seenProductIds = collect([$product->id]);
        $colourGroupIds = collect([$product->colour_variant_group_id])->filter()->values();
        $storageGroupIds = collect([$product->storage_variant_group_id])->filter()->values();

        if ($colourGroupIds->isEmpty() && $storageGroupIds->isEmpty()) {
            return $products;
        }

        do {
            $foundProducts = Product::query()
                ->where('is_active', true)
                ->whereNotIn('id', $seenProductIds)
                ->where(function ($query) use ($colourGroupIds, $storageGroupIds) {
                    if ($colourGroupIds->isNotEmpty()) {
                        $query->orWhereIn('colour_variant_group_id', $colourGroupIds);
                    }

                    if ($storageGroupIds->isNotEmpty()) {
                        $query->orWhereIn('storage_variant_group_id', $storageGroupIds);
                    }
                })
                ->get();

            $products = $products->merge($foundProducts);
            $seenProductIds = $products->pluck('id')->unique()->values();
            $nextColourGroupIds = $products->pluck('colour_variant_group_id')->filter()->unique()->values();
            $nextStorageGroupIds = $products->pluck('storage_variant_group_id')->filter()->unique()->values();
            $hasNewGroups = $nextColourGroupIds->diff($colourGroupIds)->isNotEmpty()
                || $nextStorageGroupIds->diff($storageGroupIds)->isNotEmpty();
            $colourGroupIds = $nextColourGroupIds;
            $storageGroupIds = $nextStorageGroupIds;
        } while ($foundProducts->isNotEmpty() && $hasNewGroups);

        return $products->unique('id')->values();
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

    private function productStorage(Product $product): ?string
    {
        $specs = $product->specs ?? [];

        foreach (['STORAGE CAPACITY', 'STORAGE'] as $key) {
            $value = $specs[$key] ?? null;

            if ($value !== null && trim((string) $value) !== '') {
                return trim((string) $value);
            }
        }

        return null;
    }

    private function firstMatchingVariant($variants, callable $matches, int $currentProductId): ?Product
    {
        $matchingVariants = $variants->filter($matches);

        return $matchingVariants->firstWhere('id', $currentProductId)
            ?: $matchingVariants->sortByDesc(fn (Product $variant) => $variant->stock > 0)->first();
    }

    private function variantValuesMatch(?string $first, ?string $second): bool
    {
        return $this->variantValueKey($first) !== ''
            && $this->variantValueKey($first) === $this->variantValueKey($second);
    }

    private function variantValueKey(?string $value): string
    {
        return preg_replace('/[^\pL\pN]+/u', '', mb_strtolower(trim((string) $value))) ?? '';
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
