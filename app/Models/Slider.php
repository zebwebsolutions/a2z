<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Slider extends Model
{
    protected $fillable = ['name', 'title', 'is_active'];

    public function images()
    {
        return $this->hasMany(SliderImage::class)->orderBy('sort_order');
    }
}
