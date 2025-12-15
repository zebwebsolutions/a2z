@extends('layouts.app')

@section('content')
<div class="container mx-auto py-10">
    <h1 class="text-2xl font-bold mb-6">Checkout</h1>

    <form action="{{ route('cart.placeOrder') }}" method="POST" class="space-y-4 max-w-lg">
        @csrf

        <input type="text" name="customer_name" placeholder="Full Name" class="border p-2 w-full rounded" required>
        <input type="email" name="customer_email" placeholder="Email (optional)" class="border p-2 w-full rounded">
        <input type="text" name="customer_phone" placeholder="Phone (optional)" class="border p-2 w-full rounded">
        <textarea name="customer_address" placeholder="Address" class="border p-2 w-full rounded"></textarea>

        <button class="bg-blue-600 text-white px-4 py-2 rounded">Place Order</button>
    </form>
</div>
@endsection
