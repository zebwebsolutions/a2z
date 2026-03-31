@extends('layouts.app')

@section('title', 'LifeStyleQ8 | Your Trusted Electronics & Mobile Repair Partner')

@section('content')
    {{-- Hero Section --}}
    <x-slider name="homepage_slider" />

    {{-- Categories Section --}}
    <section class="py-16 bg-gray-50">
        <div class="container mx-auto px-6">
            <h2 class="text-3xl font-bold text-center mb-10">Shop by Category</h2>

            {{-- Carousel Wrapper --}}
            <div 
                x-data="{
                    scrollLeft() { this.$refs.slider.scrollBy({ left: -300, behavior: 'smooth' }) },
                    scrollRight() { this.$refs.slider.scrollBy({ left: 300, behavior: 'smooth' }) }
                }"
                class="relative">
                {{-- Left Arrow --}}
                <button 
                    @click="scrollLeft"
                    type="button"
                    class="absolute left-0 top-1/2 -translate-y-1/2 bg-white p-2 rounded-full shadow hover:bg-gray-100 z-10 hidden md:flex"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 text-gray-600">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                    </svg>
                </button>

                {{-- Scrollable Container --}}
                <div 
                    x-ref="slider"
                    class="flex overflow-x-auto gap-6 scroll-smooth snap-x snap-mandatory no-scrollbar cursor-grab active:cursor-grabbing py-2"
                >
                    @foreach($categories as $category)
                        <a href="{{ route('category.show', ['category' => $category->slug]) }}"
                          class="group flex-shrink-0 w-64 bg-white rounded-xl shadow hover:shadow-lg transition overflow-hidden snap-start">
                            <img src="{{ asset('storage/' . $category->image) }}" 
                                alt="{{ $category->name }}" 
                                class="w-full h-48 object-cover group-hover:scale-105 transition-transform duration-300">
                            <div class="p-4 text-center">
                                <h3 class="text-lg font-semibold text-gray-800">{{ $category->name }}</h3>
                            </div>
                        </a>
                    @endforeach
                </div>

                {{-- Right Arrow --}}
                <button 
                    @click="scrollRight"
                    type="button"
                    class="absolute right-0 top-1/2 -translate-y-1/2 bg-white p-2 rounded-full shadow hover:bg-gray-100 z-10 hidden md:flex"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 text-gray-600">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                    </svg>
                </button>
            </div>
        </div>
    </section>

    @foreach ($sections as $section)
        <section class="container mx-auto px-4 py-6">
            <h2 class="text-3xl text-center font-bold mb-10">{{ $section->title }}</h2>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                @foreach ($section->ordered_products as $product)
                    @include('front.products.partials.product-card', ['product' => $product])
                @endforeach
            </div>
        </section>
    @endforeach




    {{-- Featured Products --}}
    <section class="py-16 bg-white">
        <div class="container mx-auto px-6">
            <h2 class="text-3xl font-bold text-center mb-10">Featured Products</h2>

            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-6">
                @foreach($products as $product)
                    @include('front.products.partials.product-card', ['product' => $product])
                @endforeach
            </div>
        </div>
    </section>

    {{-- About / Repair Service Section --}}
    <section class="bg-gray-100 py-20">
        <div class="container mx-auto px-6 grid md:grid-cols-2 gap-12 items-center">
            <div>
                <h2 class="text-3xl font-bold mb-4 text-gray-800">Mobile Repair Services</h2>
                <p class="text-gray-600 mb-6 leading-relaxed">
                    Whether your screen is cracked or your device needs a quick fix, LifeStyleQ8’s trained technicians handle all repairs with care and precision.
                    We offer fast turnaround and genuine parts — so your phone feels brand new again.
                </p>
                <a href="{{ route('repair.form') }}" class="bg-blue-700 text-white px-6 py-3 rounded-lg hover:bg-blue-800 transition">
                    Book a Repair
                </a>
            </div>

            <div>
                <img src="{{ asset('images/mobile-repairing.webp') }}" alt="Repair Service" class="rounded-xl shadow-lg">
            </div>
        </div>
    </section>

    {{-- Footer CTA --}}
    <section class="bg-blue-700 text-white py-12 text-center">
        <h3 class="text-2xl font-bold mb-2">Have Questions? Visit Our Stores Across Kuwait</h3>
        <p class="text-blue-200 mb-6">We’re here to serve you in 4 convenient locations.</p>
        <a href="{{ route('admin.stores.index') }}" class="bg-white text-blue-700 px-6 py-3 rounded-lg font-semibold hover:bg-blue-50 transition">
            Find a Store
        </a>
    </section>

@endsection