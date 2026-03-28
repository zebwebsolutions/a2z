<?php

namespace Tests\Feature\Api;

use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StoreControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_receives_all_stores(): void
    {
        $mainStore = Store::create([
            'name' => 'Main Store',
            'address' => 'Address 1',
        ]);

        $branchStore = Store::create([
            'name' => 'Branch Store',
            'address' => 'Address 2',
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'store_id' => $mainStore->id,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/stores');

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['id' => $mainStore->id, 'name' => 'Main Store'])
            ->assertJsonFragment(['id' => $branchStore->id, 'name' => 'Branch Store']);
    }

    public function test_non_admin_receives_only_their_store(): void
    {
        $mainStore = Store::create([
            'name' => 'Main Store',
            'address' => 'Address 1',
        ]);

        Store::create([
            'name' => 'Branch Store',
            'address' => 'Address 2',
        ]);

        $salesman = User::factory()->create([
            'role' => 'salesman',
            'is_active' => true,
            'store_id' => $mainStore->id,
        ]);

        Sanctum::actingAs($salesman);

        $response = $this->getJson('/api/stores');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $mainStore->id, 'name' => 'Main Store']);
    }
}
