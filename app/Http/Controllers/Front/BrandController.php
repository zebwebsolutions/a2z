<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    //
    public function show($slug)
    {
        $brand = Brand::where('slug', $slug)->firstOrFail();

        // Load brand products, etc.
        $products = $brand->products()->paginate(20);

        return view('front.brand', compact('brand', 'products'));
    }

}
