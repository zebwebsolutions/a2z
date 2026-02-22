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
        $templateName = (string) config('services.whatsapp.template_receipt', 'send_order_receipt');
        $templateLang = (string) config('services.whatsapp.template_lang', 'en');

        if ($token === '' || $phoneNumberId === '' || $templateName === '') {
            Log::warning('WhatsApp receipt skipped: missing token, phone number id, or template name.');
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

        $baseUrl = "https://graph.facebook.com/{$apiVersion}/{$phoneNumberId}";
        $pdfUrl = $this->publicReceiptUrl($pdfAbsolutePath);
        if (!$pdfUrl) {
            Log::warning('WhatsApp receipt skipped: receipt URL is not publicly accessible.', [
                'order_id' => $order->id,
                'path' => $pdfAbsolutePath,
            ]);
            return false;
        }

        try {
            $send = Http::withToken($token)
                ->timeout(30)
                ->post($baseUrl . '/messages', [
                    'messaging_product' => 'whatsapp',
                    'to' => $to,
                    'type' => 'template',
                    'template' => [
                        'name' => $templateName,
                        'language' => ['code' => $templateLang],
                        'components' => [
                            [
                                'type' => 'header',
                                'parameters' => [[
                                    'type' => 'document',
                                    'document' => [
                                        'link' => $pdfUrl,
                                        'filename' => 'receipt-order-' . $order->id . '.pdf',
                                    ],
                                ]],
                            ],
                            [
                                'type' => 'body',
                                'parameters' => [
                                    [
                                        'type' => 'text',
                                        'parameter_name' => 'customer_name',
                                        'text' => (string) ($order->customer_name ?: 'Customer'),
                                    ],
                                    [
                                        'type' => 'text',
                                        'parameter_name' => 'order_number',
                                        'text' => (string) $order->id,
                                    ],
                                ],
                            ],
                        ],
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

    private function publicReceiptUrl(string $absolutePath): ?string
    {
        $publicStorageRoot = storage_path('app/public') . DIRECTORY_SEPARATOR;
        if (!str_starts_with($absolutePath, $publicStorageRoot)) {
            return null;
        }

        $relative = substr($absolutePath, strlen($publicStorageRoot));
        $relative = str_replace(DIRECTORY_SEPARATOR, '/', $relative);
        $base = rtrim((string) config('app.url'), '/');

        if ($base === '') {
            return null;
        }

        return $base . '/storage/' . ltrim($relative, '/');
    }
}
