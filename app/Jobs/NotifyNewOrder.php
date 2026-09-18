<?php

namespace App\Jobs;

use App\Models\MobilePushDevice;
use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class NotifyNewOrder implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $orderId) {}

    public function handle(): void
    {
        $order = Order::find($this->orderId);
        if (! $order || $order->order_source !== 'online') {
            return;
        }
        MobilePushDevice::whereHas('user', fn ($q) => $q->where('store_id', $order->store_id)
            ->where('is_active', true)->whereIn('role', ['admin', 'salesman']))
            ->eachById(fn ($device) => DeliverOrderPush::dispatch($order->id, $device->id)->onConnection('database'));
    }
}
