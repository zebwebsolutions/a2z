<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>A2Z — Kuwait Stores</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 text-gray-800">

    {{-- Navbar --}}
    <nav class="bg-blue-700 text-white p-4 shadow-md sticky top-0 z-50">
        <div class="container mx-auto flex justify-between items-center">
            {{-- 🏬 Logo --}}
            <a href="{{ route('home') }}" class="font-bold text-2xl tracking-wide">A2Z</a>

            {{-- 🔗 Menu --}}
            <div class="flex items-center gap-6">
                <a href="{{ route('home') }}" 
                  class="{{ request()->routeIs('home') ? 'underline font-semibold' : 'hover:underline' }}">
                    Home
                </a>

                <a href="{{ route('shop.index') }}" 
                  class="{{ request()->routeIs('shop.*') ? 'underline font-semibold' : 'hover:underline' }}">
                    Shop
                </a>

                {{-- 🛒 Cart Link with Count --}}
                @php
                    $cartCount = count(session('cart', []));
                @endphp
                <a href="{{ route('cart.index') }}" class="relative hover:underline flex items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13l-1.2 6H19M7 13l.6-3m0 0l1.4-7H5.4M8 10h10" />
                    </svg>
                    <span>Cart</span>

                    {{-- 🔴 Cart Count Badge --}}
                    @if($cartCount > 0)
                        <span class="absolute -top-2 -right-3 bg-red-600 text-xs font-bold px-2 py-0.5 rounded-full">
                            {{ $cartCount }}
                        </span>
                    @endif
                </a>
            </div>
        </div>
    </nav>


    {{-- Main Content --}}
    @yield('content')

    {{-- Footer --}}
    <footer class="bg-gray-800 text-white text-center py-4 mt-10">
        <p>&copy; {{ date('Y') }} A2Z — All Rights Reserved</p>
    </footer>

</body>
</html>
