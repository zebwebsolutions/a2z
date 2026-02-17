@extends('layouts.app')

@section('title', 'Repair Services')

@section('content')
<div class="container mx-auto px-3 md:px-4 lg:px-6 py-10">

    <h1 class="text-3xl font-bold mb-6">Repair Services</h1>
    <p class="text-gray-600 mb-10">Select your device to begin your repair request.</p>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
        @foreach ($devices as $d)
            <a href="{{ route('repair.device', $d['slug']) }}"
              class="p-6 border rounded-lg shadow hover:shadow-lg text-center transition">
                <div class="text-4xl mb-3">{{ $d['icon'] }}</div>
                <div class="font-semibold">{{ $d['name'] }}</div>
            </a>
        @endforeach
    </div>

</div>
@endsection
