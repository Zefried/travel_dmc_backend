<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyAmenityConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'property_amenity_id',
    ];
}



