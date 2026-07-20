@extends('layouts.app')

@section('title', $product->name . ' | A2Z')

@section('content')
<div id="product-detail" class="scroll-mt-[120px] container mx-auto px-4 py-8">
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
                @if(!empty($images))
                    <img
                        id="mainImage"
                        src="{{ asset('storage/' . data_get($images, '0')) }}"
                        class="w-full h-100 object-contain rounded border"
                    >
                @else
                    <div class="w-full h-100 rounded border bg-gray-100 flex items-center justify-center text-gray-500">
                        No Image
                    </div>
                @endif
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
            <div>
                @if($product->stock > 0)
                    <span class="inline-block mb-3 px-3 py-1 text-sm font-semibold rounded bg-green-100 text-green-800">
                        In Stock
                    </span>
                @else
                    <span class="inline-block mb-3 px-3 py-1 text-sm font-semibold rounded bg-red-100 text-red-800">
                        Out of Stock
                    </span>
                @endif
            </div>

            <p class="text-2xl font-semibold text-blue-600 mb-6 mt-4">
                KWD {{ number_format($product->price, 2) }}
            </p>

            @if($colourVariants->isNotEmpty())
                <div class="mb-6">
                    <div class="flex items-center justify-between gap-3 mb-3">
                        <h3 class="text-sm font-semibold text-gray-900">Available Colours</h3>
                        <span class="text-sm text-gray-500">{{ $product->specs['COLOUR'] ?? $product->specs['COLOR'] ?? '' }}</span>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        @foreach($colourVariants as $variant)
                            @php
                                $colourClasses = $variant['is_current']
                                    ? 'border-blue-600 bg-blue-50 text-blue-700'
                                    : 'border-gray-200 bg-white text-gray-700 hover:border-blue-300';

                                if (! $variant['is_available']) {
                                    $colourClasses = 'border-gray-200 bg-gray-100 text-gray-400 cursor-not-allowed opacity-50';
                                } elseif (! $variant['is_exact_match']) {
                                    $colourClasses = 'border-amber-300 bg-amber-50 text-amber-800 hover:border-amber-400';
                                } elseif (! $variant['in_stock']) {
                                    $colourClasses .= ' opacity-60';
                                }

                                $colourTitle = $variant['colour'];

                                if (! $variant['is_available']) {
                                    $colourTitle .= ' is not available with the selected storage';
                                } elseif (! $variant['is_exact_match'] && ! empty($variant['fallback_label'])) {
                                    $colourTitle .= ' is available as ' . $variant['fallback_label'];
                                } elseif (! $variant['in_stock']) {
                                    $colourTitle .= ' - Out of stock';
                                }
                            @endphp

                            @if($variant['is_available'])
                                <a
                                    href="{{ $variant['url'] }}"
                                    title="{{ $colourTitle }}"
                                    class="inline-flex items-center gap-2 rounded-full border px-3 py-2 text-sm transition {{ $colourClasses }}"
                                >
                                    <span
                                        class="h-5 w-5 rounded-full border border-gray-300 shadow-sm"
                                        style="background-color: {{ $variant['swatch'] }}"
                                    ></span>
                                    <span>
                                        {{ $variant['colour'] }}
                                        @if(! $variant['is_exact_match'] && ! empty($variant['fallback_label']))
                                            <span class="ml-1 text-xs">({{ $variant['fallback_label'] }})</span>
                                        @endif
                                    </span>
                                </a>
                            @else
                                <span
                                    title="{{ $colourTitle }}"
                                    class="inline-flex items-center gap-2 rounded-full border px-3 py-2 text-sm transition {{ $colourClasses }}"
                                >
                                <span
                                    class="h-5 w-5 rounded-full border border-gray-300 shadow-sm"
                                    style="background-color: {{ $variant['swatch'] }}"
                                ></span>
                                <span>{{ $variant['colour'] }}</span>
                                </span>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif

            @if($storageVariants->isNotEmpty())
                <div class="mb-6">
                    <div class="flex items-center justify-between gap-3 mb-3">
                        <h3 class="text-sm font-semibold text-gray-900">Available Storage</h3>
                        <span class="text-sm text-gray-500">{{ $product->specs['STORAGE CAPACITY'] ?? $product->specs['STORAGE'] ?? '' }}</span>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        @foreach($storageVariants as $variant)
                            @php
                                $storageClasses = $variant['is_current']
                                    ? 'border-blue-600 bg-blue-50 text-blue-700'
                                    : 'border-gray-200 bg-white text-gray-700 hover:border-blue-300';

                                if (! $variant['is_available']) {
                                    $storageClasses = 'border-gray-200 bg-gray-100 text-gray-400 cursor-not-allowed opacity-50';
                                } elseif (! $variant['is_exact_match']) {
                                    $storageClasses = 'border-amber-300 bg-amber-50 text-amber-800 hover:border-amber-400';
                                } elseif (! $variant['in_stock']) {
                                    $storageClasses .= ' opacity-60';
                                }

                                $storageTitle = $variant['storage'];

                                if (! $variant['is_available']) {
                                    $storageTitle .= ' is not available with the selected colour';
                                } elseif (! $variant['is_exact_match'] && ! empty($variant['fallback_label'])) {
                                    $storageTitle .= ' is available in ' . $variant['fallback_label'];
                                } elseif (! $variant['in_stock']) {
                                    $storageTitle .= ' - Out of stock';
                                }
                            @endphp

                            @if($variant['is_available'])
                                <a
                                    href="{{ $variant['url'] }}"
                                    title="{{ $storageTitle }}"
                                    class="inline-flex items-center rounded-full border px-4 py-2 text-sm font-medium transition {{ $storageClasses }}"
                                >
                                    {{ $variant['storage'] }}
                                    @if(! $variant['is_exact_match'] && ! empty($variant['fallback_label']))
                                        <span class="ml-1 text-xs">({{ $variant['fallback_label'] }})</span>
                                    @endif
                                </a>
                            @else
                                <span
                                    title="{{ $storageTitle }}"
                                    class="inline-flex items-center rounded-full border px-4 py-2 text-sm font-medium transition {{ $storageClasses }}"
                                >
                                    {{ $variant['storage'] }}
                                </span>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif

            <a 
            @if($product->stock > 0)
                href="{{ route('cart.add', [
                'id' => $product->id,
                'return' => url()->full(),
                'anchor' => 'product-detail',
                ]) }}"
            @else
                href="#"
            @endif
            class="px-6 py-3 rounded 
                @if($product->stock > 0) 
                bg-green-600 hover:bg-green-700 text-white cursor-pointer
                @else 
                bg-gray-400 text-gray-200 cursor-not-allowed pointer-events-none
                @endif">
            {{ $product->stock > 0 ? 'Add to Cart' : ($product->is_used === 1 ? 'Sold' : 'Out of Stock') }}
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

            <p class="text-gray-700 whitespace-pre-line mb-6">
                {{ $product->description }}
            </p>
            
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
