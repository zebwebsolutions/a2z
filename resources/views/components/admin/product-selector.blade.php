{{-- Alpine + Sortable initializer --}}

<script>
    window.ajaxProductSelector = function(preselected = []) {
        return {
            search: "",
            results: [],
            selected: [],
            selectedIds: [],
            open: false,
            _sortableInstance: null,

            init() {
                console.log("Preselected:", preselected);

                // Normalize incoming items
                this.selected = (preselected || []).map(p => ({
                    id: p.id,
                    name: p.name,
                    price: p.price,
                    imageUrl: p.image ? '/storage/' + p.image : '/placeholder.png'
                }));

                this.selectedIds = this.selected.map(p => p.id);

                // Initialize Sortable
                this.$nextTick(() => {
                    if (!this.$refs.sortableContainer) return;

                    this._sortableInstance = new Sortable(this.$refs.sortableContainer, {
                        animation: 150,
                        ghostClass: 'bg-yellow-100',
                        handle: '.cursor-move',
                        onEnd: () => this.reorderFromDom()
                    });
                });
            },

            fetchProducts() {
                if (this.search.length < 2) {
                    this.results = [];
                    return;
                }

                fetch(`{{ route('admin.products.search') }}?q=${encodeURIComponent(this.search)}`)
                    .then(res => res.json())
                    .then(data => {
                        this.results = (data || []).map(p => ({
                            id: p.id,
                            name: p.name,
                            price: p.price,
                            imageUrl: p.image ? '/storage/' + p.image : '/placeholder.png'
                        }));
                        this.open = true;
                    })
                    .catch(err => console.error("Search error", err));
            },

            toggleSelection(product) {
                if (this.selectedIds.includes(product.id)) {
                    this.removeProduct(product.id);
                } else {
                    this.addProduct(product);
                }
            },

            addProduct(product) {
                if (this.selectedIds.includes(product.id)) return;

                this.selected.push(product);
                this.selectedIds.push(product.id);

                this.open = false;
            },

            removeProduct(id) {
                this.selected = this.selected.filter(p => p.id !== id);
                this.selectedIds = this.selectedIds.filter(pid => pid !== id);
            },

            // MOST IMPORTANT: Save order exactly as DOM shows
            reorderFromDom() {
                const items = Array.from(this.$refs.sortableContainer.querySelectorAll('.sortable-item'));

                const orderedIds = items.map(item => {
                    return Number(item.querySelector('input[type=hidden]').value);
                });

                this.selected = orderedIds.map(id => this.selected.find(p => p.id === id));
                this.selectedIds = orderedIds;

                console.log("New order:", this.selectedIds);
            }
        };
    }
</script>


{{-- COMPONENT MARKUP --}}
<div x-data="ajaxProductSelector(JSON.parse(atob('{{ base64_encode(json_encode($selectedProducts)) }}')))"
     x-init="init()"
     class="mt-6 relative"
     x-cloak>

    {{-- Search Bar --}}
    <input type="text"
           class="w-full border p-2 rounded mb-3"
           placeholder="Search products..."
           x-model="search"
           @input.debounce.300ms="fetchProducts"
           @click="open = true">

    {{-- SEARCH RESULTS DROPDOWN --}}
    <div class="absolute w-full bg-white border rounded shadow max-h-72 overflow-y-auto divide-y z-50"
         x-show="open && results.length > 0"
         @click.outside="open = false"
         x-cloak>

        <template x-for="product in results" :key="product.id">
            <label class="flex items-center gap-3 p-3 cursor-pointer hover:bg-gray-50"
                   :class="{ 'bg-blue-50': selectedIds.includes(product.id) }">

                <input type="checkbox"
                       class="h-4 w-4"
                       @change="toggleSelection(product)"
                       :checked="selectedIds.includes(product.id)">

                <img :src="product.imageUrl"
                     class="w-12 h-12 rounded object-cover border">

                <div class="flex-1">
                    <p class="font-medium" x-text="product.name"></p>
                    <p class="text-sm text-gray-500">
                        KD <span x-text="product.price"></span>
                    </p>
                </div>

            </label>
        </template>
    </div>



    {{-- SELECTED PRODUCTS + SORTING --}}
    <div class="mt-5">
        <h3 class="font-semibold mb-2">Selected Products (Drag to reorder):</h3>

        <div x-ref="sortableContainer" class="space-y-2">

            <div x-show="selected.length === 0"
                 class="text-gray-500 text-sm">No products selected.</div>

            <template x-for="product in selected" :key="product.id">
                <div class="sortable-item">
                    <div class="flex items-center gap-3 p-2 border rounded bg-gray-50 cursor-move">

                        <img :src="product.imageUrl"
                             class="w-10 h-10 rounded object-cover border">

                        <p class="flex-1" x-text="product.name"></p>

                        <button type="button"
                                class="text-red-600 text-sm"
                                @click="removeProduct(product.id)">
                            Remove
                        </button>

                        {{-- Hidden input sent to backend IN ORDER --}}
                        <input type="hidden" name="{{ $name }}[]" :value="product.id">
                    </div>
                </div>
            </template>

        </div>
    </div>
</div>
