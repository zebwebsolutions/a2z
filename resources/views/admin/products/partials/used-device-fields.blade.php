<div
    x-data="{
        isUsed: @json(old('is_used', $product->is_used ?? false))
    }"
    class=""
>

    {{-- Used device toggle --}}
    <input type="hidden" name="is_used" value="0">

    <div class="flex items-center gap-3 mb-6">
        <input
            id="is_used"
            type="checkbox"
            name="is_used"
            value="1"
            x-model="isUsed"
            class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
            {{old('is_used', $product->is_used ?? '') === 1 ? 'checked' : ''}}
        >
        <label for="is_used" class="text-sm font-medium text-gray-700">
            Mark as Used Device
        </label>
    </div>

    <template x-if="isUsed">
        <div x-cloak class="grid grid-cols-1 md:grid-cols-2 gap-6">

            {{-- Condition --}}
            <div>
                <label class="block form-label">Condition</label>
                <select name="condition_grade" class="form-select w-full">
                    <option value="">Select condition grade</option>
                    @foreach(['A+','A','B','C'] as $grade)
                        <option value="{{ $grade }}"
                            {{ old('condition_grade', $product->usedDeviceDetails->device_condition ?? '') === $grade ? 'selected' : '' }}>
                             {{ $grade }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Battery Health --}}
            <div>
                <label class="block form-label">Battery Health (%)</label>
                <input
                    type="number"
                    name="battery_health"
                    min="50"
                    max="100"
                    class="form-input w-full"
                    value="{{ old('battery_health', $product->usedDeviceDetails->battery_health ?? '') }}"
                >
            </div>

            {{-- IMEI --}}
            <div>
                <label class="form-label block">IMEI</label>
                <input
                    type="text"
                    name="imei"
                    class="form-input w-full"
                    value="{{ old('imei', $product->usedDeviceDetails->imei ?? '') }}"
                >
            </div>

            {{-- Warranty --}}
            <div>
                <label class="form-label block">Warranty (days)</label>
                <input
                    type="number"
                    name="warranty_days"
                    class="form-input w-full"
                    value="{{ old('warranty_days', $product->usedDeviceDetails->warranty_days ?? '') }}"
                >
            </div>

            {{-- Accessories --}}
            <div class="md:col-span-2">
                <label class="form-label mb-2 block">Included Accessories</label>
                <div class="flex flex-wrap gap-6">

                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="box_available" value="1"
                               class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                               {{ old('box_available', $product->usedDeviceDetails->box_available ?? false) ? 'checked' : '' }}>
                        Box
                    </label>

                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="charger_available" value="1"
                               class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                               {{ old('charger_available', $product->usedDeviceDetails->charger_available ?? false) ? 'checked' : '' }}>
                        Charger
                    </label>

                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="cable_available" value="1"
                               class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                               {{ old('cable_available', $product->usedDeviceDetails->cable_available ?? false) ? 'checked' : '' }}>
                        Cable
                    </label>

                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="headphones_available" value="1"
                               class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                               {{ old('headphones_available', $product->usedDeviceDetails->headphones_available ?? false) ? 'checked' : '' }}>
                        Headphones
                    </label>

                </div>
            </div>

        </div>
    </template>
</div>
