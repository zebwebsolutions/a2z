@extends('layouts.app')

@section('title', 'Search: ' . $query)

@section('content')

<div class="container mx-auto px-6 py-10">

    <h1 class="text-2xl font-bold mb-6">
        Search results for: <span class="text-blue-600">“{{ $query }}”</span>
    </h1>

    @if($products->count() === 0)
        <p class="text-gray-500">No products found. Try a different keyword.</p>
    @else
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6">
        @foreach($products as $product)
            <div id="product-wrapper" aria-live="polite">
                @include('front.shop.partials.product-card', ['product' => $product])
            </div>
        @endforeach
      </div>

        <div class="mt-6">
            {{ $products->links() }}
        </div>
    @endif

</div>
@endsection