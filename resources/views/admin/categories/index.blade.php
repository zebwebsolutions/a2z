@extends('admin.layouts.app')

@section('content')
<div class="container mx-auto py-8">
    <h1 class="text-2xl font-bold mb-6">Categories</h1>

    <a href="{{ route('admin.categories.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded mb-4 inline-block">
        Add New Category
    </a>

    <table class="w-full bg-white shadow rounded">
        <thead class="bg-gray-100 text-left">
            <tr>
                <th class="p-3">Name</th>
                <th class="p-3">Slug</th>
                <th class="p-3">Active</th>
                <th class="p-3">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($categories as $category)
                <tr class="border-b">
                    <td class="p-3">{{ $category->name }}</td>
                    <td class="p-3">{{ $category->slug }}</td>
                    <td class="p-3">{{ $category->is_active ? 'Yes' : 'No' }}</td>
                    <td class="p-3 flex gap-2">
                        <a href="{{ route('admin.categories.edit', $category) }}" class="text-blue-600">Edit</a>
                        <form action="{{ route('admin.categories.destroy', $category) }}" method="POST" onsubmit="return confirm('Delete this category?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center p-3 text-gray-500">No categories found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="mt-6">
        {{ $categories->links() }}
    </div>
</div>
@endsection
