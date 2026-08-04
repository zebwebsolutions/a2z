    @php
        $activeBrands = (array) request()->brand;
        $activeRam = request()->ram;
        $activeStorage = request()->storage;
        $activeBattery = request()->battery;
        if (is_array($activeBattery) && count($activeBattery)) {
            $activeBattery = min(array_map('intval', $activeBattery));
        }
        $min = request()->min;
        $max = request()->max;
        $hasPriceFilter = $min && $min != $priceMin || $max && $max != $priceMax;
    @endphp

  <div class="container mx-auto flex flex-wrap gap-2 mb-2">
    {{-- BRAND CHIPS --}}
    @if(request('brand'))
        @php $b = $availableBrands->firstWhere('slug', request('brand')); @endphp
        @if($b)
            <span class="chip" data-remove="brand" data-value="{{ $b->slug }}">
                {{ $b->name }}
                <button type="button" class="ml-1">✕</button>
            </span>
        @endif
    @endif

    {{-- RAM CHIP --}}
    @if($activeRam)
        <span class="chip" data-remove="ram" data-value="{{ $activeRam }}">
            RAM: {{ $activeRam }}
            <button type="button" class="ml-1">✕</button>
        </span>
    @endif

    {{-- STORAGE CHIP --}}
    @if($activeStorage)
        <span class="chip" data-remove="storage" data-value="{{ $activeStorage }}">
            Storage: {{ $activeStorage }}
            <button type="button" class="ml-1">✕</button>
        </span>
    @endif

    {{-- BATTERY HEALTH CHIP --}}
    @if($activeBattery)
        <span class="chip" data-remove="battery" data-value="{{ $activeBattery }}">
            Battery: {{ (int) $activeBattery }}%+
            <button type="button" class="ml-1">✕</button>
        </span>
    @endif

    {{-- PRICE CHIP --}}
    @if($hasPriceFilter)
        <span class="chip" data-remove="price">
            Price: {{ number_format((float) ($min ?? $priceMin), 2) }} – {{ number_format((float) ($max ?? $priceMax), 2) }}
            <button type="button" class="ml-1">✕</button>
        </span>
    @endif

    {{-- CLEAR ALL --}}
    @if(request()->query())
        <button id="clearAllChips" data-remove="all" type="button"
                class="chip bg-gray-700 text-white hover:bg-gray-800 hover:text-white">
            Clear All ✕
        </button>
    @endif
</div>
