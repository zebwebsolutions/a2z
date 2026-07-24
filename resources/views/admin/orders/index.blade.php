@extends('admin.layouts.app')

@section('title', 'Orders')

@section('content')
<div class="bg-white p-6 rounded-lg shadow">
    <h1 class="text-2xl font-bold mb-6">All Orders</h1>

    @if(session('success'))
        <div class="mb-4 rounded border border-green-200 bg-green-50 px-4 py-3 text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-red-700">
            {{ session('error') }}
        </div>
    @endif

    <x-admin.filter-box>

        {{-- Search --}}
        <div class="md:col-span-2">
            <label class="block text-xs font-semibold text-gray-600 mb-1">
                Search
            </label>
            <input type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="Order ID or customer"
                class="w-full border rounded px-3 py-2 text-sm">
        </div>

        {{-- Store --}}
        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">
                Store
            </label>
            <select name="store_id"
                    class="w-full border rounded px-3 py-2 text-sm">
                <option value="">All</option>
                @foreach($stores as $store)
                    <option value="{{ $store->id }}"
                        @selected(request('store_id') == $store->id)>
                        {{ $store->name }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Status --}}
        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">
                Status
            </label>
            <select name="status"
                    class="w-full border rounded px-3 py-2 text-sm">
                <option value="">All</option>
                <option value="pending"   @selected(request('status') === 'pending')>Pending</option>
                <option value="processing" @selected(request('status') === 'processing')>Processing</option>
                <option value="shipped"   @selected(request('status') === 'shipped')>Shipped</option>
                <option value="completed" @selected(request('status') === 'completed')>Completed</option>
                <option value="cancelled" @selected(request('status') === 'cancelled')>Cancelled</option>
            </select>
        </div>

        {{-- Total --}}
        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">
                Total
            </label>
            <div class="flex gap-2">
                <input type="number"
                    step="0.01"
                    name="total_min"
                    value="{{ request('total_min') }}"
                    placeholder="Min"
                    class="w-full border rounded px-2 py-2 text-sm">

                <input type="number"
                    step="0.01"
                    name="total_max"
                    value="{{ request('total_max') }}"
                    placeholder="Max"
                    class="w-full border rounded px-2 py-2 text-sm">
            </div>
        </div>

        {{-- Date range --}}
        <div class="md:col-span-2">
            <label class="block text-xs font-semibold text-gray-600 mb-1">
                Date
            </label>
            <div class="flex gap-2">
                <input type="date"
                    name="date_from"
                    value="{{ request('date_from') }}"
                    class="w-full border rounded px-2 py-2 text-sm">

                <input type="date"
                    name="date_to"
                    value="{{ request('date_to') }}"
                    class="w-full border rounded px-2 py-2 text-sm">
            </div>
        </div>

    </x-admin.filter-box>


    @if($orders->count() > 0)
        <table class="w-full border-collapse">
            <thead>
                <tr class="bg-gray-100 border-b">
                    <th class="p-3 text-left">#</th>
                    <th class="p-3 text-left">Customer</th>
                    <th class="p-3 text-left">Type</th>
                    <th class="p-3 text-left">Phone</th>
                    <th class="p-3 text-left">Discount</th>
                    <th class="p-3 text-left">Total</th>
                    <th class="p-3 text-left">Payment</th>
                    <th class="p-3 text-left">Status</th>
                    <th class="p-3 text-left">Date</th>
                    <th class="p-3 text-left">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($orders as $order)
                <tr class="border-b hover:bg-gray-50">
                    <td class="p-3">{{ $order->id }}</td>
                    <td class="p-3">{{ $order->customer_name ?? 'Guest' }}</td>
                    <td class="p-3">{{ $order->customer_type ?? '-' }}</td>
                    <td class="p-3">{{ $order->customer_phone ?? '-' }}</td>
                    <td class="p-3">
                        KD {{ number_format($order->discount ?? 0, 2) }}
                    </td>
                    <td class="p-3 font-semibold">KD {{ number_format($order->total, 2) }}</td>
                    <td class="p-3">{{ ucfirst($order->payment_method ?? '-') }}</td>
                    <td class="p-3">
                        <form action="{{ route('admin.orders.update', $order->id) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <select
                                name="status"
                                onchange="this.form.submit()"
                                class="border rounded p-1"
                                @disabled(in_array($order->status, ['cancelled', 'refunded'], true))
                            >
                                <option value="pending" {{ $order->status == 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="shipped" {{ $order->status == 'shipped' ? 'selected' : '' }}>Shipped</option>
                                <option value="processing" {{ $order->status == 'processing' ? 'selected' : '' }}>Processing</option>
                                <option value="completed" {{ $order->status == 'completed' ? 'selected' : '' }}>Completed</option>
                                <option value="cancelled" {{ $order->status == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                @if($order->status === 'refunded')
                                    <option value="refunded" selected>Refunded</option>
                                @endif
                            </select>
                        </form>
                    </td>
                    <td class="p-3">{{ $order->created_at->format('d M, Y') }}</td>
                    <td class="p-3">
                        <a href="{{ route('admin.orders.show', $order->id) }}" class="text-blue-600 hover:underline">View</a>
                        <form action="{{ route('admin.orders.destroy', $order->id) }}" method="POST" class="inline"
                              onsubmit="return confirm('Are you sure you want to delete this order? This cannot be undone.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:underline ml-2">Delete</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mt-6">
            {{ $orders->links() }}
        </div>
    @else
        <p class="text-gray-600 text-center">No orders found.</p>
    @endif
</div>
@endsection
