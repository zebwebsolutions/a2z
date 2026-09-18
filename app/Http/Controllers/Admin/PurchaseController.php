<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Services\PurchaseSearch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PurchaseController extends Controller
{
    public function index(Request $request, PurchaseSearch $search)
    {
        $purchases = $search->query($request)->with('product:id,name')->latest('id')->paginate(20)->withQueryString();

        return view('admin.purchases.index', compact('purchases'));
    }

    public function show(Request $request, Purchase $purchase)
    {
        $this->authorizePurchase($request, $purchase);
        $purchase->load('product');

        return view('admin.purchases.show', compact('purchase'));
    }

    public function idImage(Request $request, Purchase $purchase)
    {
        $this->authorizePurchase($request, $purchase);
        abort_unless(Storage::disk('local')->exists($purchase->customer_id_image), 404);

        return Storage::disk('local')->response($purchase->customer_id_image, null, [
            'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function authorizePurchase(Request $request, Purchase $purchase): void
    {
        abort_unless($request->user()->isAdmin() || (int) $request->user()->store_id === (int) $purchase->store_id, 403);
    }
}
