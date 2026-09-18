<?php

namespace Tests\Feature\Api;

use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderInboxTest extends TestCase
{
    use RefreshDatabase;

    private function user(?Store $store = null): User
    {
        $store ??= Store::create(['name' => 'Main', 'address' => 'Kuwait']);

        return User::factory()->create(['store_id' => $store->id, 'role' => 'salesman', 'is_active' => true]);
    }

    private function order(User $user, array $attributes = []): Order
    {
        return Order::create(array_merge(['user_id' => $user->id, 'store_id' => $user->store_id, 'total' => 20, 'payment_method' => 'cash', 'order_source' => 'online', 'status' => 'pending'], $attributes));
    }

    public function test_inbox_counts_only_unread_active_website_orders_for_own_store(): void
    {
        $user = $this->user();
        Sanctum::actingAs($user);
        $online = $this->order($user);
        $this->order($user, ['order_source' => 'in_store']);
        $this->order($this->user());
        foreach (['completed', 'cancelled', 'refunded'] as $status) {
            $this->order($user, ['status' => $status]);
        }
        $this->getJson('/api/order-inbox')->assertOk()->assertJsonPath('unread_count', 1)->assertJsonPath('latest_unread.id', $online->id);
    }

    public function test_opening_order_marks_it_read_for_only_that_staff_account(): void
    {
        $user = $this->user();
        $order = $this->order($user);
        Sanctum::actingAs($user);
        $this->getJson('/api/orders')->assertOk()->assertJsonPath('data.0.is_read', false);
        $this->postJson('/api/orders/'.$order->id.'/read')->assertNoContent();
        $this->postJson('/api/orders/'.$order->id.'/read')->assertNoContent();
        $this->assertDatabaseCount('order_reads', 1);
        $this->getJson('/api/order-inbox')->assertOk()->assertJsonPath('unread_count', 0)->assertJsonPath('latest_unread', null);
        $this->getJson('/api/orders')->assertOk()->assertJsonPath('data.0.is_read', true);
        $colleague = $this->user(Store::findOrFail($user->store_id));
        Sanctum::actingAs($colleague);
        $this->getJson('/api/order-inbox')->assertOk()->assertJsonPath('unread_count', 1);
        $this->getJson('/api/orders')->assertOk()->assertJsonPath('data.0.is_read', false);
    }

    public function test_cannot_mark_another_stores_order_read(): void
    {
        $user = $this->user();
        $order = $this->order($this->user());
        Sanctum::actingAs($user);
        $this->postJson('/api/orders/'.$order->id.'/read')->assertForbidden();
        $this->assertDatabaseCount('order_reads', 0);
    }

    public function test_new_orders_advance_cursor_and_reading_does_not_reset_it(): void
    {
        $user = $this->user();
        Sanctum::actingAs($user);
        $this->getJson('/api/order-inbox')->assertOk()->assertJsonPath('latest_order_id', null);
        $first = $this->order($user);
        $second = $this->order($user);
        $this->getJson('/api/order-inbox')->assertOk()->assertJsonPath('unread_count', 2)->assertJsonPath('latest_order_id', $second->id);
        $this->postJson('/api/orders/'.$second->id.'/read')->assertNoContent();
        $this->getJson('/api/order-inbox')->assertOk()->assertJsonPath('latest_order_id', $second->id)->assertJsonPath('latest_unread.id', $first->id);
    }

    public function test_inbox_requires_staff_authentication(): void
    {
        $this->getJson('/api/order-inbox')->assertUnauthorized();
        Sanctum::actingAs(User::factory()->create(['role' => 'customer', 'is_active' => true]));
        $this->getJson('/api/order-inbox')->assertForbidden();
    }
}
