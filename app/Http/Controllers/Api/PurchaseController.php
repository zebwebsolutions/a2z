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
        $query = app(\App\Services\PurchaseSearch::class)->query($request);
        return $query->with('product:id,name')->latest('id')->paginate(20);
    }

    public function customers(Request $request)
    {
        $data = $request->validate(['phone' => ['required', 'string', 'max:30', 'regex:/^[+0-9()\\s-]+$/']]);
        $phone = preg_replace('/\D/', '', $data['phone']);
        if (str_starts_with($phone, '00')) {
            $phone = substr($phone, 2);
        }
        if (strlen($phone) < 3) {
            return response()->json([]);
        }
        $ids = Purchase::query()
            ->when($request->user()->role !== 'admin', fn ($query) => $query->where('store_id', $request->user()->store_id))
            ->where('customer_phone_normalized', 'like', '%'.$phone.'%')
            ->selectRaw('MAX(id) as id')->groupBy('customer_phone_normalized');

        return Purchase::whereIn('id', $ids)->latest('id')->limit(10)
            ->get(['id', 'customer_name', 'customer_phone']);
    }

    public function idImage(Request $request, Purchase $purchase)
    {
        abort_unless($request->user()->role === 'admin' || (int) $request->user()->store_id === (int) $purchase->store_id, 403);
        abort_unless(Storage::disk('local')->exists($purchase->customer_id_image), 404);

        return Storage::disk('local')->response($purchase->customer_id_image, null, [
            'Cache-Control' => 'private, no-store',
            'Content-Type' => Storage::disk('local')->mimeType($purchase->customer_id_image),
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function show(Request $request, Purchase $purchase)
    {
        abort_unless($request->user()->role === 'admin' || (int) $request->user()->store_id === (int) $purchase->store_id, 403);

        return $purchase->load('product:id,name');
    }
}
