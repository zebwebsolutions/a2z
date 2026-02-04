{{-- NAVIGATION --}}
<nav x-data="megaMenu()" x-init="init()" class="bg-white border-b border-gray-200 fixed top-0 inset-x-0 z-50">

    {{-- NAVBAR --}}
    <div class="container mx-auto flex items-center justify-between px-4 md:px-6 py-3">

        {{-- LEFT: Mobile toggle + Logo --}}
        <div class="flex items-center gap-3">
            <button @click="mobileOpen = true" class="md:hidden text-2xl">☰</button>

            <a href="{{ route('home') }}" class="text-2xl font-bold text-blue-700">
                A2Z<span class="text-gray-900">KWT</span>
            </a>
        </div>

        {{-- CENTER: Category menu --}}
        <div class="hidden md:flex items-center gap-6">
            @foreach($menuCategories as $index => $cat)
                <a 
                href="{{ route('category.show', $cat->slug) }}"
                @mouseenter="openMenu({{ $index }})"
                class="font-medium hover:text-blue-700 transition"
            >
                {{ $cat->name }}
            </a>
            @endforeach
            {{-- Used Devices Link --}}
            <div class="hidden md:flex items-center">
                <a href="{{ route('shop.used') }}" class="font-medium hover:text-blue-700 transition">
                    Used Devices
                </a>
            </div>
        </div>

        {{-- RIGHT: Cart + Login --}}
        <div class="flex items-center gap-2">
            <a href="{{ route('cart.index') }}" class="relative text-xl">

            <i data-lucide="shopping-cart"></i>

            {{-- Cart Count Badge --}}
            @php
                $cartCount = session('cart')
                    ? collect(session('cart'))->sum('quantity')
                    : 0;
            @endphp

            @if($cartCount > 0)
                <span
                    id="cart-count"
                    class="absolute -top-2 -right-2 bg-red-600 text-white text-xs font-bold
                        w-5 h-5 flex items-center justify-center rounded-full">
                    {{ $cartCount }}
                </span>
            @else
                <span id="cart-count" class="hidden"></span>
            @endif

        </a>

            @auth
            <div class="relative group hidden md:block">
                <button
                    class="font-medium flex items-center gap-1 focus:outline-none">
                    {{ Auth::user()->name }}
                </button>

                {{-- Dropdown --}}
                <div
                    class="absolute right-0 mt-2 w-40 bg-white border rounded shadow-md
                        opacity-0 invisible group-hover:opacity-100 group-hover:visible
                        transition-all duration-150 z-50">

                    <a href="{{ route('profile.edit') }}"
                    class="block px-4 py-2 text-sm hover:bg-gray-100">
                        My Profile
                    </a>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button
                            type="submit"
                            class="w-full text-left px-4 py-2 text-sm hover:bg-gray-100 text-red-600">
                            Logout
                        </button>
                    </form>
                </div>
            </div>
            @else
            <a href="{{ route('login') }}" class="hidden md:block font-medium">
                Login
            </a>
            @endauth

        </div>
    </div>

    {{-- MEGA MENU (Desktop Only) --}}
    <div
        x-show="desktopOpen !== null"
        x-transition.opacity
        x-cloak
        @mouseenter="hoveringPanel = true"
        @mouseleave="hoveringPanel = false; scheduleClose()"
        class="hidden md:block absolute left-0 right-0 bg-white shadow-lg border-t border-gray-200"
    >
        <div class="max-w-7xl mx-auto px-6 py-6">

            {{-- CATEGORY HEADER --}}
            <template x-if="activeCategory">
                <h3 class="text-xl font-semibold mb-4" x-text="activeCategory.name"></h3>
            </template>

            <div class="grid grid-cols-4 gap-6">

                {{-- CATEGORY IMAGE --}}
                <div class="col-span-1">
                    <template x-if="activeCategory?.image">
                        <img :src="'/storage/' + activeCategory.image"
                             class="w-full h-32 object-cover rounded">
                    </template>
                </div>

                {{-- DYNAMIC MENU ITEMS (Brands OR Subcategories) --}}
                <div class="col-span-3 grid grid-cols-3 gap-4">

                    <template x-for="item in activeCategory.menu_items" :key="item.id">
                        <a
                            :href="item.is_brand
                                ? '/category/' + activeCategory.slug + '/' + item.slug
                                : '/category/' + item.slug"
                            class="flex items-center gap-3 hover:bg-gray-50 p-2 rounded"
                        >

                            {{-- Brand Logo --}}
                            <template x-if="item.is_brand && item.logo">
                                <img :src="'/storage/' + item.logo"
                                     class="w-10 h-8 object-contain">
                            </template>

                            {{-- Brand initials if no logo --}}
                            <template x-if="item.is_brand && !item.logo">
                                <div class="w-10 h-8 bg-gray-100 flex items-center justify-center rounded text-xs text-gray-600"
                                     x-text="item.name.substring(0,3)">
                                </div>
                            </template>

                            {{-- Subcategory Icon --}}
                            <template x-if="!item.is_brand">
                                <i data-lucide="square" class="w-4 h-4 text-gray-400"></i>
                            </template>

                            <span x-text="item.name" class="text-sm font-medium"></span>
                        </a>
                    </template>

                    <template x-if="activeCategory.menu_items.length === 0">
                        <p class="text-gray-500 col-span-3">No items available</p>
                    </template>

                </div>
            </div>

        </div>
    </div>

    {{-- MOBILE DRAWER --}}
    <div x-show="mobileOpen" x-cloak>

        <div class="fixed inset-0 bg-black bg-opacity-40 z-40" @click="mobileOpen = false"></div>

        <aside class="fixed top-0 left-0 w-80 h-full bg-white z-50 overflow-y-auto shadow-lg">

            <div class="p-4 flex justify-between items-center border-b">
                <h2 class="text-lg font-semibold">Menu</h2>
                <button @click="mobileOpen = false" class="text-xl">✕</button>
            </div>

            <div class="p-4 space-y-2">

                {{-- MOBILE CATEGORIES --}}
                @foreach($menuCategories as $idx => $cat)
                    <div>
                        <button
                            @click="toggleMobile({{ $idx }})"
                            class="w-full flex items-center justify-between py-2 font-medium"
                        >
                            <span>{{ $cat->name }}</span>
                            <span x-text="mobileIndex === {{ $idx }} ? '-' : '+'"></span>
                        </button>

                        {{-- MOBILE EXPANDED LIST --}}
                        <div x-show="mobileIndex === {{ $idx }}" x-collapse class="ml-4 mt-1 space-y-1">

                            {{-- Dynamically show brands OR subcategories --}}
                            @foreach($cat->menu_items as $item)
                                <a
                                    href="{{ $item->is_brand
                                        ? '/category/' . $cat->slug . '/' . $item->slug
                                        : '/category/' . $item->slug }}"
                                    class="block py-1 text-sm text-gray-700 hover:text-blue-700"
                                    @click="mobileOpen = false"
                                >
                                    {{ $item->name }}
                                </a>
                            @endforeach

                        </div>
                    </div>
                @endforeach

            </div>
        </aside>
    </div>
