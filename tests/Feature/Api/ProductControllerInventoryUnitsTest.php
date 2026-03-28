<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductControllerInventoryUnitsTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_can_create_a_product_with_imei_and_serial_units(): void
    {
        [$user, $store, $parentCategory, $category] = $this->createApiContext();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/products', [
            'store_id' => $store->id,
            'parent_category_id' => $parentCategory->id,
            'category_id' => $category->id,
            'name' => 'iPhone 15',
            'price' => 500,
            'inventory_units' => [
                [
                    'imei_1' => '356789012345678',
                    'imei_2' => '356789012345679',
                    'serial_number' => 'SN-IPH-001',
                    'barcode' => 'UNIT-IPH-001',
                ],
            ],
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('product.stock', 1)
            ->assertJsonPath('product.tracks_inventory_by_unit', true)
            ->assertJsonPath('product.units.0.imei_1', '356789012345678')
            ->assertJsonPath('product.units.0.serial_number', 'SN-IPH-001');

        $this->assertDatabaseHas('product_units', [
            'imei_1' => '356789012345678',
            'serial_number' => 'SN-IPH-001',
        ]);
    }

    public function test_mobile_can_edit_inventory_units_on_a_product(): void
    {
        [$user, $store, $parentCategory, $category] = $this->createApiContext();

        Sanctum::actingAs($user);

        $product = Product::create([
            'store_id' => $store->id,
            'parent_category_id' => $parentCategory->id,
            'category_id' => $category->id,
            'name' => 'iPhone 14',
            'slug' => 'iphone-14-' . Str::lower(Str::random(6)),
            'price' => 450,
            'stock' => 1,
            'tracks_inventory_by_unit' => true,
        ]);

        $unit = ProductUnit::create([
            'product_id' => $product->id,
            'imei_1' => '356789012345680',
            'serial_number' => 'SN-OLD-001',
            'barcode' => 'UNIT-OLD-001',
            'status' => 'available',
        ]);

        $response = $this->patchJson('/api/products/' . $product->id, [
            'name' => 'iPhone 14 Updated',
            'inventory_units' => [
                [
                    'id' => $unit->id,
                    'imei_1' => '356789012345681',
                    'imei_2' => '356789012345682',
                    'serial_number' => 'SN-NEW-001',
                    'barcode' => 'UNIT-NEW-001',
                ],
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('product.name', 'iPhone 14 Updated')
            ->assertJsonPath('product.units.0.imei_1', '356789012345681')
            ->assertJsonPath('product.units.0.serial_number', 'SN-NEW-001');

        $this->assertDatabaseHas('product_units', [
            'id' => $unit->id,
            'imei_1' => '356789012345681',
            'imei_2' => '356789012345682',
            'serial_number' => 'SN-NEW-001',
            'barcode' => 'UNIT-NEW-001',
        ]);
    }

    public function test_mobile_product_details_include_inventory_units_for_edit_screen(): void
    {
        [$user, $store, $parentCategory, $category] = $this->createApiContext();

        Sanctum::actingAs($user);

        $product = Product::create([
            'store_id' => $store->id,
            'parent_category_id' => $parentCategory->id,
            'category_id' => $category->id,
            'name' => 'Galaxy S24',
            'slug' => 'galaxy-s24-' . Str::lower(Str::random(6)),
            'price' => 550,
            'stock' => 1,
            'tracks_inventory_by_unit' => true,
        ]);

        ProductUnit::create([
            'product_id' => $product->id,
            'imei_1' => '356789012345683',
            'serial_number' => 'SN-GAL-001',
            'barcode' => 'UNIT-GAL-001',
            'status' => 'available',
        ]);

        $response = $this->getJson('/api/products/' . $product->id);

        $response
            ->assertOk()
            ->assertJsonPath('units.0.imei_1', '356789012345683')
            ->assertJsonPath('units.0.serial_number', 'SN-GAL-001');
    }

    private function createApiContext(): array
    {
        $store = Store::create([
            'name' => 'Main Store',
            'address' => 'Test Address',
        ]);

        $parentCategory = Category::create([
            'name' => 'Mobile Phones',
            'slug' => 'mobile-phones-' . Str::lower(Str::random(6)),
        ]);

        $category = Category::create([
            'name' => 'Smartphones',
            'slug' => 'smartphones-' . Str::lower(Str::random(6)),
            'parent_id' => $parentCategory->id,
        ]);

        $user = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'store_id' => $store->id,
        ]);

        return [$user, $store, $parentCategory, $category];
    }
}
