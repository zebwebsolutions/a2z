@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
  <h1 class="text-2xl font-bold mb-4">{{ $category->name }}</h1>

  @include('front.products.partials.product-grid', ['products' => $products])
</div>
@endsection