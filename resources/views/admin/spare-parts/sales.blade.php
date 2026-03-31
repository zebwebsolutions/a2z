@extends('admin.layouts.app')

@section('content')
<div class="container mx-auto px-6 py-6">

    {{-- Page Header --}}
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold text-gray-800">Spare Parts Sales</h1>

        <a href="{{ route('admin.spare-parts.index') }}"
           class="text-sm text-gray-600 hover:underline">
            ← Back to Spare Parts
        </a>
    </div>

    {{-- Card --}}
    <div class="bg-white rounded shadow">

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr class="text-left text-gray-600 uppercase text-xs tracking-wider">
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Spare Part</th>
                        <th class="px-4 py-3">Barcode</th>
                        <th class="px-4 py-3">Salesman</th>
                        <th class="px-4 py-3">Qty</th>
                        <th class="px-4 py-3">Unit Price</th>
                        <th class="px-4 py-3">Total</th>
                    </tr>
                </thead>

                <tbody class="divide-y">
                    @forelse($sales as $sale)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-gray-600">
                                {{ $sale->sold_at->format('d M Y, H:i') }}
                            </td>

                            <td class="px-4 py-3 font-medium">
                                {{ $sale->sparePart->name }}
                            </td>

                            <td class="px-4 py-3 font-mono text-xs">
                                {{ $sale->sparePart->barcode }}
                            </td>

                            <td class="px-4 py-3">
                                {{ $sale->user->name }}
                            </td>

                            <td class="px-4 py-3 font-semibold">
                                {{ $sale->quantity }}
                            </td>

                            <td class="px-4 py-3">
                                {{ number_format($sale->unit_price, 2) }}
                            </td>

                            <td class="px-4 py-3 font-semibold text-green-700">
                                {{ number_format($sale->total_price, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7"
                                class="px-4 py-6 text-center text-gray-500">
                                No spare part sales found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="px-4 py-3 border-t">
            {{ $sales->links() }}
        </div>

    </div>
</div>
@endsection