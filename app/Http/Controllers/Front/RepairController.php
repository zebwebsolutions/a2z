<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Repair;
use Illuminate\Http\Request;

class RepairController extends Controller
{
    // Single Repair Form
    public function form()
    {
        $deviceTypes = [
            'phone'     => 'Phone',
            'tablet'    => 'Tablet',
            'laptop'    => 'Laptop',
            'earbuds'   => 'Earbuds / AirPods',
            'watch'     => 'Smart Watch',
            'other'     => 'Other (Specify)',
        ];

        return view('front.repair.form', compact('deviceTypes'));
    }

    // Save the Repair Order
    public function book(Request $request)
    {
        $data = $request->validate([
            'device_type'        => 'required|string',
            'device_custom'      => 'nullable|string',
            'customer_name'      => 'required|string|max:255',
            'customer_phone'     => 'required|string|max:255',
            'imei'               => 'nullable|string',
            'problem_description'=> 'required|string',
        ]);

        // If user selected "Other", override device_type
        $data['device_model'] = $data['device_type'] === 'other'
            ? $data['device_custom']
            : ucfirst($data['device_type']);

        $data['user_id'] = auth()->id() ?? 1;
        $data['store_id'] = 1;
        $data['total_cost'] = 0;
        $data['status'] = 'pending';

        Repair::create($data);

        return redirect()->route('repair.form')
            ->with('success', 'Your repair request has been submitted!');
    }
}
