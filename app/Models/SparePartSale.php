<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SparePartSale extends Model
{
    protected $fillable = [
        'spare_part_id',
        'salesman_id',
        'quantity',
        'unit_price',
        'total_price',
        'sold_at'
    ];

    public function sparePart()
    {
        return $this->belongsTo(SparePart::class);
    }

    public function user() {
        return $this->belongsTo(User::class, 'salesman_id');
    }
}
