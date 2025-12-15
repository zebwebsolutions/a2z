@extends('layouts.app')

@section('content')

<div class="container py-10 mx-auto px-6">
    <h1 class="text-3xl font-bold mb-6">Select Your Device</h1>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
        @foreach($devices as $d)
            <a href="{{ route('repair.form', $d['slug']) }}"
               class="p-6 border rounded-lg shadow text-center hover:shadow-lg transition">
               
                <div class="text-5xl mb-3">{{ $d['icon'] }}</div>
                <p class="font-semibold">{{ $d['name'] }}</p>
            </a>
        @endforeach
    </div>
</div>

@endsection