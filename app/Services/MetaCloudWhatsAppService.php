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
        $buttonIndex = (string) config('services.whatsapp.template_button_index', '0');

        if ($token === '' || $phoneNumberId === '' || $templateName === '') {
            Log::warning('WhatsApp receipt skipped: missing token, phone number id, or template name.');
            return false;
        }

        $to = $this->resolveRecipientNumber($order);
        if (!$to) {
            Log::warning('WhatsApp receipt skipped: invalid customer phone.', [
                'order_id' => $order->id,
                'customer_phone_e164' => $order->customer_phone_e164,
                'customer_phone' => $order->customer_phone,
            ]);
            return false;
        }

        $baseUrl = "https://graph.facebook.com/{$apiVersion}/{$phoneNumberId}";
        $buttonUrlParam = $this->receiptButtonParam($pdfAbsolutePath);
        if (!$buttonUrlParam) {
            Log::warning('WhatsApp receipt skipped: receipt filename could not be resolved.', [
                'order_id' => $order->id,
                'path' => $pdfAbsolutePath,
            ]);
            return false;
        }

        try {
            $templatePayload = [
                'name' => $templateName,
                'language' => ['code' => $templateLang],
                'components' => [
                    [
                        'type' => 'body',
                        'parameters' => [],
                    ],
                    [
                        'type' => 'button',
                        'sub_type' => 'url',
                        'index' => $buttonIndex,
                        'parameters' => [[
                            'type' => 'text',
                            'text' => $buttonUrlParam,
                        ]],
                    ],
                ],
            ];

            $send = Http::withToken($token)
                ->timeout(30)
                ->post($baseUrl . '/messages', [
                    'messaging_product' => 'whatsapp',
                    'to' => $to,
                    'type' => 'template',
                    'template' => $templatePayload,
                ]);

            if (!$send->successful()) {
                Log::error('WhatsApp receipt message failed', [
                    'order_id' => $order->id,
                    'to' => $to,
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

    private function receiptButtonParam(string $absolutePath): ?string
    {
        $file = basename($absolutePath);
        return $file !== '' ? $file : null;
    }

    /**
     * Resolve WhatsApp recipient to digits-only international format.
     */
    private function resolveRecipientNumber(Order $order): ?string
    {
        $candidate = $order->customer_phone_e164
            ?: PhoneNumber::normalizeKuwait($order->customer_phone)
            ?: $order->customer_phone;

        if (!$candidate) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', (string) $candidate) ?? '';
        if ($digits === '') {
            return null;
        }

        // Support numbers entered as 00<country><number>
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        // WhatsApp expects international recipient, usually 10-15 digits.
        if (strlen($digits) < 10 || strlen($digits) > 15) {
            return null;
        }

        return $digits;
    }
}
