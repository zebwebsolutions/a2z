@extends('layouts.app')

@section('title', 'Shop Electronics in Kuwait | A2Z')

@section('meta_description',
    'Shop electronics, smartphones, accessories and repair services in Kuwait. Best prices, original products and fast delivery from A2Z.'
)

@section('og_title', 'Shop Electronics in Kuwait | A2Z')

@section('og_description',
    'Browse our full range of electronics, smartphones and accessories in Kuwait. Genuine products and expert service.'
)

@section('og_image', asset('favicon.png'))


@section('content')
<div class="container mx-auto px-3 md:px-4 lg:px-6 py-8">
    <h1 class="text-3xl font-bold mb-6">All Products</h1>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <aside class="md:col-span-1">
            <form method="GET" action="{{ route('products.index') }}" class="bg-white p-4 rounded-lg shadow space-y-4 md:sticky md:top-24">
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

                <div>
                    <label class="block font-semibold mb-1">RAM</label>
                    <select name="ram" class="border p-2 w-full rounded">
                        <option value="">All</option>
                        @foreach($ramOptions as $ram)
                            <option value="{{ $ram }}" {{ request('ram') == $ram ? 'selected' : '' }}>{{ $ram }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block font-semibold mb-1">Storage</label>
                    <select name="storage" class="border p-2 w-full rounded">
                        <option value="">All</option>
                        @foreach($storageOptions as $storage)
                            <option value="{{ $storage }}" {{ request('storage') == $storage ? 'selected' : '' }}>{{ $storage }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block font-semibold mb-1">Color</label>
                    <select name="color" class="border p-2 w-full rounded">
                        <option value="">All</option>
                        @foreach($colorOptions as $color)
                            <option value="{{ $color }}" {{ request('color') == $color ? 'selected' : '' }}>{{ $color }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex gap-2 pt-2">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Apply</button>
                    <a href="{{ route('products.index') }}" class="px-4 py-2 rounded border text-gray-700 hover:bg-gray-50">Reset</a>
                </div>
            </form>
        </aside>

        <div class="md:col-span-3">
            @if($products->count() > 0)
                @include('front.products.partials.product-grid')
                <div class="mt-8">
                    {{ $products->links() }}
                </div>
            @else
                <p class="text-center text-gray-600 bg-white rounded-lg p-6">No products found.</p>
            @endif
        </div>
    </div>
</div>
@endsection
