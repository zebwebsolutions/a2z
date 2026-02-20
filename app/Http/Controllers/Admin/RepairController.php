<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Repair;
use App\Models\Store;
use App\Models\RepairPart;
use Illuminate\Support\Facades\Auth;

class RepairController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $repairs = Repair::with('store', 'salesman')->latest()->paginate(10);
        return view('admin.repairs.index', compact('repairs'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $stores = Store::all();
        return view('admin.repairs.create', compact('stores'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'store_id' => 'required|exists:stores,id',
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
            'device_model' => 'required|string|max:255',
            'imei' => 'nullable|string|max:50',
            'problem_description' => 'required|string',
            'warranty' => 'nullable|string|max:100',
            'total_cost' => 'required|numeric|min:0',
            'status' => 'required|string',
        ]);

        $data['user_id'] = Auth::id();
        $repair = Repair::create($data);

        if($request->has('parts')) {
            foreach ($request->input('parts') as $part) {
                $repair->parts()->create([
                    'part_name' => $part['part_name'],
                    'cost' => $part['cost'],
                    'quantity' => $part['quantity'],
                ]);
            }
        }
        return redirect()->route('admin.repairs.index')->with('success', 'Repair created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Repair $repair)
    {
        $stores = Store::all();
        $repair->load('parts');
        return view('admin.repairs.edit', compact('repair', 'stores'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Repair $repair)
    {
        $data = $request->validate([
            'store_id' => 'required|exists:stores,id',
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
            'device_model' => 'required|string|max:255',
            'imei' => 'nullable|string|max:255',
            'problem_description' => 'required|string',
            'warranty' => 'nullable|string|max:100',
            'total_cost' => 'required|numeric|min:0',
            'status' => 'required|string',
        ]);

        $repair->update($data);

        $repair->parts()->delete();
        if ($request->has('parts')) {
            foreach ($request->parts as $part) {
                $repair->parts()->create([
                    'part_name' => $part['part_name'],
                    'quantity' => $part['quantity'] ?? 1,
                    'cost' => $part['cost'] ?? 0,
                ]);
            }
        }

        return redirect()->route('admin.repairs.index')->with('success', 'Repair updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Repair $repair)
    {
        $repair->delete();
        return redirect()->route('admin.repairs.index')->with('success', 'Repair deleted successfully.');
    }
}
