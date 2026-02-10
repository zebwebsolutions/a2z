<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>A2Z Admin</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.2/Sortable.min.js"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen">

    <div class="flex flex-row h-screen w-full">
        {{-- Sidebar (20%) --}}
        <aside class="w-1/5 bg-blue-800 text-white flex flex-col">
            {{-- Header --}}
            <div class="p-6 text-center border-b border-blue-700">
                <h1 class="text-2xl text-white font-bold"><a href="{{ route('admin.dashboard') }}">A2Z</a></h1>
                <p class="text-sm text-blue-300">Admin Panel</p>
            </div>

            {{-- Navigation --}}
            <nav class="flex-1 overflow-y-auto p-4 space-y-2">
                @if(auth()->user()->canView('dashboard'))
                <a href="{{ route('admin.dashboard') }}" 
                   class="flex items-center px-3 py-2 rounded-md transition duration-200 {{ request()->routeIs('admin.dashboard') ? 'bg-blue-700 font-semibold' : 'hover:bg-blue-700' }}">
                    <i data-lucide="layout-dashboard" class="w-5 h-5 mr-2"></i> Dashboard
                </a>
                @endif

                @if(auth()->user()->canView('products'))
                <a href="{{ route('admin.products.index') }}" 
                   class="flex items-center px-3 py-2 rounded-md transition duration-200 {{ request()->routeIs('admin.products.*') ? 'bg-blue-700 font-semibold' : 'hover:bg-blue-700' }}">
                    <i data-lucide="shopping-cart" class="w-5 h-5 mr-2"></i> Products
                </a>
                @endif

                @if(auth()->user()->canView('categories'))
                <a href="{{ route('admin.categories.index') }}" 
                   class="flex items-center px-3 py-2 rounded-md transition duration-200 {{ request()->routeIs('admin.categories.*') ? 'bg-blue-700 font-semibold' : 'hover:bg-blue-700' }}">
                    <i data-lucide="folder" class="w-5 h-5 mr-2"></i> Categories
                </a>
                @endif

                @if(auth()->user()->canView('brands'))
                <a href="{{ route('admin.brands.index') }}"
                    class="flex items-center px-3 py-2 rounded-md transition duration-200 {{ request()->routeIs('admin.brands.*') ? 'bg-blue-700 font-semibold' : 'hover:bg-blue-700' }}">
                    <i data-lucide="tag" class="w-5 h-5 mr-2"></i> Brands
                </a>
                @endif

                @if(auth()->user()->canView('stores'))
                <a href="{{ route('admin.stores.index') }}" 
                   class="flex items-center px-3 py-2 rounded-md transition duration-200 {{ request()->routeIs('admin.stores.*') ? 'bg-blue-700 font-semibold' : 'hover:bg-blue-700' }}">
                    <i data-lucide="store" class="w-5 h-5 mr-2"></i> Stores
                </a>
                @endif

                @if(auth()->user()->canView('repairs'))
                <a href="{{ route('admin.repairs.index') }}" 
                   class="flex items-center px-3 py-2 rounded-md transition duration-200 {{ request()->routeIs('admin.repairs.*') ? 'bg-blue-700 font-semibold' : 'hover:bg-blue-700' }}">
                    <i data-lucide="wrench" class="w-5 h-5 mr-2"></i> Repairs
                </a>
                @endif

                @if(auth()->user()->canView('spare-parts'))
                <a href="{{ route('admin.spare-parts.index') }}"
                class="flex items-center px-3 py-2 rounded-md transition duration-200
                {{ request()->routeIs('admin.spare-parts.*') ? 'bg-blue-700 font-semibold' : 'hover:bg-blue-700' }}">
                    <i data-lucide="tablet-smartphone" class="w-5 h-5 mr-2"></i> Spare Parts
                </a>
                @endif

                @if(auth()->user()->canView('orders'))
                <a href="{{ route('admin.orders.index') }}" 
                class="flex items-center px-3 py-2 rounded-md transition duration-200 {{ request()->routeIs('admin.orders.*') ? 'bg-blue-700 font-semibold' : 'hover:bg-blue-700' }}">
                    <i data-lucide="package" class="w-5 h-5 mr-2"></i> Orders
                </a>
                @endif

                @if(auth()->user()->canView('sliders'))
                <a href="{{ route('admin.sliders.index') }}" 
                class="flex items-center px-3 py-2 rounded-md transition duration-200 {{ request()->routeIs('admin.sliders.*') ? 'bg-blue-700 font-semibold' : 'hover:bg-blue-700' }}">
                    <i data-lucide="image" class="w-5 h-5 mr-2"></i> Sliders
                </a>
                @endif

                @if(auth()->user()->canView('home-sections'))
                <a href="{{ route('admin.home-sections.index') }}" 
                class="flex items-center px-3 py-2 rounded-md transition duration-200 {{ request()->routeIs('admin.home-sections.*') ? 'bg-blue-700 font-semibold' : 'hover:bg-blue-700' }}">
                    <i data-lucide="layout" class="w-5 h-5 mr-2"></i> Home Sections
                </a>
                @endif

                @if(auth()->user()->canView('users'))
                <a href="{{ route('admin.users.index') }}"
                class="flex items-center px-3 py-2 rounded-md transition duration-200 {{ request()->routeIs('admin.users.*') ? 'bg-blue-700 font-semibold' : 'hover:bg-blue-700' }}">
                    <i data-lucide="user" class="w-5 h-5 mr-2"></i>Users
                </a>
                @endif
            </nav>

            {{-- Footer --}}
            <div class="p-4 border-t border-blue-700 text-sm text-center text-blue-300">
                &copy; {{ date('Y') }} A2Z
            </div>
        </aside>

        {{-- Main Content (80%) --}}
        <div class="w-4/5 flex flex-col">
            {{-- Top Bar --}}
            <header class="bg-white shadow px-8 py-4 flex justify-between items-center sticky top-0 z-10">
                <h2 class="text-2xl font-bold">@yield('title', 'Dashboard')</h2>
                <div class="flex items-center gap-4">
                    <a href="{{ route('home') }}" class="text-blue-600 hover:underline" target="_blank">View Site</a>
                    <span class="text-gray-600 flex items-center"><i data-lucide="user" class="w-5 h-5 mr-1"></i> {{ Auth::user()->name ?? 'Admin' }}</span>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700">Logout</button>
                    </form>
                </div>
            </header>

            {{-- Main Page Content --}}
            <main class="flex-1 p-8 overflow-y-auto">
                {{-- Flash Messages --}}
                @if(session('success'))
                    <div class="bg-green-100 text-green-800 border border-green-300 px-4 py-2 mb-4 rounded">
                        {{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="bg-red-100 text-red-800 border border-red-300 px-4 py-2 mb-4 rounded">
                        {{ session('error') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="bg-red-100 text-red-800 border border-red-300 px-4 py-3 rounded mb-4">
                        <p class="font-semibold mb-2">There were some errors with your submission:</p>
                        <ul class="list-disc pl-6 space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Page Content --}}
                @yield('content')
            </main>
        </div>
    </div>

    <div id="toastContainer" class="fixed top-4 right-4 z-50 space-y-3"></div>

    {{-- Optional Scripts --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @yield('scripts')
</body>
</html>
