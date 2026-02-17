@extends('layouts.app')

@section('title', 'Shop | A2Z')

@section('content')

<div
    class="container mx-auto px-3 md:px-4 lg:px-6 py-10 grid grid-cols-1 md:grid-cols-4 gap-8"
    x-data="{
        filtersOpen: {{ request()->anyFilled(['category','ram','processor','screen_size','min_price','max_price']) ? 'true' : 'false' }},
        isDesktop: window.matchMedia('(min-width: 768px)').matches,
        open: {
            category: true,
            ram: true,
            storage: true,
            screen: true,
            price: true
        },
        init() {
            const mq = window.matchMedia('(min-width: 768px)');
            const sync = () => this.isDesktop = mq.matches;
            sync();
            mq.addEventListener('change', sync);
        },
    }"
>

    {{-- Mobile Filter Toggle --}}
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
            @if(request()->anyFilled(['category','ram','processor','screen_size','min_price','max_price']))
                <span class="text-xs px-2 py-1 rounded-full bg-blue-100 text-blue-700">Applied</span>
            @endif
        </button>
    </div>

    {{-- Mobile Backdrop --}}
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

    {{-- ⭐ Premium Sidebar --}}
    <aside
        x-show="isDesktop || filtersOpen"
        x-cloak
        class="fixed top-0 left-0 z-50 h-full w-[86vw] max-w-sm overflow-y-auto bg-white p-5 shadow-2xl border-r border-gray-200 rounded-r-2xl md:rounded-xl md:border md:shadow md:sticky md:top-24 md:h-fit md:w-auto md:max-w-none md:col-span-1"
        x-transition:enter="transform transition ease-out duration-300"
        x-transition:enter-start="-translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transform transition ease-in duration-200"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="-translate-x-full"
    >

        {{-- Header --}}
        <div class="flex justify-between items-center mb-5">
            <h2 class="text-2xl font-bold text-gray-800">Filters</h2>
            <div class="flex items-center gap-3">
                @if(request()->anyFilled(['category','ram','processor','screen_size','min_price','max_price']))
                    <a href="{{ route('shop.index') }}" 
                       class="text-sm text-red-500 hover:underline">
                       Clear All
                    </a>
                @endif
                <button type="button" @click="filtersOpen = false" class="md:hidden text-sm text-gray-500 hover:text-gray-700">
                    Close
                </button>
            </div>
        </div>

        <form id="filterForm" method="GET" action="{{ route('shop.index') }}" class="space-y-8">

            {{-- CATEGORY --}}
            <div>
                <button type="button" 
                        @click="open.category = !open.category"
                        class="flex justify-between w-full text-left font-semibold text-gray-700">
                    Category
                    <span x-text="open.category ? '-' : '+'"></span>
                </button>

                <div x-show="open.category" class="mt-2 space-y-1 pl-1">
                    <select name="category" 
                            class="w-full border p-2 rounded-md bg-white">
                        <option value="">All</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->slug }}" 
                                {{ request('category') === $category->slug ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- RAM --}}
            <div>
                <button type="button" 
                        @click="open.ram = !open.ram"
                        class="flex justify-between w-full text-left font-semibold text-gray-700">
                    RAM
                    <span x-text="open.ram ? '-' : '+'"></span>
                </button>

                <div x-show="open.ram" class="mt-2 pl-1 space-y-1">
                    @foreach($ramOptions as $ram)
                        @if(!empty($ram))
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="ram[]" value="{{ $ram }}"
                                   {{ in_array($ram, (array) request('ram')) ? 'checked' : '' }}
                                   class="rounded text-blue-600">
                            {{ $ram }}
                        </label>
                        @endif
                    @endforeach
                </div>
            </div>

            {{-- STORAGE (Processor in your file) --}}
            <div>
                <button type="button" 
                        @click="open.storage = !open.storage"
                        class="flex justify-between w-full text-left font-semibold text-gray-700">
                    Storage
                    <span x-text="open.storage ? '-' : '+'"></span>
                </button>

                <div x-show="open.storage" class="mt-2 pl-1 space-y-1">
                    @foreach($processors as $processor)
                        @if(!empty($processor))
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="processor[]" value="{{ $processor }}"
                                   {{ in_array($processor, (array) request('processor')) ? 'checked' : '' }}
                                   class="rounded text-blue-600">
                            {{ $processor }}
                        </label>
                        @endif
                    @endforeach
                </div>
            </div>

            {{-- SCREEN SIZE --}}
            <div>
                <button type="button" 
                        @click="open.screen = !open.screen"
                        class="flex justify-between w-full text-left font-semibold text-gray-700">
                    Screen Size
                    <span x-text="open.screen ? '-' : '+'"></span>
                </button>

                <div x-show="open.screen" class="mt-2 pl-1 space-y-1">
                    @foreach($screenSizes as $screenSize)
                        @if(!empty($screenSize))
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="screen_size[]" value="{{ $screenSize }}"
                                   {{ in_array($screenSize, (array) request('screen_size')) ? 'checked' : '' }}
                                   class="rounded text-blue-600">
                            {{ $screenSize }}
                        </label>
                        @endif
                    @endforeach
                </div>
            </div>

            {{-- PRICE SLIDER --}}
            <div>
                <button type="button" 
                        @click="open.price = !open.price"
                        class="flex justify-between w-full text-left font-semibold text-gray-700">
                    Price
                    <span x-text="open.price ? '-' : '+'"></span>
                </button>

                <div x-show="open.price" class="mt-3">

                    <input type="range" 
                           name="max_price" 
                           min="0" 
                           max="1000"
                           value="{{ request('max_price', 1000) }}"
                           class="w-full accent-blue-600">

                    <p class="text-gray-600 text-sm mt-1">
                        Up to: <strong>{{ request('max_price', 1000) }} KD</strong>
                    </p>
                </div>
            </div>

            {{-- Active Filter Chips --}}
            @if(request()->anyFilled(['category','ram','processor','screen_size','min_price','max_price']))
                <div class="flex flex-wrap gap-2 mt-2">

                    @foreach(request()->except('page') as $key => $values)
                        @if(is_array($values))
                            @foreach($values as $val)
                            <a href="{{ request()->fullUrlWithoutQuery("$key.$loop->index") }}"
                                class="px-3 py-1 bg-blue-100 border border-blue-200 text-blue-800 rounded-full text-xs flex items-center gap-1">
                                {{ ucfirst(str_replace('_',' ', $key)) }}: {{ $val }} ✖
                            </a>
                            @endforeach
                        @endif
                    @endforeach

                </div>
            @endif

            <!-- <button type="submit" 
                class="bg-blue-600 text-white px-4 py-2 rounded-lg w-full hover:bg-blue-700 transition">
                Apply Filters
            </button> -->

        </form>
    </aside>

    {{-- ⭐ Product Grid --}}
    <div class="md:col-span-3">
        <h2 class="text-2xl font-bold mb-6">Shop Products</h2>

        @if($products->count() > 0)
            <div id="products-wrapper">
                @include('front.products.partials.product-grid')
                @include('front.products.partials.pagination')
            </div>
        @else
            <p class="text-center text-gray-600">No products found.</p>
        @endif
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', () => {

    const form = document.querySelector('#filterForm');
    const wrapper = document.querySelector('#products-wrapper');
    if (!form || !wrapper) return;

    // toggle if you want spinner overlay in addition to skeletons
    const useSpinner = false;

    // build skeleton markup programmatically for N cards
    function buildSkeletonGrid(count = 8) {
        let cards = '';
        for (let i = 0; i < count; i++) {
            cards += `
            <div class="space-y-3 animate-pulse" aria-hidden="true">
                <div class="skeleton skeleton-img w-full"></div>
                <div class="flex justify-between items-center">
                    <div class="w-3/4">
                        <div class="skeleton skeleton-title w-full mb-2"></div>
                        <div class="skeleton skeleton-text w-2/3 mb-1"></div>
                    </div>
                    <div class="w-1/4">
                        <div class="skeleton skeleton-text w-full"></div>
                    </div>
                </div>
            </div>`;
        }
        return `<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4 gap-6">${cards}</div>`;
    }

    // optional spinner overlay
    function spinnerOverlayHtml() {
        return `
        <div id="ajax-spinner-overlay" class="fixed inset-0 flex items-center justify-center z-50 pointer-events-none">
            <div class="bg-white/60 backdrop-blur-sm p-6 rounded-md shadow">
                <svg class="animate-spin h-8 w-8 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                </svg>
            </div>
        </div>`;
    }

    let timeout;
    form.addEventListener('change', applyFilters);
    form.addEventListener('input', (e) => {
        // ignore text inputs rapid typing if wanted; still debounced
        applyFilters();
    });

    async function applyFilters() {
        clearTimeout(timeout);
        timeout = setTimeout(async () => {
            const params = new URLSearchParams(new FormData(form)).toString();
            const url = "{{ route('shop.ajax') }}?" + params;

            // set ARIA busy
            wrapper.setAttribute('aria-busy', 'true');

            // show skeletons
            wrapper.innerHTML = buildSkeletonGrid(8);

            // optional spinner
            if (useSpinner) {
                if (!document.querySelector('#ajax-spinner-overlay')) {
                    document.body.insertAdjacentHTML('beforeend', spinnerOverlayHtml());
                }
            }

            try {
                const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }});
                if (!res.ok) throw new Error('Network response was not ok');

                const data = await res.json();

               // always show skeleton for at least 400ms (so users can see it)
                const MIN_SKELETON_TIME = 400;
                const now = performance.now();

                const applyResult = () => {
                    wrapper.style.opacity = 0;

                    setTimeout(() => {
                        wrapper.innerHTML = data.html + data.pagination;
                        wrapper.style.transition = 'opacity 220ms ease';
                        wrapper.style.opacity = 1;
                    }, 80);
                };

                const elapsed = performance.now() - now;

                // if response was too fast → wait the remaining time
                if (elapsed < MIN_SKELETON_TIME) {
                    setTimeout(applyResult, MIN_SKELETON_TIME - elapsed);
                } else {
                    applyResult();
                }


                // update URL
                window.history.replaceState({}, '', "{{ route('shop.index') }}?" + params);

            } catch (err) {
                console.error('AJAX filter error', err);
                wrapper.innerHTML = `<div class="py-20 text-center text-sm text-red-600">Failed to load results. Please try again.</div>`;
            } finally {
                // remove spinner overlay if present
                const spinner = document.querySelector('#ajax-spinner-overlay');
                if (spinner) spinner.remove();

                wrapper.removeAttribute('aria-busy');
            }
        }, 300); // debounce delay
    }

});
</script>

@endsection
