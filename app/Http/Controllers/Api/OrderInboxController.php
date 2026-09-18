<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderRead;
use Illuminate\Http\Request;

class OrderInboxController extends Controller
{
    public function index(Request $request)
    {
        $orders = Order::where('store_id', $request->user()->store_id)->where('order_source', 'online');
        $latestId = (clone $orders)->max('id');
        $unread = $orders->whereNotIn('status', ['completed', 'cancelled', 'refunded'])
            ->whereDoesntHave('reads', fn ($query) => $query->where('user_id', $request->user()->id));

        return response()->json([
            'unread_count' => (clone $unread)->count(),
            'latest_order_id' => $latestId,
            'latest_unread' => $unread->latest('id')->first(['id', 'created_at']),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function read(Request $request, Order $order)
    {
        abort_unless((int) $order->store_id === (int) $request->user()->store_id, 403);
        OrderRead::firstOrCreate(['order_id' => $order->id, 'user_id' => $request->user()->id], ['read_at' => now()]);

        return response()->noContent();
    }
}
