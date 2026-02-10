@extends('layouts.app')

@section('title', $brand->name . ' Products | A2Z')

@section('content')

<div class="container mx-auto px-4 py-8">

    {{-- BREADCRUMB --}}
    <x-breadcrumb :items="$breadcrumbItems" />

    {{-- PAGE TITLE --}}
    <h1 class="text-3xl font-bold mb-6">
        {{ $brand->name }}
    </h1>

    {{-- FILTER CHIPS --}}
    <div id="filterChips" class="flex flex-wrap gap-2 mb-4">
        @include('components.filter-chips', [
            'brand' => $brand,
            'availableBrands' => $availableBrands,
            'priceMin' => $priceMin,
            'priceMax' => $priceMax,
            'availableRAM' => $availableRAM,
            'availableStorage' => $availableStorage,
            'subcategories' => $subcategories,
        ])
    </div>

    {{-- MAIN GRID --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">

        {{-- SIDEBAR --}}
        <div class="md:col-span-1">
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

        {{-- PRODUCTS --}}
        <div class="md:col-span-3 relative">

            {{-- Skeleton --}}
            <div id="productSkeleton"
                 class="inset-0 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6 hidden z-30">
            </div>

            {{-- Results --}}
            <div id="productResults" class="transition-opacity duration-500 relative z-10">
                @if($products->count() > 0)

                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
                        @foreach($products as $product)
                            @include('front.products.partials.product-card', [
                                'product' => $product
                            ])
                        @endforeach
                    </div>

                    <div id="paginationWrapper" class="mt-8">
                        {{ $products->links() }}
                    </div>

                @else
                    <div class="text-gray-500 mt-6 p-4 bg-gray-50 rounded text-center">
                        No products found for {{ $brand->name }}.
                    </div>
                @endif
            </div>

        </div>
    </div>
</div>

{{-- GLOBAL FILTER CONFIG --}}
<script>
    window.LSQ8_priceMin = {!! json_encode($priceMin) !!};
    window.LSQ8_priceMax = {!! json_encode($priceMax) !!};
    window.LSQ8_baseUrl  = {!! json_encode(route('brand.index', $brand->slug)) !!};
</script>

<script src="{{ asset('js/filter-engine.js') }}"></script>

{{-- PRICE SLIDER --}}
<script>
function priceSlider(minPrice, maxPrice) {
    return {
        realMin: minPrice,
        realMax: maxPrice,

        min: Number("{{ request('min', $priceMin) }}") || minPrice,
        max: Number("{{ request('max', $priceMax) }}") || maxPrice,

        get minPercent() {
            return ((this.min - this.realMin) / (this.realMax - this.realMin)) * 100;
        },
        get maxPercent() {
            return ((this.max - this.realMin) / (this.realMax - this.realMin)) * 100;
        },

        update(which) {
            this.min = Math.min(Math.max(this.min, this.realMin), this.realMax);
            this.max = Math.min(Math.max(this.max, this.realMin), this.realMax);

            if (which === 'min' && this.min > this.max) this.min = this.max;
            if (which === 'max' && this.max < this.min) this.max = this.min;

            let qs = `min=${this.min}&max=${this.max}`;
            window.dispatchEvent(new CustomEvent("ajaxFilter", { detail: qs }));
        }
    };
}
</script>

@endsection
