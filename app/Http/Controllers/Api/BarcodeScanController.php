<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\SparePart;
use Illuminate\Http\Request;

class BarcodeScanController extends Controller
{
    public function scan(string $barcode)
    {
        // $request->validate([
        //     'barcode' => 'required|string',
        // ]);

        //dd($barcode);

        $unit = ProductUnit::where(function ($query) use ($barcode) {
            $query->where('barcode', $barcode)
                ->orWhere('imei_1', $barcode)
                ->orWhere('imei_2', $barcode)
                ->orWhere('serial_number', $barcode);
        })
            ->where('status', 'available')
            ->with('product')
            ->first();

        if ($unit && $unit->product) {
            return response()->json([
                'type' => 'product',
                'id' => $unit->product->id,
                'name' => $unit->product->name,
                'price' => $unit->product->price,
                'stock' => $unit->product->stock,
                'matched_unit_id' => $unit->id,
            ]);
        }

        // Try product first
        $product = Product::where('barcode', $barcode)->first();
        if ($product) {
            return response()->json([
                'type' => 'product',
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'stock' => $product->stock,
            ]);
        }

        // Then spare part
        $sparePart = SparePart::where('barcode', $barcode)->first();
        if ($sparePart) {
            return response()->json([
                'type' => 'spare_part',
                'id' => $sparePart->id,
                'name' => $sparePart->name,
                'price' => $sparePart->selling_price,
                'stock' => $sparePart->stock_quantity,
            ]);
        }

        return response()->json([
            'message' => 'Barcode not found'
        ], 404);
    }
}
