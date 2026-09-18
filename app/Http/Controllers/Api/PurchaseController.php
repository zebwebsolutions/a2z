<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        return Purchase::query()
            ->with('product:id,name')
            ->when($request->user()->role !== 'admin', fn ($query) => $query->where('store_id', $request->user()->store_id))
            ->latest('id')->paginate(20);
    }

    public function idImage(Request $request, Purchase $purchase)
    {
        abort_unless($request->user()->role === 'admin' || (int) $request->user()->store_id === (int) $purchase->store_id, 403);
        abort_unless(Storage::disk('local')->exists($purchase->customer_id_image), 404);

        return Storage::disk('local')->response($purchase->customer_id_image, null, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
