<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsedDeviceDetail extends Model
{
    protected $fillable = [
        'product_id',
        'device_condition',
        'battery_health',
        'box_available',
        'cable_available',
        'charger_available',
        'headphones_available',
        'warranty_days',
        'imei',
        'imei_verified',
    ];

    protected $casts = [
        'box_available' => 'boolean',
        'cable_available' => 'boolean',
        'charger_available' => 'boolean',
        'headphones_available' => 'boolean',
        'imei_verified' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
