@extends('admin.layouts.app')

@section('title', 'Brands')

@section('content')

<div class="flex justify-between mb-6">
    <h1 class="text-2xl font-bold">Brands</h1>

    <a href="{{ route('admin.brands.create') }}"
       class="px-4 py-2 bg-blue-600 text-white rounded">
        + Add Brand
    </a>
</div>

<table class="w-full bg-white shadow rounded">
    <tr class="border-b bg-gray-100 font-semibold text-left">
        <th class="p-3">Name</th>
        <th class="p-3">Slug</th>
        <th class="p-3">Active</th>
        <th class="p-3 w-32">Actions</th>
    </tr>

    @foreach($brands as $brand)
    <tr class="border-b">
        <td class="p-3">{{ $brand->name }}</td>
        <td class="p-3 text-gray-600">{{ $brand->slug }}</td>
        <td class="p-3">
            <span class="{{ $brand->is_active ? 'text-green-600' : 'text-red-600' }}">
                {{ $brand->is_active ? 'Yes' : 'No' }}
            </span>
        </td>

        <td class="p-3 flex gap-3">
            <a href="{{ route('admin.brands.edit', $brand) }}" class="text-blue-600">Edit</a>

            <form action="{{ route('admin.brands.destroy', $brand) }}" method="POST">
                @csrf
                @method('DELETE')
                <button class="text-red-600"
                        onclick="return confirm('Delete this brand?')">Delete</button>
            </form>
        </td>
    </tr>
    @endforeach
</table>

<div class="mt-4">
    {{ $brands->links() }}
</div>

@endsection
