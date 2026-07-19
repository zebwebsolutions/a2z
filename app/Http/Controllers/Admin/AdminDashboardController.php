<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Repair;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function index(Request $request)
    {
        $rangeOptions = [
            'today' => 'Today',
            'last_7_days' => 'Last 7 Days',
            'last_30_days' => 'Last 30 Days',
            'this_month' => 'This Month',
            'all' => 'All Time',
        ];

        $range = array_key_exists($request->input('range'), $rangeOptions)
            ? $request->input('range')
            : 'last_30_days';

        $dateFrom = match ($range) {
            'today' => now()->startOfDay(),
            'last_7_days' => now()->subDays(7)->startOfDay(),
            'this_month' => now()->startOfMonth(),
            'all' => null,
            default => now()->subDays(30)->startOfDay(),
        };

        $user = auth()->user();
        $storeId = $user?->hasRole('admin') ? null : $user?->store_id;

        $applyDateRange = function ($query, string $column = 'created_at') use ($dateFrom) {
            if ($dateFrom) {
                $query->where($column, '>=', $dateFrom);
            }
        };

        $applyOnlineScope = function ($query) {
            $query->where(function ($source) {
                $source->where('orders.order_source', 'online')
                    ->orWhere(function ($legacy) {
                        $legacy->whereNull('orders.order_source')
                            ->whereNotNull('orders.customer_email');
                    });
            });
        };

        $activeOrderStatuses = ['pending', 'processing', 'shipped', 'completed'];
        $completedRepairStatuses = ['completed', 'delivered'];

        $productCountQuery = Product::query();
        $repairCountQuery = Repair::query();
        $salesmanCountQuery = User::query()->where('role', 'salesman');
        $storeCountQuery = Store::query();

        if ($storeId) {
            $productCountQuery->where('store_id', $storeId);
            $repairCountQuery->where('store_id', $storeId);
            $salesmanCountQuery->where('store_id', $storeId);
            $storeCountQuery->where('id', $storeId);
        }

        $lowStockQuery = Product::query()
            ->where('is_active', true)
            ->where('stock', '<=', 3);

        if ($storeId) {
            $lowStockQuery->where('store_id', $storeId);
        }

        $stats = [
            'products' => $productCountQuery->count(),
            'repairs' => $repairCountQuery->count(),
            'salesmen' => $salesmanCountQuery->count(),
            'stores' => $storeCountQuery->count(),
            'low_stock' => (clone $lowStockQuery)->count(),
        ];

        $onlineOrdersBase = Order::query()
            ->from('orders')
            ->whereIn('orders.status', $activeOrderStatuses);

        $applyOnlineScope($onlineOrdersBase);
        $applyDateRange($onlineOrdersBase, 'orders.created_at');

        if ($storeId) {
            $onlineOrdersBase->where('orders.store_id', $storeId);
        }

        $onlineOrderStats = [
            'received' => (clone $onlineOrdersBase)->count(),
            'pending' => (clone $onlineOrdersBase)->where('orders.status', 'pending')->count(),
            'completed' => (clone $onlineOrdersBase)->where('orders.status', 'completed')->count(),
            'gross_revenue' => (float) (clone $onlineOrdersBase)->sum('orders.total'),
            'completed_revenue' => (float) (clone $onlineOrdersBase)->where('orders.status', 'completed')->sum('orders.total'),
            'today' => (clone $onlineOrdersBase)->whereDate('orders.created_at', Carbon::today())->count(),
        ];

        $orderExceptionStats = [
            'cancelled' => Order::query()
                ->from('orders')
                ->tap($applyOnlineScope)
                ->when($storeId, fn ($query) => $query->where('orders.store_id', $storeId))
                ->tap(fn ($query) => $applyDateRange($query, 'orders.created_at'))
                ->where('orders.status', 'cancelled')
                ->count(),
            'refunded' => Order::query()
                ->from('orders')
                ->tap($applyOnlineScope)
                ->when($storeId, fn ($query) => $query->where('orders.store_id', $storeId))
                ->tap(fn ($query) => $applyDateRange($query, 'orders.created_at'))
                ->where('orders.status', 'refunded')
                ->count(),
        ];

        $topProducts = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->where('orders.status', 'completed')
            ->when($storeId, fn ($query) => $query->where('products.store_id', $storeId))
            ->tap(fn ($query) => $applyDateRange($query, 'orders.created_at'))
            ->select(
                'products.name',
                DB::raw('SUM(order_items.quantity) as total')
            )
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $topSalesmen = DB::table('repairs')
            ->join('users', 'users.id', '=', 'repairs.user_id')
            ->whereIn('repairs.status', $completedRepairStatuses)
            ->when($storeId, fn ($query) => $query->where('repairs.store_id', $storeId))
            ->tap(fn ($query) => $applyDateRange($query, 'repairs.created_at'))
            ->select(
                'users.name',
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $repairRevenueByStore = DB::table('repairs')
            ->join('stores', 'stores.id', '=', 'repairs.store_id')
            ->whereIn('repairs.status', $completedRepairStatuses)
            ->when($storeId, fn ($query) => $query->where('repairs.store_id', $storeId))
            ->tap(fn ($query) => $applyDateRange($query, 'repairs.created_at'))
            ->select('stores.name', DB::raw('SUM(repairs.total_cost) as total_revenue'))
            ->groupBy('stores.id', 'stores.name')
            ->orderByDesc('total_revenue')
            ->get();

        $productRevenueByStore = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->leftJoin('stores', 'stores.id', '=', 'products.store_id')
            ->where('orders.status', 'completed')
            ->when($storeId, fn ($query) => $query->where('products.store_id', $storeId))
            ->tap(fn ($query) => $applyDateRange($query, 'orders.created_at'))
            ->select(
                DB::raw("COALESCE(stores.name, 'Unassigned') as name"),
                DB::raw('SUM(order_items.price * order_items.quantity) as total_revenue')
            )
            ->groupByRaw("COALESCE(stores.name, 'Unassigned')")
            ->orderByDesc('total_revenue')
            ->get();

        $onlineOrderTrends = (clone $onlineOrdersBase)
            ->select(
                DB::raw('DATE(orders.created_at) as date'),
                DB::raw('COUNT(*) as total_orders'),
                DB::raw('SUM(orders.total) as total_revenue')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $repairTrends = Repair::query()
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total')
            )
            ->when($storeId, fn ($query) => $query->where('store_id', $storeId))
            ->tap(fn ($query) => $applyDateRange($query))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $recentOnlineOrders = (clone $onlineOrdersBase)
            ->latest('orders.created_at')
            ->limit(8)
            ->get([
                'orders.id',
                'orders.customer_name',
                'orders.customer_phone',
                'orders.total',
                'orders.status',
                'orders.created_at',
            ]);

        $lowStockProducts = (clone $lowStockQuery)
            ->with('store:id,name')
            ->orderBy('stock')
            ->orderBy('name')
            ->limit(8)
            ->get(['id', 'store_id', 'name', 'stock']);

        return view('admin.dashboard', compact(
            'stats',
            'range',
            'rangeOptions',
            'topProducts',
            'topSalesmen',
            'repairRevenueByStore',
            'productRevenueByStore',
            'onlineOrderStats',
            'orderExceptionStats',
            'onlineOrderTrends',
            'repairTrends',
            'recentOnlineOrders',
            'lowStockProducts'
        ));
    }
}
