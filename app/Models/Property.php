<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Property extends Model
{
    use HasFactory;

    protected $fillable = [
        'hotel_admin_id',
        'name',
        'type',
        'star_rating',
        'country_id',
        'state_id',
        'city_id',
        'description',
        'address',
        'postal_code',
        'latitude',
        'longitude',
        'phone',
        'alternative_phone',
        'email',
        'website',
        'status',
    ];

    // public function country()
    // {
    //     return $this->belongsTo(Country::class);
    // }

    // public function state()
    // {
    //     return $this->belongsTo(State::class);
    // }

    // public function city()
    // {
    //     return $this->belongsTo(City::class);
    // }

    public function roomTypes()
    {
        return $this->hasMany(RoomType::class);
    }

    public function hotelAdmin()
    {
        return $this->belongsTo(User::class, 'hotel_admin_id');
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }
}