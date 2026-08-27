<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    use HasFactory;

    protected $fillable = [
        'state_id',
        'name',
        'code',
        'status',
    ];

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function properties()
    {
        return $this->hasMany(Property::class);
    }
}