<?php

namespace Tests\Feature\Api;

use App\Jobs\DeliverOrderPush;
use App\Jobs\NotifyNewOrder;
use App\Models\MobilePushDevice;
use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OrderNotificationTest extends TestCase
{
    use RefreshDatabase;

    private function context(): array
    {
        Queue::fake();
        Http::preventStrayRequests();
        $store = Store::create(['name' => 'Main', 'address' => 'Kuwait']);
        $user = User::factory()->create(['store_id' => $store->id, 'role' => 'salesman', 'is_active' => true]);
        $token = $user->createToken('mobile');
        $device = MobilePushDevice::create(['user_id' => $user->id, 'personal_access_token_id' => $token->accessToken->id, 'expo_token' => 'ExpoPushToken[test-device]']);

        return [$store, $user, $token, $device];
    }

    private function order(User $user, string $source = 'online'): Order
    {
        return Order::create(['user_id' => $user->id, 'store_id' => $user->store_id, 'total' => 100, 'payment_method' => 'cash', 'order_source' => $source, 'status' => 'pending']);
    }

    public function test_only_committed_website_orders_queue_notifications(): void
    {
        [, $user] = $this->context();
        DB::beginTransaction();
        $order = $this->order($user);
        Queue::assertNothingPushed();
        DB::commit();
        Queue::assertPushed(NotifyNewOrder::class, fn ($job) => $job->orderId === $order->id);
        Queue::fake();
        DB::beginTransaction();
        $this->order($user);
        DB::rollBack();
        $this->order($user, 'in_store');
        Queue::assertNothingPushed();
    }

    public function test_registration_and_logout_are_bound_to_the_current_session(): void
    {
        [, $user, $token] = $this->context();
        $this->withToken($token->plainTextToken)->postJson('/api/push-devices', ['expo_token' => 'ExpoPushToken[new-device]'])->assertOk();
        $this->assertDatabaseHas('mobile_push_devices', ['user_id' => $user->id, 'personal_access_token_id' => $token->accessToken->id, 'expo_token' => 'ExpoPushToken[new-device]']);
        $this->withToken($token->plainTextToken)->postJson('/api/push-devices', ['expo_token' => 'invalid'])->assertUnprocessable();
        $this->withToken($token->plainTextToken)->deleteJson('/api/push-devices')->assertNoContent();
        $this->assertDatabaseCount('mobile_push_devices', 0);
    }

    public function test_delivery_contains_order_reference_and_rejects_other_stores(): void
    {
        [, $user, , $device] = $this->context();
        $order = $this->order($user);
        Http::fake(['exp.host/*' => Http::response(['data' => ['status' => 'ok', 'id' => 'ticket']])]);
        (new NotifyNewOrder($order->id))->handle();
        Queue::assertPushed(DeliverOrderPush::class, fn ($job) => $job->deviceId === $device->id);
        (new DeliverOrderPush($order->id, $device->id))->handle();
        Http::assertSent(fn ($request) => $request['to'] === $device->expo_token && $request['data']['order_id'] === $order->id && ! isset($request['customer_name']));
        $other = Store::create(['name' => 'Other', 'address' => 'Other']);
        $user->update(['store_id' => $other->id]);
        Http::fake();
        (new DeliverOrderPush($order->id, $device->id))->handle();
        Http::assertNothingSent();
    }

    public function test_inactive_expired_and_revoked_sessions_receive_nothing(): void
    {
        [, $user, $token, $device] = $this->context();
        $order = $this->order($user);
        Http::fake();
        $user->update(['is_active' => false]);
        (new DeliverOrderPush($order->id, $device->id))->handle();
        $user->update(['is_active' => true]);
        $token->accessToken->update(['expires_at' => now()->subMinute()]);
        (new DeliverOrderPush($order->id, $device->id))->handle();
        $token->accessToken->delete();
        (new DeliverOrderPush($order->id, $device->id))->handle();
        Http::assertNothingSent();
        $this->assertDatabaseCount('mobile_push_devices', 0);
    }

    public function test_unregistered_device_is_removed(): void
    {
        [, $user, , $device] = $this->context();
        $order = $this->order($user);
        Http::fake(['exp.host/*' => Http::response(['data' => ['status' => 'error', 'details' => ['error' => 'DeviceNotRegistered']]])]);
        (new DeliverOrderPush($order->id, $device->id))->handle();
        $this->assertDatabaseCount('mobile_push_devices', 0);
    }

    public function test_delivery_receipt_removes_invalid_tokens(): void
    {
        [, , , $device] = $this->context();
        Http::fake(['exp.host/*' => Http::response(['data' => ['receipt-1' => ['status' => 'error', 'details' => ['error' => 'DeviceNotRegistered']]]])]);
        (new \App\Jobs\CheckOrderPushReceipt($device->id, 'receipt-1'))->handle();
        $this->assertDatabaseCount('mobile_push_devices', 0);
    }
}
