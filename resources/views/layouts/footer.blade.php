<footer class="bg-gray-900 text-gray-300 pt-12 pb-6">
    <div class="container mx-auto px-4 grid grid-cols-1 md:grid-cols-4 gap-10">

        {{-- Logo + About --}}
        <div>
            <h2 class="text-white text-2xl font-bold mb-3">A2Z</h2>
            <p class="text-sm leading-relaxed">
                Your trusted store for Mobiles, Accessories & Gadgets in Kuwait.
                Original products, best prices, fast delivery - every time.
            </p>
        </div>

        {{-- Categories --}}
        <div>
            <h3 class="text-white font-semibold mb-3">Categories</h3>
            <ul class="space-y-2 text-sm">
                @foreach($footerCategories as $cat)
                    <li>
                        <a href="{{ route('category.show', $cat->slug) }}" class="hover:text-white">
                            {{ $cat->name }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>

        {{-- Brands --}}
        <div>
            <h3 class="text-white font-semibold mb-3">Brands</h3>
            <ul class="space-y-2 text-sm">
                @foreach($footerBrands as $brand)
                    <li>
                        <a href="{{ route('brand.index', $brand->slug) }}" class="hover:text-white">
                            {{ $brand->name }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>

        {{-- Support --}}
        <div>
            <h3 class="text-white font-semibold mb-3">Customer Support</h3>
            <ul class="space-y-2 text-sm">
                <li><a href="{{ route('about') }}" class="hover:text-white">About Us</a></li>
                <li><a href="/contact" class="hover:text-white">Contact Us</a></li>
                <li><a href="/warranty-policy" class="hover:text-white">Warranty Policy</a></li>
                <li><a href="/returns-refunds" class="hover:text-white">Returns & Refunds</a></li>
            </ul>

            <div class="mt-4 space-y-1">
                <p class="text-sm">
                    <a href="tel:+96597764165" class="inline-flex items-center gap-2 hover:text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-blue-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.86 19.86 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.86 19.86 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z" />
                        </svg>
                        <span>+965 515 23533</span>
                        <span>+965 977 64165</span>
                    </a>
                </p>
                <p class="text-sm">
                    <a href="mailto:support@a2z.com" class="inline-flex items-center gap-2 hover:text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-blue-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect x="3" y="5" width="18" height="14" rx="2" ry="2" />
                            <path d="M3 7l9 6 9-6" />
                        </svg>
                        <span>support@a2z.com</span>
                    </a>
                </p>
                <p class="text-sm">
                    <span class="inline-flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-blue-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M21 10c0 6-9 12-9 12s-9-6-9-12a9 9 0 1 1 18 0z" />
                            <circle cx="12" cy="10" r="3" />
                        </svg>
                        <span>Khalid Bin Waleed Street,  Kazmi 10 Building, Shop 2</span>
                    </span>
                </p>
            </div>
        </div>

    </div>

    {{-- Bottom Bar --}}
    <div class="border-t border-gray-700 mt-10 pt-4 text-center text-sm text-gray-500">
        &copy; {{ date('Y') }} A2Z - All Rights Reserved.
    </div>
</footer>
