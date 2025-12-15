@props(['name'])

@php
$slider = \App\Models\Slider::with('images')
    ->where('name', $name)
    ->where('is_active', true)
    ->first();
@endphp

@if($slider && $slider->images->count())
<div class="swiper dynamic-slider">
    <div class="swiper-wrapper">

        @foreach($slider->images as $slide)
            <div class="swiper-slide relative">
                <img src="{{ asset('storage/' . $slide->image) }}" class="w-full h-[380px] md:h-[480px] object-cover" />

                @if($slide->heading || $slide->button_text)
                    <div class="absolute left-10 top-1/3 text-white max-w-md">
                        @if($slide->heading)
                            <h2 class="text-3xl font-bold mb-2 drop-shadow">{{ $slide->heading }}</h2>
                        @endif
                        @if($slide->description)
                            <p class="mb-4 drop-shadow">{{ $slide->description }}</p>
                        @endif
                        @if($slide->button_text)
                            <a href="{{ $slide->button_link }}" 
                               class="bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded text-white">
                                {{ $slide->button_text }}
                            </a>
                        @endif
                    </div>
                @endif
            </div>
        @endforeach

    </div>

    <div class="swiper-pagination"></div>
    <div class="swiper-button-prev"></div>
    <div class="swiper-button-next"></div>
</div>
@endif