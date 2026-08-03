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
            @if($errors->has('image') || $errors->has('gallery') || $errors->has('gallery.*'))
                <p class="mt-2 text-sm">Please select the image files again. Browsers do not retain file inputs after submission.</p>
            @endif
        </div>
    @endif


    <form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data" class="space-y-5">
        @csrf

        <section class="overflow-hidden rounded-xl border border-blue-200 bg-white shadow-sm">
            <div class="flex items-center gap-3 border-b border-blue-200 bg-blue-50 px-5 py-4">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-600 text-sm font-bold text-white">1</span>
                <div>
                    <h2 class="text-xl font-semibold text-gray-900">Product Details</h2>
                    <p class="text-sm text-gray-600">Basic information, pricing, categories, barcode, and inventory.</p>
                </div>
            </div>
            <div class="space-y-5 p-5">

        {{-- STORE --}}
        <div>
            <label class="block font-medium mb-1">Store</label>
            <select name="store_id" class="border p-2 w-full" required>
                <option value="">Select Store</option>
                @foreach($stores as $store)
                    <option value="{{ $store->id }}" {{ (string) old('store_id') === (string) $store->id ? 'selected' : '' }}>
                        {{ $store->name }}
                    </option>
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
                    <option value="{{ $brand->id }}" {{ (string) old('brand_id') === (string) $brand->id ? 'selected' : '' }}>
                        {{ $brand->name }}
                    </option>
                @endforeach
            </select>
        </div>


        {{-- PRODUCT NAME --}}
        <div>
            <label class="block font-medium mb-1">Product Name</label>
            <input type="text" name="name" value="{{ old('name') }}" class="border p-2 w-full" required>
        </div>


        {{-- DESCRIPTION --}}
        <div>
            <label class="block font-medium mb-1">Description</label>
            <textarea name="description" class="border p-2 w-full" rows="8">{{ old('description') }}</textarea>
        </div>


        {{-- PRICE & STOCK --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

            <div>
                <label class="block font-medium mb-1">Price (KD)</label>
                <input type="number" step="0.01" name="price" value="{{ old('price') }}" class="border p-2 w-full" required>
            </div>

            <div>
                <label class="block font-medium mb-1">Cost Price (KD)</label>
                <input type="number" step="0.01" name="cost_price" value="{{ old('cost_price') }}" class="border p-2 w-full">
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

            </div>
        </section>

        <section class="overflow-hidden rounded-xl border border-purple-200 bg-white shadow-sm">
            <div class="flex items-center gap-3 border-b border-purple-200 bg-purple-50 px-5 py-4">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-purple-600 text-sm font-bold text-white">2</span>
                <div>
                    <h2 class="text-xl font-semibold text-gray-900">Product Images</h2>
                    <p class="text-sm text-gray-600">Choose the main product image and optional gallery images.</p>
                </div>
            </div>
            <div class="grid gap-6 p-5 xl:grid-cols-2">

        {{-- MAIN PRODUCT IMAGE --}}
        <div class="rounded-lg border border-purple-100 bg-purple-50 p-4">
            <h3 class="text-lg font-semibold text-gray-800">Main Product Image</h3>
            <p class="mt-1 text-sm text-gray-600">
                This is the primary image shown in product listings and on the product page.
            </p>
            @if(isset($product) && $product->image)
                <img src="{{ asset('storage/' . $product->image) }}" class="h-32 rounded mb-2">
            @endif
            <label for="mainProductImage" class="mt-3 block font-medium text-gray-700">
                Upload Main Product Image
            </label>
            <input
                type="file"
                name="image"
                id="mainProductImage"
                class="mt-1 border p-2 w-full"
                accept="image/*"
            >
            @error('image')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- PRODUCT GALLERY --}}
        <div class="rounded-lg border border-purple-100 bg-purple-50 p-4">
            <h3 class="text-lg font-semibold text-gray-800">Product Gallery</h3>
            <label for="galleryInput" class="mt-3 block font-medium text-gray-700">
                Upload Gallery Images
            </label>
            <input
                type="file"
                name="gallery[]"
                id="galleryInput"
                class="mt-1 border p-2 w-full"
                accept="image/*"
                multiple
            >

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

            </div>
        </section>

        {{-- SPECIFICATIONS --}}
        <section class="overflow-hidden rounded-xl border border-emerald-200 bg-white shadow-sm">
            <div class="flex items-center gap-3 border-b border-emerald-200 bg-emerald-50 px-5 py-4">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-600 text-sm font-bold text-white">3</span>
                <div>
                    <h2 class="text-xl font-semibold text-gray-900">Specifications</h2>
                    <p class="text-sm text-gray-600">Add searchable technical details such as RAM, storage, size, or color.</p>
                </div>
            </div>
            <div class="space-y-4 p-5">
                @php
                    $oldSpecKeys = array_values((array) old('specs_keys', []));
                    $oldSpecValues = array_values((array) old('specs_values', []));
                    $oldSpecRowCount = max(count($oldSpecKeys), count($oldSpecValues));
                @endphp
                <input type="hidden" name="specs_present" value="1">
                <div id="specs-wrapper">
                    @for($index = 0; $index < $oldSpecRowCount; $index++)
                        <div class="spec-row mb-2 flex flex-col gap-2 sm:flex-row">
                            <input type="text" name="specs_keys[]" value="{{ $oldSpecKeys[$index] ?? '' }}" class="w-full border p-2 sm:w-1/2" placeholder="Spec name">
                            <input type="text" name="specs_values[]" value="{{ $oldSpecValues[$index] ?? '' }}" class="w-full border p-2 sm:w-1/2" placeholder="Spec value">
                            <button type="button" class="remove-spec rounded bg-red-500 px-3 py-2 text-white">Remove</button>
                        </div>
                    @endfor
                </div>

                <button type="button" id="add-spec" class="rounded bg-emerald-100 px-3 py-2 font-medium text-emerald-800 hover:bg-emerald-200">
                    + Add Specification
                </button>
            </div>
        </section>

        <section class="overflow-hidden rounded-xl border border-amber-200 bg-white shadow-sm">
            <div class="flex items-center gap-3 border-b border-amber-200 bg-amber-50 px-5 py-4">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-amber-500 text-sm font-bold text-white">4</span>
                <div>
                    <h2 class="text-xl font-semibold text-gray-900">Product Variations</h2>
                    <p class="text-sm text-gray-600">Connect this product to its color and storage alternatives.</p>
                </div>
            </div>
            <div class="grid gap-5 p-5 xl:grid-cols-2">
                <div class="rounded-lg border border-amber-100 bg-amber-50 p-4">
                    <h3 class="text-lg font-semibold">Colour Variants</h3>
                    <p class="mt-1 text-sm text-gray-600">
                        Search and select other products that are the same model/storage but different colours.
                        Linking here will make the colour panel appear on all selected products.
                    </p>

                    <x-admin.product-selector :selected-products="$colourVariantProducts" name="colour_variants" />
                </div>

                <div class="rounded-lg border border-amber-100 bg-amber-50 p-4">
                    <h3 class="text-lg font-semibold">Storage Variants</h3>
                    <p class="mt-1 text-sm text-gray-600">
                        Search and select other products that are the same model/colour but different storage.
                        Linking here will make the storage panel appear on all selected products.
                    </p>

                    <x-admin.product-selector :selected-products="$storageVariantProducts" name="storage_variants" />
                </div>
            </div>
        </section>

        {{-- SUBMIT --}}
        <button type="submit" class="block rounded-lg bg-blue-600 px-6 py-3 font-semibold text-white shadow-sm hover:bg-blue-700">
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
        <div class="spec-row mb-2 flex flex-col gap-2 sm:flex-row">
            <input type="text" name="specs_keys[]" class="w-full border p-2 sm:w-1/2" placeholder="Spec name">
            <input type="text" name="specs_values[]" class="w-full border p-2 sm:w-1/2" placeholder="Spec value">
            <button type="button" class="remove-spec rounded bg-red-500 px-3 py-2 text-white">Remove</button>
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
