<?php

namespace Tests\Feature\Api;

use App\Models\{Order, Product, ProductUnit, SparePart, Store, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderCancellationTest extends TestCase
{
    use RefreshDatabase;

    private function context(): array
    {
        $store = Store::create(['name' => 'Main', 'address' => 'Kuwait']);
        $user = User::factory()->create(['role' => 'salesman', 'is_active' => true, 'store_id' => $store->id]);
        Sanctum::actingAs($user);
        $order = Order::create(['user_id' => $user->id, 'store_id' => $store->id, 'status' => 'pending', 'payment_method' => 'cash', 'order_source' => 'online', 'total' => 30]);
        return [$order, $store];
    }

    public function test_cancellation_restores_all_inventory_once_and_removes_order_from_inbox(): void
    {
        [$order, $store] = $this->context();
        $product = Product::create(['name' => 'Cable', 'slug' => 'cable', 'store_id' => $store->id, 'price' => 5, 'stock' => 3]);
        $order->items()->create(['product_id' => $product->id, 'quantity' => 2, 'price' => 5]);
        $tracked = Product::create(['name' => 'Phone', 'slug' => 'phone', 'store_id' => $store->id, 'price' => 10, 'stock' => 0, 'tracks_inventory_by_unit' => true]);
        $unit = ProductUnit::create(['product_id' => $tracked->id, 'serial_number' => 'CANCEL-1', 'status' => 'sold', 'sold_at' => now()]);
        $item = $order->items()->create(['product_id' => $tracked->id, 'quantity' => 1, 'price' => 10]);
        $item->productUnits()->attach($unit->id);
        $part = SparePart::create(['name' => 'Screen', 'sku' => 'SCREEN', 'barcode' => 'CANCEL-SCREEN', 'cost_price' => 5, 'selling_price' => 10, 'stock_quantity' => 2]);
        $order->sparePartItems()->create(['spare_part_id' => $part->id, 'quantity' => 1, 'price' => 10]);

        $this->getJson('/api/order-inbox')->assertJsonPath('unread_count', 1);
        for ($i = 0; $i < 2; $i++) {
            $this->postJson('/api/orders/'.$order->id.'/cancel')->assertOk()->assertJsonPath('success', true);
            $this->assertSame('cancelled', $order->fresh()->status);
            $this->assertEquals(5, $product->fresh()->stock);
            $this->assertEquals(1, $tracked->fresh()->stock);
            $this->assertEquals(3, $part->fresh()->stock_quantity);
            $this->assertSame('available', $unit->fresh()->status);
            $this->assertNull($unit->fresh()->sold_at);
        }
        $this->getJson('/api/order-inbox')->assertJsonPath('unread_count', 0);
    }

    public function test_only_pending_orders_can_be_cancelled(): void
    {
        [$order] = $this->context();
        foreach (['processing', 'shipped', 'completed', 'refunded'] as $status) {
            $order->update(['status' => $status]);
            $this->postJson('/api/orders/'.$order->id.'/cancel')->assertUnprocessable();
            $this->assertSame($status, $order->fresh()->status);
        }
    }

    public function test_staff_cannot_cancel_orders_from_another_store(): void
    {
        [$order] = $this->context();
        $other = Store::create(['name' => 'Other', 'address' => 'Kuwait']);
        Sanctum::actingAs(User::factory()->create(['role' => 'salesman', 'is_active' => true, 'store_id' => $other->id]));
        $this->postJson('/api/orders/'.$order->id.'/cancel')->assertForbidden();
        $this->assertSame('pending', $order->fresh()->status);
    }

    public function test_cancellation_requires_staff_authentication(): void
    {
        $this->postJson('/api/orders/1/cancel')->assertUnauthorized();
        [$order] = $this->context();
        Sanctum::actingAs(User::factory()->create(['role' => 'customer', 'is_active' => true]));
        $this->postJson('/api/orders/'.$order->id.'/cancel')->assertForbidden();
    }
}
