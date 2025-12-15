@extends('admin.layouts.app')

@section('content')
<div class="container mx-auto py-8">
    <h1 class="text-2xl font-bold mb-6">Edit Store</h1>

    <form method="POST" action="{{ route('admin.stores.update', $store) }}" class="space-y-4">
        @csrf @method('PUT')
        <input type="text" name="name" value="{{ $store->name }}" class="border p-2 w-full" required>
        <input type="text" name="address" value="{{ $store->address }}" class="border p-2 w-full" required>
        <input type="text" name="phone" value="{{ $store->phone }}" class="border p-2 w-full">
        <input type="text" name="city" value="{{ $store->city }}" class="border p-2 w-full">
        <button class="bg-blue-600 text-white px-4 py-2 rounded">Update</button>
    </form>
</div>
@endsection