</nav>

{{-- Spacer --}}
<div class="h-[56px]"></div>

<div class=" bg-white border-t">
    <div class="container mx-auto py-4 px-6 flex flex-col md:flex-row md:items-center justify-between md:gap-6">
        <div>
            <a class="font-bold text-lg" href="{{ route('repair.form') }}">Repairing Service</a>
        </div>
        <form action="{{ route('search') }}" method="GET" class="w-full max-w-md mb-0">
            <div class="relative">
                <input 
                    type="text"
                    id="liveSearchInput"
                    autocomplete="off"
                    name="q" 
                    class="w-full border rounded-xl py-2 pl-4 pr-10 text-sm focus:ring focus:border-gray-400"
                    placeholder="Search products…" 
                    value="{{ request('q') }}"
                >
                <button class="absolute inset-y-0 right-2 flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </button>

                <div id="liveSearchResults" 
                        class="absolute z-50 bg-white border w-full mt-1 rounded shadow-lg hidden">
                    {{-- Live search results will be injected here --}}
                </div>
            </div>
        </form>
        <div class="hotline-phone text-lg font-bold hidden md:block">
            <i data-lucide="phone" class="inline-block w-5 h-5 mr-2 text-blue-600"></i>
            <span class="text-blue-600">Hotline:</span>
            +965 2224 2220
        </div>
    </div>
</div>

<script>
function megaMenu() {
    return {
        desktopOpen: null,
        activeCategory: { menu_items: []},
        hoveringPanel: false,
        closeTimeout: null,
        mobileOpen: false,
        mobileIndex: null,
        categories: [],

        init() {
            this.categories = @json($menuCategories);
        },

        openMenu(index) {
            this.desktopOpen = index;
            this.activeCategory = this.categories[index] ?? { menu_items: []};
            this.hoveringPanel = true;

            if (this.closeTimeout) {
                clearTimeout(this.closeTimeout);
                this.closeTimeout = null;
            }
        },

        toggleMobile(index) {
            this.mobileIndex = this.mobileIndex === index ? null : index;
        },

        scheduleClose() {
            this.closeTimeout = setTimeout(() => {
                if (!this.hoveringPanel) {
                    this.desktopOpen = null;
                    this.activeCategory = { menu_items: [] };
                }
            }, 150);
        }
    };
}
</script>
