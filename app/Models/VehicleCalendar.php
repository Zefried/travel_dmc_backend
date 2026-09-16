<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehicleCalendar extends Model
{
    protected $fillable = [
        'vehicle_id',
        'vehicle_admin_id',
        'title',
        'status',
        'start_date',
        'end_date',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
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
