<?php

namespace App\Services;

use App\Http\Helpers\PhoneNumber;
use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaCloudWhatsAppService
{
    public function sendReceipt(Order $order, string $pdfAbsolutePath): bool
    {
        $token = (string) config('services.whatsapp.token');
        $phoneNumberId = (string) config('services.whatsapp.phone_number_id');
        $apiVersion = (string) config('services.whatsapp.api_version', 'v21.0');

        if ($token === '' || $phoneNumberId === '') {
            Log::warning('WhatsApp receipt skipped: missing token or phone number id.');
            return false;
        }

        $to = $order->customer_phone_e164 ?: PhoneNumber::normalizeKuwait($order->customer_phone);
        if (!$to) {
            Log::warning('WhatsApp receipt skipped: invalid customer phone.', [
                'order_id' => $order->id,
                'customer_phone' => $order->customer_phone,
            ]);
            return false;
        }

        $to = ltrim($to, '+'); // Meta expects digits only.
        $baseUrl = "https://graph.facebook.com/{$apiVersion}/{$phoneNumberId}";

        try {
            $upload = Http::withToken($token)
                ->timeout(30)
                ->attach('file', fopen($pdfAbsolutePath, 'r'), basename($pdfAbsolutePath))
                ->post($baseUrl . '/media', [
                    'messaging_product' => 'whatsapp',
                    'type' => 'application/pdf',
                ]);

            if (!$upload->successful()) {
                Log::error('WhatsApp media upload failed', [
                    'order_id' => $order->id,
                    'status' => $upload->status(),
                    'body' => $upload->body(),
                ]);
                return false;
            }

            $mediaId = $upload->json('id');
            if (!$mediaId) {
                Log::error('WhatsApp media upload missing media id', [
                    'order_id' => $order->id,
                    'response' => $upload->json(),
                ]);
                return false;
            }

            $send = Http::withToken($token)
                ->timeout(30)
                ->post($baseUrl . '/messages', [
                    'messaging_product' => 'whatsapp',
                    'to' => $to,
                    'type' => 'document',
                    'document' => [
                        'id' => $mediaId,
                        'filename' => 'receipt-order-' . $order->id . '.pdf',
                        'caption' => 'Thank you. Your receipt for Order #' . $order->id,
                    ],
                ]);

            if (!$send->successful()) {
                Log::error('WhatsApp receipt message failed', [
                    'order_id' => $order->id,
                    'status' => $send->status(),
                    'body' => $send->body(),
                ]);
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('WhatsApp receipt send exception', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}

