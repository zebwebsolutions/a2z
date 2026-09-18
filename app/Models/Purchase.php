<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Purchase extends Model
{
    protected $fillable = ['product_id', 'store_id', 'user_id', 'customer_name', 'customer_phone', 'customer_id_image', 'unit_cost', 'quantity'];

    protected $hidden = ['customer_id_image'];

    protected $casts = ['unit_cost' => 'decimal:3', 'quantity' => 'integer'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
