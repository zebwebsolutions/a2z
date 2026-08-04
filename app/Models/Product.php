<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    public const STORAGE_SPEC_KEYS = [
        'STORAGE',
        'STORAGE CAPACITY',
    ];

    public const STORAGE_SPEC_JSON_KEYS = [
        'STORAGE',
        'Storage',
        'storage',
        'STORAGE CAPACITY',
        'Storage Capacity',
        'storage capacity',
    ];

    public const RAM_SPEC_JSON_KEYS = [
        'RAM',
        'Ram',
        'ram',
    ];

    public const PROCESSOR_SPEC_JSON_KEYS = [
        'PROCESSOR',
        'Processor',
        'processor',
    ];

    public const SCREEN_SIZE_SPEC_KEYS = [
        'SCREENSIZE',
        'SCREEN SIZE',
        'SCREEN_SIZE',
    ];

    public const SCREEN_SIZE_SPEC_JSON_KEYS = [
        'SCREENSIZE',
        'ScreenSize',
        'screensize',
        'SCREEN SIZE',
        'Screen Size',
        'screen size',
        'SCREEN_SIZE',
        'screen_size',
    ];

    protected $fillable = [
        'store_id',
        'category_id',
        'name',
        'slug',
        'sku',
        'description',
        'price',
        'cost_price',
        'stock',
        'tracks_inventory_by_unit',
        'image',
        'gallery',
        'barcode',
        'barcode_type',
        'parent_category_id',
        'brand_id',
        'colour_variant_group_id',
        'storage_variant_group_id',
        'is_active',
        'is_used',
        'specs',
    ];

    protected $casts = [
        'specs' => 'array',
        'gallery' => 'array',
        'price' => 'float',
        'cost_price' => 'float',
        'stock' => 'integer',
        'tracks_inventory_by_unit' => 'boolean',
    ];

    protected static function booted()
    {
        static::creating(function ($product) {

            // Auto-generate barcode only if not provided
            if (empty($product->barcode)) {
                do {
                    $barcode = 'PRD-' . strtoupper(Str::random(8));
                } while (self::where('barcode', $barcode)->exists());

                $product->barcode = $barcode;
                $product->barcode_type = 'code128';
            }

            if (empty($product->sku)) {
                $product->sku = 'PRD-' . str_pad(
                    (string)(Product::max('id') + 1),
                    6,
                    '0',
                    STR_PAD_LEFT
                );
            }

        });
    }

    public function getSpecsAttribute($value)
    {
        $spec = is_string($value) ? json_decode($value, true) : $value;

        if (! is_array($spec)) {
            return [];
        }

        $normalized = [];

        foreach ($spec as $key => $val) {
            $cleanKey = self::normalizeSpecificationKey((string) $key);
            $normalized[$cleanKey] = is_scalar($val)
                ? self::normalizeSpecificationValue((string) $val, $cleanKey)
                : $val;
        }

        return $normalized;
    }

    public function setSpecsAttribute($value): void
    {
        $specs = is_string($value) ? json_decode($value, true) : $value;

        if (! is_array($specs)) {
            $this->attributes['specs'] = json_encode([]);

            return;
        }

        $normalized = [];

        foreach ($specs as $key => $specValue) {
            $cleanKey = self::normalizeSpecificationKey((string) $key);
            $normalized[$cleanKey] = is_scalar($specValue)
                ? self::normalizeSpecificationValue((string) $specValue, $cleanKey)
                : $specValue;
        }

        $this->attributes['specs'] = json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public static function storageSpecificationValue(mixed $specs): ?string
    {
        return self::specificationValue($specs, self::STORAGE_SPEC_KEYS);
    }

    public static function normalizeSpecificationKey(string $key): string
    {
        $key = strtoupper(trim(preg_replace('/\s+/', ' ', $key)));

        if (in_array($key, self::STORAGE_SPEC_KEYS, true)) {
            return 'STORAGE';
        }

        if (in_array($key, self::SCREEN_SIZE_SPEC_KEYS, true)) {
            return 'SCREENSIZE';
        }

        return $key;
    }

    public static function normalizeSpecificationValue(string $value, ?string $key = null): string
    {
        $value = trim((string) preg_replace('/\s+/u', ' ', $value));
        $key = $key ? self::normalizeSpecificationKey($key) : null;

        if (in_array($key, ['RAM', 'STORAGE'], true)
            && preg_match('/^(\d+(?:\.\d+)?)\s*(MB|GB|TB)$/i', $value, $matches)) {
            return $matches[1].' '.strtoupper($matches[2]);
        }

        if ($key === 'SCREENSIZE'
            && preg_match('/^(\d+(?:\.\d+)?)\s*(?:INCH(?:ES)?|")?$/i', $value, $matches)) {
            return $matches[1].' Inches';
        }

        return $value;
    }

    public static function specificationValue(mixed $specs, string|array $keys): ?string
    {
        if (! is_array($specs)) {
            return null;
        }

        $normalizedKeys = collect(is_array($keys) ? $keys : [$keys])
            ->map(fn ($key) => self::normalizeSpecificationKey((string) $key))
            ->unique();

        foreach ($specs as $key => $value) {
            $normalizedKey = self::normalizeSpecificationKey((string) $key);

            if (! $normalizedKeys->contains($normalizedKey) || ! is_scalar($value)) {
                continue;
            }

            $value = self::normalizeSpecificationValue((string) $value, $normalizedKey);

            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    public static function specificationOptions(iterable $specifications, string|array $keys): Collection
    {
        return collect($specifications)
            ->map(fn ($specs) => self::specificationValue($specs, $keys))
            ->filter()
            ->unique(fn ($value) => self::specificationComparisonValue((string) $value))
            ->sort(fn ($left, $right) => strnatcasecmp((string) $left, (string) $right))
            ->values();
    }

    public static function specificationComparisonValue(string $value, bool $stripScreenUnit = false): string
    {
        $value = Str::lower(trim($value));

        if ($stripScreenUnit) {
            $value = (string) preg_replace('/(?:inches?|\")$/i', '', $value);
        }

        return (string) preg_replace('/\s+/u', '', $value);
    }

    public function scopeWhereSpecification(
        Builder $query,
        string|array $jsonKeys,
        string|array $values,
        bool $stripScreenUnit = false
    ): Builder {
        $values = collect(is_array($values) ? $values : [$values])
            ->map(fn ($value) => self::specificationComparisonValue((string) $value, $stripScreenUnit))
            ->filter()
            ->unique()
            ->values();

        if ($values->isEmpty()) {
            return $query;
        }

        $keys = collect(is_array($jsonKeys) ? $jsonKeys : [$jsonKeys])
            ->map(fn ($key) => (string) $key)
            ->filter()
            ->unique()
            ->values();
        $driver = $query->getModel()->getConnection()->getDriverName();
        $jsonValue = match ($driver) {
            'sqlite' => 'CAST(json_extract(specs, ?) AS TEXT)',
            default => 'JSON_UNQUOTE(JSON_EXTRACT(specs, ?))',
        };
        $normalizedJsonValue = "LOWER(REPLACE(TRIM({$jsonValue}), ' ', ''))";

        if ($stripScreenUnit) {
            $normalizedJsonValue = "REPLACE(REPLACE(REPLACE({$normalizedJsonValue}, 'inches', ''), 'inch', ''), '\"', '')";
        }

        return $query->where(function (Builder $query) use ($keys, $normalizedJsonValue, $values) {
            foreach ($keys as $key) {
                $path = '$."'.str_replace('"', '\\"', $key).'"';

                foreach ($values as $value) {
                    $query->orWhereRaw("{$normalizedJsonValue} = ?", [$path, $value]);
                }
            }
        });
    }

    public function scopeWhereStorageSpecification(Builder $query, string|array $values): Builder
    {
        return $query->whereSpecification(self::STORAGE_SPEC_JSON_KEYS, $values);
    }

    public function scopeWhereRamSpecification(Builder $query, string|array $values): Builder
    {
        return $query->whereSpecification(self::RAM_SPEC_JSON_KEYS, $values);
    }

    public function scopeWhereProcessorSpecification(Builder $query, string|array $values): Builder
    {
        return $query->whereSpecification(self::PROCESSOR_SPEC_JSON_KEYS, $values);
    }

    public function scopeWhereScreenSizeSpecification(Builder $query, string|array $values): Builder
    {
        return $query->whereSpecification(self::SCREEN_SIZE_SPEC_JSON_KEYS, $values, true);
    }

    public function store()  { 
        return $this->belongsTo(Store::class); 
    }
    public function category() { 
        return $this->belongsTo(Category::class, 'category_id'); 
    }
    public function parentCategory() { 
        return $this->belongsTo(Category::class, 'parent_category_id');
    }
    public function orderItems() { 
        return $this->hasMany(OrderItem::class); 
    }
    public function brand() {
        return $this->belongsTo(Brand::class);
    }
    public function homeSections() {
        return $this->belongsToMany(HomeSection::class, 'home_section_product');
    }
    public function usedDeviceDetails() {
        return $this->hasOne(UsedDeviceDetail::class);
    }
    public function units() {
        return $this->hasMany(ProductUnit::class);
    }

}
