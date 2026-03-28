<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Admin → all stores
        if ($user->is_admin) {
            $stores = Store::select('id', 'name')->get();
        } else {
            // Staff → only their store
            $stores = Store::where('id', $user->store_id)
                ->select('id', 'name')
                ->get();
        }

        return response()->json([
            'data' => $stores
        ]);
    }
}
