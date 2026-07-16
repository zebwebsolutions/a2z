@extends('admin.layouts.app')

@section('content')
<div class="container mx-auto py-8">
    @php
        $categoryOptions = $categories->map(fn ($cat) => [
            'id' => $cat->id,
            'is_phone' => str_contains(strtolower($cat->slug ?: $cat->name), 'phone')
                || str_contains(strtolower($cat->slug ?: $cat->name), 'mobile')
                || str_contains(strtolower($cat->slug ?: $cat->name), 'smartphone')
                || str_contains(strtolower($cat->slug ?: $cat->name), 'iphone'),
        ])->values();
        $subcategoryOptions = $subcategories->map(fn ($sub) => [
            'id' => $sub->id,
            'name' => $sub->name,
            'parent_id' => $sub->parent_id,
        ])->values();
    @endphp

    <h1 class="text-2xl font-bold mb-6">Add Product</h1>

    {{-- VALIDATION ERRORS --}}
    @if ($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 p-4 rounded mb-4">
            <ul class="list-disc pl-6">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif


    <form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data" class="space-y-5">
        @csrf

        {{-- STORE --}}
        <div>
            <label class="block font-medium mb-1">Store</label>
            <select name="store_id" class="border p-2 w-full" required>
                <option value="">Select Store</option>
                @foreach($stores as $store)
                    <option value="{{ $store->id }}">{{ $store->name }}</option>
                @endforeach
            </select>
        </div>

        @php
            $product = new \App\Models\Product();
        @endphp
        @include('admin.products.partials.used-device-fields', [
            'product' => $product
        ])

        {{-- MAIN CATEGORY --}}
        <div>
            <label class="block font-medium mb-1">Main Category</label>
            <select name="parent_category_id" id="parent_category_id" class="border p-2 w-full" required>
                <option value="">Select Main Category</option>

                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ old('parent_category_id') == $cat->id ? 'selected' : '' }}>
                        {{ $cat->name }}
                    </option>
                @endforeach
            </select>
        </div>


        {{-- SUBCATEGORY --}}
        <div>
            <label class="block font-medium mb-1">Subcategory</label>
            <select name="category_id" id="category_id" class="border p-2 w-full">
                <option value="">Select Subcategory</option>
            </select>
        </div>


        {{-- BRAND --}}
        <div>
            <label class="block font-medium mb-1">Brand</label>
            <select name="brand_id" class="border p-2 w-full">
                <option value="">Select Brand</option>
                @foreach($brands as $brand)
                    <option value="{{ $brand->id }}">
                        {{ $brand->name }}
                    </option>
                @endforeach
            </select>
        </div>


        {{-- PRODUCT NAME --}}
        <div>
            <label class="block font-medium mb-1">Product Name</label>
            <input type="text" name="name" class="border p-2 w-full" required>
        </div>


        {{-- DESCRIPTION --}}
        <div>
            <label class="block font-medium mb-1">Description</label>
            <textarea name="description" class="border p-2 w-full" rows="8"></textarea>
        </div>


        {{-- PRICE & STOCK --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

            <div>
                <label class="block font-medium mb-1">Price (KD)</label>
                <input type="number" step="0.01" name="price" class="border p-2 w-full" required>
            </div>

            <div>
                <label class="block font-medium mb-1">Cost Price (KD)</label>
                <input type="number" step="0.01" name="cost_price" class="border p-2 w-full">
            </div>

            <div id="manualStockField">
                <label class="block font-medium mb-1">Stock Quantity</label>
                <input type="number" name="stock" id="stockInput" value="{{ old('stock', 0) }}" class="border p-2 w-full">
            </div>

        </div>

        {{-- SKU --}}
        <div class="mb-4">
            <label class="block font-semibold mb-1">SKU</label>
            <input 
                type="text" 
                name="sku" 
                value="{{ old('sku', $product->sku ?? '') }}"
                class="w-full border px-3 py-2"
                placeholder="e.g., LSQ-WATCH-001"
            >
        </div>

        @php
            $displayBarcode = old('barcode', $product->barcode ?? '');
        @endphp
        <div>
            <label class="block font-semibold text-gray-700">Barcode</label>
            <input
                type="text"
                name="barcode"
                value="{{ $displayBarcode }}"
                class="mt-1 w-full border rounded px-3 py-2"
                placeholder="Leave blank to auto-generate"
                autocomplete="off"
                enterkeyhint="done"
                data-scanner-field="true"
            >
        </div>

        @if(!empty($displayBarcode))
            <div class="mt-3">
                <img
                    src="data:image/png;base64,{{ DNS1D::getBarcodePNG($displayBarcode, 'C128') }}"
                    class="h-16"
                />
            </div>
        @endif

        @include('admin.products.partials.inventory-units', [
            'inventoryUnits' => old('inventory_units', []),
            'categoryOptions' => $categoryOptions,
        ])


        {{-- Main image (existing) --}}
        <div>
            <label class="block font-semibold text-gray-700">Main Image</label>
            @if(isset($product) && $product->image)
                <img src="{{ asset('storage/' . $product->image) }}" class="h-32 rounded mb-2">
            @endif
            <input type="file" name="image" accept="image/*">
        </div>

        {{-- Gallery --}}
        <div class="mt-4">
            <label class="block font-semibold text-gray-700">Gallery Images</label>
            <input type="file" name="gallery[]" id="galleryInput" accept="image/*" multiple>

            <div id="galleryPreview" class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-3"></div>

            {{-- Existing images (edit mode) --}}
            @if(isset($product) && $product->gallery && count($product->gallery))
                <h4 class="mt-3 font-semibold text-gray-700">Existing Gallery</h4>
                <div id="existingGallery" class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-2">
                    @foreach($product->gallery as $img)
                        <div class="relative">
                            <img src="{{ asset('storage/' . $img) }}" class="h-28 w-full object-cover rounded">
                            {{-- Optional: add delete button per existing image (requires AJAX route) --}}
                        </div>
                    @endforeach
                </div>
            @endif
        </div>


        {{-- SPECIFICATIONS --}}
        <h3 class="text-lg font-semibold mt-6">Specifications</h3>
        <div id="specs-wrapper">
            {{-- Will be filled dynamically --}}
        </div>

        <button type="button" id="add-spec" class="bg-gray-200 px-3 py-1 rounded">+ Add Specification</button>

        <div class="rounded-lg border border-gray-200 p-4">
            <h3 class="text-lg font-semibold">Colour Variants</h3>
            <p class="mt-1 text-sm text-gray-600">
                Search and select other products that are the same model/storage but different colours.
                Linking here will make the colour panel appear on all selected products.
            </p>

            <x-admin.product-selector :selected-products="[]" name="colour_variants" />
        </div>

        {{-- SUBMIT --}}
        <button type="submit" class="block bg-blue-600 text-white px-4 py-2 rounded">
            Save Product
        </button>

    </form>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const parentSelect = document.getElementById("parent_category_id");
    const subSelect = document.getElementById("category_id");
    const input = document.getElementById("galleryInput");
    const preview = document.getElementById("galleryPreview");
    const categories = @json($categoryOptions);
    const subcategories = @json($subcategoryOptions);
    const selectedSubcategoryId = @json(old('category_id'));

    function filterSubcategories() {
        if (!parentSelect || !subSelect) return;

        const parentId = parentSelect.value;
        const currentValue = subSelect.value || selectedSubcategoryId;
        const matchingSubcategories = subcategories.filter((sub) => String(sub.parent_id) === parentId);

        subSelect.innerHTML = '<option value="">Select Subcategory</option>';

        matchingSubcategories.forEach((sub) => {
            const option = document.createElement("option");
            option.value = sub.id;
            option.textContent = sub.name;

            if (String(currentValue) === String(sub.id)) {
                option.selected = true;
            }

            subSelect.appendChild(option);
        });
    }

    parentSelect?.addEventListener("change", filterSubcategories);
    filterSubcategories();

    if (!input) return;

    input.addEventListener("change", function () {
        preview.innerHTML = ""; // clear old preview

        Array.from(this.files).forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = e => {
                preview.insertAdjacentHTML(
                    "beforeend",
                    `
                    <div class="relative border rounded overflow-hidden">
                        <img src="${e.target.result}" class="h-28 w-full object-cover">
                        <button type="button" 
                                class="removeGallery absolute top-1 right-1 bg-red-600 text-white rounded-full w-6 h-6 flex items-center justify-center"
                                data-index="${index}">
                            ×
                        </button>
                    </div>
                    `
                );
            };
            reader.readAsDataURL(file);
        });
    });

    document.addEventListener("click", function (e) {
        if (!e.target.classList.contains("removeGallery")) return;

        const index = parseInt(e.target.dataset.index, 10);
        let dt = new DataTransfer();

        let inputFiles = input.files;

        Array.from(inputFiles)
            .filter((file, i) => i !== index)
            .forEach(file => dt.items.add(file));

        input.files = dt.files;
        e.target.parentElement.remove();
        // Re-index remaining buttons' data-index if needed (left as exercise)
    });
});
</script>


