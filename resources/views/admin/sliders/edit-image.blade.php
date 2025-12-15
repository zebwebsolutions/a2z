@extends('admin.layouts.app')

@section('title', 'Edit Slide')

@section('content')

<div class="bg-white p-6 rounded shadow max-w-2xl mx-auto">

    <h1 class="text-2xl font-bold mb-4">Edit Slide</h1>

    {{-- Preview --}}
    <div class="mb-4">
        <img src="{{ asset('storage/' . $image->image) }}" 
             class="w-full h-60 object-cover rounded shadow">
    </div>

    <form action="{{ route('admin.sliders.images.update', $image) }}" 
          method="POST" 
          enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="mb-4">
            <label class="block font-semibold mb-1">Heading</label>
            <input type="text" name="heading" class="w-full border p-2"
                   value="{{ old('heading', $image->heading) }}">
        </div>

        <div class="mb-4">
            <label class="block font-semibold mb-1">Description</label>
            <textarea name="description" class="w-full border p-2"
            >{{ old('description', $image->description) }}</textarea>
        </div>

        <div class="mb-4">
            <label class="block font-semibold mb-1">Button Text</label>
            <input type="text" name="button_text" class="w-full border p-2"
                   value="{{ old('button_text', $image->button_text) }}">
        </div>

        <div class="mb-4">
            <label class="block font-semibold mb-1">Button Link</label>
            <input type="text" name="button_link" class="w-full border p-2"
                   value="{{ old('button_link', $image->button_link) }}">
        </div>

        <div class="mb-4">
            <label class="block font-semibold mb-1">Replace Image (optional)</label>
            <input type="file" name="image" class="border p-2 w-full">
        </div>

        <button class="px-4 py-2 bg-blue-600 text-white rounded">Update Slide</button>

        <a href="{{ route('admin.sliders.images.index', $slider->id) }}" 
           class="ml-4 text-gray-600">Cancel</a>
    </form>
</div>

@endsection
