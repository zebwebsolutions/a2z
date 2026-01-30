<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $search = $request->query('search');

        $query = Product::query()
            ->select([
                'id',
                'name',
                'barcode',
                'price',
                'stock',
            ]);

        // 🔍 Search by name OR barcode
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        // 🏬 Optional: store scoping (if products are store-based)
        if ($user->store_id) {
            $query->where('store_id', $user->store_id);
        }

        return response()->json(
            $query
                ->orderBy('name')
                ->paginate(20)
        );
    }
}