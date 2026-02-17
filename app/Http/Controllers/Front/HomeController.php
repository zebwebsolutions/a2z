<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\HomeSection;

class HomeController extends Controller
{
    public function index()
    {
        $categories = Category::where('is_active', true)
        ->whereNull('parent_id')
        ->addSelect([
            'products_count' => Product::selectRaw('COUNT(*)')
                ->whereColumn('categories.id', 'products.category_id')
                ->where('products.is_active', true)
                ->orWhereIn('products.category_id', function ($query) {
                    $query->select('id')
                        ->from('categories as sub')
                        ->whereColumn('sub.parent_id', 'categories.id');
                })
        ])
        ->orderByDesc('products_count')
        ->take(8)
        ->get();

        $products = Product::where('is_active', true)
            ->take(8)
            ->get();

        // Load home sections (no need to eager-load products)
        $sections = HomeSection::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        foreach ($sections as $section) {
            // Fetch products sorted by pivot.order
            $section->orderedProducts = $section->products()
                ->orderBy('home_section_product.order')
                ->get();
        }

        return view('front.home', compact('categories', 'products', 'sections'));
    }
}
