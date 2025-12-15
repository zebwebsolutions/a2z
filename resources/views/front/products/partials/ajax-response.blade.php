{{-- This partial returns both chips and productResults for AJAX responses --}}
<div id="filterChips">
    {{-- Reuse same chip rendering as page (server-side) --}}
    @php
        $activeBrands = (array) request()->brand;
        $activeRam = request()->ram;
        $activeStorage = request()->storage;
        $min = request()->min;
        $max = request()->max;
    @endphp

    @foreach($activeBrands as $bSlug)
        @php $b = $availableBrands->firstWhere('slug', $bSlug); @endphp
        @if($b)
            <span class="chip" data-remove="brand:{{ $bSlug }}">{{ $b->name }} ✕</span>
        @endif
    @endforeach

    @if($activeRam)
        <span class="chip" data-remove="ram:{{ $activeRam }}">RAM: {{ $activeRam }} ✕</span>
    @endif

    @if($activeStorage)
        <span class="chip" data-remove="storage:{{ $activeStorage }}">Storage: {{ $activeStorage }} ✕</span>
    @endif

    @if($min || $max)
        <span class="chip" data-remove="price">Price: {{ $min ?? $priceMin }} – {{ $max ?? $priceMax }} ✕</span>
    @endif

    @if(request()->query())
        <a id="clearAllChips" class="chip bg-gray-700 text-white cursor-pointer">Clear All ✕</a>
    @endif
</div>

<div id="productResults">
    @include('front.products.partials.product-grid', ['products' => $products])
</div>
