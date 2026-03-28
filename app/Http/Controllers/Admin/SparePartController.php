<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SparePart;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SparePartController extends Controller
{
    public function index(Request $request)
    {
        $query = SparePart::query();

        // Search by name or barcode
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                ->orWhere('barcode', 'like', '%' . $request->search . '%');
            });
        }

        // Stock filter
        if ($request->filled('stock')) {
            if ($request->stock === 'in') {
                $query->where('stock_quantity', '>', 5);
            } elseif ($request->stock === 'low') {
                $query->whereBetween('stock_quantity', [1, 5]);
            } elseif ($request->stock === 'out') {
                $query->where('stock_quantity', '<=', 0);
            }
        }

        // Price range (selling price)
        if ($request->filled('price_min')) {
            $query->where('selling_price', '>=', $request->price_min);
        }

        if ($request->filled('price_max')) {
            $query->where('selling_price', '<=', $request->price_max);
        }

        $spareParts = $query->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.spare-parts.index', compact('spareParts'));
    }


    public function create()
    {
        $barcode = 'SP-' . strtoupper(Str::random(8));
        return view('admin.spare-parts.create', compact('barcode'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'selling_price' => 'required|numeric',
            'cost_price' => 'required|numeric',
            'stock_quantity' => 'required|integer|min:0',
        ]);

        SparePart::create([
            'name' => $request->name,
            'sku' => $request->sku,
            'barcode' => $request->barcode,
            'barcode_type' => 'code128',
            'cost_price' => $request->cost_price,
            'selling_price' => $request->selling_price,
            'stock_quantity' => $request->stock_quantity,
        ]);

        return redirect()->route('admin.spare-parts.index')
            ->with('success', 'Spare part added successfully');
    }

    public function edit(SparePart $sparePart)
    {
        return view('admin.spare-parts.edit', compact('sparePart'));
    }

    public function update(Request $request, SparePart $sparePart)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'selling_price' => 'required|numeric',
            'stock_quantity' => 'required|integer|min:0',
        ]);

        $sparePart->update($request->only([
            'name',
            'sku',
            'cost_price',
            'selling_price',
            'stock_quantity'
        ]));

        return back()->with('success', 'Spare part updated');
    }

    public function destroy(SparePart $sparePart)
    {
        $sparePart->delete();
        return back()->with('success', 'Spare part deleted');
    }

    public function barcode(SparePart $sparePart)
    {
        return view('admin.spare-parts.barcode', compact('sparePart'));
    }

}
