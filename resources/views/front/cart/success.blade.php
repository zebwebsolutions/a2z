@extends('layouts.app')

@section('content')
<div class="container mx-auto py-10 px-4 sm:px-6 text-center">
    <h1 class="text-3xl font-bold text-green-600 mb-4">Order Placed Successfully!</h1>
    <p class="mb-6">Thank you, {{ $order->customer_name }}. Your order has been received.</p>

    <h2 class="text-xl font-semibold mb-3">Order Details</h2>
    <div class="mx-auto max-w-4xl bg-white shadow rounded overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[620px]">
                <thead class="bg-gray-100">
                    <tr class="text-left text-sm md:text-base">
                        <th class="p-3">Product</th>
                        <th class="p-3">Qty</th>
                        <th class="p-3">Price</th>
                        <th class="p-3">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="text-sm md:text-base">
                    @foreach($order->items as $item)
                        <tr class="border-b">
                            <td class="p-3 max-w-[260px] break-words text-left">{{ $item->product->name }}</td>
                            <td class="p-3 text-left">{{ $item->quantity }}</td>
                            <td class="p-3 whitespace-nowrap text-left">{{ $item->price }}</td>
                            <td class="p-3 whitespace-nowrap text-left">{{ $item->subtotal }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <h3 class="mt-6 text-lg font-bold">Total: {{ $order->total_amount }} KWD</h3>

    <div class="mt-8">
        <a href="{{ route('products.index') }}" class="bg-blue-600 text-white px-4 py-2 rounded">Continue Shopping</a>
    </div>
</div>
@endsection
