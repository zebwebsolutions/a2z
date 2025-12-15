@extends('admin.layouts.app')

@section('content')
<div class="container mx-auto py-8">
    <h1 class="text-2xl font-bold mb-6">Edit Repair</h1>

    <form action="{{ route('admin.repairs.update', $repair) }}" method="POST" class="space-y-4">
        @csrf
        @method('PUT')

        {{-- Store --}}
        <label class="block font-semibold">Store</label>
        <select name="store_id" class="border p-2 w-full rounded" required>
            @foreach($stores as $store)
                <option value="{{ $store->id }}" {{ $repair->store_id == $store->id ? 'selected' : '' }}>
                    {{ $store->name }}
                </option>
            @endforeach
        </select>

        {{-- Customer Info --}}
        <div class="grid grid-cols-2 gap-4">
            <input type="text" name="customer_name" value="{{ $repair->customer_name }}" placeholder="Customer Name" class="border p-2 rounded" required>
            <input type="text" name="customer_phone" value="{{ $repair->customer_phone }}" placeholder="Customer Phone" class="border p-2 rounded" required>
        </div>

        {{-- Device Info --}}
        <div class="grid grid-cols-2 gap-4">
            <input type="text" name="device_model" value="{{ $repair->device_model }}" placeholder="Device Model" class="border p-2 rounded" required>
            <input type="text" name="imei" value="{{ $repair->imei }}" placeholder="IMEI (optional)" class="border p-2 rounded">
        </div>

        <textarea name="problem_description" placeholder="Problem Description" class="border p-2 w-full rounded" required>{{ $repair->problem_description }}</textarea>

        {{-- Parts Section --}}
        <div id="parts-section" class="mt-4 space-y-2">
            <h2 class="text-lg font-semibold mb-2">Parts Used</h2>

            <div id="parts-list">
                @foreach($repair->parts as $i => $part)
                <div class="part-item grid grid-cols-3 gap-2 mb-2">
                    <input type="text" name="parts[{{ $i }}][part_name]" value="{{ $part->part_name }}" placeholder="Part Name" class="border p-2 rounded">
                    <input type="number" name="parts[{{ $i }}][quantity]" value="{{ $part->quantity }}" placeholder="Qty" class="border p-2 rounded">
                    <input type="number" name="parts[{{ $i }}][cost]" value="{{ $part->cost }}" placeholder="Cost" step="0.01" class="border p-2 rounded">
                </div>
                @endforeach
            </div>

            <button type="button" id="add-part" class="mt-2 bg-green-600 text-white px-3 py-1 rounded">+ Add Another Part</button>
        </div>

        {{-- Cost & Status --}}
        <div class="grid grid-cols-2 gap-4 mt-4">
            <input type="number" name="total_cost" value="{{ $repair->total_cost }}" placeholder="Total Cost" step="0.01" class="border p-2 rounded" required>
            <select name="status" class="border p-2 rounded">
                <option value="pending" {{ $repair->status == 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="completed" {{ $repair->status == 'completed' ? 'selected' : '' }}>Completed</option>
                <option value="delivered" {{ $repair->status == 'delivered' ? 'selected' : '' }}>Delivered</option>
            </select>
        </div>

        <button class="bg-blue-600 text-white px-4 py-2 rounded mt-4">Update Repair</button>
    </form>
</div>

{{-- Add Part Button Script --}}
<script>
document.getElementById('add-part').addEventListener('click', function() {
    const partsList = document.getElementById('parts-list');
    const index = partsList.children.length;
    const newPart = document.createElement('div');
    newPart.classList.add('part-item', 'grid', 'grid-cols-3', 'gap-2');
    newPart.innerHTML = `
        <input type="text" name="parts[${index}][part_name]" placeholder="Part Name" class="border p-2 rounded">
        <input type="number" name="parts[${index}][quantity]" placeholder="Qty" class="border p-2 rounded">
        <input type="number" name="parts[${index}][cost]" placeholder="Cost" step="0.01" class="border p-2 rounded">
    `;
    partsList.appendChild(newPart);
});
</script>
@endsection
