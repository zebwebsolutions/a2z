<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{

    protected $fillable = [
        'name',
        'slug',
        'description',
        'image',
        'is_active',
        'show_in_menu',
        'parent_id', 
    ];

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function brands()
    {
        return $this->belongsToMany(Brand::class);  
    }

    public function autoBrands() {
        return Brand::whereIn('id', 
            $this->products()->distinct()->pluck('brand_id')
        );
    }

    public function showBrandsInMenu() {
        return in_array($this->slug, ['phones', 'tablets', 'laptops', 'smart-watches']);
    }

}
