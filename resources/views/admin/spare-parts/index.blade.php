@extends('admin.layouts.app')

@section('content')
<div class="container mx-auto px-6 py-6">

    {{-- Page Header --}}
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Spare Parts</h1>

        <a href="{{ route('admin.spare-parts.create') }}"
           class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm rounded hover:bg-blue-700">
            + Add Spare Part
        </a>
    </div>

    {{-- Card --}}
    <div class="bg-white rounded shadow">

    <x-admin.filter-box>

        {{-- Search --}}
        <div class="md:col-span-2">
            <label class="block text-xs font-semibold text-gray-600 mb-1">
                Search
            </label>
            <input type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="Name or barcode"
                class="w-full border rounded px-3 py-2 text-sm">
        </div>

        {{-- Stock --}}
        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">
                Stock
            </label>
            <select name="stock"
                    class="w-full border rounded px-3 py-2 text-sm">
                <option value="">All</option>
                <option value="in"  @selected(request('stock') === 'in')>In Stock</option>
                <option value="low" @selected(request('stock') === 'low')>Low Stock</option>
                <option value="out" @selected(request('stock') === 'out')>Out of Stock</option>
            </select>
        </div>

        {{-- Price --}}
        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">
                Price
            </label>
            <div class="flex gap-2">
                <input type="number"
                    step="0.01"
                    name="price_min"
                    value="{{ request('price_min') }}"
                    placeholder="Min"
                    class="w-full border rounded px-2 py-2 text-sm">

                <input type="number"
                    step="0.01"
                    name="price_max"
                    value="{{ request('price_max') }}"
                    placeholder="Max"
                    class="w-full border rounded px-2 py-2 text-sm">
            </div>
        </div>

    </x-admin.filter-box>


        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr class="text-left text-gray-600 uppercase text-xs tracking-wider">
                        <th class="px-4 py-3">Name</th>
                        <th class="px-4 py-3">SKU</th>
                        <th class="px-4 py-3">Barcode</th>
                        <th class="px-4 py-3">Cost</th>
                        <th class="px-4 py-3">Price</th>
                        <th class="px-4 py-3">Stock</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y">
                    @forelse($spareParts as $part)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium text-gray-800">
                                {{ $part->name }}
                            </td>

                            <td class="px-4 py-3 text-gray-600">
                                {{ $part->sku ?? '—' }}
                            </td>

                            <td class="px-4 py-3 font-mono text-xs">
                                {{ $part->barcode }}
                            </td>

                            <td class="px-4 py-3">
                                {{ number_format($part->cost_price, 2) }}
                            </td>

                            <td class="px-4 py-3 font-semibold">
                                {{ number_format($part->selling_price, 2) }}
                            </td>

                            <td class="px-4 py-3">
                                <span class="px-2 py-1 rounded text-xs font-semibold
                                    {{ $part->stock_quantity <= 5 ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' }}">
                                    {{ $part->stock_quantity }}
                                </span>
                            </td>

                            <td class="px-4 py-3 text-right space-x-2">
                                <a href="{{ route('admin.spare-parts.edit', $part) }}"
                                   class="text-blue-600 hover:underline text-sm">
                                    Edit
                                </a>

                                <a href="{{ route('admin.spare-parts.sales', ['part' => $part->id]) }}"
                                   class="text-gray-600 hover:underline text-sm">
                                    Sales
                                </a>

                                <a href="{{ route('admin.spare-parts.barcode', $part) }}"
                                class="text-gray-600 hover:underline text-sm">
                                    Barcode
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-gray-500">
                                No spare parts found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if(method_exists($spareParts, 'links'))
            <div class="px-4 py-3 border-t">
                {{ $spareParts->links() }}
            </div>
        @endif

    </div>
</div>
@endsection
