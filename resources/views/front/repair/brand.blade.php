@extends('layouts.app')

@section('content')

<div class="container py-10">
    <h2 class="text-2xl font-bold mb-4">Select Brand</h2>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
        @foreach($brands as $brand)
            <a href="{{ route('repair.model', [$device, $brand->slug]) }}"
               class="p-6 border rounded shadow text-center hover:shadow-lg transition">

                @if($brand->logo)
                    <img src="/storage/{{ $brand->logo }}" class="h-16 mx-auto mb-2">
                @endif

                <p class="font-semibold">{{ $brand->name }}</p>
            </a>
        @endforeach
    </div>
</div>

@endsection