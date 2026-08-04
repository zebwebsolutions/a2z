<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Support\Str;

final class StructuredData
{
    public static function site(bool $includeWebsite = false): array
    {
        $homeUrl = url('/');
        $storeId = $homeUrl.'#store';
        $graph = [
            [
                '@type' => 'ElectronicsStore',
                '@id' => $storeId,
                'name' => 'A to Z Electronics & Repairing',
                'alternateName' => 'A2Z Kuwait',
                'url' => $homeUrl,
                'logo' => asset('images/a2z-logo.png'),
                'image' => asset('images/a2z-logo.png'),
                'telephone' => ['+96551523533', '+96597764165'],
                'email' => 'support@a2z.com',
                'address' => [
                    '@type' => 'PostalAddress',
                    'streetAddress' => 'Khalid Bin Waleed Street, Block 6, Kazmi 10 Building, Shop 2',
                    'addressLocality' => 'Sharq',
                    'addressCountry' => 'KW',
                ],
                'areaServed' => [
                    '@type' => 'Country',
                    'name' => 'Kuwait',
                ],
                'currenciesAccepted' => 'KWD',
                'paymentAccepted' => 'Cash on delivery',
                'openingHoursSpecification' => [
                    [
                        '@type' => 'OpeningHoursSpecification',
                        'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
                        'opens' => '09:00',
                        'closes' => '21:00',
                    ],
                    [
                        '@type' => 'OpeningHoursSpecification',
                        'dayOfWeek' => 'Saturday',
                        'opens' => '10:00',
                        'closes' => '20:00',
                    ],
                ],
                'contactPoint' => [
                    '@type' => 'ContactPoint',
                    'telephone' => '+96597764165',
                    'contactType' => 'customer service',
                    'areaServed' => 'KW',
                ],
            ],
        ];

        if ($includeWebsite) {
            $graph[] = [
                '@type' => 'WebSite',
                '@id' => $homeUrl.'#website',
                'url' => $homeUrl,
                'name' => 'A2Z Kuwait',
                'alternateName' => ['A2Z', 'A to Z Electronics & Repairing'],
                'publisher' => ['@id' => $storeId],
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@graph' => $graph,
        ];
    }

    public static function product(Product $product): array
    {
        $productUrl = route('product.show', $product->slug);
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            '@id' => $productUrl.'#product',
            'name' => $product->name,
            'url' => $productUrl,
        ];

        $images = self::productImages($product);
        if ($images !== []) {
            $schema['image'] = $images;
        }

        $description = self::plainText($product->description);
        if ($description !== '') {
            $schema['description'] = $description;
        }

        if (filled($product->sku)) {
            $schema['sku'] = (string) $product->sku;
        }

        if ($product->barcode_type === 'ean13' && self::isValidGtin13((string) $product->barcode)) {
            $schema['gtin13'] = (string) $product->barcode;
        }

        if ($product->brand && filled($product->brand->name)) {
            $schema['brand'] = [
                '@type' => 'Brand',
                'name' => $product->brand->name,
            ];
        }

        if ($product->category && filled($product->category->name)) {
            $schema['category'] = $product->category->name;
        }

        $colour = data_get($product->specs, 'COLOUR') ?? data_get($product->specs, 'COLOR');
        if (filled($colour)) {
            $schema['color'] = (string) $colour;
        }

        $properties = collect($product->specs)
            ->filter(fn ($value) => is_scalar($value) && filled((string) $value))
            ->map(fn ($value, $name) => [
                '@type' => 'PropertyValue',
                'name' => (string) $name,
                'value' => (string) $value,
            ])
            ->values()
            ->all();

        if ($properties !== []) {
            $schema['additionalProperty'] = $properties;
        }

        $schema['offers'] = [
            '@type' => 'Offer',
            'url' => $productUrl,
            'priceCurrency' => 'KWD',
            'price' => round((float) $product->price, 3),
            'availability' => self::availability($product),
            'itemCondition' => $product->is_used
                ? 'https://schema.org/UsedCondition'
                : 'https://schema.org/NewCondition',
            'seller' => ['@id' => url('/').'#store'],
        ];

        return $schema;
    }

    public static function breadcrumbs(iterable $items): array
    {
        $elements = collect($items)
            ->values()
            ->map(function ($item, $index) {
                $item = is_array($item) ? $item : (array) $item;
                $itemUrl = data_get($item, 'url');

                return [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'name' => (string) data_get($item, 'label', ''),
                    'item' => filled($itemUrl) && $itemUrl !== '#'
                        ? self::absoluteUrl((string) $itemUrl)
                        : url()->current(),
                ];
            })
            ->filter(fn ($item) => $item['name'] !== '')
            ->values()
            ->all();

        if ($elements === []) {
            return [];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $elements,
        ];
    }

    public static function encode(array $data): string
    {
        return json_encode(
            $data,
            JSON_UNESCAPED_SLASHES
                | JSON_UNESCAPED_UNICODE
                | JSON_HEX_TAG
                | JSON_HEX_AMP
                | JSON_HEX_APOS
                | JSON_HEX_QUOT
                | JSON_INVALID_UTF8_SUBSTITUTE
                | JSON_THROW_ON_ERROR
        );
    }

    private static function productImages(Product $product): array
    {
        return collect([$product->image])
            ->merge(is_array($product->gallery) ? $product->gallery : [])
            ->filter(fn ($image) => filled($image))
            ->map(function ($image) {
                $image = (string) $image;

                return Str::startsWith($image, ['http://', 'https://'])
                    ? $image
                    : asset('storage/'.ltrim($image, '/'));
            })
            ->unique()
            ->values()
            ->all();
    }

    private static function availability(Product $product): string
    {
        if ($product->stock > 0) {
            return 'https://schema.org/InStock';
        }

        return $product->is_used
            ? 'https://schema.org/SoldOut'
            : 'https://schema.org/OutOfStock';
    }

    private static function plainText(?string $value): string
    {
        $value = html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim((string) preg_replace('/\s+/u', ' ', $value));
    }

    private static function absoluteUrl(string $url): string
    {
        return Str::startsWith($url, ['http://', 'https://']) ? $url : url($url);
    }

    private static function isValidGtin13(string $value): bool
    {
        if (! preg_match('/^\d{13}$/', $value)) {
            return false;
        }

        $sum = 0;
        for ($index = 0; $index < 12; $index++) {
            $sum += (int) $value[$index] * ($index % 2 === 0 ? 1 : 3);
        }

        return (10 - ($sum % 10)) % 10 === (int) $value[12];
    }
}
