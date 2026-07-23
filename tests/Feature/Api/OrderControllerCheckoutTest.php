<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\SparePart;
use App\Models\Store;
use App\Models\User;
use App\Services\MetaCloudWhatsAppService;
use App\Services\OrderReceiptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderControllerCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_can_checkout_a_unit_tracked_product(): void
    {
        [$user, $store, $category] = $this->createApiContext();

        $product = Product::create([
            'store_id' => $store->id,
            'category_id' => $category->id,
            'name' => 'Tracked Phone',
            'slug' => 'tracked-phone-' . Str::lower(Str::random(6)),
            'price' => 100,
            'stock' => 1,
            'tracks_inventory_by_unit' => true,
        ]);

        $unit = ProductUnit::create([
            'product_id' => $product->id,
            'imei_1' => '356789012345678',
            'status' => 'available',
        ]);

        $this->withoutReceiptDelivery();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/orders', [
            'payment_method' => 'cash',
            'total' => 100,
            'customer_name' => 'Mobile Customer',
            'customer_phone' => '55555555',
            'customer_address' => 'Kuwait City',
            'items' => [
                [
                    'type' => 'product',
                    'id' => $product->id,
                    'price' => 100,
                    'qty' => 1,
                ],
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('orders', [
            'id' => $response->json('order_id'),
            'user_id' => $user->id,
            'customer_address' => 'Kuwait City',
        ]);
        $this->assertDatabaseHas('product_units', [
            'id' => $unit->id,
            'status' => 'sold',
        ]);
        $this->assertDatabaseHas('order_item_product_unit', [
            'product_unit_id' => $unit->id,
        ]);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 0,
        ]);
    }

    public function test_mobile_can_checkout_a_mixed_product_and_spare_part_cart(): void
    {
        [$user, $store, $category] = $this->createApiContext();

        $product = Product::create([
            'store_id' => $store->id,
            'category_id' => $category->id,
            'name' => 'Charging Cable',
            'slug' => 'charging-cable-' . Str::lower(Str::random(6)),
            'price' => 10,
            'stock' => 2,
        ]);

        $sparePart = SparePart::create([
            'name' => 'Replacement Battery',
            'sku' => 'BAT-' . Str::upper(Str::random(6)),
            'barcode' => 'SP-' . Str::upper(Str::random(6)),
            'cost_price' => 5,
            'selling_price' => 15,
            'stock_quantity' => 3,
        ]);

        $this->withoutReceiptDelivery();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/orders', [
            'payment_method' => 'card',
            'total' => 25,
            'items' => [
                [
                    'type' => 'product',
                    'id' => $product->id,
                    'price' => 10,
                    'qty' => 1,
                ],
                [
                    'type' => 'spare_part',
                    'id' => $sparePart->id,
                    'price' => 15,
                    'qty' => 1,
                ],
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('order_spare_part_items', [
            'order_id' => $response->json('order_id'),
            'spare_part_id' => $sparePart->id,
            'quantity' => 1,
            'price' => 15,
        ]);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 1,
        ]);
        $this->assertDatabaseHas('spare_parts', [
            'id' => $sparePart->id,
            'stock_quantity' => 2,
        ]);
    }

    private function createApiContext(): array
    {
        $store = Store::create([
            'name' => 'Checkout Store',
            'address' => 'Test Address',
        ]);

        $category = Category::create([
            'name' => 'Accessories',
            'slug' => 'accessories-' . Str::lower(Str::random(6)),
        ]);

        $user = User::factory()->create([
            'role' => 'salesman',
            'is_active' => true,
            'store_id' => $store->id,
        ]);

        return [$user, $store, $category];
    }

    private function withoutReceiptDelivery(): void
    {
        $this->mock(OrderReceiptService::class)
            ->shouldReceive('generate')
            ->once()
            ->andReturn(null);

        $this->mock(MetaCloudWhatsAppService::class)
            ->shouldNotReceive('sendReceipt');
    }
}
