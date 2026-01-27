<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\support\Carbon;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Order::query()
            ->with('items')
            ->where('store_id', $user->store_id);

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                ->orWhere('customer_phone', 'like', "%{$search}%");
                if (is_numeric($search)) {
                    $q->orWhere('id', (int) $search);
                }
            });
        }

        match ($request->query('date')) {
            'today' => $query->whereDate('created_at', Carbon::today()),
            '7days' => $query->where('created_at', '>=', Carbon::now()->subDays(7)),
            'month' => $query->whereMonth('created_at', Carbon::now()->month)
                            ->whereYear('created_at', Carbon::now()->year),
            default => null,
        };

        if ($payment = $request->query('payment')) {
            $query->where('payment_method', $payment);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $orders = $query
            ->latest()
            ->paginate(20);

        return response()->json($orders);
    }

    public function store(Request $request)
    {
        $user = $request->user(); 

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $data = $request->validate([
            'payment_method' => 'required|in:cash,card',
            'total' => 'required|numeric|min:0',

            // order items
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|integer',
            'items.*.price' => 'required|numeric',
            'items.*.qty' => 'required|integer|min:1',

            // OPTIONAL customer fields
            'customer_name' => 'nullable|string|max:255',
            'customer_email' => 'nullable|email|max:255',
            'customer_phone' => 'nullable|string|max:255',
            'customer_address' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($data, $user) {

            $order = Order::create([
                'user_id' => $user->id,
                'store_id' => $user->store_id ?? null,
                'customer_name' => $data['customer_name'] ?? null,
                'customer_email' => $data['customer_email'] ?? null,
                'customer_phone' => $data['customer_phone'] ?? null,
                'customer_address' => $data['customer_address'] ?? null,
                'total' => $data['total'],
                'status' => 'completed',
            ]);

            foreach ($data['items'] as $item) {
                $product = Product::where('id', $item['id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($product->stock < $item['qty']) {
                    abort(400, "Insufficient stock for {$product->name}");
                }

                $product->decrement('stock', $item['qty']);

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['id'],
                    'price' => $item['price'],
                    'quantity' => $item['qty'],
                ]);
            }

            return response()->json([
                'success' => true,
                'order_id' => $order->id,
            ]);
        });
    }

    public function show(Order $order)
    {
        // Optional: restrict to same store
        if ($order->store_id !== auth()->user()->store_id) {
            abort(403);
        }

        return response()->json(
            $order->load([
                'items.product:id,name',
                'user:id,name',
            ])
        );
    }

    public function refund(Order $order)
    {
        if (!in_array($order->status, ['completed'])) {
            return response()->json([
                'message' => 'Order cannot be refunded'
            ], 400);
        }

        DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                // restore stock
                $item->product->increment('stock', $item->quantity);
            }

            $order->update([
                'status' => 'refunded',
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Order refunded successfully',
        ]);
    }

}