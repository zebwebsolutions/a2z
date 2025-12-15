<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RepairPart extends Model
{
    use HasFactory;

    protected $fillable = [
        'repair_id',
        'part_name',
        'cost',
        'quantity',
    ];

    public function repair(){ 
        return $this->belongsTo(Repair::class); 
    }
}
