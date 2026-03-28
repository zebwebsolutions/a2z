<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SparePart extends Model
{
    protected $fillable = [
        'name',
        'sku',
        'barcode',
        'barcode_type',
        'cost_price',
        'selling_price',
        'stock_quantity'
    ];

    public function sales()
    {
        return $this->hasMany(SparePartSale::class);
    }
}
