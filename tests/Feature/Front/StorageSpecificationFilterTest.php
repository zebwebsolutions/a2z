<?php

namespace Tests\Feature\Front;

use App\Models\Product;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class StorageSpecificationFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_storage_filter_matches_storage_and_storage_capacity_specs(): void
    {
        $store = Store::create([
            'name' => 'Main Store',
            'address' => 'Test Address',
        ]);

        $storageProduct = $this->createProduct($store, 'Storage Key Product', [
            'STORAGE' => '1TB',
        ]);
        $capacityProduct = $this->createProduct($store, 'Storage Capacity Key Product', [
            'STORAGE' => '1 TB',
        ]);
        DB::table('products')->where('id', $capacityProduct->id)->update([
            'specs' => json_encode(['storage capacity' => '1tb']),
        ]);
        $this->createProduct($store, 'Different Storage Product', [
            'STORAGE' => '512GB',
        ]);

        $matchingIds = Product::query()
            ->whereStorageSpecification('1TB')
            ->orderBy('id')
            ->pluck('id')
            ->all();

        $this->assertSame([$storageProduct->id, $capacityProduct->id], $matchingIds);
        $this->assertSame('STORAGE', Product::normalizeSpecificationKey('storage capacity'));
        $this->assertSame('1 TB', Product::storageSpecificationValue(['Storage Capacity' => '1tb']));
        $this->assertSame(
            ['1 TB'],
            Product::specificationOptions([
                ['STORAGE' => '1 TB'],
                ['STORAGE CAPACITY' => '1tb'],
            ], Product::STORAGE_SPEC_KEYS)->all()
        );
    }

    private function createProduct(Store $store, string $name, array $specs): Product
    {
        return Product::create([
            'store_id' => $store->id,
            'name' => $name,
            'slug' => Str::slug($name) . '-' . Str::lower(Str::random(6)),
            'price' => 100,
            'stock' => 1,
            'is_active' => true,
            'specs' => $specs,
        ]);
    }
}
