<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomType extends Model
{
    use HasFactory;

   
    protected $fillable = [
        'property_id',
        'name',
        'type',
        'bedroom',
        'size',
        'size_unit',
        'max_adults',
        'max_children',
        'max_occupancy',
        'description',
        'view',
        'default_bed_type',
        'default_bed_quantity',
        'status',
        'base_price',
    ];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function roomConfigurations()
    {
        return $this->hasMany(RoomConfiguration::class);
    }

    // public function rooms()
    // {
    //     return $this->hasMany(Room::class);
    // }

}