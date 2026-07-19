<div id="product-{{ $product->id }}" class="scroll-mt-[120px] bg-white rounded-lg overflow-hidden shadow hover:shadow-lg transition-all duration-300 flex flex-col product-card">

    @php
        // Determine which image to display
        $displayImage = $product->image 
            ?: data_get($product->gallery, '0');
        $stockLabel = $product->is_used === 1 ? 'Sold' : 'Out of Stock';
    @endphp

    @php
        $returnUrl = request()->routeIs('shop.ajax')
            ? route('shop.index') . (request()->getQueryString() ? ('?' . request()->getQueryString()) : '')
            : url()->full();
    @endphp

    <a href="{{ route('product.show', $product->slug) }}">
        @if($displayImage)
            <img src="{{ asset('storage/' . $displayImage) }}"
                 class="w-full h-64 object-contain mb-3"
                 alt="{{ $product->name }}"
                 loading="lazy"
                 decoding="async">
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
            KWD {{ number_format($product->price, 2) }}
        </span>

        @if($product->stock > 0)
            <a href="{{ route('cart.add', [
                'id' => $product->id,
                'return' => $returnUrl,
                'anchor' => 'product-' . $product->id,
            ]) }}"
               class="bg-green-600 text-white text-sm px-3 py-1 rounded hover:bg-green-700">
                Add to Cart
            </a>
        @else
            <span class="bg-gray-200 text-gray-700 text-sm px-3 py-1 rounded font-semibold">
                {{ $stockLabel }}
            </span>
        @endif
    </div>

</div>
