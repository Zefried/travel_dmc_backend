<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomConfiguration extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_type_id',
        'type',
        'name',
        'meal_code',
        'description',
        'extra_price',
        'status',
    ];

    public function roomType()
    {
        return $this->belongsTo(RoomType::class);
    }
}