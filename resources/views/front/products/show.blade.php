@extends('layouts.app')

@section('title', $product->name . ' | LifeStyleQ8')

@section('content')
<div class="container mx-auto px-4 py-8">
    <x-breadcrumb :items="$breadcrumbItems" />
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        @php
            // Build full gallery array: main image first, then gallery images
            $images = [];

            if ($product->image) {
                $images[] = $product->image;
            }

            if ($product->gallery && is_array($product->gallery)) {
                foreach ($product->gallery as $img) {
                    $images[] = $img;
                }
            }
        @endphp

        <div class="grid grid-cols-1 md:grid-cols-5 gap-2">

            {{-- THUMBNAILS --}}
            <div class="flex md:flex-col gap-2">

                @foreach($images as $index => $img)
                    <img 
                        src="{{ asset('storage/' . $img) }}" 
                        class="w-24 h-24 object-cover rounded cursor-pointer border hover:border-blue-500 thumb"
                        data-index="{{ $index }}"
                    >
                @endforeach

            </div>

            {{-- MAIN IMAGE --}}
            <div class="md:col-span-4">
                <img 
                    id="mainImage"
                    src="{{ asset('storage/' . $images[0]) }}"
                    class="w-full h-100 object-contain rounded border"
                >
            </div>

        </div>


        {{-- Details --}}
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
            <h1 class="text-3xl font-bold mb-2">{{ $product->name }}</h1>
            <p class="text-gray-500 mb-4">Category: {{ $product->category->name ?? 'Uncategorized' }}</p>
            
            <p class="text-gray-700 whitespace-pre-line mb-6">
                {{ $product->description }}
            </p>

            <p class="text-2xl font-semibold text-blue-600 mb-6">
                ${{ number_format($product->price, 2) }}
            </p>

            <a href="{{ route('cart.add', $product->id) }}" class="bg-green-600 text-white px-6 py-3 rounded hover:bg-green-700">
                Add to Cart
            </a>

            @if($product->specs)
            <div class="mt-6">
                <h3 class="text-xl font-bold mb-3">Specifications</h3>
                <ul class="list-disc pl-6">
                    @foreach($product->specs as $key => $value)
                        <li><strong>{{ $key }}:</strong> {{ $value }}</li>
                    @endforeach
                </ul>
            </div>
            @endif
            <p class="text-gray-500 text-sm mt-6">SKU: <span class="font-semibold">{{ $product->sku }}</span></p>
        </div>
    </div>

    {{-- Related Products --}}
    @if($relatedProducts->count() > 0)
        <h2 class="text-2xl font-bold mt-12 mb-6">Related Products</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6">
            @foreach($relatedProducts as $related)
                @include('front.products.partials.product-card', ['product' => $related])
            @endforeach
        </div>
    @endif
</div>
@endsection

<script>
document.addEventListener("DOMContentLoaded", function () {

    const mainImage = document.getElementById("mainImage");

    document.querySelectorAll(".thumb").forEach(thumb => {
        thumb.addEventListener("click", function () {
            mainImage.src = this.src;

            // highlight selected thumb
            document.querySelectorAll(".thumb").forEach(t => t.classList.remove("border-blue-500"));
            this.classList.add("border-blue-500");
        });
    });

});
</script>

