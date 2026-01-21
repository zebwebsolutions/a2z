<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $data = $request->validate([
            'payment_method' => 'required|in:cash,card',
            'total' => 'required|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|integer',
            'items.*.type' => 'required|in:product,spare_part',
            'items.*.price' => 'required|numeric',
            'items.*.qty' => 'required|integer|min:1',
        ]);

        return DB::transaction(function () use ($data, $user) {

            $order = Order::create([
                'user_id' => $user->id,
                'payment_method' => $data['payment_method'],
                'total' => $data['total'],
                'status' => 'completed',
            ]);

            foreach ($data['items'] as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'item_id' => $item['id'],
                    'item_type' => $item['type'],
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
}