@extends('layouts.app')

@section('content')
<div class="container mx-auto py-10 px-4 sm:px-6">
    <h1 class="text-2xl font-bold mb-6">Checkout</h1>

    @php
        $subtotal = collect($cart)->sum(fn ($item) => $item['price'] * $item['quantity']);
        $deliveryCharge = 1;
    @endphp

    <div class="mb-6 max-w-lg rounded border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
        We currently offer cash on delivery only. Delivery charges are a flat 1 KWD.
    </div>

    <div class="mb-6 max-w-lg rounded border bg-white p-4 text-sm">
        <div class="flex justify-between">
            <span>Items subtotal</span>
            <strong>{{ number_format($subtotal, 3) }} KWD</strong>
        </div>
        <div class="mt-2 flex justify-between">
            <span>Delivery</span>
            <strong>{{ number_format($deliveryCharge, 3) }} KWD</strong>
        </div>
        <div class="mt-3 flex justify-between border-t pt-3 text-base">
            <span>Total</span>
            <strong>{{ number_format($subtotal + $deliveryCharge, 3) }} KWD</strong>
        </div>
    </div>

    <form action="{{ route('cart.placeOrder') }}" method="POST" class="space-y-4 max-w-lg">
        @csrf

        <input type="text" name="customer_name" value="{{ old('customer_name') }}" placeholder="Full Name" class="border p-2 w-full rounded" required>
        <input type="email" name="customer_email" value="{{ old('customer_email') }}" placeholder="Email" class="border p-2 w-full rounded" required>
        @error('customer_email') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
        <input type="text" name="customer_phone" value="{{ old('customer_phone') }}" placeholder="Phone" class="border p-2 w-full rounded" required>
        @error('customer_phone') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
        <textarea name="customer_address" placeholder="Address" class="border p-2 w-full rounded">{{ old('customer_address') }}</textarea>
        @error('customer_address') <p class="text-sm text-red-600">{{ $message }}</p> @enderror

        <input type="hidden" name="payment_method" value="cash">

        <button class="bg-blue-600 text-white px-4 py-2 rounded">Place Order</button>
    </form>
</div>
@endsection
