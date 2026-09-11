<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'activity_id',
        'name',
        'transfer_type',
        'transfer_duration',
        'transfer_duration_unit',
        'transfer_price',
        'pickup_type',
        'pickup_description',
        'status',
    ];

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }
}