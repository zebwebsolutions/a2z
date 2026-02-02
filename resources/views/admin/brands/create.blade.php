@extends('admin.layouts.app')

@section('title', 'Add Brand')

@section('content')

<div class="bg-white p-6 shadow rounded max-w-xl mx-auto">

    <h1 class="text-2xl font-bold mb-6">Add Brand</h1>

    <form action="{{ route('admin.brands.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="mb-4">
            <label class="block font-semibold mb-1">Brand Name</label>
            <input type="text" name="name" class="border p-2 w-full"
                   placeholder="Apple, Samsung, Xiaomi..."
                   required>
        </div>

        <div class="mb-4">
            <label class="block font-semibold mb-1">Brand Logo</label>
            <input type="file" name="logo" class="border p-2 w-full"
                   accept="image/*">
        </div>

        <div class="mb-4">
            <label class="flex gap-2 items-center">
                <input type="checkbox" name="is_active" checked>
                <span>Active</span>
            </label>
        </div>

        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">Create Brand</button>

        <a href="{{ route('admin.brands.index') }}" class="ml-4 text-gray-600">Cancel</a>
    </form>

</div>

@endsection
