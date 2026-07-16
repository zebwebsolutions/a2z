<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

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

        if (!is_array($spec)) {
            return [];
        }

        $normalized = [];

        foreach ($spec as $key => $val) {
            $cleanKey = strtoupper(trim($key));    // Normalize key (RAM, STORAGE, PROCESSOR)
            $normalized[$cleanKey] = $val;
        }

        return $normalized;
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
