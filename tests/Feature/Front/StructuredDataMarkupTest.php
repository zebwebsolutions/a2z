<?php

namespace Tests\Feature\Front;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StructuredDataMarkupTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_identifies_the_website_and_electronics_store(): void
    {
        $html = $this->get(route('home'))->assertOk()->getContent();
        $schemas = $this->schemasFromResponse($html);
        $siteGraph = collect($schemas)->first(fn ($schema) => isset($schema['@graph']));

        $this->assertStringContainsString('<title>A2Z Mobiles &amp; Repair Kuwait', $html);
        $this->assertStringNotContainsString('A2Z Mobiles &amp;amp; Repair Kuwait', $html);
        $this->assertNotNull($siteGraph);
        $this->assertNotNull(collect($siteGraph['@graph'])->firstWhere('@type', 'ElectronicsStore'));
        $this->assertSame('A2Z Kuwait', collect($siteGraph['@graph'])->firstWhere('@type', 'WebSite')['name']);
    }

    public function test_product_page_outputs_product_offer_and_breadcrumb_markup(): void
    {
        $store = Store::create([
            'name' => 'Main Store',
            'address' => 'Test Address',
        ]);
        $category = Category::create([
            'name' => 'Phones',
            'slug' => 'phones-test-schema',
            'is_active' => true,
        ]);
        $brand = Brand::create([
            'name' => 'Apple',
            'slug' => 'apple-test-schema',
            'is_active' => true,
        ]);
        $product = Product::create([
            'store_id' => $store->id,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'name' => 'Schema Test Phone',
            'slug' => 'schema-test-phone',
            'description' => 'A phone used to verify structured data.',
            'price' => 199.5,
            'stock' => 3,
            'image' => 'products/schema-test.jpg',
            'is_active' => true,
            'is_used' => false,
            'specs' => ['RAM' => '8 GB', 'STORAGE' => '256 GB'],
        ]);

        $schemas = $this->schemasFromResponse(
            $this->get(route('product.show', $product->slug))->assertOk()->getContent()
        );
        $productSchema = collect($schemas)->firstWhere('@type', 'Product');
        $breadcrumbSchema = collect($schemas)->firstWhere('@type', 'BreadcrumbList');

        $this->assertNotNull($productSchema);
        $this->assertSame('Schema Test Phone', $productSchema['name']);
        $this->assertSame('KWD', $productSchema['offers']['priceCurrency']);
        $this->assertSame(199.5, $productSchema['offers']['price']);
        $this->assertSame('https://schema.org/InStock', $productSchema['offers']['availability']);
        $this->assertSame('Apple', $productSchema['brand']['name']);
        $this->assertSame('256 GB', collect($productSchema['additionalProperty'])->firstWhere('name', 'STORAGE')['value']);

        $this->assertNotNull($breadcrumbSchema);
        $this->assertSame('Schema Test Phone', collect($breadcrumbSchema['itemListElement'])->last()['name']);
    }

    private function schemasFromResponse(string $html): array
    {
        preg_match_all('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $matches);

        return collect($matches[1] ?? [])
            ->map(fn ($json) => json_decode($json, true, flags: JSON_THROW_ON_ERROR))
            ->all();
    }
}
