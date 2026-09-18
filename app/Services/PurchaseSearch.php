<?php

namespace App\Services;

use App\Models\Purchase;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class PurchaseSearch
{
    public function query(Request $request): Builder
    {
        $data = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'customer_purchase_id' => ['nullable', 'integer', 'min:1'],
        ]);
        $query = Purchase::query()
            ->when($request->user()->role !== 'admin', fn ($query) => $query->where('store_id', $request->user()->store_id));

        if (! empty($data['customer_purchase_id'])) {
            $customerPurchase = (clone $query)->findOrFail($data['customer_purchase_id']);
            $query->where('customer_phone_normalized', $customerPurchase->customer_phone_normalized);
        }

        $search = trim($data['search'] ?? '');
        if ($search !== '') {
            $like = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search).'%';
            $phone = preg_match('/^[+0-9()\s-]+$/', $search) ? preg_replace('/\D/', '', $search) : '';
            if (str_starts_with($phone, '00')) {
                $phone = substr($phone, 2);
            }
            $query->where(function ($query) use ($like, $phone, $search) {
                $query->whereRaw("LOWER(customer_name) LIKE LOWER(?) ESCAPE '!'", [$like])
                    ->orWhereRaw("LOWER(product_name) LIKE LOWER(?) ESCAPE '!'", [$like]);
                if ($phone !== '') {
                    $query->orWhere('customer_phone_normalized', 'like', '%'.$phone.'%');
                }
                if (ctype_digit($search)) {
                    $query->orWhere('id', $search);
                }
            });
        }

        return $query;
    }
}
