@extends('admin.layouts.app')

@section('content')
<div class="container mx-auto py-8">
    <h1 class="text-2xl font-bold mb-4">Stores</h1>

    <a href="{{ route('admin.stores.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded">Add Store</a>

    <table class="w-full mt-6 bg-white shadow rounded">
        <thead>
            <tr class="bg-gray-100 text-left">
                <th class="p-3">#</th>
                <th class="p-3">Name</th>
                <th class="p-3">Address</th>
                <th class="p-3">Phone</th>
                <th class="p-3">City</th>
                <th class="p-3">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($stores as $store)
            <tr class="border-b">
                <td class="p-3">{{ $store->id }}</td>
                <td class="p-3">{{ $store->name }}</td>
                <td class="p-3">{{ $store->address }}</td>
                <td class="p-3">{{ $store->phone }}</td>
                <td class="p-3">{{ $store->city }}</td>
                <td class="p-3">
                    <a href="{{ route('admin.stores.edit', $store) }}" class="text-blue-600 mr-2">Edit</a>
                    <form action="{{ route('admin.stores.destroy', $store) }}" method="POST" class="inline">
                        @csrf @method('DELETE')
                        <button onclick="return confirm('Are you sure?')" class="text-red-600">Delete</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="mt-4">
        {{ $stores->links() }}
    </div>
</div>
@endsection
