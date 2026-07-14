<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class GoogleFeedController extends Controller
{
    /**
     * Map your category slugs/names to Google's official product taxonomy.
     * Full list: https://www.google.com/basepages/producttype/taxonomy-with-ids.en-US.txt
     */
    private array $googleCategoryMap = [
        // Phones
        'phones'             => 'Electronics > Communications > Telephony > Mobile Phones',
        'mobile phones'      => 'Electronics > Communications > Telephony > Mobile Phones',

        // Laptops
        'laptops'            => 'Electronics > Computers > Laptops',

        // Tablets
        'tablets'            => 'Electronics > Computers > Tablet Computers',

        // Smart Watches
        'smart watches'      => 'Electronics > Electronics Accessories > Wearable Technology > Smart Watches',
        'smart-watches'      => 'Electronics > Electronics Accessories > Wearable Technology > Smart Watches',

        // Wearables
        'wearables'          => 'Electronics > Electronics Accessories > Wearable Technology',
        'fitness bands'      => 'Electronics > Electronics Accessories > Wearable Technology > Fitness Trackers',
        'smart rings'        => 'Electronics > Electronics Accessories > Wearable Technology',
        'smart trackers'     => 'Electronics > Electronics Accessories > Wearable Technology > Fitness Trackers',

        // Accessories
        'accessories'        => 'Electronics > Electronics Accessories',
        'chargers'           => 'Electronics > Electronics Accessories > Power > Chargers',
        'adapters'           => 'Electronics > Electronics Accessories > Power > Chargers',
        'cables'             => 'Electronics > Electronics Accessories > Cables',
        'cases & covers'     => 'Electronics > Electronics Accessories > Cases & Covers',
        'cases and covers'   => 'Electronics > Electronics Accessories > Cases & Covers',
        'cases-covers'       => 'Electronics > Electronics Accessories > Cases & Covers',
        'earbuds'            => 'Electronics > Audio > Headphones',
        'headphones'         => 'Electronics > Audio > Headphones',
        'power banks'        => 'Electronics > Electronics Accessories > Power > Portable Power Supplies',
        'power-banks'        => 'Electronics > Electronics Accessories > Power > Portable Power Supplies',
        'screen protectors'  => 'Electronics > Electronics Accessories > Screen Protectors',
        'screen-protectors'  => 'Electronics > Electronics Accessories > Screen Protectors',
        'speakers'           => 'Electronics > Audio > Speakers',
        'memory cards'       => 'Electronics > Electronics Accessories > Memory > Flash Memory Cards',
        'car holders'        => 'Vehicles & Parts > Vehicle Parts & Accessories > Car Accessories',
        'stands & holders'   => 'Electronics > Electronics Accessories > Stands',
        'stands-holders'     => 'Electronics > Electronics Accessories > Stands',
        'watch straps'       => 'Electronics > Electronics Accessories > Wearable Technology > Smart Watch Accessories',
        'watch-straps'       => 'Electronics > Electronics Accessories > Wearable Technology > Smart Watch Accessories',
        'watch chargers'     => 'Electronics > Electronics Accessories > Wearable Technology > Smart Watch Accessories',
        'watch-chargers'     => 'Electronics > Electronics Accessories > Wearable Technology > Smart Watch Accessories',
        'popsockets & grips' => 'Electronics > Electronics Accessories',
        'grips'              => 'Electronics > Electronics Accessories',
        'smart gadgets'      => 'Electronics > Electronics Accessories',
    ];

    /**
     * Detect real brand from product name when brand relation is missing or generic.
     */
    private array $brandKeywords = [
        'apple watch' => 'Apple',
        'iphone'      => 'Apple',
        'ipad'        => 'Apple',
        'macbook'     => 'Apple',
        'airpods'     => 'Apple',
        'apple'       => 'Apple',
        'galaxy'      => 'Samsung',
        'samsung'     => 'Samsung',
        'huawei'      => 'Huawei',
        'belkin'      => 'Belkin',
        'hoco'        => 'Hoco',
        'yesido'      => 'Yesido',
        'pininfarina' => 'Pininfarina',
        'earldom'     => 'Earldom',
        'wewe'        => 'Wewe',
    ];

    /**
     * Old/budget models to skip if priced above a threshold (price in KWD).
     */
    private array $suspiciousPriceRules = [
        'a03' => 50.0,
        'a10' => 50.0,
        'a12' => 60.0,
        'a13' => 65.0,
        'a20' => 55.0,
        'a30' => 60.0,
    ];

    private array $merchantSpecLabels = [
        'SCREEN SIZE' => 'Screen Size',
        'FRONT CAMERA RESOLUTION' => 'Front Camera Resolution',
        'RAM' => 'RAM',
        'REAR CAMERA RESOLUTION' => 'Rear Camera Resolution',
        'STORAGE CAPACITY' => 'Storage Capacity',
        'SCREEN RESOLUTION' => 'Screen Resolution',
        'WEIGHT' => 'Weight',
        'COLOUR' => 'Colour',
    ];

    public function index(): Response
    {
        $feedXml = Cache::remember('google_merchant_feed', now()->addHours(6), function () {
            return $this->buildFeed();
        });

        return response($feedXml, 200)->header('Content-Type', 'application/xml');
    }

    // -------------------------------------------------------------------------
    // Feed builder
    // -------------------------------------------------------------------------

    private function buildFeed(): string
    {
        $products = Product::with(['brand:id,name', 'category:id,name,slug', 'parentCategory:id,name,slug'])
            ->where('is_active', 1)
            ->where('stock', '>', 0)
            ->where(function ($query) {
                $query->whereNull('is_used')->orWhere('is_used', 0);
            })
            ->get();

        $xml = new \SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">'
            . '<channel></channel></rss>'
        );

        $xml->channel->addChild('title', 'A to Z Electronics &amp; Repairing');
        $xml->channel->addChild('link', url('/'));
        $xml->channel->addChild('description', 'Electronics &amp; Mobile Repair Products in Kuwait');

        foreach ($products as $product) {
            $this->maybeAddItem($xml->channel, $product);
        }

        return $xml->asXML();
    }

    private function maybeAddItem(\SimpleXMLElement $channel, Product $product): void
    {
        // Must have an image
        if (empty($product->image)) {
            return;
        }

        // Must have a real price
        if (!$product->price || floatval($product->price) <= 0) {
            return;
        }

        // Skip spare parts categories
        if ($this->isSparePartCategory($product->parentCategory?->name, $product->category?->name)) {
            return;
        }

        // Skip old budget models with suspicious prices
        if ($this->isSuspiciouslyPriced($product->name, floatval($product->price))) {
            return;
        }

        // Resolve all fields
        $brand          = $this->resolveBrand($product);
        $googleCategory = $this->resolveGoogleCategory($product);
        $productType    = $this->resolveProductType($product);
        $description    = $this->buildMerchantDescription($product);

        // Must have a meaningful description after specs are included.
        if (!$this->hasMeaningfulDescription($description)) {
            return;
        }

        $price          = number_format(floatval($product->price), 3) . ' KWD';
        $imageUrl       = $this->resolveImageUrl($product->image);
        $productUrl     = $this->resolveProductUrl($product);
        $rawBarcode     = trim((string) ($product->barcode ?? ''));
        $gtin           = $this->normalizeAndValidateGtin($rawBarcode);

        // Build the item node
        $ns   = 'http://base.google.com/ns/1.0';
        $item = $channel->addChild('item');

        $item->addChild('g:id',           (string) $product->id,                    $ns);
        $item->addChild('g:title',        htmlspecialchars((string) $product->name), $ns);
        $item->addChild('g:description',  htmlspecialchars($description),            $ns);
        $item->addChild('g:link',         $productUrl,                               $ns);
        $item->addChild('g:image_link',   $imageUrl,                                 $ns);
        $item->addChild('g:availability', 'in stock',                                $ns);
        $item->addChild('g:price',        $price,                                    $ns);
        $item->addChild('g:condition',    'new',                                     $ns);
        $item->addChild('g:brand',        htmlspecialchars($brand),                  $ns);
        $item->addChild('g:product_type', htmlspecialchars($productType),            $ns);

        // Google's official taxonomy — improves ad targeting and placement
        if ($googleCategory) {
            $item->addChild('g:google_product_category', $googleCategory, $ns);
        }

        // GTIN (valid manufacturer barcode) vs fallback
        if ($gtin) {
            $item->addChild('g:gtin', $gtin, $ns);
        } else {
            // Barcode exists but isn't a valid GTIN — use as MPN instead
            if ($rawBarcode !== '') {
                $item->addChild('g:mpn', htmlspecialchars($rawBarcode), $ns);
            }
            $item->addChild('g:identifier_exists', 'no', $ns);
        }
    }

    // -------------------------------------------------------------------------
    // Resolution helpers
    // -------------------------------------------------------------------------

    private function resolveBrand(Product $product): string
    {
        // Check product name first — most reliable for electronics
        $name = mb_strtolower((string) $product->name);

        foreach ($this->brandKeywords as $keyword => $brand) {
            if (str_contains($name, $keyword)) {
                return $brand;
            }
        }

        // Fall back to brand relation if it's not a generic store name
        $relationBrand = trim((string) ($product->brand?->name ?? ''));
        $genericNames  = ['a2z electronics', 'a to z electronics', 'a to z electronics & repairing'];

        if ($relationBrand !== '' && !in_array(mb_strtolower($relationBrand), $genericNames, true)) {
            return $relationBrand;
        }

        return 'A to Z Electronics & Repairing';
    }

    private function resolveGoogleCategory(Product $product): ?string
    {
        // Your breadcrumb is: Home > Phones > SAMSUNG
        // So parentCategory = the actual product type (Phones, Laptops, etc.)
        // and category = the brand (Samsung, Apple, etc.) — not useful for Google taxonomy
        // Therefore we only look at parentCategory for Google's taxonomy mapping.
        $candidates = [
            mb_strtolower(trim((string) ($product->parentCategory?->slug ?? ''))),
            mb_strtolower(trim((string) ($product->parentCategory?->name ?? ''))),
            // Fall back to category only if parentCategory is empty
            mb_strtolower(trim((string) ($product->category?->slug ?? ''))),
            mb_strtolower(trim((string) ($product->category?->name ?? ''))),
        ];

        foreach ($candidates as $candidate) {
            if ($candidate !== '' && isset($this->googleCategoryMap[$candidate])) {
                return $this->googleCategoryMap[$candidate];
            }
        }

        return null;
    }

    private function resolveProductType(Product $product): string
    {
        // Your breadcrumb: Home > Phones > SAMSUNG
        // For g:product_type we want "Phones" only — the brand segment is redundant
        // since we already have g:brand. Showing "Phones > Samsung" would look odd.
        $parentName = trim((string) ($product->parentCategory?->name ?? ''));

        if ($parentName !== '') {
            return $parentName;
        }

        // If no parent, fall back to category name (only if it's not a brand name)
        $categoryName = trim((string) ($product->category?->name ?? ''));
        $isBrandName  = isset($this->brandKeywords[mb_strtolower($categoryName)]);

        if ($categoryName !== '' && !$isBrandName) {
            return $categoryName;
        }

        return 'Electronics';
    }

    private function resolveImageUrl(string $image): string
    {
        if (str_starts_with($image, 'http')) {
            return $image;
        }

        return asset('storage/' . ltrim($image, '/'));
    }

    private function resolveProductUrl(Product $product): string
    {
        // Prefer slug-based URLs to match your live site (/product/slug)
        if (!empty($product->slug)) {
            return url('/product/' . $product->slug);
        }

        // Fallback for localhost testing
        return url('/products/' . $product->id);
    }

    private function buildMerchantDescription(Product $product): string
    {
        $description = $this->cleanText((string) $product->description);

        if ($description === '') {
            $description = $this->cleanText((string) $product->name . ' available at A2Z Mobiles Kuwait.');
        }

        $specs = $this->buildMerchantSpecsText($product->specs ?? []);

        $parts = array_filter([
            $description,
            $specs,
            'Available at A2Z Mobiles Kuwait.',
        ]);

        return mb_substr($this->cleanText(implode(' ', $parts)), 0, 4990);
    }

    private function buildMerchantSpecsText(array $specs): string
    {
        $segments = [];

        foreach ($this->merchantSpecLabels as $storedKey => $label) {
            $value = $this->findSpecValue($specs, $storedKey);

            if ($value !== null) {
                $segments[] = "{$label}: {$value}.";
            }
        }

        return $this->cleanText(implode(' ', $segments));
    }

    private function findSpecValue(array $specs, string $storedKey): ?string
    {
        foreach ($specs as $key => $value) {
            if (mb_strtoupper(trim((string) $key)) !== $storedKey) {
                continue;
            }

            $cleanValue = $this->cleanText((string) $value);

            return $cleanValue !== '' ? $cleanValue : null;
        }

        return null;
    }

    private function cleanText(string $text): string
    {
        return trim(preg_replace('/\s+/', ' ', strip_tags($text)) ?? '');
    }

    // -------------------------------------------------------------------------
    // Validation helpers
    // -------------------------------------------------------------------------

    private function hasMeaningfulDescription(?string $description): bool
    {
        $clean = $this->cleanText((string) $description);

        if ($clean === '' || mb_strlen($clean) < 20) {
            return false;
        }

        return count(array_filter(preg_split('/\s+/', $clean) ?: [])) >= 4;
    }

    private function isSparePartCategory(?string $parentCategory, ?string $category): bool
    {
        $haystack = mb_strtolower(trim(($parentCategory ?? '') . ' ' . ($category ?? '')));

        return str_contains($haystack, 'spare part')
            || str_contains($haystack, 'spare parts')
            || str_contains($haystack, 'parts');
    }

    private function isSuspiciouslyPriced(string $name, float $price): bool
    {
        $lower = mb_strtolower($name);

        foreach ($this->suspiciousPriceRules as $model => $maxPrice) {
            if (str_contains($lower, $model) && $price > $maxPrice) {
                return true;
            }
        }

        return false;
    }

    /**
     * Normalize barcode to digits only, validate GTIN length,
     * and verify the GS1 checksum to prevent Google rejections.
     */
    private function normalizeAndValidateGtin(?string $barcode): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $barcode);

        if ($digits === null || $digits === '') {
            return null;
        }

        // Valid GTIN lengths: 8 (GTIN-8), 12 (UPC), 13 (EAN), 14 (GTIN-14)
        if (!in_array(strlen($digits), [8, 12, 13, 14], true)) {
            return null;
        }

        if (!$this->isValidGtinChecksum($digits)) {
            return null;
        }

        return $digits;
    }

    /**
     * GS1 GTIN checksum (Luhn variant).
     * Multiplies alternating digits by 3 and 1 from right to left,
     * then verifies the final check digit.
     */
    private function isValidGtinChecksum(string $digits): bool
    {
        $length    = strlen($digits);
        $sum       = 0;
        $multiplyBy3 = true; // Rightmost digit before check digit gets ×3

        for ($i = $length - 2; $i >= 0; $i--) {
            $sum       += (int) $digits[$i] * ($multiplyBy3 ? 3 : 1);
            $multiplyBy3 = !$multiplyBy3;
        }

        $expectedCheck = (10 - ($sum % 10)) % 10;

        return $expectedCheck === (int) $digits[$length - 1];
    }
}
