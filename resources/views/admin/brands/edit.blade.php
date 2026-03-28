@extends('admin.layouts.app')

@section('title', 'Edit Brand')

@section('content')

<div class="bg-white p-6 shadow rounded max-w-xl mx-auto">

    <h1 class="text-2xl font-bold mb-6">Edit Brand</h1>

    <form action="{{ route('admin.brands.update', $brand) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="mb-4">
            <label class="block font-semibold mb-1">Brand Name</label>
            <input type="text" name="name" class="border p-2 w-full"
                   value="{{ $brand->name }}" required>
        </div>

        @if($brand->logo) 
        <div class="mb-4">
            <label class="block font-semibold mb-1">Current Logo</label>
            <img src="{{ asset('storage/' . $brand->logo) }}" alt="{{ $brand->name }}" class="h-20">
        </div>
        @endif

        <div class="mb-4">
            <label class="block font-semibold mb-1">Replace Brand Logo</label>
            <input type="file" name="logo" class="border p-2 w-full"
                   accept="image/*">
        </div>

        <div class="mb-4">
            <label class="flex gap-2 items-center">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" {{ $brand->is_active ? 'checked' : '' }}>
                <span>Active</span>
            </label>
        </div>

        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">Update Brand</button>

        <a href="{{ route('admin.brands.index') }}" class="ml-4 text-gray-600">Cancel</a>
    </form>

</div>

@endsection
