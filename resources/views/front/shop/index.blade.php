@extends('layouts.app')

@section('title', 'Shop | A2Z')
@section('meta_description', 'Shop new and used phones, tablets, and accessories in Kuwait. Filter by category, specs, and price at A2Z.')

@section('content')

<div
    class="container mx-auto px-3 md:px-4 lg:px-6 py-10"
    x-data="{
        filtersOpen: false,
        isDesktop: window.matchMedia('(min-width: 768px)').matches,
        init() {
            const mq = window.matchMedia('(min-width: 768px)');
            const sync = () => this.isDesktop = mq.matches;
            sync();
            mq.addEventListener('change', sync);
        },
    }"
>

    {{-- Filter Toggle --}}
    <div class="mb-6 md:hidden">
        <button
            type="button"
            @click="filtersOpen = !filtersOpen"
            class="w-full inline-flex items-center justify-between gap-4 px-4 py-3 rounded-lg border border-gray-300 bg-white shadow-sm"
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

    <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
        {{-- Sidebar (same drawer style as category pages, hidden until clicked) --}}
        <aside
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
                :category="$virtualCategory"
                :availableBrands="$availableBrands"
                :priceMin="$priceMin"
                :priceMax="$priceMax"
                :availableRAM="$ramOptions"
                :availableStorage="$storageOptions"
                :subcategories="$categories"
            />
        </aside>

        {{-- ⭐ Product Grid --}}
        <div class="md:col-span-3">
            <h2 class="text-2xl font-bold mb-6">Shop Products</h2>
            <div id="filterChips" class="flex flex-wrap gap-2 mb-4">
                @include('components.filter-chips', [
                    'category' => $virtualCategory,
                    'availableBrands' => $availableBrands,
                    'priceMin' => $priceMin,
                    'priceMax' => $priceMax,
                    'availableRAM' => $ramOptions,
                    'availableStorage' => $storageOptions,
                    'subcategories' => $categories,
                ])
            </div>

            <div id="products-wrapper">
                @include('front.shop.partials.products-grid')
            </div>
        </div>
    </div>

</div>

<script>
function priceSlider(minPrice, maxPrice) {
    return {
        realMin: Number(minPrice),
        realMax: Number(maxPrice),
        min: Number("{{ request('min', request('min_price', $priceMin)) }}") || Number(minPrice),
        max: Number("{{ request('max', request('max_price', $priceMax)) }}") || Number(maxPrice),
        get minPercent() {
            const range = (this.realMax - this.realMin) || 1;
            return ((this.min - this.realMin) / range) * 100;
        },
        get maxPercent() {
            const range = (this.realMax - this.realMin) || 1;
            return ((this.max - this.realMin) / range) * 100;
        },
        update(handle) {
            this.min = Number(this.min);
            this.max = Number(this.max);
            this.min = Math.min(Math.max(this.min, this.realMin), this.realMax);
            this.max = Math.min(Math.max(this.max, this.realMin), this.realMax);

            if (this.min > this.max) {
                if (handle === 'min') this.max = this.min;
                if (handle === 'max') this.min = this.max;
            }

            const qs = `min=${Number(this.min).toFixed(2)}&max=${Number(this.max).toFixed(2)}`;
            window.dispatchEvent(new CustomEvent("ajaxFilter", { detail: qs }));
        }
    };
}

document.addEventListener('DOMContentLoaded', () => {

    const form = document.querySelector('#filterForm');
    const wrapper = document.querySelector('#products-wrapper');
    const chips = document.querySelector('#filterChips');
    const serverPriceMin = Number("{{ $priceMin }}");
    const serverPriceMax = Number("{{ $priceMax }}");
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
    form.addEventListener('change', () => applyFilters());
    form.addEventListener('input', () => applyFilters());

    function buildFilterParams(paramsOverride = null) {
        const params = new URLSearchParams(window.location.search);
        const controlledKeys = [
            'page', 'brand', 'ram', 'storage', 'battery', 'battery[]',
            'min', 'max', 'min_price', 'max_price'
        ];

        controlledKeys.forEach(key => params.delete(key));

        new FormData(form).forEach((value, key) => {
            params.set(key.replace(/\[\]$/, ''), value);
        });

        if (paramsOverride !== null) {
            new URLSearchParams(paramsOverride).forEach((value, key) => {
                params.set(key.replace(/\[\]$/, ''), value);
            });
        }

        if (Number(params.get('min')) === serverPriceMin && Number(params.get('max')) === serverPriceMax) {
            params.delete('min');
            params.delete('max');
        }

        return params.toString();
    }

    async function applyFilters(paramsOverride = null) {
        clearTimeout(timeout);
        timeout = setTimeout(async () => {
            const params = buildFilterParams(paramsOverride);
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
                        if (chips && data.chips !== undefined) {
                            chips.innerHTML = data.chips;
                        }
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

    window.addEventListener('ajaxFilter', (event) => {
        if (!event.detail) return;
        applyFilters(event.detail);
    });

    document.addEventListener('click', (e) => {
        const chip = e.target.closest('[data-remove]');
        if (!chip) return;

        e.preventDefault();
        const type = chip.dataset.remove;

        if (type === 'all') {
            form.querySelectorAll("input[type='checkbox'], input[type='radio']").forEach(el => {
                el.checked = false;
            });

            const minInput = form.querySelector("input[name='min']");
            const maxInput = form.querySelector("input[name='max']");
            if (minInput) minInput.value = serverPriceMin;
            if (maxInput) maxInput.value = serverPriceMax;

            const ranges = form.querySelectorAll('.range-hidden');
            if (ranges[0]) ranges[0].value = serverPriceMin;
            if (ranges[1]) ranges[1].value = serverPriceMax;

            applyFilters('');
            return;
        }

        if (type === 'brand') {
            form.querySelectorAll("input[name='brand']").forEach(el => el.checked = false);
        }
        if (type === 'ram') {
            form.querySelectorAll("input[name='ram']").forEach(el => el.checked = false);
        }
        if (type === 'storage') {
            form.querySelectorAll("input[name='storage']").forEach(el => el.checked = false);
        }
        if (type === 'battery') {
            form.querySelectorAll("input[name='battery'], input[name='battery[]']").forEach(el => el.checked = false);
        }
        if (type === 'price') {
            const minInput = form.querySelector("input[name='min']");
            const maxInput = form.querySelector("input[name='max']");
            if (minInput) minInput.value = serverPriceMin;
            if (maxInput) maxInput.value = serverPriceMax;
            const ranges = form.querySelectorAll('.range-hidden');
            if (ranges[0]) ranges[0].value = serverPriceMin;
            if (ranges[1]) ranges[1].value = serverPriceMax;
        }

        applyFilters();
    });

});
</script>

@endsection
