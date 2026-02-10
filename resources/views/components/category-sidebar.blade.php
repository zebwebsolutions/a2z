@props([
    'category',
    'availableBrands' => [],
    'priceMin' => 0,
    'priceMax' => 0,
    'availableRAM' => [],
    'availableStorage' => [],
    'subcategories' => [],
    'batteryMin' => null,
    'batteryMax' => null,
])

@php
    $currentBrand = request('brand');

    // If we're on /brand/{slug}
    if (!$currentBrand && request()->routeIs('brand.show')) {
        $currentBrand = request()->route('brand');
    }
@endphp

<div class="hidden md:block w-64 p-4 bg-white rounded-lg border shadow-sm h-fit space-y-8">
    <form id="filterForm" class="space-y-6">

        {{-- ===========================
            BRAND FILTER
        ============================ --}}
        @if($availableBrands && $availableBrands->count())
            <div x-data="{ open: true }" class="border-b pb-4">
                <button type="button" 
                        @click="open = !open" 
                        class="w-full flex justify-between items-center font-semibold text-lg mb-2 focus:outline-none">
                    Brands
                    <span x-text="open ? '−' : '+'" class="text-xl leading-none"></span>
                </button>

                <div x-show="open" x-collapse class="space-y-2">
                    @foreach($availableBrands as $brand)
                        <label class="flex items-center gap-2 cursor-pointer text-sm hover:text-blue-600 transition-colors">
                            <input type="radio"
                                   name="brand"
                                   class="brandRadio autoFilter accent-blue-600"
                                   value="{{ $brand->slug }}"
                                   @checked(request('brand') === $brand->slug)>
                            <span>{{ $brand->name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- ===========================
            SUBCATEGORIES
        ============================ --}}
        @if($subcategories && $subcategories->count())
            <div x-data="{ open: true }" class="border-b pb-4">
                <button type="button" 
                        @click="open = !open"
                        class="w-full flex justify-between items-center font-semibold text-lg mb-2 focus:outline-none">
                    Categories
                    <span x-text="open ? '−' : '+'" class="text-xl leading-none"></span>
                </button>

                <ul x-show="open" x-collapse class="space-y-1 text-sm">
                    @foreach($subcategories as $sub)
                        <li>
                            @if($currentBrand)
                                <a href="{{ route('brand.category', [
                                    'category' => $sub->slug,
                                    'brand' => $currentBrand
                                ]) }}"
                                class="block px-2 py-1 rounded hover:bg-gray-100">
                                    {{ $sub->name }}
                                </a>
                            @else
                                <a href="{{ route('category.show', $sub->slug) }}"
                                class="block px-2 py-1 rounded hover:bg-gray-100">
                                    {{ $sub->name }}
                                </a>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif


        {{-- ===========================
            BEAUTIFUL PRICE SLIDER
        =========================== --}}
        @if($priceMin < $priceMax)
        <div x-data="priceSlider({{ $priceMin }}, {{ $priceMax }})" class="pb-6 border-b">

            <div class="flex justify-between mb-2">
                <h3 class="font-semibold text-lg">Price</h3>
                <span class="text-xs text-gray-500">PKR</span>
            </div>

            {{-- Added relative container height h-6 to fit handles --}}
            <div class="relative h-6 mt-3">

                {{-- Gray Background Track --}}
                <div class="absolute top-1/2 left-0 w-full h-2 bg-gray-200 rounded-full -translate-y-1/2"></div>

                {{-- Blue Active Track --}}
                <div class="absolute top-1/2 h-2 bg-blue-600 rounded-full -translate-y-1/2 transition-all duration-0"
                    :style="`left:${minPercent}%; right:${100 - maxPercent}%`">
                </div>

                {{-- VISUAL Handle MIN --}}
                <div class="handle pointer-events-none"
                    :style="`left: ${minPercent}%; transform: translate(-50%, -50%)`">
                </div>

                {{-- VISUAL Handle MAX --}}
                <div class="handle pointer-events-none"
                    :style="`left: ${maxPercent}%; transform: translate(-50%, -50%)`">
                </div>

                {{-- INTERACTIVE INPUTS --}}
                {{-- Note: pointer-events:none in CSS handles the overlapping issue --}}
                <input type="range"
                    x-model="min"
                    :min="realMin"
                    :max="realMax"
                    step="0.1"
                    @input="update('min')"
                    class="range-hidden">

                <input type="range"
                    x-model="max"
                    :min="realMin"
                    :max="realMax"
                    step="0.1"
                    @input="update('max')"
                    class="range-hidden">

            </div>

            <div class="flex justify-between text-sm mt-3">
                <span>Min: <strong x-text="Number(min).toFixed(2)"></strong></span>
                <span>Max: <strong x-text="Number(max).toFixed(2)"></strong></span>
            </div>

            <input type="hidden" name="min" :value="min">
            <input type="hidden" name="max" :value="max">

        </div>
        @endif

        {{-- BATTERY HEALTH (USED DEVICES ONLY) --}}
        @if(!is_null($batteryMin) && !is_null($batteryMax) && $batteryMax >= 80)
            <div class="mt-6">
                <h3 class="text-sm font-semibold mb-2">
                    Battery Health
                </h3>

                @php
                    $batterySteps = [80, 85, 90, 95];
                    $selected = (array) request('battery');
                @endphp

                <div class="space-y-2">
                    @foreach($batterySteps as $value)
                        @if($value <= $batteryMax)
                            <label class="flex items-center gap-2 text-sm cursor-pointer">
                                <input type="checkbox"
                                    name="battery[]"
                                    value="{{ $value }}"
                                    @checked(in_array($value, $selected))
                                    onchange="this.form.submit()"
                                    class="rounded border-gray-300 text-black focus:ring-black">

                                <span>{{ $value }}%+</span>
                            </label>
                        @endif
                    @endforeach
                </div>
            </div>
        @endif

        {{-- ===========================
            RAM FILTER
        ============================ --}}
        @if(!empty($availableRAM) && count($availableRAM))
            <div x-data="{ open: true }" class="border-b pb-4">
                <button type="button" 
                        @click="open = !open"
                        class="w-full flex justify-between items-center font-semibold text-lg mb-2 focus:outline-none">
                    RAM
                    <span x-text="open ? '−' : '+'" class="text-xl leading-none"></span>
                </button>

                <div x-show="open" x-collapse class="space-y-2 pt-1">
                    @foreach($availableRAM as $ram)
                        <label class="flex items-center gap-2 cursor-pointer text-sm hover:text-blue-600 transition-colors">
                            <input type="radio"
                                   name="ram"
                                   value="{{ $ram }}"
                                   class="autoFilter accent-blue-600"
                                   {{ request('ram') == $ram ? 'checked' : '' }}>
                            <span>{{ $ram }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- ===========================
            STORAGE FILTER
        ============================ --}}
        @if(!empty($availableStorage) && count($availableStorage))
            <div x-data="{ open: true }" class="border-b pb-4">
                <button type="button" 
                        @click="open = !open"
                        class="w-full flex justify-between items-center font-semibold text-lg mb-2 focus:outline-none">
                    Storage
                    <span x-text="open ? '−' : '+'" class="text-xl leading-none"></span>
                </button>

                <div x-show="open" x-collapse class="space-y-2 pt-1">
                    @foreach($availableStorage as $s)
                        <label class="flex items-center gap-2 cursor-pointer text-sm hover:text-blue-600 transition-colors">
                            <input type="radio"
                                   name="storage"
                                   value="{{ $s }}"
                                   class="autoFilter accent-blue-600"
                                   {{ request('storage') == $s ? 'checked' : '' }}>
                            <span>{{ $s }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- CLEAR FILTERS --}}
        @if($category && $category->id)
            {{-- REAL CATEGORY --}}
            <a href="{{ route('category.show', $category->slug) }}"
            class="block mt-6 text-center bg-gray-200 py-2 rounded hover:bg-gray-300 text-sm font-medium transition-colors">
                Clear Filters
            </a>
        @else
            {{-- USED / VIRTUAL PAGE --}}
            <a href="{{ url()->current() }}"
            class="block mt-6 text-center bg-gray-200 py-2 rounded hover:bg-gray-300 text-sm font-medium transition-colors">
                Clear Filters
            </a>
        @endif

    </form>
