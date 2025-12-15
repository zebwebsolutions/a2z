@extends('admin.layouts.app')

@section('title', 'Order Details')

@section('content')
<div class="bg-white p-6 rounded-lg shadow">
    <h1 class="text-2xl font-bold mb-4">Order #{{ $order->id }}</h1>

    <div class="mb-6">
        <h2 class="text-xl font-semibold mb-2">Customer Information</h2>
        <p><strong>Name:</strong> {{ $order->customer_name ?? 'Guest' }}</p>
        <p><strong>Email:</strong> {{ $order->customer_email ?? 'N/A' }}</p>
        <p><strong>Phone:</strong> {{ $order->customer_phone ?? 'N/A' }}</p>
        <p><strong>Address:</strong> {{ $order->customer_address ?? 'N/A' }}</p>
    </div>

    <div class="mb-6">
        <h2 class="text-xl font-semibold mb-2">Order Summary</h2>
        <p><strong>Status:</strong> {{ ucfirst($order->status) }}</p>
        <p><strong>Total:</strong> ${{ number_format($order->total, 2) }}</p>
        <p><strong>Placed On:</strong> {{ $order->created_at->format('d M, Y h:i A') }}</p>
    </div>

    <div>
        <h2 class="text-xl font-semibold mb-2">Items</h2>
        <table class="w-full border-collapse">
            <thead>
                <tr class="bg-gray-100 border-b">
                    <th class="p-3 text-left">Product</th>
                    <th class="p-3 text-left">Quantity</th>
                    <th class="p-3 text-left">Price</th>
                    <th class="p-3 text-left">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @forelse($order->items as $item)
                    <tr class="border-b">
                        <td class="p-3">{{ optional($item->product)->name ?? 'Deleted Product' }}</td>
                        <td class="p-3">{{ $item->quantity }}</td>
                        <td class="p-3">${{ number_format($item->price, 2) }}</td>
                        <td class="p-3">${{ number_format($item->subtotal, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="p-4 text-center text-gray-600">No items found for this order.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
