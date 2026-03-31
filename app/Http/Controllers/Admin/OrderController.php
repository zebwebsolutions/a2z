<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Store;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Order::with(['store', 'user']);

        $user = auth()->user();
        if ($user->roleRelation?->name === 'salesman' || $user->roleRelation?->name === 'technician') {
            $query->where('store_id', $user->store_id);
        }

        // Search (order id OR customer fields)
        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {

                // Order ID (numeric only)
                if (is_numeric($search)) {
                    $q->orWhere('id', $search);
                }

                // Customer name / email / phone
                $q->orWhereHas('user', function ($u) use ($search) {
                    $u->where('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_email', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%");
                });

                $q->orWhereHas('items.product', function ($p) use ($search) {
                    $p->where('name', 'like', "%{$search}%");
                });

            });
        }


        // Store filter
        if ($request->filled('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Total range
        if ($request->filled('total_min')) {
            $query->where('total', '>=', $request->total_min);
        }

        if ($request->filled('total_max')) {
            $query->where('total', '<=', $request->total_max);
        }

        // Date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $orders = $query->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.orders.index', [
            'orders' => $orders,
            'stores' => Store::orderBy('name')->get(),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $order = Order::with('items.product')->findOrFail($id);
        return view('admin.orders.show', compact('order'));
    }

    /**
     * Update order status.
     */
    public function update(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        $request->validate([
            'status' => 'required|string|max:50',
        ]);

        $order->update(['status' => $request->status]);

        return redirect()->route('admin.orders.index')->with('success', 'Order status updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $order = Order::findOrFail($id);
        $order->delete();

        return redirect()->route('admin.orders.index')->with('success', 'Order deleted successfully.');
    }
}
