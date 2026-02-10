@extends('admin.layouts.app')

@section('content')
<div class="container mx-auto py-8">
    <h1 class="text-2xl font-bold mb-6">Edit Product</h1>

    <form action="{{ route('admin.products.update', $product) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
        @csrf
        @method('PUT')


        {{-- STORE --}}
        <select name="store_id" class="border p-2 w-full" required>
            <option value="">Select Store</option>
            @foreach($stores as $store)
                <option value="{{ $store->id }}" {{ $product->store_id == $store->id ? 'selected' : '' }}>
                    {{ $store->name }}
                </option>
            @endforeach
        </select>      

        {{-- USED DEVICE FIELDS --}}
        @include('admin.products.partials.used-device-fields', ['product' => $product])

        {{-- MAIN CATEGORY --}}
        <div>
            <label class="block font-medium">Main Category</label>
            <select name="parent_category_id" id="parent_category_id" class="border p-2 w-full" required>
                <option value="">Select Main Category</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}"
                        {{ old('parent_category_id', $product->parent_category_id) == $cat->id ? 'selected' : '' }}>
                        {{ $cat->name }}
                    </option>
                @endforeach
            </select>
        </div>



        {{-- SUBCATEGORY --}}
        <div>
            <label class="block font-medium">Subcategory</label>
            <select name="category_id" id="category_id" class="border p-2 w-full">
                <option value="">Select Subcategory</option>

                @foreach($subcategories as $sub)
                    <option value="{{ $sub->id }}"
                        data-parent="{{ $sub->parent_id }}"
                        {{ old('category_id', $product->category_id) == $sub->id ? 'selected' : '' }}>
                        {{ $sub->name }}
                    </option>
                @endforeach
            </select>
        </div>



        {{-- BRAND --}}
        <select name="brand_id" class="border p-2 w-full">
            <option value="">Select Brand</option>
            @foreach($brands as $brand)
                <option value="{{ $brand->id }}" {{ $product->brand_id == $brand->id ? 'selected' : '' }}>
                    {{ $brand->name }}
                </option>
            @endforeach
        </select>



        {{-- PRODUCT FIELDS --}}
        <input type="text" name="name" value="{{ old('name', $product->name) }}" class="border p-2 w-full" placeholder="Product Name" required>

        <textarea name="description" class="border p-2 w-full" rows="10">{{ old('description', $product->description) }}</textarea>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block font-medium mb-1">Price (KD)</label>
                <input type="number" step="0.01" name="price" value="{{ old('price', $product->price) }}" class="border p-2 w-full" required>
            </div>

            <div>
                <label class="block font-medium mb-1">Cost Price (KD)</label>
                <input type="number" step="0.01" name="cost_price" value="{{ old('cost_price', $product->cost_price) }}" class="border p-2 w-full">
            </div>

            <div>
                <label class="block font-medium mb-1">Stock Quantity</label>
                <input type="number" name="stock" value="{{ old('stock', $product->stock) }}" class="border p-2 w-full" required>
            </div>
        </div>

        {{-- SKU --}}
        <div class="mb-4">
            <label class="block font-semibold mb-1">SKU</label>
            <input 
                type="text" 
                name="sku" 
                value="{{ old('sku', $product->sku ?? '') }}"
                class="w-full border rounded-xl px-3 py-2"
                placeholder="e.g., LSQ-WATCH-001"
            >
        </div>

        {{-- Barcode --}}
        <div>
            <label class="block text-sm font-medium text-gray-700">Barcode</label>
            <input type="text"
                name="barcode"
                value="{{ old('barcode', $product->barcode ?? '') }}"
                class="mt-1 w-full border rounded px-3 py-2">
        </div>

        @if(!empty($product->barcode))
            <div class="mt-3">
                <img
                    src="data:image/png;base64,{{ DNS1D::getBarcodePNG($product->barcode, 'C128') }}"
                    class="h-16"
                />
            </div>
        @endif

        {{-- MAIN IMAGE --}}
        @if($product->image)
            <div class="flex items-center gap-4">
                <img src="{{ asset('storage/' . $product->image) }}" class="w-24 h-24 object-cover rounded shadow">
                <p class="text-sm text-gray-600">Current main image</p>
            </div>
        @endif

        <input type="file" name="image" class="border p-2 w-full" accept="image/*">



        {{-- GALLERY --}}
        <h3 class="text-lg font-semibold mt-6">Product Gallery</h3>

        {{-- EXISTING GALLERY IMAGES --}}
        @if($product->gallery && is_array($product->gallery))
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-2">
                @foreach($product->gallery as $index => $img)
                    <div class="relative gallery-item" data-index="{{ $index }}">
                        <img src="{{ asset('storage/' . $img) }}" class="h-28 w-full object-cover rounded border">

                        <button type="button"
                            class="delete-existing absolute top-1 right-1 bg-red-600 text-white text-xs px-2 py-1 rounded"
                            data-index="{{ $index }}"
                            data-product-id="{{ $product->id }}">
                            Delete
                        </button>
                    </div>
                @endforeach
            </div>
        @endif


        {{-- UPLOAD NEW GALLERY IMAGES --}}
        <div>
            <label class="block font-medium mb-1">Upload Additional Gallery Images</label>
            <input type="file" name="gallery[]" id="galleryInput" class="border p-2 w-full" accept="image/*" multiple>

            <div id="galleryPreview" class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-3"></div>
        </div>



        {{-- SPECIFICATIONS --}}
        <h3 class="text-lg font-semibold mt-2">Specifications</h3>

        <div id="specs-wrapper">
            @if($product->specs)
                @foreach($product->specs as $key => $value)
                    <div class="flex gap-2 mb-2 spec-row">
                        <input type="text" name="specs_keys[]" value="{{ $key }}" class="border p-2 w-1/2" placeholder="Spec name">
                        <input type="text" name="specs_values[]" value="{{ $value }}" class="border p-2 w-1/2" placeholder="Spec value">
                        <button type="button" class="remove-spec bg-red-500 text-white px-2 rounded">X</button>
                    </div>
                @endforeach
            @endif
        </div>

        <button type="button" id="add-spec" class="bg-gray-200 px-3 py-1 rounded">+ Add Specification</button>




        {{-- SUBMIT --}}
        <button type="submit" class="block bg-blue-600 text-white px-4 py-2 rounded">
            Update Product
        </button>




        {{-- JS: Add spec row --}}
        <script>
            function attachRemoveEvents() {
                document.querySelectorAll('.remove-spec').forEach(btn => {
                    btn.onclick = () => btn.closest('.spec-row').remove();
                });
            }
            attachRemoveEvents();

            document.getElementById('add-spec').addEventListener('click', () => {
                document.getElementById('specs-wrapper').insertAdjacentHTML('beforeend', `
                    <div class="flex gap-2 mb-2 spec-row">
                        <input type="text" name="specs_keys[]" class="border p-2 w-1/2" placeholder="Spec name">
                        <input type="text" name="specs_values[]" class="border p-2 w-1/2" placeholder="Spec value">
                        <button type="button" class="remove-spec bg-red-500 text-white px-2 rounded">X</button>
                    </div>
                `);
                attachRemoveEvents();
            });
        </script>



        {{-- JS: Filter Subcategories --}}
        <script>
            document.addEventListener("DOMContentLoaded", () => {
                const parentSelect = document.getElementById("parent_category_id");
                const subSelect = document.getElementById("category_id");

                function filterSubcategories() {
                    const parentId = parentSelect.value;
                    Array.from(subSelect.options).forEach(opt => {
                        if (!opt.value) return;
                        opt.hidden = opt.dataset.parent !== parentId;
                    });
                }

                parentSelect.addEventListener("change", filterSubcategories);
                filterSubcategories();
            });
        </script>



        {{-- JS: New Gallery Preview --}}
        <script>
        document.addEventListener("DOMContentLoaded", () => {
            const input = document.getElementById("galleryInput");
            const preview = document.getElementById("galleryPreview");

            if (!input) return;

            input.addEventListener("change", function () {
                preview.innerHTML = "";

                Array.from(this.files).forEach((file, index) => {
                    const reader = new FileReader();
                    reader.onload = e => {
                        preview.insertAdjacentHTML("beforeend", `
                            <div class="relative border rounded overflow-hidden">
                                <img src="${e.target.result}" class="h-28 w-full object-cover">
                                <button type="button"
                                    class="removeGallery absolute top-1 right-1 bg-red-600 text-white rounded-full w-6 h-6 flex items-center justify-center"
                                    data-index="${index}">
                                    ×
                                </button>
                            </div>
                        `);
                    };
                    reader.readAsDataURL(file);
                });
            });

            document.addEventListener("click", e => {
                if (!e.target.classList.contains("removeGallery")) return;

                const index = e.target.dataset.index;
                let dt = new DataTransfer();

                Array.from(input.files)
                    .filter((file, i) => i != index)
                    .forEach(file => dt.items.add(file));

                input.files = dt.files;
                e.target.parentElement.remove();
            });
        });
        </script>

        {{-- JS: Delete Existing Gallery Image --}}
    <script>
    document.addEventListener('DOMContentLoaded', function () {

        const token = document.querySelector('input[name="_token"]').getAttribute('value');
        const popup = document.getElementById('confirmPopup');
        const btnCancel = document.getElementById('confirmCancel');
        const btnDelete = document.getElementById('confirmDelete');

        let deleteTarget = {
            productId: null,
            index: null,
            element: null,
        };

        // OPEN CONFIRM POPUP
        document.querySelectorAll('.delete-existing').forEach(btn => {
            btn.addEventListener('click', function () {

                deleteTarget.productId = this.dataset.productId;
                deleteTarget.index = this.dataset.index;
                deleteTarget.element = this.closest('.gallery-item');

                // show popup
                popup.classList.remove('hidden');
            });
        });

        // CANCEL DELETION
        btnCancel.addEventListener('click', function () {
            popup.classList.add('hidden');
        });

        // CONFIRM DELETE
        btnDelete.addEventListener('click', async function () {

            const { productId, index, element } = deleteTarget;

            popup.classList.add('hidden'); // hide popup

            try {
                const res = await fetch(`/admin/products/${productId}/gallery/${index}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                });

                if (!res.ok) {
                    showToast("Failed to delete image", "error");
                    return;
                }

                const data = await res.json();

                // Remove DOM element
                element.remove();

                // Re-index remaining gallery items
                const items = document.querySelectorAll('.gallery-item');
                items.forEach((item, idx) => {
                    item.dataset.index = idx;
                    item.querySelector('.delete-existing').dataset.index = idx;
                });

                showToast("Image deleted!", "success");

            } catch (error) {
                console.error(error);
                showToast("Network error", "error");
            }

        });

    });
    </script>





        <script>
        function showToast(message, type = "success") {
            const container = document.getElementById("toastContainer");

            const colors = {
                success: "bg-green-600",
                error: "bg-red-600",
                warning: "bg-yellow-600",
            };

            const toast = document.createElement("div");
            toast.className = `${colors[type]} text-white px-4 py-2 rounded shadow-lg animate-fadeIn`;
            toast.innerText = message;

            container.appendChild(toast);

            // Auto remove toast after 2.5 seconds
            setTimeout(() => {
                toast.classList.add("opacity-0", "transition", "duration-500");
                setTimeout(() => toast.remove(), 500);
            }, 2500);
        }
        </script>

        <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-6px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fadeIn {
            animation: fadeIn 0.25s ease-out;
        }
        </style>


    </form>
</div>

{{-- INLINE CONFIRM POPUP TEMPLATE --}}
<div id="confirmPopup" 
    class="hidden fixed inset-0 bg-black bg-opacity-20 z-50 flex items-center justify-center">
    
    <div class="bg-white p-5 rounded shadow-lg w-72 animate-confirmFade">
        
        <p class="font-semibold mb-4 text-gray-800">
            Are you sure you want to delete this image?
        </p>

        <div class="flex justify-end gap-3">
            <button id="confirmCancel" 
                    class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">
                Cancel
            </button>

            <button id="confirmDelete" 
                    class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
                Delete
            </button>
        </div>

    </div>
</div>

@endsection
