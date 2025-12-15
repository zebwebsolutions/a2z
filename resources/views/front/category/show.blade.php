<x-breadcrumb :items="$breadcrumbItems" />

<div class="container mx-auto px-4 py-8 grid grid-cols-1 md:grid-cols-4 gap-6">

    {{-- SIDEBAR --}}
    <div class="hidden md:block">
      <x-category-sidebar 
          :category="$category"
          :availableBrands="$availableBrands" 
          :priceMin="$priceMin" 
          :priceMax="$priceMax"
          :availableRAM="$availableRAM"
          :availableStorage="$availableStorage"
          :subcategories="$subcategories"
      />
    </div>

    {{-- PRODUCTS LIST --}}
    <div class="md:col-span-3">

        <h1 class="text-3xl font-bold mb-6">{{ $category->name }}</h1>

        @if($products->count())
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
                @foreach($products as $product)
                    @include('front.products.partials.product-card', ['product' => $product])
                @endforeach
            </div>

            <div class="mt-8">
                {{ $products->links() }}
            </div>

        @else
            <p class="text-gray-500 mt-6">No products match your filters.</p>
        @endif

    </div>

</div>
