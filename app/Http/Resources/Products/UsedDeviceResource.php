<?php

namespace App\Http\Resources\Products;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UsedDeviceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'condition_grade' => $this->device_condition,
            'battery_health'  => $this->battery_health,

            'accessories' => [
                'box'        => $this->box_available,
                'cable'      => $this->cable_available,
                'charger'    => $this->charger_available,
                'headphones' => $this->headphones_available,
            ],

            'warranty_days' => $this->warranty_days,

            'imei' => [
                'number'   => $this->when($request->user()?->isAdmin(), $this->imei),
                'verified' => $this->imei_verified,
            ],

            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
