<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderSparePartItem extends Model
{
    protected $fillable = [
        'order_id',
        'spare_part_id',
        'quantity',
        'price',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function sparePart()
    {
        return $this->belongsTo(SparePart::class);
    }
}
