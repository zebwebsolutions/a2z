<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\SparePart;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class BarcodeScanControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_finds_a_product_unit_by_imei_for_mobile_scan_requests(): void
    {
        $product = $this->createProduct();

        $unit = ProductUnit::create([
            'product_id' => $product->id,
            'imei_1' => '356789012345678',
            'serial_number' => 'SN-001',
            'barcode' => 'UNIT-001',
            'status' => 'available',
        ]);

        $response = $this->postJson('/api/products/scan', [
            'identifier' => $unit->imei_1,
        ]);

        $response
            ->assertOk()
            ->assertJson([
                'type' => 'product',
                'id' => $product->id,
                'matched_unit_id' => $unit->id,
                'matched_by' => 'imei_1',
                'matched_value' => $unit->imei_1,
            ]);
    }

    public function test_it_finds_a_product_unit_by_serial_number(): void
    {
        $product = $this->createProduct();

        $unit = ProductUnit::create([
            'product_id' => $product->id,
            'imei_1' => '356789012345679',
            'serial_number' => 'SN-XYZ-123',
            'barcode' => 'UNIT-002',
            'status' => 'available',
        ]);

        $response = $this->getJson('/api/products/scan/' . $unit->serial_number);

        $response
            ->assertOk()
            ->assertJson([
                'type' => 'product',
                'id' => $product->id,
                'matched_unit_id' => $unit->id,
                'matched_by' => 'serial_number',
                'matched_value' => $unit->serial_number,
            ]);
    }

    public function test_legacy_barcode_route_still_works(): void
    {
        $product = $this->createProduct([
            'barcode' => 'PRD-LEGACY-001',
        ]);

        $response = $this->getJson('/api/products/barcode/' . $product->barcode);

        $response
            ->assertOk()
            ->assertJson([
                'type' => 'product',
                'id' => $product->id,
                'matched_by' => 'barcode',
                'matched_value' => $product->barcode,
            ]);
    }

    public function test_it_still_finds_spare_parts_by_barcode(): void
    {
        SparePart::create([
            'name' => 'Battery',
            'sku' => 'BAT-001',
            'barcode' => 'SP-001',
            'barcode_type' => 'code128',
            'cost_price' => 5,
            'selling_price' => 10,
            'stock_quantity' => 3,
        ]);

        $response = $this->postJson('/api/products/scan', [
            'barcode' => 'SP-001',
        ]);

        $response
            ->assertOk()
            ->assertJson([
                'type' => 'spare_part',
                'name' => 'Battery',
                'matched_by' => 'barcode',
                'matched_value' => 'SP-001',
            ]);
    }

    private function createProduct(array $overrides = []): Product
    {
        $store = Store::create([
            'name' => 'Main Store',
            'address' => 'Test Address',
        ]);

        $category = Category::create([
            'name' => 'Phones',
            'slug' => 'phones-' . Str::lower(Str::random(6)),
        ]);

        return Product::create(array_merge([
            'store_id' => $store->id,
            'category_id' => $category->id,
            'name' => 'iPhone Test',
            'slug' => 'iphone-test-' . Str::lower(Str::random(6)),
            'price' => 100,
            'stock' => 1,
            'is_active' => true,
        ], $overrides));
    }
}
