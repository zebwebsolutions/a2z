<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'store_id',
        'user_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'customer_phone_e164',
        'customer_address',
        'receipt_language',
        'customer_type',
        'discount',
        'total',
        'payment_method',
        'order_source',
        'status',
    ];

    protected static function booted(): void
    {
        static::created(function (Order $order) {
            if ($order->order_source !== 'online') return;
            \Illuminate\Support\Facades\DB::afterCommit(function () use ($order) {
                try {
                    \App\Jobs\NotifyNewOrder::dispatch($order->id)->onConnection('database');
                } catch (\Throwable $exception) {
                    report($exception);
                }
            });
        });
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_id', 'id');
    }

    public function sparePartItems()
    {
        return $this->hasMany(OrderSparePartItem::class, 'order_id', 'id');
    }

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
