<?php

namespace Tests\Feature\Front;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Store;
use App\Models\User;
use App\Services\MetaCloudWhatsAppService;
use App\Services\OrderReceiptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class CartCheckoutInventoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_online_checkout_reserves_stock_and_cancellation_restores_it(): void
    {
        $store = Store::create([
            'name' => 'Online Store',
            'address' => 'Test Address',
        ]);
        $category = Category::create([
            'name' => 'Online Products',
            'slug' => 'online-products-' . Str::lower(Str::random(6)),
        ]);
        $user = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'store_id' => $store->id,
        ]);

        $trackedProduct = Product::create([
            'store_id' => $store->id,
            'category_id' => $category->id,
            'name' => 'Online Tracked Phone',
            'slug' => 'online-tracked-phone-' . Str::lower(Str::random(6)),
            'price' => 100,
            'stock' => 1,
            'tracks_inventory_by_unit' => true,
        ]);
        $unit = ProductUnit::create([
            'product_id' => $trackedProduct->id,
            'imei_1' => '356789012345678',
            'status' => 'available',
        ]);
        $regularProduct = Product::create([
            'store_id' => $store->id,
            'category_id' => $category->id,
            'name' => 'Online Accessory',
            'slug' => 'online-accessory-' . Str::lower(Str::random(6)),
            'price' => 10,
            'stock' => 3,
        ]);

        Mail::fake();
        $this->mock(OrderReceiptService::class)
            ->shouldReceive('generate')
            ->once()
            ->andReturn(null);
        $this->mock(MetaCloudWhatsAppService::class)
            ->shouldNotReceive('sendReceipt');

        $response = $this
            ->actingAs($user)
            ->withSession([
                'cart' => [
                    $trackedProduct->id => [
                        'name' => $trackedProduct->name,
                        'price' => 1,
                        'quantity' => 1,
                    ],
                    $regularProduct->id => [
                        'name' => $regularProduct->name,
                        'price' => 1,
                        'quantity' => 2,
                    ],
                ],
            ])
            ->post('/cart/place-order', [
                'customer_name' => 'Online Customer',
                'customer_email' => 'customer@example.test',
                'customer_phone' => '55555555',
                'customer_address' => 'Kuwait City',
            ]);

        $order = Order::query()->latest('id')->firstOrFail();

        $response->assertRedirect(route('cart.success', $order->id));
        $this->assertSame('pending', $order->status);
        $this->assertSame('online', $order->order_source);
        $this->assertSame(121.0, (float) $order->total);
        $this->assertSame(0, $trackedProduct->fresh()->stock);
        $this->assertSame('sold', $unit->fresh()->status);
        $this->assertSame(1, $regularProduct->fresh()->stock);
        $this->assertDatabaseHas('order_item_product_unit', [
            'product_unit_id' => $unit->id,
        ]);

        $cancelResponse = $this
            ->actingAs($user)
            ->put('/admin/orders/' . $order->id, [
                'status' => 'cancelled',
            ]);

        $cancelResponse->assertRedirect(route('admin.orders.index'));
        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame(1, $trackedProduct->fresh()->stock);
        $this->assertSame('available', $unit->fresh()->status);
        $this->assertSame(3, $regularProduct->fresh()->stock);
    }
}