</div>



{{-- ============================================
    AUTO-FILTER LOGIC FOR RADIO + CHECKBOX
============================================ --}}
<script>
document.addEventListener("DOMContentLoaded", () => {

    document.querySelectorAll(".autoFilter").forEach(el => {
        el.addEventListener("change", () => {

            let params = new URLSearchParams(window.location.search);

            // RAM
            if (el.name === "ram") {
                params.delete('ram');
                params.set('ram', el.value);
            }

            // STORAGE
            if (el.name === "storage") {
                params.delete('storage');
                params.set("storage", el.value);
            }

            // BRAND (checkboxes)
            if (el.classList.contains("brandRadio")) {
                params.delete('brand');
                const selected = document.querySelector("input[name='brand']:checked");
                if(selected) {
                  params.append('brand', selected.value);
                }
            }

            window.dispatchEvent(new CustomEvent("ajaxFilter", { detail: params.toString() }));
        });
    });

});
</script>

<style>
/* ... (Keep your .handle and .tooltip styles the same) ... */

.handle {
    position: absolute;
    top: 50%;
    width: 24px;
    height: 24px;
    background: white;
    border: 2px solid #2563eb; 
    border-radius: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    box-shadow: 0 0 6px rgba(0, 0, 0, 0.25);
    transition: transform .15s ease;
    z-index: 20; /* Visual handle z-index */
}

/* ... tooltip styles ... */

/* === THE FIX STARTS HERE === */
.range-hidden {
    position: absolute;
    top: 0;
    left: -12px; /* center thumb on track */
    width: calc(100% + 24px);
    height: 100%; /* Cover full height of parent */
    opacity: 0;
    cursor: pointer;
    
    /* 1. Let clicks pass through the track */
    pointer-events: none; 
    
    /* Remove default styling */
    -webkit-appearance: none; 
    appearance: none;
    background: transparent;
    margin: 0;
}

/* 2. Re-enable clicks ONLY on the specific handle (thumb) */
.range-hidden::-webkit-slider-thumb {
    pointer-events: auto; /* Catch the click here */
    -webkit-appearance: none;
    width: 24px; /* Match visual handle size */
    height: 24px;
    cursor: pointer;
    border-radius: 50%;
}

/* Firefox Support */
.range-hidden::-moz-range-thumb {
    pointer-events: auto;
    width: 24px;
    height: 24px;
    cursor: pointer;
    border: none;
    border-radius: 50%;
}
</style>
