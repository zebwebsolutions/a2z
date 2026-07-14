@php
    $unitRows = collect($inventoryUnits ?? [])->map(function ($unit) {
        return [
            'id' => data_get($unit, 'id'),
            'imei_1' => data_get($unit, 'imei_1'),
            'imei_2' => data_get($unit, 'imei_2'),
            'serial_number' => data_get($unit, 'serial_number'),
            'barcode' => data_get($unit, 'barcode'),
        ];
    })->values();
@endphp

<div class="rounded-lg border border-gray-200 p-4 space-y-4">
    <div class="flex items-center justify-between gap-4">
        <div>
            <h3 class="text-lg font-semibold">Inventory Units</h3>
            <p id="inventory-units-hint" class="text-sm text-gray-600">
                Add individual units only when you want to track IMEI or serial numbers.
            </p>
        </div>

        <button type="button" id="addInventoryUnit" class="rounded bg-gray-200 px-3 py-2 text-sm">
            + Add Unit
        </button>
    </div>

    <div id="inventoryUnitsRows" class="space-y-3"></div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const parentSelect = document.getElementById("parent_category_id");
    const stockInput = document.getElementById("stockInput");
    const stockWrapper = document.getElementById("manualStockField");
    const addUnitButton = document.getElementById("addInventoryUnit");
    const unitsWrapper = document.getElementById("inventoryUnitsRows");
    const unitsHint = document.getElementById("inventory-units-hint");
    const categories = @json($categoryOptions);
    const initialUnits = @json($unitRows);

    let unitIndex = 0;

    function isPhoneCategory() {
        const selectedCategory = categories.find((category) => String(category.id) === parentSelect?.value);
        return Boolean(selectedCategory?.is_phone);
    }

    function buildUnitRow(row = {}) {
        const phone = isPhoneCategory();
        const index = unitIndex++;
        const wrapper = document.createElement("div");
        wrapper.className = "rounded border border-gray-200 p-3 unit-row";
        wrapper.innerHTML = `
            <div class="flex items-center justify-between gap-4 mb-3">
                <p class="text-sm font-medium text-gray-700">Unit</p>
                <button type="button" class="remove-unit text-sm text-red-600">Remove</button>
            </div>
            ${row.id ? `<input type="hidden" name="inventory_units[${index}][id]" value="${row.id}">` : ""}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                ${phone ? `
                    <div>
                        <label class="block text-sm font-medium text-gray-700">IMEI 1</label>
                        <input type="text" name="inventory_units[${index}][imei_1]" value="${row.imei_1 ?? ""}" class="mt-1 w-full border rounded px-3 py-2 unit-imei-1 scanner-friendly-field" autocomplete="off" enterkeyhint="done" data-scanner-field="true">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">IMEI 2</label>
                        <input type="text" name="inventory_units[${index}][imei_2]" value="${row.imei_2 ?? ""}" class="mt-1 w-full border rounded px-3 py-2 scanner-friendly-field" autocomplete="off" enterkeyhint="done" data-scanner-field="true">
                    </div>
                ` : `
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Serial Number</label>
                        <input type="text" name="inventory_units[${index}][serial_number]" value="${row.serial_number ?? ""}" class="mt-1 w-full border rounded px-3 py-2 scanner-friendly-field" autocomplete="off" enterkeyhint="done" data-scanner-field="true">
                    </div>
                `}
                <div>
                    <label class="block text-sm font-medium text-gray-700">Unit Barcode</label>
                    <input type="text" name="inventory_units[${index}][barcode]" value="${row.barcode ?? ""}" class="mt-1 w-full border rounded px-3 py-2 scanner-friendly-field" autocomplete="off" enterkeyhint="done" data-scanner-field="true">
                </div>
            </div>
        `;

        wrapper.querySelector(".remove-unit").addEventListener("click", () => {
            wrapper.remove();
            syncInventoryMode();
        });

        unitsWrapper.appendChild(wrapper);
        syncInventoryMode();
    }

    function renderUnits() {
        const existingRows = Array.from(unitsWrapper.querySelectorAll(".unit-row"))
            .map((row) => ({
                id: row.querySelector('input[name$="[id]"]')?.value || null,
                imei_1: row.querySelector('input[name$="[imei_1]"]')?.value || "",
                imei_2: row.querySelector('input[name$="[imei_2]"]')?.value || "",
                serial_number: row.querySelector('input[name$="[serial_number]"]')?.value || "",
                barcode: row.querySelector('input[name$="[barcode]"]')?.value || "",
            }))
            .filter((row) => row.id || row.imei_1 || row.imei_2 || row.serial_number || row.barcode);

        unitsWrapper.innerHTML = "";
        unitIndex = 0;

        (existingRows.length ? existingRows : initialUnits).forEach((row) => buildUnitRow(row));

        if (!existingRows.length && !initialUnits.length && isPhoneCategory()) {
            buildUnitRow();
        }

        syncInventoryMode();
    }

    function syncInventoryMode() {
        const phone = isPhoneCategory();
        const trackedCount = unitsWrapper.querySelectorAll(".unit-row").length;

        if (unitsHint) {
            unitsHint.textContent = phone
                ? "Phone products are tracked by unit. Stock is calculated from the IMEI rows below."
                : "For non-phone products, stock can stay manual or be calculated from unit rows if you add them.";
        }

        if (!stockInput || !stockWrapper) return;

        const usesUnitStock = phone || trackedCount > 0;
        stockInput.readOnly = usesUnitStock;
        stockInput.classList.toggle("bg-gray-100", usesUnitStock);
        stockInput.value = usesUnitStock ? trackedCount : stockInput.value;

        const label = stockWrapper.querySelector("label");
        if (label) {
            label.textContent = usesUnitStock ? "Stock Quantity (calculated from units)" : "Stock Quantity";
        }
    }

    document.addEventListener("keydown", (event) => {
        const field = event.target.closest("[data-scanner-field='true']");
        if (!field) return;

        if (event.key === "Enter") {
            event.preventDefault();
            event.stopPropagation();
            field.value = field.value.replace(/[\r\n]+/g, "").trim();
            syncInventoryMode();
        }
    }, true);

    document.addEventListener("input", (event) => {
        const field = event.target.closest("[data-scanner-field='true']");
        if (!field) return;

        const cleanedValue = field.value.replace(/[\r\n]+/g, "").trim();
        if (field.value !== cleanedValue) {
            field.value = cleanedValue;
        }
    });

    addUnitButton?.addEventListener("click", () => buildUnitRow());
    parentSelect?.addEventListener("change", renderUnits);

    renderUnits();
});
</script>
