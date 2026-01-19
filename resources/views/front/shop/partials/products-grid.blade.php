@if($products->count() > 0)
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
        @foreach($products as $product)
            @include('front.products.partials.product-card', ['product' => $product])
        @endforeach
    </div>
    <div class="mt-8">
        {{-- Render pagination links here so JSON also contains them if needed --}}
        {{ $products->links() }}
    </div>
@else
    <div class="text-center py-8">
        <p class="text-gray-500 text-lg">No products found matching your criteria.</p>
    </div>
@endif