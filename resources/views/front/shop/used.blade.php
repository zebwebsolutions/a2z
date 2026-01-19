@extends('layouts.app')

@section('title', 'Used Devices in Kuwait | LifeStyleQ8')

@section('content')

<div class="container mx-auto px-6 py-10">

    {{-- Page Header --}}
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">
            Used Devices
        </h1>
        <p class="text-gray-600 mt-2">
            Affordable used phones and devices, fully tested and ready to use.
        </p>
    </div>

    <div id="filterChips" class="flex flex-wrap gap-2 mb-4">
      @include('components.filter-chips', [
          'category' => $category,
          'availableBrands' => $availableBrands,
          'priceMin' => $priceMin,
          'priceMax' => $priceMax,
          'availableRAM' => $availableRAM,
          'availableStorage' => $availableStorage,
          'subcategories' => $subcategories,
      ])
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">

        {{-- SIDEBAR --}}
        <div class="md:col-span-1 relative">
            <x-category-sidebar 
                :category="$category"
                :availableBrands="$availableBrands"
                :priceMin="$priceMin"
                :priceMax="$priceMax"
                :batteryMin="$batteryMin"
                :batteryMax="$batteryMax"
                :availableRAM="$availableRAM"
                :availableStorage="$availableStorage"
                :subcategories="$subcategories"
            />
        </div>

        <div class="md:col-span-3">

            {{-- Skeleton Loader --}}
            <div id="productSkeleton" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6 hidden">
                {{-- Will be filled by JavaScript --}}
            </div>

            <div id="productResults">
                {{-- Products Grid --}}
                <div id="productGrid">
                    @include('front.shop.partials.products-grid', ['products' => $products])
                </div>

                <div id="paginationWrapper" class="mt-8">
                    {{ $products->links() }}
                </div>
            </div>
        </div>
    </div>

</div>

<script src="{{ asset('js/filter-engine.js') }}"></script>
<script src="{{ asset('js/preserve-scroll.js') }}"></script>

<script>
function priceSlider(minPrice, maxPrice) {
    return {
        realMin: minPrice,
        realMax: maxPrice,
        min: Number("{{ request('min') ?? $priceMin }}"),
        max: Number("{{ request('max') ?? $priceMax }}"),

        get minPercent() {
            return ((this.min - this.realMin) / (this.realMax - this.realMin)) * 100;
        },
        get maxPercent() {
            return ((this.max - this.realMin) / (this.realMax - this.realMin)) * 100;
        },

        update(handle) {
            // Ensure numbers
            this.min = parseInt(this.min);
            this.max = parseInt(this.max);

            // Crossing logic: Push the other handle if we cross it
            if (this.min > this.max) {
                if (handle === 'min') this.max = this.min;
                if (handle === 'max') this.min = this.max;
            }

            // Dispatch AJAX update
            let qs = `min=${this.min}&max=${this.max}`;
            window.dispatchEvent(new CustomEvent("ajaxFilter", { detail: qs }));
        }
    };
}
</script>

@endsection