@extends('layouts.app')

@section('content')
<div class="bg-gray-100 py-10">
    <div class="container mx-auto px-6 mb-6">
        <nav class="text-sm text-gray-600 flex items-center gap-2">
            <a href="{{ url('/') }}" class="hover:text-blue-600">Home</a>
            <span>/</span>
            <span class="text-gray-800 font-semibold">About Us</span>
        </nav>
    </div>

    <div class="container mx-auto px-6 space-y-8">
        <div class="bg-white p-8 rounded-xl shadow-sm border">
            <h1 class="text-4xl font-extrabold tracking-tight mb-4">About Us</h1>
            <p class="text-gray-700 text-lg leading-relaxed">
                Life Style, Nada Phone, International Link, and A2Z operate under the same owner,
                with one shared mission: to deliver trusted mobile solutions and reliable service across Kuwait.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white p-6 rounded-xl shadow-sm border">
                <h2 class="text-2xl font-bold mb-3">What We Do</h2>
                <p class="text-gray-700 leading-relaxed">
                    We buy and sell both new and used phones, with carefully selected stock and fair pricing.
                    Our team also provides accessories and day-to-day device support to keep customers connected.
                </p>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-sm border">
                <h2 class="text-2xl font-bold mb-3">Repair Services</h2>
                <p class="text-gray-700 leading-relaxed">
                    We repair phones, tablets, smartwatches, and similar devices.
                    From screen and battery replacement to charging and hardware issues, we focus on fast,
                    practical fixes with clear communication and dependable after-service support.
                </p>
            </div>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-sm border">
            <h2 class="text-2xl font-bold mb-3">Our Commitment</h2>
            <p class="text-gray-700 leading-relaxed">
                Across all four stores, we follow the same standards for product quality, honest advice,
                and customer care. Whether you are buying, selling, or repairing a device, our goal is simple:
                make the process smooth, transparent, and worth your trust.
            </p>
        </div>
    </div>
</div>
@endsection
