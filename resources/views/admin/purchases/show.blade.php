@extends('admin.layouts.app')
@section('title', 'Purchase Details')
@section('content')
<div class="bg-white p-6 rounded-lg shadow">
    <a href="{{ route('admin.purchases.index') }}" class="text-sm text-blue-600 hover:underline">← Purchases</a>
    <h1 class="text-2xl font-bold mt-4 mb-6">Purchase #{{ $purchase->id }}</h1>
    <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        <div><dt class="font-semibold text-gray-600">Product</dt><dd>{{ $purchase->product_name ?? $purchase->product?->name ?? 'Deleted product' }}</dd></div>
        <div><dt class="font-semibold text-gray-600">Customer</dt><dd><a class="text-blue-600 hover:underline" href="{{ route('admin.purchases.index', ['customer_purchase_id' => $purchase->id]) }}">{{ $purchase->customer_name }}</a></dd></div>
        <div><dt class="font-semibold text-gray-600">Phone</dt><dd>{{ $purchase->customer_phone }}</dd></div>
        <div><dt class="font-semibold text-gray-600">Purchased at</dt><dd>{{ $purchase->created_at->format('d M Y, H:i') }}</dd></div>
        <div><dt class="font-semibold text-gray-600">Quantity / Unit Cost</dt><dd>{{ $purchase->quantity }} × {{ number_format($purchase->unit_cost, 3) }} KD</dd></div>
        <div><dt class="font-semibold text-gray-600">Total</dt><dd>{{ number_format($purchase->quantity * $purchase->unit_cost, 3) }} KD</dd></div>
    </dl>
    <h2 class="text-lg font-semibold mt-6 mb-3">Customer ID</h2>
    <a href="{{ route('admin.purchases.id-image', $purchase) }}" target="_blank" rel="noopener" class="text-blue-600 text-sm hover:underline">Open ID photo</a>
    <img src="{{ route('admin.purchases.id-image', $purchase) }}" alt="Customer ID copy" class="mt-3 max-w-full max-h-screen rounded border" loading="lazy">
</div>
@endsection
