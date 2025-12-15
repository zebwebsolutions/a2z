@extends('admin.layouts.app')

@section('content')
<div class="container mx-auto py-8">
    <h1 class="text-2xl font-bold mb-4">Products</h1>
    <a href="{{ route('admin.products.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded">Add Product</a>

    <table class="w-full mt-6 bg-white shadow rounded">
        <thead class="bg-gray-100 text-left">
            <tr>
                <th class="p-3">Name</th>
                <th class="p-3">Store</th>
                <th class="p-3">Category</th>
                <th class="p-3">Price</th>
                <th class="p-3">Stock</th>
                <th class="p-3">Image</th>
                <th class="p-3">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($products as $product)
            <tr class="border-b">
                <td class="p-3">{{ $product->name }}</td>
                <td class="p-3">{{ $product->store->name }}</td>
                <td class="p-3">{{ $product->category?->name ?? '-' }}</td>
                <td class="p-3">${{ number_format($product->price, 2) }}</td>
                <td class="p-3">{{ $product->stock }}</td>
                <td class="p-3">
                    @if($product->image)
                        <img src="{{ asset('storage/' . $product->image) }}" class="w-16 h-16 object-cover rounded">
                    @endif
                </td>
                <td class="p-3">
                    <a href="{{ route('admin.products.edit', $product) }}" class="text-blue-600 mr-2">Edit</a>
                    <form action="{{ route('admin.products.destroy', $product) }}" method="POST" class="inline">
                        @csrf @method('DELETE')
                        <button onclick="return confirm('Delete this product?')" class="text-red-600">Delete</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="mt-4">
        {{ $products->links() }}
    </div>
</div>
@endsection
