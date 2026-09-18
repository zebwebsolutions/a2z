<?php

namespace Tests\Feature\Admin;

use App\Models\Purchase;
use App\Models\Role;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PurchasesAndUsersTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_pages_match_navigation_and_protect_customer_id(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('purchase-ids/id.jpg', 'private photo');
        $store = Store::create(['name' => 'Main', 'address' => 'Kuwait']);
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $purchase = Purchase::create(['store_id' => $store->id, 'user_id' => $admin->id, 'product_name' => 'Bought device', 'customer_name' => 'Buyer One', 'customer_phone' => '55551234', 'customer_id_image' => 'purchase-ids/id.jpg', 'unit_cost' => 10, 'quantity' => 1]);
        $this->actingAs($admin)->get('/admin/purchases')->assertOk()->assertSee('Bought device')->assertSee('data-lucide="shopping-bag"', false);
        $this->get('/admin/purchases/'.$purchase->id)->assertOk()->assertSee('Buyer One');
        $this->get('/admin/purchases/'.$purchase->id.'/id-image')->assertOk();
        $other = Store::create(['name' => 'Other', 'address' => 'Other']);
        $staff = User::factory()->create(['role' => 'salesman', 'is_active' => true, 'store_id' => $other->id]);
        $this->actingAs($staff)->get('/admin/purchases')->assertOk()->assertDontSee('Bought device');
        $this->get('/admin/purchases/'.$purchase->id)->assertForbidden();
        $this->get('/admin/purchases/'.$purchase->id.'/id-image')->assertForbidden();
        $customer = User::factory()->create(['role' => 'customer', 'is_active' => true]);
        $this->actingAs($customer)->get('/admin/purchases')->assertForbidden();
    }

    public function test_role_filter_supports_linked_and_legacy_roles_and_pagination(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $role = Role::create(['name' => 'customer']);
        User::factory()->create(['name' => 'Linked Customer', 'role_id' => $role->id]);
        $legacy = User::factory()->create(['name' => 'Legacy Customer', 'role' => 'customer']);
        $legacy->forceFill(['role_id' => null])->saveQuietly();
        User::factory()->create(['name' => 'Sales Staff', 'role' => 'salesman']);
        $this->actingAs($admin)->get('/admin/users?role=customer')->assertOk()
            ->assertSee('Linked Customer')->assertSee('Legacy Customer')->assertDontSee('Sales Staff');
        $this->get('/admin/users?role=salesman')->assertOk()->assertSee('Sales Staff')->assertDontSee('Linked Customer');
        User::factory()->count(22)->create(['role' => 'customer', 'role_id' => $role->id]);
        $this->get('/admin/users?role=customer')->assertOk()->assertSee('role=customer', false);
    }
}
