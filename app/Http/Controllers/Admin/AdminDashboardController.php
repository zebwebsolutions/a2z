<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Order;
use App\Models\Repair;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function index()
    {
        // 1️⃣ Top-level counts
        $stats = [
            'products'  => Product::count(),
            'repairs'   => Repair::count(),
            'salesmen'  => User::where('role', 'salesman')->count(),
            'stores'    => Store::count(),
        ];

        // 2️⃣ Top products (by total sales quantity)
        $topProducts = DB::table('order_items')
            ->select('product_id', DB::raw('SUM(quantity) as total_sold'))
            ->groupBy('product_id')
            ->orderByDesc('total_sold')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                $product = \App\Models\Product::find($item->product_id);
                return [
                    'name'  => $product?->name ?? 'Unknown',
                    'total' => $item->total_sold,
                ];
            });

        // 3️⃣ Top salesmen by repair count
        $topSalesmen = DB::table('repairs')
            ->select('user_id', DB::raw('COUNT(*) as total_repairs'))
            ->groupBy('user_id')
            ->orderByDesc('total_repairs')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                $user = \App\Models\User::find($item->user_id);
                return [
                    'name'  => $user?->name ?? 'Unknown',
                    'total' => $item->total_repairs,
                ];
            });

        // 4️⃣ Repair revenue by store
        $repairRevenueByStore = DB::table('repairs')
            ->join('stores', 'stores.id', '=', 'repairs.store_id')
            ->select('stores.name', DB::raw('SUM(total_cost) as total_revenue'))
            ->groupBy('stores.name')
            ->orderByDesc('total_revenue')
            ->get();

        // 5️⃣ Product revenue by store
        $productRevenueByStore = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->leftJoin('stores', 'stores.id', '=', 'products.store_id')
            ->where('orders.status', '!=', 'refunded')
            ->select(
                DB::raw("COALESCE(stores.name, 'Unassigned') as name"),
                DB::raw('SUM(order_items.price * order_items.quantity) as total_revenue')
            )
            ->groupBy('stores.name')
            ->orderByDesc('total_revenue')
            ->get();

        // 6️⃣ Repairs in the last 7 days
        $repairTrends = Repair::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total')
            )
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return view('admin.dashboard', compact(
            'stats',
            'topProducts',
            'topSalesmen',
            'repairRevenueByStore',
            'productRevenueByStore',
            'repairTrends'
        ));
    }
}
