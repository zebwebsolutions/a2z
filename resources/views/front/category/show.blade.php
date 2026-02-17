<x-breadcrumb :items="$breadcrumbItems" />

<div
    class="container mx-auto px-4 py-8 grid grid-cols-1 md:grid-cols-4 gap-6"
    x-data="{
        filtersOpen: {{ request()->query() ? 'true' : 'false' }},
        isDesktop: window.matchMedia('(min-width: 768px)').matches,
        init() {
            const mq = window.matchMedia('(min-width: 768px)');
            const sync = () => this.isDesktop = mq.matches;
            sync();
            mq.addEventListener('change', sync);
        }
    }"
>
    <div class="md:hidden">
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
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
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
