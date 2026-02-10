@extends('admin.layouts.app')

@section('content')
<div class="container mx-auto py-8">
    <h1 class="text-2xl font-bold mb-6">A2Z Analytics Dashboard</h1>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-4 gap-4 mb-8">
        <div class="bg-white shadow p-4 rounded text-center">
            <h2 class="text-sm text-gray-500">Products</h2>
            <p class="text-2xl font-bold">{{ $stats['products'] }}</p>
        </div>
        <div class="bg-white shadow p-4 rounded text-center">
            <h2 class="text-sm text-gray-500">Repairs</h2>
            <p class="text-2xl font-bold">{{ $stats['repairs'] }}</p>
        </div>
        <div class="bg-white shadow p-4 rounded text-center">
            <h2 class="text-sm text-gray-500">Salesmen</h2>
            <p class="text-2xl font-bold">{{ $stats['salesmen'] }}</p>
        </div>
        <div class="bg-white shadow p-4 rounded text-center">
            <h2 class="text-sm text-gray-500">Stores</h2>
            <p class="text-2xl font-bold">{{ $stats['stores'] }}</p>
        </div>
    </div>

    {{-- Charts --}}
    <div class="grid grid-cols-2 gap-8">
        <div class="bg-white p-4 shadow rounded">
            <h2 class="text-lg font-semibold mb-4">Top Products</h2>
            <canvas id="topProductsChart"></canvas>
        </div>

        <div class="bg-white p-4 shadow rounded">
            <h2 class="text-lg font-semibold mb-4">Top Salesmen</h2>
            <canvas id="topSalesmenChart"></canvas>
        </div>

        <div class="bg-white p-4 shadow rounded">
            <h2 class="text-lg font-semibold mb-4">Revenue by Store</h2>
            <canvas id="revenueByStoreChart"></canvas>
        </div>

        <div class="bg-white p-4 shadow rounded">
            <h2 class="text-lg font-semibold mb-4">Repairs (Last 7 Days)</h2>
            <canvas id="repairTrendChart"></canvas>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
const topProductsData = {
    labels: {!! json_encode($topProducts->pluck('name')) !!},
    datasets: [{
        label: 'Units Sold',
        data: {!! json_encode($topProducts->pluck('total')) !!},
        borderWidth: 1,
        backgroundColor: 'rgba(54, 162, 235, 0.7)',
    }]
};

const topSalesmenData = {
    labels: {!! json_encode($topSalesmen->pluck('name')) !!},
    datasets: [{
        label: 'Repairs Completed',
        data: {!! json_encode($topSalesmen->pluck('total')) !!},
        borderWidth: 1,
        backgroundColor: 'rgba(255, 99, 132, 0.7)',
    }]
};

const revenueByStoreData = {
    labels: {!! json_encode($revenueByStore->pluck('name')) !!},
    datasets: [{
        label: 'Total Revenue (KWD)',
        data: {!! json_encode($revenueByStore->pluck('total_revenue')) !!},
        borderWidth: 1,
        backgroundColor: 'rgba(75, 192, 192, 0.7)',
    }]
};

const repairTrendsData = {
    labels: {!! json_encode($repairTrends->pluck('date')) !!},
    datasets: [{
        label: 'Repairs',
        data: {!! json_encode($repairTrends->pluck('total')) !!},
        borderWidth: 2,
        borderColor: 'rgba(153, 102, 255, 0.9)',
        fill: false,
        tension: 0.3
    }]
};

// Render charts
new Chart(document.getElementById('topProductsChart'), { type: 'bar', data: topProductsData });
new Chart(document.getElementById('topSalesmenChart'), { type: 'bar', data: topSalesmenData });
new Chart(document.getElementById('revenueByStoreChart'), { type: 'pie', data: revenueByStoreData });
new Chart(document.getElementById('repairTrendChart'), { type: 'line', data: repairTrendsData });
</script>
@endsection
