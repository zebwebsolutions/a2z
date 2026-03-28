<?php

namespace App\Services\Products;

use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductUnit;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ProductInventoryService
{
    public function normalizeUnits(?array $rows): array
    {
        return collect($rows ?? [])
            ->map(function ($row) {
                return [
                    'id' => isset($row['id']) ? (int) $row['id'] : null,
                    'imei_1' => $this->cleanValue($row['imei_1'] ?? null),
                    'imei_2' => $this->cleanValue($row['imei_2'] ?? null),
                    'serial_number' => $this->cleanValue($row['serial_number'] ?? null),
                    'barcode' => $this->cleanValue($row['barcode'] ?? null),
                ];
            })
            ->filter(function ($row) {
                return $row['id']
                    || $row['imei_1']
                    || $row['imei_2']
                    || $row['serial_number']
                    || $row['barcode'];
            })
            ->values()
            ->all();
    }

    public function validateUnits(array $rows, ?Product $product = null, bool $requiresImei = false): void
    {
        $errors = [];
        $existingIds = collect($rows)->pluck('id')->filter()->map(fn ($id) => (int) $id)->all();

        foreach (['imei_1', 'imei_2', 'serial_number', 'barcode'] as $field) {
            $values = collect($rows)
                ->pluck($field)
                ->filter()
                ->map(fn ($value) => strtolower($value))
                ->values();

            if ($values->count() !== $values->unique()->count()) {
                $errors["inventory_units.$field"] = ucfirst(str_replace('_', ' ', $field)) . ' values must be unique.';
            }
        }

        if ($requiresImei && empty($rows)) {
            $errors['inventory_units'] = 'Add at least one unit for phone products.';
        }

        foreach ($rows as $index => $row) {
            if ($requiresImei && empty($row['imei_1'])) {
                $errors["inventory_units.$index.imei_1"] = 'IMEI 1 is required for phone units.';
            }
        }

        foreach (['imei_1', 'imei_2', 'serial_number', 'barcode'] as $field) {
            $values = collect($rows)->pluck($field)->filter()->unique()->values();
            if ($values->isEmpty()) {
                continue;
            }

            $query = ProductUnit::query()->whereIn($field, $values->all());
            if (!empty($existingIds)) {
                $query->whereNotIn('id', $existingIds);
            }

            if ($query->exists()) {
                $errors["inventory_units.$field"] = ucfirst(str_replace('_', ' ', $field)) . ' must be unique.';
            }
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }

    public function syncUnits(Product $product, array $rows): void
    {
        $existingAvailable = $product->units()
            ->where('status', 'available')
            ->get()
            ->keyBy('id');

        $incomingIds = [];

        foreach ($rows as $row) {
            $payload = [
                'imei_1' => $row['imei_1'],
                'imei_2' => $row['imei_2'],
                'serial_number' => $row['serial_number'],
                'barcode' => $row['barcode'],
                'status' => 'available',
                'sold_at' => null,
            ];

            if (!empty($row['id']) && $existingAvailable->has($row['id'])) {
                $unit = $existingAvailable[$row['id']];
                $unit->update($payload);
                $incomingIds[] = $unit->id;
                continue;
            }

            $unit = $product->units()->create($payload);
            $incomingIds[] = $unit->id;
        }

        $existingAvailable
            ->keys()
            ->diff($incomingIds)
            ->each(function ($unitId) use ($existingAvailable) {
                $existingAvailable[$unitId]->delete();
            });

        $this->refreshProductStock($product);
    }

    public function reserveUnits(Product $product, int $quantity): Collection
    {
        $units = $product->units()
            ->where('status', 'available')
            ->orderBy('id')
            ->lockForUpdate()
            ->limit($quantity)
            ->get();

        if ($units->count() < $quantity) {
            abort(400, "Insufficient stock for {$product->name}");
        }

        ProductUnit::whereIn('id', $units->pluck('id'))
            ->update([
                'status' => 'sold',
                'sold_at' => Carbon::now(),
            ]);

        $product->decrement('stock', $units->count());

        return $units->fresh();
    }

    public function restoreUnits(OrderItem $orderItem): int
    {
        $units = $orderItem->productUnits()->get();
        if ($units->isEmpty()) {
            return 0;
        }

        ProductUnit::whereIn('id', $units->pluck('id'))
            ->update([
                'status' => 'available',
                'sold_at' => null,
            ]);

        $orderItem->product->increment('stock', $units->count());

        return $units->count();
    }

    public function refreshProductStock(Product $product): void
    {
        $availableUnitsCount = $product->units()->where('status', 'available')->count();
        $hasTrackedHistory = $product->units()->exists();

        $product->forceFill([
            'tracks_inventory_by_unit' => $hasTrackedHistory,
            'stock' => $hasTrackedHistory ? $availableUnitsCount : $product->stock,
        ])->saveQuietly();
    }

    private function cleanValue(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
