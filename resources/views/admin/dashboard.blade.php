@extends('admin.layouts.app')

@section('content')
<div class="container mx-auto py-8">
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h1 class="text-2xl font-bold">A2Z Analytics Dashboard</h1>
            <p class="mt-1 text-sm text-gray-500">
                Revenue uses completed orders/repairs only. Online received orders exclude cancelled and refunded orders.
            </p>
        </div>

        <form method="GET" action="{{ route('admin.dashboard') }}" class="flex items-center gap-2">
            <label for="range" class="text-sm font-medium text-gray-600">Date Range</label>
            <select
                id="range"
                name="range"
                onchange="this.form.submit()"
                class="rounded border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
            >
                @foreach($rangeOptions as $value => $label)
                    <option value="{{ $value }}" @selected($range === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </form>
    </div>

    {{-- Summary Cards --}}
    <div class="mb-8 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded bg-white p-4 shadow">
            <h2 class="text-sm text-gray-500">Online Orders Received</h2>
            <p class="mt-2 text-3xl font-bold">{{ $onlineOrderStats['received'] }}</p>
            <p class="mt-1 text-sm text-gray-500">Today: {{ $onlineOrderStats['today'] }}</p>
        </div>

        <div class="rounded bg-white p-4 shadow">
            <h2 class="text-sm text-gray-500">Online Pending</h2>
            <p class="mt-2 text-3xl font-bold text-amber-600">{{ $onlineOrderStats['pending'] }}</p>
            <p class="mt-1 text-sm text-gray-500">Needs follow-up</p>
        </div>

        <div class="rounded bg-white p-4 shadow">
            <h2 class="text-sm text-gray-500">Completed Online Revenue</h2>
            <p class="mt-2 text-3xl font-bold">KWD {{ number_format($onlineOrderStats['completed_revenue'], 2) }}</p>
            <p class="mt-1 text-sm text-gray-500">{{ $onlineOrderStats['completed'] }} completed online orders</p>
        </div>

        <div class="rounded bg-white p-4 shadow">
            <h2 class="text-sm text-gray-500">Online Gross Received</h2>
            <p class="mt-2 text-3xl font-bold">KWD {{ number_format($onlineOrderStats['gross_revenue'], 2) }}</p>
            <p class="mt-1 text-sm text-gray-500">Includes pending/processing/shipped/completed</p>
        </div>

        <div class="rounded bg-white p-4 shadow">
            <h2 class="text-sm text-gray-500">Products</h2>
            <p class="mt-2 text-3xl font-bold">{{ $stats['products'] }}</p>
            <p class="mt-1 text-sm text-gray-500">Active and inactive</p>
        </div>

        <div class="rounded bg-white p-4 shadow">
            <h2 class="text-sm text-gray-500">Low Stock Products</h2>
            <p class="mt-2 text-3xl font-bold text-red-600">{{ $stats['low_stock'] }}</p>
            <p class="mt-1 text-sm text-gray-500">Stock is 3 or lower</p>
        </div>

        <div class="rounded bg-white p-4 shadow">
            <h2 class="text-sm text-gray-500">Repairs</h2>
            <p class="mt-2 text-3xl font-bold">{{ $stats['repairs'] }}</p>
            <p class="mt-1 text-sm text-gray-500">All repair records</p>
        </div>

        <div class="rounded bg-white p-4 shadow">
            <h2 class="text-sm text-gray-500">Cancelled / Refunded Online</h2>
            <p class="mt-2 text-3xl font-bold">{{ $orderExceptionStats['cancelled'] + $orderExceptionStats['refunded'] }}</p>
            <p class="mt-1 text-sm text-gray-500">
                {{ $orderExceptionStats['cancelled'] }} cancelled, {{ $orderExceptionStats['refunded'] }} refunded
            </p>
        </div>
    </div>

    {{-- Charts --}}
    <div class="grid grid-cols-1 gap-8 xl:grid-cols-2">
        <div class="rounded bg-white p-4 shadow">
            <h2 class="mb-4 text-lg font-semibold">Online Store Orders</h2>
            <canvas id="onlineOrderTrendChart"></canvas>
        </div>

        <div class="rounded bg-white p-4 shadow">
            <h2 class="mb-4 text-lg font-semibold">Product Revenue by Store</h2>
            <canvas id="productRevenueByStoreChart"></canvas>
        </div>

        <div class="rounded bg-white p-4 shadow">
            <h2 class="mb-4 text-lg font-semibold">Repair Revenue by Store</h2>
            <canvas id="repairRevenueByStoreChart"></canvas>
        </div>

        <div class="rounded bg-white p-4 shadow">
            <h2 class="mb-4 text-lg font-semibold">Top Products Sold</h2>
            <canvas id="topProductsChart"></canvas>
        </div>

        <div class="rounded bg-white p-4 shadow">
            <h2 class="mb-4 text-lg font-semibold">Top Technicians / Salesmen by Completed Repairs</h2>
            <canvas id="topSalesmenChart"></canvas>
        </div>

        <div class="rounded bg-white p-4 shadow">
            <h2 class="mb-4 text-lg font-semibold">Repairs Created</h2>
            <canvas id="repairTrendChart"></canvas>
        </div>
    </div>

    {{-- Action Tables --}}
    <div class="mt-8 grid grid-cols-1 gap-8 xl:grid-cols-2">
        <div class="rounded bg-white p-4 shadow">
            <h2 class="mb-4 text-lg font-semibold">Recent Online Orders</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b bg-gray-50 text-gray-600">
                            <th class="p-3">Order</th>
                            <th class="p-3">Customer</th>
                            <th class="p-3">Status</th>
                            <th class="p-3">Total</th>
                            <th class="p-3">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentOnlineOrders as $order)
                            <tr class="border-b">
                                <td class="p-3">
                                    <a href="{{ route('admin.orders.show', $order->id) }}" class="font-semibold text-blue-600 hover:underline">
                                        #{{ $order->id }}
                                    </a>
                                </td>
                                <td class="p-3">
                                    <div>{{ $order->customer_name ?? 'Guest' }}</div>
                                    <div class="text-xs text-gray-500">{{ $order->customer_phone ?? '-' }}</div>
                                </td>
                                <td class="p-3 capitalize">{{ $order->status }}</td>
                                <td class="p-3">KWD {{ number_format($order->total, 2) }}</td>
                                <td class="p-3">{{ $order->created_at->format('d M, H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-4 text-center text-gray-500">No online orders for this range.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded bg-white p-4 shadow">
            <h2 class="mb-4 text-lg font-semibold">Low Stock Products</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b bg-gray-50 text-gray-600">
                            <th class="p-3">Product</th>
                            <th class="p-3">Store</th>
                            <th class="p-3">Stock</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($lowStockProducts as $product)
                            <tr class="border-b">
                                <td class="p-3">{{ $product->name }}</td>
                                <td class="p-3">{{ $product->store?->name ?? 'Unassigned' }}</td>
                                <td class="p-3">
                                    <span class="rounded bg-red-50 px-2 py-1 font-semibold text-red-700">
                                        {{ $product->stock }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="p-4 text-center text-gray-500">No low stock products.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
const chartColors = [
    'rgba(37, 99, 235, 0.75)',
    'rgba(34, 197, 94, 0.75)',
    'rgba(245, 158, 11, 0.75)',
    'rgba(239, 68, 68, 0.75)',
    'rgba(139, 92, 246, 0.75)',
    'rgba(14, 165, 233, 0.75)',
    'rgba(100, 116, 139, 0.75)',
];

const chartBorderColors = chartColors.map(color => color.replace('0.75', '0.95'));

const topProductsData = {
    labels: {!! json_encode($topProducts->pluck('name')) !!},
    datasets: [{
        label: 'Completed Units Sold',
        data: {!! json_encode($topProducts->pluck('total')) !!},
        borderWidth: 1,
        backgroundColor: chartColors[0],
    }]
};

const topSalesmenData = {
    labels: {!! json_encode($topSalesmen->pluck('name')) !!},
    datasets: [{
        label: 'Completed Repairs',
        data: {!! json_encode($topSalesmen->pluck('total')) !!},
        borderWidth: 1,
        backgroundColor: chartColors[4],
    }]
};

const repairRevenueByStoreData = {
    labels: {!! json_encode($repairRevenueByStore->pluck('name')) !!},
    datasets: [{
        label: 'Repair Revenue (KWD)',
        data: {!! json_encode($repairRevenueByStore->pluck('total_revenue')) !!},
        borderWidth: 1,
        backgroundColor: chartColors,
        borderColor: chartBorderColors,
    }]
};

const productRevenueByStoreData = {
    labels: {!! json_encode($productRevenueByStore->pluck('name')) !!},
    datasets: [{
        label: 'Completed Product Revenue (KWD)',
        data: {!! json_encode($productRevenueByStore->pluck('total_revenue')) !!},
        borderWidth: 1,
        backgroundColor: chartColors,
        borderColor: chartBorderColors,
    }]
};

const onlineOrderTrendData = {
    labels: {!! json_encode($onlineOrderTrends->pluck('date')) !!},
    datasets: [
        {
            label: 'Orders Received',
            data: {!! json_encode($onlineOrderTrends->pluck('total_orders')) !!},
            borderWidth: 2,
            borderColor: chartBorderColors[0],
            backgroundColor: 'rgba(37, 99, 235, 0.15)',
            yAxisID: 'y',
            tension: 0.3
        },
        {
            label: 'Gross Received (KWD)',
            data: {!! json_encode($onlineOrderTrends->pluck('total_revenue')) !!},
            borderWidth: 2,
            borderColor: chartBorderColors[1],
            backgroundColor: 'rgba(34, 197, 94, 0.15)',
            yAxisID: 'y1',
            tension: 0.3
        }
    ]
};

const repairTrendsData = {
    labels: {!! json_encode($repairTrends->pluck('date')) !!},
    datasets: [{
        label: 'Repairs Created',
        data: {!! json_encode($repairTrends->pluck('total')) !!},
        borderWidth: 2,
        borderColor: chartBorderColors[4],
        backgroundColor: 'rgba(139, 92, 246, 0.15)',
        fill: false,
        tension: 0.3
    }]
};

const axisOptions = {
    responsive: true,
    maintainAspectRatio: true,
    scales: {
        y: { beginAtZero: true }
    }
};

const dualAxisOptions = {
    responsive: true,
    maintainAspectRatio: true,
    scales: {
        y: { beginAtZero: true, position: 'left' },
        y1: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false } }
    }
};

new Chart(document.getElementById('onlineOrderTrendChart'), {
    type: 'line',
    data: onlineOrderTrendData,
    options: dualAxisOptions
});
new Chart(document.getElementById('productRevenueByStoreChart'), { type: 'pie', data: productRevenueByStoreData });
new Chart(document.getElementById('repairRevenueByStoreChart'), { type: 'pie', data: repairRevenueByStoreData });
new Chart(document.getElementById('topProductsChart'), { type: 'bar', data: topProductsData, options: axisOptions });
new Chart(document.getElementById('topSalesmenChart'), { type: 'bar', data: topSalesmenData, options: axisOptions });
new Chart(document.getElementById('repairTrendChart'), { type: 'line', data: repairTrendsData, options: axisOptions });
</script>
@endsection
