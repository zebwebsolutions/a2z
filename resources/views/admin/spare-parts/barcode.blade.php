@extends('admin.layouts.app')

@section('content')
<div class="container mx-auto px-6 py-6">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6 print:hidden">
        <h1 class="text-xl font-semibold text-gray-800">Barcode</h1>

        <div class="space-x-2">
            <button onclick="window.print()"
                    class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                Print
            </button>

            <a href="{{ route('admin.spare-parts.edit', $sparePart) }}"
               class="px-4 py-2 border rounded text-gray-700">
                Back
            </a>
        </div>
    </div>

    {{-- Barcode Card --}}
    <div id="barcode-print-area" class="max-w-md mx-auto bg-white rounded shadow p-6 text-center">

        <h2 class="text-lg font-semibold mb-2">
            {{ $sparePart->name }}
        </h2>

        @if($sparePart->sku)
            <p class="text-sm text-gray-500 mb-2">
                SKU: {{ $sparePart->sku }}
            </p>
        @endif

        <div class="my-4 flex justify-center">
            <img
                src="data:image/png;base64,{{ DNS1D::getBarcodePNG($sparePart->barcode, 'C128') }}"
                alt="Barcode"
                class="mx-auto"
            />
        </div>

        <p class="font-mono text-sm tracking-wide">
            {{ $sparePart->barcode }}
        </p>

        <p class="mt-2 text-sm font-semibold">
            Price: {{ number_format($sparePart->selling_price, 2) }}
        </p>
    </div>
</div>
@endsection