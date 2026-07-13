@extends('layouts.app')

@section('title', $product->name . ' | A2Z')

@section('content')
<div class="container mx-auto px-3 md:px-4 lg:px-6 py-10 grid grid-cols-1 lg:grid-cols-2 gap-10">

    {{-- Product Images --}}
    <div>
        <img 
            src="{{ $product->image_url }}" 
            alt="{{ $product->name }}"
            class="w-full rounded-xl shadow"
        />
    </div>

    {{-- Product Info --}}
    <div>
        {{-- Condition Badge --}}
        @if($product->is_used === 1)
            <span class="inline-block mb-3 px-3 py-1 text-sm font-semibold rounded bg-yellow-100 text-yellow-800">
                Used Device
            </span>
        @elseif($product->condition === 'refurbished')
            <span class="inline-block mb-3 px-3 py-1 text-sm font-semibold rounded bg-blue-100 text-blue-800">
                Refurbished Device
            </span>
        @endif

        <h1 class="text-3xl font-bold text-gray-900 mb-4">
            {{ $product->name }}
        </h1>

        <p class="text-2xl font-semibold text-green-600 mb-6">
            KD {{ number_format($product->price, 2) }}
        </p>

        {{-- Used Device Details --}}
        @if(in_array($product->condition, ['used','refurbished']) && $product->usedDeviceDetails)
            <div class="bg-gray-50 border rounded-xl p-6 mb-6">
                <h3 class="text-lg font-semibold mb-4">
                    Device Details
                </h3>

                <ul class="space-y-2 text-gray-700">
                    @if($product->usedDeviceDetails->battery_health)
                        <li>
                            🔋 Battery Health:
                            <strong>{{ $product->usedDeviceDetails->battery_health }}%</strong>
                        </li>
                    @endif

                    <li>
                        📦 Box:
                        <strong>{{ $product->usedDeviceDetails->box_available ? 'Included' : 'Not Included' }}</strong>
                    </li>

                    <li>
                        🔌 Charger:
                        <strong>{{ $product->usedDeviceDetails->charger_available ? 'Included' : 'Not Included' }}</strong>
                    </li>

                    <li>
                        🔗 Cable:
                        <strong>{{ $product->usedDeviceDetails->cable_available ? 'Included' : 'Not Included' }}</strong>
                    </li>

                    <li>
                        🎧 Headphones:
                        <strong>{{ $product->usedDeviceDetails->headphones_available ? 'Included' : 'Not Included' }}</strong>
                    </li>

                    <li>
                        🛡 Warranty:
                        <strong>{{ $product->usedDeviceDetails->warranty_days }} days</strong>
                    </li>

                    @if($product->usedDeviceDetails->imei)
                        <li>
                            📱 IMEI:
                            <strong>{{ $product->usedDeviceDetails->imei }}</strong>
                            @if($product->usedDeviceDetails->imei_verified)
                                <span class="text-green-600 ml-2">(Verified)</span>
                            @endif
                        </li>
                    @endif
                </ul>
            </div>
        @endif

        {{-- Add to Cart --}}
        @if($product->stock > 0)
            <a href="{{ route('cart.add', [
                'id' => $product->id,
                'return' => url()->full(),
            ]) }}" class="block w-full bg-black text-center text-white py-3 rounded-xl font-semibold hover:bg-gray-800 transition">
                Add to Cart
            </a>
        @else
            <span class="block w-full bg-gray-200 text-center text-gray-700 py-3 rounded-xl font-semibold">
                {{ $product->is_used === 1 ? 'Sold' : 'Out of Stock' }}
            </span>
        @endif
    </div>

</div>
@endsection
