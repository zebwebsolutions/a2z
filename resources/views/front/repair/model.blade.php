@extends('layouts.app')

@section('content')

<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <h2 class="text-2xl font-bold mb-4">Select Your Model</h2>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
        @foreach($models as $m)
            <a href="{{ route('repair.service', [$device, $brand->slug, $m->slug]) }}"
               class="p-6 border rounded shadow hover:shadow-lg transition text-center">
                <img src="/storage/{{ $m->image }}" class="h-24 mx-auto mb-3">
                <p class="font-medium">{{ $m->name }}</p>
            </a>
        @endforeach
    </div>
</div>

@endsection
