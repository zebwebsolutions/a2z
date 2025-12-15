@props(['category', 'brands', 'subcategories'])

<aside class="w-64 bg-white border rounded p-4 h-fit">

    {{-- Brand Filter --}}
    @if($brands->count())
        <h3 class="font-bold text-lg mb-2">Brands</h3>
        <ul class="space-y-1 mb-4">
            @foreach($brands as $brand)
                <li>
                    <a href="{{ request()->fullUrlWithQuery(['brand' => $brand->slug]) }}"
                       class="block px-2 py-1 rounded hover:bg-gray-100
                              {{ request('brand') == $brand->slug ? 'bg-blue-100 font-semibold' : '' }}">
                        {{ $brand->name }}
                    </a>
                </li>
            @endforeach
        </ul>
    @endif

    {{-- Subcategories (useful for Accessories) --}}
    @if($subcategories->count())
        <h3 class="font-bold text-lg mb-2">Categories</h3>
        <ul class="space-y-1 mb-4">
            @foreach($subcategories as $sub)
                <li>
                    <a href="{{ route('category.show', $sub->slug) }}"
                       class="block px-2 py-1 rounded hover:bg-gray-100">
                        {{ $sub->name }}
                    </a>
                </li>
            @endforeach
        </ul>
    @endif

    {{-- Price Filter --}}
    <h3 class="font-bold text-lg mb-2">Price Range</h3>
    <form method="GET" action="">
        <div class="flex gap-2">
            <input type="number" name="min" placeholder="Min"
                   value="{{ request('min') }}"
                   class="border rounded p-1 w-full">
            <input type="number" name="max" placeholder="Max"
                   value="{{ request('max') }}"
                   class="border rounded p-1 w-full">
        </div>

        <button class="mt-3 w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700">
            Apply
        </button>
    </form>

    {{-- Specs Filters (example RAM & Storage) --}}
    <h3 class="font-bold text-lg mt-6 mb-2">Specifications</h3>

    <form method="GET" action="" class="space-y-3">

        <div>
            <label class="block text-sm">RAM</label>
            <select name="ram" class="border p-1 rounded w-full">
                <option value="">Any</option>
                <option value="4GB" {{ request('ram') == '4GB' ? 'selected' : '' }}>4GB</option>
                <option value="6GB" {{ request('ram') == '6GB' ? 'selected' : '' }}>6GB</option>
                <option value="8GB" {{ request('ram') == '8GB' ? 'selected' : '' }}>8GB</option>
                <option value="12GB" {{ request('ram') == '12GB' ? 'selected' : '' }}>12GB</option>
            </select>
        </div>

        <div>
            <label class="block text-sm">Storage</label>
            <select name="storage" class="border p-1 rounded w-full">
                <option value="">Any</option>
                <option value="64GB" {{ request('storage') == '64GB' ? 'selected' : '' }}>64GB</option>
                <option value="128GB" {{ request('storage') == '128GB' ? 'selected' : '' }}>128GB</option>
                <option value="256GB" {{ request('storage') == '256GB' ? 'selected' : '' }}>256GB</option>
            </select>
        </div>

        <button class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700">
            Apply Specs
        </button>
    </form>

    {{-- Clear Filters --}}
    @if(request()->query())
        <a href="{{ route('category.show', $category->slug) }}"
           class="block mt-4 text-center bg-gray-200 py-2 rounded hover:bg-gray-300">
            Clear Filters
        </a>
    @endif

</aside>