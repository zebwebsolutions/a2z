<?php

namespace App\Jobs;

use App\Models\MobilePushDevice;
use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

class DeliverOrderPush implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public function backoff(): array
    {
        return [30, 120, 300];
    }

    public function __construct(public int $orderId, public int $deviceId) {}

    public function handle(): void
    {
        $order = Order::find($this->orderId);
        $device = MobilePushDevice::with(['user', 'accessToken'])->find($this->deviceId);
        if (! $order || ! $device || ! $device->user || ! $device->accessToken) {
            return;
        }
        $user = $device->user;
        $token = $device->accessToken;
        if (! $user->is_active || ! in_array($user->role, ['admin', 'salesman'])
            || (int) $user->store_id !== (int) $order->store_id
            || ($token->expires_at && $token->expires_at->isPast())
            || (config('sanctum.expiration') && $token->created_at->lte(now()->subMinutes(config('sanctum.expiration'))))) {
            return;
        }

        $request = Http::acceptJson()->timeout(20);
        if ($accessToken = config('services.expo.access_token')) {
            $request = $request->withToken($accessToken);
        }
        $response = $request->post('https://exp.host/--/api/v2/push/send', [
            'to' => $device->expo_token,
            'title' => 'New order received',
            'body' => 'Order #'.$order->id.' is ready to review.',
            'sound' => 'default', 'channelId' => 'orders', 'priority' => 'high',
            'data' => ['type' => 'new_order', 'order_id' => $order->id, 'store_id' => $order->store_id],
        ])->throw();
        $ticket = $response->json('data');
        if (($ticket['details']['error'] ?? null) === 'DeviceNotRegistered') {
            $device->delete();

            return;
        }
        if (($ticket['status'] ?? null) === 'ok' && isset($ticket['id'])) {
            CheckOrderPushReceipt::dispatch($device->id, $ticket['id'])->onConnection('database')->delay(now()->addMinutes(15));
        }
        if (($ticket['status'] ?? null) !== 'ok') {
            throw new \RuntimeException('Push provider rejected the order notification.');
        }
    }
}
