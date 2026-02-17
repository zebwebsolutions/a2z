@extends('layouts.app')

@section('title', 'Products | A2Z')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-6 text-center">All Products</h1>

    {{-- Filters --}}
    <form method="GET" action="{{ route('shop.index') }}" class="bg-white p-4 rounded shadow mb-8 grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <label class="block font-semibold mb-1">Category</label>
            <select name="category" class="border p-2 w-full rounded">
                <option value="">All Categories</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block font-semibold mb-1">Store</label>
            <select name="store" class="border p-2 w-full rounded">
                <option value="">All Stores</option>
                @foreach($stores as $store)
                    <option value="{{ $store->id }}" {{ request('store') == $store->id ? 'selected' : '' }}>{{ $store->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block font-semibold mb-1">Price Range</label>
            <div class="flex gap-2">
                <input type="number" name="min_price" value="{{ request('min_price') }}" placeholder="Min" class="border p-2 w-1/2 rounded">
                <input type="number" name="max_price" value="{{ request('max_price') }}" placeholder="Max" class="border p-2 w-1/2 rounded">
            </div>
        </div>

        <div>
            <label class="block font-semibold mb-1">Sort By</label>
            <select name="sort" class="border p-2 w-full rounded">
                <option value="">Latest</option>
                <option value="price_asc" {{ request('sort') == 'price_asc' ? 'selected' : '' }}>Price: Low to High</option>
                <option value="price_desc" {{ request('sort') == 'price_desc' ? 'selected' : '' }}>Price: High to Low</option>
            </select>
        </div>

        {{-- RAM Filter --}}
        <select name="ram" class="border p-2">
            <option value="">RAM</option>
            @foreach($ramOptions as $ram)
                <option value="{{ $ram }}" {{ request('ram') == $ram ? 'selected' : '' }}>{{ $ram }}</option>
            @endforeach
        </select>

        {{-- Storage Filter --}}
        <select name="storage" class="border p-2">
            <option value="">Storage</option>
            @foreach($storageOptions as $storage)
                <option value="{{ $storage }}" {{ request('storage') == $storage ? 'selected' : '' }}>{{ $storage }}</option>
            @endforeach
        </select>

        {{-- Color Filter --}}
        <select name="color" class="border p-2">
            <option value="">Color</option>
            @foreach($colorOptions as $color)
                <option value="{{ $color }}" {{ request('color') == $color ? 'selected' : '' }}>{{ $color }}</option>
            @endforeach
        </select>

        <div class="md:col-span-4 flex justify-end">
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                Apply Filters
            </button>
        </div>
    </form>

    {{-- Product Grid --}}
    @if($products->count() > 0)
        <div id="product-wrapper" aria-live="polite">
            @include('front.products.partials.product-grid')
            @include('front.products.partials.pagination')
        </div>
    @else
        <p class="text-center text-gray-600">No products found.</p>
    @endif
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {

    const form = document.querySelector('#filter-form');
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

                // fade-in transition
                wrapper.style.opacity = 0;
                setTimeout(() => {
                    wrapper.innerHTML = data.html + data.pagination;
                    wrapper.style.transition = 'opacity 220ms ease';
                    wrapper.style.opacity = 1;
                }, 80);

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

