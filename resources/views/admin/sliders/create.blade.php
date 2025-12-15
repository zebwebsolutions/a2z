@extends('admin.layouts.app')

@section('title', 'Create Slider')

@section('content')
<div class="bg-white p-6 rounded shadow max-w-xl mx-auto">
    <h1 class="text-2xl font-bold mb-6">Create New Slider</h1>

    <form action="{{ route('admin.sliders.store') }}" method="POST">
        @csrf

        <div class="mb-4">
            <label class="block font-semibold mb-1">Slider Name (used in shortcode)</label>
            <input type="text" name="name" class="border p-2 w-full" placeholder="homepage_slider" required>
        </div>

        <div class="mb-4">
            <label class="block font-semibold mb-1">Title (for admin)</label>
            <input type="text" name="title" class="border p-2 w-full" placeholder="Homepage Slider">
        </div>

        <div class="mb-4">
            <label class="flex items-center gap-2">
                <input type="checkbox" name="is_active" value="1" checked>
                <span>Active</span>
            </label>
        </div>

        <button class="px-4 py-2 bg-blue-600 text-white rounded">Create Slider</button>
    </form>
</div>
@endsection
