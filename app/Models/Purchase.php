<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Purchase extends Model
{
    protected $fillable = ['request_key', 'product_name', 'product_id', 'store_id', 'user_id', 'customer_name', 'customer_phone', 'customer_id_image', 'unit_cost', 'quantity'];

    protected $hidden = ['customer_id_image', 'request_key'];

    protected $casts = ['unit_cost' => 'decimal:3', 'quantity' => 'integer'];

    public static function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        return strlen($digits) === 8 ? '965'.$digits : $digits;
    }

    protected static function booted(): void
    {
        static::creating(function (Purchase $purchase) {
            if (blank($purchase->product_name) && $purchase->product_id) {
                $purchase->product_name = Product::whereKey($purchase->product_id)->value('name');
            }
        });

        static::saving(function (Purchase $purchase) {
            $purchase->customer_phone_normalized = self::normalizePhone($purchase->customer_phone);
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
