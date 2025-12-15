@extends('admin.layouts.app')

@section('content')
<div class="container mx-auto py-8">
    <h1 class="text-2xl font-bold mb-6">Add Store</h1>

    <form method="POST" action="{{ route('admin.stores.store') }}" class="space-y-4">
        @csrf
        <input type="text" name="name" placeholder="Store Name" class="border p-2 w-full" required>
        <input type="text" name="address" placeholder="Address" class="border p-2 w-full" required>
        <input type="text" name="phone" placeholder="Phone" class="border p-2 w-full">
        <input type="text" name="city" placeholder="City" class="border p-2 w-full">
        <button class="bg-blue-600 text-white px-4 py-2 rounded">Save</button>
    </form>
</div>
@endsection
