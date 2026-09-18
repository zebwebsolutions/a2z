@extends('admin.layouts.app')
@section('title', 'Purchases')
@section('content')
<div class="bg-white p-6 rounded-lg shadow">
    <h1 class="text-2xl font-bold mb-6">{{ request('customer_purchase_id') ? 'Customer Purchase History' : 'Purchases' }}</h1>
    <x-admin.filter-box>
        @if(request('customer_purchase_id'))
            <input type="hidden" name="customer_purchase_id" value="{{ request('customer_purchase_id') }}">
        @endif
        <div class="md:col-span-4">
            <label for="purchase-search" class="block text-xs font-semibold text-gray-600 mb-1">Search</label>
            <input id="purchase-search" name="search" value="{{ request('search') }}" placeholder="Customer name, phone, product or purchase number" class="w-full border rounded px-3 py-2 text-sm">
        </div>
    </x-admin.filter-box>
    <p class="text-sm text-gray-600 mb-4">{{ $purchases->total() }} purchases</p>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-100"><tr>
                <th class="p-3 text-left">Purchase</th><th class="p-3 text-left">Product</th><th class="p-3 text-left">Customer</th>
                <th class="p-3 text-left">Quantity</th><th class="p-3 text-left">Total (KD)</th><th class="p-3 text-left">Date</th><th class="p-3 text-right">Actions</th>
            </tr></thead>
            <tbody>
                @forelse($purchases as $purchase)
                <tr class="border-b">
                    <td class="p-3">#{{ $purchase->id }}</td>
                    <td class="p-3">{{ $purchase->product_name ?? $purchase->product?->name ?? 'Deleted product' }}</td>
                    <td class="p-3"><a class="text-blue-600 hover:underline" href="{{ route('admin.purchases.index', ['customer_purchase_id' => $purchase->id]) }}">{{ $purchase->customer_name }}</a><div class="text-gray-500">{{ $purchase->customer_phone }}</div></td>
                    <td class="p-3">{{ $purchase->quantity }}</td>
                    <td class="p-3">{{ number_format($purchase->quantity * $purchase->unit_cost, 3) }}</td>
                    <td class="p-3">{{ $purchase->created_at->format('d M Y, H:i') }}</td>
                    <td class="p-3 text-right"><a class="text-blue-600 hover:underline" href="{{ route('admin.purchases.show', $purchase) }}">View details / ID</a></td>
                </tr>
                @empty
                <tr><td colspan="7" class="p-6 text-center text-gray-500">No purchases found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $purchases->links() }}</div>
</div>
@endsection