{{-- JS: ADD/REMOVE SPECS --}}
<script>
document.getElementById('add-spec').addEventListener('click', function () {
    const wrapper = document.getElementById('specs-wrapper');
    const html = `
        <div class="flex gap-2 mb-2 spec-row">
            <input type="text" name="specs_keys[]" class="border p-2 w-1/2" placeholder="Spec name">
            <input type="text" name="specs_values[]" class="border p-2 w-1/2" placeholder="Spec value">
            <button type="button" class="remove-spec bg-red-500 text-white px-2 rounded">X</button>
        </div>
    `;
    wrapper.insertAdjacentHTML('beforeend', html);
    attachRemoveEvents();
});

function attachRemoveEvents() {
    document.querySelectorAll('.remove-spec').forEach(btn => {
        btn.onclick = function () {
            this.closest('.spec-row').remove();
        };
    });
}

attachRemoveEvents();
</script>


{{-- JS: FILTER SUBCATEGORIES --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    const parentSelect = document.getElementById('parent_category_id');
    const subSelect = document.getElementById('category_id');

    function filterSubcategories() {
        const parentId = parentSelect.value;

        Array.from(subSelect.options).forEach(opt => {
            if (!opt.value) return;
            opt.hidden = opt.getAttribute('data-parent') !== parentId;
        });

        subSelect.value = "";
    }

    parentSelect.addEventListener('change', filterSubcategories);
    filterSubcategories();
});
</script>

@endsection
