@extends('admin.layouts.app')

@section('content')

<div class="p-6">

    <h1 class="text-2xl font-bold mb-6">Homepage Sections</h1>

    <a href="{{ route('admin.home-sections.create') }}"
       class="bg-blue-600 text-white px-4 py-2 rounded mb-4 inline-block">
        + Add New Section
    </a>

    <table class="w-full bg-white shadow rounded-lg">
        <thead>
            <tr class="bg-gray-100 text-left">
                <th class="p-3">Title</th>
                <th class="p-3">Active</th>
                <th class="p-3">Sort Order</th>
                <th class="p-3">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sections as $section)
                <tr class="border-b">
                    <td class="p-3">{{ $section->title }}</td>
                    <td class="p-3">{{ $section->is_active ? 'Yes' : 'No' }}</td>
                    <td class="p-3">{{ $section->sort_order }}</td>
                    <td class="p-3">
                        <a href="{{ route('admin.home-sections.edit', $section->id) }}"
                           class="text-blue-600">Edit</a>
                        |
                        <form action="{{ route('admin.home-sections.destroy', $section->id) }}"
                              method="POST" class="inline">
                            @csrf @method('DELETE')
                            <button class="text-red-600"
                                    onclick="return confirm('Delete this section?')">
                                Delete
                            </button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

</div>

@endsection