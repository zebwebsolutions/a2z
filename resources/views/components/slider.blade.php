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

        @foreach($slider->images->sortBy('sort_order') as $slide)
            <div class="swiper-slide overflow-hidden">
                @if($slide->url)
                <a href="{{ $slide->url }}" class="block">
                    <img
                        src="{{ asset('storage/'.$slide->image) }}"
                        class="w-full object-cover scale-[1.08] md:scale-100 transform origin-center"
                        alt="Promotion"
                        loading="lazy"
                    >
                </a>
                @else
                    <img
                        src="{{ asset('storage/'.$slide->image) }}"
                        class="w-full object-cover scale-[1.08] md:scale-100 transform origin-center"
                        alt="Promotion"
                        loading="lazy"
                    >
                @endif
            </div>
        @endforeach

    </div>

    <div class="swiper-pagination"></div>
    <div class="swiper-button-prev"></div>
    <div class="swiper-button-next"></div>
</div>
@endif
