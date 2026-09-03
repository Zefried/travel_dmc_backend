<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'hotel_admin_id',
        'type',
        'name',
        'model',
        'registration_no',
        'seating_capacity',
        'color',
        'driver_name',
        'driver_phone',
        'status',
    ];


    public function hotelAdmin()
    {
        return $this->belongsTo(User::class, 'hotel_admin_id');
    }
}