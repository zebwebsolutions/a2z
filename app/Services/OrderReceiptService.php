<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class OrderReceiptService
{
    /**
     * Generate and store an order receipt PDF.
     * Returns absolute file path on success, otherwise null.
     */
    public function generate(Order $order): ?string
    {
        try {
            $order->loadMissing('items.product', 'sparePartItems.sparePart', 'store', 'user');

            if (!app()->bound('dompdf.wrapper')) {
                Log::warning('Receipt PDF skipped: dompdf.wrapper is not available.');
                return null;
            }

            $pdf = app('dompdf.wrapper');
            $pdf->loadView('front.cart.receipt-pdf', ['order' => $order]);

            $relativePath = 'receipts/order-' . $order->id . '-' . now()->format('YmdHis') . '.pdf';
            Storage::disk('public')->put($relativePath, $pdf->output());

            return storage_path('app/public/' . $relativePath);
        } catch (\Throwable $e) {
            Log::error('Receipt PDF generation failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
