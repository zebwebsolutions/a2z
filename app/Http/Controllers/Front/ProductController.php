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

        $products = $query->paginate(12);
        $stores = Store::all();
        $categories = Category::all();

        return view('front.products.index', compact('products', 'stores', 'categories'));
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
                'url' => route('brand.category', [
                    'category' => $parent->slug ?? '',
                    'brand' => $brand->slug
                ])
            ];
        }

        abort_unless($product->is_active, 404);

        $relatedProducts = Product::where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->where('is_active', true)
            ->take(4)
            ->get();

        return view('front.products.show', compact('product', 'breadcrumbItems', 'relatedProducts'));
    }

}
