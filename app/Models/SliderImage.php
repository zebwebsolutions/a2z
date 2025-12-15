<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SliderImage extends Model
{
    protected $fillable = [
        'slider_id', 'image', 'heading', 'description', 
        'button_text', 'button_link', 'sort_order'
    ];

    public function slider()
    {
        return $this->belongsTo(Slider::class);
    }
}

