@extends('layouts.app')

@section('content')
<div class="container mx-auto py-10 px-4 sm:px-6">
    <h1 class="text-2xl font-bold mb-6">Shopping Cart</h1>

    @if(empty($cart))
        <p>Your cart is empty. <a href="{{ route('products.index') }}" class="text-blue-600 underline">Go shopping</a>.</p>
    @else
        <div class="bg-white shadow rounded overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[680px]">
                    <thead class="bg-gray-100">
                        <tr class="text-left text-sm md:text-base">
                            <th class="p-3">Product</th>
                            <th class="p-3">Price</th>
                            <th class="p-3">Quantity</th>
                            <th class="p-3">Subtotal</th>
                            <th class="p-3">Action</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm md:text-base">
                        @php $total = 0; @endphp
                        @foreach($cart as $id => $item)
                            @php $total += $item['price'] * $item['quantity']; @endphp
                            <tr class="border-b">
                                <td class="p-3 max-w-[260px] break-words">{{ $item['name'] }}</td>
                                <td class="p-3 whitespace-nowrap">{{ $item['price'] }} KWD</td>
                                <td class="p-3">{{ $item['quantity'] }}</td>
                                <td class="p-3 whitespace-nowrap">{{ $item['price'] * $item['quantity'] }} KWD</td>
                                <td class="p-3">
                                    <a href="{{ route('cart.remove', $id) }}" class="text-red-600 hover:underline whitespace-nowrap">Remove</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="text-right mt-6">
            <h2 class="text-xl font-bold">Total: {{ $total }} KWD</h2>
            <a href="{{ route('cart.checkout') }}" class="bg-blue-600 text-white px-4 py-2 rounded mt-4 inline-block">Proceed to Checkout</a>
        </div>
    @endif
</div>
@endsection
