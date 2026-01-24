<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Repair;
use Illuminate\Http\Request;

class RepairController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        return Repair::where('store_id', $user->store_id)
            ->latest()
            ->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
            'device_model' => 'required|string|max:255',
            'imei' => 'nullable|string|max:50',
            'problem_description' => 'required|string',
            'total_cost' => 'nullable|numeric|min:0',
        ]);

        $user = $request->user();

        $repair = Repair::create([
            ...$data,
            'user_id' => $user->id,
            'store_id' => $user->store_id,
            'status' => 'pending',
        ]);

        return response()->json($repair, 201);
    }

    public function show(Repair $repair)
    {
        $this->authorizeRepair($repair);
        return $repair;
    }

    public function update(Request $request, Repair $repair)
    {
        $this->authorizeRepair($repair);

        $data = $request->validate([
            'status' => 'nullable|in:pending,in_progress,completed,delivered,cancelled',
            'total_cost' => 'nullable|numeric|min:0',
        ]);

        $repair->update($data);

        return $repair;
    }

    private function authorizeRepair(Repair $repair)
    {
        if ($repair->store_id !== auth()->user()->store_id) {
            abort(403);
        }
    }
}