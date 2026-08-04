<?php

namespace Tests\Unit;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Support\StructuredData;
use Tests\TestCase;

class StructuredDataTest extends TestCase
{
    public function test_product_markup_only_uses_a_valid_ean_as_gtin(): void
    {
        $product = new Product([
            'name' => 'Test Product',
            'slug' => 'test-product',
            'sku' => 'SKU-1',
            'description' => '<p>Useful &amp; safe.</p>',
            'price' => 10.25,
            'stock' => 1,
            'barcode' => '4006381333931',
            'barcode_type' => 'ean13',
            'is_used' => false,
            'specs' => ['COLOR' => 'Black'],
        ]);
        $product->setRelation('brand', new Brand(['name' => 'Test Brand']));
        $product->setRelation('category', new Category(['name' => 'Accessories']));

        $schema = StructuredData::product($product);

        $this->assertSame('4006381333931', $schema['gtin13']);
        $this->assertSame('Useful & safe.', $schema['description']);
        $this->assertSame('Black', $schema['color']);

        $product->barcode = '1234567890123';
        $this->assertArrayNotHasKey('gtin13', StructuredData::product($product));
    }

    public function test_json_encoding_cannot_close_the_script_element(): void
    {
        $encoded = StructuredData::encode(['name' => '</script><script>alert(1)</script>']);

        $this->assertStringNotContainsString('</script>', $encoded);
        $this->assertStringContainsString('\\u003C', $encoded);
    }
}
