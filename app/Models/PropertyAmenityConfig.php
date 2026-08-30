<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyAmenityConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'room_type_id',
        'property_amenity_id',
    ];

    public function propertyAmenity()
    {
        return $this->belongsTo(PropertyAmenity::class);
    }
}
