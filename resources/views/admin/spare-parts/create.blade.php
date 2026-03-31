@extends('admin.layouts.app')

@section('content')
<div class="container mx-auto px-6 py-6">

    {{-- Page Header --}}
    <div class="mb-6">
        <h1 class="text-xl font-semibold text-gray-800">Add Spare Part</h1>
    </div>

    <form action="{{ route('admin.spare-parts.store') }}" method="POST"
          class="bg-white rounded shadow p-6 space-y-6">
        @csrf

        {{-- Name --}}
        <div>
            <label class="block text-sm font-medium text-gray-700">Name</label>
            <input type="text" name="name" value="{{ old('name') }}"
                   class="mt-1 w-full border rounded px-3 py-2"
                   required>
        </div>

        {{-- SKU --}}
        <div>
            <label class="block text-sm font-medium text-gray-700">SKU</label>
            <input type="text" name="sku" value="{{ old('sku') }}"
                   class="mt-1 w-full border rounded px-3 py-2">
        </div>

        {{-- Barcode --}}
        <div>
            <label class="block text-sm font-medium text-gray-700">Barcode</label>
            <input type="text" name="barcode" value="{{ $barcode }}"
                   readonly
                   class="mt-1 w-full border rounded px-3 py-2 bg-gray-100 cursor-not-allowed">

            <div class="mt-3">
                {!! DNS1D::getBarcodeHTML($barcode, 'C128') !!}
            </div>
        </div>

        {{-- Prices --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700">Cost Price</label>
                <input type="number" step="0.01" name="cost_price"
                       value="{{ old('cost_price') }}"
                       class="mt-1 w-full border rounded px-3 py-2"
                       required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Selling Price</label>
                <input type="number" step="0.01" name="selling_price"
                       value="{{ old('selling_price') }}"
                       class="mt-1 w-full border rounded px-3 py-2"
                       required>
            </div>
        </div>

        {{-- Stock --}}
        <div>
            <label class="block text-sm font-medium text-gray-700">Initial Stock</label>
            <input type="number" name="stock_quantity"
                   value="{{ old('stock_quantity', 0) }}"
                   class="mt-1 w-full border rounded px-3 py-2"
                   min="0"
                   required>
        </div>

        {{-- Actions --}}
        <div class="flex justify-end space-x-3">
            <a href="{{ route('admin.spare-parts.index') }}"
               class="px-4 py-2 border rounded text-gray-700">
                Cancel
            </a>

            <button type="submit"
                    class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                Save Spare Part
            </button>
        </div>
    </form>
</div>
@endsection