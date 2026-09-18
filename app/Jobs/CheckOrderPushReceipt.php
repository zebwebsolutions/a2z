<?php

namespace App\Jobs;

use App\Models\MobilePushDevice;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

class CheckOrderPushReceipt implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(public int $deviceId, public string $receiptId) {}

    public function backoff(): array
    {
        return [300, 900];
    }

    public function handle(): void
    {
        $request = Http::acceptJson()->timeout(20);
        if ($accessToken = config('services.expo.access_token')) {
            $request = $request->withToken($accessToken);
        }
        $response = $request->post('https://exp.host/--/api/v2/push/getReceipts', ['ids' => [$this->receiptId]])->throw();
        $receipt = $response->json('data')[$this->receiptId] ?? null;
        if (! $receipt) {
            throw new \RuntimeException('Push delivery receipt is not yet available.');
        }
        if (($receipt['details']['error'] ?? null) === 'DeviceNotRegistered') {
            MobilePushDevice::whereKey($this->deviceId)->delete();
        } elseif (($receipt['status'] ?? null) !== 'ok') {
            throw new \RuntimeException('Order push delivery failed: '.($receipt['details']['error'] ?? 'UnknownError'));
        }
    }
}
