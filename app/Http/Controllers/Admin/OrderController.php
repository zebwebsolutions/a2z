<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Store;
use App\Services\Products\ProductInventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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

                // Customer name / phone / type
                $q->orWhereHas('user', function ($u) use ($search) {
                    $u->where('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%")
                    ->orWhere('customer_type', 'like', "%{$search}%");
                });

                $q->orWhereHas('items.product', function ($p) use ($search) {
                    $p->where('name', 'like', "%{$search}%");
                });

                $q->orWhereHas('sparePartItems.sparePart', function ($p) use ($search) {
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
        $order = Order::with(['items.product', 'sparePartItems.sparePart'])->findOrFail($id);
        return view('admin.orders.show', compact('order'));
    }

    public function refund(Order $order, ProductInventoryService $productInventoryService)
    {
        $user = auth()->user();
        if (
            in_array($user->roleRelation?->name, ['salesman', 'technician'], true)
            && $order->store_id !== $user->store_id
        ) {
            abort(403);
        }

        if ($order->status !== 'completed') {
            return redirect()
                ->route('admin.orders.show', $order)
                ->with('error', 'Only completed orders can be refunded.');
        }

        DB::transaction(function () use ($order, $productInventoryService) {
            $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $this->restoreOrderStock($lockedOrder, $productInventoryService);

            $lockedOrder->update([
                'status' => 'refunded',
            ]);
        });

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('success', 'Order refunded successfully.');
    }

    /**
     * Update order status.
     */
    public function update(Request $request, $id, ProductInventoryService $productInventoryService)
    {
        $order = Order::findOrFail($id);
        $data = $request->validate([
            'status' => ['required', Rule::in([
                'pending',
                'processing',
                'shipped',
                'completed',
                'cancelled',
            ])],
        ]);

        if (
            in_array($order->status, ['cancelled', 'refunded'], true)
            && $data['status'] !== $order->status
        ) {
            return redirect()
                ->route('admin.orders.index')
                ->with('error', 'Cancelled or refunded orders cannot be reopened.');
        }

        DB::transaction(function () use ($order, $data, $productInventoryService) {
            $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($data['status'] === 'cancelled' && $lockedOrder->status !== 'cancelled') {
                $this->restoreOrderStock($lockedOrder, $productInventoryService);
            }

            $lockedOrder->update(['status' => $data['status']]);
        });

        return redirect()->route('admin.orders.index')->with('success', 'Order status updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id, ProductInventoryService $productInventoryService)
    {
        DB::transaction(function () use ($id, $productInventoryService) {
            $order = Order::whereKey($id)->lockForUpdate()->firstOrFail();

            if (!in_array($order->status, ['cancelled', 'refunded'], true)) {
                $this->restoreOrderStock($order, $productInventoryService);
            }

            $order->delete();
        });

        return redirect()->route('admin.orders.index')->with('success', 'Order deleted successfully.');
    }

    private function restoreOrderStock(
        Order $order,
        ProductInventoryService $productInventoryService
    ): void {
        $items = $order->items()->lockForUpdate()->get();

        foreach ($items as $item) {
            if ($productInventoryService->restoreUnits($item) === 0) {
                Product::whereKey($item->product_id)
                    ->lockForUpdate()
                    ->first()
                    ?->increment('stock', $item->quantity);
            }
        }

        $sparePartItems = $order->sparePartItems()->lockForUpdate()->get();

        foreach ($sparePartItems as $item) {
            $item->sparePart()
                ->lockForUpdate()
                ->first()
                ?->increment('stock_quantity', $item->quantity);
        }
    }
}
