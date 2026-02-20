@extends('admin.layouts.app')

@section('content')
<div class="container mx-auto py-8">
    <h1 class="text-2xl font-bold mb-4">Repairs</h1>
    <a href="{{ route('admin.repairs.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded">Add Repair</a>

    <table class="w-full mt-6 bg-white shadow rounded">
        <thead class="bg-gray-100 text-left">
            <tr>
                <th class="p-3">Customer</th>
                <th class="p-3">Device</th>
                <th class="p-3">Store</th>
                <th class="p-3">Salesman</th>
                <th class="p-3">Warranty</th>
                <th class="p-3">Status</th>
                <th class="p-3">Total</th>
                <th class="p-3">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($repairs as $r)
            <tr class="border-b">
                <td class="p-3">{{ $r->customer_name }}</td>
                <td class="p-3">{{ $r->device_model }}</td>
                <td class="p-3">{{ $r->store->name }}</td>
                <td class="p-3">{{ $r->salesman->name }}</td>
                <td class="p-3">{{ $r->warranty ?: '-' }}</td>
                <td class="p-3 capitalize">{{ $r->status }}</td>
                <td class="p-3">${{ number_format($r->total_cost, 2) }}</td>
                <td class="p-3">
                    <a href="{{ route('admin.repairs.edit', $r) }}" class="text-blue-600 mr-2">Edit</a>
                    <form action="{{ route('admin.repairs.destroy', $r) }}" method="POST" class="inline">
                        @csrf @method('DELETE')
                        <button onclick="return confirm('Delete this repair?')" class="text-red-600">Delete</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="mt-4">
        {{ $repairs->links() }}
    </div>
</div>
@endsection
