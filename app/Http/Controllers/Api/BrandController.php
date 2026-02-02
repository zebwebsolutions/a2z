<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Brand;

class BrandController extends Controller
{
    public function index()
    {
        // return response()->json([
        //     'data' => Brand::select('id', 'name')
        //         ->orderBy('name')
        //         ->get()
        // ]);
        return response()->json('brands will be listed here once the route is created');
    }
}
