<?php

namespace App\Http\Requests\Products;

use Illuminate\Foundation\Http\FormRequest;

class StoreUsedDeviceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'condition_grade'   => 'required|in:A+,A,B,C',
            'battery_health'    => 'nullable|integer|min:50|max:100',

            'box_available'     => 'nullable|boolean',
            'cable_available'   => 'nullable|boolean',
            'charger_available' => 'nullable|boolean',
            'headphones_available' => 'nullable|boolean',

            'warranty_days'     => 'nullable|integer|min:0|max:365',

            'imei'              => 'nullable|string|max:20|unique:used_device_details,imei',
            'imei_verified'     => 'nullable|boolean',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'box_available'     => $this->boolean('box_available'),
            'cable_available'   => $this->boolean('cable_available'),
            'charger_available' => $this->boolean('charger_available'),
            'headphones_available' => $this->boolean('headphones_available'),
            'imei_verified'     => $this->boolean('imei_verified'),
        ]);
    }
}
