@extends('layouts.app')

@section('content')

<div class="container mx-auto max-w-xl px-4 sm:px-6 lg:px-8 py-10">

    <h1 class="text-3xl font-bold mb-6">Repair Request Form</h1>

    @if(session('success'))
        <div class="bg-green-100 text-green-800 p-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <form method="POST" action="{{ route('repair.book') }}">
        @csrf

        {{-- Device Type --}}
        <div class="mb-4">
            <label class="font-semibold">Select Device Type</label>
            <select name="device_type"
                    id="deviceType"
                    class="w-full border p-2 rounded"
                    onchange="toggleCustomDevice()"
                    required>
                @foreach($deviceTypes as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        {{-- Custom Field (only when 'other' is selected) --}}
        <div id="customDeviceField" class="mb-4 hidden">
            <label class="font-semibold">Enter Device Name</label>
            <input type="text" name="device_custom" class="w-full border p-2 rounded">
        </div>

        {{-- Customer Name --}}
        <div class="mb-4">
            <label class="font-semibold">Your Name</label>
            <input type="text" name="customer_name" required class="w-full border p-2 rounded">
        </div>

        {{-- Phone --}}
        <div class="mb-4">
            <label class="font-semibold">Phone Number</label>
            <input type="text" name="customer_phone" required class="w-full border p-2 rounded">
        </div>

        {{-- IMEI --}}
        <div class="mb-4">
            <label class="font-semibold">IMEI (optional)</label>
            <input type="text" name="imei" class="w-full border p-2 rounded">
        </div>

        {{-- Description --}}
        <div class="mb-4">
            <label class="font-semibold">Describe the Problem</label>
            <textarea name="problem_description" required class="w-full border p-2 rounded h-28"></textarea>
        </div>

        <button class="bg-blue-600 text-white px-4 py-2 rounded">
            Submit Request
        </button>
    </form>
</div>

<script>
    function toggleCustomDevice() {
        let select = document.getElementById('deviceType');
        let customField = document.getElementById('customDeviceField');

        if (select.value === 'other') {
            customField.classList.remove('hidden');
        } else {
            customField.classList.add('hidden');
        }
    }
</script>

@endsection
