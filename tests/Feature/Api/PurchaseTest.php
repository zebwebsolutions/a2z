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
        $image = $this->get('/api/purchases/'.$purchase->id.'/id-image');
        $image->assertOk()->assertHeader('Content-Type', 'image/jpeg');
        $this->assertSame(Storage::disk('local')->get($purchase->customer_id_image), $image->streamedContent());
    }

    public function test_purchase_details_include_snapshot_and_keep_id_photo_private(): void
    {
        $store = $this->context();
        $this->postJson('/api/purchases', $this->payload($store))->assertCreated();
        $purchase = Purchase::firstOrFail();
        $this->getJson('/api/purchases/'.$purchase->id)->assertOk()
            ->assertJsonPath('id', $purchase->id)
            ->assertJsonPath('customer_name', 'Test Customer')
            ->assertJsonPath('unit_cost', '75.125')
            ->assertJsonPath('quantity', 1)
            ->assertJsonPath('product.name', 'Customer device')
            ->assertJsonMissingPath('customer_id_image');
        $purchase->product->delete();
        $this->getJson('/api/purchases/'.$purchase->id)->assertOk()
            ->assertJsonPath('product_name', 'Customer device')->assertJsonPath('product', null);
    }

    public function test_phone_suggestions_group_customers_and_are_scoped_to_store(): void
    {
        $store = $this->context();
        $this->postJson('/api/purchases', $this->payload($store))->assertCreated();
        $purchase = Purchase::firstOrFail();
        $copy = $purchase->replicate();
        $copy->customer_phone = '55551234';
        $copy->save();
        $this->getJson('/api/purchases/customers?phone=5555')->assertOk()
            ->assertJsonCount(1)->assertJsonPath('0.id', $copy->id)
            ->assertJsonPath('0.customer_name', 'Test Customer')->assertJsonMissingPath('0.customer_id_image');
        $this->getJson('/api/purchases/customers?phone=00965%205555')->assertOk()->assertJsonCount(1);
        $this->getJson('/api/purchases/customers?phone=55')->assertOk()->assertExactJson([]);
        $other = Store::create(['name' => 'Other', 'address' => 'Other']);
        Sanctum::actingAs(User::factory()->create(['role' => 'salesman', 'is_active' => true, 'store_id' => $other->id]));
        $this->getJson('/api/purchases/customers?phone=5555')->assertOk()->assertExactJson([]);
    }

    public function test_saved_customer_can_be_reused_without_reentering_details_or_id(): void
    {
        $store = $this->context();
        $this->postJson('/api/purchases', $this->payload($store))->assertCreated();
        $original = Purchase::firstOrFail();
        $payload = $this->payload($store);
        unset($payload['customer_name'], $payload['customer_phone'], $payload['customer_id_image']);
        $payload['customer_purchase_id'] = $original->id;
        $payload['inventory_units'][0]['serial_number'] = 'REPEAT-001';
        $this->postJson('/api/purchases', $payload)->assertCreated()
            ->assertJsonPath('purchase.customer_name', $original->customer_name)
            ->assertJsonPath('purchase.customer_phone', $original->customer_phone);
        $repeat = Purchase::latest('id')->firstOrFail();
        $this->assertNotSame($original->customer_id_image, $repeat->customer_id_image);
        $this->assertSame(Storage::disk('local')->get($original->customer_id_image), Storage::disk('local')->get($repeat->customer_id_image));
        $this->getJson('/api/purchases?customer_purchase_id='.$original->id)->assertJsonPath('total', 2);
    }

    public function test_reusing_customer_rejects_other_stores_and_missing_id_photos(): void
    {
        $store = $this->context();
        $this->postJson('/api/purchases', $this->payload($store))->assertCreated();
        $original = Purchase::firstOrFail();
        $payload = ['customer_purchase_id' => $original->id];
        Storage::disk('local')->delete($original->customer_id_image);
        $this->postJson('/api/purchases', $payload)->assertUnprocessable();
        $other = Store::create(['name' => 'Other', 'address' => 'Other']);
        Sanctum::actingAs(User::factory()->create(['role' => 'salesman', 'is_active' => true, 'store_id' => $other->id]));
        $this->postJson('/api/purchases', $payload)->assertForbidden();
        $this->assertDatabaseCount('purchases', 1);
    }

    public function test_failed_repeat_purchase_does_not_remove_original_id_photo(): void
    {
        $store = $this->context();
        $this->postJson('/api/purchases', $this->payload($store))->assertCreated();
        $original = Purchase::firstOrFail();
        $payload = $this->payload($store);
        unset($payload['customer_name'], $payload['customer_phone'], $payload['customer_id_image']);
        $payload['customer_purchase_id'] = $original->id;
        $payload['inventory_units'][0]['serial_number'] = 'REPEAT-FAIL';
        Purchase::creating(fn () => throw new \RuntimeException('Simulated failure'));
        try {
            $this->postJson('/api/purchases', $payload)->assertStatus(500);
            Storage::disk('local')->assertExists($original->customer_id_image);
            $this->assertCount(1, Storage::disk('local')->allFiles('purchase-ids'));
            $this->assertDatabaseCount('purchases', 1);
        } finally {
            Purchase::flushEventListeners();
        }
    }

    public function test_product_details_can_be_reused_with_fresh_units_and_independent_photos(): void
    {
        $store = $this->context();
        $first = $this->payload($store);
        $first['image'] = UploadedFile::fake()->image('product.jpg');
        $first['gallery'] = [UploadedFile::fake()->image('gallery.jpg')];
        $this->postJson('/api/purchases', $first)->assertCreated();
        $original = Purchase::firstOrFail();
        $product = $original->product;
        $product->update(['description' => 'Original description', 'specs' => ['STORAGE' => '128 GB']]);
        $this->getJson('/api/products?search=Customer')->assertOk()->assertJsonPath('data.0.id', $product->id);
        $this->getJson('/api/products/'.$product->id)->assertOk()
            ->assertJsonPath('description', 'Original description')->assertJsonPath('specs.STORAGE', '128 GB');
        $payload = $this->payload($store);
        $payload['source_product_id'] = $product->id;
        $payload['reuse_product_image'] = true;
        $payload['source_gallery_indices'] = [0];
        $payload['inventory_units'][0]['serial_number'] = 'FRESH-UNIT';
        $payload['cost_price'] = 65;
        $this->postJson('/api/purchases', $payload)->assertCreated()->assertJsonPath('purchase.unit_cost', '65.000');
        $newProduct = Purchase::latest('id')->firstOrFail()->product;
        $this->assertNotEquals($product->id, $newProduct->id);
        $this->assertNotSame($product->barcode, $newProduct->barcode);
        $this->assertNotSame($product->image, $newProduct->image);
        $this->assertNotSame($product->gallery[0], $newProduct->gallery[0]);
        $this->assertSame(Storage::disk('public')->get($product->image), Storage::disk('public')->get($newProduct->image));
        $this->assertSame(Storage::disk('public')->get($product->gallery[0]), Storage::disk('public')->get($newProduct->gallery[0]));
        $this->assertSame(['FRESH-UNIT'], $newProduct->units()->pluck('serial_number')->all());
        $this->assertSame(['CUSTOMER-001'], $product->units()->pluck('serial_number')->all());
        $this->assertSame(1, $product->fresh()->stock);
    }

    public function test_staff_cannot_suggest_read_or_copy_another_stores_product(): void
    {
        $store = $this->context();
        $this->postJson('/api/purchases', $this->payload($store))->assertCreated();
        $product = Purchase::firstOrFail()->product;
        $other = Store::create(['name' => 'Other', 'address' => 'Other']);
        Sanctum::actingAs(User::factory()->create(['role' => 'salesman', 'is_active' => true, 'store_id' => $other->id]));
        $this->getJson('/api/products?search=Customer')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/products/'.$product->id)->assertForbidden();
        $payload = $this->payload($other);
        $payload['source_product_id'] = $product->id;
        $this->postJson('/api/purchases', $payload)->assertForbidden();
    }

    public function test_failed_product_copy_keeps_original_photos_and_removes_copies(): void
    {
        $store = $this->context();
        $payload = $this->payload($store);
        $payload['image'] = UploadedFile::fake()->image('product.jpg');
        $this->postJson('/api/purchases', $payload)->assertCreated();
        $product = Purchase::firstOrFail()->product;
        $payload = $this->payload($store);
        $payload['source_product_id'] = $product->id;
        $payload['reuse_product_image'] = true;
        $payload['inventory_units'][0]['serial_number'] = 'COPY-FAIL';
        Purchase::creating(fn () => throw new \RuntimeException('Simulated failure'));
        try {
            $this->postJson('/api/purchases', $payload)->assertStatus(500);
            Storage::disk('public')->assertExists($product->image);
            $this->assertCount(1, Storage::disk('public')->allFiles());
            $this->assertDatabaseCount('products', 1);
        } finally {
            Purchase::flushEventListeners();
        }
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
        $this->getJson('/api/purchases/'.$purchase->id)->assertForbidden();
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

    public function test_retry_returns_saved_purchase_without_duplicate_inventory_or_photos(): void
    {
        $store = $this->context();
        $payload = $this->payload($store);
        $payload['request_key'] = 'same-mobile-purchase';
        $first = $this->postJson('/api/purchases', $payload)->assertCreated();
        $this->postJson('/api/purchases', $payload)->assertOk()
            ->assertJsonPath('purchase.id', $first->json('purchase.id'));
        $this->assertDatabaseCount('purchases', 1);
        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseCount('product_units', 1);
        $this->assertCount(1, Storage::disk('local')->allFiles('purchase-ids'));
    }

    public function test_purchase_keeps_original_name_after_product_changes(): void
    {
        $store = $this->context();
        $this->postJson('/api/purchases', $this->payload($store))->assertCreated();
        $purchase = Purchase::firstOrFail();
        $purchase->product->update(['name' => 'Renamed device']);
        $this->getJson('/api/purchases')->assertOk()->assertJsonPath('data.0.product_name', 'Customer device');
        $purchase->product->delete();
        $this->getJson('/api/purchases')->assertOk()->assertJsonPath('data.0.product_name', 'Customer device')->assertJsonPath('data.0.product', null);
    }

    public function test_deleting_product_preserves_missing_legacy_purchase_names(): void
    {
        $store = $this->context();
        $this->postJson('/api/purchases', $this->payload($store))->assertCreated();
        $purchase = Purchase::firstOrFail();
        foreach ([null, '', 'Deleted product'] as $name) {
            $copy = $purchase->replicate();
            $copy->save();
            // Simulate records created before product names were saved.
            Purchase::whereKey($copy->id)->update(['product_name' => $name]);
        }
        $purchase->product->delete();
        $this->assertSame(4, Purchase::where('product_name', 'Customer device')->whereNull('product_id')->count());
        $this->getJson('/api/purchases?search=Customer%20device')->assertOk()->assertJsonPath('total', 4);
    }

    public function test_phone_requires_digits_and_money_cannot_be_silently_rounded(): void
    {
        $store = $this->context();
        foreach (['---', '123', '+1234567890123456'] as $phone) {
            $payload = $this->payload($store);
            $payload['customer_phone'] = $phone;
            $this->postJson('/api/purchases', $payload)->assertUnprocessable()->assertJsonValidationErrors('customer_phone');
        }
        $payload = $this->payload($store);
        $payload['cost_price'] = '1.2345';
        $this->postJson('/api/purchases', $payload)->assertUnprocessable()->assertJsonValidationErrors('cost_price');
        $this->assertDatabaseCount('products', 0);
    }

    public function test_failed_purchase_cleans_up_product_images_too(): void
    {
        $store = $this->context();
        $payload = $this->payload($store);
        $payload['image'] = UploadedFile::fake()->image('product.jpg');
        $payload['gallery'] = [UploadedFile::fake()->image('gallery.jpg')];
        Purchase::creating(function () {
            throw new \RuntimeException('Simulated failure');
        });
        try {
            $this->postJson('/api/purchases', $payload)->assertStatus(500);
            $this->assertSame([], Storage::disk('public')->allFiles());
            $this->assertSame([], Storage::disk('local')->allFiles());
            $this->assertDatabaseCount('products', 0);
        } finally {
            Purchase::flushEventListeners();
        }
    }

    public function test_invalid_condition_does_not_store_product_photos(): void
    {
        $store = $this->context();
        $payload = $this->payload($store);
        $payload['is_used'] = true;
        $payload['image'] = UploadedFile::fake()->image('product.jpg');
        $this->postJson('/api/purchases', $payload)->assertUnprocessable();
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_purchase_cannot_use_existing_inventory_ids_to_bypass_uniqueness(): void
    {
        $store = $this->context();
        $this->postJson('/api/purchases', $this->payload($store))->assertCreated();
        $payload = $this->payload($store);
        $payload['inventory_units'][0]['id'] = \App\Models\ProductUnit::firstOrFail()->id;
        $this->postJson('/api/purchases', $payload)->assertUnprocessable()->assertJsonValidationErrors('inventory_units.0.id');
        $this->assertDatabaseCount('products', 1);
    }

    public function test_unauthenticated_requests_are_rejected(): void
    {
        $this->getJson('/api/purchases')->assertUnauthorized();
        $this->postJson('/api/purchases', [])->assertUnauthorized();
        $this->getJson('/api/purchases/1/id-image')->assertUnauthorized();
    }

    public function test_search_finds_customers_by_name_formatted_phone_and_product(): void
    {
        $store = $this->context();
        $this->postJson('/api/purchases', $this->payload($store))->assertCreated();
        foreach (['test customer', '55551234', '+965 5555', 'Customer device'] as $search) {
            $this->getJson('/api/purchases?'.http_build_query(['search' => $search]))
                ->assertOk()->assertJsonPath('total', 1);
        }
        $this->getJson('/api/purchases?search=unknown')->assertOk()->assertJsonPath('total', 0);
        $this->getJson('/api/purchases?search=%25')->assertOk()->assertJsonPath('total', 0);
    }

    public function test_customer_history_groups_phone_formats_without_grouping_namesakes(): void
    {
        $store = $this->context();
        $this->postJson('/api/purchases', $this->payload($store))->assertCreated();
        $first = Purchase::firstOrFail();
        foreach (['55551234', '00965 5555 1234', '+965 9999 1234'] as $phone) {
            $copy = $first->replicate();
            $copy->customer_phone = $phone;
            $copy->save();
        }
        $this->getJson('/api/purchases?customer_purchase_id='.$first->id)
            ->assertOk()->assertJsonPath('total', 3);
        $other = Store::create(['name' => 'Other', 'address' => 'Other']);
        Sanctum::actingAs(User::factory()->create(['role' => 'salesman', 'is_active' => true, 'store_id' => $other->id]));
        $this->getJson('/api/purchases?customer_purchase_id='.$first->id)->assertNotFound();
        $this->getJson('/api/purchases?search=55551234')->assertOk()->assertJsonPath('total', 0);
    }

    public function test_customer_history_is_paginated_and_searches_beyond_first_page(): void
    {
        $store = $this->context();
        $this->postJson('/api/purchases', $this->payload($store))->assertCreated();
        $first = Purchase::firstOrFail();
        for ($i = 0; $i < 21; $i++) {
            $copy = $first->replicate();
            $copy->customer_name = 'Repeat Buyer';
            $copy->save();
        }
        $this->getJson('/api/purchases?customer_purchase_id='.$first->id)
            ->assertOk()->assertJsonPath('total', 22)->assertJsonCount(20, 'data');
        $this->getJson('/api/purchases?customer_purchase_id='.$first->id.'&page=2')
            ->assertOk()->assertJsonCount(2, 'data');
        $this->getJson('/api/purchases?search=Test%20Customer')->assertOk()->assertJsonPath('total', 1);
    }
}
