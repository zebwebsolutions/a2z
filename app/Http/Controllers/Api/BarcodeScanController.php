<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\SparePart;
use Illuminate\Http\Request;

class BarcodeScanController extends Controller
{
    public function scan(Request $request, ?string $identifier = null)
    {
        $identifier = trim((string) ($identifier
            ?? $request->input('identifier')
            ?? $request->input('barcode')
            ?? $request->input('imei')
            ?? $request->input('serial_number')));

        if ($identifier === '') {
            return response()->json([
                'message' => 'Identifier is required',
            ], 422);
        }

        $unit = ProductUnit::where(function ($query) use ($identifier) {
            $query->where('barcode', $identifier)
                ->orWhere('imei_1', $identifier)
                ->orWhere('imei_2', $identifier)
                ->orWhere('serial_number', $identifier);
        })
            ->where('status', 'available')
            ->with('product')
            ->first();

        if ($unit && $unit->product) {
            $matchedBy = match ($identifier) {
                $unit->barcode => 'barcode',
                $unit->imei_1 => 'imei_1',
                $unit->imei_2 => 'imei_2',
                $unit->serial_number => 'serial_number',
                default => 'identifier',
            };

            return response()->json([
                'type' => 'product',
                'id' => $unit->product->id,
                'name' => $unit->product->name,
                'price' => $unit->product->price,
                'stock' => $unit->product->stock,
                'matched_unit_id' => $unit->id,
                'matched_by' => $matchedBy,
                'matched_value' => $identifier,
            ]);
        }

        // Try product first
        $product = Product::where('barcode', $identifier)->first();
        if ($product) {
            return response()->json([
                'type' => 'product',
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'stock' => $product->stock,
                'matched_by' => 'barcode',
                'matched_value' => $identifier,
            ]);
        }

        // Then spare part
        $sparePart = SparePart::where('barcode', $identifier)->first();
        if ($sparePart) {
            return response()->json([
                'type' => 'spare_part',
                'id' => $sparePart->id,
                'name' => $sparePart->name,
                'price' => $sparePart->selling_price,
                'stock' => $sparePart->stock_quantity,
                'matched_by' => 'barcode',
                'matched_value' => $identifier,
            ]);
        }

        return response()->json([
            'message' => 'Identifier not found',
        ], 404);
    }
}
