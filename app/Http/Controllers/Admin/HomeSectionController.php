<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomeSection;
use App\Models\Product;
use Illuminate\Http\Request;

class HomeSectionController extends Controller
{
    public function index()
    {
        $sections = HomeSection::orderBy('sort_order')->get();
        return view('admin.home_sections.index', compact('sections'));
    }

    public function create()
    {
        $products = Product::orderBy('name')->get();
        return view('admin.home_sections.create', compact('products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'sort_order' => 'nullable|integer',
        ]);

        $section = HomeSection::create([
            'title' => $request->title,
            'is_active' => $request->is_active ?? 0,
            'sort_order' => $request->sort_order ?? 0,
        ]);

        if ($request->products) {
            $section->products()->sync($request->products);
        }

        return redirect()->route('admin.home-sections.index')
                         ->with('success', 'Section created successfully.');
    }

    public function edit(HomeSection $home_section)
    {
        $home_section->load('products');

        $selectedProducts = $home_section->products->map(function($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'image' => $product->image,
            ];
        })->values()->toArray();

        return view('admin.home_sections.edit', [
            'section' => $home_section,
            'products' => $selectedProducts,
        ]);
    }

    public function update(Request $request, HomeSection $home_section)
    {
        $request->validate([
            'title' => 'required',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
            'products' => 'nullable|array',
            'products.*' => 'integer|exists:products,id',
        ]);

        $home_section->update([
            'title' => $request->title,
            'is_active' => $request->is_active ?? 0,
            'sort_order' => $request->sort_order ?? 0,
        ]);

        $products = $request->input('products', []);

        $syncData = [];
        foreach ($products as $index => $productId) {
            $syncData[$productId] = ['order' => $index];
        }

        $home_section->products()->sync($syncData);

        return redirect()->route('admin.home-sections.index')
                         ->with('success', 'Section updated successfully.');
    }

    public function destroy(HomeSection $home_section)
    {
        $home_section->delete();
        return back()->with('success', 'Section deleted.');
    }
}
