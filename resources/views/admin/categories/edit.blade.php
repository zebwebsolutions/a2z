@extends('admin.layouts.app')

@section('content')
<div class="container mx-auto py-8">
    <h1 class="text-2xl font-bold mb-6">Edit Category</h1>

    <form action="{{ route('admin.categories.update', $category) }}" method="POST" class="space-y-4" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <input type="text" name="name" value="{{ old('name', $category->name) }}" class="border p-2 w-full rounded" required>

        <textarea name="description" class="border p-2 w-full rounded">{{ old('description', $category->description) }}</textarea>

        {{-- Parent Category --}}
        <div>
            <label class="font-medium">Parent Category</label>
            <select name="parent_id" class="w-full border rounded p-2">

                <option value="">None (Top-Level Category)</option>

                @foreach($categories as $cat)
                    {{-- Prevent selecting itself as parent --}}
                    @if($cat->id != $category->id)
                        <option value="{{ $cat->id }}"
                            {{ old('parent_id', $category->parent_id) == $cat->id ? 'selected' : '' }}>
                            {{ $cat->name }}
                        </option>
                    @endif
                @endforeach

            </select>
        </div>
        
        {{-- Existing Image --}}
        @if($category->image)
            <div class="flex items-center gap-4 mb-4">
                <img src="{{ asset('storage/' . $category->image) }}" class="w-24 h-24 object-cover rounded shadow">
                <p class="text-sm text-gray-600">Current image</p>
            </div>
        @endif

        <input type="file" name="image" class="border p-2 w-full">

        <label class="flex items-center gap-2">
            <input type="checkbox" name="is_active" value="1" {{ $category->is_active ? 'checked' : '' }}>
            <span>Active</span>
        </label>

        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Update</button>
    </form>
</div>
@endsection
