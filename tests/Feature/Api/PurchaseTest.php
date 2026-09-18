<?php

namespace Tests\Feature\Api;

use App\Models\Purchase;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PurchaseTest extends TestCase
{
    use RefreshDatabase;

    private function context(): Store
    {
        Storage::fake('local');
        Storage::fake('public');
        $store = Store::create(['name' => 'Main', 'address' => 'Kuwait']);
        Sanctum::actingAs(User::factory()->create(['role' => 'salesman', 'is_active' => true, 'store_id' => $store->id]));

        return $store;
    }

    private function payload(Store $store): array
    {
        return [
            'store_id' => $store->id, 'name' => 'Customer device', 'price' => 100,
            'cost_price' => 75.125, 'stock' => 1,
            'customer_name' => 'Test Customer', 'customer_phone' => '+965 5555 1234',
            'customer_id_image' => UploadedFile::fake()->image('id.jpg'),
            'inventory_units' => [['serial_number' => 'CUSTOMER-001']],
        ];
    }

    public function test_purchase_saves_inventory_customer_and_server_timestamp_with_private_id(): void
    {
        $store = $this->context();
        $this->travelTo(now()->startOfSecond());
        $response = $this->postJson('/api/purchases', $this->payload($store));
        $response->assertCreated()->assertJsonPath('purchase.quantity', 1)->assertJsonPath('purchase.unit_cost', '75.125');
        $purchase = Purchase::firstOrFail();
        $this->assertTrue($purchase->created_at->equalTo(now()));
        $this->assertDatabaseHas('products', ['id' => $purchase->product_id, 'stock' => 1]);
        $this->assertDatabaseHas('product_units', ['product_id' => $purchase->product_id, 'serial_number' => 'CUSTOMER-001']);
        Storage::disk('local')->assertExists($purchase->customer_id_image);
        Storage::disk('public')->assertMissing($purchase->customer_id_image);
        $response->assertJsonMissingPath('purchase.customer_id_image');
        $this->getJson('/api/purchases')->assertOk()->assertJsonPath('data.0.customer_name', 'Test Customer')->assertJsonMissingPath('data.0.customer_id_image');
        $this->get('/api/purchases/'.$purchase->id.'/id-image')->assertOk();
    }

    public function test_customer_details_and_id_are_required_before_creating_inventory(): void
    {
        $store = $this->context();
        $payload = $this->payload($store);
        unset($payload['customer_name'], $payload['customer_phone'], $payload['customer_id_image'], $payload['cost_price']);
        $this->postJson('/api/purchases', $payload)->assertUnprocessable()->assertJsonValidationErrors(['customer_name', 'customer_phone', 'customer_id_image', 'cost_price']);
        $this->assertDatabaseCount('products', 0);
        $this->assertDatabaseCount('purchases', 0);
    }

    public function test_staff_cannot_buy_for_another_store_or_access_its_records(): void
    {
        $store = $this->context();
        $this->postJson('/api/purchases', $this->payload($store))->assertCreated();
        $purchase = Purchase::firstOrFail();
        $other = Store::create(['name' => 'Other', 'address' => 'Other']);
        Sanctum::actingAs(User::factory()->create(['role' => 'salesman', 'is_active' => true, 'store_id' => $other->id]));
        $this->postJson('/api/purchases', $this->payload($store))->assertForbidden();
        $this->getJson('/api/purchases')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/purchases/'.$purchase->id.'/id-image')->assertForbidden();
    }

    public function test_failed_purchase_rolls_back_inventory_and_removes_id_photo(): void
    {
        $store = $this->context();
        Purchase::creating(function () {
            throw new \RuntimeException('Simulated database failure');
        });
        try {
            $this->postJson('/api/purchases', $this->payload($store))->assertStatus(500);
            $this->assertDatabaseCount('products', 0);
            $this->assertDatabaseCount('product_units', 0);
            $this->assertDatabaseCount('purchases', 0);
            $this->assertSame([], Storage::disk('local')->allFiles('purchase-ids'));
        } finally {
            Purchase::flushEventListeners();
        }
    }

    public function test_invalid_id_file_is_rejected(): void
    {
        $store = $this->context();
        $payload = $this->payload($store);
        $payload['customer_id_image'] = UploadedFile::fake()->create('id.pdf', 50, 'application/pdf');
        $this->postJson('/api/purchases', $payload)->assertUnprocessable()->assertJsonValidationErrors('customer_id_image');
        $this->assertDatabaseCount('products', 0);
    }
}
