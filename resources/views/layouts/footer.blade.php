<footer class="bg-gray-900 text-gray-300 pt-12 pb-6">
    <div class="container mx-auto px-4 grid grid-cols-1 md:grid-cols-4 gap-10">

        {{-- Logo + About --}}
        <div>
            <h2 class="text-white text-2xl font-bold mb-3">LifeStyleQ8</h2>
            <p class="text-sm leading-relaxed">
                Your trusted store for Mobiles, Accessories & Gadgets in Kuwait.
                Original products, best prices, fast delivery — every time.
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
                        <a href="{{ route('brand.show', $brand->slug) }}" class="hover:text-white">
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
                <li><a href="/contact" class="hover:text-white">Contact Us</a></li>
                <li><a href="/warranty-policy" class="hover:text-white">Warranty Policy</a></li>
                <li><a href="/returns-refunds" class="hover:text-white">Returns & Refunds</a></li>
            </ul>

            <div class="mt-4">
                <p class="text-sm">📞 +965 515 23533</p>
                <p class="text-sm">✉ support@lifestyleq8.com</p>
            </div>
        </div>

    </div>

    {{-- Bottom Bar --}}
    <div class="border-t border-gray-700 mt-10 pt-4 text-center text-sm text-gray-500">
        © {{ date('Y') }} LifeStyleQ8 — All Rights Reserved.
    </div>
</footer>