<?php

namespace Tests\Feature\Front;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\UsedDeviceDetail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class StorefrontFilterIssuesTest extends TestCase
{
    use RefreshDatabase;

    public function test_specification_filters_ignore_unit_spacing_and_case_and_support_screen_size_keys(): void
    {
        $store = $this->createStore();
        $first = $this->createProduct($store, 'First Device', specs: ['RAM' => '8 GB', 'SCREENSIZE' => '6.8 Inches']);
        $second = $this->createProduct($store, 'Second Device', specs: ['RAM' => '8 GB', 'SCREENSIZE' => '6.8 Inches']);

        DB::table('products')->where('id', $first->id)->update([
            'specs' => json_encode(['ram' => '8gb', 'Screen Size' => '6.8']),
        ]);
        DB::table('products')->where('id', $second->id)->update([
            'specs' => json_encode(['RAM' => '8 GB', 'SCREENSIZE' => '6.8 inches']),
        ]);

        $this->assertSame(
            [$first->id, $second->id],
            Product::query()->whereRamSpecification('8 GB')->orderBy('id')->pluck('id')->all()
        );
        $this->assertSame(
            [$first->id, $second->id],
            Product::query()->whereScreenSizeSpecification('6.8 Inches')->orderBy('id')->pluck('id')->all()
        );
    }

    public function test_category_page_hides_inactive_products(): void
    {
        $store = $this->createStore();
        $category = Category::create([
            'name' => 'Cameras',
            'slug' => 'cameras',
            'is_active' => true,
        ]);

        $this->createProduct($store, 'Visible Camera', category: $category);
        $this->createProduct($store, 'Hidden Camera', category: $category, active: false);

        $this->get(route('category.show', $category->slug))
            ->assertOk()
            ->assertSee('Visible Camera')
            ->assertDontSee('Hidden Camera');
    }

    public function test_shop_filter_options_are_scoped_to_the_current_category(): void
    {
        $store = $this->createStore();
        $phones = Category::create(['name' => 'Phones Test', 'slug' => 'phones-test', 'is_active' => true]);
        $laptops = Category::create(['name' => 'Laptops Test', 'slug' => 'laptops-test', 'is_active' => true]);
        $phoneBrand = Brand::create(['name' => 'Phone Brand', 'slug' => 'phone-brand', 'is_active' => true]);
        $laptopBrand = Brand::create(['name' => 'Laptop Brand', 'slug' => 'laptop-brand', 'is_active' => true]);

        $this->createProduct($store, 'Scoped Phone', ['RAM' => '8 GB'], $phones, $phoneBrand);
        $this->createProduct($store, 'Unrelated Laptop', ['RAM' => '32 GB'], $laptops, $laptopBrand);

        $this->get(route('shop.index', ['category' => $phones->slug]))
            ->assertOk()
            ->assertSee('value="8 GB"', false)
            ->assertDontSee('value="32 GB"', false)
            ->assertSee('value="phone-brand"', false)
            ->assertDontSee('value="laptop-brand"', false);
    }

    public function test_used_device_battery_filter_is_single_choice_and_has_a_removable_chip(): void
    {
        $store = $this->createStore();
        $product = $this->createProduct($store, 'Used Phone', ['RAM' => '8 GB'], used: true);

        UsedDeviceDetail::create([
            'product_id' => $product->id,
            'device_condition' => 'A',
            'battery_health' => 95,
        ]);

        $this->get(route('shop.used', ['battery' => 90]))
            ->assertOk()
            ->assertSee('name="battery"', false)
            ->assertDontSee('name="battery[]"', false)
            ->assertSee('Battery: 90%+')
            ->assertSee('data-remove="battery"', false);
    }

    private function createStore(): Store
    {
        return Store::create([
            'name' => 'Main Store',
            'address' => 'Test Address',
        ]);
    }

    private function createProduct(
        Store $store,
        string $name,
        array $specs = [],
        ?Category $category = null,
        ?Brand $brand = null,
        bool $active = true,
        bool $used = false,
    ): Product {
        return Product::create([
            'store_id' => $store->id,
            'category_id' => $category?->id,
            'brand_id' => $brand?->id,
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(6)),
            'price' => 100,
            'stock' => 1,
            'is_active' => $active,
            'is_used' => $used,
            'specs' => $specs,
        ]);
    }
}
