<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\SparePart;
use App\Models\SparePartSale;

class SparePartScanController extends Controller
{

    public function scan(Request $request)
    {
        $request->validate([
            'barcode' => 'required|string'
        ]);

        $part = SparePart::where('barcode', $request->barcode)->firstOrFail();

        return response()->json([
            'id' => $part->id,
            'name' => $part->name,
            'price' => $part->selling_price,
            'stock' => $part->stock_quantity
        ]);
    }

    public function sell(Request $request)
    {
        $request->validate([
            'spare_part_id' => 'required|exists:spare_parts,id',
            'quantity' => 'required|integer|min:1'
        ]);

        $part = SparePart::findOrFail($request->spare_part_id);

        if ($part->stock_quantity < $request->quantity) {
            abort(400, 'Insufficient stock');
        }

        $total = $part->selling_price * $request->quantity;

        SparePartSale::create([
            'spare_part_id' => $part->id,
            'salesman_id' => auth()->id(),
            'quantity' => $request->quantity,
            'unit_price' => $part->selling_price,
            'total_price' => $total,
            'sold_at' => now(),
        ]);

        $part->decrement('stock_quantity', $request->quantity);

        return response()->json(['success' => true]);
    }

}
