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

        $search = $request->query('search');
        $status = $request->query('status');

        $query = Repair::query()
            ->with('items')
            ->where('store_id', $user->store_id);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('customer_name', 'like', "%$search%")
                ->orWhere('customer_phone', 'like', "%$search%")
                ->orWhere('device_model', 'like', "%$search%");
            });
        }

        if ($status) {
            $query->where('status', $status);
        }


        $repairs = $query->latest()->paginate(20);

        return response()->json($repairs);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',

            'device_model' => 'required|string|max:255',
            'imei' => 'nullable|string|max:50',

            'problem_description' => 'required|string',

            'total_cost' => 'required|numeric|min:0',
            'status' => 'required|in:pending,in_progress,completed,delivered,cancelled',

            'parts' => 'nullable|array',
            'parts.*.part_name' => 'required_with:parts|string|max:255',
            'parts.*.quantity' => 'required_with:parts|integer|min:1',
            'parts.*.cost' => 'required_with:parts|numeric|min:0',
        ]);

        $user = $request->user();

        // Create repair
        $repair = Repair::create([
            'store_id' => $user->store_id,
            'user_id' => $user->id,

            'customer_name' => $data['customer_name'],
            'customer_phone' => $data['customer_phone'],

            'device_model' => $data['device_model'],
            'imei' => $data['imei'] ?? null,

            'problem_description' => $data['problem_description'],
            'total_cost' => $data['total_cost'],
            'status' => $data['status'],
        ]);

        // Save parts (if any)
        if (!empty($data['parts'])) {
            foreach ($data['parts'] as $part) {
                $repair->parts()->create([
                    'part_name' => $part['part_name'],
                    'quantity' => $part['quantity'],
                    'cost' => $part['cost'],
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'repair' => $repair->load('parts'),
        ], 201);
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