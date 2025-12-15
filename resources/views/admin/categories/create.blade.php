@extends('admin.layouts.app')

@section('content')
<div class="container mx-auto py-8">
    <h1 class="text-2xl font-bold mb-6">Add Category</h1>

    <form action="{{ route('admin.categories.store') }}" method="POST" class="space-y-4" enctype="multipart/form-data">
        @csrf

        <input type="text" name="name" placeholder="Category Name" class="border p-2 w-full rounded" required>

        {{-- Parent Category --}}
        <div>
            <select name="parent_id" class="w-full border rounded p-2">
                <option value="">Select Parent Category</option>

                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}">
                        {{ $cat->name }}
                    </option>
                @endforeach
            </select>
            <small class="text-gray-500">Choose a parent to create a subcategory</small>
        </div>

        <textarea name="description" placeholder="Category Description (optional)" class="border p-2 w-full rounded"></textarea>

        <img id="preview" class="w-24 h-24 object-cover mt-2 rounded hidden">
        
        <input type="file" name="image" class="border p-2 w-full">

        <label class="flex items-center gap-2">
            <input type="checkbox" name="is_active" value="1" checked>
            <span>Active</span>
        </label>

        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Save</button>
    </form>
    <script>
      document.addEventListener('DOMContentLoaded', () => {
        let image = document.querySelector('input[name="image"]');
        image.addEventListener('change', e => {
            const file = e.target.files[0];
            if (file) {
                const preview = document.getElementById('preview');
                preview.src = URL.createObjectURL(file);
                preview.classList.remove('hidden');
            }
        });
      });
    </script>
</div>
@endsection
