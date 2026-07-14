@extends('layouts.app')

@section('title', 'Repair ' . $brand . ' ' . $model)

@section('content')
<div class="container mx-auto px-3 md:px-4 lg:px-6 py-10">

    <h1 class="text-2xl font-bold mb-4">
        {{ ucfirst($brand) }} {{ ucfirst($model) }} Repair Services
    </h1>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        {{-- Repair Options --}}
        <div class="border p-6 rounded-lg shadow">
            <h2 class="font-semibold text-lg mb-4">Available Repairs</h2>

            <ul class="space-y-3">
                @foreach ($repairs as $repair)
                    <li class="flex justify-between border-b pb-3">
                        <span>{{ $repair['name'] }}</span>
                        <span class="font-bold text-blue-600">KWD {{ $repair['price'] }}</span>
                    </li>
                @endforeach
            </ul>
        </div>

        {{-- Booking Form --}}
        <div class="border p-6 rounded-lg shadow">
            <h2 class="font-semibold text-lg mb-4">Book Your Repair</h2>

            @if(session('success'))
                <div class="p-3 bg-green-100 text-green-700 rounded mb-4">
                    {{ session('success') }}
                </div>
            @endif

            <form method="POST" action="{{ route('repair.book') }}">
                @csrf

                <input type="hidden" name="device" value="{{ $device }}">
                <input type="hidden" name="brand" value="{{ $brand }}">
                <input type="hidden" name="model" value="{{ $model }}">

                <div class="mb-3">
                    <label class="block font-medium">Name</label>
                    <input type="text" name="name" class="border p-2 w-full" required>
                </div>

                <div class="mb-3">
                    <label class="block font-medium">Phone</label>
                    <input type="text" name="phone" class="border p-2 w-full" required>
                </div>

                <div class="mb-3">
                    <label class="block font-medium">Select Repair Service</label>
                    <select name="service" class="border p-2 w-full" required>
                        @foreach ($repairs as $repair)
                            <option value="{{ $repair['name'] }}">{{ $repair['name'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="block font-medium">Notes (optional)</label>
                    <textarea name="notes" class="border p-2 w-full"></textarea>
                </div>

                <button class="bg-blue-600 text-white px-4 py-2 rounded">
                    Submit Request
                </button>
            </form>
        </div>

    </div>

</div>
@endsection
