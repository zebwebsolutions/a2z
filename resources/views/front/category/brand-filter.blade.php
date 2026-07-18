@extends('layouts.app')

@section('title', $brand->name . ' ' . $category->name . ' | A2Z')

@section('content')

<div class="container mx-auto px-4 py-8" x-data="{
    filtersOpen: {{ request()->query() ? 'true' : 'false' }},
    isDesktop: window.matchMedia('(min-width: 768px)').matches,
    init() {
        const mq = window.matchMedia('(min-width: 768px)');
        const sync = () => this.isDesktop = mq.matches;
        sync();
        mq.addEventListener('change', sync);
    }
}">

    {{-- BREADCRUMB --}}
    <x-breadcrumb :items="$breadcrumbItems" />

    {{-- PAGE TITLE --}}
    <h1 class="text-3xl font-bold mb-6">
        {{ $brand->name }} {{ $category->name }}
    </h1>

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

    <div class="md:hidden mb-4">
        <button
            type="button"
            @click="filtersOpen = !filtersOpen"
            class="w-full inline-flex items-center justify-between px-4 py-3 rounded-lg border border-gray-300 bg-white shadow-sm"
        >
            <span class="inline-flex items-center gap-2 font-semibold text-gray-800">
                <i data-lucide="sliders-horizontal" class="w-4 h-4"></i>
                <span x-text="filtersOpen ? 'Hide Filters' : 'Show Filters'"></span>
            </span>
            @if(request()->query())
                <span class="text-xs px-2 py-1 rounded-full bg-blue-100 text-blue-700">Applied</span>
            @endif
        </button>
    </div>

    <div
        x-show="!isDesktop && filtersOpen"
        x-cloak
        @click="filtersOpen = false"
        class="fixed inset-0 bg-black/40 z-40 md:hidden"
        x-transition:enter="transition-opacity ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    ></div>

    {{-- MAIN GRID --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">

        {{-- SIDEBAR --}}
        <div
            x-show="isDesktop || filtersOpen"
            x-cloak
            class="fixed top-0 left-0 z-50 h-full w-[86vw] max-w-sm overflow-y-auto p-3 md:p-0 bg-white shadow-2xl border-r border-gray-200 rounded-r-2xl md:rounded-none md:bg-transparent md:shadow-none md:border-0 md:static md:z-auto md:h-auto md:w-auto md:max-w-none md:overflow-visible md:col-span-1"
            x-transition:enter="transform transition ease-out duration-300"
            x-transition:enter-start="-translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transform transition ease-in duration-200"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="-translate-x-full"
        >
            <div class="md:hidden flex justify-end mb-2">
                <button type="button" @click="filtersOpen = false" class="text-sm text-gray-600">Close</button>
            </div>
            <x-category-sidebar 
                :category="$category"
                :availableBrands="$availableBrands"
                :subcategories="$subcategories"
                :availableRAM="$availableRAM ?? []"
                :availableStorage="$availableStorage ?? []"
                :priceMin="$priceMin ?? 0"
                :priceMax="$priceMax ?? 0"
            />
        </div>

        {{-- PRODUCTS --}}
        <div class="md:col-span-3 relative">

            {{-- Skeleton --}}
            <div id="productSkeleton" class=" absolute inset-0 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 hidden z-30">    
            </div>

            {{-- Results --}}
            <div id="productResults" class="transition-opacity duration-500 relative z-10">
                @if($products->count() > 0)
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($products as $product)
                            @include('front.products.partials.product-card', ['product' => $product])
                        @endforeach
                    </div>
                @else
                    <div class="text-gray-500 mt-6 p-4 bg-gray-50 rounded text-center">
                        No products found for this brand.
                    </div>
                @endif
            </div>

            <div id="paginationWrapper" class="mt-8">
                {{ $products->links() }}
            </div>

        </div>
    </div>
</div>

<script>
    window.LSQ8_priceMin = {!! json_encode($priceMin) !!};
    window.LSQ8_priceMax = {!! json_encode($priceMax) !!};
    window.LSQ8_baseUrl = {!! json_encode(route('brand.category', [
        'category' => $category->slug,
        'brand' => $brand->slug
    ])) !!};
</script>

<script src="{{ asset('js/filter-engine.js') }}"></script>

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
            // Keep values inside range
            this.min = Math.min(Math.max(this.min, this.realMin), this.realMax);
            this.max = Math.min(Math.max(this.max, this.realMin), this.realMax);

            // Prevent crossing (touching allowed)
            if (which === 'min' && this.min > this.max) this.min = this.max;
            if (which === 'max' && this.max < this.min) this.max = this.min;

            // Send AJAX update
            let qs = `min=${this.min}&max=${this.max}`;
            window.dispatchEvent(new CustomEvent("ajaxFilter", { detail: qs }));
        }
    };
}
</script>

@endsection
