@extends('admin.layouts.app')

@section('content')

<div class="p-6 max-w-3xl mx-auto">

    <h1 class="text-2xl font-bold mb-6">Create Homepage Section</h1>

    <form action="{{ route('admin.home-sections.store') }}" method="POST">
        @csrf

        <label class="block mb-2 font-semibold">Title</label>
        <input type="text" name="title" class="w-full border p-2 rounded mb-4">

        <label class="block mb-2 font-semibold">Sort Order</label>
        <input type="number" name="sort_order" class="w-full border p-2 rounded mb-4">

        <label class="block mb-2 font-semibold">Active</label>
        <select name="is_active" class="w-full border p-2 rounded mb-4">
            <option value="1">Active</option>
            <option value="0">Inactive</option>
        </select>

        <x-admin.product-selector :selected-products="[]" name="products" />

        <button class="mt-4 bg-blue-600 text-white px-4 py-2 rounded">
            Save Section
        </button>

    </form>
</div>

@endsection
