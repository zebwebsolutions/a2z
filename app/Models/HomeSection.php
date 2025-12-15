<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomeSection extends Model
{
    //
    protected $fillable = ['title', 'is_active', 'sort_order'];

    public function products() {
        return $this->belongsToMany(Product::class, 'home_section_product')
                    ->withPivot('order')
                    ->orderBy('home_section_product.order');
    }

    public function getOrderedProductsAttribute()
    {
        // Use the relation but ensure pivot ordering
        return $this->products()->orderBy('home_section_product.order')->get();
    }
}
