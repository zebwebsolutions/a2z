@extends('layouts.app')

@section('content')

<div class="container py-10 max-w-lg mx-auto">

    <h2 class="text-2xl font-bold mb-6">{{ $product->name }} Repair</h2>

    <form method="POST" action="{{ route('repair.book') }}">
        @csrf

        <input type="hidden" name="device_model" value="{{ $product->name }}">

        <div class="mb-4">
            <label class="font-semibold">Your Name</label>
            <input type="text" name="customer_name" required class="w-full p-2 border rounded">
        </div>

        <div class="mb-4">
            <label class="font-semibold">Phone Number</label>
            <input type="text" name="customer_phone" required class="w-full p-2 border rounded">
        </div>

        <div class="mb-4">
            <label class="font-semibold">IMEI (optional)</label>
            <input type="text" name="imei" class="w-full p-2 border rounded">
        </div>

        <div class="mb-4">
            <label class="font-semibold">Describe the Problem</label>
            <textarea name="problem_description" required class="w-full p-2 border rounded h-24"></textarea>
        </div>

        <button class="bg-blue-600 text-white px-4 py-2 rounded">
            Submit
        </button>

    </form>

</div>

@endsection