<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="shortcut icon" href="{{ asset('favicon.png') }}" type="image/x-icon">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        @php
            $defaultTitle = 'A to Z Electronics & Repairing | New & Used Phones, Tablets, Accessories & Repairs';
            $pageTitle = trim($__env->yieldContent('title')) ?: $defaultTitle;
            $defaultDescription = 'A2Z Kuwait offers new and used phones, tablets, accessories, and expert repair services for phones, tablets, and smart watches in Sharq, Kuwait.';
            $pageDescription = trim($__env->yieldContent('meta_description')) ?: $defaultDescription;
            $metaRobots = trim($__env->yieldContent('meta_robots')) ?: 'index,follow';
            $canonicalUrl = trim($__env->yieldContent('canonical')) ?: url()->current();
            $ogTitle = trim($__env->yieldContent('og_title')) ?: $pageTitle;
            $ogDescription = trim($__env->yieldContent('og_description')) ?: $pageDescription;
            $ogImage = trim($__env->yieldContent('og_image')) ?: asset('favicon.png');
            $organizationSchema = [
                '@context' => 'https://schema.org',
                '@type' => 'Organization',
                'name' => 'A to Z Electronics & Repairing',
                'alternateName' => 'A2Z Kuwait',
                'url' => url('/'),
                'logo' => asset('images/a2z-logo.png'),
                'contactPoint' => [[
                    '@type' => 'ContactPoint',
                    'telephone' => '+96597764165',
                    'contactType' => 'customer service',
                    'areaServed' => 'KW',
                ]],
                'address' => [
                    '@type' => 'PostalAddress',
                    'streetAddress' => 'Khalid Bin Waleed Street, Kazmi 10 Building, Shop 2',
                    'addressLocality' => 'Sharq',
                    'addressCountry' => 'KW',
                ],
            ];
        @endphp

        <title>{{ $pageTitle }}</title>
        <meta name="description" content="{{ $pageDescription }}">
        <meta name="robots" content="{{ $metaRobots }}">
        <link rel="canonical" href="{{ $canonicalUrl }}">
        <link rel="sitemap" type="application/xml" title="Sitemap" href="{{ url('/sitemap.xml') }}">

        <meta property="og:type" content="website">
        <meta property="og:site_name" content="A to Z Electronics & Repairing">
        <meta property="og:title" content="{{ $ogTitle }}">
        <meta property="og:description" content="{{ $ogDescription }}">
        <meta property="og:url" content="{{ request()->fullUrl() }}">
        <meta property="og:image" content="{{ $ogImage }}">

        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $ogTitle }}">
        <meta name="twitter:description" content="{{ $ogDescription }}">
        <meta name="twitter:image" content="{{ $ogImage }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <script type="application/ld+json">
            @json($organizationSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        </script>
    </head>
    <body class="font-sans antialiased">
        <!-- Google tag (gtag.js) -->
        <script async src="https://www.googletagmanager.com/gtag/js?id=G-25WKKLJPT4"></script>
        <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());

        gtag('config', 'G-25WKKLJPT4');
        </script>

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
