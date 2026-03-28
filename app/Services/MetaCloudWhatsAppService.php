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
        $token        = (string) config('services.whatsapp.token');
        $phoneNumberId = (string) config('services.whatsapp.phone_number_id');
        $apiVersion   = (string) config('services.whatsapp.api_version', 'v21.0');
        $receiptLanguage = $order->receipt_language === 'ar' ? 'ar' : 'en';
        $templateName = $receiptLanguage === 'ar'
            ? (string) config('services.whatsapp.template_receipt_ar', 'send_receipt_link_arabic')
            : (string) config('services.whatsapp.template_receipt', 'send_receipt_link');
        $templateLang = $receiptLanguage;
        $buttonIndex  = (int)    config('services.whatsapp.template_button_index', 0);

        if ($token === '' || $phoneNumberId === '' || $templateName === '') {
            Log::warning('WhatsApp receipt skipped: missing token, phone number id, or template name.');
            return false;
        }

        $to = $this->resolveRecipientNumber($order);

        if (!$to) {
            Log::warning('WhatsApp receipt skipped: invalid customer phone.', [
                'order_id'       => $order->id,
                'customer_phone' => $order->customer_phone,
            ]);
            return false;
        }

        $baseUrl  = "https://graph.facebook.com/{$apiVersion}/{$phoneNumberId}";
        $filename = basename($pdfAbsolutePath);

        try {
            $response = Http::withToken($token)
                ->timeout(30)
                ->post($baseUrl . '/messages', [
                    'messaging_product' => 'whatsapp',
                    'to'                => $to,
                    'type'              => 'template',
                    'template'          => [
                        'name'       => $templateName,
                        'language'   => ['code' => $templateLang],
                        'components' => [
                            [
                                'type'       => 'body',
                                'parameters' => [],
                            ],
                            [
                                'type'       => 'button',
                                'sub_type'   => 'url',
                                'index'      => (string) $buttonIndex,
                                'parameters' => [
                                    [
                                        'type' => 'text',
                                        'text' => $filename,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]);

            if (!$response->successful()) {
                Log::error('WhatsApp receipt message failed', [
                    'order_id' => $order->id,
                    'status'   => $response->status(),
                    'body'     => $response->body(),
                ]);
                return false;
            }

            Log::info('WhatsApp receipt sent successfully', [
                'order_id' => $order->id,
                'to'       => $to,
                'filename' => $filename,
            ]);

            return true;

        } catch (\Throwable $e) {
            Log::error('WhatsApp receipt send exception', [
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
            ]);
            return false;
        }
    }

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

        // Convert 00<country><number> to <country><number>
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        // WhatsApp API expects international digits only (no +), usually 10-15.
        if (strlen($digits) < 10 || strlen($digits) > 15) {
            return null;
        }

        return $digits;
    }
}
