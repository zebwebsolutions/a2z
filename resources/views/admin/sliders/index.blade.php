@extends('admin.layouts.app')

@section('title', 'Sliders')

@section('content')

<div class="flex justify-between mb-6">
    <h1 class="text-2xl font-bold">Sliders</h1>
    <a href="{{ route('admin.sliders.create') }}"
       class="px-4 py-2 bg-blue-600 text-white rounded">+ New Slider</a>
</div>

<table class="w-full bg-white shadow rounded">
    <tr class="border-b text-left">
        <th class="p-3">Name</th>
        <th class="p-3">Title</th>
        <th class="p-3">Active</th>
        <th class="p-3">Actions</th>
    </tr>

    @foreach($sliders as $slider)
    <tr class="border-b">
        <td class="p-3">{{ $slider->name }}</td>
        <td class="p-3">{{ $slider->title }}</td>
        <td class="p-3">{{ $slider->is_active ? 'Yes' : 'No' }}</td>
        <td class="p-3 flex gap-2">
            <a href="{{ route('admin.sliders.edit', $slider) }}" class="text-blue-600">Edit</a>
            <a href="{{ route('admin.sliders.images.index', $slider) }}" class="text-green-600">Images</a>
            <form action="{{ route('admin.sliders.destroy', $slider) }}" method="POST">
                @csrf @method('DELETE')
                <button class="text-red-600 ml-2">Delete</button>
            </form>
        </td>
    </tr>
    @endforeach
</table>

@endsection
