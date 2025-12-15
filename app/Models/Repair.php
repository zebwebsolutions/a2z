<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Repair extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'store_id',
        'customer_name',
        'customer_phone',
        'device_model',
        'imei',
        'problem_description',
        'total_cost',
        'status',
    ];

    public function salesman(){ 
        return $this->belongsTo(User::class, 'user_id'); 
    }
    public function store(){ 
        return $this->belongsTo(Store::class); 
    }
    public function parts(){ 
        return $this->hasMany(RepairPart::class); 
    }
}
