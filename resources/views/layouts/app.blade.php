<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="shortcut icon" href="{{ asset('favicon.png') }}" type="image/x-icon">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">

        <div id="topLoader"
            class="fixed top-0 left-0 h-[3px] bg-black z-[9999]
                    transition-all duration-300 ease-out"
            style="width:0%; opacity:0;">
        </div>
        
        <div class="min-h-screen bg-gray-100">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                @yield('content')
            </main>

            @include('layouts.footer')
        </div>
        <script src="{{ asset('js/live-search.js') }}" defer></script>
        <script>
            // Preserve exact scroll position when adding to cart and returning back.
            document.addEventListener('click', function (event) {
                const link = event.target.closest('a[href*="/cart/add/"]');
                if (!link) return;

                sessionStorage.setItem('a2z_cart_scroll_y', String(window.scrollY));
                sessionStorage.setItem('a2z_cart_scroll_path', window.location.pathname + window.location.search);
            });

            document.addEventListener('DOMContentLoaded', function () {
                const savedY = sessionStorage.getItem('a2z_cart_scroll_y');
                const savedPath = sessionStorage.getItem('a2z_cart_scroll_path');
                const currentPath = window.location.pathname + window.location.search;

                if (!savedY || !savedPath || savedPath !== currentPath) return;

                const y = parseInt(savedY, 10);
                if (Number.isNaN(y)) return;

                // Run multiple times to override browser hash jump and late layout shifts.
                window.scrollTo(0, y);
                requestAnimationFrame(() => window.scrollTo(0, y));
                setTimeout(() => window.scrollTo(0, y), 120);

                sessionStorage.removeItem('a2z_cart_scroll_y');
                sessionStorage.removeItem('a2z_cart_scroll_path');
            });
        </script>
    </body>
</html>
