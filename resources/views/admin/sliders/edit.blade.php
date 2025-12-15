@extends('admin.layouts.app')

@section('title', 'Edit Slider')

@section('content')

<div class="bg-white p-6 rounded shadow max-w-2xl mx-auto">

    <h1 class="text-2xl font-bold mb-4">Edit Slider</h1>

    {{-- Slider info fields --}}
    <form action="{{ route('admin.sliders.update', $slider->id) }}" 
          method="POST">

        @csrf
        @method('PUT')

        {{-- Name --}}
        <div class="mb-4">
            <label class="block font-semibold mb-1">Name</label>
            <input type="text" name="name" 
                   value="{{ old('name', $slider->name) }}"
                   class="w-full border p-2 rounded">
        </div>

        {{-- Title --}}
        <div class="mb-4">
            <label class="block font-semibold mb-1">Title (optional)</label>
            <input type="text" name="title" 
                   value="{{ old('title', $slider->title) }}"
                   class="w-full border p-2 rounded">
        </div>

        {{-- Active Toggle --}}
        <div class="mb-4">
            <label class="block font-semibold mb-1">Active</label>
            <select name="is_active" class="w-full border p-2 rounded">
                <option value="1" @selected($slider->is_active)>Yes</option>
                <option value="0" @selected(!$slider->is_active)>No</option>
            </select>
        </div>

        <button class="px-4 py-2 bg-blue-600 text-white rounded">
            Update Slider
        </button>

        <a href="{{ route('admin.sliders.index') }}" 
           class="ml-4 text-gray-600">Cancel</a>

    </form>

    {{-- Show list of all slider images --}}
    <div class="mt-8">
        <h2 class="text-lg font-bold mb-2">Slide Images</h2>

        <a href="{{ route('admin.sliders.images.index', $slider->id) }}"
           class="text-blue-600 underline">
            Manage Slider Images →
        </a>
    </div>

</div>

@endsection