<div class="bg-white rounded-lg overflow-hidden shadow hover:shadow-lg transition-all duration-300 flex flex-col product-card">

    @php
        // Determine which image to display
        $displayImage = $product->image 
            ?: ($product->gallery[0] ?? null);
    @endphp

    <a href="{{ route('product.show', $product->slug) }}">
        @if($displayImage)
            <img src="{{ asset('storage/' . $displayImage) }}"
                 class="w-full h-64 object-contain mb-3"
                 alt="{{ $product->name }}">
        @else
            <div class="w-full h-64 bg-gray-200 flex items-center justify-center text-gray-500">
                No Image
            </div>
        @endif
    </a>

    <h2 class="text-lg font-semibold mb-1 p-2">
        <a href="{{ route('product.show', $product->slug) }}" class="hover:text-blue-600">
            {{ $product->name }}
        </a>
    </h2>

    {{-- BOTTOM SECTION LOCKED TO BOTTOM --}}
    <div class="flex justify-between items-center p-2 mt-auto">
        <span class="text-blue-600 font-semibold">
            KWD{{ number_format($product->price, 2) }}
        </span>

        <a href="{{ route('cart.add', $product->id) }}"
           class="bg-green-600 text-white text-sm px-3 py-1 rounded hover:bg-green-700">
            Add to Cart
        </a>
    </div>

</div>