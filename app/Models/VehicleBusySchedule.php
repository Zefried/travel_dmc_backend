<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleBusySchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id',
        'vehicle_admin_id',
        'start_date',
        'end_date',
        'reason',
        'note',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function vehicleAdmin()
    {
        return $this->belongsTo(User::class, 'vehicle_admin_id');
    }
}
